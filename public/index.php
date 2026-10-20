<?php
require_once '../config/config.php';
require_auth();
require_once '../classes/Appointment.php';
require_once '../includes/layout.php';

$db = Database::connection();
$model = new Appointment($db);
$error = '';
$edit = null;

function validate_appointment(): array {
    $d = [
        'reference_no'     => trim($_POST['reference_no'] ?? ''),
        'client_name'      => trim($_POST['client_name'] ?? ''),
        'client_email'     => trim($_POST['client_email'] ?? ''),
        'client_phone'     => trim($_POST['client_phone'] ?? ''),
        'appointment_date' => $_POST['appointment_date'] ?? '',
        'appointment_time' => $_POST['appointment_time'] ?? '',
        'service_type'     => trim($_POST['service_type'] ?? ''),
        'status'           => $_POST['status'] ?? 'Scheduled',
        'notes'            => trim($_POST['notes'] ?? '')
    ];

    $errors = [];
    $required_fields = ['reference_no', 'client_name', 'client_phone', 'appointment_date', 'appointment_time', 'service_type'];

    foreach ($required_fields as $k) {
        if ($d[$k] === '') {
            $errors[] = 'All required fields must be completed.';
        }
    }

    if (!filter_var($d['client_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid client email.';
    }

    if (!in_array($d['status'], ['Scheduled', 'Completed', 'Cancelled'], true)) {
        $errors[] = 'Invalid status.';
    }

    if (strlen($d['reference_no']) > 30 || strlen($d['client_name']) > 120) {
        $errors[] = 'One or more fields are too long.';
    }

    return [$d, array_unique($errors)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $model->deleteAppointment((int)$_POST['id'], (int)auth_user()['id']);
            flash('success', 'Appointment deleted. Transaction recorded.');
            redirect('index.php');
        }

        [$data, $errors] = validate_appointment();

        if ($errors) {
            $error = implode(' ', $errors);
        } else if ($action === 'create') {
            $model->addAppointment($data, (int)auth_user()['id']);
            flash('success', 'Appointment added successfully. Transaction recorded.');
            redirect('index.php');
        } else if ($action === 'update') {
            $model->updateAppointment((int)$_POST['id'], $data, (int)auth_user()['id']);
            flash('success', 'Appointment updated successfully. Transaction recorded.');
            redirect('index.php');
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'The operation could not be completed. Check for duplicate reference numbers.';
    }
}

if (isset($_GET['edit'])) {
    $edit = $model->getAppointment((int)$_GET['edit']);
}

$search = trim($_GET['q'] ?? '');
$rows = $model->getAppointments($search);
$stats = $model->stats();

page_header($edit ? 'Edit appointment' : 'Appointments');
?>

<div class="stats">
    <div class="card stat">
        <small>Total records</small>
        <strong><?= e((string)($stats['total'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Scheduled</small>
        <strong><?= e((string)($stats['scheduled'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Completed</small>
        <strong><?= e((string)($stats['completed'] ?? 0)) ?></strong>
    </div>
    <div class="card stat">
        <small>Cancelled</small>
        <strong><?= e((string)($stats['cancelled'] ?? 0)) ?></strong>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($edit || isset($_GET['new'])): ?>
    <div class="card" style="margin-bottom:22px">
        <h2><?= $edit ? 'Update record' : 'Add new appointment' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">

            <div class="form-grid">
                <div class="field">
                    <label>Reference number *</label>
                    <input class="input" name="reference_no" required maxlength="30" autocomplete="off" value="<?= e($edit['reference_no'] ?? 'APT-' . date('Ymd-His')) ?>">
                </div>

                <div class="field">
                    <label>Service type *</label>
                    <input class="input" name="service_type" required list="service-options" value="<?= e($edit['service_type'] ?? '') ?>" placeholder="Consultation">
                    <datalist id="service-options">
                        <option>Consultation</option>
                        <option>Follow-up</option>
                        <option>Checkup</option>
                        <option>Meeting</option>
                        <option>Assessment</option>
                    </datalist>
                </div>

                <div class="field">
                    <label>Client name *</label>
                    <input class="input" name="client_name" autocomplete="name" required value="<?= e($edit['client_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Client email *</label>
                    <input class="input" type="email" name="client_email" autocomplete="email" spellcheck="false" required value="<?= e($edit['client_email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Client phone *</label>
                    <input class="input" type="tel" name="client_phone" autocomplete="tel" inputmode="tel" required value="<?= e($edit['client_phone'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Status *</label>
                    <select name="status">
                        <option <?= ($edit['status'] ?? 'Scheduled') === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option <?= ($edit['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option <?= ($edit['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="field">
                    <label>Date *</label>
                    <input class="input" type="date" name="appointment_date" required value="<?= e($edit['appointment_date'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Time *</label>
                    <input class="input" type="time" name="appointment_time" required value="<?= e(substr($edit['appointment_time'] ?? '', 0, 5)) ?>">
                </div>

                <div class="field full">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Optional details..."><?= e($edit['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn"><?= $edit ? 'Save changes' : 'Add appointment' ?></button>
                <a class="btn secondary" href="index.php">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="toolbar">
        <form class="search" method="get">
            <input class="input" type="search" name="q" aria-label="Search appointments" value="<?= e($search) ?>" placeholder="Search reference, client, service, status...">
            <button class="btn secondary">Search</button>
        </form>
        <a class="btn" href="?new=1">+ Add appointment</a>
    </div>

    <div class="card table-wrap">
        <?php if (!$rows): ?>
            <div class="empty">No appointments found. Add your first record to begin.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Schedule</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['reference_no']) ?></strong><br>
                                <small><?= e($r['client_email']) ?></small>
                            </td>
                            <td>
                                <?= e($r['client_name']) ?><br>
                                <small><?= e($r['client_phone']) ?></small>
                            </td>
                            <td>
                                <?= e(date('M j, Y', strtotime($r['appointment_date']))) ?><br>
                                <small><?= e(date('g:i A', strtotime($r['appointment_time']))) ?></small>
                            </td>
                            <td><?= e($r['service_type']) ?></td>
                            <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this appointment permanently?')">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php page_footer(); ?>