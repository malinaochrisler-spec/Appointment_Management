<?php
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../includes/layout.php';

if (auth_user()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        try {
            $s = Database::connection()->prepare('SELECT * FROM users WHERE email = :email');
            $s->execute(['email' => $email]);
            $u = $s->fetch();

            if ($u && password_verify($password, $u['password_hash'])) {
                session_regenerate_id(true);
                unset($u['password_hash']);
                $_SESSION['user'] = $u;
                redirect('index.php');
            }

            $error = 'The email or password is incorrect.';
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'Unable to sign in right now.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12263f">
    <title>Sign in · <?= APP_NAME ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css?v=4">
</head>
<body>
    <main class="auth-wrap">
        <div class="brand" style="color: var(--navy); font-size: 20px;">
            <span class="brand-mark">AD</span><?= APP_NAME ?>
        </div>

        <div class="card auth-card">
            <p class="eyebrow">SECURE ACCESS</p>
            <h1>Sign in</h1>
            <p>Manage your appointment records from one workspace.</p>
            <p class="auth-note">Academic project demo. Accounts are created by the administrator; there is no public sign-up.</p>

            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label>Email address</label>
                    <input class="input" type="email" name="email" autocomplete="username" spellcheck="false" required autofocus>
                </div>

                <div class="field">
                    <label>Password</label>
                    <input class="input" type="password" name="password" autocomplete="current-password" required>
                </div>

                <button class="btn" style="width: 100%;">Sign in</button>
            </form>
        </div>

        <p class="auth-foot">Secure access · <?= APP_NAME ?></p>
    </main>
</body>
</html>