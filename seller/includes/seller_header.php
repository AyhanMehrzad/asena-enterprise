<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Seller or Admin
$currentUser = AuthGuard::requireRole(['seller', 'admin'], $pdo);
$sellerName = $currentUser['name'] ?: 'فروشگاه گرامی';
$sellerId = (int)$currentUser['id'];

// Fetch seller wallet info
$walletStmt = $pdo->prepare("SELECT * FROM seller_wallets WHERE seller_id = ?");
$walletStmt->execute([$sellerId]);
$sellerWallet = $walletStmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerWallet) {
    // Initialize seller wallet
    $pdo->prepare("
        INSERT INTO seller_wallets (seller_id, cleared_balance, in_escrow_balance, bank_account_holder, created_at, updated_at)
        VALUES (?, 0, 0, ?, NOW(), NOW())
    ")->execute([$sellerId, $sellerName]);
    
    $walletStmt->execute([$sellerId]);
    $sellerWallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
}

$activeTab = $_GET['tab'] ?? 'orders';
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>فروشندگان</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/enterprise-ui.css">
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
<div id="seller-backdrop" class="fixed inset-0 bg-black/50 z-[60] hidden lg:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleSellerSidebar()"></div>

<!-- SideNavBar matching Doctor & Pharmacist panels -->
<aside id="seller-sidebar" class="fixed inset-y-0 right-0 w-64 bg-tertiary flex flex-col z-[70] lg:z-40 rtl shadow-lg transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-6 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3 group" title="مشاهده فروشگاه در سایت">
                <img src="../assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-xl text-tertiary-fixed font-bold leading-tight group-hover:text-secondary-container transition-colors">آسنا</h1>
                    <p class="text-sm text-on-tertiary-container/70">پنل فروشندگان و پت‌شاپ</p>
                </div>
            </a>
            <button onclick="toggleSellerSidebar()" class="lg:hidden text-on-tertiary-container hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 text-tertiary-fixed text-xs font-medium mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>نسخه اینترپرایز جامع (فول اکوسیستم)</span>
        </div>

        <button onclick="openNewProductModal()" class="w-full bg-gradient-to-r from-blue-600 to-primary hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-2.5 px-3 rounded-xl flex items-center justify-center gap-2 shadow-md transition-all text-xs my-2">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>+ ثبت کالای جدید در فروشگاه</span>
        </button>
    </div>

    <nav class="flex-1 px-3 mt-2 space-y-1">
        <?php
        $navItems = [
            'orders'       => ['icon' => 'local_shipping', 'title' => 'سفارشات و ارسال کالا', 'tab' => 'orders-tab'],
            'products'     => ['icon' => 'inventory_2', 'title' => 'مدیریت موجودی و انبارداری', 'tab' => 'products-tab'],
            'wallet'       => ['icon' => 'account_balance_wallet', 'title' => 'کیف پول امانی و تسویه پایا', 'tab' => 'wallet-tab'],
            'interactions' => ['icon' => 'hub', 'title' => 'تعاملات و صورت‌حساب آسنا', 'url' => '../interactions.php'],
            'shipping'     => ['icon' => 'markunread_mailbox', 'title' => 'رهگیری مرسولات و پستکس', 'tab' => 'shipping-tab'],
            'settings'     => ['icon' => 'store', 'title' => 'مشخصات فروشگاه و حساب بانکی', 'tab' => 'settings-tab'],
        ];

        foreach ($navItems as $key => $item):
            $isActive = ($activeTab === $key);
            $classes = $isActive 
                ? "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white font-bold bg-secondary-container shadow-sm transition-all"
                : "seller-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-on-tertiary-container hover:bg-white/10 hover:text-white transition-all";
            $onclick = !empty($item['tab']) ? "if(typeof switchSellerTab === 'function') { switchSellerTab('{$item['tab']}'); if(window.innerWidth < 1024) toggleSellerSidebar(); return false; }" : "";
            $href = !empty($item['url']) ? $item['url'] : "index.php?tab={$key}";
        ?>
        <a id="seller-nav-<?= $key ?>" class="<?= $classes ?>" href="<?= $href ?>" <?= !empty($onclick) ? 'onclick="'.$onclick.'"' : '' ?>>
            <span class="material-symbols-outlined text-[20px]"><?= $item['icon'] ?></span>
            <span class="text-xs font-bold leading-tight"><?= $item['title'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-white/10">
        <div class="px-1 mb-2 space-y-1.5">
            <a href="../shop.php" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[20px]">storefront</span>
                <span>مشاهده فروشگاه آنلاین</span>
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
            <button onclick="toggleSellerSidebar()" class="lg:hidden w-10 h-10 flex shrink-0 items-center justify-center rounded-lg hover:bg-surface-container transition-colors text-primary">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-xs text-slate-500 font-bold">پت‌شاپ اختصاصی:</span>
                <span class="text-xs font-black text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg"><?= htmlspecialchars($sellerName) ?></span>
            </div>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3">
            <button onclick="openNewProductModal()" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-primary to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-sm transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ افزودن کالا</span>
            </button>
            <a href="../shop.php" target="_blank" class="hidden sm:flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-blue-50 text-primary border border-blue-200 hover:bg-primary hover:text-white transition-all text-xs font-bold">
                <span class="material-symbols-outlined text-base">storefront</span>
                <span>مشاهده در سایت</span>
            </a>
            <div class="h-8 w-[1px] bg-outline-variant mx-1"></div>
            <div class="flex items-center gap-3 pl-2">
                <div class="text-left">
                    <p class="text-xs font-bold text-on-surface leading-tight"><?= htmlspecialchars($sellerName) ?></p>
                    <p class="text-[11px] text-emerald-600 font-bold">فروشنده تایید شده</p>
                </div>
                <div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden bg-primary-container text-white flex items-center justify-center">
                    <span class="material-symbols-outlined">store</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Quick Tab Bar for Sellers (Digikala Seller App Standard) -->
    <div class="lg:hidden flex items-center gap-2 overflow-x-auto px-4 py-2.5 bg-white border-b border-outline-variant/20 custom-scrollbar sticky top-16 z-30 shadow-xs">
        <button type="button" id="seller-mobile-btn-orders" onclick="switchSellerTab('orders-tab')" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-secondary-container text-white text-xs font-bold shrink-0 flex items-center gap-1.5 shadow-sm">
            <span class="material-symbols-outlined text-sm">local_shipping</span>
            <span>سفارشات</span>
        </button>
        <button type="button" id="seller-mobile-btn-products" onclick="switchSellerTab('products-tab')" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-teal-600">inventory_2</span>
            <span>محصولات</span>
        </button>
        <button type="button" id="seller-mobile-btn-wallet" onclick="switchSellerTab('wallet-tab')" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-emerald-600">account_balance_wallet</span>
            <span>تسویه پایا</span>
        </button>
        <button type="button" id="seller-mobile-btn-shipping" onclick="switchSellerTab('shipping-tab')" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-indigo-600">markunread_mailbox</span>
            <span>رهگیری مرسولات</span>
        </button>
        <button type="button" id="seller-mobile-btn-settings" onclick="switchSellerTab('settings-tab')" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-amber-600">store</span>
            <span>تنظیمات</span>
        </button>
        <a href="../interactions.php" class="seller-mobile-tab px-3.5 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-blue-600">hub</span>
            <span>تعاملات آسنا</span>
        </a>
    </div>
