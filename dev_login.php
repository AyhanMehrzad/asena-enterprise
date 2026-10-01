<?php
/**
 * ASENA Enterprise - Local Dev Quick Access
 * Automatically logs in as test doctor and opens Site Builder Studio
 */
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden: Local Dev Only');
}

require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE id = 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['password_hash'] = hash('sha256', $user['password'] ?? '');
    $_SESSION['contract_accepted_version'] = 'v2.0-2026';
}

$target = $_GET['target'] ?? 'doctor/site_builder.php';
header("Location: " . $target);
exit;
