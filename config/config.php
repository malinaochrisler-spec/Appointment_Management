<?php
declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
    || str_starts_with($host, 'localhost:')
    || str_starts_with($host, '127.0.0.1:')
    || str_starts_with($host, '[::1]');

if (!$isHttps && !$isLocalHost && PHP_SAPI !== 'cli') {
    $target = 'https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $target, true, 301);
    exit;
}

session_start();

if ($isHttps) {
    header('Strict-Transport-Security: max-age=2592000');
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'appointment_management';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Appointment Desk';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid request token. Please go back and try again.');
    }
}

function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function get_flash(): ?array { $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $flash; }
function auth_user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): void { if (!auth_user()) redirect('login.php'); }
function require_admin(): void { require_auth(); if (($_SESSION['user']['role'] ?? '') !== 'admin') { http_response_code(403); exit('Administrator access required.'); } }
