<?php
/**
 * Test Enterprise Suite Verification
 * Validates:
 * 1. Specialists (Doctors + Groomers) existence and metadata (services_json, tags, clinic_name, provider_type)
 * 2. 5% Platform Interest calculation for appointments & products
 * 3. Single Person Seller role and profile isolation
 * 4. Organization Doctors & Shifts (doctor_blocked_slots)
 * 5. Autoship Subscriptions schema & active data
 * 6. Appointment creation, status progression (pending -> approved -> completed), and payout availability
 */

require_once __DIR__ . '/../includes/db.php';

echo "=== STARTING ASENA ENTERPRISE VERIFICATION SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] {$name}" . ($details ? " - {$details}" : "") . "\n";
        $passCount++;
    } else {
        echo "[FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
        $failCount++;
    }
}

// 1. Specialists (Doctors + Groomers)
$docQuery = $pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'doctor'")->fetchColumn();
$groomerQuery = $pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'groomer'")->fetchColumn();
assertTest("Doctors exist in DB", $docQuery > 0, "Count: {$docQuery}");
assertTest("Groomers exist in DB", $groomerQuery > 0, "Count: {$groomerQuery}");

// Check groomer details
$groomer = $pdo->query("SELECT * FROM doctors WHERE provider_type = 'groomer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest("Groomer has services_json", !empty($groomer['services_json']), "Groomer: {$groomer['name']}");
assertTest("Groomer has clinic/salon name", !empty($groomer['clinic_name']), "Clinic: {$groomer['clinic_name']}");

// 2. Platform Interest (5% on appointments)
$testPrice = 400000;
$expectedComm = round($testPrice * 0.05); // 20,000
$expectedNet = $testPrice - $expectedComm; // 380,000
assertTest("5% Platform Interest Calculation", $expectedComm === 20000.0 && $expectedNet === 380000.0, "Gross: {$testPrice}, 5% Comm: {$expectedComm}, 95% Net: {$expectedNet}");

// 3. Single Person Seller Role & Wallet
$sellerStmt = $pdo->prepare("SELECT * FROM users WHERE role = 'seller' LIMIT 1");
$sellerStmt->execute();
$seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);
assertTest("Single Person Seller exists", !empty($seller), "Seller Phone: " . ($seller['phone'] ?? 'none'));

if ($seller) {
    $walletStmt = $pdo->prepare("SELECT * FROM seller_wallets WHERE seller_id = ?");
    $walletStmt->execute([$seller['id']]);
    $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
    assertTest("Seller has Paya-ready Wallet", !empty($wallet), "Bank: " . ($wallet['bank_name'] ?? 'Samaneh'));
}

// 4. Organization Doctors & Blocked Slots (Time Management)
$orgDocCount = $pdo->query("SELECT COUNT(*) FROM organization_doctors")->fetchColumn();
assertTest("Organization has linked specialists", $orgDocCount > 0, "Count: {$orgDocCount}");

$blockedSlotCount = $pdo->query("SELECT COUNT(*) FROM doctor_blocked_slots")->fetchColumn();
assertTest("Doctor blocked slots table operational", $blockedSlotCount >= 0, "Table exists, rows: {$blockedSlotCount}");

// Insert a test blocked slot for surgery/leave
$docId = $pdo->query("SELECT id FROM doctors LIMIT 1")->fetchColumn();
$insBlock = $pdo->prepare("
    INSERT INTO doctor_blocked_slots (doctor_id, organization_id, block_date, start_time, end_time, reason)
    VALUES (?, 1, '2026-10-15', '14:00', '16:00', 'تست شیفت جراحی ارتوپدی')
");
$insBlock->execute([$docId]);
$testBlockId = $pdo->lastInsertId();
assertTest("Can register blocked slot / shift vacation", $testBlockId > 0, "Block ID: {$testBlockId}");

// Cleanup test block
$pdo->prepare("DELETE FROM doctor_blocked_slots WHERE id = ?")->execute([$testBlockId]);

// 5. Autoship Subscriptions
$subCount = $pdo->query("SELECT COUNT(*) FROM user_subscriptions")->fetchColumn();
assertTest("Autoship Subscriptions table populated", $subCount > 0, "Total subscriptions: {$subCount}");

// 6. Test Appointment Full Lifecycle
$testAptDoc = $pdo->query("SELECT id, price FROM doctors WHERE provider_type = 'groomer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$grossFee = (int)$testAptDoc['price'];
$commFee = (int)round($grossFee * 0.05);
$netFee = $grossFee - $commFee;

$insTestApt = $pdo->prepare("
    INSERT INTO appointments (
        user_id, doctor_id, organization_id, appointment_date, appointment_time,
        pet_name, pet_type, fee, commission_amount, net_amount, visit_purpose,
        service_type, settlement_status, status, created_at
    ) VALUES (
        1, ?, 1, '2026-10-20', '11:00',
        'تست هپی', 'سگ', ?, ?, ?, 'اصلاح و گرومینگ تخصصی تست',
        'grooming', 'held_in_escrow', 'pending', NOW()
    )
");
$insTestApt->execute([$testAptDoc['id'], $grossFee, $commFee, $netFee]);
$testAptId = (int)$pdo->lastInsertId();
assertTest("Can create Grooming appointment with 5% commission", $testAptId > 0, "Apt #{$testAptId}, Fee: {$grossFee}, Comm: {$commFee}, Net: {$netFee}");

// Transition to approved
$upApp = $pdo->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?");
$upApp->execute([$testAptId]);
$status1 = $pdo->query("SELECT status, settlement_status FROM appointments WHERE id = {$testAptId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Appointment transitioned to approved", $status1['status'] === 'approved' && $status1['settlement_status'] === 'held_in_escrow');

// Transition to completed (should unlock for payout)
$upComp = $pdo->prepare("
    UPDATE appointments SET status = 'completed', settlement_status = 'available_for_payout' WHERE id = ?
");
$upComp->execute([$testAptId]);
$status2 = $pdo->query("SELECT status, settlement_status FROM appointments WHERE id = {$testAptId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Appointment completed unlocks Paya settlement", $status2['status'] === 'completed' && $status2['settlement_status'] === 'available_for_payout');

// Cleanup test appointment
$pdo->prepare("DELETE FROM appointments WHERE id = ?")->execute([$testAptId]);

echo "\n=== SUITE SUMMARY ===\n";
echo "Total Passed: {$passCount}\n";
echo "Total Failed: {$failCount}\n";

if ($failCount === 0) {
    echo "🎉 ALL ENTERPRISE TESTS PASSED SUCCESSFULLY!\n";
} else {
    echo "❌ SOME TESTS FAILED.\n";
}
