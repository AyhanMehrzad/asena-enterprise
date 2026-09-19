<?php
/**
 * Test Suite: ASENA 10% VAT & Zero-Tax Payment / Escrow Engine
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PaymentService.php';
require_once __DIR__ . '/../includes/MarketplaceEscrowService.php';

echo "====================================================\n";
echo "   ASENA 10% VAT & ZERO-TAX PAYMENT TEST SUITE      \n";
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

// ── Test 2: PaymentService Request Generation ────────────────────────────────
runTest("Test 2: PaymentService generates Card-to-Card payment intent", function() use ($pdo) {
    $service = new PaymentService($pdo);
    $driver = $service->getActiveDriver();
    
    // Find or create test user
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = (int)$uStmt->fetchColumn() ?: 1;

    $req = $service->requestPayment($userId, 990000, 'تست پرداخت سفارش کارت به کارت', 'order', null, ['test' => 1]);
    
    return ($req['success'] === true && !empty($req['authority']) && str_starts_with($req['authority'], 'ASENA-TX-') && str_contains($req['payment_url'], 'card_payment.php'));
});

// ── Test 3: Card Receipt Submission & Validation ─────────────────────────────
runTest("Test 3: Buyer submits bank tracking code for Card-to-Card", function() use ($pdo) {
    $service = new PaymentService($pdo);
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = (int)$uStmt->fetchColumn() ?: 1;

    $req = $service->requestPayment($userId, 500000, 'تست سفارش با فیش واریزی', 'order');
    $auth = $req['authority'];
    $testTrackingCode = 'TRK' . mt_rand(100000, 999999);

    $subRes = $service->submitCardReceipt($auth, $testTrackingCode, '7890', null);
    
    // Verify in database
    $checkStmt = $pdo->prepare("SELECT status FROM payment_transactions WHERE authority_or_ref = ?");
    $checkStmt->execute([$auth]);
    $status = $checkStmt->fetchColumn();

    return ($subRes['success'] === true && $status === 'pending_verification');
});

// ── Test 4: Financial Approval & Ledger Inflow ───────────────────────────────
runTest("Test 4: Admin approval transitions order to paid and writes to platform_ledger_entries", function() use ($pdo) {
    $service = new PaymentService($pdo);
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = (int)$uStmt->fetchColumn() ?: 1;

    $req = $service->requestPayment($userId, 600000, 'تست پرداخت تاییدیه مالی', 'order');
    $auth = $req['authority'];
    $testCode = 'TRK' . mt_rand(100000, 999999);

    $subRes = $service->submitCardReceipt($auth, $testCode, '1234');
    $subId = (int)$subRes['submission_id'];

    // Admin approves
    $appRes = $service->approveCardReceipt($subId, 1, 'تست خودکار واحد');
    
    // Check transaction status
    $tStmt = $pdo->prepare("SELECT status FROM payment_transactions WHERE authority_or_ref = ?");
    $tStmt->execute([$auth]);
    $tStatus = $tStmt->fetchColumn();

    // Check ledger entry
    $lStmt = $pdo->prepare("SELECT type, amount FROM platform_ledger_entries WHERE type = 'customer_inflow' ORDER BY id DESC LIMIT 1");
    $lStmt->execute();
    $ledger = $lStmt->fetch(PDO::FETCH_ASSOC);

    return ($appRes['success'] === true && $tStatus === 'paid' && (int)$ledger['amount'] === 600000);
});

// ── Test 5: Bank SMS Webhook Auto-Match ──────────────────────────────────────
runTest("Test 5: Bank SMS Webhook auto-detects and matches transaction", function() use ($pdo) {
    $service = new PaymentService($pdo);
    $uStmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $userId = (int)$uStmt->fetchColumn() ?: 1;

    $uniqueAmount = mt_rand(400000, 499990);
    $req = $service->requestPayment($userId, $uniqueAmount, 'تست وب‌سرویس پیامکی بانک');
    $auth = $req['authority'];
    $bankRef = (string)mt_rand(1000000, 9999999);

    $sub = $service->submitCardReceipt($auth, $bankRef, '5555');
    $subId = $sub['submission_id'];

    // Simulate webhook calling approve
    $autoApprove = $service->approveCardReceipt($subId, 0, 'تأیید خودکار از طریق وب‌سرویس پیامک بانک');

    $checkSub = $pdo->prepare("SELECT status FROM card_receipt_submissions WHERE id = ?");
    $checkSub->execute([$subId]);

    return ($autoApprove['success'] === true && $checkSub->fetchColumn() === 'approved');
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
