<?php
session_start();
$currentPage = 'tickets';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Handle ticket status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_ticket_status') {
    csrf_verify();
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $newStatus = ($_POST['status'] === 'closed') ? 'closed' : 'open';
    if ($ticketId > 0) {
        $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?")->execute([$newStatus, $ticketId]);
        header("Location: tickets.php?role=" . urlencode($_GET['role'] ?? ''));
        exit;
    }
}

// Auto-close tickets inactive for 48 hours
$pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");

// Role tab filter
$selectedRole = trim($_GET['role'] ?? '');

$roleWhere = "";
$roleParams = [];
if ($selectedRole === 'organization') {
    $roleWhere = "AND u.role = 'organization'";
} elseif ($selectedRole === 'seller') {
    $roleWhere = "AND u.role = 'seller'";
} elseif ($selectedRole === 'specialist') {
    $roleWhere = "AND u.role IN ('doctor', 'pharmacist')";
} elseif ($selectedRole === 'user') {
    $roleWhere = "AND (u.role = 'user' OR u.role IS NULL OR u.role = '' OR u.role NOT IN ('organization', 'seller', 'doctor', 'pharmacist'))";
}

// Fetch tickets with role segmentation & last message preview
$stmt = $pdo->prepare("
    SELECT t.id, t.status, t.created_at, t.updated_at, u.name, u.phone, u.role,
           COALESCE(
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1),
               'پیامی ثبت نشده است'
           ) as last_message,
           (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.mode = 'admin' {$roleWhere}
    ORDER BY (t.status = 'open') DESC, t.updated_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts per role for tabs
$counts = [
    'all'          => (int)$pdo->query("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.mode = 'admin'")->fetchColumn(),
    'organization' => (int)$pdo->query("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.mode = 'admin' AND u.role = 'organization'")->fetchColumn(),
    'seller'       => (int)$pdo->query("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.mode = 'admin' AND u.role = 'seller'")->fetchColumn(),
    'specialist'   => (int)$pdo->query("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.mode = 'admin' AND u.role IN ('doctor', 'pharmacist')")->fetchColumn(),
    'user'         => (int)$pdo->query("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.mode = 'admin' AND (u.role = 'user' OR u.role IS NULL OR u.role = '' OR u.role NOT IN ('organization', 'seller', 'doctor', 'pharmacist'))")->fetchColumn(),
];

// Open count
$openCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE mode = 'admin' AND status = 'open'")->fetchColumn();
?>

<div class="p-6 md:p-8 space-y-6 max-w-[1600px] mx-auto rtl text-right" dir="rtl">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-secondary-container">support_agent</span>
                <h1 class="text-2xl font-black text-on-surface">مرکز تیکتینگ و پاسخگویی چندنقشی</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-500 text-white shadow-sm">
                    <?= number_format($openCount) ?> تیکت باز
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-1">
                رسیدگی تفکیک‌شده به درخواست‌های مراکز درمانی، فروشندگان کالا، پزشکان و خریداران عادی سامانه آسنا.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">dashboard</span>
                <span>پیشخوان کلان</span>
            </a>
            <a href="reviews.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200 transition-colors flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">rate_review</span>
                <span>پایش نظرات مراجعین</span>
            </a>
        </div>
    </div>

    <!-- Role Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-outline-variant/30">
        <a href="tickets.php" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= empty($selectedRole) ? 'bg-primary text-white shadow-md' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' ?>">
            <span class="material-symbols-outlined text-base">all_inbox</span>
            <span>همه تیکت‌ها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= empty($selectedRole) ? 'bg-white/20 text-white' : 'bg-surface-container text-on-surface' ?>"><?= $counts['all'] ?></span>
        </a>

        <a href="tickets.php?role=organization" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $selectedRole === 'organization' ? 'bg-indigo-600 text-white shadow-md' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' ?>">
            <span class="material-symbols-outlined text-base">apartment</span>
            <span>مراکز درمانی و بیمارستان‌ها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $selectedRole === 'organization' ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-700' ?>"><?= $counts['organization'] ?></span>
        </a>

        <a href="tickets.php?role=seller" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $selectedRole === 'seller' ? 'bg-amber-600 text-white shadow-md' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' ?>">
            <span class="material-symbols-outlined text-base">storefront</span>
            <span>فروشندگان و پت‌شاپ‌ها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $selectedRole === 'seller' ? 'bg-white/20 text-white' : 'bg-amber-50 text-amber-700' ?>"><?= $counts['seller'] ?></span>
        </a>

        <a href="tickets.php?role=specialist" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $selectedRole === 'specialist' ? 'bg-emerald-600 text-white shadow-md' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' ?>">
            <span class="material-symbols-outlined text-base">stethoscope</span>
            <span>پزشکان و داروسازان</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $selectedRole === 'specialist' ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700' ?>"><?= $counts['specialist'] ?></span>
        </a>

        <a href="tickets.php?role=user" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 <?= $selectedRole === 'user' ? 'bg-sky-600 text-white shadow-md' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' ?>">
            <span class="material-symbols-outlined text-base">person</span>
            <span>خریداران و کاربران عادی</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $selectedRole === 'user' ? 'bg-white/20 text-white' : 'bg-sky-50 text-sky-700' ?>"><?= $counts['user'] ?></span>
        </a>
    </div>

    <!-- Main Chat Split Layout -->
    <div class="bg-surface-container-lowest rounded-3xl shadow-sm border border-outline-variant/20 flex flex-col md:flex-row h-[750px] overflow-hidden">
        
        <!-- Tickets Sidebar List -->
        <div class="w-full md:w-5/12 lg:w-4/12 border-l border-outline-variant/20 flex flex-col bg-surface-container-lowest">
            <!-- Search Filter -->
            <div class="p-3.5 border-b border-outline-variant/20 bg-surface-container-lowest">
                <div class="relative">
                    <span class="absolute inset-y-0 right-3 flex items-center text-outline">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </span>
                    <input id="ticket-search-input" onkeyup="filterTicketsClientSide()" class="w-full pr-10 pl-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-xs focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="جستجوی نام، تلفن یا متن..." type="text"/>
                </div>
            </div>

            <!-- Tickets Stream -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5" id="ticket-list-container">
                <?php if(empty($tickets)): ?>
                    <div class="p-8 text-center text-on-surface-variant/60 text-xs">
                        تیکتی در این دسته‌بندی یافت نشد.
                    </div>
                <?php else: ?>
                    <?php foreach($tickets as $t): 
                        $role = $t['role'] ?? 'user';
                        $roleBadge = match($role) {
                            'organization' => ['label' => 'مرکز درمانی', 'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'apartment'],
                            'seller'       => ['label' => 'فروشنده', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'storefront'],
                            'doctor'       => ['label' => 'پزشک', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'stethoscope'],
                            'pharmacist'   => ['label' => 'داروساز', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'prescriptions'],
                            default        => ['label' => 'کاربر عادی', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'person']
                        };
                        $isOpen = ($t['status'] === 'open');
                    ?>
                    <button onclick="loadTicket(<?= (int)$t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'] ?: $t['phone'])) ?>', '<?= htmlspecialchars($roleBadge['label']) ?>', '<?= $isOpen ? 'open' : 'closed' ?>')" 
                            class="ticket-card w-full text-right p-3.5 rounded-2xl hover:bg-surface-container transition-all flex flex-col gap-2 border border-transparent hover:border-outline-variant/20 focus:bg-primary/5 focus:border-primary/30 group" 
                            data-search="<?= htmlspecialchars(strtolower($t['name'] . ' ' . $t['phone'] . ' ' . $t['last_message'])) ?>">
                        
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-8 h-8 rounded-xl <?= $roleBadge['bg'] ?> border flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-sm"><?= $roleBadge['icon'] ?></span>
                                </div>
                                <div class="truncate">
                                    <h4 class="font-black text-xs text-on-surface truncate"><?= htmlspecialchars($t['name'] ?: 'کاربر ' . $t['phone']) ?></h4>
                                    <span class="text-[10px] text-on-surface-variant font-mono" dir="ltr"><?= htmlspecialchars($t['phone']) ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border <?= $roleBadge['bg'] ?>">
                                    <?= $roleBadge['label'] ?>
                                </span>
                                <?php if ($isOpen): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        پاسخ‌داده‌نشده
                                    </span>
                                <?php else: ?>
                                    <span class="text-[9px] text-slate-400">بسته شده</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Snippet of Last Message -->
                        <p class="text-[11px] text-on-surface-variant/80 line-clamp-1 leading-snug">
                            <?php if ($t['last_sender'] === 'admin'): ?>
                                <span class="font-bold text-primary">شما: </span>
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
        <div class="flex-1 flex flex-col bg-surface-container-low" id="chat-area">
            <!-- Empty State -->
            <div class="flex-1 flex flex-col items-center justify-center text-on-surface-variant/50 p-8" id="empty-state">
                <div class="w-16 h-16 rounded-3xl bg-white shadow-sm flex items-center justify-center mb-3 text-outline">
                    <span class="material-symbols-outlined text-3xl">chat</span>
                </div>
                <h3 class="font-black text-sm text-on-surface mb-1">یک تیکت را برای پاسخگویی انتخاب کنید</h3>
                <p class="text-xs max-w-xs text-center">روی هر یک از تیکت‌های ستون راست کلیک کنید تا سوابق گفتگو و پاسخگویی به سازمان، فروشنده یا خریدار نمایش داده شود.</p>
            </div>

            <!-- Active Chat Interface -->
            <div class="hidden flex-1 flex flex-col h-full" id="active-chat">
                <!-- Header -->
                <div class="bg-white border-b border-outline-variant/20 px-6 py-3.5 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary-container text-white flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-black text-sm text-primary" id="active-user-name">کاربر</h3>
                                <span id="active-user-role" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">نقش</span>
                            </div>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">شناسه تیکت: #<span id="active-ticket-id"></span></p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <form method="POST" action="tickets.php" id="toggle-status-form" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_ticket_status">
                            <input type="hidden" name="ticket_id" id="toggle-ticket-id" value="">
                            <input type="hidden" name="status" id="toggle-status-val" value="closed">
                            <button type="submit" id="toggle-status-btn" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-outline-variant/30 hover:bg-slate-100 transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                <span>بستن تیکت</span>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Messages Stream -->
                <div class="flex-1 p-6 space-y-4 overflow-y-auto custom-scrollbar" id="admin-chat-messages">
                    <!-- Messages injected here dynamically -->
                </div>

                <!-- Admin Response Form -->
                <div class="p-4 bg-white border-t border-outline-variant/20">
                    <form id="admin-chat-form" class="flex items-center gap-3 relative" onsubmit="sendAdminMessage(event)">
                        <div class="flex-1 relative">
                            <input id="admin-chat-input" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-2xl pr-4 pl-4 py-3.5 focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-xs font-medium" placeholder="پاسخ رسمی پشتیبانی آسنا به این نقش را تایپ کنید..." type="text" autocomplete="off" />
                        </div>
                        <button type="submit" class="w-12 h-12 bg-primary text-white rounded-2xl hover:scale-105 hover:bg-primary-container transition-all flex items-center justify-center shadow-lg shadow-primary/20 shrink-0">
                            <span class="material-symbols-outlined text-xl -ml-0.5">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentAdminTicketId = null;
let adminLastMessageId = 0;
let adminPollingInterval = null;

function filterTicketsClientSide() {
    const q = document.getElementById('ticket-search-input').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.ticket-card');
    cards.forEach(card => {
        const searchData = card.getAttribute('data-search') || '';
        if (searchData.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function loadTicket(ticketId, userName, userRole, status) {
    currentAdminTicketId = ticketId;
    adminLastMessageId = 0;
    
    document.getElementById('empty-state').classList.add('hidden');
    document.getElementById('active-chat').classList.remove('hidden');
    document.getElementById('active-chat').classList.add('flex');
    
    document.getElementById('active-ticket-id').innerText = ticketId;
    document.getElementById('active-user-name').innerText = userName;
    document.getElementById('active-user-role').innerText = userRole;
    
    document.getElementById('toggle-ticket-id').value = ticketId;
    const toggleBtn = document.getElementById('toggle-status-btn');
    const toggleVal = document.getElementById('toggle-status-val');
    if (status === 'closed') {
        toggleVal.value = 'open';
        toggleBtn.innerHTML = '<span class="material-symbols-outlined text-sm">lock_open</span><span>بازگشایی تیکت</span>';
        toggleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors flex items-center gap-1';
    } else {
        toggleVal.value = 'closed';
        toggleBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span><span>بستن تیکت</span>';
        toggleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition-colors flex items-center gap-1';
    }
    
    const msgContainer = document.getElementById('admin-chat-messages');
    msgContainer.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">در حال بارگذاری گفتگو...</div>';
    
    if (adminPollingInterval) clearInterval(adminPollingInterval);
    fetchAdminMessages();
    adminPollingInterval = setInterval(fetchAdminMessages, 3000);
}

function fetchAdminMessages() {
    if (!currentAdminTicketId) return;
    
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentAdminTicketId);
    fd.append('last_id', adminLastMessageId);
    
    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                if (adminLastMessageId === 0) {
                    document.getElementById('admin-chat-messages').innerHTML = '';
                }
                renderAdminMessages(data.messages);
                adminLastMessageId = data.messages[data.messages.length - 1].id;
                const container = document.getElementById('admin-chat-messages');
                container.scrollTop = container.scrollHeight;
            } else if (adminLastMessageId === 0 && (!data.messages || data.messages.length === 0)) {
                document.getElementById('admin-chat-messages').innerHTML = '<div class="text-center py-8 text-xs text-slate-400">هیچ پیامی در این تیکت وجود ندارد.</div>';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderAdminMessages(messages) {
    const container = document.getElementById('admin-chat-messages');
    
    messages.forEach(msg => {
        const isAdmin = (msg.sender_type === 'admin');
        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
        const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');
        
        let imgHtml = '';
        if (msg.image_url) {
            const safeImg = escapeHtml(msg.image_url);
            imgHtml = `<img src="../${safeImg}" class="rounded-2xl mb-2 max-w-[250px] border border-outline-variant/20 cursor-pointer hover:opacity-95" onclick="window.open(this.src)" alt="ضمیمه">`;
        }

        if (isAdmin) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-4 py-3 rounded-2xl rounded-tl-sm shadow-sm text-xs leading-relaxed">
                        <div>${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-1.5 text-left w-full block">${time} • پاسخ مدیر</div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%]">
                    <div class="bg-white px-4 py-3 rounded-2xl rounded-br-sm shadow-sm text-xs border border-outline-variant/20 leading-relaxed text-slate-800">
                        ${imgHtml}
                        <div>${safeMessage}</div>
                        <div class="text-[9px] text-slate-400 mt-1.5 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function sendAdminMessage(e) {
    e.preventDefault();
    if (!currentAdminTicketId) return;
    
    const input = document.getElementById('admin-chat-input');
    const msg = input.value.trim();
    if (!msg) return;
    
    input.value = '';
    
    const fd = new FormData();
    fd.append('action', 'admin_send');
    fd.append('ticket_id', currentAdminTicketId);
    fd.append('message', msg);
    
    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                fetchAdminMessages();
            } else {
                alert('خطا در ارسال پاسخ: ' + (data.message || 'نامشخص'));
            }
        });
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
