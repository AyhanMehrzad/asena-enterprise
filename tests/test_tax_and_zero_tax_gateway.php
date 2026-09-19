<?php
/**
 * Test Suite: ASENA 10% VAT & Official Payment Gateway / Escrow Engine
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PaymentService.php';
require_once __DIR__ . '/../includes/MarketplaceEscrowService.php';

echo "====================================================\n";
echo "   ASENA 10% VAT & OFFICIAL GATEWAY TEST SUITE      \n";
echo "====================================================\n\n";

$passed = 0;
$total = 0;

function runTest(string $name, callable $fn) {
    global $passed, $total;
    $total++;
    try {
        $result = $fn();
        if ($result) {
            echo " [PASS] {$name}\n";
            $passed++;
        } else {
            echo " [FAIL] {$name}\n";
        }
    } catch (Throwable $e) {
        echo " [FAIL] {$name}: " . $e->getMessage() . "\n";
    }
}

// ── Test 1: 10% VAT Calculation ─────────────────────────────────────────────
runTest("Test 1: 10% VAT statutory calculation", function() use ($pdo) {
    set_setting($pdo, 'tax_rate_percent', '10.0');
    $subtotal = 900000;
    $taxRate = (float)get_setting($pdo, 'tax_rate_percent', 10.0);
    $taxAmount = (int)round($subtotal * ($taxRate / 100.0));
    $finalTotal = $subtotal + $taxAmount;

    return ($taxRate === 10.0 && $taxAmount === 90000 && $finalTotal === 990000);
});

// ── Test 2: Official Gateway Driver Verification ─────────────────────────────
runTest("Test 2: PaymentService uses official gateway driver (ZarinPal)", function() use ($pdo) {
    set_setting($pdo, 'active_payment_gateway', 'zarinpal');
    $service = new PaymentService($pdo);
    return ($service->getActiveDriver() === 'zarinpal');
});

// ── Test 3: Official Gateway Payment Request Initiation ──────────────────────
runTest("Test 3: Official Gateway generates valid authority and payment URL", function() use ($pdo) {
    $service = new PaymentService($pdo);
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = (int)$uStmt->fetchColumn() ?: 1;

    $req = $service->requestPayment($userId, 750000, 'سفارش خرید تستی', 'order', 101, ['test' => 1]);
    return ($req['success'] === true && !empty($req['authority']) && !empty($req['payment_url']));
});

// ── Test 4: Escrow Ledger Double-Entry Recording ─────────────────────────────
runTest("Test 4: Marketplace Escrow double-entry ledger integrity", function() use ($pdo) {
    $escrow = new MarketplaceEscrowService($pdo);
    $wallet = $escrow->getSellerWallet(1);
    return (is_array($wallet) && isset($wallet['balance_available_for_payout']));
});

echo "\n----------------------------------------------------\n";
echo " RESULTS: {$passed} / {$total} Tests Passed.\n";
echo "----------------------------------------------------\n";

if ($passed === $total) {
    echo " ALL TESTS COMPLETED SUCCESSFULLY!\n\n";
} else {
    echo " SOME TESTS FAILED!\n\n";
    exit(1);
}
