<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Organization managers and administrators
$currentUser = AuthGuard::requireRole(['organization', 'admin'], $pdo);

// Find organization managed by this user (check owner first)
$orgStmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ? OR manager_name = ? OR email = ? LIMIT 1");
$orgStmt->execute([$currentUser['id'], $currentUser['name'], $currentUser['email'] ?? '']);
$currentOrg = $orgStmt->fetch(PDO::FETCH_ASSOC);

$currentAdminRole = 'owner';
$currentAdminTitle = 'مدیر ارشد و موسس مرکز';
$currentAdminPermissions = ['all'];

if ($currentOrg) {
    // Ensure primary owner exists in organization_admins table
    if (!empty($currentOrg['user_id'])) {
        try {
            $insOwner = $pdo->prepare("
                INSERT IGNORE INTO organization_admins (organization_id, user_id, admin_role, title, permissions_json, status, created_by, created_at)
                VALUES (?, ?, 'owner', 'مدیر ارشد و موسس', '[\"all\"]', 'active', ?, NOW())
            ");
            $insOwner->execute([$currentOrg['id'], $currentOrg['user_id'], $currentOrg['user_id']]);
        } catch (Throwable $e) {
            // Ignore if already exists or constraint
        }
    }
    
    // Check if customized admin role exists
    try {
        $checkAdmin = $pdo->prepare("SELECT * FROM organization_admins WHERE organization_id = ? AND user_id = ? LIMIT 1");
        $checkAdmin->execute([$currentOrg['id'], $currentUser['id']]);
        $adminRow = $checkAdmin->fetch(PDO::FETCH_ASSOC);
        if ($adminRow) {
            $currentAdminRole = $adminRow['admin_role'];
            $currentAdminTitle = $adminRow['title'] ?: ($adminRow['admin_role'] === 'owner' ? 'مدیر ارشد و موسس' : 'مدیر مرکز');
            $currentAdminPermissions = !empty($adminRow['permissions_json']) ? json_decode($adminRow['permissions_json'], true) : ['all'];
        }
    } catch (Throwable $e) {
        // Fallback to default owner permissions if table or query fails
    }
} else {
    // Check if user is an active sub-admin in organization_admins
    try {
        $subAdminStmt = $pdo->prepare("
            SELECT o.*, oa.admin_role, oa.title as staff_title, oa.permissions_json, oa.status as staff_status
            FROM organization_admins oa
            JOIN organizations o ON o.id = oa.organization_id
            WHERE oa.user_id = ? AND oa.status = 'active'
            LIMIT 1
        ");
        $subAdminStmt->execute([$currentUser['id']]);
        $subAdminRow = $subAdminStmt->fetch(PDO::FETCH_ASSOC);
        if ($subAdminRow) {
            $currentOrg = $subAdminRow;
            $currentAdminRole = $subAdminRow['admin_role'];
            $currentAdminTitle = $subAdminRow['staff_title'] ?: 'مدیر همکار مرکز';
            $currentAdminPermissions = !empty($subAdminRow['permissions_json']) ? json_decode($subAdminRow['permissions_json'], true) : [];
        }
    } catch (Throwable $e) {
        // Graceful fallback if table is not available
    }
}

// If admin or newly assigned without linked row, default to first organization
if (!$currentOrg && $currentUser['role'] === 'admin') {
    $currentOrg = $pdo->query("SELECT * FROM organizations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $currentAdminRole = 'super_admin';
    $currentAdminTitle = 'مدیر کل سامانه (Super Admin)';
    $currentAdminPermissions = ['all'];
}

if (!$currentOrg) {
    // If user has organization role but no organization record exists yet, create one
    $slug = 'org-' . $currentUser['id'] . '-' . time();
    $ins = $pdo->prepare("
        INSERT INTO organizations (user_id, name, slug, type, manager_name, phone, city, status, created_at)
        VALUES (?, ?, ?, 'clinic', ?, ?, 'تهران', 'approved', NOW())
    ");
    $ins->execute([$currentUser['id'], $currentUser['name'], $slug, $currentUser['name'], $currentUser['phone'] ?? '']);
    $newId = (int)$pdo->lastInsertId();
    $currentOrg = $pdo->query("SELECT * FROM organizations WHERE id = $newId")->fetch(PDO::FETCH_ASSOC);

    try {
        $pdo->prepare("
            INSERT IGNORE INTO organization_admins (organization_id, user_id, admin_role, title, permissions_json, status, created_by, created_at)
            VALUES (?, ?, 'owner', 'مدیر ارشد و موسس', '[\"all\"]', 'active', ?, NOW())
        ")->execute([$newId, $currentUser['id'], $currentUser['id']]);
    } catch (Throwable $e) {}
}

if (!function_exists('hasOrgPermission')) {
    function hasOrgPermission(string $perm, array $permissions): bool {
        if (in_array('all', $permissions, true)) {
            return true;
        }
        return in_array($perm, $permissions, true);
    }
}

$orgName = $currentOrg['name'] ?? 'مرکز درمانی';
$orgSlug = $currentOrg['slug'] ?? '';
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>مراکز</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "surface-variant": "#e2e2e2",
              "surface-container-high": "#e8e8e8",
              "secondary-container": "#fd8100",
              "tertiary": "#001f31",
              "on-primary-container": "#7a97e2",
              "on-tertiary-fixed": "#001e2f",
              "primary": "#001a48",
              "on-error": "#ffffff",
              "outline-variant": "#c4c6d2",
              "outline": "#747782",
              "primary-fixed-dim": "#b1c5ff",
              "tertiary-fixed": "#cae6ff",
              "surface-tint": "#3d5ca2",
              "surface-container-lowest": "#ffffff",
              "error": "#ba1a1a",
              "tertiary-container": "#133449",
              "surface": "#f9f9f9",
              "secondary": "#954a00",
              "primary-container": "#002d72",
              "on-surface-variant": "#444651",
              "on-surface": "#1a1c1c",
              "on-tertiary-container": "#7f9db6"
            }
          }
        }
      }
    </script>
    <style>
        body { font-family: 'Geist', sans-serif; }
        .stat-card-shadow { box-shadow: 0px 4px 12px rgba(0, 45, 114, 0.08); }
    </style>
    <script src="../assets/js/bidi-direction.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="bg-surface text-on-surface selection:bg-secondary-container/30">

<!-- Mobile Backdrop -->
<div id="org-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleOrgSidebar()"></div>

<!-- SideNavBar matching Doctor & Pharmacist exact styling -->
<aside id="org-sidebar" class="fixed inset-y-0 right-0 w-64 bg-tertiary flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3 group" title="مشاهده سایت">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight group-hover:text-secondary-container transition-colors">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">پنل مرکز درمانی</p>
                </div>
            </a>
            <button onclick="toggleOrgSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 text-tertiary-fixed text-xs font-medium mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>نسخه اینترپرایز جامع (فول اکوسیستم)</span>
        </div>

        <a href="appointments.php" class="w-full bg-gradient-to-r from-blue-600 to-primary hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-2.5 px-3 rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-xs my-2">
            <span class="material-symbols-outlined text-base">calendar_month</span>
            <span>+ ثبت و مدیریت نوبت‌ها</span>
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 mt-2 space-y-1">
        <?php
        $navItems = [
            'index.php'        => ['icon' => 'dashboard', 'title' => 'پیشخوان و مشخصات مرکز'],
            'appointments.php' => ['icon' => 'calendar_month', 'title' => 'نوبت‌دهی و مراجعین کلینیک'],
            'tickets.php'      => ['icon' => 'support_agent', 'title' => 'تیکت و پشتیبانی (مراجعین / مدیریت)'],
            'doctors.php'      => ['icon' => 'groups', 'title' => 'پزشکان، داروسازان و گرومرها'],
            'shifts.php'       => ['icon' => 'schedule', 'title' => 'مدیریت زمان و تقویم شیفت‌ها'],
            'orders.php'       => ['icon' => 'local_shipping', 'title' => 'سفارشات محصولات مرکز'],
            'subscriptions.php'=> ['icon' => 'event_repeat', 'title' => 'اشتراک‌ها و Autoship کلینیک'],
            'inventory.php'    => ['icon' => 'medication', 'title' => 'داروخانه و موجودی کالا'],
            'wallet.php'       => ['icon' => 'account_balance_wallet', 'title' => 'مدیریت مالی و تسویه (پایا)'],
            'admins.php'       => ['icon' => 'manage_accounts', 'title' => 'مدیران و دسترسی‌های مرکز'],
        ];

        foreach ($navItems as $file => $item):
            $isActive = ($currentFile === $file);
            $classes = $isActive
                ? "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all"
                : "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
        ?>
        <a class="<?= $classes ?>" href="<?= $file ?>">
            <span class="material-symbols-outlined text-[20px]"><?= $item['icon'] ?></span>
            <span class="text-xs font-bold leading-tight"><?= $item['title'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-white/10">
        <div class="px-1 mb-2 space-y-1.5">
            <a href="../organization_profile.php?slug=<?= urlencode($orgSlug) ?>" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                <span>مشاهده پروفایل عمومی</span>
                <span class="material-symbols-outlined text-xs mr-auto">north_east</span>
            </a>
        </div>
        <a href="../index.php" class="w-full bg-secondary-container text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:translate-x-1 duration-200">
            <span class="material-symbols-outlined">home</span>
            <span class="text-xs">بازگشت به سایت</span>
        </a>
        
        <div class="mt-4 space-y-1">
            <a class="flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all text-xs" href="../logout.php" onclick="return confirm('آیا از خروج از حساب کاربری اطمینان دارید؟');">
                <span class="material-symbols-outlined text-error text-lg">logout</span>
                <span>خروج از حساب</span>
            </a>
        </div>
    </div>
</aside>

<!-- Main Content Wrapper -->
<main class="lg:mr-64 mr-0 min-h-screen transition-all duration-300">
    <!-- TopAppBar -->
    <header class="sticky top-0 z-40 flex justify-between items-center h-16 px-4 lg:px-6 bg-surface shadow-sm border-b border-outline-variant/20">
        <div class="flex items-center gap-2 lg:gap-6">
            <button onclick="toggleOrgSidebar()" class="lg:hidden w-10 h-10 flex shrink-0 items-center justify-center rounded-lg hover:bg-surface-container transition-colors text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 font-bold hidden sm:inline">مرکز درمانی:</span>
                <span class="text-xs font-black text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg"><?= htmlspecialchars($orgName) ?></span>
            </div>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="appointments.php" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-sm transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ رزرو نوبت حضوری</span>
            </a>
            <div class="h-8 w-[1px] bg-outline-variant mx-1"></div>
            <div class="flex items-center gap-3 pl-2">
                <div class="text-left">
                    <p class="text-xs font-bold text-on-surface leading-tight"><?= htmlspecialchars($currentUser['name']) ?></p>
                    <p class="text-[11px] text-on-surface-variant font-medium"><?= htmlspecialchars($currentAdminTitle) ?></p>
                </div>
                <div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden bg-primary-container text-white flex items-center justify-center font-black text-sm">
                    <?= mb_substr($currentUser['name'], 0, 1) ?>
                </div>
            </div>
        </div>
    </header>

<script>
function toggleOrgSidebar() {
    const sidebar = document.getElementById('org-sidebar');
    const backdrop = document.getElementById('org-backdrop');
    
    if (sidebar.classList.contains('translate-x-full')) {
        sidebar.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
        setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('opacity-0');
        setTimeout(() => backdrop.classList.add('hidden'), 300);
        document.body.style.overflow = '';
    }
}
</script>
