<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Pansion, Organization, or Admin
$currentUser = AuthGuard::requireRole(['pansion', 'organization', 'admin'], $pdo);
$pansionName = $currentUser['name'] ?: 'مدیریت پانسیون و هتل پت';
$pansionUserId = (int)$currentUser['id'];

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
    <title>سامانه جامع مدیریت هتل و پانسیون حیوانات خانگی - آسنا</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <style>
        body { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script src="../assets/js/currency-formatter.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="bg-slate-50 text-slate-800 selection:bg-emerald-500/20 antialiased">

<!-- Mobile Backdrop -->
<div id="pansion-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-xs transition-opacity opacity-0" onclick="togglePansionSidebar()"></div>

<!-- Side Navigation Bar -->
<aside id="pansion-sidebar" class="fixed inset-y-0 right-0 w-64 bg-slate-900 text-white flex flex-col z-[70] lg:z-40 shadow-xl transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3 group" title="مشاهده سایت اصلی آسنا">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-emerald-400 font-black leading-tight">آسنا پانسیون</h1>
                    <p class="text-[11px] text-slate-400">هتل و نگهداری تخصصی پت</p>
                </div>
            </a>
            <button onclick="togglePansionSidebar()" class="lg:hidden text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <?php if ($affiliatedOrg): ?>
            <div class="mt-4 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-400 text-xl">local_hospital</span>
                <div class="min-w-0">
                    <p class="text-[10px] text-emerald-300 font-bold">کلینیک همکار و پشتیبان:</p>
                    <p class="text-xs font-black text-white truncate"><?= htmlspecialchars($affiliatedOrg['name']) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-4 space-y-1.5 text-xs font-bold">
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= $currentPage === 'dashboard' ? 'bg-emerald-600 text-white font-black shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-all">
            <span class="material-symbols-outlined text-xl">hotel</span>
            <span>میز کار و داشبورد هتل</span>
        </a>

        <a href="#rooms-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">meeting_room</span>
            <span>سوئیت‌ها و ظرفیت اتاق‌ها</span>
        </a>

        <a href="#boarders-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">pets</span>
            <span>مهمانان مقیم (پذیرش فعال)</span>
        </a>

        <a href="#inventory-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">inventory</span>
            <span>وسایل، تغذیه و داروها</span>
        </a>

        <div class="pt-4 mt-4 border-t border-slate-800 space-y-1.5">
            <a href="../profile.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                <span>حساب کاربری و کیف‌پول</span>
            </a>
            <a href="../index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">storefront</span>
                <span>مشاهده سایت و فروشگاه</span>
            </a>
            <a href="../logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-400 hover:bg-rose-500/10 transition-all">
                <span class="material-symbols-outlined text-xl">logout</span>
                <span>خروج از حساب</span>
            </a>
        </div>
    </nav>

    <!-- Pansion Profile Footer in Sidebar -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-slate-950 font-black flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">holiday_village</span>
            </div>
            <div class="min-w-0">
                <h4 class="text-xs font-black text-white truncate"><?= htmlspecialchars($pansionName) ?></h4>
                <p class="text-[10px] text-emerald-400 font-bold">هتل و پانسیون VIP</p>
            </div>
        </div>
    </div>
</aside>

<!-- Top Bar for Mobile & Screen Area -->
<div class="lg:mr-64 flex flex-col min-h-screen">
    <!-- Top Bar -->
    <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
        <div class="flex items-center gap-3">
            <button onclick="togglePansionSidebar()" class="lg:hidden p-2 rounded-xl hover:bg-slate-100 text-slate-600 transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="font-black text-slate-800 text-sm hidden sm:inline">سامانه جامع مدیریت هتل و پانسیون حیوانات خانگی</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="../profile.php" class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                <span>کیف‌پول: <?= number_format($currentUser['wallet_balance'] ?? 0) ?> تومان</span>
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
