<?php
require_once 'includes/organization_header.php';

// Feature Guard
if (!Feature::has('organization_subadmins')) {
    echo '<div class="p-8 text-center text-slate-500 font-bold">این قابلیت نیازمند پلن اینترپرایز آسنا می‌باشد.</div>';
    require_once 'includes/organization_footer.php';
    exit;
}

$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Check permission: Owner, Super Admin, or user with manage_admins permission
$isOwner = (!empty($currentOrg['user_id']) && (int)$currentOrg['user_id'] === (int)$currentUser['id']) || ($currentUser['role'] === 'admin');
$canManageAdmins = $isOwner || hasOrgPermission('manage_admins', $currentAdminPermissions);

// Predefined Roles Definition
$availableRoles = [
    'assistant_manager' => [
        'title' => 'معاون اجرایی مرکز',
        'desc' => 'دسترسی گسترده به مدیریت نوبت‌ها، پزشکان، شیفت‌ها، انبار و پروفایل',
        'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'icon' => 'badge',
        'default_perms' => ['manage_profile', 'manage_appointments', 'manage_doctors', 'manage_shifts', 'manage_inventory', 'manage_orders', 'manage_tickets']
    ],
    'supervisor' => [
        'title' => 'سرپرست پذیرش و مراجعین',
        'desc' => 'مدیریت رزروها، تقویم کاری، زمان‌بندی و پذیرش حضوری',
        'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
        'icon' => 'calendar_month',
        'default_perms' => ['manage_appointments', 'manage_doctors', 'manage_shifts']
    ],
    'accountant' => [
        'title' => 'مدیر مالی و حسابدار',
        'desc' => 'دسترسی اختصاصی به گزارش‌های مالی، فاکتورها، سفارشات و تسویه پایا',
        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'icon' => 'account_balance_wallet',
        'default_perms' => ['manage_wallet', 'manage_orders']
    ],
    'operator' => [
        'title' => 'اپراتور شیفت و پذیرش',
        'desc' => 'ثبت و بررسی سریع نوبت‌ها و استعلام وضعیت مراجعین',
        'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
        'icon' => 'support_agent',
        'default_perms' => ['manage_appointments']
    ],
    'pharmacist' => [
        'title' => 'مسئول داروخانه و انبار',
        'desc' => 'مدیریت موجودی داروها، ثبت اقلام و پردازش سفارشات دارویی',
        'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
        'icon' => 'medication',
        'default_perms' => ['manage_inventory', 'manage_orders']
    ],
    'custom' => [
        'title' => 'دسترسی سفارشی',
        'desc' => 'انتخاب آزادانه دسترسی‌ها متناسب با شرح وظایف پرسنل',
        'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
        'icon' => 'tune',
        'default_perms' => ['manage_appointments']
    ]
];

$allPermissions = [
    'manage_appointments' => ['title' => 'مدیریت نوبت‌ها و مراجعین', 'desc' => 'رزرو، لغو، تغییر زمان و مشاهده لیست بیماران', 'icon' => 'calendar_month'],
    'manage_doctors'      => ['title' => 'پزشکان و کادر درمان', 'desc' => 'افزودن و انتساب دامپزشکان، گرومرها و پرسنل به مرکز', 'icon' => 'stethoscope'],
    'manage_shifts'       => ['title' => 'تقویم کاری و شیفت‌ها', 'desc' => 'تعریف ساعات حضور، روزهای کاری و ظرفیت نوبت‌دهی', 'icon' => 'schedule'],
    'manage_profile'      => ['title' => 'ویرایش پروفایل و گالری', 'desc' => 'تغییر بیوگرافی، تصاویر بنر، شماره‌های تماس و گالری کلینیک', 'icon' => 'storefront'],
    'manage_inventory'    => ['title' => 'داروخانه و موجودی کالا', 'desc' => 'ثبت داروها، اقلام پت‌شاپ و انبارداری مرکز', 'icon' => 'medication'],
    'manage_orders'       => ['title' => 'سفارشات فروشگاه', 'desc' => 'پیگیری و آماده‌سازی سفارشات ارسالی مراجعین', 'icon' => 'local_shipping'],
    'manage_wallet'       => ['title' => 'امور مالی و تسویه پایا', 'desc' => 'مشاهده درآمدها، گردش حساب و درخواست برداشت وجه', 'icon' => 'account_balance_wallet'],
    'manage_tickets'      => ['title' => 'تیکت‌های پشتیبانی', 'desc' => 'مکاتبه رسمی با مدیریت کلان و پیگیری درخواست‌ها', 'icon' => 'support'],
    'manage_admins'       => ['title' => 'مدیریت سایر ادمین‌ها', 'desc' => 'تعریف حساب‌های جدید و تغییر وضعیت مدیران همکار', 'icon' => 'manage_accounts'],
];

// Handle Actions (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    csrf_verify();

    if (!$canManageAdmins) {
        $message = 'شما مجوز لازم برای تغییر و مدیریت حساب‌های مدیران را ندارید.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'];

        // 1. ADD NEW SUB-ADMIN
        if ($action === 'add_admin') {
            $name        = trim($_POST['name'] ?? '');
            $phone       = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
            $email       = trim($_POST['email'] ?? '');
            $adminRole   = trim($_POST['admin_role'] ?? 'custom');
            $customTitle = trim($_POST['title'] ?? '');
            $password    = trim($_POST['password'] ?? '');
            $selectedPerms = $_POST['permissions'] ?? [];

            // Normalize Persian phone if starts with 98 or 09
            if (strlen($phone) === 10 && strpos($phone, '9') === 0) {
                $phone = '0' . $phone;
            } elseif (strlen($phone) === 12 && strpos($phone, '98') === 0) {
                $phone = '0' . substr($phone, 2);
            }

            if (empty($name) || mb_strlen($name) < 2) {
                $message = 'لطفاً نام و نام خانوادگی مدیر را به صورت معتبر وارد فرمایید.';
                $messageType = 'error';
            } elseif (strlen($phone) !== 11 || strpos($phone, '09') !== 0) {
                $message = 'لطفاً یک شماره تلفن همراه معتبر ۱۱ رقمی (مثلاً ۰۹۱۲۳۴۵۶۷۸۹) وارد فرمایید.';
                $messageType = 'error';
            } else {
                // Check if user already exists
                $chkUser = $pdo->prepare("SELECT id, name, role, password FROM users WHERE phone = ? LIMIT 1");
                $chkUser->execute([$phone]);
                $existingUser = $chkUser->fetch(PDO::FETCH_ASSOC);

                if ($existingUser) {
                    $targetUserId = (int)$existingUser['id'];

                    // Check if already an admin in this organization
                    $chkOrgAdmin = $pdo->prepare("SELECT id FROM organization_admins WHERE organization_id = ? AND user_id = ? LIMIT 1");
                    $chkOrgAdmin->execute([$orgId, $targetUserId]);
                    if ($chkOrgAdmin->fetch()) {
                        $message = 'این کاربر هم‌اکنون به عنوان مدیر در این مرکز تعریف شده است.';
                        $messageType = 'error';
                        goto end_action;
                    }

                    // If user exists and new password is supplied, update it; otherwise keep existing
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            $message = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
                            $messageType = 'error';
                            goto end_action;
                        }
                        $updPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updPass->execute([password_hash($password, PASSWORD_DEFAULT), $targetUserId]);
                    }

                    // If user is simple user, promote role to organization so they can access panel
                    if ($existingUser['role'] === 'user') {
                        $updRole = $pdo->prepare("UPDATE users SET role = 'organization' WHERE id = ?");
                        $updRole->execute([$targetUserId]);
                    }
                } else {
                    // Create new user
                    if (empty($password) || strlen($password) < 6) {
                        $message = 'جهت ایجاد حساب کاربری برای کاربر جدید، تعیین رمز عبور با حداقل ۶ کاراکتر الزامی است.';
                        $messageType = 'error';
                        goto end_action;
                    }

                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $pdo->prepare("
                        INSERT INTO users (phone, name, email, password, role, verification_status, created_at)
                        VALUES (?, ?, ?, ?, 'organization', 'approved', NOW())
                    ");
                    $insUser->execute([$phone, $name, $email ?: null, $passHash]);
                    $targetUserId = (int)$pdo->lastInsertId();
                }

                // Prepare permissions
                $validPermKeys = array_keys($allPermissions);
                $finalPerms = array_values(array_intersect($selectedPerms, $validPermKeys));
                if (empty($finalPerms)) {
                    $finalPerms = $availableRoles[$adminRole]['default_perms'] ?? ['manage_appointments'];
                }

                $titleToUse = $customTitle ?: ($availableRoles[$adminRole]['title'] ?? 'مدیر همکار');

                $insAdmin = $pdo->prepare("
                    INSERT INTO organization_admins (organization_id, user_id, admin_role, title, permissions_json, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
                ");
                $insAdmin->execute([
                    $orgId,
                    $targetUserId,
                    $adminRole,
                    $titleToUse,
                    json_encode($finalPerms, JSON_UNESCAPED_UNICODE),
                    $currentUser['id']
                ]);

                $message = "حساب مدیر همکار «{$name}» با موفقیت فعال و به این مرکز متصل گردید. کاربر با شماره همراه وارد پنل خواهد شد.";
                $messageType = 'success';
            }
        }

        // 2. TOGGLE STATUS
        elseif ($action === 'toggle_status') {
            $adminId = (int)($_POST['admin_id'] ?? 0);
            $chkAdmin = $pdo->prepare("SELECT * FROM organization_admins WHERE id = ? AND organization_id = ? LIMIT 1");
            $chkAdmin->execute([$adminId, $orgId]);
            $adminRow = $chkAdmin->fetch(PDO::FETCH_ASSOC);

            if (!$adminRow) {
                $message = 'رکورد مدیر مورد نظر یافت نشد.';
                $messageType = 'error';
            } elseif ((int)$adminRow['user_id'] === (int)$currentOrg['user_id'] || $adminRow['admin_role'] === 'owner') {
                $message = 'امکان غیرفعال‌سازی یا تعلیق حساب مالک و موسس اصلی مرکز وجود ندارد.';
                $messageType = 'error';
            } else {
                $newStatus = ($adminRow['status'] === 'active') ? 'inactive' : 'active';
                $updStatus = $pdo->prepare("UPDATE organization_admins SET status = ? WHERE id = ?");
                $updStatus->execute([$newStatus, $adminId]);

                $statusLabel = ($newStatus === 'active') ? 'فعال و مجاز' : 'غیرفعال و مسدود';
                $message = "وضعیت دسترسی مدیر با موفقیت به «{$statusLabel}» تغییر یافت.";
                $messageType = 'success';
            }
        }

        // 3. EDIT ADMIN PERMISSIONS
        elseif ($action === 'update_admin') {
            $adminId     = (int)($_POST['admin_id'] ?? 0);
            $customTitle = trim($_POST['title'] ?? '');
            $adminRole   = trim($_POST['admin_role'] ?? 'custom');
            $selectedPerms = $_POST['permissions'] ?? [];

            $chkAdmin = $pdo->prepare("SELECT * FROM organization_admins WHERE id = ? AND organization_id = ? LIMIT 1");
            $chkAdmin->execute([$adminId, $orgId]);
            $adminRow = $chkAdmin->fetch(PDO::FETCH_ASSOC);

            if (!$adminRow) {
                $message = 'رکورد مدیر مورد نظر یافت نشد.';
                $messageType = 'error';
            } else {
                $validPermKeys = array_keys($allPermissions);
                $finalPerms = array_values(array_intersect($selectedPerms, $validPermKeys));
                
                // If owner, preserve 'all'
                if ($adminRow['admin_role'] === 'owner' || (int)$adminRow['user_id'] === (int)$currentOrg['user_id']) {
                    $finalPerms = ['all'];
                    $adminRole = 'owner';
                }

                $titleToUse = $customTitle ?: ($availableRoles[$adminRole]['title'] ?? $adminRow['title']);

                $upd = $pdo->prepare("
                    UPDATE organization_admins 
                    SET admin_role = ?, title = ?, permissions_json = ?, updated_at = NOW()
                    WHERE id = ? AND organization_id = ?
                ");
                $upd->execute([$adminRole, $titleToUse, json_encode($finalPerms, JSON_UNESCAPED_UNICODE), $adminId, $orgId]);

                $message = 'سطوح دسترسی و مشخصات مدیر با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }

        // 4. DELETE ADMIN
        elseif ($action === 'delete_admin') {
            $adminId = (int)($_POST['admin_id'] ?? 0);
            $chkAdmin = $pdo->prepare("SELECT * FROM organization_admins WHERE id = ? AND organization_id = ? LIMIT 1");
            $chkAdmin->execute([$adminId, $orgId]);
            $adminRow = $chkAdmin->fetch(PDO::FETCH_ASSOC);

            if (!$adminRow) {
                $message = 'رکورد مدیر یافت نشد.';
                $messageType = 'error';
            } elseif ((int)$adminRow['user_id'] === (int)$currentOrg['user_id'] || $adminRow['admin_role'] === 'owner') {
                $message = 'امکان حذف حساب مالک اصلی مرکز وجود ندارد.';
                $messageType = 'error';
            } else {
                $del = $pdo->prepare("DELETE FROM organization_admins WHERE id = ? AND organization_id = ?");
                $del->execute([$adminId, $orgId]);

                $message = 'دسترسی مدیر همکار با موفقیت از این مرکز حذف گردید.';
                $messageType = 'success';
            }
        }
    }
}
end_action:

// Fetch all admins linked to this organization
$adminsQuery = $pdo->prepare("
    SELECT oa.*, u.name as user_name, u.phone as user_phone, u.email as user_email, u.created_at as user_created_at
    FROM organization_admins oa
    JOIN users u ON u.id = oa.user_id
    WHERE oa.organization_id = ?
    ORDER BY (oa.admin_role = 'owner') DESC, oa.id ASC
");
$adminsQuery->execute([$orgId]);
$admins = $adminsQuery->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$totalAdmins = count($admins);
$activeAdminsCount = 0;
$subAdminsCount = 0;
foreach ($admins as $adm) {
    if ($adm['status'] === 'active') $activeAdminsCount++;
    if ($adm['admin_role'] !== 'owner') $subAdminsCount++;
}
?>

<div class="p-4 lg:p-8 max-w-7xl mx-auto space-y-6">

    <!-- Flash Message Notification -->
    <?php if (!empty($message)): ?>
    <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?> shadow-sm animate-fade-in">
        <span class="material-symbols-outlined text-2xl"><?= $messageType === 'success' ? 'check_circle' : 'error' ?></span>
        <div class="text-xs lg:text-sm font-bold flex-1 leading-relaxed"><?= htmlspecialchars($message) ?></div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Top Hero Header -->
    <div class="bg-gradient-to-l from-primary via-[#0a275e] to-primary p-6 lg:p-8 rounded-3xl text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="relative z-10 space-y-2 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm text-secondary-container text-xs font-black">
                <span class="material-symbols-outlined text-sm">security</span>
                <span>سیستم مدیریت سطوح دسترسی چندکاربره (RBAC)</span>
            </div>
            <h1 class="text-xl lg:text-3xl font-black tracking-tight">مدیران و دسترسی‌های مرکز</h1>
            <p class="text-xs lg:text-sm text-slate-200 leading-relaxed font-medium">
                شما می‌توانید برای کادر اداری، پذیرش، داروخانه و حسابداری مرکز خود، حساب‌های مجزا تعریف نموده و سطح دسترسی هر کدام را به صورت دقیق و اختصاصی محدود یا تفویض نمایید.
            </p>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-3 shrink-0">
            <?php if ($canManageAdmins): ?>
            <button onclick="openAddAdminModal()" class="px-5 py-3 rounded-2xl bg-secondary-container hover:bg-amber-500 text-white font-black text-xs lg:text-sm shadow-lg shadow-amber-900/20 flex items-center gap-2 transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">person_add</span>
                <span>+ افزودن مدیر همکار جدید</span>
            </button>
            <?php endif; ?>
            <a href="index.php" class="px-4 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs lg:text-sm transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
                <span>پیشخوان</span>
            </a>
        </div>

        <!-- Decorative background shapes -->
        <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-blue-500/10 blur-2xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-12 w-48 h-48 rounded-full bg-amber-500/10 blur-xl pointer-events-none"></div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Stat 1 -->
        <div class="bg-white rounded-2xl p-4 lg:p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">manage_accounts</span>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-bold">کل کادر مدیریت مرکز</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl lg:text-2xl font-black text-slate-900 font-mono"><?= $totalAdmins ?></span>
                    <span class="text-[10px] text-slate-500 font-bold">نفر</span>
                </div>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="bg-white rounded-2xl p-4 lg:p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">verified_user</span>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-bold">حساب‌های فعال و مجاز</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl lg:text-2xl font-black text-emerald-600 font-mono"><?= $activeAdminsCount ?></span>
                    <span class="text-[10px] text-slate-500 font-bold">اکتیو</span>
                </div>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="bg-white rounded-2xl p-4 lg:p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">supervisor_account</span>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-bold">مدیران همکار و اپراتورها</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl lg:text-2xl font-black text-amber-600 font-mono"><?= $subAdminsCount ?></span>
                    <span class="text-[10px] text-slate-500 font-bold">همکار</span>
                </div>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="bg-white rounded-2xl p-4 lg:p-5 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">lock</span>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-bold">امنیت و ایزولاسیون مرکز</p>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-sm font-black text-sky-700">تضمین‌شده (PDO)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Admin Overview Guide Card -->
    <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-4 lg:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">info</span>
            </div>
            <div class="space-y-0.5">
                <p class="text-xs font-black text-slate-800">راهنمای ورود مدیران همکار به پنل مرکز:</p>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    پرسنل و مدیران اضافه شده، با مراجعه به صفحه ورود اصلی (<span class="font-mono text-primary font-bold">login.php</span>) با وارد کردن شماره همراه و رمز عبور تعیین‌شده، به طور خودکار به این پنل هدایت می‌شوند.
                </p>
            </div>
        </div>
        <div class="text-[11px] font-bold text-slate-500 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shrink-0">
            شناسه مرکز: <span class="font-mono font-black text-slate-800">#<?= $orgId ?></span>
        </div>
    </div>

    <!-- Admins Table Section -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base lg:text-lg font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">group</span>
                    <span>فهرست مدیران و اعضای دارای دسترسی به پنل</span>
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">مشاهده اختیارات تفویض شده، شماره تماس و تغییر وضعیت حساب‌ها</p>
            </div>
            <span class="text-xs font-bold bg-slate-100 text-slate-700 px-3 py-1 rounded-full w-fit">
                تعداد کل: <?= count($admins) ?> نفر
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 text-[11px] font-black uppercase tracking-wider border-b border-slate-200/80">
                        <th class="py-3.5 px-4 lg:px-6">مشخصات کاربر</th>
                        <th class="py-3.5 px-4">نقش و عنوان سازمانی</th>
                        <th class="py-3.5 px-4">دسترسـی‌های فعال (RBAC)</th>
                        <th class="py-3.5 px-4 text-center">وضعیت دسترسی</th>
                        <th class="py-3.5 px-4 text-center">تاریخ انتساب</th>
                        <th class="py-3.5 px-4 lg:px-6 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <span class="material-symbols-outlined text-4xl block mb-2 opacity-50">person_off</span>
                            هنوز مدیری برای این مرکز ثبت نشده است.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($admins as $admin):
                            $isRowOwner = ($admin['admin_role'] === 'owner') || ((int)$admin['user_id'] === (int)$currentOrg['user_id']);
                            $roleMeta = $availableRoles[$admin['admin_role']] ?? null;
                            $perms = !empty($admin['permissions_json']) ? json_decode($admin['permissions_json'], true) : [];
                            $isFullAccess = in_array('all', $perms, true);
                        ?>
                        <tr class="hover:bg-slate-50/60 transition-colors <?= $admin['status'] === 'inactive' ? 'opacity-60 bg-slate-50/30' : '' ?>">
                            <!-- User Details -->
                            <td class="py-4 px-4 lg:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl <?= $isRowOwner ? 'bg-gradient-to-tr from-primary to-blue-700 text-white' : 'bg-slate-100 text-slate-700' ?> flex items-center justify-center font-black text-sm shrink-0 shadow-sm">
                                        <?= mb_substr($admin['user_name'] ?? 'م', 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-slate-900 text-sm"><?= htmlspecialchars($admin['user_name']) ?></span>
                                            <?php if ($isRowOwner): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-200">
                                                    👑 موسس و مالک
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500 font-mono">
                                            <span class="material-symbols-outlined text-[13px] text-slate-400">call</span>
                                            <span><?= htmlspecialchars($admin['user_phone']) ?></span>
                                            <?php if (!empty($admin['user_email'])): ?>
                                                <span class="text-slate-300">|</span>
                                                <span class="font-sans text-[11px]"><?= htmlspecialchars($admin['user_email']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role and Title -->
                            <td class="py-4 px-4">
                                <div>
                                    <span class="font-bold text-slate-800 text-xs block">
                                        <?= htmlspecialchars($admin['title'] ?: ($roleMeta['title'] ?? 'مدیر مرکز')) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[10px] font-black <?= $isRowOwner ? 'bg-amber-50 text-amber-800' : ($roleMeta['badge'] ?? 'bg-slate-100 text-slate-700') ?>">
                                        <span class="material-symbols-outlined text-[12px]"><?= $isRowOwner ? 'workspace_premium' : ($roleMeta['icon'] ?? 'shield') ?></span>
                                        <span><?= $isRowOwner ? 'مالک ارشد مرکز' : ($roleMeta['title'] ?? 'مدیر همکار') ?></span>
                                    </span>
                                </div>
                            </td>

                            <!-- Permissions Badges -->
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    <?php if ($isFullAccess || $isRowOwner): ?>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]">all_inclusive</span>
                                            <span>دسترسی کامل (مدیر ارشد)</span>
                                        </span>
                                    <?php elseif (empty($perms)): ?>
                                        <span class="text-slate-400 text-[11px] italic">بدون دسترسی</span>
                                    <?php else: ?>
                                        <?php foreach ($perms as $pKey): 
                                            $pMeta = $allPermissions[$pKey] ?? null;
                                            if (!$pMeta) continue;
                                        ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors flex items-center gap-1" title="<?= htmlspecialchars($pMeta['desc']) ?>">
                                                <span class="material-symbols-outlined text-[11px]"><?= $pMeta['icon'] ?></span>
                                                <span><?= htmlspecialchars($pMeta['title']) ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4 text-center">
                                <?php if ($admin['status'] === 'active'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>فعال</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>غیرفعال</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Created Date -->
                            <td class="py-4 px-4 text-center text-[11px] font-mono text-slate-500">
                                <?= substr($admin['created_at'], 0, 10) ?>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 lg:px-6 text-center">
                                <?php if ($isRowOwner): ?>
                                    <span class="text-[11px] font-bold text-slate-400 italic">حساب اصلی مالک</span>
                                <?php elseif ($canManageAdmins): ?>
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Edit Permissions Button -->
                                        <button 
                                            onclick='openEditAdminModal(<?= json_encode($admin, JSON_UNESCAPED_UNICODE) ?>)'
                                            class="w-8 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 flex items-center justify-center transition-colors"
                                            title="ویرایش دسترسی‌ها و عنوان">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </button>

                                        <!-- Toggle Status Button -->
                                        <form method="POST" action="admins.php" class="inline" onsubmit="return confirm('آیا از تغییر وضعیت دسترسی این حساب اطمینان دارید؟')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>">
                                            <button type="submit" 
                                                class="w-8 h-8 rounded-lg <?= $admin['status'] === 'active' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' ?> flex items-center justify-center transition-colors"
                                                title="<?= $admin['status'] === 'active' ? 'تعلیق و غیرفعال‌سازی' : 'فعال‌سازی مجدد' ?>">
                                                <span class="material-symbols-outlined text-base"><?= $admin['status'] === 'active' ? 'block' : 'check_circle' ?></span>
                                            </button>
                                        </form>

                                        <!-- Delete Button -->
                                        <form method="POST" action="admins.php" class="inline" onsubmit="return confirm('آیا از حذف دسترسی این مدیر همکار از مرکز اطمینان کامل دارید؟')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-center transition-colors" title="حذف دسترسی">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= ADD SUB-ADMIN MODAL ================= -->
<div id="addAdminModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-100 animate-scale-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-secondary-container/10 text-secondary-container flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-2xl">person_add</span>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base">افزودن مدیر همکار جدید به مرکز</h3>
                    <p class="text-[11px] text-slate-500 font-medium">ایجاد حساب کاربری، تفویض اختیارات و تعیین سطوح دسترسی</p>
                </div>
            </div>
            <button onclick="closeAddAdminModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form method="POST" action="admins.php" class="p-6 space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_admin">

            <!-- Personal Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        نام و نام خانوادگی مدیر <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="مثال: رضا محمدی"
                           class="w-full text-xs font-bold px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        شماره تلفن همراه (نام کاربری جهت ورود) <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" name="phone" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr"
                           class="w-full text-xs font-mono font-bold px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/10 outline-none transition-all text-left">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        ایمیل سازمانی (اختیاری)
                    </label>
                    <input type="email" name="email" placeholder="staff@example.com" dir="ltr"
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/10 outline-none transition-all text-left">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        رمز عبور حساب کاربری <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="password" required minlength="6" placeholder="حداقل ۶ کاراکتر" dir="ltr"
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/10 outline-none transition-all text-left">
                </div>
            </div>

            <!-- Role Selector Preset -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <label class="block text-xs font-black text-slate-800">
                    انتخاب قالب و نقش از پیش‌تعریف‌شده (Preset Role):
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <?php foreach ($availableRoles as $roleKey => $roleInfo): ?>
                    <label class="flex flex-col p-3 rounded-xl border border-slate-200 bg-white hover:border-primary cursor-pointer transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                        <div class="flex items-center justify-between mb-1">
                            <span class="material-symbols-outlined text-lg text-primary"><?= $roleInfo['icon'] ?></span>
                            <input type="radio" name="admin_role" value="<?= $roleKey ?>" 
                                   <?= $roleKey === 'assistant_manager' ? 'checked' : '' ?>
                                   onchange="applyRolePreset('<?= $roleKey ?>')"
                                   class="text-primary focus:ring-0">
                        </div>
                        <span class="text-xs font-black text-slate-800"><?= $roleInfo['title'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        عنوان سازمانی نمایشی (اختیاری)
                    </label>
                    <input type="text" id="add_admin_title" name="title" placeholder="مثال: معاون اجرایی و سرپرست شیفت عصر"
                           class="w-full text-xs font-bold px-3 py-2 rounded-xl border border-slate-300 focus:border-primary outline-none">
                </div>
            </div>

            <!-- Permissions Matrix -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">checklist</span>
                        <span>ماتریس اختیارات و دسترسی‌ها (RBAC Permissions)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="selectAllPerms(true)" class="text-[11px] text-primary font-bold hover:underline">انتخاب همه</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="selectAllPerms(false)" class="text-[11px] text-slate-500 font-bold hover:underline">لغو انتخاب</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" id="addAdminPermsContainer">
                    <?php foreach ($allPermissions as $permKey => $permInfo): ?>
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50/80 cursor-pointer transition-colors">
                        <input type="checkbox" name="permissions[]" value="<?= $permKey ?>" 
                               id="perm_<?= $permKey ?>"
                               class="perm-checkbox mt-0.5 rounded text-primary focus:ring-0">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-800 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-slate-500"><?= $permInfo['icon'] ?></span>
                                <span><?= $permInfo['title'] ?></span>
                            </span>
                            <p class="text-[10px] text-slate-500 leading-tight"><?= $permInfo['desc'] ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeAddAdminModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#002d72] text-white font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">check</span>
                    <span>ایجاد حساب و اعطای دسترسی</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT SUB-ADMIN MODAL ================= -->
<div id="editAdminModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto border border-slate-100 animate-scale-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-2xl">edit_note</span>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base" id="edit_modal_user_name">ویرایش دسترسی‌های مدیر</h3>
                    <p class="text-[11px] text-slate-500 font-medium" id="edit_modal_user_phone">شماره همراه: -</p>
                </div>
            </div>
            <button onclick="closeEditAdminModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form method="POST" action="admins.php" class="p-6 space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_admin">
            <input type="hidden" name="admin_id" id="edit_admin_id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نقش سازمانی</label>
                    <select name="admin_role" id="edit_admin_role" class="w-full text-xs font-bold px-3 py-2.5 rounded-xl border border-slate-300 focus:border-primary outline-none">
                        <?php foreach ($availableRoles as $rk => $rv): ?>
                        <option value="<?= $rk ?>"><?= $rv['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان شغلی نمایشی</label>
                    <input type="text" name="title" id="edit_admin_title" class="w-full text-xs font-bold px-3 py-2 rounded-xl border border-slate-300 focus:border-primary outline-none">
                </div>
            </div>

            <!-- Edit Permissions Matrix -->
            <div class="space-y-3">
                <label class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-base">tune</span>
                    <span>انتخاب دسترسی‌های اختصاصی:</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <?php foreach ($allPermissions as $permKey => $permInfo): ?>
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50/80 cursor-pointer transition-colors">
                        <input type="checkbox" name="permissions[]" value="<?= $permKey ?>" 
                               id="edit_perm_<?= $permKey ?>"
                               class="edit-perm-checkbox mt-0.5 rounded text-primary focus:ring-0">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-800 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-slate-500"><?= $permInfo['icon'] ?></span>
                                <span><?= $permInfo['title'] ?></span>
                            </span>
                            <p class="text-[10px] text-slate-500 leading-tight"><?= $permInfo['desc'] ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditAdminModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#002d72] text-white font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>ذخیره تغییرات</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const rolePresets = <?= json_encode($availableRoles, JSON_UNESCAPED_UNICODE) ?>;

function applyRolePreset(roleKey) {
    const preset = rolePresets[roleKey];
    if (!preset) return;
    
    // Update default title
    const titleInput = document.getElementById('add_admin_title');
    if (titleInput && (!titleInput.value || Object.values(rolePresets).some(p => p.title === titleInput.value))) {
        titleInput.value = preset.title;
    }

    // Check defaults
    const defaultPerms = preset.default_perms || [];
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = defaultPerms.includes(cb.value);
    });
}

function selectAllPerms(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = checked;
    });
}

function openAddAdminModal() {
    document.getElementById('addAdminModal').classList.remove('hidden');
    applyRolePreset('assistant_manager');
    document.body.style.overflow = 'hidden';
}

function closeAddAdminModal() {
    document.getElementById('addAdminModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function openEditAdminModal(admin) {
    document.getElementById('edit_admin_id').value = admin.id;
    document.getElementById('edit_modal_user_name').textContent = 'ویرایش دسترسی: ' + admin.user_name;
    document.getElementById('edit_modal_user_phone').textContent = 'شماره همراه: ' + admin.user_phone;
    document.getElementById('edit_admin_role').value = admin.admin_role;
    document.getElementById('edit_admin_title').value = admin.title || '';

    let perms = [];
    try {
        perms = JSON.parse(admin.permissions_json || '[]');
    } catch(e) {}

    const isFull = perms.includes('all');
    document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
        cb.checked = isFull || perms.includes(cb.value);
    });

    document.getElementById('editAdminModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditAdminModal() {
    document.getElementById('editAdminModal').classList.add('hidden');
    document.body.style.overflow = '';
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
