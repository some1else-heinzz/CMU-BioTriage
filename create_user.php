<?php
// Command line only. Usage: php create_user.php "Full Name" email@example.com staff|admin
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
require __DIR__ . '/includes/db.php';
$name = trim($argv[1] ?? '');
$email = mb_strtolower(trim($argv[2] ?? ''), 'UTF-8');
$role = strtolower(trim($argv[3] ?? 'staff'));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['staff', 'admin'], true))
    exit("Usage: php create_user.php \"Full Name\" email@example.com staff|admin\n");
echo 'Password (at least 10 characters; it will be visible as you type): ';
$pw = trim((string)fgets(STDIN));
if (strlen($pw) < 10) exit("Password is too short.\n");
try {
    db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
} catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) exit("A user with that email already exists.\n");
    throw $e;
}
echo "User created.\n";
