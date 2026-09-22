<?php
/**
 * ASENA Enterprise - Profile Settings Router
 * Gracefully routes legacy settings requests to the unified Profile Hub.
 */
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['settings_success'])) {
    $_SESSION['profile_success'] = $_SESSION['settings_success'];
    unset($_SESSION['settings_success']);
}
if (isset($_SESSION['settings_error'])) {
    $_SESSION['profile_error'] = $_SESSION['settings_error'];
    unset($_SESSION['settings_error']);
}

$tab = trim($_GET['tab'] ?? '');
$validTabs = ['overview', 'personal-info', 'addresses', 'pets', 'appointments', 'orders', 'prescriptions', 'subscriptions', 'wallet'];

if (!empty($tab) && in_array($tab, $validTabs)) {
    header("Location: profile.php?tab=" . urlencode($tab) . "#" . urlencode($tab));
} else {
    header("Location: profile.php?tab=personal-info#personal-info");
}
exit;