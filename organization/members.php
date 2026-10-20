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
        $message = 'شما دسترسی لازم برای تغییر یا انتساب کادر این مرکز را ندارید.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];

        // 1. Assign existing user by phone or ID
        if ($action === 'assign_user') {
            $phone    = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
            $role     = trim($_POST['member_role'] ?? 'staff');
            $jobTitle = trim($_POST['job_title'] ?? '');
            $notes    = trim($_POST['notes'] ?? '');

            if (strlen($phone) === 10 && strpos($phone, '9') === 0) {
                $phone = '0' . $phone;
            } elseif (strlen($phone) === 12 && strpos($phone, '98') === 0) {
                $phone = '0' . substr($phone, 2);
            }

            if (empty($phone) || strlen($phone) !== 11) {
                $message = 'لطفاً یک شماره همراه معتبر ۱۱ رقمی (مانند ۰۹۱۲۳۴۵۶۷۸۹) وارد فرمایید.';
                $messageType = 'error';
            } else {
                $uStmt = $pdo->prepare("SELECT id, name, role FROM users WHERE phone = ? LIMIT 1");
                $uStmt->execute([$phone]);
                $user = $uStmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $message = "کاربری با شماره {$phone} در سامانه یافت نشد. می‌توانید از بخش «تعریف سریع پرسنل جدید» برای ایشان حساب بسازید.";
                    $messageType = 'error';
                } else {
                    $targetUserId = (int)$user['id'];
                    $ok = $orgService->assignMember($orgId, $targetUserId, $role, $jobTitle, $notes);

                    // If role is doctor, also sync with organization_doctors
                    if ($role === 'doctor') {
                        $docChk = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                        $docChk->execute([$targetUserId]);
                        $docId = (int)$docChk->fetchColumn();
                        if ($docId > 0) {
                            $orgService->linkDoctor($orgId, $docId, 0, 'شنبه تا چهارشنبه', '۱۶:۰۰ الی ۲۱:۰۰', 'doctor');
                        }
                    }

                    if ($ok) {
                        $roleTitles = [
                            'pharmacologist' => 'فارماکولوژیست / داروساز',
                            'pharmacist'     => 'مسئول فنی داروخانه',
                            'doctor'         => 'پزشک معالج',
                            'manager'        => 'مدیر داخلی',
                            'assistant_manager' => 'معاون اجرایی',
                            'receptionist'   => 'پذیرش و نوبت‌دهی',
                            'groomer'        => 'گرومر و آرایشگر',
                            'staff'          => 'پرسنل مرکز'
                        ];
                        $roleLabel = $roleTitles[$role] ?? $role;
                        $message = "کاربر گرامی «{$user['name']}» با موفقیت در نقش «{$roleLabel}» به این مرکز منتسب گردید.";
                        $messageType = 'success';
                    } else {
                        $message = 'خطا در انتساب کاربر به مرکز.';
                        $messageType = 'error';
                    }
                }
            }
        }

        // 2. Quick create user & assign
        elseif ($action === 'create_and_assign') {
            $name     = trim($_POST['name'] ?? '');
            $phone    = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            $role     = trim($_POST['member_role'] ?? 'staff');
            $jobTitle = trim($_POST['job_title'] ?? '');
            $notes    = trim($_POST['notes'] ?? '');

            if (strlen($phone) === 10 && strpos($phone, '9') === 0) {
                $phone = '0' . $phone;
            } elseif (strlen($phone) === 12 && strpos($phone, '98') === 0) {
                $phone = '0' . substr($phone, 2);
            }

            if (empty($name) || mb_strlen($name) < 2) {
                $message = 'لطفاً نام و نام خانوادگی را به طور کامل وارد فرمایید.';
                $messageType = 'error';
            } elseif (strlen($phone) !== 11 || strpos($phone, '09') !== 0) {
                $message = 'شماره تلفن همراه ۱۱ رقمی معتبر الزامی است.';
                $messageType = 'error';
            } elseif (empty($password) || strlen($password) < 6) {
                $message = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
                $messageType = 'error';
            } else {
                // Check if user already exists
                $chk = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                $chk->execute([$phone]);
                $existingId = (int)$chk->fetchColumn();

                if ($existingId > 0) {
                    $targetUserId = $existingId;
                    $message = 'این کاربر قبلاً در سامانه ثبت‌نام داشته است و حساب ایشان به کادر این مرکز منتسب شد.';
                } else {
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (name, phone, password, role, organization_id, verification_status, created_at)
                        VALUES (?, ?, ?, ?, ?, 'approved', NOW())
                    ");
                    $ins->execute([$name, $phone, $passHash, $role, $orgId]);
                    $targetUserId = (int)$pdo->lastInsertId();
                }

                $ok = $orgService->assignMember($orgId, $targetUserId, $role, $jobTitle, $notes);
                if ($ok) {
                    $message = "همکار جدید «{$name}» با موفقیت تعریف و به کادر مرکز افزوده شد.";
                    $messageType = 'success';
                }
            }
        }

        // 3. Remove member
        elseif ($action === 'remove_member') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            if ($targetUserId > 0) {
                $ok = $orgService->removeMember($orgId, $targetUserId);
                if ($ok) {
                    $message = 'همکار با موفقیت از کادر این مرکز منفصل گردید.';
                    $messageType = 'success';
                }
            }
        }

        // 4. Toggle member status
        elseif ($action === 'toggle_status') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newStatus    = trim($_POST['status'] ?? 'active');
            if ($targetUserId > 0 && in_array($newStatus, ['active', 'inactive'])) {
                $orgService->updateMemberStatus($orgId, $targetUserId, $newStatus);
                $message = 'وضعیت فعالیت همکار به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch all members of this organization
$members = $orgService->getMembers($orgId);

// Role Counts
$countTotal = count($members);
$countPharma = count(array_filter($members, fn($m) => in_array($m['member_role'], ['pharmacologist', 'pharmacist'])));
$countDocs = count(array_filter($members, fn($m) => $m['member_role'] === 'doctor'));
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
                <span>مدیریت پرسنل، داروسازان و کادر مرکز</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">انتساب افراد و متخصصان به مرکز درمانی، اشتراک‌گذاری خودکار انبار دارویی با فارماکولوژیست‌ها و تنظیم دسترسی‌ها</p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('assign-existing-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">person_add</span>
                <span>انتساب کاربر موجود به مرکز</span>
            </button>
            <button onclick="document.getElementById('create-new-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base text-teal-600">person_add_alt</span>
                <span>تعریف سریع پرسنل جدید</span>
            </button>
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

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">groups</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">کل کادر و پرسنل مرکز</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($countTotal) ?> نفر</h3>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">medication</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">داروسازان و فارماکولوژیست‌ها</p>
                <h3 class="text-xl font-black text-purple-700 mt-0.5"><?= number_format($countPharma) ?> متخصص</h3>
                <span class="text-[10px] text-slate-400">انبار مشترک فعال</span>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">stethoscope</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">دامپزشکان و جراحان</p>
                <h3 class="text-xl font-black text-emerald-700 mt-0.5"><?= number_format($countDocs) ?> پزشک</h3>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">manage_accounts</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">مدیریت داخلی و پذیرش</p>
                <h3 class="text-xl font-black text-sky-700 mt-0.5"><?= number_format($countManagers) ?> مدیر / پذیرش</h3>
            </div>
        </div>
    </div>

    <!-- Modal 1: Assign Existing User -->
    <div id="assign-existing-modal" class="hidden bg-white p-6 rounded-3xl border border-indigo-200 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-indigo-950 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">person_add</span>
                <span>انتساب کاربر ثبت‌نام شده در آسنا به کادر این مرکز</span>
            </h2>
            <button type="button" onclick="document.getElementById('assign-existing-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="members.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="assign_user">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل کاربر در آسنا *</label>
                <input type="tel" name="phone" required placeholder="09123456789" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نقش و مسئولیت در این مرکز *</label>
                <select name="member_role" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                    <option value="pharmacologist">دکتر فارماکولوژیست (انبار و مشاوره دارویی)</option>
                    <option value="pharmacist">مسئول فنی داروخانه مرکز</option>
                    <option value="doctor">دامپزشک و متخصص درمان</option>
                    <option value="assistant_manager">معاون اجرایی و مدیر داخلی کلینیک</option>
                    <option value="receptionist">سرپرست پذیرش و تریاژ مراجعین</option>
                    <option value="groomer">گرومر و آرایشگر حیوانات خانگی</option>
                    <option value="staff">پرسنل عمومی و خدمات درمانی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان سازمانی / سمت شغلی</label>
                <input type="text" name="job_title" placeholder="مثال: مسئول انبار دارویی و داروخانه شبانه‌روزی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شیفت حضور یا یادداشت داخلی</label>
                <input type="text" name="notes" placeholder="مثال: شیفت عصر روزهای فرد ۱۶:۰۰ الی ۲۲:۰۰" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('assign-existing-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                    انتساب به مرکز
                </button>
            </div>
        </form>
    </div>

    <!-- Modal 2: Create New User & Assign -->
    <div id="create-new-modal" class="hidden bg-white p-6 rounded-3xl border border-teal-200 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-teal-950 flex items-center gap-2">
                <span class="material-symbols-outlined text-teal-600">person_add_alt</span>
                <span>تعریف حساب کاربری جدید و انتساب مستقیم به مرکز</span>
            </h2>
            <button type="button" onclick="document.getElementById('create-new-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="members.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_and_assign">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی *</label>
                <input type="text" name="name" required placeholder="مثال: دکتر مهسا راد" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن همراه (ورود به سامانه) *</label>
                <input type="tel" name="phone" required placeholder="09120000000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">رمز عبور اولیه (حداقل ۶ کاراکتر) *</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نقش و مسئولیت در مرکز *</label>
                <select name="member_role" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                    <option value="pharmacologist">دکتر فارماکولوژیست (انبار دارویی و فارماکولوژی)</option>
                    <option value="pharmacist">مسئول فنی داروخانه</option>
                    <option value="doctor">پزشک معالج و جراح</option>
                    <option value="assistant_manager">معاون اجرایی و مدیر داخلی کلینیک</option>
                    <option value="receptionist">سرپرست پذیرش و تریاژ مراجعین</option>
                    <option value="groomer">گرومر و آرایشگر حیوانات خانگی</option>
                    <option value="staff">پرسنل عمومی</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">سمت شغلی / عنوان سازمانی</label>
                <input type="text" name="job_title" placeholder="مثال: کارشناس ارشد فارماکولوژی و داروخانه کلینیک" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شیفت کاری یا توضیحات تکمیلی</label>
                <input type="text" name="notes" placeholder="روزهای کاری، ساعات حضور و توضیحات تکمیلی..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-new-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-sm transition-all">
                    ایجاد حساب و انتساب به مرکز
                </button>
            </div>
        </form>
    </div>

    <!-- Members Table / Cards -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">badge</span>
                <span>فهرست پرسنل و متخصصان منتسب به مرکز</span>
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
                        <th class="p-4">سمت و شیفت</th>
                        <th class="p-4">اطلاعات تماس</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">group_off</span>
                                هنوز هیچ فردی به کادر این مرکز منتسب نشده است. از دکمه‌های بالا برای افزودن فارماکولوژیست، داروساز، پزشک یا پرسنل استفاده نمایید.
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
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">کاربر #<?= (int)$m['user_id'] ?></div>
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
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($m['job_title'] ?: 'عضو کادر مرکز') ?></div>
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
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if (in_array($m['member_role'], ['pharmacologist', 'pharmacist'])): ?>
                                        <a href="inventory.php" class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 text-[10px] font-bold flex items-center gap-1 transition-all" title="مشاهده انبار این داروساز">
                                            <span class="material-symbols-outlined text-xs">inventory_2</span>
                                            <span>انبار دارویی</span>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Toggle Active -->
                                    <form method="POST" action="members.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors">
                                            <?= $isActive ? 'غیرفعال‌سازی' : 'فعال‌سازی' ?>
                                        </button>
                                    </form>

                                    <!-- Remove Member -->
                                    <form method="POST" action="members.php" class="inline m-0" onsubmit="return confirm('آیا از حذف این همکار از کادر مرکز اطمینان دارید؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
                                        <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition-colors" title="حذف از کادر مرکز">
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

<?php require_once 'includes/organization_footer.php'; ?>
