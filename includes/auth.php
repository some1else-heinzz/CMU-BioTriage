<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_staff(): bool { $u = user(); return $u && in_array($u['role'], ['staff', 'admin'], true); }
function require_staff(): void {
    if (!is_staff()) { header('Location: ' . BASE . '/login.php'); exit; }
}
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('This form expired. Go back, reload the page, and try again.');
    }
}
function attempt_login(string $email, string $pw): bool {
    $email = mb_strtolower(trim($email), 'UTF-8');
    $s = db()->prepare('SELECT id, name, role, password_hash FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    if ($u && password_verify($pw, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role']];
        return true;
    }
    usleep(400000); // slow down guessing
    return false;
}
