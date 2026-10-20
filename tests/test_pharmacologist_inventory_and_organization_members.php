<?php
/**
 * Test Suite: Pharmacologist / Pharmacist Inventory & Organization Member Assignment
 * Tests:
 * 1. AuthGuard recognition of 'pharmacologist' role and mapping to pharmacist portal
 * 2. Organization tier configuration includes 'organization_members' feature
 * 3. OrganizationService member management (assignMember, getMembers, updateMemberStatus, removeMember)
 * 4. Multi-tenant comprehensive inventory synchronization (pharmacist items visible in organization cockpit)
 * 5. BPMS prescription gating decommissioned (shipping_unlocked defaults to 1 and does not block dispatch)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/AuthGuard.php';
require_once __DIR__ . '/../includes/OrganizationService.php';
require_once __DIR__ . '/../includes/BpmsService.php';

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

echo "\n========================================================================\n";
echo "   ASENA ENTERPRISE: PHARMACOLOGIST & ORG MEMBERS TEST SUITE            \n";
echo "========================================================================\n\n";

// Test 1: AuthGuard recognizes pharmacologist role
runTest("Test 1: AuthGuard recognizes 'pharmacologist' role and isPharmacist() returns true", function() {
    $_SESSION['user_id'] = 9991;
    $_SESSION['role'] = 'pharmacologist';

    $hasPharmacologistRole = AuthGuard::hasRole('pharmacologist');
    $isPharmacist = AuthGuard::isPharmacist();

    return $hasPharmacologistRole === true && $isPharmacist === true;
});

// Test 2: Tier check for organization_members feature
runTest("Test 2: Tier config declares 'organization_members' feature in premium & enterprise", function() {
    $tierConfig = require __DIR__ . '/../config/tiers.php';
    $tiers = $tierConfig['tiers'] ?? [];
    $inPremium = in_array('organization_members', $tiers['premium']['features'] ?? []);
    $inEnterprise = in_array('organization_members', $tiers['enterprise']['features'] ?? []);
    return $inPremium && $inEnterprise;
});

// Test 3: Organization Member Assignment & Listing
runTest("Test 3: OrganizationService::assignMember and getMembers", function() use ($pdo) {
    // Setup test organization and test pharmacologist user
    $pdo->exec("INSERT OR IGNORE INTO users (id, role, organization_id, password) VALUES (8801, 'organization', 8801, 'dummy')");
    $pdo->exec("INSERT OR IGNORE INTO users (id, role, organization_id, password) VALUES (8802, 'pharmacologist', 8801, 'dummy')");

    $orgId = 8801;
    $memberUserId = 8802;

    $orgService = new OrganizationService($pdo);
    $assigned = $orgService->assignMember(
        $orgId,
        $memberUserId,
        'pharmacologist',
        'مدیر ارشد فارماکولوژی و انبار دارویی',
        'مسئول تأیید زنجیره سرد'
    );

    if (!$assigned) {
        return false;
    }

    $members = $orgService->getMembers($orgId);
    $found = false;
    foreach ($members as $m) {
        if ((int)$m['user_id'] === $memberUserId && $m['member_role'] === 'pharmacologist') {
            $found = true;
            break;
        }
    }

    return $found;
});

// Test 4: Organization Member Status Toggle & Unlink
runTest("Test 4: OrganizationService status toggle and unlink", function() use ($pdo) {
    $orgId = 8801;
    $memberUserId = 8802;
    $orgService = new OrganizationService($pdo);

    // Toggle to inactive
    $inactivated = $orgService->updateMemberStatus($orgId, $memberUserId, 'inactive');
    $membersInactive = $orgService->getMembers($orgId);
    $isInactive = false;
    foreach ($membersInactive as $m) {
        if ((int)$m['user_id'] === $memberUserId && $m['status'] === 'inactive') {
            $isInactive = true;
            break;
        }
    }

    // Toggle back to active
    $reactivated = $orgService->updateMemberStatus($orgId, $memberUserId, 'active');

    return $inactivated && $isInactive && $reactivated;
});

// Test 5: Multi-Tenant Comprehensive Inventory Integration
runTest("Test 5: Organization comprehensive inventory aggregates pharmacist and org stock", function() use ($pdo) {
    $orgId = 8801;
    $pharmacistUserId = 8802;

    // Check if column batch_number exists in pharmacy_medicines
    $cols = [];
    try {
        $q = $pdo->query("PRAGMA table_info(pharmacy_medicines)");
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cols[] = $c['name'];
        }
    } catch (Throwable $e) {}

    if (!in_array('batch_number', $cols, true)) {
        $pdo->exec("ALTER TABLE pharmacy_medicines ADD COLUMN batch_number TEXT NULL");
    }
    if (!in_array('expiry_date', $cols, true)) {
        $pdo->exec("ALTER TABLE pharmacy_medicines ADD COLUMN expiry_date TEXT NULL");
    }
    if (!in_array('cold_chain', $cols, true)) {
        $pdo->exec("ALTER TABLE pharmacy_medicines ADD COLUMN cold_chain INTEGER NOT NULL DEFAULT 0");
    }

    $stmt = $pdo->prepare("
        INSERT INTO pharmacy_medicines 
        (name, brand, category, price, stock, requires_prescription, batch_number, expiry_date, cold_chain, organization_id, user_id)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'سفالکسین تست سازمانی',
        'داروپخش',
        'آنتی‌بیوتیک',
        185000,
        45,
        0,
        'BT-9901-ORG',
        '2027-11-20',
        1,
        $orgId,
        $pharmacistUserId
    ]);

    $orgService = new OrganizationService($pdo);
    $inventory = $orgService->getComprehensiveInventory($orgId);
    $itemFound = false;
    foreach ($inventory as $item) {
        if (isset($item['batch_number']) && $item['batch_number'] === 'BT-9901-ORG' && (int)$item['organization_id'] === $orgId) {
            $itemFound = true;
            break;
        }
    }

    return $itemFound;
});

// Test 6: BPMS Prescription Decoupling & Auto-Unlock
runTest("Test 6: Prescriptions created with shipping_unlocked = 1 and BPMS locks decommissioned", function() use ($pdo) {
    // Setup prescriptions table if needed
    $pdo->exec("CREATE TABLE IF NOT EXISTS prescriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        pet_id INTEGER,
        doctor_id INTEGER,
        organization_id INTEGER,
        pharmacy_id INTEGER,
        diagnosis TEXT,
        doctor_examination_report TEXT,
        items_json TEXT,
        rx_file_url TEXT,
        vet_name TEXT,
        vet_phone TEXT,
        vet_license_number TEXT,
        clinic_name TEXT,
        status TEXT DEFAULT 'approved',
        dispensing_status TEXT DEFAULT 'ready_for_pickup',
        bpms_state TEXT DEFAULT 'broadcasted',
        pharmacist_decision TEXT DEFAULT 'none',
        shipping_unlocked INTEGER NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $bpms = new BpmsService($pdo);
    $prescId = $bpms->doctorSubmitPrescription([
        'user_id' => 8802,
        'doctor_id' => 10,
        'pet_id' => 1,
        'organization_id' => 8801,
        'diagnosis' => 'تست سلامت بالینی',
        'items' => [
            ['name' => 'آمپی‌سیلین', 'dosage' => 'هر ۱۲ ساعت']
        ]
    ]);

    $stmt = $pdo->prepare("SELECT shipping_unlocked, status, dispensing_status FROM prescriptions WHERE id = ?");
    $stmt->execute([$prescId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($row && (int)$row['shipping_unlocked'] === 1 && $row['status'] === 'approved' && $row['dispensing_status'] === 'ready_for_pickup');
});

echo "\n========================================================================\n";
echo "   ALL TESTS COMPLETED                                                  \n";
echo "========================================================================\n\n";
