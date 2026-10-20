<?php
require_once __DIR__ . '/../config/config.php';

function page_header(string $title): void {
    $user = auth_user();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12263f">
    <title><?= e($title) ?> · <?= APP_NAME ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css?v=4">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="topbar">
        <a class="brand" href="index.php">
            <span class="brand-mark">AD</span>
            <span><?= APP_NAME ?></span>
        </a>

        <?php if ($user): ?>
        <nav aria-label="Main navigation">
            <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>" <?= $current === 'index.php' ? 'aria-current="page"' : '' ?>>Appointments</a>
            <a href="transactions.php" class="<?= $current === 'transactions.php' ? 'active' : '' ?>" <?= $current === 'transactions.php' ? 'aria-current="page"' : '' ?>>Transactions</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="setup.php" class="<?= $current === 'setup.php' ? 'active' : '' ?>" <?= $current === 'setup.php' ? 'aria-current="page"' : '' ?>>Admin Setup</a>
            <?php endif; ?>
            <a class="user" href="logout.php">Sign out (<?= e($user['full_name']) ?>)</a>
        </nav>
        <?php endif; ?>
    </header>

    <main id="main-content" class="container">
        <div class="heading">
            <div>
                <p class="eyebrow">APPOINTMENT OPERATIONS</p>
                <h1><?= e($title) ?></h1>
            </div>
        </div>

        <?php if ($flash = get_flash()): ?>
            <div class="alert <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
        <?php endif; ?>
<?php
}

function page_footer(): void {
?>
    </main>
    <footer>Appointment Desk · <?= date('Y') ?> · PHP OOP + PDO academic system</footer>
</body>
</html>
<?php
}
