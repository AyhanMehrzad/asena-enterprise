<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Pharmacist or Admin
$currentUser = AuthGuard::requireRole(['pharmacist', 'admin'], $pdo);

$pharmacistName = $currentUser['name'] ?: 'داروساز گرامی';

// Check if linked to an organization
$orgStmt = $pdo->prepare("
    SELECT o.* 
    FROM organizations o
    JOIN organization_doctors od ON o.id = od.organization_id
    JOIN doctors d ON od.doctor_id = d.id
    WHERE d.user_id = ? AND od.role_type = 'pharmacist'
    LIMIT 1
");
$orgStmt->execute([$currentUser['id']]);
$linkedOrg = $orgStmt->fetch(PDO::FETCH_ASSOC);

if (!$linkedOrg) {
    // Default to main hospital
    $linkedOrg = $pdo->query("SELECT * FROM organizations ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
$orgName = $linkedOrg['name'] ?? 'داروخانه مرکزی بیمارستان آسنا';
$orgId = (int)($linkedOrg['id'] ?? 1);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>داروساز</title>
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
</head>
<body class="bg-surface text-on-surface selection:bg-secondary-container/30">

<!-- Mobile Backdrop -->
<div id="pharmacist-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="togglePharmacistSidebar()"></div>

<!-- SideNavBar matching Doctor panel exact layout -->
<aside id="pharmacist-sidebar" class="fixed inset-y-0 right-0 w-64 bg-tertiary flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3 group" title="مشاهده سایت">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight group-hover:text-secondary-container transition-colors">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">پنل داروسازان</p>
                </div>
            </a>
            <button onclick="togglePharmacistSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 text-tertiary-fixed text-xs font-medium mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>نسخه اینترپرایز جامع (فول اکوسیستم)</span>
        </div>

        <button type="button" onclick="document.getElementById('addMedModal').classList.remove('hidden')" class="w-full bg-gradient-to-r from-blue-600 to-primary hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-2.5 px-3 rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-xs my-2">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>+ ثبت داروی جدید در انبار</span>
        </button>
    </div>

    <!-- Navigation Items -->
    <nav class="flex-1 px-3 mt-1 space-y-1">
        <?php 
        $activeTabKey = $_GET['tab'] ?? 'prescriptions';
        
        $navItems = [
            'prescriptions' => ['icon' => 'prescriptions', 'title' => 'کارتابل نسخه‌های الکترونیک', 'tab' => 'prescriptions-tab'],
            'inventory'     => ['icon' => 'medication', 'title' => 'انبار دارویی و کنترل موجودی', 'tab' => 'inventory-tab'],
            'autoship'      => ['icon' => 'autorenew', 'title' => 'تکرار دارو و اتوشیپ مزمن', 'tab' => 'autoship-tab'],
            'interactions'  => ['icon' => 'sync_problem', 'title' => 'راهنمای تداخلات و هشدارها', 'tab' => 'interactions-tab'],
            'history'       => ['icon' => 'history', 'title' => 'آرشیو تحویل و سوابق دارویی', 'tab' => 'history-tab'],
        ];

        foreach ($navItems as $key => $item):
            $isActive = ($activeTabKey === $key);
            $classes = $isActive 
                ? "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all"
                : "flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
            $onclick = "if(typeof switchTab === 'function') { switchTab('{$item['tab']}'); if(window.innerWidth < 1024) togglePharmacistSidebar(); return false; }";
        ?>
        <a id="nav-item-<?= $key ?>" class="<?= $classes ?>" href="index.php?tab=<?= $key ?>" onclick="<?= $onclick ?>">
            <span class="material-symbols-outlined text-[20px]"><?= $item['icon'] ?></span>
            <span class="text-xs font-bold leading-tight"><?= $item['title'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-6 pt-2 flex flex-col gap-2">
        <div class="px-1 mb-2 space-y-1.5">
            <a href="../pharmacy.php" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[20px]">storefront</span>
                <span>داروخانه عمومی سایت</span>
                <span class="material-symbols-outlined text-xs mr-auto">north_east</span>
            </a>
            <div class="px-3.5 py-2 rounded-xl bg-white/5 text-slate-300 text-[11px] leading-relaxed border border-white/5">
                <span class="text-emerald-400 font-bold block mb-0.5">وابسته به مرکز:</span>
                <span class="text-white font-bold"><?= htmlspecialchars($orgName) ?></span>
            </div>
        </div>

        <a href="../index.php" class="w-full bg-secondary-container text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:translate-x-1 duration-200">
            <span class="material-symbols-outlined">storefront</span>
            <span class="text-xs font-bold">بازگشت به سایت</span>
        </a>

        <div class="mt-2">
            <a class="flex items-center gap-3 px-4 py-2 text-on-tertiary-container hover:text-white transition-all text-xs font-bold" href="../logout.php" onclick="return confirm('آیا از خروج از حساب کاربری اطمینان دارید؟');">
                <span class="material-symbols-outlined text-rose-400">logout</span>
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
            <button onclick="togglePharmacistSidebar()" class="lg:hidden w-10 h-10 flex shrink-0 items-center justify-center rounded-lg hover:bg-surface-container transition-colors text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                <span class="material-symbols-outlined text-emerald-600 text-lg">local_pharmacy</span>
                <span class="font-bold text-slate-800"><?= htmlspecialchars($orgName) ?></span>
                <span>• بخش فارماکولوژی و داروخانه</span>
            </div>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" onclick="document.getElementById('addMedModal').classList.remove('hidden')" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 text-white shadow-sm transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ ثبت داروی جدید</span>
            </button>
            <div class="h-8 w-[1px] bg-outline-variant mx-1"></div>
            <div class="flex items-center gap-3 pl-2">
                <div class="text-left">
                    <p class="font-bold text-xs text-on-surface leading-tight"><?= htmlspecialchars($pharmacistName) ?></p>
                    <p class="text-[10px] text-emerald-700 font-bold">مسئول فنی و داروساز</p>
                </div>
                <div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden bg-primary-container text-white flex items-center justify-center">
                    <span class="material-symbols-outlined">medication</span>
                </div>
            </div>
        </div>
    </header>

<script>
function togglePharmacistSidebar() {
    const sidebar = document.getElementById('pharmacist-sidebar');
    const backdrop = document.getElementById('pharmacist-backdrop');
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
