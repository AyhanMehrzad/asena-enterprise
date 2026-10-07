<?php
/**
 * Test Suite: Organization Tickets Resilience & Anti-Blank-Page Validation
 * Ensures organization/tickets.php never dies silently or renders empty content.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== ASENA Enterprise: Organization Tickets Resilience Test ===\n\n";

$passed = 0;
$failed = 0;

function assertTest($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $passed++;
    } else {
        echo " [FAIL] $name\n";
        $failed++;
    }
}

// 1. Setup mock organization user in DB
$pdo->exec("INSERT OR IGNORE INTO users (id, name, phone, role) VALUES (888, 'مدیر آزمایشی کلینیک', '09128888888', 'organization')");
$pdo->exec("UPDATE users SET role = 'organization' WHERE id = 888");

$pdo->exec("INSERT OR IGNORE INTO organizations (id, user_id, name, slug, phone, status) VALUES (888, 888, 'کلینیک دامپزشکی آزمایشی پایتخت', 'paytakht-test', '09128888888', 'approved')");
$pdo->exec("UPDATE organizations SET user_id = 888, name = 'کلینیک دامپزشکی آزمایشی پایتخت' WHERE id = 888");

$_SESSION['user_id'] = 888;
$_SESSION['role'] = 'organization';
$_SESSION['name'] = 'مدیر آزمایشی کلینیک';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/organization/tickets.php?tab=clients';
$_GET['tab'] = 'clients';

// 2. Load tickets.php and ensure schema
ob_start();
require_once __DIR__ . '/../organization/tickets.php';
$html = ob_get_clean();

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$ticketCols = [];
if ($driver === 'sqlite') {
    $ticketCols = $pdo->query("PRAGMA table_info(tickets)")->fetchAll(PDO::FETCH_COLUMN, 1) ?: [];
} else {
    $ticketCols = $pdo->query("SHOW COLUMNS FROM `tickets`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

assertTest("tickets table exists", in_array('id', $ticketCols));
assertTest("tickets.organization_id column exists", in_array('organization_id', $ticketCols));
assertTest("tickets.subject column exists", in_array('subject', $ticketCols));

// 3. Test GET request to organization/tickets.php (Clients tab)
$_SESSION['user_id'] = 888;
$_SESSION['role'] = 'organization';
$_SESSION['name'] = 'مدیر آزمایشی کلینیک';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/organization/tickets.php?tab=clients';
$_GET['tab'] = 'clients';

ob_start();
include __DIR__ . '/../organization/tickets.php';
$html = ob_get_clean();

assertTest("Clients tab: Output is not blank (length > 5000 bytes)", strlen($html) > 5000);
assertTest("Clients tab: Header title rendered", strpos($html, 'میز تیکت و پشتیبانی مرکز') !== false);
assertTest("Clients tab: Chat pane rendered", strpos($html, 'org-chat-pane') !== false);
assertTest("Clients tab: Empty state or message stream present", strpos($html, 'org-ticket-list') !== false);

// 4. Test GET request to organization/tickets.php (Admin tab)
$_GET['tab'] = 'admin';
$_SERVER['REQUEST_URI'] = '/organization/tickets.php?tab=admin';

ob_start();
include __DIR__ . '/../organization/tickets.php';
$htmlAdmin = ob_get_clean();

assertTest("Admin tab: Output is not blank (length > 5000 bytes)", strlen($htmlAdmin) > 5000);
assertTest("Admin tab: Tab link active", strpos($htmlAdmin, 'ارتباط با مدیریت کل آسنا') !== false);

// 5. Test POST request: New ticket to Asena Super Admin
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => csrf_token(),
    'action' => 'new_admin_ticket',
    'category' => 'پشتیبانی سامانه‌ای',
    'subject' => 'درخواست افزودن دسترسی شیفت شب',
    'message' => 'سلام، لطفاً شیفت شب مرکز ما را در سیستم فعال نمایید.'
];

ob_start();
include __DIR__ . '/../organization/tickets.php';
$postHtml = ob_get_clean();

assertTest("POST ticket: Success notice displayed", strpos($postHtml, 'تیکت جدید با موفقیت برای مدیریت آسنا ارسال گردید') !== false);

// 6. Test chat_action fetch on newly created ticket
$lastTicketId = (int)$pdo->query("SELECT id FROM tickets WHERE organization_id = 888 ORDER BY id DESC LIMIT 1")->fetchColumn();
assertTest("Newly created ticket ID found in database", $lastTicketId > 0);

$chatCmd = "/opt/lampp/bin/php -r " . escapeshellarg('
session_start();
$_SESSION["user_id"] = 888;
$_SESSION["role"] = "organization";
$_POST = ["action" => "fetch", "ticket_id" => ' . $lastTicketId . ', "last_id" => 0];
$_SERVER["REQUEST_METHOD"] = "POST";
include "actions/chat_action.php";
');

$chatJson = shell_exec($chatCmd);
$chatData = json_decode($chatJson, true);

assertTest("chat_action fetch status is success", ($chatData['status'] ?? '') === 'success');
assertTest("chat_action fetch returned welcome and initial message", count($chatData['messages'] ?? []) >= 2);

echo "\nSummary: $passed passed, $failed failed.\n";
if ($failed > 0) {
    exit(1);
}
