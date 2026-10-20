<?php
declare(strict_types=1);
require_once __DIR__ . '/Database.php';

final class Appointment {
    public function __construct(private PDO $db) {}

    /**
     * Transaction 1: insert the appointment and its transaction-log row atomically.
     */
    public function addAppointment(array $data, ?int $createdBy): int {
        $this->db->beginTransaction();
        try {
            $sql = 'INSERT INTO appointments (reference_no, client_name, client_email, client_phone, appointment_date, appointment_time, service_type, status, notes, created_by) VALUES (:reference_no,:client_name,:client_email,:client_phone,:appointment_date,:appointment_time,:service_type,:status,:notes,:created_by)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([...$data, 'created_by' => $createdBy]);

            $id = (int)$this->db->lastInsertId();
            $this->recordTransaction($id, $data['reference_no'], 'Created', $createdBy, 'Appointment created');
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getAppointments(string $search = '', int $limit = 100): array {
        if ($search === '') {
            return $this->db->query('SELECT a.*, u.full_name AS creator FROM appointments a LEFT JOIN users u ON u.id=a.created_by ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT '.(int)$limit)->fetchAll();
        }
        return $this->searchAppointments($search, $limit);
    }

    public function getAppointment(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM appointments WHERE id=:id'); $stmt->execute(['id'=>$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Transaction 2: lock the existing row, update it, and write the log entry.
     */
    public function updateAppointment(int $id, array $data, ?int $performedBy = null): bool {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT reference_no FROM appointments WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $id]);
            if (!$lock->fetch()) {
                $this->db->rollBack();
                return false;
            }

            $sql = 'UPDATE appointments SET reference_no=:reference_no, client_name=:client_name, client_email=:client_email, client_phone=:client_phone, appointment_date=:appointment_date, appointment_time=:appointment_time, service_type=:service_type, status=:status, notes=:notes WHERE id=:id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([...$data, 'id'=>$id]);

            $this->recordTransaction($id, $data['reference_no'], 'Updated', $performedBy, 'Appointment updated');
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Transaction 3: lock the row, delete it, and preserve the audit log entry.
     */
    public function deleteAppointment(int $id, ?int $performedBy = null): bool {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT reference_no FROM appointments WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $id]);
            $row = $lock->fetch();
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare('DELETE FROM appointments WHERE id=:id');
            $stmt->execute(['id'=>$id]);

            $this->recordTransaction($id, $row['reference_no'], 'Deleted', $performedBy, 'Appointment deleted');
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function searchAppointments(string $keyword, int $limit = 100): array {
        $term = '%'.trim($keyword).'%';
        $stmt = $this->db->prepare('SELECT a.*, u.full_name AS creator FROM appointments a LEFT JOIN users u ON u.id=a.created_by WHERE a.reference_no LIKE :term OR a.client_name LIKE :term OR a.client_email LIKE :term OR a.client_phone LIKE :term OR a.service_type LIKE :term OR a.status LIKE :term OR a.notes LIKE :term ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT '.(int)$limit);
        $stmt->execute(['term'=>$term]); return $stmt->fetchAll();
    }

    public function stats(): array {
        return $this->db->query("SELECT COUNT(*) total, SUM(status='Scheduled') scheduled, SUM(status='Completed') completed, SUM(status='Cancelled') cancelled FROM appointments")->fetch();
    }

    public function transactionStats(): array {
        return $this->db->query("SELECT COUNT(*) total, SUM(action_type='Created') created, SUM(action_type='Updated') updated, SUM(action_type='Deleted') deleted FROM appointment_transactions")->fetch();
    }

    public function getTransactions(int $limit = 100): array {
        $stmt = $this->db->prepare('SELECT t.*, u.full_name AS performed_by_name FROM appointment_transactions t LEFT JOIN users u ON u.id = t.performed_by ORDER BY t.created_at DESC, t.id DESC LIMIT '.(int)$limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function recordTransaction(int $appointmentId, string $referenceNo, string $action, ?int $userId, string $details): void {
        $stmt = $this->db->prepare('INSERT INTO appointment_transactions (appointment_id, reference_no, action_type, performed_by, details) VALUES (:appointment_id, :reference_no, :action_type, :performed_by, :details)');
        $stmt->execute([
            'appointment_id' => $appointmentId,
            'reference_no' => $referenceNo,
            'action_type' => $action,
            'performed_by' => $userId,
            'details' => $details,
        ]);
    }
}
