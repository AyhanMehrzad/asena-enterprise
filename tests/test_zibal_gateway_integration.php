<?php
/**
 * Test Suite: Zibal Gateway & IPG Integration
 * Verifies driver logic, status codes, sandbox switch, PaymentService, and callbacks.
 */

require_once __DIR__ . '/../includes/App.php';
App::boot();

$pdo = App::db();
require_once __DIR__ . '/../includes/ZibalGateway.php';
require_once __DIR__ . '/../includes/PaymentService.php';

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name} - Details: {$details}\n";
        $failed++;
    }
}

echo "=== Running Zibal Gateway Integration Tests ===\n\n";

// Test 1: Instantiation with Sandbox
echo "1. Driver Configuration & Merchant Resolution:\n";
$gwSandbox = new ZibalGateway('zibal', true, $pdo);
assertTest("Sandbox mode forces merchant to 'zibal'", $gwSandbox->getMerchant() === 'zibal');
assertTest("isSandbox returns true", $gwSandbox->isSandbox() === true);

$gwCustom = new ZibalGateway('6ad4ed70241349c91a027978', false, $pdo);
assertTest("Custom merchant key resolved correctly", $gwCustom->getMerchant() === '6ad4ed70241349c91a027978');
assertTest("isSandbox returns false for live", $gwCustom->isSandbox() === false);

// Test 2: Error & Status Code Translations
echo "\n2. Persian Status & Error Code Translations:\n";
$msg100 = $gwSandbox->getRequestErrorMessage(100);
assertTest("Code 100 translates to success message", str_contains($msg100, 'موفقیت'));

$msg104 = $gwSandbox->getRequestErrorMessage(104);
assertTest("Code 104 translates to invalid merchant", str_contains($msg104, 'مرچنت') && str_contains($msg104, 'نامعتبر'));

$msg115 = $gwSandbox->getRequestErrorMessage(115);
assertTest("Code 115 explains IP restriction", str_contains($msg115, 'IP') || str_contains($msg115, 'آی‌پی'));

// Test 3: Sandbox Payment Token Generation
echo "\n3. Sandbox Payment Token (trackId) Generation via Live Gateway:\n";
$req = $gwSandbox->requestPayment(10000, "تست خرید خودکار توکن پرداخت", "https://asena.company/actions/complete_payment.php");
assertTest("Sandbox request returned success", !empty($req['success']));
assertTest("Sandbox request issued trackId", !empty($req['trackId']));
assertTest("Payment URL begins with gateway.zibal.ir/start/", str_starts_with($req['payment_url'] ?? '', 'https://gateway.zibal.ir/start/'));

// Test 4: Health Check Diagnostics
echo "\n4. Health Check Ping Probe:\n";
$health = $gwSandbox->checkHealth('zibal', true);
assertTest("Health check on sandbox reports healthy", ($health['status'] ?? '') === 'healthy');
assertTest("Health check records latency > 0", ($health['latency_ms'] ?? 0) > 0);
assertTest("Health check returned test track_id", !empty($health['track_id']));

// Test 5: PaymentService Integration
echo "\n5. PaymentService Multi-Driver Integration:\n";
set_setting($pdo, 'zibal_sandbox', '1');
$ps = new PaymentService($pdo);
assertTest("PaymentService active driver defaults to zibal", $ps->getActiveDriver() === 'zibal');

$psReq = $ps->requestPayment(1, 25000, "تست یکپارچه‌سازی سرویس پرداخت", "order", 888, ["mobile" => "09120000000"]);
assertTest("PaymentService returns success", !empty($psReq['success']));
assertTest("PaymentService driver returned is zibal", ($psReq['driver'] ?? '') === 'zibal');
assertTest("PaymentService authority matches trackId", !empty($psReq['authority']));
set_setting($pdo, 'zibal_sandbox', '0');

// Test 6: Verify Payment Transactions Table Entry
echo "\n6. Database Transaction Logging:\n";
$trackId = $psReq['authority'];
$stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE authority_or_ref = ? LIMIT 1");
$stmt->execute([$trackId]);
$txRow = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("Transaction logged with status 'initiated'", ($txRow['status'] ?? '') === 'initiated');
assertTest("Transaction gateway_driver is 'zibal'", ($txRow['gateway_driver'] ?? '') === 'zibal');
assertTest("Transaction amount is 25000", (int)($txRow['amount'] ?? 0) === 25000);

echo "\n============================================\n";
echo "Test Results: {$passed} passed, {$failed} failed.\n";
echo "============================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
