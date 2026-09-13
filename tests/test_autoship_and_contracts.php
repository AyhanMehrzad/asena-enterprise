<?php
/**
 * ASENA Enterprise - Test Suite: Autoship Inventory Authentication & Electronic Contracts
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ContractService.php';
require_once __DIR__ . '/../includes/AutoshipService.php';
require_once __DIR__ . '/../includes/App.php';

echo "=== ASENA Enterprise: Autoship & Contract Test Suite ===\n\n";

$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $testName) {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo "✅ PASS: $testName\n";
    } else {
        echo "❌ FAIL: $testName\n";
    }
}

// ── 1. Test ContractService ──────────────────────────────────────────────────
echo "--- 1. Testing ContractService ---\n";
$contractService = App::contract();

// Roles verification
$roles = ['user', 'doctor', 'pharmacist', 'seller', 'organization'];
foreach ($roles as $r) {
    $cData = ContractService::getContractData($r);
    assertTest(!empty($cData['title']) && !empty($cData['articles']) && !empty($cData['win_win']), "Contract generated for role: $r");
    assertTest(count($cData['win_win']['profit_for_user']) > 0, "Win-Win user benefits exist for: $r");
    assertTest(count($cData['win_win']['profit_for_platform']) > 0, "Win-Win platform benefits exist for: $r");
}

// Contract signing & verification test
$testUserId = 1; // existing or mock user
$signRes = $contractService->recordAcceptance($testUserId, 'seller', '127.0.0.1', 'CLI Test Agent');
assertTest($signRes['success'] === true, "Contract electronic signature recorded");
assertTest(!empty($signRes['signature_hash']), "Signature hash generated: " . substr($signRes['signature_hash'], 0, 12) . "...");

$isAccepted = $contractService->hasAcceptedCurrentContract($testUserId, 'seller');
assertTest($isAccepted === true, "hasAcceptedCurrentContract returns true for accepted user");

$notAccepted = $contractService->hasAcceptedCurrentContract(999999, 'seller');
assertTest($notAccepted === false, "hasAcceptedCurrentContract returns false for non-existent user");

// ── 2. Test AutoshipService Inventory Authentication ────────────────────────
echo "\n--- 2. Testing AutoshipService Inventory Authentication ---\n";

// Threshold checks
assertTest(AutoshipService::isEligibleForAutoship(10, 5) === true, "Stock 10 >= 5 is eligible for autoship");
assertTest(AutoshipService::isEligibleForAutoship(5, 5) === true, "Stock 5 >= 5 is eligible for autoship");
assertTest(AutoshipService::isEligibleForAutoship(4, 5) === false, "Stock 4 < 5 is NOT eligible for autoship");
assertTest(AutoshipService::isEligibleForAutoship(1, 5) === false, "Stock 1 < 5 is NOT eligible for autoship");

// Detailed authentication: High stock (Qualifies for Autoship)
$highStockItem = ['id' => 101, 'name' => 'غذای خشک رویال کنین', 'stock' => 12, 'autoship_min_months_stock' => 5];
$authHigh = AutoshipService::authenticateAutoshipInventory($highStockItem);
assertTest($authHigh['is_eligible'] === true, "High stock qualifies for autoship");
assertTest($authHigh['can_single_buy'] === true, "High stock allows single purchase");
assertTest($authHigh['status'] === 'autoship_qualified', "Status is autoship_qualified");

// Detailed authentication: Low stock (Single Purchase Only - Neither User nor Provider is Sad)
$lowStockItem = ['id' => 102, 'name' => 'کنسرو سگ هیلز', 'stock' => 2, 'autoship_min_months_stock' => 5];
$authLow = AutoshipService::authenticateAutoshipInventory($lowStockItem);
assertTest($authLow['is_eligible'] === false, "Low stock does NOT qualify for autoship suggestion");
assertTest($authLow['can_single_buy'] === true, "Low stock STILL allows single purchase (User not blocked)");
assertTest($authLow['status'] === 'single_order_only', "Status is single_order_only");
assertTest(strpos($authLow['user_note'], 'خرید تکی') !== false, "User note explains single purchase availability");
assertTest(strpos($authLow['provider_tip'], '5 عدد') !== false || strpos($authLow['provider_tip'], '۵ عدد') !== false, "Provider tip guides how to unlock autoship badge");

// Detailed authentication: Out of Stock
$zeroStockItem = ['id' => 103, 'name' => 'قطره چشمی', 'stock' => 0];
$authZero = AutoshipService::authenticateAutoshipInventory($zeroStockItem);
assertTest($authZero['is_eligible'] === false, "Zero stock is not eligible for autoship");
assertTest($authZero['can_single_buy'] === false, "Zero stock cannot be bought");
assertTest($authZero['status'] === 'out_of_stock', "Status is out_of_stock");

// ── 3. Test Provider Tag Resolution ──────────────────────────────────────────
echo "\n--- 3. Testing Provider / Organization Tag Resolution ---\n";

// Clinic provider
$clinicItem = ['org_name' => 'بیمارستان دامپزشکی پایتخت', 'org_type' => 'hospital', 'organization_id' => 4];
$tagClinic = AutoshipService::resolveProviderTag($clinicItem);
assertTest($tagClinic['type'] === 'clinic', "Resolved clinic provider type");
assertTest(strpos($tagClinic['tag_label'], 'مرکز درمانی') !== false, "Clinic tag label formatted");

// Pharmacy provider
$pharmaItem = ['org_name' => 'داروخانه شبانه‌روزی رازی', 'org_type' => 'pharmacy', 'organization_id' => 7];
$tagPharma = AutoshipService::resolveProviderTag($pharmaItem);
assertTest($tagPharma['type'] === 'pharmacy', "Resolved pharmacy provider type");
assertTest(strpos($tagPharma['tag_label'], 'داروخانه') !== false, "Pharmacy tag label formatted");

// Seller provider
$sellerItem = ['seller_name' => 'پت‌شاپ پرشین', 'seller_id' => 12];
$tagSeller = AutoshipService::resolveProviderTag($sellerItem);
assertTest($tagSeller['type'] === 'seller', "Resolved seller provider type");
assertTest(strpos($tagSeller['tag_label'], 'تأمین‌کننده') !== false, "Seller tag label formatted");

// Default express
$defaultItem = [];
$tagDefault = AutoshipService::resolveProviderTag($defaultItem);
assertTest($tagDefault['type'] === 'official', "Resolved ASENA official express");

echo "\n=======================================================\n";
echo "Results: $passCount / $totalTests tests PASSED.\n";
if ($passCount === $totalTests) {
    echo "🎉 ALL AUTOSHIP INVENTORY & CONTRACT TESTS PASSED PERFECTLY!\n";
} else {
    echo "⚠️ Some tests failed. Check logs above.\n";
}
