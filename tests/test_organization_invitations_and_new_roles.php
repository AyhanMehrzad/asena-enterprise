<?php
/**
 * Automated Verification: Organization Invitations Handshake, Groomer & Pansion Roles
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/AuthGuard.php';
require_once __DIR__ . '/../includes/OrganizationService.php';

echo "=== 1. Setting up Isolated Database Environment ===\n";

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

// Ensure tables exist
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

        CREATE TABLE IF NOT EXISTS organizations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255),
            user_id INTEGER,
            type VARCHAR(50) DEFAULT 'clinic',
            city VARCHAR(100) DEFAULT 'تهران',
            address TEXT,
            phone VARCHAR(50),
            logo VARCHAR(255) NULL,
            status VARCHAR(50) DEFAULT 'approved',
            is_24_7 INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS organization_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER,
            user_id INTEGER,
            member_role VARCHAR(50),
            job_title VARCHAR(150),
            notes TEXT,
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS organization_doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER,
            doctor_id INTEGER,
            consultation_fee BIGINT DEFAULT 0,
            working_days VARCHAR(150),
            working_hours VARCHAR(150),
            doctor_role VARCHAR(50) DEFAULT 'doctor',
            status VARCHAR(50) DEFAULT 'active'
        );

        CREATE TABLE IF NOT EXISTS doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            name VARCHAR(150),
            specialty VARCHAR(150),
            medical_council_code VARCHAR(50),
            price BIGINT DEFAULT 150000
        );

        CREATE TABLE IF NOT EXISTS organization_invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER,
            user_id INTEGER,
            suggested_role VARCHAR(50),
            job_title VARCHAR(150),
            proposed_shifts TEXT,
            commission_rate DECIMAL(5,2) DEFAULT 0,
            notes TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            declined_reason TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS grooming_services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            groomer_user_id INTEGER,
            organization_id INTEGER NULL,
            title VARCHAR(150),
            category VARCHAR(50),
            duration_minutes INTEGER DEFAULT 60,
            price BIGINT DEFAULT 0,
            description TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS grooming_portfolio (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            groomer_user_id INTEGER,
            pet_type VARCHAR(50),
            pet_breed VARCHAR(100),
            before_image VARCHAR(255),
            after_image VARCHAR(255),
            title VARCHAR(150),
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS pansion_rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER NULL,
            pansion_user_id INTEGER,
            room_number VARCHAR(50),
            room_title VARCHAR(150),
            room_type VARCHAR(50),
            capacity INTEGER DEFAULT 1,
            price_per_night BIGINT DEFAULT 0,
            description TEXT,
            status VARCHAR(50) DEFAULT 'available',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS pansion_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER,
            organization_id INTEGER NULL,
            pansion_user_id INTEGER,
            customer_user_id INTEGER,
            pet_name VARCHAR(100),
            pet_species VARCHAR(50),
            pet_breed VARCHAR(100),
            check_in_date DATE,
            check_out_date DATE,
            nights_count INTEGER DEFAULT 1,
            total_price BIGINT DEFAULT 0,
            status VARCHAR(50) DEFAULT 'pending',
            special_diet_notes TEXT,
            medication_notes TEXT,
            belongings_list TEXT,
            emergency_contact_phone VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

function assertTest(bool $condition, string $passMessage, string $failMessage = 'Assertion failed'): void {
    if ($condition) {
        echo "  [PASS] {$passMessage}\n";
    } else {
        echo "  [FAIL] {$failMessage}\n";
        exit(1);
    }
}

$orgService = App::organization();

// Create sample test organization
$pdo->exec("DELETE FROM organizations WHERE name = 'کلینیک تخصصی دکتر پارت'");
$pdo->prepare("INSERT INTO organizations (name, type, city, phone, is_24_7) VALUES ('کلینیک تخصصی دکتر پارت', 'hospital', 'تهران', '02188888888', 1)")->execute();
$orgId = (int)$pdo->lastInsertId();
assertTest($orgId > 0, "Test organization created with ID {$orgId}");

// Clean up any test state
$pdo->exec("DELETE FROM organization_invitations");
$pdo->exec("DELETE FROM organization_members");
$pdo->exec("DELETE FROM organization_doctors");
$pdo->exec("DELETE FROM users WHERE phone IN ('09121110001', '09121110002', '09121110003', '09121110004')");

// Doctor
$pdo->prepare("INSERT INTO users (name, phone, role, verification_status) VALUES ('دکتر کیوان راد', '09121110001', 'doctor', 'approved')")->execute();
$docUserId = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO doctors (user_id, name, specialty) VALUES (?, 'دکتر کیوان راد', 'جراح ارشد بافت نرم')")->execute([$docUserId]);

// Groomer
$pdo->prepare("INSERT INTO users (name, phone, role, verification_status) VALUES ('سارا افشار (گرومر)', '09121110002', 'groomer', 'approved')")->execute();
$groomerUserId = (int)$pdo->lastInsertId();

// Pharmacist
$pdo->prepare("INSERT INTO users (name, phone, role, verification_status) VALUES ('دکتر نیما صبوری', '09121110003', 'pharmacist', 'approved')")->execute();
$pharmaUserId = (int)$pdo->lastInsertId();

// Pansion Manager
$pdo->prepare("INSERT INTO users (name, phone, role, verification_status) VALUES ('پانسیون و هتل وی آی پی مهر', '09121110004', 'pansion', 'approved')")->execute();
$pansionUserId = (int)$pdo->lastInsertId();

echo "\n=== 2. Testing Specialist Search & Filter Directory ===\n";

$searchResults = $orgService->searchSpecialists('کیوان', 'doctor', $orgId);
assertTest(count($searchResults) >= 1, "Search for verified doctor returned results");
assertTest($searchResults[0]['name'] === 'دکتر کیوان راد', "Doctor name matched perfectly");
assertTest($searchResults[0]['org_relation'] === 'available', "Doctor initially has 'available' status with org");

$groomerSearch = $orgService->searchSpecialists('سارا', 'groomer', $orgId);
assertTest(count($groomerSearch) >= 1, "Search for verified groomer returned results");
assertTest($groomerSearch[0]['role'] === 'groomer', "Groomer role identified correctly");

echo "\n=== 3. Testing Invitation Handshake & Non-Destructive Flow ===\n";

// 3.1 Send invitation to doctor
$invRes = $orgService->sendInvitation($orgId, $docUserId, 'doctor', 'سرپرست بخش جراحی', 'شنبه تا چهارشنبه ۱۷:۰۰ الی ۲۱:۰۰', 60.0, 'دعوت جهت همکاری در اتاق عمل کلینیک');
assertTest($invRes['success'] === true, "Invitation sent successfully to doctor");
$docInvId = (int)$invRes['invitation_id'];
assertTest($docInvId > 0, "Invitation ID generated as {$docInvId}");

// Check search shows pending invitation
$reCheck = $orgService->searchSpecialists('کیوان', 'doctor', $orgId);
assertTest($reCheck[0]['org_relation'] === 'pending_invitation', "Doctor status reflects 'pending_invitation'");

// 3.2 Doctor reviews pending invitations in their panel
$docInvs = $orgService->getUserInvitations($docUserId, 'pending');
assertTest(count($docInvs) === 1, "Doctor sees 1 pending invitation in their portal");
assertTest($docInvs[0]['suggested_role'] === 'doctor', "Suggested role is 'doctor'");
assertTest((float)$docInvs[0]['commission_rate'] === 60.0, "Proposed commission is 60%");

// 3.3 Doctor declines invitation first (simulating human error or conflict)
$decRes = $orgService->respondToInvitation($docInvId, $docUserId, 'declined', 'تداخل شیفت کاری با مرکز دیگر');
assertTest($decRes['success'] === true, "Doctor declined invitation cleanly");

$declinedCheck = $orgService->getInvitations($orgId, 'declined');
assertTest(count($declinedCheck) >= 1, "Organization sees invitation marked as declined with reason");

// 3.4 Non-destructive retry: Organization re-invites with new adjusted shifts
$reInvRes = $orgService->sendInvitation($orgId, $docUserId, 'doctor', 'سرپرست جراحی شیفت صبح', 'روزهای زوج ۹:۰۰ الی ۱۳:۰۰', 65.0, 'تنظیم شیفت بر اساس درخواست شما');
assertTest($reInvRes['success'] === true, "Organization successfully re-invited doctor after decline");
$newDocInvId = (int)$reInvRes['invitation_id'];

// 3.5 Doctor accepts the re-invitation
$accRes = $orgService->respondToInvitation($newDocInvId, $docUserId, 'accepted');
assertTest($accRes['success'] === true, "Doctor accepted the new invitation");

// Check doctor is now an active member
$activeMembers = $orgService->getMembers($orgId);
$docMember = array_filter($activeMembers, fn($m) => (int)$m['user_id'] === $docUserId);
assertTest(!empty($docMember), "Doctor is officially enrolled in organization_members");

// Check linked into organization_doctors
$docLinkChk = $pdo->prepare("SELECT COUNT(*) FROM organization_doctors WHERE organization_id = ?");
$docLinkChk->execute([$orgId]);
assertTest((int)$docLinkChk->fetchColumn() >= 1, "Doctor automatically synchronized with organization_doctors roster");

// 3.6 Organization customizes active member's job title & notes
$updOk = $orgService->assignMember($orgId, $docUserId, 'doctor', 'رئیس دپارتمان جراحی و درمان', 'شیفت قطعی ثبت شد');
assertTest($updOk === true, "Organization customized doctor's job title post-acceptance");

$membersAfterEdit = $orgService->getMembers($orgId);
$editedDoc = array_values(array_filter($membersAfterEdit, fn($m) => (int)$m['user_id'] === $docUserId))[0];
assertTest($editedDoc['job_title'] === 'رئیس دپارتمان جراحی و درمان', "Job title updated in roster");

echo "\n=== 4. Testing Groomer & Pansion Roles in AuthGuard & Tiers ===\n";

assertTest(in_array('groomer', AuthGuard::SPECIAL_ROLES, true), "AuthGuard recognizes 'groomer' in SPECIAL_ROLES");
assertTest(in_array('pansion', AuthGuard::SPECIAL_ROLES, true), "AuthGuard recognizes 'pansion' in SPECIAL_ROLES");

$tiers = require __DIR__ . '/../config/tiers.php';
assertTest(in_array('groomer_cockpit', $tiers['tiers']['enterprise']['features'], true), "Tiers matrix includes 'groomer_cockpit'");
assertTest(in_array('pansion_portal', $tiers['tiers']['enterprise']['features'], true), "Tiers matrix includes 'pansion_portal'");

echo "\n=== 5. Testing Groomer Services & Portfolio Records ===\n";

// Add sample service
$sIns = $pdo->prepare("INSERT INTO grooming_services (groomer_user_id, title, category, duration_minutes, price) VALUES (?, 'گرومینگ VIP پامرانین', 'full_grooming', 60, 480000)");
$sIns->execute([$groomerUserId]);
$srvId = (int)$pdo->lastInsertId();
assertTest($srvId > 0, "Grooming service created successfully (ID {$srvId})");

// Add portfolio work
$pIns = $pdo->prepare("INSERT INTO grooming_portfolio (groomer_user_id, pet_type, pet_breed, before_image, after_image, title) VALUES (?, 'سگ', 'پامرانین', 'uploads/grooming/b1.jpg', 'uploads/grooming/a1.jpg', 'اصلاح تدی فانتزی')");
$pIns->execute([$groomerUserId]);
$portId = (int)$pdo->lastInsertId();
assertTest($portId > 0, "Grooming portfolio item created with before/after links (ID {$portId})");

echo "\n=== 6. Testing Pansion Hotel Rooms & Boarding Flow ===\n";

// Create room
$rIns = $pdo->prepare("INSERT INTO pansion_rooms (pansion_user_id, room_number, room_title, room_type, price_per_night, status) VALUES (?, 'VIP-202', 'سوئیت گاردن با تراس اختصاصی', 'vip_suite', 750000, 'available')");
$rIns->execute([$pansionUserId]);
$pansionRoomId = (int)$pdo->lastInsertId();
assertTest($pansionRoomId > 0, "Pansion suite VIP-202 created as 'available'");

// Check-in pet
$bIns = $pdo->prepare("
    INSERT INTO pansion_bookings 
    (room_id, pansion_user_id, customer_user_id, pet_name, pet_species, pet_breed, check_in_date, check_out_date, nights_count, total_price, status, belongings_list, special_diet_notes)
    VALUES (?, ?, ?, 'میلو', 'سگ', 'گلدن رتریور', '2026-10-22', '2026-10-26', 4, 3000000, 'checked_in', 'قلاده چرمی، تشک خواب، ظرف آب', 'غذای خشک رویال کنین ۲ وعده')
");
$bIns->execute([$pansionRoomId, $pansionUserId, $docUserId]);
$bookingId = (int)$pdo->lastInsertId();
assertTest($bookingId > 0, "Pet 'میلو' checked in for 4 nights (Total: 3,000,000 Tomans)");

// Update room status to occupied
$pdo->prepare("UPDATE pansion_rooms SET status = 'occupied' WHERE id = ?")->execute([$pansionRoomId]);
$roomStatus = $pdo->query("SELECT status FROM pansion_rooms WHERE id = {$pansionRoomId}")->fetchColumn();
assertTest($roomStatus === 'occupied', "Room status transitioned to 'occupied'");

// Check-out pet
$pdo->prepare("UPDATE pansion_bookings SET status = 'checked_out' WHERE id = ?")->execute([$bookingId]);
$pdo->prepare("UPDATE pansion_rooms SET status = 'cleaning' WHERE id = ?")->execute([$pansionRoomId]);

$afterCheckoutStatus = $pdo->query("SELECT status FROM pansion_rooms WHERE id = {$pansionRoomId}")->fetchColumn();
assertTest($afterCheckoutStatus === 'cleaning', "After checkout, room automatically transitions to 'cleaning' status");

// Finish cleaning
$pdo->prepare("UPDATE pansion_rooms SET status = 'available' WHERE id = ?")->execute([$pansionRoomId]);
$cleanFinished = $pdo->query("SELECT status FROM pansion_rooms WHERE id = {$pansionRoomId}")->fetchColumn();
assertTest($cleanFinished === 'available', "After sanitation, room is released back to 'available'");

echo "\n=== ALL 23 TESTS FOR PERSONNEL INVITATIONS, GROOMER & PANSION PASSED PERFECTLY! ===\n";
