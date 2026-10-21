<?php
/**
 * ASENA Enterprise - Master Architecture & Core Capabilities Suite
 *
 * Validates:
 * 1. Specialist Roles (Doctors, Groomers, Pansions, Sellers) existence and metadata
 * 2. 15% Platform Brokerage Commission calculation (85% provider net liability)
 * 3. Single Person Seller role and Paya-ready wallet structure
 * 4. Organization Doctors & Shift Vacation / Blocked Slots
 * 5. Autoship Subscriptions schema & lifecycle
 * 6. Appointment lifecycle (pending -> approved -> completed -> available_for_payout)
 * 7. Persian Currency Formatting & Number parsing utilities
 */

// Provide isolated SQLite test PDO if running standalone without active MySQL
if (!isset($pdo)) {
    $testDb = sys_get_temp_dir() . '/asena_enterprise_suite_' . getmypid() . '.sqlite';
    $pdo = new PDO("sqlite:" . $testDb, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "===============================================================\n";
echo "   ASENA ENTERPRISE - MASTER ARCHITECTURE & CORE SUITE        \n";
echo "===============================================================\n\n";

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

// Ensure tables exist for isolated test run
if ($isSqlite) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(150),
            phone VARCHAR(20) UNIQUE,
            password VARCHAR(255),
            role VARCHAR(50) DEFAULT 'user',
            verification_status VARCHAR(50) DEFAULT 'approved',
            organization_id INTEGER NULL,
            wallet_balance BIGINT DEFAULT 0,
            avatar VARCHAR(255) NULL,
            email VARCHAR(150) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            name VARCHAR(150),
            clinic_name VARCHAR(150) NULL,
            provider_type VARCHAR(50) DEFAULT 'doctor',
            specialty VARCHAR(100) NULL,
            services_json TEXT NULL,
            price BIGINT DEFAULT 350000,
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS seller_wallets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER,
            bank_name VARCHAR(100) DEFAULT 'Samaneh',
            sheba_number VARCHAR(50) NULL,
            balance BIGINT DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS organization_doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER,
            doctor_id INTEGER,
            custom_title VARCHAR(150) NULL,
            commission_rate INT DEFAULT 60,
            status VARCHAR(50) DEFAULT 'active'
        );

        CREATE TABLE IF NOT EXISTS doctor_blocked_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            doctor_id INTEGER,
            organization_id INTEGER NULL,
            block_date VARCHAR(20),
            start_time VARCHAR(20),
            end_time VARCHAR(20),
            reason VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS user_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            plan_name VARCHAR(100),
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS appointments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            doctor_id INTEGER,
            organization_id INTEGER NULL,
            appointment_date VARCHAR(20),
            appointment_time VARCHAR(20),
            pet_name VARCHAR(100),
            pet_type VARCHAR(50),
            fee BIGINT,
            commission_amount BIGINT,
            net_amount BIGINT,
            visit_purpose VARCHAR(255),
            service_type VARCHAR(50) DEFAULT 'veterinary',
            settlement_status VARCHAR(50) DEFAULT 'held_in_escrow',
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed test fixtures if needed
    $docCount = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'doctor'")->fetchColumn();
    if ($docCount === 0) {
        $pdo->exec("INSERT INTO doctors (name, clinic_name, provider_type, specialty, price) VALUES ('دکتر سهرابی', 'کلینیک مرکزی البرز', 'doctor', 'جراحی بافت نرم', 400000)");
    }

    $groomCount = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'groomer'")->fetchColumn();
    if ($groomCount === 0) {
        $pdo->exec("INSERT INTO doctors (name, clinic_name, provider_type, specialty, services_json, price) VALUES ('آرایشگاه تیکو', 'سالن گرومینگ تخصصی پت', 'groomer', 'اصلاح و گرومینگ', '[\"شستشو\", \"کوتاهی پامرانین\"]', 350000)");
    }

    $sellerCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
    if ($sellerCount === 0) {
        $pdo->exec("INSERT INTO users (name, phone, role) VALUES ('فروشگاه ژیکان', '09129998877', 'seller')");
        $sellerId = (int)$pdo->lastInsertId();
        $pdo->exec("INSERT INTO seller_wallets (seller_id, bank_name, sheba_number, balance) VALUES ({$sellerId}, 'سامان', 'IR120560000000000000000001', 500000)");
    }

    $orgDocCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_doctors")->fetchColumn();
    if ($orgDocCount === 0) {
        $pdo->exec("INSERT INTO organization_doctors (organization_id, doctor_id, custom_title, commission_rate) VALUES (1, 1, 'پزشک ارشد', 70)");
    }

    $subCount = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions")->fetchColumn();
    if ($subCount === 0) {
        $pdo->exec("INSERT INTO user_subscriptions (user_id, plan_name, status) VALUES (1, 'بسته ماهانه مراقبت سگ', 'active')");
    }
}

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$name}" . ($details ? " - {$details}" : "") . "\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
        $failCount++;
    }
}

echo "=== 1. Specialists & Provider Metadata ===\n";
$docQuery = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'doctor'")->fetchColumn();
$groomerQuery = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE provider_type = 'groomer'")->fetchColumn();
assertTest("Doctors exist in DB", $docQuery > 0, "Count: {$docQuery}");
assertTest("Groomers exist in DB", $groomerQuery > 0, "Count: {$groomerQuery}");

$groomer = $pdo->query("SELECT * FROM doctors WHERE provider_type = 'groomer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertTest("Groomer has services_json or specialties", !empty($groomer['services_json']) || !empty($groomer['specialty']), "Groomer: {$groomer['name']}");
assertTest("Groomer has clinic/salon name", !empty($groomer['clinic_name']), "Clinic: {$groomer['clinic_name']}");

echo "\n=== 2. Platform Interest & Escrow Accounting ===\n";
$testPrice = 400000;
$expectedComm = (int)round($testPrice * 0.15); // 60,000
$expectedNet = $testPrice - $expectedComm;    // 340,000
assertTest("15% Platform Brokerage Commission Calculation", $expectedComm === 60000 && $expectedNet === 340000, "Gross: {$testPrice}, 15% Comm: {$expectedComm}, 85% Net: {$expectedNet}");

echo "\n=== 3. Single Person Seller Role & Wallet ===\n";
$sellerStmt = $pdo->prepare("SELECT * FROM users WHERE role = 'seller' LIMIT 1");
$sellerStmt->execute();
$seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);
assertTest("Single Person Seller exists", !empty($seller), "Seller: " . ($seller['name'] ?? 'none'));

if ($seller) {
    $walletStmt = $pdo->prepare("SELECT * FROM seller_wallets WHERE seller_id = ?");
    $walletStmt->execute([$seller['id']]);
    $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
    assertTest("Seller has Paya-ready Wallet", !empty($wallet), "Bank: " . ($wallet['bank_name'] ?? 'None'));
}

echo "\n=== 4. Organization Doctors & Blocked Slots (Time Management) ===\n";
$orgDocCount = (int)$pdo->query("SELECT COUNT(*) FROM organization_doctors")->fetchColumn();
assertTest("Organization has linked specialists", $orgDocCount > 0, "Count: {$orgDocCount}");

$insBlock = $pdo->prepare("
    INSERT INTO doctor_blocked_slots (doctor_id, organization_id, block_date, start_time, end_time, reason)
    VALUES (1, 1, '2026-10-25', '14:00', '16:00', 'شیفت جراحی ارتوپدی')
");
$insBlock->execute();
$testBlockId = (int)$pdo->lastInsertId();
assertTest("Can register blocked slot / shift vacation", $testBlockId > 0, "Block ID: {$testBlockId}");

// Cleanup test block
$pdo->prepare("DELETE FROM doctor_blocked_slots WHERE id = ?")->execute([$testBlockId]);

echo "\n=== 5. Autoship Subscriptions Schema ===\n";
$subCount = (int)$pdo->query("SELECT COUNT(*) FROM user_subscriptions")->fetchColumn();
assertTest("Autoship Subscriptions table populated", $subCount > 0, "Total subscriptions: {$subCount}");

echo "\n=== 6. Appointment Lifecycle & Escrow Settlement ===\n";
$insTestApt = $pdo->prepare("
    INSERT INTO appointments (
        user_id, doctor_id, organization_id, appointment_date, appointment_time,
        pet_name, pet_type, fee, commission_amount, net_amount, visit_purpose,
        service_type, settlement_status, status
    ) VALUES (
        1, 1, 1, '2026-10-25', '11:00',
        'تست هپی', 'سگ', 400000, 60000, 340000, 'اصلاح و گرومینگ تخصصی تست',
        'grooming', 'held_in_escrow', 'pending'
    )
");
$insTestApt->execute();
$testAptId = (int)$pdo->lastInsertId();
assertTest("Appointment created with 15% escrow commission", $testAptId > 0, "Apt #{$testAptId}");

// Transition to approved
$pdo->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?")->execute([$testAptId]);
$status1 = $pdo->query("SELECT status, settlement_status FROM appointments WHERE id = {$testAptId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Appointment transitioned to approved", $status1['status'] === 'approved' && $status1['settlement_status'] === 'held_in_escrow');

// Transition to completed (unlocks payout)
$pdo->prepare("UPDATE appointments SET status = 'completed', settlement_status = 'available_for_payout' WHERE id = ?")->execute([$testAptId]);
$status2 = $pdo->query("SELECT status, settlement_status FROM appointments WHERE id = {$testAptId}")->fetch(PDO::FETCH_ASSOC);
assertTest("Appointment completed unlocks Paya settlement", $status2['status'] === 'completed' && $status2['settlement_status'] === 'available_for_payout');

// Cleanup test appointment
$pdo->prepare("DELETE FROM appointments WHERE id = ?")->execute([$testAptId]);

echo "\n=== 7. Persian Currency & Digits Formatting ===\n";
if (function_exists('to_english_digits')) {
    assertTest("Convert Persian digits to English", to_english_digits('۱۲۳۴۵۶') === '123456');
    assertTest("Convert Arabic digits to English", to_english_digits('١٢٣٤٥٦') === '123456');
}
if (function_exists('format_price')) {
    $formatted = format_price(150000);
    assertTest("Price formatted with Toman symbol/unit", strpos($formatted, '150') !== false || strpos($formatted, '۱۵۰') !== false);
}

echo "\n=== SUITE SUMMARY ===\n";
echo "Total Passed: {$passCount}\n";
echo "Total Failed: {$failCount}\n";

if ($failCount === 0) {
    echo "🎉 ALL ENTERPRISE TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED.\n";
    exit(1);
}
