<?php
/**
 * ASENA Enterprise - Quick Role Switcher & Direct Panel Access
 * Allows instantaneous role-based authentication for dev, staging, and administrative testing.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower(trim($_GET['role'] ?? 'admin'));

if ($role === 'logout' || $role === 'public' || $role === 'guest') {
    unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['role'], $_SESSION['name']);
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';

// Role mappings
$roleMap = [
    'admin'        => ['phone' => '09123456789', 'target' => 'admin/index.php'],
    'doctor'       => ['phone' => '09129972056', 'target' => 'doctor/index.php'],
    'pharmacist'   => ['phone' => '09120000004', 'target' => 'pharmacist/index.php'],
    'seller'       => ['phone' => '09120000003', 'target' => 'seller/index.php'],
    'organization' => ['phone' => '09122193637', 'target' => 'organization/index.php'],
    'user'         => ['phone' => '09000000001', 'target' => 'profile.php'],
];

if (!isset($roleMap[$role])) {
    die("Invalid role specified. Supported roles: " . implode(', ', array_keys($roleMap)));
}

$targetData = $roleMap[$role];

// 1. Try finding by dedicated test phone
$stmt = $pdo->prepare("SELECT id, name, phone, role FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$targetData['phone']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Fallback: find any user with that role
if (!$user) {
    $stmtRole = $pdo->prepare("SELECT id, name, phone, role FROM users WHERE role = ? ORDER BY id ASC LIMIT 1");
    $stmtRole->execute([$role]);
    $user = $stmtRole->fetch(PDO::FETCH_ASSOC);
}

if ($user) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['name']      = $user['name'] ?? '';

    header("Location: " . $targetData['target']);
    exit;
} else {
    die("No user found in database for role: " . htmlspecialchars($role));
}
