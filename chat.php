<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['ticket_id'] ?? null;

if (!$ticket_id) {
    // Redirect to tickets history if no ticket provided
    header('Location: user_tickets.php');
    exit;
}

// Auto-close tickets inactive for 24 hours
try {
    $pdo->exec("UPDATE tickets SET status = 'closed' WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
} catch (Throwable $e) {}

// Check if organization_id exists in tickets
$has_org_in_tickets = false;
try {
    $col_check = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'organization_id'");
    $has_org_in_tickets = (bool)$col_check->fetch();
} catch (Throwable $e) {}

$joinOrg = $has_org_in_tickets ? "LEFT JOIN organizations o ON t.organization_id = o.id" : "";
$selectOrg = $has_org_in_tickets ? "o.name AS organization_name, o.logo_url AS organization_logo" : "NULL AS organization_name, NULL AS organization_logo";

$joinDoc = "LEFT JOIN doctors d ON t.doctor_id = d.id";
$selectDoc = ", d.name AS doctor_name, d.specialty AS doctor_specialty, d.image_url AS doctor_image";

// Verify ticket ownership & fetch details
$ticket = null;
try {
    $stmt = $pdo->prepare("
        SELECT t.*, {$selectOrg} {$selectDoc}
        FROM tickets t 
        {$joinOrg}
        {$joinDoc}
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, NULL as organization_name, NULL as organization_logo, NULL as doctor_name, NULL as doctor_specialty, NULL as doctor_image FROM tickets t WHERE t.id = ? AND t.user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {}
}

if (!$ticket) {
    header('Location: user_tickets.php');
    exit;
}

$mode = $ticket['mode'] ?? 'admin';
$orgName = $ticket['organization_name'] ?? 'مرکز درمانی';

// Fetch all tickets for sidebar with organization name and doctor name
$all_tickets = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, {$selectOrg} {$selectDoc}
        FROM tickets t 
        {$joinOrg}
        {$joinDoc}
        WHERE t.user_id = ? 
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $all_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, NULL as organization_name, NULL as doctor_name FROM tickets t WHERE t.user_id = ? ORDER BY t.id DESC");
        $stmt->execute([$user_id]);
        $all_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $all_tickets = [];
    }
}

require_once 'includes/header.php';
?>

<main class="w-full max-w-[1200px] mx-auto py-8 px-4 h-[calc(100vh-100px)]">
    <div class="grid grid-cols-1 md:grid-cols-4 h-full gap-6">
        
        <!-- Mobile Sidebar Backdrop -->
        <div id="chat-sidebar-backdrop" onclick="toggleChatSidebar()" class="fixed inset-0 bg-black/50 z-[50] hidden md:hidden"></div>

        <!-- Sidebar: Chat History -->
        <div id="chat-sidebar" class="fixed md:relative top-0 right-0 h-full w-4/5 md:w-auto z-[60] md:z-0 translate-x-full md:translate-x-0 transition-transform duration-300 workstation-module rounded-none md:rounded-[2.5rem] overflow-hidden flex flex-col bg-surface-container-lowest col-span-1 shadow-2xl border-none">
            <div class="md:hidden p-4 bg-primary text-white flex justify-between items-center">
                <h3 class="font-bold">منوی گفتگو</h3>
                <button onclick="toggleChatSidebar()" class="material-symbols-outlined hover:bg-white/10 rounded-full p-2">close</button>
            </div>
            <div class="p-6 border-b border-outline-variant/20 bg-surface-container-lowest flex items-center justify-between">
                <h3 class="font-bold text-primary">گفتگوهای من</h3>
                <a href="booking.php" class="w-8 h-8 rounded-full bg-primary-container/10 text-primary-container flex items-center justify-center hover:bg-primary-container hover:text-white transition-colors" title="رزرو و مشاوره جدید">
                    <span class="material-symbols-outlined text-sm">add</span>
                </a>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php foreach($all_tickets as $t): 
                    $tMode = $t['mode'];
                    $icon = 'support_agent';
                    $badgeClass = 'bg-secondary-container text-white';
                    $tTitle = 'پشتیبانی مدیریت آسنا';
                    if ($tMode === 'ai') {
                        $icon = 'cruelty_free';
                        $badgeClass = 'bg-primary-container text-white';
                        $tTitle = 'لئو (AI)';
                    } elseif ($tMode === 'organization') {
                        $icon = 'apartment';
                        $badgeClass = 'bg-sky-600 text-white';
                        $tTitle = !empty($t['organization_name']) ? $t['organization_name'] : 'مرکز درمانی';
                    } elseif ($tMode === 'doctor') {
                        $icon = 'medical_services';
                        $badgeClass = 'bg-emerald-600 text-white';
                        $tTitle = !empty($t['doctor_name']) ? ('دکتر ' . $t['doctor_name']) : 'مشاوره تله‌هلث';
                    }
                ?>
                <a href="chat.php?ticket_id=<?php echo $t['id']; ?>" class="block w-full text-right p-3 rounded-xl hover:bg-surface-container transition-colors flex items-center gap-3 <?php echo $t['id'] == $ticket_id ? 'bg-primary-container/10 border border-primary-container/20 shadow-sm' : ''; ?>">
                    <div class="w-10 h-10 rounded-full <?php echo $badgeClass; ?> flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl"><?php echo $icon; ?></span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-between items-center mb-1">
                            <h4 class="font-bold text-primary text-sm truncate"><?php echo htmlspecialchars($tTitle); ?></h4>
                        </div>
                        <p class="text-[10px] text-on-surface-variant truncate flex justify-between">
                            <span>تیکت #<?php echo $t['id']; ?></span>
                            <span class="<?php echo $t['status'] == 'open' ? 'text-emerald-500 font-bold' : ($t['status'] == 'resolved' ? 'text-blue-500 font-bold' : 'text-outline'); ?>">
                                <?php echo $t['status'] == 'open' ? 'فعال' : ($t['status'] == 'resolved' ? 'خاتمه یافته' : 'بسته'); ?>
                            </span>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area (Main) -->
        <div class="workstation-module rounded-[2.5rem] overflow-hidden flex flex-col h-full col-span-1 md:col-span-3 shadow-2xl border-none bg-surface-container-lowest relative">
        <!-- Chat Header -->
        <div class="bg-white border-b border-outline-variant/20 px-6 py-4 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center gap-4">
                <!-- Mobile Sidebar Toggle -->
                <button type="button" onclick="toggleChatSidebar()" class="md:hidden w-10 h-10 rounded-full bg-primary-container/10 text-primary-container hover:bg-primary-container hover:text-white flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined">menu_open</span>
                </button>
                <a href="user_tickets.php" class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
                <?php
                $headerTitle = 'پشتیبانی مدیریت آسنا';
                $headerIcon = 'support_agent';
                $headerSub = 'آماده پاسخگویی';
                if ($mode === 'ai') {
                    $headerTitle = 'لئو (دستیار هوشمند آسنا)';
                    $headerIcon = 'cruelty_free';
                    $headerSub = 'هوش مصنوعی دامپزشکی';
                } elseif ($mode === 'organization') {
                    $headerTitle = !empty($ticket['organization_name']) ? $ticket['organization_name'] : 'گفتگو با مرکز درمانی';
                    $headerIcon = 'apartment';
                    $headerSub = 'مرکز درمانی آسنا';
                } elseif ($mode === 'doctor') {
                    $headerTitle = !empty($ticket['doctor_name']) ? ('دکتر ' . $ticket['doctor_name']) : 'مشاوره آنلاین تله‌هلث';
                    $headerIcon = 'medical_services';
                    $headerSub = !empty($ticket['doctor_specialty']) ? $ticket['doctor_specialty'] : 'دامپزشک معالج';
                }
                ?>
                <div class="relative">
                    <?php if($mode === 'doctor' && !empty($ticket['doctor_image'])): ?>
                    <img src="<?= htmlspecialchars($ticket['doctor_image']) ?>" alt="پزشک" class="w-14 h-14 rounded-full object-cover border-2 border-emerald-500 shadow-sm" onerror="this.onerror=null; this.src='assets/images/doc-placeholder.webp';">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container border-2 border-primary-container">
                        <span class="material-symbols-outlined text-3xl"><?php echo $headerIcon; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($ticket['status'] === 'open'): ?>
                    <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white" title="جلسه فعال"></div>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-primary"><?php echo htmlspecialchars($headerTitle); ?></h3>
                    <p class="text-xs text-on-surface-variant flex items-center gap-1">
                        <?php if($ticket['status'] === 'open'): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <?= htmlspecialchars($headerSub) ?> • آنلاین
                        <?php elseif($ticket['status'] === 'resolved'): ?>
                        <span class="material-symbols-outlined text-xs text-blue-500">task_alt</span>
                        جلسه بالینی پایان یافته است
                        <?php else: ?>
                        بسته شده
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if($mode === 'doctor'): ?>
                <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">
                    <span class="material-symbols-outlined text-xs">verified</span>
                    تله‌هلث بالینی
                </span>
                <?php endif; ?>
                <span class="text-xs font-bold text-outline-variant">تیکت #<?php echo $ticket_id; ?></span>
            </div>
        </div>

        <?php if($mode === 'ai'): ?>
        <!-- Medical & Clinical Legal Disclaimer Banner -->
        <div class="bg-amber-500/10 border-b border-amber-500/20 px-4 py-2.5 flex items-center justify-between text-[11px] text-amber-900 font-medium">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-base shrink-0">health_and_safety</span>
                <span><strong>سلب مسئولیت پزشکی:</strong> لئو یک دستیار هوشمند است و نظرات آن جایگزین معاینه حضوری دامپزشک نیست. در موارد مسمومیت یا سوانح حاد، فوراً به کلینیک مراجعه فرمایید.</span>
            </div>
            <a href="booking.php?emergency=1" class="text-amber-700 hover:text-amber-900 font-bold underline whitespace-nowrap mr-3 shrink-0 flex items-center gap-1">
                رزرو نوبت اورژانس
                <span class="material-symbols-outlined text-xs">arrow_left</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Chat Body -->
        <div class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar bg-surface-container-lowest relative" id="chat-messages">
            <?php if($mode === 'doctor' && !empty($ticket['resolution_notes'])): ?>
            <!-- Clinical Discharge / Resolution Card -->
            <div class="bg-gradient-to-l from-emerald-50 to-teal-50 border border-emerald-200 rounded-3xl p-5 text-xs text-emerald-950 shadow-sm flex items-start gap-3.5 mb-4">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-xl">medical_services</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-sm text-emerald-900 mb-1 flex items-center justify-between">
                        <span>دستورات و توصیه‌های نهایی پزشک</span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full font-medium">پایان ویزیت آنلاین</span>
                    </div>
                    <p class="leading-relaxed text-emerald-900/90 whitespace-pre-line"><?= htmlspecialchars($ticket['resolution_notes']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex justify-center mb-8">
                <div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">تاریخچه مکالمه</div>
            </div>
            <!-- Messages will be injected here via JS -->
        </div>
        
        <!-- Loading Indicator -->
        <div id="chat-typing" class="px-6 py-2 bg-surface-container-lowest hidden items-center gap-2 text-xs text-on-surface-variant font-medium">
            <div class="flex gap-1">
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <span><?php echo $mode === 'ai' ? 'لئو در حال تایپ است...' : ($mode === 'doctor' ? 'پزشک در حال بررسی است...' : 'پشتیبان در حال پاسخگویی است...'); ?></span>
        </div>

        <!-- Image Preview Overlay -->
        <div id="image-preview-container" class="hidden px-6 py-4 bg-surface-container border-t border-outline-variant/20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img id="image-preview" src="" class="w-16 h-16 object-cover rounded-lg shadow-sm border border-outline-variant/30">
                <div class="text-xs font-bold text-primary">تصویر ضمیمه شد</div>
            </div>
            <button type="button" onclick="clearImage()" class="w-8 h-8 bg-error/10 text-error rounded-full flex items-center justify-center hover:bg-error hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>

        <!-- Chat Footer (Input Bar) -->
        <?php if($ticket['status'] === 'open'): ?>
        <div class="p-3 md:p-4 bg-white border-t border-outline-variant/20 z-10 space-y-2">
            <!-- Quick Chips for Clinical Care -->
            <?php if($mode === 'doctor'): ?>
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-[11px] custom-scrollbar">
                <button type="button" onclick="setQuickChip('وضعیت بهبود و تغییرات علائم پت:')" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-primary-container/10 hover:text-primary rounded-full font-medium transition-all">
                    🩺 گزارش روند بهبودی
                </button>
                <button type="button" onclick="setQuickChip('سؤال در مورد نحوه و زمان مصرف داروها:')" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-primary-container/10 hover:text-primary rounded-full font-medium transition-all">
                    💊 دوز و مصرف دارو
                </button>
                <button type="button" onclick="document.getElementById('chat-image-input').click()" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-primary-container/10 hover:text-primary rounded-full font-medium transition-all">
                    📷 ارسال عکس نسخه/آزمایش
                </button>
            </div>
            <?php endif; ?>

            <form id="chat-form" data-ajax="true" class="flex items-center gap-2 md:gap-3 relative" onsubmit="sendChatMessage(event)">
                <input type="file" id="chat-image-input" class="hidden" accept="image/*" onchange="handleImageSelect(this)">
                <button type="button" onclick="document.getElementById('chat-image-input').click()" class="w-11 h-11 md:w-12 md:h-12 rounded-full hover:bg-primary-container/10 text-on-surface-variant hover:text-primary-container flex items-center justify-center transition-colors shrink-0" title="افزودن تصویر یا آزمایش">
                    <span class="material-symbols-outlined text-2xl">attach_file</span>
                </button>
                
                <div class="flex-1 relative">
                    <input id="chat-input" dir="auto" class="w-full bg-surface-container-low border-none rounded-full px-5 py-3 md:py-3.5 focus:ring-2 focus:ring-primary-container transition-all text-sm font-medium" placeholder="<?= $mode === 'doctor' ? 'پیام یا گزارش بالینی خود را برای پزشک بنویسید...' : 'پیام خود را بنویسید...' ?>" type="text" autocomplete="off" />
                </div>
                
                <button type="submit" id="chat-send-btn" data-no-spinner="true" class="w-11 h-11 md:w-12 md:h-12 bg-primary text-white rounded-full hover:scale-105 hover:bg-primary-container transition-all flex items-center justify-center shadow-lg shrink-0" title="ارسال پیام">
                    <span class="material-symbols-outlined text-xl -ml-0.5">send</span>
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="p-6 bg-surface-container-low border-t border-outline-variant/20 z-10 text-center flex flex-col items-center gap-3">
            <?php if($mode === 'doctor'): ?>
            <p class="text-xs md:text-sm font-bold text-on-surface-variant">این جلسه مشاوره توسط پزشک پایان یافته است. جهت مشاوره بالینی مجدد، لطفاً نوبت جدید ثبت فرمایید.</p>
            <a href="booking.php<?= !empty($ticket['doctor_id']) ? '?doctor_id=' . (int)$ticket['doctor_id'] : '' ?>" class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold hover:bg-primary-container hover:scale-105 transition-all shadow-md inline-flex items-center gap-2 text-xs">
                <span class="material-symbols-outlined text-sm">calendar_month</span>
                رزرو نوبت جدید ویزیت با پزشک
            </a>
            <?php else: ?>
            <p class="text-sm font-bold text-on-surface-variant">این گفتگو بسته شده است.</p>
            <button type="button" onclick="reopenTicket()" class="bg-primary text-white px-6 py-2 rounded-xl font-bold hover:bg-primary-container hover:scale-105 transition-all shadow-md">
                باز کردن مجدد گفتگو
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
</main>

<script>
const currentTicketId = <?php echo $ticket_id; ?>;
const chatMode = '<?php echo $mode; ?>';
let lastMessageId = 0;
let chatPollingInterval = null;

function setQuickChip(text) {
    const input = document.getElementById('chat-input');
    if (!input) return;
    input.value = text + ' ';
    input.focus();
}

function fetchMessages() {
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentTicketId);
    fd.append('last_id', lastMessageId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
                document.getElementById('chat-typing').style.display = 'none';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    
    messages.forEach(msg => {
        const isUser = msg.sender_type === 'user';
        let avatar = 'support_agent';
        let badgeColor = 'bg-primary-container';
        let badgeLabel = 'پشتیبان';

        if (chatMode === 'ai' || msg.sender_type === 'ai') {
            avatar = 'cruelty_free';
            badgeColor = 'bg-primary-container';
            badgeLabel = 'لئو (هوش مصنوعی)';
        } else if (chatMode === 'organization' || msg.sender_type === 'organization') {
            avatar = 'apartment';
            badgeColor = 'bg-sky-600';
            badgeLabel = 'مرکز درمانی';
        } else if (chatMode === 'doctor' || msg.sender_type === 'doctor') {
            avatar = 'medical_services';
            badgeColor = 'bg-emerald-600';
            badgeLabel = 'پزشک معالج';
        }
        
        let imgHtml = '';
        if (msg.image_url) {
            const safeImgUrl = escapeHtml(msg.image_url);
            imgHtml = `<a href="${safeImgUrl}" target="_blank" class="block"><img src="${safeImgUrl}" class="rounded-xl mb-3 max-w-[220px] max-h-[220px] object-cover cursor-pointer border border-outline-variant/20 hover:scale-105 transition-transform" alt="ضمیمه چت"></a>`;
        }

        const isEmergency = msg.message && (msg.message.includes('🚨') && (msg.message.includes('هشدار قرمز') || msg.message.includes('اورژانس حیاتی')));
        
        let safeMessage = escapeHtml(msg.message);
        safeMessage = safeMessage.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        safeMessage = safeMessage.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="underline font-bold text-inherit hover:opacity-80 inline-flex items-center gap-0.5">$1</a>');
        safeMessage = safeMessage.replace(/\n/g, '<br>');

        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });

        if (isUser) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-5 py-3.5 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-1.5 text-left w-full block">${time} <span class="material-symbols-outlined text-[10px] ml-0.5" style="vertical-align: middle">done_all</span></div>
                    </div>
                </div>
            `);
        } else if (isEmergency) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[92%] sm:max-w-[85%]">
                    <div class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shrink-0 border-2 border-white shadow-md animate-pulse mt-auto" title="تریاژ فوری اورژانس">
                        <span class="material-symbols-outlined text-lg">emergency</span>
                    </div>
                    <div class="bg-red-50/95 border-2 border-red-500/80 rounded-3xl rounded-br-sm p-4 sm:p-5 shadow-lg shadow-red-500/10 text-sm leading-relaxed text-red-950">
                        <div class="text-[11px] font-black text-red-700 mb-1.5 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                            <span>🚨 پروتکل تریاژ اورژانس حیاتی دامپزشکی آسنا</span>
                        </div>
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text leading-relaxed font-medium" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="mt-3.5 pt-3 border-t border-red-200/80 flex flex-wrap items-center gap-2">
                            <a href="tel:02191015000" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-black shadow-sm transition-all active:scale-95">
                                <span class="material-symbols-outlined text-sm">call</span>
                                تماس فوری با اورژانس ۲۴ ساعته
                            </a>
                            <a href="booking.php?emergency=1" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white border border-red-300 hover:bg-red-100 text-red-800 rounded-xl text-xs font-bold shadow-sm transition-all active:scale-95">
                                <span class="material-symbols-outlined text-sm">medical_services</span>
                                رزرو سریع ویزیت اورژانسی
                            </a>
                        </div>
                        <div class="text-[9px] text-red-700/70 mt-2 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%]">
                    <div class="w-10 h-10 rounded-full ${badgeColor} text-white flex items-center justify-center shrink-0 border-2 border-white shadow-sm mt-auto" title="${badgeLabel}">
                        <span class="material-symbols-outlined text-lg">${avatar}</span>
                    </div>
                    <div class="bg-white px-5 py-3.5 rounded-3xl rounded-br-sm shadow-md text-sm border border-outline-variant/10 leading-relaxed text-on-surface">
                        <div class="text-[10px] font-bold text-primary mb-1 flex items-center gap-1">
                            <span>${badgeLabel}</span>
                        </div>
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text markdown-body" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-1.5 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}


function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
}

// --- Mobile Sidebar Toggle ---
function toggleChatSidebar() {
    const sidebar = document.getElementById('chat-sidebar');
    const backdrop = document.getElementById('chat-sidebar-backdrop');
    if (sidebar.classList.contains('translate-x-full')) {
        sidebar.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
    } else {
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('hidden');
    }
}

// Ensure chat messages stay scrolled to bottom
const chatMessages = document.getElementById('chat-messages');
chatMessages.scrollTop = chatMessages.scrollHeight;

function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('image-preview-container').classList.add('flex');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('chat-image-input').value = '';
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('image-preview-container').classList.remove('flex');
    document.getElementById('image-preview').src = '';
}

let pendingAiRequests = 0;

function sendChatMessage(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const input = document.getElementById('chat-input');
    const imageInput = document.getElementById('chat-image-input');
    const sendBtn = document.getElementById('chat-send-btn');
    const msg = input.value.trim();
    
    if (!msg && imageInput.files.length === 0) return;
    
    // Always ensure send button stays enabled and unblocked for continuous speaking
    if (sendBtn) {
        sendBtn.disabled = false;
        sendBtn.querySelectorAll('.animate-spin').forEach(el => el.remove());
    }

    // Unique temp ID for this specific outgoing message bubble
    const tempId = 'temp-msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);

    // Show Optimistic UI for User Message
    const container = document.getElementById('chat-messages');
    let imgHtml = '';
    if (imageInput.files.length > 0 && document.getElementById('image-preview').src) {
        imgHtml = `<img src="${document.getElementById('image-preview').src}" class="rounded-xl mb-3 max-w-[200px] opacity-70">`;
    }
    
    const time = new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    
    container.insertAdjacentHTML('beforeend', `
        <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto opacity-70" id="${tempId}">
            <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                ${imgHtml}
                <div>${escapeHtml(msg).replace(/\n/g, '<br>')}</div>
                <div class="text-[9px] text-white/70 mt-2 text-left w-full block"><span class="material-symbols-outlined text-[10px] animate-spin">sync</span></div>
            </div>
        </div>
    `);
    scrollToBottom();
    
    // Clear Inputs immediately and keep focus so user can continuously speak!
    input.value = '';
    const file = imageInput.files[0];
    clearImage();
    input.focus();
    
    // Show Typing Indicator
    if (chatMode === 'ai') {
        pendingAiRequests++;
        document.getElementById('chat-typing').style.display = 'flex';
    }
    scrollToBottom();
    
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('ticket_id', currentTicketId);
    fd.append('message', msg);
    if (file) {
        fd.append('image', file);
    }
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            // Remove this specific temporary placeholder
            document.getElementById(tempId)?.remove();

            if (data.status === 'success') {
                if (data.messages && data.messages.length > 0) {
                    // Instantly render returned messages (user message & AI response)
                    const newOnes = data.messages.filter(m => m.id > lastMessageId);
                    if (newOnes.length > 0) {
                        renderMessages(newOnes);
                        lastMessageId = Math.max(...data.messages.map(m => m.id), lastMessageId);
                        scrollToBottom();
                    }
                } else {
                    fetchMessages();
                }
            } else {
                alert(data.message || 'خطا در ارسال پیام');
            }
        })
        .catch(err => {
            document.getElementById(tempId)?.remove();
            console.error('Chat send error:', err);
        })
        .finally(() => {
            if (chatMode === 'ai') {
                pendingAiRequests = Math.max(0, pendingAiRequests - 1);
                if (pendingAiRequests === 0) {
                    document.getElementById('chat-typing').style.display = 'none';
                }
            }
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.querySelectorAll('.animate-spin').forEach(el => el.remove());
            }
            input.focus();
        });
}

function reopenTicket() {
    if(!confirm("آیا از باز کردن مجدد این گفتگو اطمینان دارید؟")) return;
    
    const fd = new FormData();
    fd.append('action', 'reopen');
    fd.append('ticket_id', currentTicketId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert('خطا در باز کردن مجدد گفتگو');
            }
        });
}

document.addEventListener('DOMContentLoaded', () => {
    fetchMessages();
    chatPollingInterval = setInterval(fetchMessages, 3000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
