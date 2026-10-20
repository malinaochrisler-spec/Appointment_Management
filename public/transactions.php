<?php
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Appointment.php';
require_once '../includes/layout.php';
require_auth();

$model = new Appointment(Database::connection());
$stats = $model->transactionStats();
$rows = $model->getTransactions();

page_header('Transaction Log');
?>

<p class="page-intro">Every appointment create, update, and delete runs inside a database transaction and is recorded below.</p>

<div class="stats">
    <div class="card stat">
        <small>Total transactions</small>
        <strong><?= e((string)($stats['total'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Created</small>
        <strong><?= e((string)($stats['created'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Updated</small>
        <strong><?= e((string)($stats['updated'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Deleted</small>
        <strong><?= e((string)($stats['deleted'] ?? 0)) ?></strong>
    </div>
</div>

<div class="card table-wrap">
    <?php if (!$rows): ?>
        <div class="empty">No transactions recorded yet. Add, edit, or delete an appointment to create one.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Appointment</th>
                    <th>Action</th>
                    <th>Performed by</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>#<?= (int)$row['id'] ?></td>
                        <td>
                            <strong><?= e($row['reference_no']) ?></strong><br>
                            <small>Appointment #<?= (int)$row['appointment_id'] ?></small>
                        </td>
                        <td><span class="badge <?= e($row['action_type']) ?>"><?= e($row['action_type']) ?></span></td>
                        <td><?= e($row['performed_by_name'] ?? 'System') ?></td>
                        <td><?= e($row['details'] ?? '') ?></td>
                        <td><?= e(date('M j, Y g:i A', strtotime($row['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php page_footer(); ?>
