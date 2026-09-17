<?php
/**
 * ASENA Doctor Panel — Telehealth & Online Clinical Consultation Hub
 * Real-time clinical triage, patient follow-up & consult termination controls
 */

$currentPage = 'telehealth';
require_once 'includes/doctor_header.php';
require_once '../includes/functions.php';

$doctorId = (int)($doctorProfile['id'] ?? 0);

// Fetch all telehealth tickets for this doctor
$allChats = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name AS patient_name, u.phone AS patient_phone,
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_message,
               (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_sender,
               (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_msg_time,
               (SELECT pet_name FROM appointments WHERE user_id = t.user_id AND doctor_id = t.doctor_id ORDER BY appointment_date DESC LIMIT 1) AS pet_name,
               (SELECT appointment_date FROM appointments WHERE user_id = t.user_id AND doctor_id = t.doctor_id ORDER BY appointment_date DESC LIMIT 1) AS last_appt_date
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.doctor_id = ? AND t.mode = 'doctor'
        ORDER BY CASE WHEN t.status = 'open' THEN 0 ELSE 1 END, t.updated_at DESC
    ");
    $stmt->execute([$doctorId]);
    $allChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("[DoctorTelehealth] Error fetching chats: " . $e->getMessage());
    $allChats = [];
}

// Determine active chat
$activeTicketId = (int)($_GET['ticket_id'] ?? ($allChats[0]['id'] ?? 0));
$activeChat = null;
foreach ($allChats as $chat) {
    if ((int)$chat['id'] === $activeTicketId) {
        $activeChat = $chat;
        break;
    }
}
?>

<div class="p-3 md:p-6 max-w-[1500px] mx-auto h-[calc(100vh-80px)] flex flex-col">
    <!-- Top Bar / Breadcrumb -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">medical_services</span>
            </div>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-primary flex items-center gap-2">
                    <span>مشاوره آنلاین بالینی (تله‌هلث)</span>
                    <span class="text-[11px] font-normal bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">نظارت پزشک</span>
                </h1>
                <p class="text-xs text-on-surface-variant">پاسخگویی مستقیم به مراجعین ویزیت‌شده طی ۷ روز گذشته و پیگیری درمان</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="index.php?tab=bpms-tab" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-bold text-primary flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">medication</span>
                <span>نسخه‌نویسی الکترونیک</span>
            </a>
            <a href="index.php?tab=calendar-tab" class="px-3.5 py-2 bg-primary-container text-white rounded-xl text-xs font-bold hover:bg-primary flex items-center gap-1.5 shadow-sm transition-all">
                <span class="material-symbols-outlined text-base">calendar_month</span>
                <span>تقویم نوبت‌ها</span>
            </a>
        </div>
    </div>

    <!-- Main Workspace Container: 2-Column Split View -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 overflow-hidden bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-xl relative">
        
        <!-- Left Pane: Consultations Thread List (lg:col-span-4) -->
        <div id="thread-list-pane" class="lg:col-span-4 border-l border-outline-variant/20 flex flex-col h-full overflow-hidden bg-white <?= $activeChat ? 'hidden lg:flex' : 'flex' ?>">
            <!-- List Header & Search -->
            <div class="p-4 border-b border-outline-variant/20 bg-surface-container-lowest flex flex-col gap-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-primary flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-emerald-600">forum</span>
                        لیست مشاوره‌ها (<?= count($allChats) ?>)
                    </span>
                    <span class="text-[10px] text-on-surface-variant font-medium">بیماران ۷ روز اخیر</span>
                </div>
                <div class="relative">
                    <input type="text" id="chatSearchInput" onkeyup="filterChats()" placeholder="جستجوی نام بیمار یا پت..." class="w-full bg-surface-container-low border-none rounded-xl pl-9 pr-3 py-2 text-xs font-medium focus:ring-2 focus:ring-emerald-500">
                    <span class="material-symbols-outlined text-sm absolute left-3 top-2.5 text-on-surface-variant">search</span>
                </div>
            </div>

            <!-- List Body -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5" id="chatsContainer">
                <?php if (empty($allChats)): ?>
                    <div class="p-8 text-center text-on-surface-variant flex flex-col items-center gap-3">
                        <span class="material-symbols-outlined text-4xl text-outline">mark_chat_read</span>
                        <p class="text-xs font-bold">هیچ گفتگوی آنلاینی ثبت نشده است.</p>
                        <p class="text-[11px] text-outline">تنها بیمارانی که ظرف ۷ روز گذشته توسط شما ویزیت شده‌اند امکان ارسال پیام دارند.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allChats as $c): 
                        $isActive = ((int)$c['id'] === $activeTicketId);
                        $isOpen = ($c['status'] === 'open');
                        $petDisplay = !empty($c['pet_name']) ? "پت: " . htmlspecialchars($c['pet_name']) : "مراجع بالینی";
                    ?>
                    <a href="telehealth.php?ticket_id=<?= (int)$c['id'] ?>" class="chat-item block p-3 rounded-2xl transition-all border <?= $isActive ? 'bg-emerald-50/70 border-emerald-300 shadow-sm' : 'hover:bg-surface-container-low border-transparent' ?>" data-search="<?= htmlspecialchars(mb_strtolower($c['patient_name'] . ' ' . $c['pet_name'])) ?>">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <div class="w-9 h-9 rounded-full <?= $isOpen ? 'bg-emerald-500 text-white' : 'bg-surface-container-high text-on-surface-variant' ?> flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                    <?= mb_substr($c['patient_name'], 0, 1) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h3 class="text-xs font-bold text-primary truncate"><?= htmlspecialchars($c['patient_name']) ?></h3>
                                    <span class="text-[10px] text-emerald-700 font-medium truncate block"><?= $petDisplay ?></span>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold whitespace-nowrap <?= $isOpen ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-container text-outline' ?>">
                                <?= $isOpen ? 'فعال' : 'مختومه' ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant line-clamp-1 pr-11">
                            <?= !empty($c['last_message']) ? htmlspecialchars($c['last_message']) : 'تصویر یا پیوست ارسالی' ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Pane: Active Thread & Clinical Controls (lg:col-span-8) -->
        <div id="thread-chat-pane" class="lg:col-span-8 flex flex-col h-full overflow-hidden bg-surface-container-lowest <?= $activeChat ? 'flex' : 'hidden lg:flex' ?>">
            <?php if ($activeChat): ?>
                <!-- Active Thread Header -->
                <div class="p-3 md:p-4 bg-white border-b border-outline-variant/20 flex items-center justify-between gap-3 shadow-sm z-10">
                    <div class="flex items-center gap-3">
                        <a href="telehealth.php" class="lg:hidden w-8 h-8 rounded-full hover:bg-surface-container flex items-center justify-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                        <div class="relative">
                            <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm border border-emerald-300">
                                <?= mb_substr($activeChat['patient_name'], 0, 1) ?>
                            </div>
                            <?php if ($activeChat['status'] === 'open'): ?>
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="font-bold text-sm md:text-base text-primary"><?= htmlspecialchars($activeChat['patient_name']) ?></h2>
                                <span class="text-[10px] font-bold text-on-surface-variant/80 bg-surface-container px-2 py-0.5 rounded-full">
                                    تیکت #<?= (int)$activeChat['id'] ?>
                                </span>
                            </div>
                            <p class="text-[11px] text-on-surface-variant flex items-center gap-2">
                                <span>📞 <?= htmlspecialchars($activeChat['patient_phone']) ?></span>
                                <?php if (!empty($activeChat['last_appt_date'])): ?>
                                <span>• آخرین ویزیت: <?= htmlspecialchars($activeChat['last_appt_date']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Clinical Action Buttons -->
                    <div class="flex items-center gap-2">
                        <?php if ($activeChat['status'] === 'open'): ?>
                        <button type="button" onclick="openEndChatModal()" class="px-3 py-1.5 md:px-4 md:py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            <span>پایان مشاوره بالینی</span>
                        </button>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-emerald-600">task_alt</span>
                            مشاوره خاتمه یافته
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resolution Notice (If resolved) -->
                <?php if ($activeChat['status'] === 'resolved' && !empty($activeChat['resolution_notes'])): ?>
                <div class="mx-4 mt-3 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-900 shadow-sm flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0 mt-0.5">verified</span>
                    <div>
                        <div class="font-bold mb-1">جلسه مشاوره توسط شما خاتمه یافته است.</div>
                        <p class="leading-relaxed whitespace-pre-line"><?= htmlspecialchars($activeChat['resolution_notes']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages Container -->
                <div class="flex-1 p-4 md:p-6 space-y-4 overflow-y-auto custom-scrollbar bg-surface-container-lowest" id="doctor-chat-messages">
                    <div class="flex justify-center mb-4">
                        <div class="bg-surface-container px-3 py-0.5 rounded-full text-[10px] text-on-surface-variant font-bold">
                            آغاز گفتگوی تله‌هلث با بیمار
                        </div>
                    </div>
                    <!-- Dynamically populated via JS -->
                </div>

                <!-- Doctor Input Bar -->
                <?php if ($activeChat['status'] === 'open'): ?>
                <div class="p-3 md:p-4 bg-white border-t border-outline-variant/20 space-y-2">
                    <!-- Quick Diagnostic Advice Chips -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-[11px] custom-scrollbar">
                        <button type="button" onclick="setDocChip('سلام، مدارک بررسی شد. روند بهبودی مطلوب است و دارو را طبق دستور ادامه دهید.')" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-emerald-50 hover:text-emerald-800 rounded-full font-medium transition-all">
                            ✅ تأیید روند درمان
                        </button>
                        <button type="button" onclick="setDocChip('لطفاً دوز دارو را طبق نسخه روزانه ۲ بار بعد از غذا مصرف کنید.')" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-emerald-50 hover:text-emerald-800 rounded-full font-medium transition-all">
                            💊 دستور دارویی
                        </button>
                        <button type="button" onclick="setDocChip('در صورت تداوم علائم، لطفاً جهت معاینه حضوری مجدد به کلینیک مراجعه فرمایید.')" class="whitespace-nowrap px-3 py-1 bg-surface-container hover:bg-emerald-50 hover:text-emerald-800 rounded-full font-medium transition-all">
                            🏥 ارجاع به ویزیت حضوری
                        </button>
                    </div>

                    <!-- Input Form -->
                    <form id="doctor-chat-form" class="flex items-center gap-2 relative" onsubmit="sendDoctorMessage(event)">
                        <input type="file" id="doctor-image-input" class="hidden" accept="image/*" onchange="handleDoctorImageSelect(this)">
                        <button type="button" onclick="document.getElementById('doctor-image-input').click()" class="w-11 h-11 rounded-full hover:bg-emerald-50 text-on-surface-variant hover:text-emerald-700 flex items-center justify-center transition-colors shrink-0" title="ارسال تصویر آزمایش یا دستور">
                            <span class="material-symbols-outlined text-2xl">attach_file</span>
                        </button>

                        <div class="flex-1 relative">
                            <input id="doctor-input" dir="auto" class="w-full bg-surface-container-low border-none rounded-full px-5 py-3 focus:ring-2 focus:ring-emerald-500 transition-all text-xs md:text-sm font-medium" placeholder="توصیه و دستورات بالینی خود را بنویسید..." type="text" autocomplete="off" />
                        </div>

                        <button type="submit" id="doctor-send-btn" class="w-11 h-11 bg-emerald-600 text-white rounded-full hover:scale-105 hover:bg-emerald-700 transition-all flex items-center justify-center shadow-lg shrink-0" title="ارسال پیام">
                            <span class="material-symbols-outlined text-xl -ml-0.5">send</span>
                        </button>
                    </form>

                    <!-- Image Preview -->
                    <div id="doctor-preview-container" class="hidden px-4 py-2 bg-surface-container rounded-2xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img id="doctor-preview" src="" class="w-12 h-12 object-cover rounded-lg border border-outline-variant/30">
                            <span class="text-xs font-bold text-primary">تصویر ضمیمه شد</span>
                        </div>
                        <button type="button" onclick="clearDoctorImage()" class="w-7 h-7 bg-rose-100 text-rose-700 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-xs">close</span>
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="p-4 bg-surface-container-low border-t border-outline-variant/20 text-center flex flex-col items-center justify-center gap-2">
                    <p class="text-xs font-bold text-on-surface-variant">این جلسه مشاوره بالینی خاتمه یافته است و بیمار قادر به ارسال پیام جدید نیست.</p>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Chat Selected State -->
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-on-surface-variant">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-3xl">chat</span>
                    </div>
                    <h2 class="text-base font-bold text-primary mb-1">یک گفتگو را از لیست انتخاب فرمایید</h2>
                    <p class="text-xs text-outline max-w-sm">پیام‌ها و تصاویر ارسالی بیماران را مشاهده و مشاوره‌های بالینی خود را ارائه دهید.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: End Consultation Session ("پایان مشاوره بالینی") -->
<div id="endChatModal" class="fixed inset-0 bg-black/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl relative">
        <button onclick="closeEndChatModal()" class="absolute top-5 left-5 text-on-surface-variant hover:text-rose-600">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">verified</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-primary">پایان مشاوره بالینی</h3>
                <p class="text-xs text-on-surface-variant">ثبت خلاصه و خاتمه جلسه آنلاین</p>
            </div>
        </div>

        <p class="text-xs text-on-surface-variant mb-4 leading-relaxed">
            با پایان جلسه، امکان ارسال پیام از سوی بیمار مسدود شده و توصیه‌های نهایی شما در پنل بیمار ثبت می‌گردد.
        </p>

        <form onsubmit="submitEndChat(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-primary mb-1.5">دستورات و توصیه‌های نهایی پزشک (اختیاری):</label>
                <textarea id="resolutionNotes" rows="4" placeholder="مثال: روند بهبودی مطلوب ارزیابی شد. مصرف قطره چشمی تا ۳ روز آینده ادامه یابد..." class="w-full text-xs p-3 bg-surface-container-low border border-outline-variant/30 rounded-2xl outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" id="endChatSubmitBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl text-xs font-bold transition-all shadow-md">
                    تأیید و خاتمه جلسه مشاوره
                </button>
                <button type="button" onclick="closeEndChatModal()" class="px-4 py-2.5 bg-surface-container text-on-surface-variant rounded-xl text-xs font-bold hover:bg-surface-container-high transition-all">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const activeTicketId = <?= (int)$activeTicketId ?>;
let lastMsgId = 0;
let pollingInterval = null;

function filterChats() {
    const q = document.getElementById('chatSearchInput').value.toLowerCase().trim();
    document.querySelectorAll('.chat-item').forEach(item => {
        const searchTxt = item.getAttribute('data-search') || '';
        item.style.display = searchTxt.includes(q) ? 'block' : 'none';
    });
}

function setDocChip(text) {
    const input = document.getElementById('doctor-input');
    if (!input) return;
    input.value = text;
    input.focus();
}

function handleDoctorImageSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('doctor-preview').src = e.target.result;
            document.getElementById('doctor-preview-container').classList.remove('hidden');
            document.getElementById('doctor-preview-container').classList.add('flex');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearDoctorImage() {
    document.getElementById('doctor-image-input').value = '';
    document.getElementById('doctor-preview-container').classList.add('hidden');
    document.getElementById('doctor-preview-container').classList.remove('flex');
    document.getElementById('doctor-preview').src = '';
}

function fetchDoctorMessages() {
    if (!activeTicketId) return;
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', activeTicketId);
    fd.append('last_id', lastMsgId);

    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderDoctorMessages(data.messages);
                lastMsgId = data.messages[data.messages.length - 1].id;
                scrollDoctorChatBottom();
            }
        });
}

function escapeDocHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderDoctorMessages(messages) {
    const container = document.getElementById('doctor-chat-messages');
    if (!container) return;

    messages.forEach(msg => {
        const isDoc = (msg.sender_type === 'doctor' || msg.sender_type === 'admin');
        let imgHtml = '';
        if (msg.image_url) {
            const safeUrl = '../' + escapeDocHtml(msg.image_url).replace(/^\.\.\//, '');
            imgHtml = `<a href="${safeUrl}" target="_blank" class="block mb-2"><img src="${safeUrl}" class="rounded-xl max-w-[220px] max-h-[220px] object-cover border border-outline-variant/20 hover:scale-105 transition-transform" alt="ضمیمه بالینی"></a>`;
        }
        const safeText = escapeDocHtml(msg.message).replace(/\n/g, '<br>');
        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });

        if (isDoc) {
            // Doctor message (Right side / emerald theme)
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-emerald-600 text-white px-4 py-3 rounded-3xl rounded-tl-sm shadow-md text-xs leading-relaxed">
                        <div class="text-[10px] font-bold text-emerald-100 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">stethoscope</span>
                            <span>دستور پزشک</span>
                        </div>
                        ${imgHtml}
                        <div dir="auto">${safeText}</div>
                        <div class="text-[9px] text-white/70 mt-1 text-left w-full block">${time} <span class="material-symbols-outlined text-[10px] ml-0.5" style="vertical-align: middle">done_all</span></div>
                    </div>
                </div>
            `);
        } else {
            // Patient message (Left side / white bubble)
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-3 max-w-[85%]">
                    <div class="w-8 h-8 rounded-full bg-primary-container text-white flex items-center justify-center shrink-0 border border-white shadow-sm mt-auto text-xs font-bold">
                        بیمار
                    </div>
                    <div class="bg-white px-4 py-3 rounded-3xl rounded-br-sm shadow-md text-xs border border-outline-variant/10 leading-relaxed text-on-surface">
                        <div class="text-[10px] font-bold text-primary mb-1">گزارش بیمار</div>
                        ${imgHtml}
                        <div dir="auto">${safeText}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-1 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function scrollDoctorChatBottom() {
    const c = document.getElementById('doctor-chat-messages');
    if (c) c.scrollTop = c.scrollHeight;
}

function sendDoctorMessage(e) {
    e.preventDefault();
    const input = document.getElementById('doctor-input');
    const imageInput = document.getElementById('doctor-image-input');
    const msg = input.value.trim();

    if (!msg && imageInput.files.length === 0) return;

    const fd = new FormData();
    fd.append('action', 'doctor_send');
    fd.append('ticket_id', activeTicketId);
    fd.append('message', msg);
    if (imageInput.files[0]) {
        fd.append('image', imageInput.files[0]);
    }

    input.value = '';
    clearDoctorImage();

    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                fetchDoctorMessages();
            } else {
                alert(data.message || 'خطا در ارسال پیام');
            }
        })
        .catch(err => alert('خطا در برقراری ارتباط'));
}

function openEndChatModal() {
    document.getElementById('endChatModal').classList.remove('hidden');
}

function closeEndChatModal() {
    document.getElementById('endChatModal').classList.add('hidden');
}

function submitEndChat(e) {
    e.preventDefault();
    const notes = document.getElementById('resolutionNotes').value.trim();
    const btn = document.getElementById('endChatSubmitBtn');
    btn.disabled = true;
    btn.innerText = 'در حال ثبت...';

    const fd = new FormData();
    fd.append('action', 'doctor_end_chat');
    fd.append('ticket_id', activeTicketId);
    fd.append('resolution_notes', notes);

    fetch('../actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeEndChatModal();
                location.reload();
            } else {
                alert(data.message || 'خطا در خاتمه جلسه');
                btn.disabled = false;
                btn.innerText = 'تأیید و خاتمه جلسه مشاوره';
            }
        })
        .catch(err => {
            alert('خطای ارتباط با سرور');
            btn.disabled = false;
            btn.innerText = 'تأیید و خاتمه جلسه مشاوره';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    if (activeTicketId) {
        fetchDoctorMessages();
        pollingInterval = setInterval(fetchDoctorMessages, 3000);
    }
});
</script>

<?php require_once 'includes/doctor_footer.php'; ?>
