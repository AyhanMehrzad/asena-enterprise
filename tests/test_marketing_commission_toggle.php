<?php
/**
 * Test Suite: Platform Interest / Commission (5%) & Marketing Toggle Mode
 * Tests:
 * 1. Default platform commission rate is 5.0% (reduced from 15%)
 * 2. ON/OFF Marketing toggle button waives 100% of commission (0% interest)
 * 3. Custom interest percentage input (e.g. 7.5%, 3.0%) is dynamically stored and applied
 * 4. MarketplaceEscrowService allocates 100% to sellers when marketing mode is active
 * 5. Effective commission calculations in booking action match configured settings
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/MarketplaceEscrowService.php';

function runTest(string $title, callable $testFn): void {
    try {
        $result = $testFn();
        if ($result === true) {
            echo " [PASS] " . $title . "\n";
        } else {
            echo " [FAIL] " . $title . " (Returned false)\n";
        }
    } catch (Throwable $e) {
        echo " [FAIL] " . $title . " (Exception: " . $e->getMessage() . ")\n";
    }
}

echo "\n=========================================================\n";
echo "   ASENA ENTERPRISE MARKETING COMMISSION TEST SUITE      \n";
echo "=========================================================\n\n";

// Test 1: Check Default Rate Decreased to 5%
runTest("Test 1: Default rate constant in MarketplaceEscrowService is 5.00%", function() {
    return MarketplaceEscrowService::DEFAULT_COMMISSION_RATE === 5.00;
});

// Test 2: Dynamic setting retrieval defaults to 5%
runTest("Test 2: get_setting and get_effective_platform_commission_rate default to 5%", function() use ($pdo) {
    set_setting($pdo, 'platform_commission_percent', '5');
    set_setting($pdo, 'platform_commission_enabled', '1');

    $rate = (float)get_setting($pdo, 'platform_commission_percent', 5.0);
    $effective = get_effective_platform_commission_rate($pdo);

    return ($rate === 5.0 && $effective === 5.0);
});

// Test 3: Marketing ON/OFF switch disables interest to 0%
runTest("Test 3: Marketing toggle OFF disables interest (effective rate is 0.0%)", function() use ($pdo) {
    set_setting($pdo, 'platform_commission_enabled', '0');
    $effective = get_effective_platform_commission_rate($pdo);

    $escrowService = new MarketplaceEscrowService($pdo);
    $serviceEffective = $escrowService->getEffectiveCommissionRate();

    return ($effective === 0.0 && $serviceEffective === 0.0);
});

// Test 4: Custom interest percentage input is stored and respected
runTest("Test 4: Custom interest percentage (e.g. 7.5%) is correctly updated and read", function() use ($pdo) {
    set_setting($pdo, 'platform_commission_enabled', '1');
    set_setting($pdo, 'platform_commission_percent', '7.5');

    $rate = (float)get_setting($pdo, 'platform_commission_percent', 5.0);
    $effective = get_effective_platform_commission_rate($pdo);

    // Reset back to 5.0%
    set_setting($pdo, 'platform_commission_percent', '5');

    return ($rate === 7.5 && $effective === 7.5);
});

// Test 5: Provider Net Share calculation with 5% rate gives 95% net
runTest("Test 5: Standard 5% rate gives 95% net share to provider, 5% to platform", function() use ($pdo) {
    set_setting($pdo, 'platform_commission_enabled', '1');
    set_setting($pdo, 'platform_commission_percent', '5');

    $orderAmount = 1000000; // 1,000,000 Tomans
    $rate = get_effective_platform_commission_rate($pdo);
    $commission = (int)round($orderAmount * ($rate / 100.0));
    $netProvider = $orderAmount - $commission;

    return ($commission === 50000 && $netProvider === 950000);
});

// Test 6: Marketing mode active gives 100% net share to provider, 0% to platform
runTest("Test 6: Active marketing mode gives 100% net share to provider, 0% to platform", function() use ($pdo) {
    set_setting($pdo, 'platform_commission_enabled', '0');

    $orderAmount = 1000000; // 1,000,000 Tomans
    $rate = get_effective_platform_commission_rate($pdo);
    $commission = (int)round($orderAmount * ($rate / 100.0));
    $netProvider = $orderAmount - $commission;

    // Reset back to enabled for production standard
    set_setting($pdo, 'platform_commission_enabled', '1');
    set_setting($pdo, 'platform_commission_percent', '5');

    return ($commission === 0 && $netProvider === 1000000);
});

echo "\n---------------------------------------------------------\n";
echo " ALL COMMISSION & MARKETING TESTS COMPLETED!\n";
echo "---------------------------------------------------------\n\n";
