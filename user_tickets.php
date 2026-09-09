<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Handle New Admin Support Ticket Submission
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_admin_ticket') {
    csrf_verify();
    $subject = trim($_POST['subject'] ?? 'درخواست پشتیبانی');
    $initialMessage = trim($_POST['message'] ?? '');
    
    if (!empty($initialMessage)) {
        // Create new admin ticket
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
        $stmt->execute([$user_id]);
        $ticketId = (int)$pdo->lastInsertId();
        
        // Insert user initial message
        $msgStmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'user', ?, NOW())");
        $msgStmt->execute([$ticketId, $initialMessage]);
        
        // Automated welcome from admin support
        $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', 'سلام و درود. پیام شما دریافت شد. کارشناسان و تیم پشتیبانی مدیریت آسنا در اسرع وقت پاسخگوی شما خواهند بود.', NOW())")->execute([$ticketId]);
        
        header("Location: chat.php?ticket_id=" . $ticketId);
        exit;
    } else {
        $msg = 'لطفاً متن پیام یا سوال خود را وارد فرمایید.';
        $msgType = 'error';
    }
}

// Auto-close tickets inactive for 48 hours
$pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");

// Active Tab Filter: 'admin', 'ai', 'all'
$activeTab = trim($_GET['tab'] ?? 'admin');
if (!in_array($activeTab, ['admin', 'ai', 'all'])) {
    $activeTab = 'admin';
}

$tabCondition = "";
if ($activeTab === 'admin') {
    $tabCondition = "AND t.mode = 'admin'";
} elseif ($activeTab === 'ai') {
    $tabCondition = "AND t.mode = 'ai'";
}

// Fetch tickets for this user with last message preview
$stmt = $pdo->prepare("
    SELECT t.*, 
           COALESCE(
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1),
               'پیامی ثبت نشده است'
           ) as last_message,
           (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_message_time
    FROM tickets t 
    WHERE t.user_id = ? {$tabCondition}
    ORDER BY (t.status = 'open') DESC, t.updated_at DESC, t.created_at DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for tabs
$counts = [
    'all'   => (int)$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?")->execute([$user_id]) ? $pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0,
    'admin' => 0,
    'ai'    => 0,
];

$stmtCounts = $pdo->prepare("SELECT mode, COUNT(*) as c FROM tickets WHERE user_id = ? GROUP BY mode");
$stmtCounts->execute([$user_id]);
while ($r = $stmtCounts->fetch(PDO::FETCH_ASSOC)) {
    if ($r['mode'] === 'admin') $counts['admin'] = (int)$r['c'];
    if ($r['mode'] === 'ai') $counts['ai'] = (int)$r['c'];
}
$counts['all'] = $counts['admin'] + $counts['ai'];

require_once 'includes/header.php';
$fmtDateTime = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'd MMMM YYYY - HH:mm');
?>

<main class="w-full max-w-5xl mx-auto py-12 px-4 space-y-8 rtl text-right" dir="rtl">
    
    <!-- Top Hero / Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-r from-primary-container via-primary to-indigo-950 p-8 rounded-3xl text-white shadow-xl">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-secondary-container">support_agent</span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">مرکز پشتیبانی و تیکتینگ کاربران</h1>
            </div>
            <p class="text-xs sm:text-sm text-white/80 leading-relaxed max-w-xl">
                ارتباط مستقیم با تیم پشتیبانی مدیریت آسنا، پیگیری سفارشات، استعلام‌های مالی و گفتگو با دستیار هوشمند لئو.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
            <button onclick="openNewTicketModal()" class="px-5 py-3 rounded-2xl bg-secondary-container hover:opacity-95 active:scale-95 text-white text-xs font-black flex items-center justify-center gap-2 shadow-lg shadow-secondary-container/30 transition-all">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                <span>ارسال تیکت جدید به مدیریت</span>
            </button>

            <form action="actions/chat_action.php" method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="init">
                <input type="hidden" name="mode" value="ai">
                <button type="submit" class="w-full sm:w-auto px-4 py-3 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold flex items-center justify-center gap-2 transition-colors">
                    <span class="material-symbols-outlined text-lg text-emerald-400">cruelty_free</span>
                    <span>مشاوره با لئو (هوش مصنوعی)</span>
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 text-xs font-bold <?= $msgType === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' ?>">
            <span class="material-symbols-outlined text-base"><?= $msgType === 'error' ? 'error' : 'check_circle' ?></span>
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Navigation Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
        <!-- 1. Normal Admin Ticketing Tab -->
        <a href="user_tickets.php?tab=admin" class="px-5 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 shrink-0 <?= $activeTab === 'admin' ? 'bg-secondary-container text-white shadow-md shadow-secondary-container/20' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-base">support_agent</span>
            <span>تیکت‌های پشتیبانی مدیریت (Normal Admin Ticketing)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'admin' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800' ?>"><?= $counts['admin'] ?></span>
        </a>

        <!-- 2. AI Assistant Tab -->
        <a href="user_tickets.php?tab=ai" class="px-5 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $activeTab === 'ai' ? 'bg-primary-container text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-base">cruelty_free</span>
            <span>مشاوره هوشمند با لئو (هوش مصنوعی)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'ai' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800' ?>"><?= $counts['ai'] ?></span>
        </a>

        <!-- 3. All Tickets Tab -->
        <a href="user_tickets.php?tab=all" class="px-5 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $activeTab === 'all' ? 'bg-slate-800 text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-base">all_inbox</span>
            <span>همه گفتگوها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800' ?>"><?= $counts['all'] ?></span>
        </a>
    </div>

    <!-- Tickets Container -->
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200/80 space-y-4">
        
        <?php if (empty($tickets)): ?>
            <!-- Empty State -->
            <div class="text-center py-16 space-y-4 max-w-md mx-auto">
                <div class="w-16 h-16 rounded-3xl bg-secondary-container/10 text-secondary-container flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl"><?= $activeTab === 'ai' ? 'cruelty_free' : 'support_agent' ?></span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 mb-1">
                        <?= $activeTab === 'admin' ? 'هیچ تیکتی با تیم پشتیبانی مدیریت ندارید' : ($activeTab === 'ai' ? 'هیچ گفتگویی با هوش مصنوعی ندارید' : 'هیچ تیکت یا گفتگویی یافت نشد') ?>
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        <?= $activeTab === 'admin' ? 'در صورت داشتن هرگونه سوال در مورد سفارش، پرداخت، نوبت کلینیک یا گزارش مشکل، تیکت جدید ارسال کنید تا کارشناسان ما پاسخ دهند.' : 'برای مشاوره سریع پزشکی، تغذیه و سلامت پت می‌توانید همین حالا با لئو گفتگو کنید.' ?>
                    </p>
                </div>

                <div class="pt-2">
                    <?php if ($activeTab === 'admin' || $activeTab === 'all'): ?>
                        <button onclick="openNewTicketModal()" class="px-6 py-2.5 rounded-xl bg-secondary-container text-white text-xs font-black shadow-md hover:opacity-95 transition-all">
                            ارسال اولین تیکت به مدیریت
                        </button>
                    <?php else: ?>
                        <form action="actions/chat_action.php" method="POST" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="init">
                            <input type="hidden" name="mode" value="ai">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary-container text-white text-xs font-black shadow-md hover:bg-primary transition-all">
                                شروع گفتگو با لئو
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Tickets List Stream -->
            <div class="space-y-3.5">
                <?php foreach ($tickets as $t): 
                    $isAdmin = ($t['mode'] === 'admin');
                    $isOpen = ($t['status'] === 'open');
                ?>
                    <div class="p-5 rounded-2xl bg-slate-50 hover:bg-slate-100/90 border border-slate-200/80 transition-all flex flex-col md:flex-row items-start md:items-center justify-between gap-4 group">
                        
                        <!-- Left: Info & Snippet -->
                        <div class="flex items-start gap-4 min-w-0 flex-1">
                            <div class="w-12 h-12 rounded-2xl <?= $isAdmin ? 'bg-secondary-container/10 text-secondary-container border border-secondary-container/20' : 'bg-primary-container/10 text-primary-container border border-primary-container/20' ?> flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                                <span class="material-symbols-outlined text-2xl"><?= $isAdmin ? 'support_agent' : 'cruelty_free' ?></span>
                            </div>

                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-black text-slate-900">
                                        <?= $isAdmin ? 'تیکت پشتیبانی مدیریت (ارتباط با اپراتور)' : 'مشاوره هوشمند با لئو (هوش مصنوعی)' ?>
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black <?= $isAdmin ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $isAdmin ? 'پشتیبانی انسانی' : 'دستیار AI' ?>
                                    </span>
                                    <span class="text-slate-400 font-mono text-[10px]">#TKT-<?= $t['id'] ?></span>
                                </div>

                                <!-- Last Message Preview -->
                                <p class="text-xs text-slate-600 line-clamp-1 leading-relaxed">
                                    <?php if ($t['last_sender'] === 'admin'): ?>
                                        <strong class="text-primary font-bold">پاسخ کارشناس: </strong>
                                    <?php elseif ($t['last_sender'] === 'user'): ?>
                                        <span class="text-slate-500">پیام شما: </span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($t['last_message']) ?>
                                </p>

                                <div class="flex items-center gap-4 text-[10px] text-slate-400 pt-1">
                                    <span>تاریخ شروع: <?= date('Y/m/d - H:i', strtotime($t['created_at'])) ?></span>
                                    <?php if ($isOpen): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-600 font-black">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                            باز و در جریان گفتگو
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">بسته شده</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Action Button -->
                        <div class="shrink-0 self-end md:self-center">
                            <a href="chat.php?ticket_id=<?= (int)$t['id'] ?>" class="px-5 py-2.5 rounded-xl <?= $isOpen ? ($isAdmin ? 'bg-secondary-container text-white shadow-md hover:opacity-95' : 'bg-primary-container text-white shadow-md hover:bg-primary') : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-100' ?> text-xs font-black flex items-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-sm"><?= $isOpen ? 'chat' : 'history' ?></span>
                                <span><?= $isOpen ? ($isAdmin ? 'گفتگو با کارشناس' : 'ادامه گفتگو') : 'مشاهده سوابق گفتگو' ?></span>
                                <span class="material-symbols-outlined text-xs">arrow_back</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</main>

<!-- ==========================================================================
     NEW ADMIN SUPPORT TICKET MODAL
     ========================================================================== -->
<div id="new-ticket-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 text-right animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-orange-50 via-amber-50 to-indigo-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-secondary-container text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-xl">support_agent</span>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">ارسال تیکت جدید به پشتیبانی مدیریت</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">پاسخگویی مستقیم توسط کارشناسان انسانی آسنا</p>
                </div>
            </div>
            <button onclick="closeNewTicketModal()" class="w-8 h-8 rounded-full bg-white text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors shadow-sm">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Form -->
        <form method="POST" action="user_tickets.php" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="new_admin_ticket">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">موضوع درخواست</label>
                <select name="subject" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:border-secondary-container focus:ring-1 focus:ring-secondary-container outline-none font-bold text-slate-800">
                    <option value="پیگیری سفارش و مرسوله پستی">📦 پیگیری سفارش، تاخیر یا رهگیری پستی</option>
                    <option value="مسائل مالی، کیف پول و بازگشت وجه">💳 امور مالی، شارژ کیف پول یا عودت وجه</option>
                    <option value="نوبت‌دهی کلینیک و هماهنگی پزشک">🩺 هماهنگی نوبت درمانی، لغو یا تغییر زمان</option>
                    <option value="گزارش مشکل فنی در سایت">⚙️ گزارش باگ، خطا یا مشکل در وبسایت</option>
                    <option value="پیشنهادات و انتقادات">💬 نظرات، شکایات، پیشنهادات و انتقادات</option>
                    <option value="سایر موارد">📝 سایر موارد پشتیبانی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">متن پیام و توضیحات شما</label>
                <textarea name="message" rows="4" required placeholder="لطفاً جزییات درخواست، شماره سفارش یا سوال خود را اینجا با دقت بنویسید..." class="w-full p-3.5 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:border-secondary-container focus:ring-1 focus:ring-secondary-container outline-none leading-relaxed text-slate-800"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeNewTicketModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-secondary-container hover:opacity-95 text-white text-xs font-black shadow-md transition-all flex items-center gap-1.5">
                    <span>ثبت و ارسال تیکت</span>
                    <span class="material-symbols-outlined text-sm">send</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function openNewTicketModal() {
    const modal = document.getElementById('new-ticket-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeNewTicketModal() {
    const modal = document.getElementById('new-ticket-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once 'includes/footer.php'; ?>
