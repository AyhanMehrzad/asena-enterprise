<?php
/**
 * Test Suite: Cancellation Rules & In-App Wallet Engine
 * Tests 100% refund to wallet, Postex departure enforcement, 24h appointment window, and autoship blackout week.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';

function assertRule(bool $condition, string $label): void {
    if ($condition) {
        echo "  [PASS] $label\n";
    } else {
        echo "  [FAIL] $label\n";
        exit(1);
    }
}

echo "=== 1. Testing WalletService Core Operations ===\n";

// Find or create test user
$userStmt = $pdo->query("SELECT id FROM users LIMIT 1");
$testUserId = (int)$userStmt->fetchColumn();
if (!$testUserId) {
    $pdo->exec("INSERT INTO users (role, wallet_balance, loyalty_points) VALUES ('customer', 0, 0)");
    $testUserId = (int)$pdo->lastInsertId();
}

// Reset balance to known state
$pdo->prepare("UPDATE users SET wallet_balance = 100000 WHERE id = ?")->execute([$testUserId]);

$wallet = App::wallet();
$balance = $wallet->getBalance($testUserId);
assertRule($balance === 100000, "Initial wallet balance retrieved correctly ($balance Tomans)");

// Test deposit
$depRes = $wallet->deposit($testUserId, 50000, 'test_module', 'TEST-DEP-1', 'تست افزایش موجودی');
assertRule($depRes['success'] === true, "Wallet deposit succeeded");
assertRule($depRes['balance_after'] === 150000, "Balance after deposit is 150,000 Tomans");
assertRule($wallet->getBalance($testUserId) === 150000, "Live balance verified as 150,000 Tomans");

// Test deduct
$dedRes = $wallet->deduct($testUserId, 30000, 'test_module', 'TEST-DED-1', 'تست کسر موجودی');
assertRule($dedRes['success'] === true, "Wallet deduct succeeded");
assertRule($dedRes['balance_after'] === 120000, "Balance after deduct is 120,000 Tomans");

// Test over-deduct (insufficient funds)
$overDed = $wallet->deduct($testUserId, 99999999, 'test_module', 'TEST-DED-OVER', 'تست کسر نامجاز');
assertRule($overDed['success'] === false, "Over-deduction blocked due to insufficient funds");

// Test 100% refund
$refRes = $wallet->refund($testUserId, 30000, 'test_module', 'TEST-REF-1', 'تست استرداد وجه');
assertRule($refRes['success'] === true, "Wallet refund succeeded");
assertRule($refRes['balance_after'] === 150000, "Balance restored to 150,000 Tomans");

// Verify transaction ledger
$txs = $wallet->getTransactions($testUserId, 10);
assertRule(count($txs) >= 3, "Transactions ledger recorded successfully (" . count($txs) . " records)");

echo "\n=== 2. Testing Order Cancellation Rules Engine ===\n";

$cancellation = App::cancellation();

// Create test order in 'processing' (pre-dispatch)
$pdo->prepare("
    INSERT INTO orders (user_id, total_amount, status, created_at)
    VALUES (?, 250000, 'processing', datetime('now'))
")->execute([$testUserId]);
$processingOrderId = (int)$pdo->lastInsertId();

$checkProcessing = $cancellation->checkOrderEligibility($processingOrderId, $testUserId);
assertRule($checkProcessing['can_cancel'] === true, "Order in processing before dispatch can be cancelled");
assertRule($checkProcessing['refund_amount'] === 250000, "Refund amount matches paid total (250,000 Tomans)");

// Execute cancellation
$curBalBefore = $wallet->getBalance($testUserId);
$cancelOrderRes = $cancellation->cancelOrder($processingOrderId, $testUserId);
assertRule($cancelOrderRes['success'] === true, "Order cancellation executed successfully");
$curBalAfter = $wallet->getBalance($testUserId);
assertRule($curBalAfter === $curBalBefore + 250000, "100% of order cost refunded to wallet (Balance increased by 250,000)");

// Verify order status is cancelled
$orderStatus = $pdo->query("SELECT status FROM orders WHERE id = $processingOrderId")->fetchColumn();
assertRule($orderStatus === 'cancelled', "Order status transitioned to 'cancelled'");

// Attempt cancelling again
$reCancel = $cancellation->checkOrderEligibility($processingOrderId, $testUserId);
assertRule($reCancel['can_cancel'] === false, "Already cancelled order cannot be re-cancelled");

// Create test order in 'shipped' (left provider city)
$pdo->prepare("
    INSERT INTO orders (user_id, total_amount, status, post_tracking_code, created_at)
    VALUES (?, 400000, 'shipped', '1000026102199887766554', datetime('now'))
")->execute([$testUserId]);
$shippedOrderId = (int)$pdo->lastInsertId();

$checkShipped = $cancellation->checkOrderEligibility($shippedOrderId, $testUserId);
assertRule($checkShipped['can_cancel'] === false, "Order shipped and in-transit CANNOT be cancelled");
assertRule(str_contains($checkShipped['reason'], 'شهر مبدأ') || str_contains($checkShipped['reason'], 'پست'), "Exact legal departure reason returned: " . $checkShipped['reason']);

// Attempt cancellation execution on shipped order
$cancelShippedRes = $cancellation->cancelOrder($shippedOrderId, $testUserId);
assertRule($cancelShippedRes['success'] === false, "Cancellation execution on shipped order is strictly rejected");

echo "\n=== 3. Testing Appointment Cancellation Rules Engine (24-Hour Law) ===\n";

// Doctor appointment > 24 hours in future (e.g. 3 days from now)
$futureDate = date('Y-m-d', strtotime('+3 days'));
$pdo->prepare("
    INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, fee, status)
    VALUES (?, 1, ?, '10:00:00', 350000, 'approved')
")->execute([$testUserId, $futureDate]);
$futureAptId = (int)$pdo->lastInsertId();

$checkFutureApt = $cancellation->checkAppointmentEligibility($futureAptId, $testUserId);
assertRule($checkFutureApt['can_cancel'] === true, "Appointment > 24 hours away is eligible for cancellation");
assertRule($checkFutureApt['refund_amount'] === 350000, "Refund amount matches full fee (350,000 Tomans)");

// Execute future appointment cancellation
$balBeforeApt = $wallet->getBalance($testUserId);
$cancelAptRes = $cancellation->cancelAppointment($futureAptId, $testUserId);
assertRule($cancelAptRes['success'] === true, "Appointment cancellation executed successfully");
$balAfterApt = $wallet->getBalance($testUserId);
assertRule($balAfterApt === $balBeforeApt + 350000, "Full appointment fee refunded 100% to wallet");

// Doctor appointment < 24 hours in future (e.g. 5 hours from now)
$nearDate = date('Y-m-d', strtotime('+5 hours'));
$nearTime = date('H:i:s', strtotime('+5 hours'));
$pdo->prepare("
    INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, fee, status)
    VALUES (?, 1, ?, ?, 350000, 'approved')
")->execute([$testUserId, $nearDate, $nearTime]);
$nearAptId = (int)$pdo->lastInsertId();

$checkNearApt = $cancellation->checkAppointmentEligibility($nearAptId, $testUserId);
assertRule($checkNearApt['can_cancel'] === false, "Appointment < 24 hours away CANNOT be cancelled");
assertRule(str_contains($checkNearApt['reason'], '۲۴ ساعت'), "Legal 24-hour window explanation returned: " . $checkNearApt['reason']);

// Attempt cancellation execution on < 24h appointment
$cancelNearRes = $cancellation->cancelAppointment($nearAptId, $testUserId);
assertRule($cancelNearRes['success'] === false, "Execution of < 24h appointment cancellation is blocked");

echo "\n=== 4. Testing Subscription / Autoship Cancellation Rules Engine ===\n";

// Subscription in blackout week (next delivery in 3 days)
$blackoutDate = date('Y-m-d', strtotime('+3 days'));
$pdo->prepare("
    INSERT INTO user_subscriptions (user_id, plan_name, amount, status, next_delivery_date, duration_months)
    VALUES (?, 'اشتراک غذای سگ ۳ ماهه', 600000, 'active', ?, 3)
")->execute([$testUserId, $blackoutDate]);
$blackoutSubId = (int)$pdo->lastInsertId();

// Add deliveries
$pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, 1, ?, 'pending')")->execute([$blackoutSubId, $blackoutDate]);
$pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, 2, date(?, '+30 days'), 'pending')")->execute([$blackoutSubId, $blackoutDate]);

$checkBlackoutSub = $cancellation->checkSubscriptionEligibility($blackoutSubId, $testUserId);
assertRule($checkBlackoutSub['can_cancel'] === false, "Subscription in final preparation week (<= 7 days) is locked");
assertRule($checkBlackoutSub['is_blackout_week'] === true, "is_blackout_week flag detected as true");
assertRule(str_contains($checkBlackoutSub['reason'], 'هفته'), "Blackout week rule notice returned: " . $checkBlackoutSub['reason']);

// Subscription outside blackout week (next delivery in 20 days)
$safeDate = date('Y-m-d', strtotime('+20 days'));
$pdo->prepare("
    INSERT INTO user_subscriptions (user_id, plan_name, amount, status, next_delivery_date, duration_months)
    VALUES (?, 'اشتراک تشویقی ۳ ماهه', 900000, 'active', ?, 3)
")->execute([$testUserId, $safeDate]);
$safeSubId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, 1, '2026-08-01', 'delivered')")->execute([$safeSubId]);
$pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, 2, ?, 'pending')")->execute([$safeSubId, $safeDate]);
$pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status) VALUES (?, 3, date(?, '+30 days'), 'pending')")->execute([$safeSubId, $safeDate]);

$checkSafeSub = $cancellation->checkSubscriptionEligibility($safeSubId, $testUserId);
assertRule($checkSafeSub['can_cancel'] === true, "Subscription outside blackout week can be cancelled");
assertRule($checkSafeSub['remaining_deliveries'] === 2, "2 remaining unused deliveries identified");
// 900,000 / 3 * 2 = 600,000
assertRule($checkSafeSub['refund_amount'] === 600000, "Prorated refund calculated correctly as 600,000 Tomans");

// Execute safe subscription cancellation
$balBeforeSub = $wallet->getBalance($testUserId);
$cancelSubRes = $cancellation->cancelSubscription($safeSubId, $testUserId);
assertRule($cancelSubRes['success'] === true, "Subscription cancellation executed successfully");
$balAfterSub = $wallet->getBalance($testUserId);
assertRule($balAfterSub === $balBeforeSub + 600000, "Prorated unused deliveries amount (600,000 Tomans) refunded 100% to wallet");

// Verify pending deliveries were cancelled
$delivCount = $pdo->query("SELECT COUNT(*) FROM subscription_deliveries WHERE subscription_id = $safeSubId AND status = 'pending'")->fetchColumn();
assertRule((int)$delivCount === 0, "All remaining pending deliveries marked as cancelled");

echo "\n=== ALL 22 CANCELLATION & WALLET REFUND TESTS PASSED PERFECTLY! ===\n";
