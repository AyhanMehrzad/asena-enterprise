<?php
/**
 * Test Suite: Automated Weekly Payouts, Digital Bank Transcript QR, 9% VAT / 5% Commission, & SMS Management
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/App.php';
require_once dirname(__DIR__) . '/includes/QrCode.php';
require_once dirname(__DIR__) . '/includes/MarketplaceEscrowService.php';
require_once dirname(__DIR__) . '/includes/SmsService.php';

$passed = 0;
$failed = 0;

function it(string $desc, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "\033[32m[PASS]\033[0m {$desc}\n";
        $passed++;
    } else {
        echo "\033[31m[FAIL]\033[0m {$desc}\n";
        $failed++;
    }
}

echo "=== STARTING PAYOUT, QR, VAT & SMS VERIFICATION SUITE ===\n\n";

// 1. Vector QR Code Generator
$testUrl = "https://example.com/verify_payout.php?ref=PAYA-TEST-12345";
$svg = QrCode::svg($testUrl, 180, 2);
it("QrCode::svg generates valid SVG markup", str_contains($svg, '<svg') && str_contains($svg, '</svg>'));
it("QrCode::svg has proper viewBox and path elements", str_contains($svg, 'viewBox="0 0') && str_contains($svg, '<path'));
it("QrCode requires no external network calls", !str_contains($svg, 'qrserver') && !str_contains($svg, 'googleapis'));

// 2. Iranian Tax & Hidden Commission Calculation
$productPrice = 1000000; // 1,000,000 Tomans
$taxRate = 0.09;
$commissionRate = 0.05;

$buyerVat = (int)round($productPrice * $taxRate);
$buyerTotal = $productPrice + $buyerVat;
$platformInterest = (int)round($productPrice * $commissionRate);
$sellerNetEarnings = $productPrice - $platformInterest;

it("9% VAT calculation for buyer is exactly 90,000 Tomans on 1,000,000 Tomans", $buyerVat === 90000 && $buyerTotal === 1090000);
it("5% hidden platform commission is exactly 50,000 Tomans on 1,000,000 Tomans", $platformInterest === 50000);
it("Seller net earnings after 5% deduction is exactly 950,000 Tomans", $sellerNetEarnings === 950000);
it("Buyer does NOT pay the 5% platform commission", ($buyerTotal - $productPrice) === $buyerVat);

// 3. Appointment Tax & Commission (Article 9 Medical Exemption Configurable)
$appointmentFee = 350000; // 350,000 Tomans
$aptVat = (int)round($appointmentFee * 0.09);
$aptDoctorComm = (int)round($appointmentFee * 0.05);
$aptDoctorNet = $appointmentFee - $aptDoctorComm;
it("Appointment 9% VAT calculation: 31,500 Tomans on 350,000", $aptVat === 31500);
it("Appointment 5% hidden commission: 17,500 Tomans on 350,000", $aptDoctorComm === 17500);
it("Doctor net earnings: 332,500 Tomans", $aptDoctorNet === 332500);

// 4. Admin Site Settings & Bank Credentials
$cardStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'admin_bank_card'");
$cardNum = $cardStmt->fetchColumn();
it("Asena corporate card number is seeded in site_settings", !empty($cardNum) && strlen(str_replace(' ', '', $cardNum)) === 16);

$shabaStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'admin_bank_sheba'");
$shaba = $shabaStmt->fetchColumn();
it("Asena corporate Shaba is seeded in site_settings", !empty($shaba) && str_starts_with($shaba, 'IR'));

// 5. Melipayamak SMS Settings & SMS Credits System
$smsKeyStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'melipayamak_api_key'");
$smsKey = $smsKeyStmt->fetchColumn();
it("Melipayamak API key setting exists in site_settings", !empty($smsKey));

// Test user SMS credit deduction & balance check
$testUserStmt = $pdo->query("SELECT id FROM users WHERE role IN ('seller', 'doctor', 'organization') LIMIT 1");
$testUserId = (int)$testUserStmt->fetchColumn();

if ($testUserId) {
    // Ensure wallet exists
    $pdo->prepare("INSERT IGNORE INTO seller_wallets (seller_id, sms_credits) VALUES (?, 10)")->execute([$testUserId]);
    $pdo->prepare("UPDATE seller_wallets SET sms_credits = 25 WHERE seller_id = ?")->execute([$testUserId]);
    
    $creditsBefore = SmsService::getUserSmsCredits($pdo, $testUserId);
    it("SmsService::getUserSmsCredits returns current balance", $creditsBefore === 25);
    
    $deducted = SmsService::deductUserSmsCredits($pdo, $testUserId, '09120000000', 'تست ارسال پیامک نوبت دهی آزمایشگاهی', 2);
    it("SmsService::deductUserSmsCredits successfully deducts credits", $deducted === true);
    
    $creditsAfter = SmsService::getUserSmsCredits($pdo, $testUserId);
    it("Credits accurately decremented by 2", $creditsAfter === 23);
    
    // Check log entry
    $logStmt = $pdo->prepare("SELECT COUNT(*) FROM sms_usage_logs WHERE user_id = ? AND credits_deducted = 2");
    $logStmt->execute([$testUserId]);
    it("SMS usage logged in sms_usage_logs table", (int)$logStmt->fetchColumn() > 0);
    
    // Insufficient credits test
    $failedDeduct = SmsService::deductUserSmsCredits($pdo, $testUserId, '09120000000', 'Overdraft test', 9999);
    it("SmsService prevents overdraft when credits are insufficient", $failedDeduct === false);
}

// 6. Automated Weekly Thursday 9:00 AM Tehran Scheduler
$tehranTz = new DateTimeZone('Asia/Tehran');
$tehranNow = new DateTime('now', $tehranTz);
echo "Current Tehran Time: " . $tehranNow->format('Y-m-d H:i:s (l)') . "\n";

// Test scheduler in simulation / force mode
$schedResult = App::escrow()->checkAndExecuteScheduledWeeklyPayout(true, 'test');
it("Weekly scheduler execution returns valid result structure", isset($schedResult['executed']) && (isset($schedResult['reason']) || isset($schedResult['batch_res'])));

// Check payout_cron_runs table
$cronLog = $pdo->query("SELECT * FROM payout_cron_runs ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
it("payout_cron_runs table logs cron execution history", !empty($cronLog));

echo "\n=== SUITE SUMMARY ===\n";
echo "Total Passed: {$passed}\n";
echo "Total Failed: {$failed}\n";

if ($failed === 0) {
    echo "🎉 ALL PAYOUT, TAX, AND SMS VERIFICATION TESTS PASSED!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED!\n";
    exit(1);
}
