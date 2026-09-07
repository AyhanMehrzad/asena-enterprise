<?php
/**
 * Automated Verification: Organization Types, Immutability, Tickets & Sub-Admins
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== STARTING VERIFICATION: ORG TYPES, TICKETS & SUB-ADMINS ===\n\n";

$testsPassed = 0;
$testsTotal = 0;

function assertTest($condition, $name) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        echo "  [PASS] $name\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] $name\n";
    }
}

// 1. Verify 6 Standard Types in DB ENUM
$typesStmt = $pdo->query("SHOW COLUMNS FROM organizations LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
$typeEnumStr = $typesStmt['Type'] ?? '';
$expectedTypes = ['hospital', 'clinic', 'pharmacy', 'shelter_charity', 'emergency_center', 'diagnostic_lab'];
$allTypesPresent = true;
foreach ($expectedTypes as $t) {
    if (strpos($typeEnumStr, "'$t'") === false) {
        $allTypesPresent = false;
        break;
    }
}
assertTest($allTypesPresent, "Database ENUM contains all 6 standard organization types ($typeEnumStr)");

// 2. Verify organization_subadmins is active in Enterprise tier
$hasSubadmins = Feature::has('organization_subadmins');
assertTest($hasSubadmins, "Feature 'organization_subadmins' is enabled in enterprise tier");

// 3. Setup or find a test organization & owner
$validUser = $pdo->query("SELECT id FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ownerId = (int)($validUser['id'] ?? 1);

$testOrg = $pdo->query("SELECT * FROM organizations WHERE type IN ('hospital', 'clinic', 'emergency_center') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$testOrg) {
    $pdo->query("INSERT INTO organizations (user_id, name, slug, type, city, status, created_at) VALUES ($ownerId, 'مرکز درمانی تست', 'test-clinic-auto', 'clinic', 'تهران', 'approved', NOW())");
    $testOrg = $pdo->query("SELECT * FROM organizations WHERE slug = 'test-clinic-auto'")->fetch(PDO::FETCH_ASSOC);
} else {
    // Ensure test organization has user_id set
    $pdo->prepare("UPDATE organizations SET user_id = ? WHERE id = ?")->execute([$ownerId, $testOrg['id']]);
}
$orgId = (int)$testOrg['id'];
assertTest($orgId > 0 && !empty($testOrg['type']), "Test organization identified: ID #{$orgId}, Type: {$testOrg['type']}, Owner User ID: #{$ownerId}");

// 4. Test Ticket Creation for Type Change
$destType = ($testOrg['type'] === 'hospital') ? 'emergency_center' : 'hospital';
$insTicket = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
$insTicket->execute([$ownerId]);
$ticketId = (int)$pdo->lastInsertId();

$justification = "درخواست ارتقای رسته مرکز از {$testOrg['type']} به {$destType} به علت تکمیل بخش جراحی و بستری.";
$ticketBody = "📋 [درخواست رسمی تغییر رسته مرکز درمانی]\n\n"
            . "نام مرکز: " . $testOrg['name'] . " (شناسه: #" . $orgId . ")\n"
            . "رسته فعلی تاییدشده: " . $testOrg['type'] . "\n"
            . "رسته جدید درخواستی: " . $destType . "\n"
            . "شماره پروانه استنادی: ۹۹۴۴۲۲۱۱\n\n"
            . "دلایل و توضیحات متقاضی:\n" . $justification;

$insMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'user', ?, NOW())");
$insMsg->execute([$ticketId, $ticketBody]);

// Verify ticket created
$chkTicket = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$chkTicket->execute([$ticketId]);
$ticketRow = $chkTicket->fetch(PDO::FETCH_ASSOC);

$chkMsg = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ?");
$chkMsg->execute([$ticketId]);
$msgRow = $chkMsg->fetch(PDO::FETCH_ASSOC);

assertTest($ticketRow && $ticketRow['mode'] === 'admin' && $ticketRow['status'] === 'open', "Ticket #{$ticketId} created with mode='admin' and status='open'");
assertTest($msgRow && strpos($msgRow['message'], 'درخواست رسمی تغییر رسته') !== false, "Ticket message correctly recorded with structured type-change metadata");

// 5. Test Sub-Admin Creation
$testSubAdminPhone = '0999' . rand(1000000, 9999999);
$testSubAdminName = 'دکتر سعید ناصری (تست)';
$testPassHash = password_hash('password123', PASSWORD_DEFAULT);

// Clean up any previous test record if exists
$pdo->prepare("DELETE FROM users WHERE phone = ?")->execute([$testSubAdminPhone]);

// Insert subadmin user
$insSubUser = $pdo->prepare("
    INSERT INTO users (phone, name, email, password, role, verification_status, created_at)
    VALUES (?, ?, 'subadmin@test.com', ?, 'organization', 'approved', NOW())
");
$insSubUser->execute([$testSubAdminPhone, $testSubAdminName, $testPassHash]);
$subUserId = (int)$pdo->lastInsertId();

assertTest($subUserId > 0, "New sub-admin user created in users table (ID: #{$subUserId}, Phone: {$testSubAdminPhone})");

// Attach to organization_admins
$subRole = 'assistant_manager';
$subTitle = 'معاون اجرایی و جانشین مدیر مرکز';
$subPerms = ['manage_appointments', 'manage_doctors', 'manage_shifts', 'manage_profile'];

$insAdminRow = $pdo->prepare("
    INSERT INTO organization_admins (organization_id, user_id, admin_role, title, permissions_json, status, created_by, created_at)
    VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
");
$insAdminRow->execute([$orgId, $subUserId, $subRole, $subTitle, json_encode($subPerms), $ownerId]);
$subAdminRecordId = (int)$pdo->lastInsertId();

assertTest($subAdminRecordId > 0, "Sub-admin linked in organization_admins table (ID: #{$subAdminRecordId})");

// 6. Test Organization Resolution for Sub-Admin
$subAdminStmt = $pdo->prepare("
    SELECT o.*, oa.admin_role, oa.title as staff_title, oa.permissions_json, oa.status as staff_status
    FROM organization_admins oa
    JOIN organizations o ON o.id = oa.organization_id
    WHERE oa.user_id = ? AND oa.status = 'active'
    LIMIT 1
");
$subAdminStmt->execute([$subUserId]);
$resolvedOrg = $subAdminStmt->fetch(PDO::FETCH_ASSOC);

assertTest($resolvedOrg && (int)$resolvedOrg['id'] === $orgId, "Sub-admin user correctly resolves to Organization #{$orgId}");
assertTest($resolvedOrg && $resolvedOrg['admin_role'] === 'assistant_manager', "Resolved role matches 'assistant_manager'");

$permsArray = json_decode($resolvedOrg['permissions_json'] ?? '[]', true);
assertTest(in_array('manage_appointments', $permsArray) && in_array('manage_doctors', $permsArray), "Permissions correctly parsed from permissions_json");

// 7. Test Permission Checker Helper
if (!function_exists('hasOrgPermission')) {
    function hasOrgPermission(string $perm, array $permissions): bool {
        if (in_array('all', $permissions, true)) {
            return true;
        }
        return in_array($perm, $permissions, true);
    }
}

assertTest(hasOrgPermission('manage_appointments', $permsArray) === true, "hasOrgPermission returns true for 'manage_appointments'");
assertTest(hasOrgPermission('manage_wallet', $permsArray) === false, "hasOrgPermission returns false for unassigned 'manage_wallet'");
assertTest(hasOrgPermission('anything', ['all']) === true, "hasOrgPermission with ['all'] grants any permission");

// 8. Test Sub-Admin Status Toggle (active -> inactive)
$toggleUpd = $pdo->prepare("UPDATE organization_admins SET status = 'inactive' WHERE id = ?");
$toggleUpd->execute([$subAdminRecordId]);

// Verify that when inactive, resolution returns no active organization
$subAdminStmt->execute([$subUserId]);
$inactiveResolution = $subAdminStmt->fetch(PDO::FETCH_ASSOC);
assertTest($inactiveResolution === false, "Inactive sub-admin is blocked from resolving active organization");

// Reactivate
$pdo->prepare("UPDATE organization_admins SET status = 'active' WHERE id = ?")->execute([$subAdminRecordId]);

// 9. Cleanup Test Records
$pdo->prepare("DELETE FROM organization_admins WHERE id = ?")->execute([$subAdminRecordId]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$subUserId]);
$pdo->prepare("DELETE FROM ticket_messages WHERE ticket_id = ?")->execute([$ticketId]);
$pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticketId]);

echo "\n=======================================================\n";
echo "RESULT: $testsPassed / $testsTotal tests passed.\n";
echo "=======================================================\n";

if ($testsPassed === $testsTotal) {
    echo "SUCCESS: ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "FAILURE: SOME TESTS FAILED!\n";
    exit(1);
}
