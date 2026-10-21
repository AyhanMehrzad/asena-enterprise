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
    <style>
        body { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script src="../assets/js/currency-formatter.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="bg-slate-50 text-slate-800 selection:bg-amber-500/20 antialiased">

<!-- Mobile Backdrop -->
<div id="groomer-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-xs transition-opacity opacity-0" onclick="toggleGroomerSidebar()"></div>

<!-- Side Navigation Bar -->
<aside id="groomer-sidebar" class="fixed inset-y-0 right-0 w-64 bg-slate-900 text-white flex flex-col z-[70] lg:z-40 shadow-xl transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3 group" title="مشاهده سایت اصلی آسنا">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-amber-400 font-black leading-tight">آسنا گرومینگ</h1>
                    <p class="text-[11px] text-slate-400">کابین تخصصی آرایش پت</p>
                </div>
            </a>
            <button onclick="toggleGroomerSidebar()" class="lg:hidden text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <?php if ($affiliatedOrg): ?>
            <div class="mt-4 p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-amber-400 text-xl">domain</span>
                <div class="min-w-0">
                    <p class="text-[10px] text-amber-300 font-bold">مرکز درمانی همکار:</p>
                    <p class="text-xs font-black text-white truncate"><?= htmlspecialchars($affiliatedOrg['name']) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-4 space-y-1.5 text-xs font-bold">
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= $currentPage === 'dashboard' ? 'bg-amber-500 text-slate-950 font-black shadow-lg shadow-amber-500/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?> transition-all">
            <span class="material-symbols-outlined text-xl">content_cut</span>
            <span>میز کار و شیفت گرومینگ</span>
        </a>

        <a href="#appointments-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">calendar_month</span>
            <span>نوبت‌های اصلاح و شستشو</span>
        </a>

        <a href="#portfolio-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">photo_library</span>
            <span>آلبوم قبل و بعد (Portfolio)</span>
        </a>

        <a href="#services-section" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
            <span class="material-symbols-outlined text-xl">sell</span>
            <span>تعرفه‌ها و خدمات گرومر</span>
        </a>

        <div class="pt-4 mt-4 border-t border-slate-800 space-y-1.5">
            <a href="../profile.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">account_circle</span>
                <span>حساب کاربری و کیف‌پول</span>
            </a>
            <a href="../index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xl">storefront</span>
                <span>مشاهده فروشگاه و سایت</span>
            </a>
            <a href="../logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-400 hover:bg-rose-500/10 transition-all">
                <span class="material-symbols-outlined text-xl">logout</span>
                <span>خروج از حساب</span>
            </a>
        </div>
    </nav>

    <!-- Groomer Profile Footer in Sidebar -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-black flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">pets</span>
            </div>
            <div class="min-w-0">
                <h4 class="text-xs font-black text-white truncate"><?= htmlspecialchars($groomerName) ?></h4>
                <p class="text-[10px] text-amber-400 font-bold">گرومر و استایلیست حیوانات</p>
            </div>
        </div>
    </div>
</aside>

<!-- Top Bar for Mobile & Screen Area -->
<div class="lg:mr-64 flex flex-col min-h-screen">
    <!-- Top Bar -->
    <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
        <div class="flex items-center gap-3">
            <button onclick="toggleGroomerSidebar()" class="lg:hidden p-2 rounded-xl hover:bg-slate-100 text-slate-600 transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="font-black text-slate-800 text-sm hidden sm:inline">کابین تخصصی گرومینگ و آرایش حیوانات خانگی</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="../profile.php" class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                <span>کیف‌پول من: <?= number_format($currentUser['wallet_balance'] ?? 0) ?> تومان</span>
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
