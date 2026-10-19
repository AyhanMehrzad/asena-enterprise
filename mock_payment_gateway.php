<?php
/**
 * ASENA Payment Gateway - Mock Simulator Decommissioned
 * All transactions must be routed through official live payment gateways (Zibal / Shaparak).
 */

require_once __DIR__ . '/includes/functions.php';

// Decommissioned notice redirect to real payment or cart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['profile_error'] = 'درگاه شبیه‌ساز آزمایشی غیرفعال گردیده است. تراکنش‌ها مستقیماً از طریق درگاه رسمی شاپرک (زیبال) پردازش می‌شوند.';

$target = (!empty($_SESSION['pending_order'])) ? 'payment.php' : 'cart.php';
header("Location: {$target}");
exit;
