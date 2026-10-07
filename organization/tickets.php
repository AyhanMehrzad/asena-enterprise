<?php
/**
 * ASENA Enterprise - Organization Ticketing & Support Console
 * Features:
 * 1. Direct administrative communication with Platform Super Admin (Talk with Asena Admin)
 * 2. Dedicated Client Inquiries stream (Pet owner questions sent specifically to this clinic)
 * Strict Zero-Trust Data Isolation: Each organization only accesses its own tickets.
 */

require_once 'includes/organization_header.php';

/**
 * Self-healing tickets & ticket_messages schema alignment
 * Guarantees cross-database resilience across local SQLite and cPanel production MySQL
 */
if (!function_exists('ensure_tickets_system_schema')) {
    function ensure_tickets_system_schema(PDO $pdo): void {
        static $ensured = false;
        if ($ensured) return;
        $ensured = true;

        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS tickets (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        organization_id INTEGER NULL,
                        doctor_id INTEGER NULL,
                        subject TEXT NULL,
                        mode TEXT DEFAULT 'admin',
                        status TEXT DEFAULT 'open',
                        target_role TEXT NULL,
                        target_id INTEGER NULL,
                        closed_by INTEGER NULL,
                        resolution_notes TEXT NULL,
                        last_notified_at DATETIME NULL,
                        last_user_notified_at DATETIME NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS ticket_messages (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        ticket_id INTEGER NOT NULL,
                        sender_type TEXT NOT NULL DEFAULT 'user',
                        message TEXT NULL,
                        image_url TEXT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $cols = $pdo->query("PRAGMA table_info(tickets)")->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('organization_id', $cols)) {
                    @$pdo->exec("ALTER TABLE tickets ADD COLUMN organization_id INTEGER");
                }
                if (!in_array('subject', $cols)) {
                    @$pdo->exec("ALTER TABLE tickets ADD COLUMN subject TEXT");
                }
                if (!in_array('doctor_id', $cols)) {
                    @$pdo->exec("ALTER TABLE tickets ADD COLUMN doctor_id INTEGER");
                }
            } else {
                // MySQL / MariaDB
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `tickets` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `organization_id` int(11) NULL DEFAULT NULL,
                        `doctor_id` int(11) NULL DEFAULT NULL,
                        `subject` varchar(255) NULL DEFAULT NULL,
                        `mode` varchar(50) NOT NULL DEFAULT 'admin',
                        `status` enum('open','closed','resolved') NOT NULL DEFAULT 'open',
                        `target_role` varchar(50) NULL DEFAULT NULL,
                        `target_id` int(11) NULL DEFAULT NULL,
                        `closed_by` int(11) NULL DEFAULT NULL,
                        `resolution_notes` text NULL DEFAULT NULL,
                        `last_notified_at` datetime NULL DEFAULT NULL,
                        `last_user_notified_at` datetime NULL DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_tickets_user` (`user_id`),
                        KEY `idx_tickets_org` (`organization_id`),
                        KEY `idx_tickets_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `ticket_messages` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `ticket_id` int(11) NOT NULL,
                        `sender_type` varchar(50) NOT NULL DEFAULT 'user',
                        `message` text DEFAULT NULL,
                        `image_url` varchar(500) DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_messages_ticket` (`ticket_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $cols = $pdo->query("SHOW COLUMNS FROM `tickets`")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('organization_id', $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE `tickets` ADD COLUMN `organization_id` INT(11) NULL AFTER `user_id`");
                    } catch (Throwable $e) {}
                }
                if (!in_array('subject', $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE `tickets` ADD COLUMN `subject` VARCHAR(255) NULL AFTER `organization_id`");
                    } catch (Throwable $e) {}
                }
                if (!in_array('doctor_id', $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE `tickets` ADD COLUMN `doctor_id` INT(11) NULL AFTER `subject`");
                    } catch (Throwable $e) {}
                }
                if (!in_array('target_role', $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE `tickets` ADD COLUMN `target_role` VARCHAR(50) NULL AFTER `doctor_id`");
                    } catch (Throwable $e) {}
                }
                if (!in_array('target_id', $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE `tickets` ADD COLUMN `target_id` INT(11) NULL AFTER `target_role`");
                    } catch (Throwable $e) {}
                }

                // Expand mode and sender_type
                try {
                    $pdo->exec("ALTER TABLE `tickets` MODIFY COLUMN `mode` VARCHAR(50) NOT NULL DEFAULT 'admin'");
                } catch (Throwable $e) {}
                try {
                    $pdo->exec("ALTER TABLE `ticket_messages` MODIFY COLUMN `sender_type` VARCHAR(50) NOT NULL DEFAULT 'user'");
                } catch (Throwable $e) {}
            }
        } catch (Throwable $e) {
            error_log("ensure_tickets_system_schema notice: " . $e->getMessage());
        }
    }
}
ensure_tickets_system_schema($pdo);

// Inspect available columns on tickets for maximum defensive queries
$ticketsCols = [];
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $ticketsCols = $pdo->query("PRAGMA table_info(tickets)")->fetchAll(PDO::FETCH_COLUMN, 1) ?: [];
    } else {
        $ticketsCols = $pdo->query("SHOW COLUMNS FROM `tickets`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
} catch (Throwable $e) {}

$hasOrgCol = in_array('organization_id', $ticketsCols);
$hasSubjectCol = in_array('subject', $ticketsCols);

$orgId = (int)($currentOrg['id'] ?? 0);
$orgUserId = (int)($currentOrg['user_id'] ?? $currentUser['id']);

// Handle Status Toggle (Open/Closed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    csrf_verify();
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $newStatus = ($_POST['status'] === 'closed') ? 'closed' : 'open';
    if ($ticketId > 0) {
        try {
            // IDOR verify ticket belongs to this organization
            if ($hasOrgCol) {
                $chk = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND (organization_id = ? OR (user_id = ? AND mode = 'admin'))");
                $chk->execute([$ticketId, $orgId, $orgUserId]);
            } else {
                $chk = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
                $chk->execute([$ticketId, $orgUserId]);
            }
            if ($chk->fetchColumn()) {
                $pdo->prepare("UPDATE tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$newStatus, $ticketId]);
            }
        } catch (Throwable $e) {
            error_log("Ticket status toggle error: " . $e->getMessage());
        }
        header("Location: tickets.php?tab=" . urlencode($_GET['tab'] ?? 'clients'));
        exit;
    }
}

// Handle New Ticket to Asena Admin
$notice = '';
$noticeType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_admin_ticket') {
    csrf_verify();
    $subject = trim($_POST['subject'] ?? 'درخواست مرکز درمانی');
    $category = trim($_POST['category'] ?? 'عمومی');
    $message = trim($_POST['message'] ?? '');

    if (!empty($message)) {
        try {
            $orgName = $currentOrg['name'] ?? 'مرکز درمانی';
            $fullSubject = "[{$orgName}] {$category}: {$subject}";

            if ($hasSubjectCol && $hasOrgCol) {
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, subject, mode, organization_id, status, created_at, updated_at) 
                    VALUES (?, ?, 'admin', ?, 'open', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$orgUserId, $fullSubject, $orgId]);
            } elseif ($hasOrgCol) {
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, mode, organization_id, status, created_at, updated_at) 
                    VALUES (?, 'admin', ?, 'open', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$orgUserId, $orgId]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, mode, status, created_at, updated_at) 
                    VALUES (?, 'admin', 'open', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$orgUserId]);
            }
            $newTicketId = (int)$pdo->lastInsertId();

            $msgStmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'user', ?, CURRENT_TIMESTAMP)");
            $msgStmt->execute([$newTicketId, $message]);

            // Automated welcome acknowledgement
            try {
                $pdo->prepare("
                    INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) 
                    VALUES (?, 'admin', 'سلام و احترام همکار گرامی. پیام شما برای تیم مدیریت ارشد و پشتیبانی تخصصی آسنا ثبت شد و در اسرع وقت بررسی خواهد شد.', CURRENT_TIMESTAMP)
                ")->execute([$newTicketId]);
            } catch (Throwable $eAck) {}

            $notice = "تیکت جدید با موفقیت برای مدیریت آسنا ارسال گردید.";
            $noticeType = "success";
        } catch (Throwable $e) {
            error_log("Error creating admin ticket: " . $e->getMessage());
            $notice = "خطا در ثبت تیکت. لطفاً مجدداً تلاش فرمایید.";
            $noticeType = "error";
        }
    } else {
        $notice = "لطفاً متن پیام را وارد فرمایید.";
        $noticeType = "error";
    }
}

// Tab Filter: 'clients' (default) or 'admin'
$activeTab = trim($_GET['tab'] ?? 'clients');
if (!in_array($activeTab, ['clients', 'admin'])) {
    $activeTab = 'clients';
}

// Fetch Client Inquiries for THIS organization only
$clientTickets = [];
if ($orgId > 0 && $hasOrgCol) {
    try {
        $subjectExpr = $hasSubjectCol 
            ? "t.subject" 
            : "COALESCE((SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id ASC LIMIT 1), 'پیام مراجعین')";

        $cStmt = $pdo->prepare("
            SELECT t.id, t.status, t.created_at, t.updated_at,
                   {$subjectExpr} as subject,
                   u.name as client_name, u.phone as client_phone,
                   COALESCE((SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1), 'پیامی ثبت نشده') as last_message,
                   (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE t.mode = 'organization' AND t.organization_id = ?
            ORDER BY (t.status = 'open') DESC, t.updated_at DESC
        ");
        $cStmt->execute([$orgId]);
        $clientTickets = $cStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log("Error fetching client tickets: " . $e->getMessage());
        $clientTickets = [];
    }
}

// Fetch Admin Tickets for THIS organization only
$adminTickets = [];
try {
    $subjectExpr = $hasSubjectCol 
        ? "t.subject" 
        : "COALESCE((SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id ASC LIMIT 1), 'مکاتبه با مدیریت')";

    if ($hasOrgCol) {
        $aStmt = $pdo->prepare("
            SELECT t.id, t.status, t.created_at, t.updated_at,
                   {$subjectExpr} as subject,
                   COALESCE((SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1), 'پیامی ثبت نشده') as last_message,
                   (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender
            FROM tickets t
            WHERE t.mode = 'admin' AND (t.organization_id = ? OR t.user_id = ?)
            ORDER BY (t.status = 'open') DESC, t.updated_at DESC
        ");
        $aStmt->execute([$orgId, $orgUserId]);
    } else {
        $aStmt = $pdo->prepare("
            SELECT t.id, t.status, t.created_at, t.updated_at,
                   {$subjectExpr} as subject,
                   COALESCE((SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1), 'پیامی ثبت نشده') as last_message,
                   (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender
            FROM tickets t
            WHERE t.mode = 'admin' AND t.user_id = ?
            ORDER BY (t.status = 'open') DESC, t.updated_at DESC
        ");
        $aStmt->execute([$orgUserId]);
    }
    $adminTickets = $aStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log("Error fetching admin tickets: " . $e->getMessage());
    $adminTickets = [];
}

// Counts
$clientOpenCount = 0;
foreach ($clientTickets as $ct) {
    if (($ct['status'] ?? '') === 'open') $clientOpenCount++;
}
$adminOpenCount = 0;
foreach ($adminTickets as $at) {
    if (($at['status'] ?? '') === 'open') $adminOpenCount++;
}
?>

<div class="p-6 md:p-8 space-y-6 max-w-[1600px] mx-auto rtl text-right" dir="rtl">

    <?php if (!empty($notice)): ?>
    <div class="p-4 rounded-2xl text-xs font-bold flex items-center justify-between <?= $noticeType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base"><?= $noticeType === 'success' ? 'check_circle' : 'error' ?></span>
            <span><?= htmlspecialchars($notice) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-secondary-container">support_agent</span>
                <h1 class="text-2xl font-black text-on-surface">میز تیکت و پشتیبانی مرکز</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-500 text-white shadow-sm">
                    <?= $activeTab === 'clients' ? ($clientOpenCount . ' پیام مراجعین نیازمند پاسخ') : ($adminOpenCount . ' تیکت اداری فعال') ?>
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-1">
                پاسخ‌گویی اختصاصی به سوالات و استعلام‌های مراجعین کلینیک و ارتباط مستقیم با مدیریت کل سامانه آسنا.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="openNewAdminTicketModal()" class="px-4 py-2.5 rounded-xl text-xs font-black bg-secondary-container hover:bg-secondary text-white transition-all flex items-center gap-1.5 shadow-md">
                <span class="material-symbols-outlined text-base">outgoing_mail</span>
                <span>+ تیکت جدید با مدیریت آسنا</span>
            </button>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="flex items-center gap-2 border-b border-outline-variant/30 pb-2">
        <a href="tickets.php?tab=clients" class="px-4 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 <?= $activeTab === 'clients' ? 'bg-primary text-white shadow-md' : 'bg-surface-container text-on-surface-variant hover:text-primary' ?>">
            <span class="material-symbols-outlined text-base">forum</span>
            <span>پیام‌ها و سوالات مراجعین کلینیک</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'clients' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' ?>"><?= count($clientTickets) ?></span>
        </a>

        <a href="tickets.php?tab=admin" class="px-4 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 <?= $activeTab === 'admin' ? 'bg-indigo-600 text-white shadow-md' : 'bg-surface-container text-on-surface-variant hover:text-indigo-600' ?>">
            <span class="material-symbols-outlined text-base">admin_panel_settings</span>
            <span>ارتباط با مدیریت کل آسنا (Asena Admin)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'admin' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-800' ?>"><?= count($adminTickets) ?></span>
        </a>
    </div>

    <!-- Main Split-View Workspace -->
    <div class="bg-surface-container-lowest rounded-3xl shadow-sm border border-outline-variant/20 flex flex-col md:flex-row h-[750px] overflow-hidden">
        
        <!-- Tickets Sidebar List -->
        <div class="w-full md:w-5/12 lg:w-4/12 border-l border-outline-variant/20 flex flex-col bg-surface-container-lowest">
            <div class="p-3.5 border-b border-outline-variant/20 bg-surface-container-lowest">
                <div class="relative">
                    <span class="absolute inset-y-0 right-3 flex items-center text-outline">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </span>
                    <input id="org-ticket-search" onkeyup="filterOrgTickets()" class="w-full pr-10 pl-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="جستجو در پیام‌ها یا شماره تماس..." type="text"/>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5" id="org-ticket-list">
                <?php 
                $ticketsToDisplay = ($activeTab === 'clients') ? $clientTickets : $adminTickets;
                if (empty($ticketsToDisplay)): ?>
                    <div class="p-8 text-center text-on-surface-variant/60 text-xs">
                        <?= $activeTab === 'clients' ? 'هنوز پیامی از طرف مراجعین برای این مرکز ارسال نشده است.' : 'مکاتبه‌ای با مدیریت آسنا ثبت نشده است.' ?>
                    </div>
                <?php else: 
                    foreach($ticketsToDisplay as $t): 
                        $isOpen = ($t['status'] === 'open');
                        $title = ($activeTab === 'clients') ? ($t['client_name'] ?: 'کاربر ' . $t['client_phone']) : ($t['subject'] ?: 'مکاتبه با مدیریت');
                        $subInfo = ($activeTab === 'clients') ? $t['client_phone'] : 'شناسه تیکت #' . $t['id'];
                    ?>
                    <button onclick="loadOrgTicket(<?= (int)$t['id'] ?>, '<?= htmlspecialchars(addslashes($title)) ?>', '<?= $isOpen ? 'open' : 'closed' ?>', '<?= $activeTab ?>')" 
                            class="org-ticket-card w-full text-right p-3.5 rounded-2xl hover:bg-surface-container transition-all flex flex-col gap-1.5 border border-transparent hover:border-outline-variant/20 focus:bg-primary/5 focus:border-primary/30 group" 
                            data-search="<?= htmlspecialchars(strtolower($title . ' ' . $subInfo . ' ' . $t['last_message'])) ?>">
                        
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-8 h-8 rounded-xl <?= $activeTab === 'clients' ? 'bg-sky-50 text-sky-700' : 'bg-indigo-50 text-indigo-700' ?> flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-sm"><?= $activeTab === 'clients' ? 'person' : 'admin_panel_settings' ?></span>
                                </div>
                                <div class="truncate">
                                    <h4 class="font-black text-xs text-on-surface truncate"><?= htmlspecialchars($title) ?></h4>
                                    <span class="text-[10px] text-on-surface-variant font-mono" dir="ltr"><?= htmlspecialchars($subInfo) ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <?php if ($isOpen): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        باز
                                    </span>
                                <?php else: ?>
                                    <span class="text-[9px] text-slate-400">بسته شده</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="text-[11px] text-on-surface-variant/80 line-clamp-1 leading-snug" dir="auto">
                            <?php if ($t['last_sender'] === 'admin'): ?>
                                <span class="font-bold text-primary"><?= $activeTab === 'clients' ? 'مرکز درمانی:' : 'مدیریت آسنا:' ?> </span>
                            <?php endif; ?>
                            <?= htmlspecialchars($t['last_message']) ?>
                        </p>

                        <div class="flex items-center justify-between text-[10px] text-outline pt-1 border-t border-outline-variant/10">
                            <span>تیکت #<?= $t['id'] ?></span>
                            <span><?= date('m/d H:i', strtotime($t['updated_at'])) ?></span>
                        </div>
                    </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Conversation Area -->
        <div class="flex-1 flex flex-col bg-surface-container-low/30 relative">
            <div id="org-chat-placeholder" class="m-auto text-center p-8 text-slate-400 space-y-3">
                <span class="material-symbols-outlined text-5xl text-slate-300">chat_bubble_outline</span>
                <p class="text-xs font-bold">جهت مشاهده پیام‌ها، یک گفتگو را از فهرست انتخاب فرمایید.</p>
            </div>

            <!-- Active Chat Box -->
            <div id="org-chat-pane" class="hidden flex-1 flex flex-col h-full">
                <!-- Top Header -->
                <div class="p-3.5 px-6 bg-white border-b border-outline-variant/20 flex items-center justify-between shadow-sm shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center font-bold" id="org-active-avatar">
                            <span class="material-symbols-outlined text-xl">forum</span>
                        </div>
                        <div>
                            <h3 class="font-black text-sm text-on-surface" id="org-active-title">-</h3>
                            <span class="text-[10px] text-on-surface-variant" id="org-active-subtitle">-</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" id="org-toggle-form" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="ticket_id" id="org-toggle-ticket-id" value="">
                            <input type="hidden" name="status" id="org-toggle-status-val" value="">
                            <button type="submit" id="org-toggle-status-btn" class="px-3 py-1.5 text-xs font-bold rounded-xl transition-colors flex items-center gap-1">
                                <!-- Dynamic -->
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Messages Stream -->
                <div id="org-chat-messages" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4">
                    <!-- Injected via JS -->
                </div>

                <!-- Footer Input -->
                <div class="p-3.5 bg-white border-t border-outline-variant/20 shrink-0">
                    <form onsubmit="sendOrgMessage(event)" class="flex items-center gap-2">
                        <input id="org-chat-input" type="text" dir="auto" placeholder="پاسخ خود را بنویسید..." autocomplete="off" class="flex-1 px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-2xl text-xs focus:ring-2 focus:ring-primary outline-none transition-all font-medium">
                        <button type="submit" id="org-chat-send-btn" class="px-5 py-3 bg-secondary-container hover:bg-secondary text-white rounded-2xl font-black text-xs transition-all flex items-center gap-1.5 shadow-sm shrink-0">
                            <span class="material-symbols-outlined text-base">send</span>
                            <span>ارسال پاسخ</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modal: New Ticket to Asena Admin -->
<div id="newAdminTicketModal" class="fixed inset-0 bg-black/50 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 text-right">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container text-xl">outgoing_mail</span>
                <h3 class="font-black text-slate-900 text-sm">ارسال تیکت جدید به مدیریت کل آسنا</h3>
            </div>
            <button onclick="closeNewAdminTicketModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="tickets.php?tab=admin" class="space-y-3.5 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="new_admin_ticket">

            <div>
                <label class="block font-bold text-slate-700 mb-1">دپارتمان / موضوع پیگیری:</label>
                <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-800 outline-none focus:ring-2 focus:ring-secondary-container">
                    <option value="امور مالی و تسویه پایا">امور مالی و تسویه حساب‌های پایا (بانک مرکزی)</option>
                    <option value="پروانه و صلاحیت بالینی">تطبیق مدارک، پروانه اشتغال و صلاحیت پزشکان</option>
                    <option value="پشتیبانی سامانه‌ای">پشتیبانی نرم‌افزاری و پنل مرکز</option>
                    <option value="مغایرت و شکایات">گزارش مغایرت یا پیگیری حقوقی</option>
                    <option value="سایر موارد">سایر درخواست‌ها و مکاتبات اداری</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">عنوان خلاصه:</label>
                <input type="text" name="subject" required placeholder="مثال: درخواست تسویه هفتگی دور دوم شهریور" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-800 outline-none focus:ring-2 focus:ring-secondary-container">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">متن کامل درخواست یا پیام:</label>
                <textarea name="message" rows="4" required dir="auto" placeholder="توضیحات کامل درخواست خود را بنویسید..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-800 outline-none focus:ring-2 focus:ring-secondary-container leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeNewAdminTicketModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition-colors">انصراف</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-secondary-container text-white font-black hover:bg-secondary transition-all shadow-md">ثبت و ارسال تیکت</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentOrgTicketId = null;
let currentTabType = '<?= $activeTab ?>';
let orgLastMessageId = 0;
let orgPollingInterval = null;

function filterOrgTickets() {
    const q = document.getElementById('org-ticket-search').value.toLowerCase();
    document.querySelectorAll('.org-ticket-card').forEach(c => {
        const text = c.getAttribute('data-search') || '';
        c.style.display = text.includes(q) ? 'flex' : 'none';
    });
}

function openNewAdminTicketModal() {
    document.getElementById('newAdminTicketModal').classList.remove('hidden');
}

function closeNewAdminTicketModal() {
    document.getElementById('newAdminTicketModal').classList.add('hidden');
}

function loadOrgTicket(ticketId, title, status, tabType) {
    currentOrgTicketId = ticketId;
    currentTabType = tabType;
    orgLastMessageId = 0;

    document.getElementById('org-chat-placeholder').classList.add('hidden');
    document.getElementById('org-chat-pane').classList.remove('hidden');

    document.getElementById('org-active-title').innerText = title;
    document.getElementById('org-active-subtitle').innerText = (tabType === 'clients') ? 'پیام مراجعه‌کننده کلینیک' : 'گفتگو با سوپراَدمین آسنا';

    document.getElementById('org-toggle-ticket-id').value = ticketId;
    const toggleBtn = document.getElementById('org-toggle-status-btn');
    const toggleVal = document.getElementById('org-toggle-status-val');
    if (status === 'closed') {
        toggleVal.value = 'open';
        toggleBtn.innerHTML = '<span class="material-symbols-outlined text-sm">lock_open</span><span>بازگشایی تیکت</span>';
        toggleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors flex items-center gap-1';
    } else {
        toggleVal.value = 'closed';
        toggleBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span><span>بستن تیکت</span>';
        toggleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition-colors flex items-center gap-1';
    }

    const msgContainer = document.getElementById('org-chat-messages');
    msgContainer.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">در حال بارگذاری گفتگو...</div>';

    if (orgPollingInterval) clearInterval(orgPollingInterval);
    fetchOrgMessages();
    orgPollingInterval = setInterval(fetchOrgMessages, 3000);
}

function fetchOrgMessages() {
    if (!currentOrgTicketId) return;

    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentOrgTicketId);
    fd.append('last_id', orgLastMessageId);

    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                if (orgLastMessageId === 0) {
                    document.getElementById('org-chat-messages').innerHTML = '';
                }
                renderOrgMessages(data.messages);
                orgLastMessageId = data.messages[data.messages.length - 1].id;
                const container = document.getElementById('org-chat-messages');
                container.scrollTop = container.scrollHeight;
            } else if (orgLastMessageId === 0 && (!data.messages || data.messages.length === 0)) {
                document.getElementById('org-chat-messages').innerHTML = '<div class="text-center py-8 text-xs text-slate-400">هیچ پیامی در این تیکت وجود ندارد.</div>';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderOrgMessages(messages) {
    const container = document.getElementById('org-chat-messages');

    messages.forEach(msg => {
        // When clinic is talking to clients: clinic is 'admin' or 'organization', user is client.
        // When clinic is talking to Asena Admin: clinic is 'user', admin is Asena Admin.
        const isSelf = (currentTabType === 'clients') ? (msg.sender_type === 'admin' || msg.sender_type === 'organization') : (msg.sender_type === 'user');
        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
        const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');

        let imgHtml = '';
        if (msg.image_url) {
            const safeImg = escapeHtml(msg.image_url);
            imgHtml = `<img src="../${safeImg}" class="rounded-2xl mb-2 max-w-[250px] border border-outline-variant/20 cursor-pointer hover:opacity-95" onclick="window.open(this.src)" alt="ضمیمه">`;
        }

        const senderLabel = isSelf ? 'شما (مرکز درمانی)' : (currentTabType === 'clients' ? 'مراجعه‌کننده' : 'مدیریت کل آسنا');

        if (isSelf) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-4 py-3 rounded-2xl rounded-tl-sm shadow-sm text-xs leading-relaxed">
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-1.5 text-left w-full block">${time} • ${senderLabel}</div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%]">
                    <div class="bg-white px-4 py-3 rounded-2xl rounded-br-sm shadow-sm text-xs border border-outline-variant/20 leading-relaxed text-slate-800">
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-slate-400 mt-1.5 text-right w-full block">${time} • ${senderLabel}</div>
                    </div>
                </div>
            `);
        }
    });
}

function sendOrgMessage(e) {
    e.preventDefault();
    if (!currentOrgTicketId) return;

    const input = document.getElementById('org-chat-input');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';

    const fd = new FormData();
    // If talking to client, use org_send. If talking to admin, use normal send as ticket creator.
    fd.append('action', (currentTabType === 'clients') ? 'org_send' : 'send');
    fd.append('ticket_id', currentOrgTicketId);
    fd.append('message', msg);

    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                fetchOrgMessages();
            } else {
                alert('خطا در ارسال پیام: ' + (data.message || 'نامشخص'));
            }
        });
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
