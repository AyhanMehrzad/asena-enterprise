<?php
/**
 * ASENA Enterprise - Test Website Navigation Special Role Visibility
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/AuthGuard.php';

echo "=== Testing Website Link Role-Based Visibility ===\n";

$passCount = 0;
function testAssert(bool $condition, string $msg): void {
    global $passCount;
    if ($condition) {
        echo "  [PASS] $msg\n";
        $passCount++;
    } else {
        echo "  [FAIL] $msg\n";
        exit(1);
    }
}

// 1. Test AuthGuard::hasSpecialRole logic
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Case A: Guest
unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['role']);
testAssert(!AuthGuard::hasSpecialRole(), "Guest hasSpecialRole is false");

// Case B: Regular pet-owner user
$_SESSION['user_id'] = 9999;
$_SESSION['user_role'] = 'user';
testAssert(!AuthGuard::hasSpecialRole(), "Regular 'user' hasSpecialRole is false");

// Case C: Special roles
$specialRoles = ['admin', 'superadmin', 'doctor', 'organization', 'organization_manager', 'pharmacist', 'pharmacy', 'seller', 'clinic'];
foreach ($specialRoles as $role) {
    $_SESSION['user_role'] = $role;
    testAssert(AuthGuard::hasSpecialRole(), "Role '$role' hasSpecialRole is true");
}

// 2. Test Header HTML rendering for Guest
unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['role']);
$cmdGuest = "/opt/lampp/bin/php -r '\$_SESSION = []; ob_start(); include \"" . __DIR__ . "/../includes/header.php\"; echo ob_get_clean();'";
$guestHtml = shell_exec($cmdGuest);
testAssert(strpos($guestHtml, 'سفارش سایت') === false, "Guest header does NOT contain 'سفارش سایت'");
testAssert(strpos($guestHtml, 'سفارش وب‌سایت اختصاصی') === false, "Guest header does NOT contain 'سفارش وب‌سایت اختصاصی'");

// 3. Test Header HTML rendering for Regular 'user'
$cmdUser = "/opt/lampp/bin/php -r '\$_SESSION = [\"user_id\" => 123, \"user_role\" => \"user\"]; ob_start(); include \"" . __DIR__ . "/../includes/header.php\"; echo ob_get_clean();'";
$userHtml = shell_exec($cmdUser);
testAssert(strpos($userHtml, 'سفارش سایت') === false, "Regular user header does NOT contain 'سفارش سایت'");
testAssert(strpos($userHtml, 'سفارش وب‌سایت اختصاصی') === false, "Regular user header does NOT contain 'سفارش وب‌سایت اختصاصی'");

// 4. Test Header HTML rendering for Doctor
$cmdDoctor = "/opt/lampp/bin/php -r '\$_SESSION = [\"user_id\" => 456, \"user_role\" => \"doctor\"]; ob_start(); include \"" . __DIR__ . "/../includes/header.php\"; echo ob_get_clean();'";
$doctorHtml = shell_exec($cmdDoctor);
testAssert(strpos($doctorHtml, 'سفارش سایت') !== false, "Doctor header DOES contain 'سفارش سایت'");
testAssert(strpos($doctorHtml, 'سفارش وب‌سایت اختصاصی') !== false, "Doctor header DOES contain 'سفارش وب‌سایت اختصاصی'");

// 5. Test Footer HTML rendering for Guest vs Doctor
$cmdFooterGuest = "/opt/lampp/bin/php -r '\$_SESSION = []; ob_start(); include \"" . __DIR__ . "/../includes/footer.php\"; echo ob_get_clean();'";
$footerGuestHtml = shell_exec($cmdFooterGuest);
testAssert(strpos($footerGuestHtml, 'سفارش وب‌سایت اختصاصی') === false, "Guest footer does NOT contain 'سفارش وب‌سایت اختصاصی'");

$cmdFooterDoctor = "/opt/lampp/bin/php -r '\$_SESSION = [\"user_id\" => 456, \"user_role\" => \"doctor\"]; ob_start(); include \"" . __DIR__ . "/../includes/footer.php\"; echo ob_get_clean();'";
$footerDoctorHtml = shell_exec($cmdFooterDoctor);
testAssert(strpos($footerDoctorHtml, 'سفارش وب‌سایت اختصاصی') !== false, "Doctor footer DOES contain 'سفارش وب‌سایت اختصاصی'");

echo "=== All $passCount Website Visibility Tests Passed Successfully! ===\n";
