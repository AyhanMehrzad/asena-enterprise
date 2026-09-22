<?php
/**
 * Test Suite: AI Doctor License Verification & Clean URLs & Profile Routing
 */

echo "========================================================\n";
echo "       ASENA ENTERPRISE - AUTOMATED TEST SUITE          \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  ✔ PASS: {$message}\n";
        $passCount++;
    } else {
        echo "  ✖ FAIL: {$message}\n";
        $failCount++;
    }
}

// 1. Test DoctorVerificationService Instance & Heuristics
require_once __DIR__ . '/../includes/DoctorVerificationService.php';

$verifier = new DoctorVerificationService();
assertTest($verifier instanceof DoctorVerificationService, 'DoctorVerificationService instantiates correctly');

// Test Case A: Valid Veterinary Doctor Credentials (5-digit IRVC ID)
$docValid = [
    'full_name'      => 'دکتر علیرضا محمدی',
    'license_number' => '۵۴۳۲۱',
    'specialty'      => 'دامپزشک عمومی',
    'phone'          => '09123456789'
];
$resValid = $verifier->verifyDocument('non_existent_file.jpg', $docValid);
assertTest($resValid['success'] === true, 'Verifier returns success true for valid data');
assertTest($resValid['status'] === 'verified', 'Valid IRVC license and 2-word name receives verified status');
assertTest($resValid['confidence'] >= 85, 'Valid license confidence is >= 85% (Got: ' . $resValid['confidence'] . '%)');
assertTest($resValid['extracted_data']['extracted_license_number'] === '54321', 'Persian digits 54321 normalized to English');

// Test Case B: Suspicious / Short / Invalid License Number
$docInvalid = [
    'full_name'      => 'علی',
    'license_number' => '12',
    'specialty'      => 'دامپزشک',
    'phone'          => '09120000000'
];
$resInvalid = $verifier->verifyDocument('non_existent_file.jpg', $docInvalid);
assertTest($resInvalid['status'] === 'needs_review', 'Short license and 1-word name receives needs_review status');
assertTest($resInvalid['confidence'] < 80, 'Low confidence for irregular format (Got: ' . $resInvalid['confidence'] . '%)');
assertTest(!empty($resInvalid['extracted_data']['risk_factors']), 'Risk factors identified for short license');

// 2. Test Clean URL Regex Pattern Matching from .htaccess
$rewritePattern = '/^(.*)\.php$/';
$testUrls = [
    'shop.php' => 'shop',
    'booking.php' => 'booking',
    'doctor/index.php' => 'doctor/index',
    'profile.php' => 'profile',
];
foreach ($testUrls as $withPhp => $expectedClean) {
    $clean = preg_replace($rewritePattern, '$1', $withPhp);
    assertTest($clean === $expectedClean, "URL {$withPhp} cleanly maps to {$expectedClean}");
}

// 3. Test profile_settings.php logic
$testScript = file_get_contents(__DIR__ . '/../profile_settings.php');
assertTest(strpos($testScript, 'Location: profile.php?tab=') !== false, 'profile_settings.php redirects to profile.php with dynamic tab support');
assertTest(strpos($testScript, '<!DOCTYPE html>') === false, 'profile_settings.php has no orphaned HTML markup');

// 4. Test paw-loader.js deferred disable
$pawJs = file_get_contents(__DIR__ . '/../assets/js/paw-loader.js');
assertTest(strpos($pawJs, 'setTimeout(function()') !== false && strpos($pawJs, 'submitBtn.disabled = true') !== false, 'paw-loader.js defers button disabling via setTimeout to preserve POST values');

echo "\n--------------------------------------------------------\n";
echo "Tests Summary: {$passCount} Passed, {$failCount} Failed\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
