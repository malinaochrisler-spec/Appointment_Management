<?php
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../includes/layout.php';

$db = Database::connection();
$count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($count > 0) {
    require_admin();
}

$error = '';
$done = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($name === '' || !$email || strlen($password) < 8) {
        $error = 'Provide a name, valid email, and password of at least 8 characters.';
    } else {
        try {
            $s = $db->prepare('INSERT INTO users(full_name, email, password_hash, role) VALUES(:name, :email, :hash, :role)');
            $s->execute([
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin'
            ]);
            $done = 'Administrator created. You can now sign in.';
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'Could not create account. The email may already exist.';
        }
    }
}

page_header('Administrator setup');
?>

<div class="card" style="max-width:560px">
    <p>Use this one-time page after importing the database to create the initial administrator. Passwords are stored with PHP password hashing.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($done): ?>
        <div class="alert success"><?= e($done) ?></div>
        <a class="btn" href="login.php">Go to sign in</a>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="field" style="margin-bottom:16px">
                <label>Full name</label>
                <input class="input" name="full_name" autocomplete="name" required>
            </div>

            <div class="field" style="margin-bottom:16px">
                <label>Email</label>
                <input class="input" type="email" name="email" autocomplete="email" spellcheck="false" required>
            </div>

            <div class="field">
                <label>Password</label>
                <input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <small class="hint">Minimum 8 characters.</small>
            </div>

            <div class="form-actions">
                <button class="btn">Create administrator</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php page_footer(); ?>