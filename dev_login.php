<?php
/**
 * ASENA Enterprise - Local Development Quick Role Switcher
 * Only accessible on local development environment (127.0.0.1 / localhost)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$clientIp = get_client_ip();
if (!in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Forbidden: Dev login only permitted on localhost');
}

$role = $_GET['role'] ?? 'doctor';
$to = $_GET['to'] ?? 'doctor/site_builder.php';

// Prepare user record in DB
$user = $pdo->query("SELECT * FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $pdo->exec("INSERT INTO users (id, name, role, email, phone) VALUES (1, 'دکتر رامین علوی', '$role', 'doctor@asena.company', '09123456789')");
} else {
    $pdo->prepare("UPDATE users SET role = ?, name = ? WHERE id = 1")->execute([$role, $role === 'doctor' ? 'دکتر رامین علوی' : 'مدیر مرکز درمانی رازی']);
}

// Ensure session is set
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['role'] = $role;
$_SESSION['user_role'] = $role;
$_SESSION['user_name'] = ($role === 'doctor' ? 'دکتر رامین علوی' : 'مدیر کلینیک رازی');
$_SESSION['user_email'] = 'doctor@asena.company';
$_SESSION['last_activity'] = time();

header("Location: " . $to);
exit;
