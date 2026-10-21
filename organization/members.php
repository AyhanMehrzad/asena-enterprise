<?php
require_once 'includes/organization_header.php';

$orgService = App::organization();
$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Check permission: Owner, Super Admin, or user with manage_admins permission
$isOwner = (!empty($currentOrg['user_id']) && (int)$currentOrg['user_id'] === (int)$currentUser['id']) || ($currentUser['role'] === 'admin');
$canManageMembers = $isOwner || hasOrgPermission('manage_admins', $currentAdminPermissions) || hasOrgPermission('manage_doctors', $currentAdminPermissions);

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();

    if (!$canManageMembers) {
        $message = 'شما دسترسی لازم برای تغییر یا ارسال دعوت‌نامه کادر این مرکز را ندارید.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];

        // 1. Send Invitation to Verified Specialist
        if ($action === 'send_invitation' || $action === 'resend_invitation') {
            $targetUserId   = (int)($_POST['user_id'] ?? 0);
            $suggestedRole  = trim($_POST['suggested_role'] ?? 'staff');
            $jobTitle       = trim($_POST['job_title'] ?? '');
            $shifts         = trim($_POST['shifts'] ?? '');
            $commissionRate = (float)($_POST['commission_rate'] ?? 0);
            $notes          = trim($_POST['notes'] ?? '');

            if ($targetUserId <= 0) {
                $message = 'لطفاً یک متخصص احراز هویت شده را از فهرست جستجو انتخاب فرمایید.';
                $messageType = 'error';
            } else {
                $res = $orgService->sendInvitation($orgId, $targetUserId, $suggestedRole, $jobTitle, $shifts, $commissionRate, $notes);
                if (!empty($res['success'])) {
                    $message = $res['message'];
                    $messageType = 'success';
                } else {
                    $message = $res['message'] ?? 'خطا در ارسال دعوت‌نامه همکاری.';
                    $messageType = 'error';
                }
            }
        }

        // 2. Cancel Pending Invitation
        elseif ($action === 'cancel_invitation') {
            $invId = (int)($_POST['invitation_id'] ?? 0);
            if ($invId > 0) {
                $ok = $orgService->cancelInvitation($invId, $orgId);
                if ($ok) {
                    $message = 'دعوت‌نامه همکاری با موفقیت لغو شد.';
                    $messageType = 'success';
                } else {
                    $message = 'خطا در لغو دعوت‌نامه یا دعوت‌نامه قبلاً تعیین تکلیف شده است.';
                    $messageType = 'error';
                }
            }
        }

        // 3. Edit Active Member Customization (Job title, shifts, notes)
        elseif ($action === 'edit_member') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newRole      = trim($_POST['member_role'] ?? 'staff');
            $jobTitle     = trim($_POST['job_title'] ?? '');
            $notes        = trim($_POST['notes'] ?? '');

            if ($targetUserId > 0) {
                $ok = $orgService->assignMember($orgId, $targetUserId, $newRole, $jobTitle, $notes);
                if ($ok) {
                    $message = 'اطلاعات، سمت سازمانی و شیفت کاری همکار با موفقیت ذخیره گردید.';
                    $messageType = 'success';
                } else {
                    $message = 'خطا در به‌روزرسانی اطلاعات همکار.';
                    $messageType = 'error';
                }
            }
        }

        // 4. Remove member (Unlink)
        elseif ($action === 'remove_member') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId > 0) {
                $ok = $orgService->removeMember($orgId, $targetUserId);
                if ($ok) {
                    $message = 'همکار با موفقیت از کادر این مرکز منفصل گردید.';
                    $messageType = 'success';
                } else {
                    $message = 'خطا در منفصل‌سازی همکار از مرکز.';
                    $messageType = 'error';
                }
            }
        }

        // 5. Toggle member status
        elseif ($action === 'toggle_status') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newStatus    = trim($_POST['status'] ?? 'active');
            if ($targetUserId > 0 && in_array($newStatus, ['active', 'inactive'])) {
                $orgService->updateMemberStatus($orgId, $targetUserId, $newStatus);
                $message = 'وضعیت فعالیت همکار با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    }
}

// Search Filter Handling
$searchQuery = trim($_GET['sq'] ?? '');
$searchRole  = trim($_GET['srole'] ?? 'all');
$searchResults = [];
if (!empty($searchQuery) || ($searchRole !== 'all' && isset($_GET['search_submitted']))) {
    $searchResults = $orgService->searchSpecialists($searchQuery, $searchRole, $orgId, 20);
}

// Fetch all active members
$members = $orgService->getMembers($orgId);

// Fetch all invitations
$invitations = $orgService->getInvitations($orgId);
$pendingInvitations = array_filter($invitations, fn($i) => $i['status'] === 'pending');

// Role Counts
$countTotal = count($members);
$countPharma = count(array_filter($members, fn($m) => in_array($m['member_role'], ['pharmacologist', 'pharmacist'])));
$countDocs = count(array_filter($members, fn($m) => $m['member_role'] === 'doctor'));
$countGroomers = count(array_filter($members, fn($m) => $m['member_role'] === 'groomer'));
$countManagers = count(array_filter($members, fn($m) => in_array($m['member_role'], ['manager', 'assistant_manager', 'receptionist'])));
?>

<div class="p-4 sm:p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header Cockpit -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    <span>سازمان و مرکز درمانی: <?= htmlspecialchars($currentOrg['name']) ?></span>
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-indigo-600 text-3xl">badge</span>
                <span>مدیریت هوشمند کادر، پزشکان، گرومرها و داروسازان</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">دعوت دوطرفه از متخصصان دارای حساب کاربری تاییدشده در آسنا، تنظیم توافق‌نامه شیفت و انبار مشترک</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="#invite-specialists-section" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">person_search</span>
                <span>جستجو و ارسال دعوت‌نامه جدید</span>
            </a>
            <a href="#invitations-history-section" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition-all relative">
                <span class="material-symbols-outlined text-base text-amber-600">outgoing_mail</span>
                <span>پیگیری دعوت‌نامه‌ها</span>
                <?php if (count($pendingInvitations) > 0): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500 absolute -top-0.5 -right-0.5"></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Security & Legal Policy Note -->
    <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border border-indigo-100 flex items-start gap-3.5 text-xs text-indigo-950">
        <span class="material-symbols-outlined text-indigo-600 shrink-0 text-xl mt-0.5">verified_user</span>
        <div class="space-y-1">
            <p class="font-bold">نظام‌نامه امنیت و یکپارچگی کادر سلامت و خدمات آسنا:</p>
            <p class="text-slate-600 leading-relaxed">
                جهت پیشگیری از ایجاد هویت‌های نامعتبر، امکان ساخت دستی حساب کاربری بدون هویت در پنل مراکز مسدود می‌باشد. کلیه همکاران محترم (پزشک، گرومر، داروساز و ...) باید ابتدا دارای حساب کاربری فعال و احراز هویت شده در آسنا باشند. مرکز شما می‌تواند نام یا شماره ایشان را جستجو کرده و دعوت‌نامه رسمی ارسال نماید. به محض پذیرش توسط متخصص در پنل شخصی‌اش، اتصال و دسترسی‌های سازمانی به صورت خودکار فعال می‌گردد.
            </p>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 5 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">groups</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500">کادر رسمی مرکز</p>
                <h3 class="text-lg font-black text-slate-900 mt-0.5"><?= number_format($countTotal) ?> نفر</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500">دامپزشک و درمان</p>
                <h3 class="text-lg font-black text-emerald-700 mt-0.5"><?= number_format($countDocs) ?> پزشک</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">content_cut</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500">گرومر و آرایشگر</p>
                <h3 class="text-lg font-black text-amber-700 mt-0.5"><?= number_format($countGroomers) ?> نفر</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">medication</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500">داروساز / مسئول فنی</p>
                <h3 class="text-lg font-black text-purple-700 mt-0.5"><?= number_format($countPharma) ?> نفر</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">outgoing_mail</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500">دعوت‌نامه در انتظار</p>
                <h3 class="text-lg font-black text-yellow-700 mt-0.5"><?= number_format(count($pendingInvitations)) ?> مورد</h3>
            </div>
        </div>
    </div>

    <!-- SECTION 1: SEARCH & INVITE SPECIALISTS -->
    <div id="invite-specialists-section" class="bg-white rounded-3xl border border-indigo-100 shadow-sm overflow-hidden">
        <div class="p-5 bg-gradient-to-l from-indigo-50/50 via-white to-transparent border-b border-indigo-100/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">person_search</span>
                    <span>جستجو در بانک متخصصان احراز هویت شده آسنا</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">یافتن پزشکان، داروسازان، گرومرها یا کادر پذیرش با شماره تماس یا نام و ارسال دعوت‌نامه رسمی</p>
            </div>
            <span class="text-[11px] font-bold px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                اتصال دوطرفه و غیرقابل جعل
            </span>
        </div>

        <div class="p-5 space-y-4">
            <form method="GET" action="members.php#invite-specialists-section" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <input type="hidden" name="search_submitted" value="1">
                
                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام متخصص، کد نظام یا شماره موبایل</label>
                    <div class="relative">
                        <input type="text" name="sq" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="مثال: دکتر مهسا راد یا ۰۹۱۲۳۴۵۶۷۸۹" class="w-full h-11 pl-4 pr-10 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-xl pointer-events-none">search</span>
                    </div>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">فیلتر بر اساس تخصص / نقش</label>
                    <select name="srole" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-700">
                        <option value="all" <?= $searchRole === 'all' ? 'selected' : '' ?>>همه متخصصان و همکاران</option>
                        <option value="doctor" <?= $searchRole === 'doctor' ? 'selected' : '' ?>>دامپزشک و متخصص درمان</option>
                        <option value="groomer" <?= $searchRole === 'groomer' ? 'selected' : '' ?>>گرومر و آرایشگر حیوانات</option>
                        <option value="pharmacist" <?= $searchRole === 'pharmacist' ? 'selected' : '' ?>>مسئول فنی و دکتر داروساز</option>
                        <option value="pharmacologist" <?= $searchRole === 'pharmacologist' ? 'selected' : '' ?>>فارماکولوژیست</option>
                        <option value="staff" <?= $searchRole === 'staff' ? 'selected' : '' ?>>پرسنل عمومی و پذیرش</option>
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-end">
                    <button type="submit" class="w-full h-11 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm flex items-center justify-center gap-1.5 transition-all">
                        <span class="material-symbols-outlined text-base">search</span>
                        <span>جستجو</span>
                    </button>
                </div>
            </form>

            <!-- Search Results Display -->
            <?php if (!empty($searchResults)): ?>
                <div class="pt-3 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-600 mb-3">نتایج جستجو (<?= count($searchResults) ?> نفر یافت شد):</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        <?php foreach ($searchResults as $sp): 
                            $rel = $sp['org_relation'] ?? 'available';
                            $roleLabel = match($sp['role']) {
                                'doctor' => 'دامپزشک معالج',
                                'groomer' => 'گرومر تخصصی پت',
                                'pharmacist' => 'مسئول فنی داروخانه',
                                'pharmacologist' => 'دکتر فارماکولوژیست',
                                default => 'کاربر فعال آسنا'
                            };
                        ?>
                        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-indigo-300 transition-all flex flex-col justify-between gap-3 shadow-xs">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 font-black text-sm">
                                    <?php if (!empty($sp['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($sp['avatar']) ?>" class="w-full h-full object-cover rounded-xl" alt="avatar">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-2xl">account_circle</span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-black text-xs text-slate-900 truncate"><?= htmlspecialchars($sp['name']) ?></h4>
                                        <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800">
                                            <span class="material-symbols-outlined text-[10px]">verified</span>
                                            <span>احراز هویت شده</span>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-indigo-700 font-bold"><?= $roleLabel ?> <?= !empty($sp['doctor_specialty']) ? ' - ' . htmlspecialchars($sp['doctor_specialty']) : '' ?></p>
                                    <p class="text-[11px] font-mono text-slate-500" dir="ltr"><?= htmlspecialchars($sp['phone'] ?? '—') ?></p>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <?php if ($rel === 'active_member'): ?>
                                    <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        <span>عضو فعال مرکز</span>
                                    </span>
                                <?php elseif ($rel === 'pending_invitation'): ?>
                                    <span class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">hourglass_top</span>
                                        <span>دعوت‌نامه در انتظار پاسخ</span>
                                    </span>
                                <?php else: ?>
                                    <button type="button" onclick="openInviteModal(<?= (int)$sp['id'] ?>, '<?= htmlspecialchars(addslashes($sp['name'])) ?>', '<?= htmlspecialchars($sp['role'] ?? 'staff') ?>')" class="w-full px-3 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center gap-1.5 transition-all shadow-xs">
                                        <span class="material-symbols-outlined text-sm">outgoing_mail</span>
                                        <span>ارسال دعوت‌نامه همکاری</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['search_submitted'])): ?>
                <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-2xl">
                    <span class="material-symbols-outlined text-3xl block mb-1 text-slate-300">person_off</span>
                    <span>متخصصی با مشخصات جستجو شده یافت نشد. اطمینان حاصل کنید که فرد مورد نظر با این شماره در آسنا ثبت‌نام کرده باشد.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 2: SENT INVITATIONS TRACKER -->
    <div id="invitations-history-section" class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">outgoing_mail</span>
                <span>دعوت‌نامه‌های ارسالی مرکز و پیگیری وضعیت پذیرش</span>
            </h3>
            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-bold">
                <?= count($invitations) ?> دعوت‌نامه
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">متخصص مدعو</th>
                        <th class="p-4">نقش پیشنهادی</th>
                        <th class="p-4">شیفت و پورسانت پیشنهادی</th>
                        <th class="p-4">وضعیت دعوت‌نامه</th>
                        <th class="p-4">تاریخ ارسال</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($invitations)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                هنوز هیچ دعوت‌نامه‌ای از طرف این مرکز ارسال نشده است. از بخش جستجوی بالا متخصصان مورد نظر خود را دعوت نمایید.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invitations as $inv): 
                            $statusMeta = match($inv['status']) {
                                'pending'  => ['label' => 'در انتظار پاسخ متخصص', 'badge' => 'bg-amber-100 text-amber-900', 'icon' => 'hourglass_top'],
                                'accepted' => ['label' => 'تأیید شده و پیوسته به کادر', 'badge' => 'bg-emerald-100 text-emerald-900', 'icon' => 'check_circle'],
                                'declined' => ['label' => 'رد شده توسط متخصص', 'badge' => 'bg-rose-100 text-rose-900', 'icon' => 'cancel'],
                                'cancelled'=> ['label' => 'لغو شده توسط مرکز', 'badge' => 'bg-slate-100 text-slate-600', 'icon' => 'block'],
                                default    => ['label' => $inv['status'], 'badge' => 'bg-slate-100 text-slate-700', 'icon' => 'info'],
                            };
                            $roleName = match($inv['suggested_role']) {
                                'doctor' => 'پزشک معالج / جراح',
                                'groomer' => 'گرومر و آرایشگر پت',
                                'pharmacist' => 'مسئول فنی داروخانه',
                                'pharmacologist' => 'دکتر فارماکولوژیست',
                                'receptionist' => 'سرپرست پذیرش',
                                'manager' => 'مدیر داخلی',
                                'assistant_manager' => 'معاون اجرایی',
                                default => 'پرسنل مرکز'
                            };
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4">
                                <div class="font-black text-slate-900"><?= htmlspecialchars($inv['specialist_name'] ?? 'متخصص آسنا') ?></div>
                                <div class="text-[11px] font-mono text-slate-400" dir="ltr"><?= htmlspecialchars($inv['specialist_phone'] ?? '—') ?></div>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-slate-800"><?= $roleName ?></span>
                                <?php if (!empty($inv['job_title'])): ?>
                                    <div class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($inv['job_title']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="text-slate-700"><?= htmlspecialchars($inv['proposed_shifts'] ?: 'توافقی') ?></div>
                                <?php if ((float)$inv['commission_rate'] > 0): ?>
                                    <div class="text-[11px] text-emerald-700 font-bold mt-0.5"><?= (float)$inv['commission_rate'] ?>٪ پورسانت خدمات</div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold <?= $statusMeta['badge'] ?>">
                                    <span class="material-symbols-outlined text-xs"><?= $statusMeta['icon'] ?></span>
                                    <span><?= $statusMeta['label'] ?></span>
                                </span>
                                <?php if (!empty($inv['declined_reason'])): ?>
                                    <div class="text-[10px] text-rose-600 mt-1 max-w-xs">علت: <?= htmlspecialchars($inv['declined_reason']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono text-slate-500 text-[11px]">
                                <?= substr($inv['created_at'], 0, 16) ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($inv['status'] === 'pending'): ?>
                                    <form method="POST" action="members.php" class="inline m-0" onsubmit="return confirm('آیا از لغو این دعوت‌نامه اطمینان دارید؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_invitation">
                                        <input type="hidden" name="invitation_id" value="<?= (int)$inv['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors">
                                            لغو دعوت‌نامه
                                        </button>
                                    </form>
                                <?php elseif (in_array($inv['status'], ['declined', 'cancelled'])): ?>
                                    <button type="button" onclick="openInviteModal(<?= (int)$inv['user_id'] ?>, '<?= htmlspecialchars(addslashes($inv['specialist_name'])) ?>', '<?= htmlspecialchars($inv['suggested_role']) ?>')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                                        ارسال مجدد
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-400 text-[11px]">پیوسته</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 3: ACTIVE PERSONNEL ROSTER -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">badge</span>
                <span>فهرست پرسنل، متخصصان و کادر رسمی مرکز</span>
            </h3>
            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-bold">
                <?= count($members) ?> نفر عضو
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">همکار / متخصص</th>
                        <th class="p-4">نقش سازمانی</th>
                        <th class="p-4">سمت و شیفت کاری</th>
                        <th class="p-4">اطلاعات تماس</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4 text-center">عملیات سازمانی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">group_off</span>
                                هنوز متخصصی به کادر این مرکز پیوسته نشده است. از فرم جستجوی بالا برای ارسال دعوت‌نامه رسمی استفاده نمایید.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): 
                            $roleMeta = match($m['member_role']) {
                                'pharmacologist' => ['title' => 'فارماکولوژیست', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'medication'],
                                'pharmacist'     => ['title' => 'مسئول داروخانه', 'bg' => 'bg-teal-50 text-teal-700 border-teal-200', 'icon' => 'local_pharmacy'],
                                'doctor'         => ['title' => 'پزشک متخصص', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'stethoscope'],
                                'assistant_manager' => ['title' => 'معاون اجرایی', 'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'icon' => 'manage_accounts'],
                                'receptionist'   => ['title' => 'پذیرش و تریاژ', 'bg' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'support_agent'],
                                'groomer'        => ['title' => 'گرومر پت', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'content_cut'],
                                default          => ['title' => 'پرسنل مرکز', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'person'],
                            };
                            $isActive = ($m['status'] === 'active');
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 <?= $roleMeta['bg'] ?>">
                                        <span class="material-symbols-outlined text-lg"><?= $roleMeta['icon'] ?></span>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900"><?= htmlspecialchars($m['user_name'] ?? 'همکار آسنا') ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">شناسه #<?= (int)$m['user_id'] ?></div>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border inline-flex items-center gap-1 <?= $roleMeta['bg'] ?>">
                                    <span class="material-symbols-outlined text-xs"><?= $roleMeta['icon'] ?></span>
                                    <span><?= $roleMeta['title'] ?></span>
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($m['job_title'] ?: 'عضو رسمی کادر مرکز') ?></div>
                                <?php if (!empty($m['notes'])): ?>
                                    <div class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($m['notes']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 font-mono text-slate-700" dir="ltr">
                                <?= htmlspecialchars($m['user_phone'] ?? '—') ?>
                            </td>

                            <td class="p-4">
                                <?php if ($isActive): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        فعال در مرکز
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        غیرفعال
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <?php if (in_array($m['member_role'], ['pharmacologist', 'pharmacist'])): ?>
                                        <a href="inventory.php" class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 text-[10px] font-bold flex items-center gap-1 transition-all" title="مشاهده انبار این داروساز">
                                            <span class="material-symbols-outlined text-xs">inventory_2</span>
                                            <span>انبار</span>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Edit Member Modal Trigger -->
                                    <button type="button" onclick="openEditMemberModal(<?= (int)$m['user_id'] ?>, '<?= htmlspecialchars(addslashes($m['user_name'])) ?>', '<?= htmlspecialchars($m['member_role']) ?>', '<?= htmlspecialchars(addslashes($m['job_title'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($m['notes'] ?? '')) ?>')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 transition-colors" title="ویرایش عنوان و شیفت">
                                        <span class="material-symbols-outlined text-xs">edit</span>
                                        <span>ویرایش</span>
                                    </button>

                                    <!-- Toggle Active -->
                                    <form method="POST" action="members.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors">
                                            <?= $isActive ? 'تعلیق' : 'فعال‌سازی' ?>
                                        </button>
                                    </form>

                                    <!-- Remove Member -->
                                    <form method="POST" action="members.php" class="inline m-0" onsubmit="return confirm('آیا از انفصال این همکار از کادر مرکز اطمینان دارید؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
                                        <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition-colors" title="انفصال از کادر مرکز">
                                            <span class="material-symbols-outlined text-base">person_remove</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Send / Resend Invitation -->
<div id="invite-specialist-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-indigo-100 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">outgoing_mail</span>
                <span>ارسال دعوت‌نامه رسمی همکاری به: <span id="modal-specialist-name" class="text-indigo-600"></span></span>
            </h3>
            <button type="button" onclick="closeInviteModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="members.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_invitation">
            <input type="hidden" id="modal-user-id" name="user_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نقش و مسئولیت پیشنهادی در این مرکز *</label>
                <select id="modal-role-select" name="suggested_role" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-800">
                    <option value="doctor">دامپزشک معالج و جراح</option>
                    <option value="groomer">گرومر و آرایشگر حیوانات خانگی</option>
                    <option value="pharmacologist">دکتر فارماکولوژیست (انبار و مشاوره دارویی)</option>
                    <option value="pharmacist">مسئول فنی داروخانه مرکز</option>
                    <option value="assistant_manager">معاون اجرایی و مدیر داخلی کلینیک</option>
                    <option value="receptionist">سرپرست پذیرش و تریاژ مراجعین</option>
                    <option value="staff">پرسنل عمومی و پشتیبانی درمانی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">سمت شغلی / عنوان سازمانی</label>
                <input type="text" name="job_title" placeholder="مثال: سرپرست بخش جراحی بافت نرم" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شیفت کاری پیشنهادی</label>
                    <input type="text" name="shifts" placeholder="مثال: شنبه تا چهارشنبه ۱۶ الی ۲۱" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">درصد پورسانت خدمات (در صورت توافق)</label>
                    <input type="number" step="0.5" min="0" max="100" name="commission_rate" placeholder="مثال: 50" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">پیام دعوت یا توضیحات همکاری</label>
                <textarea name="notes" rows="3" placeholder="توضیحات تکمیلی شرایط همکاری، تجهیزات کلینیک یا جزئیات مورد توافق..." class="w-full p-3 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeInviteModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 transition-all">
                    ارسال دعوت‌نامه رسمی
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Active Member Customization -->
<div id="edit-member-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">edit_note</span>
                <span>ویرایش اطلاعات و شیفت: <span id="edit-member-name" class="text-indigo-600"></span></span>
            </h3>
            <button type="button" onclick="closeEditMemberModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="members.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_member">
            <input type="hidden" id="edit-member-id" name="user_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نقش سازمانی در مرکز</label>
                <select id="edit-member-role" name="member_role" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-800">
                    <option value="doctor">دامپزشک معالج و جراح</option>
                    <option value="groomer">گرومر و آرایشگر حیوانات خانگی</option>
                    <option value="pharmacologist">دکتر فارماکولوژیست</option>
                    <option value="pharmacist">مسئول فنی داروخانه</option>
                    <option value="assistant_manager">معاون اجرایی و مدیر داخلی کلینیک</option>
                    <option value="receptionist">سرپرست پذیرش و تریاژ مراجعین</option>
                    <option value="staff">پرسنل عمومی و خدمات</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">سمت شغلی / عنوان سازمانی</label>
                <input type="text" id="edit-member-job" name="job_title" placeholder="سمت سازمانی..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شیفت حضور یا یادداشت داخلی</label>
                <textarea id="edit-member-notes" name="notes" rows="3" placeholder="روزهای کاری، ساعات شیفت و توضیحات داخلی..." class="w-full p-3 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeEditMemberModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openInviteModal(userId, name, defaultRole) {
    document.getElementById('modal-user-id').value = userId;
    document.getElementById('modal-specialist-name').textContent = name;
    if (defaultRole) {
        var sel = document.getElementById('modal-role-select');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === defaultRole) {
                sel.selectedIndex = i;
                break;
            }
        }
    }
    document.getElementById('invite-specialist-modal').classList.remove('hidden');
}

function closeInviteModal() {
    document.getElementById('invite-specialist-modal').classList.add('hidden');
}

function openEditMemberModal(userId, name, role, jobTitle, notes) {
    document.getElementById('edit-member-id').value = userId;
    document.getElementById('edit-member-name').textContent = name;
    document.getElementById('edit-member-job').value = jobTitle || '';
    document.getElementById('edit-member-notes').value = notes || '';
    var sel = document.getElementById('edit-member-role');
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === role) {
            sel.selectedIndex = i;
            break;
        }
    }
    document.getElementById('edit-member-modal').classList.remove('hidden');
}

function closeEditMemberModal() {
    document.getElementById('edit-member-modal').classList.add('hidden');
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
