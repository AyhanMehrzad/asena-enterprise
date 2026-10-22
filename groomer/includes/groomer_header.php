<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Groomer or Admin
$currentUser = AuthGuard::requireRole(['groomer', 'admin'], $pdo);
$groomerName = $currentUser['name'] ?: 'گرومر گرامی';
$groomerUserId = (int)$currentUser['id'];

// Check affiliated organization if any
$affiliatedOrg = null;
if (!empty($currentUser['organization_id'])) {
    try {
        $orgStmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ? LIMIT 1");
        $orgStmt->execute([$currentUser['organization_id']]);
        $affiliatedOrg = $orgStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

$currentPage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>پنل تخصصی گرومینگ و آرایشگاه حیوانات - آسنا</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <link rel="stylesheet" href="../assets/css/enterprise-ui.css">
    <style>
        body { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script src="../assets/js/currency-formatter.js?v=<?php echo time(); ?>" defer></script>
    <script src="../assets/js/panel-sidebar.js?v=<?php echo time(); ?>" defer></script>
    <script>
        (function() {
            try {
                if (localStorage.getItem('asana_panel_sidebar_collapsed') === '1' && window.innerWidth >= 1024) {
                    document.documentElement.classList.add('sidebar-initially-collapsed');
                }
            } catch(e) {}
        })();
    </script>
</head>
<body class="bg-surface text-on-surface selection:bg-secondary-container/30 antialiased">

<!-- Mobile Backdrop -->
<div id="groomer-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleGroomerSidebar()"></div>

<!-- SideNavBar matching original Asena panels with desktop collapse -->
<aside id="groomer-sidebar" class="panel-sidebar fixed inset-y-0 right-0 w-64 bg-tertiary flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-all duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between sidebar-toggle-container">
            <a href="../index.php" class="flex items-center gap-3 group sidebar-header-text" title="مشاهده سایت">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight group-hover:text-secondary-container transition-colors">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">آرایش و گرومینگ تخصصی</p>
                </div>
            </a>
            <div class="flex items-center gap-1">
                <button type="button" class="sidebar-desktop-toggle-btn hidden lg:flex w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-tertiary-fixed items-center justify-center transition-colors shadow-xs" onclick="togglePanelDesktopSidebar()" title="جمع کردن / باز کردن منو">
                    <span class="sidebar-desktop-toggle-icon material-symbols-outlined text-xl">chevron_right</span>
                </button>
                <button onclick="toggleGroomerSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors p-1" aria-label="بستن منو">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>

        <div class="sidebar-tier-badge inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 text-tertiary-fixed text-xs font-medium mb-1">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            <span>کابین تخصصی گرومینگ پت</span>
        </div>

        <button type="button" onclick="document.getElementById('portfolio-modal').classList.remove('hidden')" class="sidebar-cta-btn w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-slate-950 font-bold py-2.5 px-3 rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-xs my-2" title="ثبت نمونه‌کار جدید">
            <span class="material-symbols-outlined text-base shrink-0">add_photo_alternate</span>
            <span class="sidebar-label">+ ثبت نمونه‌کار جدید</span>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 mt-1 space-y-1">
        <?php
        $navItems = [
            'index.php'               => ['icon' => 'content_cut', 'title' => 'میز کار و شیفت گرومینگ', 'active' => ($currentPage === 'dashboard')],
            '#appointments-section'   => ['icon' => 'calendar_month', 'title' => 'نوبت‌های اصلاح و شستشو', 'active' => false],
            '#portfolio-section'      => ['icon' => 'photo_library', 'title' => 'آلبوم قبل و بعد (Portfolio)', 'active' => false],
            '#services-section'       => ['icon' => 'sell', 'title' => 'تعرفه‌ها و خدمات گرومر', 'active' => false],
        ];

        foreach ($navItems as $url => $item):
            $classes = $item['active']
                ? "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all"
                : "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
        ?>
        <a class="<?= $classes ?>" href="<?= $url ?>" title="<?= $item['title'] ?>">
            <span class="material-symbols-outlined text-[20px] shrink-0"><?= $item['icon'] ?></span>
            <span class="text-xs font-bold leading-tight sidebar-label"><?= $item['title'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-6 pt-2 flex flex-col gap-2">
        <div class="px-1 mb-2 space-y-1.5 sidebar-footer-extra">
            <?php if ($affiliatedOrg): ?>
                <div class="px-3.5 py-2 rounded-xl bg-white/5 text-slate-300 text-[11px] leading-relaxed border border-white/5">
                    <span class="text-amber-400 font-bold block mb-0.5">مرکز درمانی همکار:</span>
                    <span class="text-white font-bold"><?= htmlspecialchars($affiliatedOrg['name']) ?></span>
                </div>
            <?php endif; ?>
            <div class="px-3.5 py-2 rounded-xl bg-white/5 text-slate-300 text-[11px] leading-relaxed border border-white/5">
                <span class="text-secondary-fixed-dim font-bold block mb-0.5">گرومر و استایلیست:</span>
                <span class="text-white font-bold truncate block"><?= htmlspecialchars($groomerName) ?></span>
            </div>
        </div>

        <a href="../profile.php" class="sidebar-footer-link w-full bg-white/10 hover:bg-white/15 text-white font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors text-xs" title="حساب کاربری و کیف‌پول">
            <span class="material-symbols-outlined text-lg shrink-0">account_circle</span>
            <span class="sidebar-label">حساب کاربری و کیف‌پول</span>
        </a>

        <a href="../index.php" class="sidebar-footer-link w-full bg-secondary-container text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:translate-x-1 duration-200" title="مشاهده فروشگاه و سایت">
            <span class="material-symbols-outlined text-lg shrink-0">storefront</span>
            <span class="text-xs font-bold sidebar-label">مشاهده فروشگاه و سایت</span>
        </a>

        <div class="mt-2">
            <a class="sidebar-footer-link flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all text-xs font-bold" href="../logout.php" onclick="return confirm('آیا از خروج از حساب کاربری اطمینان دارید؟');" title="خروج از حساب">
                <span class="material-symbols-outlined text-rose-400 text-lg shrink-0">logout</span>
                <span class="sidebar-label">خروج از حساب</span>
            </a>
        </div>
    </div>
</aside>

<!-- Top Bar for Mobile & Screen Area -->
<div id="panel-main-content" class="panel-main-content lg:mr-64 flex flex-col min-h-screen transition-all duration-300">
    <!-- Top Bar -->
    <header class="h-16 bg-surface border-b border-outline-variant/20 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
        <div class="flex items-center gap-3">
            <button onclick="toggleGroomerSidebar()" class="lg:hidden p-2 rounded-xl hover:bg-surface-container text-on-surface-variant transition-colors" aria-label="منوی ناوبری">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="font-bold text-on-surface text-sm hidden sm:inline">کابین تخصصی گرومینگ و آرایش حیوانات خانگی</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="../profile.php" class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold flex items-center gap-1.5 shadow-xs">
                <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                <span>کیف‌پول من: <?= number_format($currentUser['wallet_balance'] ?? 0) ?> تومان</span>
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
