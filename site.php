<?php
/**
 * ASENA Enterprise - Luxury Multi-Tier Tenant Showcase Microsite
 * Agency-grade, mobile-first responsive showcase website for Organizations, Doctors, Pharmacists, and Sellers.
 * Features:
 * - 5-Tier capability archetypes (Basic, Standard, Premium, Pharmacy, Enterprise)
 * - Thumb-Zone Sticky Conversion Bar on mobile viewports (<768px)
 * - Bento Grid facility architecture & live duty status pulsing
 * - Real-time stats counter strip & verified social proof
 * - Direct integration with ASENA's centralized booking, inventory, and payment engine
 * Version: 2.0.0
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';

// Resolve Slug from Subdomain or GET parameter
$host = $_SERVER['HTTP_HOST'] ?? '';
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    $hostParts = explode('.', $host);
    if (count($hostParts) >= 3 && $hostParts[0] !== 'www') {
        $slug = $hostParts[0];
    }
}

if (empty($slug)) {
    header("Location: index.php");
    exit;
}

$tenantService = App::tenantSite();
$site = $tenantService->getSiteBySlug($slug);

if (!$site) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>وب‌سایت یافت نشد | آسنا</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="assets/css/geist.css">
        <link rel="stylesheet" href="assets/css/tailwind.output.css">
    </head>
    <body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 text-slate-800">
        <div class="max-w-md w-full bg-white rounded-3xl p-8 text-center shadow-xl border border-slate-100">
            <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-2xl mx-auto flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-3xl">domain_disabled</span>
            </div>
            <h1 class="text-xl font-bold mb-2">وب‌سایت مورد نظر یافت نشد</h1>
            <p class="text-sm text-slate-500 mb-6">این نشانی هنوز در سامانه ابری آسنا راه‌اندازی نشده یا در حال آماده‌سازی است.</p>
            <a href="index.php" class="inline-flex items-center gap-2 px-6 py-3 bg-[#001a48] text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors">
                بازگشت به صفحه اصلی آسنا
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$isPreview = isset($_GET['preview']) && (int)$_GET['preview'] === 1;
$tenantType = $site['tenant_type'];
$tenantId = (int)$site['tenant_id'];
$siteTier = $site['site_tier'] ?? 'enterprise';

$defaultLayout = $tenantService->buildDefaultLayout($tenantType, [
    'name' => $site['site_title'],
    'phone' => $site['layout']['blocks']['header']['phone'] ?? '',
    'operating_hours' => $site['layout']['blocks']['contact']['hours'] ?? '',
    'banner_url' => $site['banner_url']
], $siteTier)['blocks'];

$layout = array_replace_recursive($defaultLayout, $site['layout']['blocks'] ?? []);
$headerBlock = $layout['header'] ?? [];
$heroBlock = $layout['hero'] ?? [];
$statsBlock = $layout['stats_strip'] ?? [];
$bentoBlock = $layout['bento_facilities'] ?? [];
$aboutBlock = $layout['about'] ?? [];
$servicesBlock = $layout['services'] ?? [];
$doctorsBlock = $layout['doctors_roster'] ?? [];
$telehealthBlock = $layout['telehealth_launcher'] ?? [];
$rxBlock = $layout['rx_prescription_box'] ?? [];
$autoshipBlock = $layout['autoship_showcase'] ?? [];
$bookingBlock = $layout['booking'] ?? [];
$storefrontBlock = $layout['storefront'] ?? [];
$articlesBlock = $layout['articles'] ?? [];
$loyaltyBlock = $layout['loyalty_club'] ?? [];
$reviewsBlock = $layout['reviews'] ?? [];
$contactBlock = $layout['contact'] ?? [];
$mobileBarBlock = $layout['sticky_mobile_bar'] ?? [];
$footerBlock = $layout['footer'] ?? [];

// Hydrate live items from ASENA database
$tenantProducts = [];
if (!empty($storefrontBlock['enabled'])) {
    $itemLimit = (int)($storefrontBlock['item_limit'] ?? 6);
    $tenantProducts = $tenantService->getTenantProducts($tenantType, $tenantId, $itemLimit);
}

$tenantDoctors = [];
if (!empty($doctorsBlock['enabled']) || $tenantType === 'organization') {
    $tenantDoctors = $tenantService->getOrganizationDoctors($tenantId);
}

$tenantArticles = [];
if (!empty($articlesBlock['enabled'])) {
    $tenantArticles = $tenantService->getTenantArticles(3);
}

$tenantReviews = [];
if (!empty($reviewsBlock['enabled'])) {
    $tenantReviews = $tenantService->getTenantReviews($tenantType, $tenantId, 3);
}

// Color Theme Palettes
$paletteMap = [
    'emerald' => [
        'primary' => '#059669',
        'primary_hover' => '#047857',
        'primary_light' => '#ecfdf5',
        'primary_border' => '#a7f3d0',
        'accent' => '#fd8100',
        'gradient' => 'from-emerald-600 to-teal-800',
        'subtle_glow' => 'rgba(5, 150, 105, 0.12)'
    ],
    'navy' => [
        'primary' => '#001a48',
        'primary_hover' => '#002666',
        'primary_light' => '#eff6ff',
        'primary_border' => '#bfdbfe',
        'accent' => '#fd8100',
        'gradient' => 'from-[#001a48] to-[#0a3580]',
        'subtle_glow' => 'rgba(0, 26, 72, 0.12)'
    ],
    'orange' => [
        'primary' => '#ea580c',
        'primary_hover' => '#c2410c',
        'primary_light' => '#fff7ed',
        'primary_border' => '#fed7aa',
        'accent' => '#001a48',
        'gradient' => 'from-orange-600 to-amber-700',
        'subtle_glow' => 'rgba(234, 88, 12, 0.12)'
    ],
    'purple' => [
        'primary' => '#7c3aed',
        'primary_hover' => '#6d28d9',
        'primary_light' => '#f5f3ff',
        'primary_border' => '#ddd6fe',
        'accent' => '#ea580c',
        'gradient' => 'from-purple-600 to-indigo-800',
        'subtle_glow' => 'rgba(124, 58, 237, 0.12)'
    ]
];
$theme = $paletteMap[$site['theme_palette']] ?? $paletteMap['emerald'];

// SEO & Meta
$metaTitle = htmlspecialchars($site['site_title'] . (!empty($site['site_tagline']) ? ' - ' . $site['site_tagline'] : ''));
$metaDesc = htmlspecialchars($site['meta_description'] ?: ($site['site_title'] . ' در شبکه رسمی سلامت و خدمات آسنا.'));
$siteLogo = !empty($site['logo_url']) ? $site['logo_url'] : 'assets/images/logo.png';

$ctaHref = match($tenantType) {
    'doctor' => "booking.php?doctor_id={$tenantId}",
    'organization' => "booking.php?org_id={$tenantId}",
    'pharmacist' => "pharmacy.php",
    default => "shop.php"
};
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <link rel="icon" href="<?= htmlspecialchars($siteLogo) ?>">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/material-symbols.css">
    <link rel="stylesheet" href="assets/css/geist.css">
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <link rel="stylesheet" href="assets/css/enterprise-ui.css">

    <style>
        :root {
            --tenant-primary: <?= $theme['primary'] ?>;
            --tenant-primary-hover: <?= $theme['primary_hover'] ?>;
            --tenant-primary-light: <?= $theme['primary_light'] ?>;
            --tenant-primary-border: <?= $theme['primary_border'] ?>;
            --tenant-accent: <?= $theme['accent'] ?>;
            --tenant-glow: <?= $theme['subtle_glow'] ?>;
        }
        body { 
            font-family: 'Vazirmatn', 'Geist', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .bg-tenant-primary { background-color: var(--tenant-primary); }
        .bg-tenant-primary-hover:hover { background-color: var(--tenant-primary-hover); }
        .text-tenant-primary { color: var(--tenant-primary); }
        .border-tenant-primary { border-color: var(--tenant-primary); }
        .bg-tenant-light { background-color: var(--tenant-primary-light); }
        .border-tenant-light { border-color: var(--tenant-primary-border); }

        .mesh-ambient {
            background-image: radial-gradient(at 0% 0%, var(--tenant-glow) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(253, 129, 0, 0.08) 0px, transparent 50%);
        }

        <?php if ($isPreview): ?>
        [data-block-id] {
            position: relative;
            transition: outline 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        [data-block-id]:hover {
            outline: 2px dashed var(--tenant-accent);
            outline-offset: 4px;
        }
        [data-block-id]:hover::after {
            content: 'ویرایش این بخش ✎';
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--tenant-accent);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            z-index: 50;
            pointer-events: none;
        }
        <?php endif; ?>
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-orange-500 selection:text-white pb-20 md:pb-0">

    <!-- Top Announcement & Verified Institutional Strip -->
    <div class="bg-[#001a48] text-white py-2 px-4 text-xs font-medium border-b border-slate-700/50">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[11px] sm:text-xs">عضو رسمی شبکه خدمات و سلامت حیوانات خانگی آسنا (ASENA.company)</span>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-[11px]">
                <?php if (!empty($contactBlock['emergency_phone'])): ?>
                <a href="tel:<?= htmlspecialchars($contactBlock['emergency_phone']) ?>" class="text-rose-300 hover:text-white flex items-center gap-1 font-bold">
                    <span class="material-symbols-outlined text-xs">e911_emergency</span>
                    <span>اورژانس: <span class="font-mono" dir="ltr"><?= htmlspecialchars($contactBlock['emergency_phone']) ?></span></span>
                </a>
                <?php endif; ?>
                <a href="index.php" target="_blank" class="hover:text-amber-300 transition-colors flex items-center gap-0.5">
                    <span>پرتال مرکزی</span>
                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-xl border-b border-slate-200/80 shadow-sm transition-all" data-block-id="header">
        <div class="max-w-6xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-sm flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover">
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug"><?= htmlspecialchars($site['site_title']) ?></h1>
                        <span class="material-symbols-outlined text-emerald-600 text-sm" title="تایید صلاحیت رسمی">verified</span>
                    </div>
                    <?php if (!empty($site['site_tagline'])): ?>
                        <p class="text-[11px] sm:text-xs text-slate-500 font-medium truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($site['site_tagline']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center gap-6 text-xs font-bold text-slate-600">
                <a href="#about" class="hover:text-tenant-primary transition-colors">معرفی</a>
                <a href="#services" class="hover:text-tenant-primary transition-colors">خدمات تخصصی</a>
                <?php if (!empty($doctorsBlock['enabled']) && !empty($tenantDoctors)): ?>
                    <a href="#doctors" class="hover:text-tenant-primary transition-colors">پزشکان مرکز</a>
                <?php endif; ?>
                <?php if (!empty($bentoBlock['enabled'])): ?>
                    <a href="#facilities" class="hover:text-tenant-primary transition-colors">امکانات کلینیک</a>
                <?php endif; ?>
                <?php if (!empty($bookingBlock['enabled'])): ?>
                    <a href="#booking" class="hover:text-tenant-primary transition-colors">نوبت‌دهی</a>
                <?php endif; ?>
                <?php if (!empty($storefrontBlock['enabled'])): ?>
                    <a href="#storefront" class="hover:text-tenant-primary transition-colors">کالاها و داروها</a>
                <?php endif; ?>
                <?php if (!empty($articlesBlock['enabled'])): ?>
                    <a href="#articles" class="hover:text-tenant-primary transition-colors">دانشنامه سلامت</a>
                <?php endif; ?>
                <?php if (!empty($reviewsBlock['enabled'])): ?>
                    <a href="#reviews" class="hover:text-tenant-primary transition-colors">نظرات مراجعین</a>
                <?php endif; ?>
                <a href="#contact" class="hover:text-tenant-primary transition-colors">تماس و آدرس</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-2.5">
                <?php if (!empty($headerBlock['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($headerBlock['phone']) ?>" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                        <span class="material-symbols-outlined text-sm text-tenant-primary">call</span>
                        <span dir="ltr"><?= htmlspecialchars($headerBlock['phone']) ?></span>
                    </a>
                <?php endif; ?>

                <a href="<?= $ctaHref ?>" class="hidden md:inline-flex px-5 py-2.5 rounded-xl bg-tenant-primary bg-tenant-primary-hover text-white text-xs font-bold shadow-lg shadow-emerald-900/10 transition-transform active:scale-95 items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                    <span><?= htmlspecialchars($headerBlock['cta_text'] ?? 'رزرو نوبت') ?></span>
                </a>

                <!-- Mobile Menu Button -->
                <button type="button" onclick="toggleMobileDrawer()" class="lg:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700">
                    <span class="material-symbols-outlined text-xl">menu</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex-col justify-end lg:hidden transition-opacity">
        <div class="bg-white rounded-t-3xl p-6 space-y-4 max-h-[80vh] overflow-y-auto shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <span class="font-bold text-sm text-slate-800">ناوبری و بخش‌های وب‌سایت</span>
                <button onclick="toggleMobileDrawer()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <nav class="space-y-2 text-sm font-bold text-slate-700">
                <a href="#about" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">info</span>
                    <span>معرفی و سوابق</span>
                </a>
                <a href="#services" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">medical_services</span>
                    <span>خدمات درمانی و بالینی</span>
                </a>
                <?php if (!empty($doctorsBlock['enabled']) && !empty($tenantDoctors)): ?>
                <a href="#doctors" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">person</span>
                    <span>پزشکان و متخصصان مرکز</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($bentoBlock['enabled'])): ?>
                <a href="#facilities" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">apartment</span>
                    <span>تجهیزات و بخش‌های مرکز</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($bookingBlock['enabled'])): ?>
                <a href="#booking" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">calendar_today</span>
                    <span>تقویم نوبت‌دهی آنلاین</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($storefrontBlock['enabled'])): ?>
                <a href="#storefront" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">inventory_2</span>
                    <span>داروخانه و محصولات پت‌شاپ</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($articlesBlock['enabled'])): ?>
                <a href="#articles" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">menu_book</span>
                    <span>دانشنامه سلامت پت</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($reviewsBlock['enabled'])): ?>
                <a href="#reviews" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">rate_review</span>
                    <span>نظرات مراجعین تاییدشده</span>
                </a>
                <?php endif; ?>
                <a href="#contact" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">location_on</span>
                    <span>اطلاعات تماس و نشانی</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Hero Authority Zone (Above-the-Fold) -->
    <?php if (!empty($heroBlock['enabled'])): ?>
    <section class="relative py-12 md:py-20 overflow-hidden border-b border-slate-200/60 bg-gradient-to-b from-white via-slate-50 to-white mesh-ambient" data-block-id="hero">
        <div class="max-w-6xl mx-auto px-4 relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-center lg:text-right">
                
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5">
                    <?php if (!empty($heroBlock['badge'])): ?>
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-tenant-light border border-tenant-light text-tenant-primary text-xs font-black shadow-sm">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span><?= htmlspecialchars($heroBlock['badge']) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Live On-Duty Pulsing Indicator -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>پذیرش فعال و نوبت‌دهی آنلاین</span>
                    </div>
                </div>

                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 leading-[1.2] tracking-tight">
                    <?= htmlspecialchars($heroBlock['title'] ?? $site['site_title']) ?>
                </h2>

                <p class="text-sm sm:text-base md:text-lg text-slate-600 leading-relaxed font-normal max-w-2xl mx-auto lg:mx-0">
                    <?= htmlspecialchars($heroBlock['subtitle'] ?? '') ?>
                </p>

                <!-- Dual High-Intent CTAs -->
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5">
                    <a href="<?= $ctaHref ?>" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-tenant-primary bg-tenant-primary-hover text-white text-sm font-black shadow-xl shadow-emerald-900/15 hover:shadow-2xl transition-all flex items-center justify-center gap-2 group">
                        <span><?= htmlspecialchars($heroBlock['cta_primary_text'] ?? 'رزرو آنلاین نوبت') ?></span>
                        <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                    
                    <?php if (!empty($heroBlock['cta_secondary_text'])): ?>
                    <a href="#contact" class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 text-sm font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-slate-500">call</span>
                        <span><?= htmlspecialchars($heroBlock['cta_secondary_text']) ?></span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Trust Strip Validation -->
                <div class="pt-6 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-slate-500 text-xs font-semibold border-t border-slate-200/60">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">shield</span>
                        <span>پرداخت شاپرک متمرکز در آسنا</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">sms</span>
                        <span>پیامک تأیید نوبت ملی‌پیامک</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">support_agent</span>
                        <span>پشتیبانی شبانه‌روزی ۲۴ ساعته</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 relative flex justify-center">
                <div class="w-full max-w-md aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 relative group">
                    <?php $heroImg = !empty($heroBlock['image']) ? $heroBlock['image'] : $site['banner_url']; ?>
                    <img src="<?= htmlspecialchars($heroImg ?: 'assets/images/clinic-banner.jpg') ?>" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 right-4 left-4 text-white p-3.5 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>سامانه خدمات هوشمند حیوانات</span>
                            </span>
                            <span class="text-amber-300 font-mono">ASENA Powered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social Proof Operational Scale Strip -->
    <?php if (!empty($statsBlock['enabled'])): ?>
    <section class="py-6 bg-white border-b border-slate-200/80" data-block-id="stats_strip">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach (($statsBlock['stats'] ?? []) as $stat): ?>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-center flex flex-col items-center justify-center">
                    <div class="w-8 h-8 rounded-xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-1.5">
                        <span class="material-symbols-outlined text-lg"><?= htmlspecialchars($stat['icon'] ?? 'check') ?></span>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 tracking-tight font-mono"><?= htmlspecialchars($stat['value']) ?></div>
                    <div class="text-[11px] text-slate-500 font-bold"><?= htmlspecialchars($stat['label']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bento Grid Facilities Architecture -->
    <?php if (!empty($bentoBlock['enabled']) && !empty($bentoBlock['items'])): ?>
    <section id="facilities" class="py-16 bg-slate-50 border-b border-slate-200/60" data-block-id="bento_facilities">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">استانداردهای بالینی و درمانی</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($bentoBlock['heading']) ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($bentoBlock['subtitle'] ?? '') ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($bentoBlock['items'] as $item): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 flex items-start gap-4 group">
                    <div class="w-14 h-14 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($item['icon'] ?? 'local_hospital') ?></span>
                    </div>
                    <div class="space-y-1.5">
                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200/60"><?= htmlspecialchars($item['tag'] ?? 'تخصصی') ?></span>
                        <h4 class="text-base font-black text-slate-900"><?= htmlspecialchars($item['title']) ?></h4>
                        <p class="text-xs text-slate-600 leading-relaxed"><?= htmlspecialchars($item['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Telehealth Live Consultation Launcher (Premium & Enterprise) -->
    <?php if (!empty($telehealthBlock['enabled'])): ?>
    <section class="py-12 bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-900 text-white border-b border-slate-800" data-block-id="telehealth_launcher">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-right">
                <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[11px] font-bold border border-indigo-500/30">تله‌هلث و مشاوره تصویری هوشمند</span>
                <h3 class="text-2xl sm:text-3xl font-black"><?= htmlspecialchars($telehealthBlock['heading']) ?></h3>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl font-normal leading-relaxed"><?= htmlspecialchars($telehealthBlock['subtitle']) ?></p>
            </div>
            <div>
                <a href="chat.php" class="px-7 py-3.5 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-white font-black text-xs shadow-xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">videocam</span>
                    <span><?= htmlspecialchars($telehealthBlock['cta_text'] ?? 'شروع مشاوره آنلاین') ?></span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Rx Prescription Photo Upload (Pharmacy & Enterprise) -->
    <?php if (!empty($rxBlock['enabled'])): ?>
    <section class="py-12 bg-gradient-to-r from-purple-900 via-purple-950 to-slate-900 text-white border-b border-slate-800" data-block-id="rx_prescription_box">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-right">
                <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-[11px] font-bold border border-purple-500/30">داروخانه تخصصی با شرایط زنجیره سرد (۲-۸°C)</span>
                <h3 class="text-2xl sm:text-3xl font-black"><?= htmlspecialchars($rxBlock['heading']) ?></h3>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl font-normal leading-relaxed"><?= htmlspecialchars($rxBlock['subtitle']) ?></p>
            </div>
            <div>
                <a href="pharmacy.php" class="px-7 py-3.5 rounded-2xl bg-purple-500 hover:bg-purple-600 text-white font-black text-xs shadow-xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">upload_file</span>
                    <span>ارسال و ثبت نسخه دارویی</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Autoship Periodic Delivery Showcase (Standard, Premium, Enterprise) -->
    <?php if (!empty($autoshipBlock['enabled'])): ?>
    <section class="py-12 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white border-b border-orange-600 shadow-inner" data-block-id="autoship_showcase">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 text-center md:text-right">
                <span class="px-3 py-1 rounded-full bg-black/20 text-white text-[11px] font-black">مدل اختصاصی Chewy Autoship</span>
                <h3 class="text-2xl sm:text-3xl font-black"><?= htmlspecialchars($autoshipBlock['heading']) ?></h3>
                <p class="text-amber-100 text-xs sm:text-sm max-w-xl font-normal leading-relaxed"><?= htmlspecialchars($autoshipBlock['subtitle']) ?></p>
            </div>
            <div>
                <a href="subscriptions.php" class="px-7 py-3.5 rounded-2xl bg-white hover:bg-slate-100 text-orange-950 font-black text-xs shadow-2xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-orange-600">autorenew</span>
                    <span>مشاهده پلن‌های تحویل دوره‌ای</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- About Section -->
    <?php if (!empty($aboutBlock['enabled'])): ?>
    <section id="about" class="py-16 bg-white border-b border-slate-200/60" data-block-id="about">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-black text-tenant-primary">
                        <span class="w-2.5 h-2.5 rounded-full bg-tenant-primary"></span>
                        <span>معرفی و سوابق رسمی</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900"><?= htmlspecialchars($aboutBlock['heading'] ?? 'درباره ما') ?></h3>
                    <p class="text-slate-600 leading-relaxed text-sm sm:text-base font-normal">
                        <?= nl2br(htmlspecialchars($aboutBlock['text'] ?? '')) ?>
                    </p>

                    <?php if (!empty($aboutBlock['vet_council'])): ?>
                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-600 text-2xl">badge</span>
                        <div>
                            <div class="text-xs font-bold text-slate-800">شماره مجوز و پروانه نظام دامپزشکی</div>
                            <div class="text-sm font-black text-amber-900 font-mono tracking-wider"><?= htmlspecialchars($aboutBlock['vet_council']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($aboutBlock['features']) && is_array($aboutBlock['features'])): ?>
                    <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($aboutBlock['features'] as $feat): ?>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            <span class="material-symbols-outlined text-emerald-600 text-base">check_circle</span>
                            <span><?= htmlspecialchars($feat) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-4 bg-slate-50 p-6 rounded-3xl border border-slate-200 text-center space-y-4 shadow-sm">
                    <div class="w-20 h-20 mx-auto rounded-full bg-tenant-light border-2 border-tenant-primary flex items-center justify-center text-tenant-primary">
                        <span class="material-symbols-outlined text-4xl">verified_user</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-base">ضمانت رسمی پلتفرم آسنا</h4>
                    <p class="text-xs text-slate-500 leading-normal">
                        کلیه خدمات پزشکی و فروشگاهی این وب‌سایت تحت پوشش قوانین امانت‌داری مالی (Escrow) آسنا و تضمین کیفیت ارائه می‌شود.
                    </p>
                    <div class="pt-2">
                        <?php 
                        $profileUrl = match($tenantType) {
                            'doctor' => "doctor_profile.php?id={$tenantId}",
                            'organization' => "organization_profile.php?id={$tenantId}",
                            default => "organizations.php"
                        };
                        ?>
                        <a href="<?= $profileUrl ?>" target="_blank" class="w-full py-2.5 px-4 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors">
                            <span>مشاهده پرونده در شبکه آسنا</span>
                            <span class="material-symbols-outlined text-xs">arrow_left</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Services Block -->
    <?php if (!empty($servicesBlock['enabled']) && !empty($servicesBlock['items'])): ?>
    <section id="services" class="py-16 bg-slate-50 border-b border-slate-200/60" data-block-id="services">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">تخصص‌ها و ظرفیت‌ها</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($servicesBlock['heading'] ?? 'خدمات تخصصی') ?></h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($servicesBlock['items'] as $srv): ?>
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-2xl"><?= htmlspecialchars($srv['icon'] ?? 'star') ?></span>
                    </div>
                    <h4 class="font-black text-slate-900 text-base mb-2"><?= htmlspecialchars($srv['title'] ?? '') ?></h4>
                    <p class="text-xs text-slate-600 leading-relaxed"><?= htmlspecialchars($srv['desc'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Doctors & Specialists Roster Block -->
    <?php if (!empty($doctorsBlock['enabled']) && !empty($tenantDoctors)): ?>
    <section id="doctors" class="py-16 bg-white border-b border-slate-200/60" data-block-id="doctors_roster">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">کادر تخصصی و پزشکان مقیم</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($doctorsBlock['heading'] ?? 'پزشکان و جراحان مرکز') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($doctorsBlock['subtitle'] ?? 'دامپزشکان مجرب با پرونده سلامت ابری و امکان نوبت‌دهی آنلاین') ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($tenantDoctors as $doc): ?>
                <?php 
                    $docId = (int)$doc['id'];
                    $docName = $doc['name'] ?? 'دکتر دامپزشک';
                    $docSpec = $doc['specialty'] ?? 'متخصص داخلی و جراحی حیوانات خانگی';
                    $docAvatar = !empty($doc['avatar']) ? $doc['avatar'] : (!empty($doc['image']) ? $doc['image'] : 'assets/images/default-avatar.png');
                    $isHead = !empty($doc['is_head_physician']);
                ?>
                <div class="bg-slate-50 rounded-3xl p-5 border border-slate-200 hover:border-slate-300 hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                    <div class="text-center">
                        <div class="w-24 h-24 mx-auto rounded-2xl overflow-hidden border-2 border-white shadow-md mb-4 bg-slate-200 relative group-hover:scale-105 transition-transform">
                            <img src="<?= htmlspecialchars($docAvatar) ?>" alt="<?= htmlspecialchars($docName) ?>" class="w-full h-full object-cover">
                            <?php if ($isHead): ?>
                            <span class="absolute bottom-1 right-1 bg-amber-500 text-white p-1 rounded-lg text-[10px]" title="رئیس کادر پزشکی">
                                <span class="material-symbols-outlined text-xs">award_star</span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($isHead): ?>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 mb-2">رئیس کادر درمانی</span>
                        <?php endif; ?>

                        <h4 class="font-black text-slate-900 text-sm mb-1"><?= htmlspecialchars($docName) ?></h4>
                        <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed mb-3"><?= htmlspecialchars($docSpec) ?></p>

                        <?php if (!empty($doc['medical_code']) || !empty($doc['license_number'])): ?>
                        <div class="text-[10px] text-slate-400 font-mono mb-3">
                            کد نظام: <?= htmlspecialchars($doc['medical_code'] ?? $doc['license_number']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-200/80">
                        <a href="booking.php?doctor_id=<?= $docId ?>" class="w-full py-2.5 px-3 rounded-xl bg-tenant-light hover:bg-tenant-primary text-tenant-primary hover:text-white text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                            <span>رزرو مستقیم نوبت</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Booking Widget Block (Doctors & Clinics) -->
    <?php if (!empty($bookingBlock['enabled'])): ?>
    <section id="booking" class="py-16 bg-gradient-to-r from-[#001a48] to-[#042866] text-white border-b border-slate-800" data-block-id="booking">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-3 text-center md:text-right">
                <span class="px-3 py-1 rounded-full bg-white/10 text-amber-300 text-xs font-bold">سامانه نوبت‌دهی آنلاین ۲۴ ساعته</span>
                <h3 class="text-2xl sm:text-3xl font-black"><?= htmlspecialchars($bookingBlock['heading'] ?? 'رزرو اینترنتی نوبت') ?></h3>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl font-normal leading-relaxed">
                    <?= htmlspecialchars($bookingBlock['subtitle'] ?? 'تقویم نوبت‌های آزاد را مشاهده کنید و زمان مناسب خود را بدون فوت وقت رزرو نمایید.') ?>
                </p>
            </div>
            <div>
                <a href="<?= $ctaHref ?>" class="px-8 py-4 rounded-2xl bg-[#fd8100] hover:bg-[#ea580c] text-white font-black text-sm shadow-2xl transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">calendar_today</span>
                    <span>ورود به تقویم نوبت‌دهی</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Storefront & Pharmacy Products Grid (Synced from ASENA Inventory) -->
    <?php if (!empty($storefrontBlock['enabled']) && !empty($tenantProducts)): ?>
    <section id="storefront" class="py-16 bg-white border-b border-slate-200/60" data-block-id="storefront">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
                <div>
                    <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">موجود در انبار اختصاصی</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($storefrontBlock['heading'] ?? 'ویترین محصولات و داروها') ?></h3>
                </div>
                <div class="text-xs text-slate-500 flex items-center gap-1">
                    <span class="material-symbols-outlined text-emerald-600 text-sm">inventory_2</span>
                    <span>موجودی همگام با دیتابیس آسنا</span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <?php foreach ($tenantProducts as $p): ?>
                <?php 
                    $pPrice = (float)($p['price'] ?? 0);
                    $pImg = !empty($p['image_url']) ? $p['image_url'] : (!empty($p['image']) ? $p['image'] : 'assets/images/placeholders/placeholder-product.svg');
                    $isRx = !empty($p['requires_prescription']);
                ?>
                <div class="bg-white rounded-3xl p-4 border border-slate-200 hover:border-slate-300 hover:shadow-xl transition-all flex flex-col justify-between group">
                    <div>
                        <div class="aspect-square rounded-2xl bg-slate-50 overflow-hidden mb-3 relative">
                            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php if ($isRx): ?>
                                <span class="absolute top-2 right-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow">نسخه‌ای (Rx)</span>
                            <?php endif; ?>
                        </div>
                        <h4 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2 mb-1.5"><?= htmlspecialchars($p['name']) ?></h4>
                        <?php if (!empty($p['brand'])): ?>
                            <div class="text-[11px] text-slate-400 font-medium mb-2"><?= htmlspecialchars($p['brand']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-slate-900"><?= number_format($pPrice) ?></span>
                            <span class="text-[10px] text-slate-400">تومان</span>
                        </div>
                        <a href="product_details.php?id=<?= (int)$p['id'] ?>" class="w-8 h-8 rounded-xl bg-tenant-light hover:bg-tenant-primary text-tenant-primary hover:text-white flex items-center justify-center transition-colors" title="مشاهده و خرید">
                            <span class="material-symbols-outlined text-sm">shopping_cart</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Scientific Knowledge Base & Articles Block -->
    <?php if (!empty($articlesBlock['enabled']) && !empty($tenantArticles)): ?>
    <section id="articles" class="py-16 bg-slate-50 border-b border-slate-200/60" data-block-id="articles">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
                <div>
                    <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">دانشنامه علمی سلامت</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($articlesBlock['heading'] ?? 'مقالات و راهنماهای بالینی دامپزشکی') ?></h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1"><?= htmlspecialchars($articlesBlock['subtitle'] ?? 'آموزش‌های کاربردی مراقبت، تغذیه و پیشگیری با تایید علمی') ?></p>
                </div>
                <div>
                    <a href="knowledge_base.php" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700 shadow-sm transition-colors">
                        <span>مشاهده کلیه مقالات</span>
                        <span class="material-symbols-outlined text-xs">arrow_left</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($tenantArticles as $art): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span class="px-2.5 py-0.5 rounded-md bg-tenant-light text-tenant-primary font-bold"><?= htmlspecialchars($art['category_name'] ?? 'پزشکی و سلامت') ?></span>
                            <span class="flex items-center gap-1 font-mono">
                                <span class="material-symbols-outlined text-xs">schedule</span>
                                <span><?= htmlspecialchars($art['read_time'] ?? '۵ دقیقه') ?></span>
                            </span>
                        </div>
                        <h4 class="font-black text-slate-900 text-sm leading-snug group-hover:text-tenant-primary transition-colors">
                            <?= htmlspecialchars($art['title']) ?>
                        </h4>
                        <p class="text-xs text-slate-500 leading-relaxed line-clamp-3">
                            <?= htmlspecialchars($art['short_desc'] ?? '') ?>
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400 text-[11px] font-mono"><?= htmlspecialchars($art['created_at']) ?></span>
                        <a href="knowledge_base.php?article=<?= urlencode($art['slug']) ?>" target="_blank" class="text-tenant-primary font-bold hover:underline flex items-center gap-1">
                            <span>مطالعه کامل</span>
                            <span class="material-symbols-outlined text-xs">arrow_left</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ASENA Loyalty Club Reward Strip -->
    <?php if (!empty($loyaltyBlock['enabled'])): ?>
    <section class="py-10 bg-gradient-to-r from-amber-500 via-amber-600 to-orange-600 text-white shadow-inner border-b border-amber-600" data-block-id="loyalty_club">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4 text-center md:text-right">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center shrink-0 shadow-lg">
                    <span class="material-symbols-outlined text-3xl text-amber-200">loyalty</span>
                </div>
                <div>
                    <div class="inline-block px-3 py-0.5 rounded-full bg-black/20 text-amber-100 text-[10px] font-black mb-1">باشگاه مراجعین وفادار آسنا (ASENA Club)</div>
                    <h3 class="text-lg sm:text-xl font-black"><?= htmlspecialchars($loyaltyBlock['heading'] ?? '۵۰ امتیاز پاداش با هر ثبت نوبت یا خرید آنلاین') ?></h3>
                    <p class="text-xs text-amber-100 mt-1 max-w-xl"><?= htmlspecialchars($loyaltyBlock['subtitle'] ?? 'امتیازهای دریافتی بلافاصله در کیف‌پول ذخیره شده و در ویزیت‌ها و سفارش‌های بعدی به عنوان تخفیف نقدی قابل کسر است.') ?></p>
                </div>
            </div>
            <div>
                <a href="<?= $ctaHref ?>" class="px-6 py-3.5 rounded-xl bg-white text-orange-950 font-black text-xs hover:bg-amber-50 shadow-xl transition-all flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-base text-amber-600">stars</span>
                    <span>شروع دریافت امتیازات</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Verified Patient & Client Reviews -->
    <?php if (!empty($reviewsBlock['enabled']) && !empty($tenantReviews)): ?>
    <section id="reviews" class="py-16 bg-white border-b border-slate-200/60" data-block-id="reviews">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">اعتبار سنجی مراجعین</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($reviewsBlock['heading'] ?? 'نظرات و بازخورد سرپرستان پت') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($reviewsBlock['subtitle'] ?? 'تجربه مراجعین واقعی با استناد به پرونده‌های ثبت‌شده در سامانه آسنا') ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($tenantReviews as $rev): ?>
                <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex text-amber-400">
                                <?php for ($i = 0; $i < (int)($rev['rating'] ?? 5); $i++): ?>
                                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                                <?php endfor; ?>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>ویزیت تأییدشده</span>
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-normal italic">
                            «<?= htmlspecialchars($rev['comment']) ?>»
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-200/80 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-black text-slate-900"><?= htmlspecialchars($rev['user_name'] ?? 'مراجع محترم') ?></div>
                            <?php if (!empty($rev['pet_info'])): ?>
                            <div class="text-[11px] text-slate-400 font-medium flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-xs text-orange-500">pets</span>
                                <span><?= htmlspecialchars($rev['pet_info']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($rev['date'])): ?>
                        <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($rev['date']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact & Operating Hours -->
    <?php if (!empty($contactBlock['enabled'])): ?>
    <section id="contact" class="py-16 bg-slate-50 border-b border-slate-200/60" data-block-id="contact">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-10">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">راه‌های ارتباطی</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1"><?= htmlspecialchars($contactBlock['heading'] ?? 'اطلاعات تماس و نشانی') ?></h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Address -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">location_on</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm mb-1">نشانی مراجعه حضوری</h4>
                        <p class="text-xs text-slate-500 leading-relaxed"><?= htmlspecialchars($contactBlock['address'] ?? 'تهران') ?></p>
                    </div>
                </div>

                <!-- Hours -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">schedule</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm mb-1">ساعات کاری و پذیرش</h4>
                        <p class="text-xs text-slate-500 leading-relaxed"><?= htmlspecialchars($contactBlock['hours'] ?? 'شنبه تا پنجشنبه ۸ الی ۲۲') ?></p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">call</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm mb-1">تلفن‌های تماس</h4>
                        <div class="space-y-1">
                            <?php if (!empty($contactBlock['phone'])): ?>
                                <div><a href="tel:<?= htmlspecialchars($contactBlock['phone']) ?>" class="text-xs font-bold text-slate-700 hover:text-tenant-primary font-mono" dir="ltr"><?= htmlspecialchars($contactBlock['phone']) ?></a></div>
                            <?php endif; ?>
                            <?php if (!empty($contactBlock['emergency_phone'])): ?>
                                <div class="text-[11px] text-red-600 font-bold">اورژانس: <span dir="ltr" class="font-mono"><?= htmlspecialchars($contactBlock['emergency_phone']) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-white py-10 border-t border-slate-200" data-block-id="footer">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-right">
            <div>
                <p class="text-xs text-slate-500 font-medium">
                    <?= htmlspecialchars($footerBlock['copyright_text'] ?? "کلیه حقوق برای {$site['site_title']} محفوظ است.") ?>
                </p>
                <p class="text-[11px] text-slate-400 mt-1">
                    درگاه پرداخت و تسویه الکترونیک تحت لایسنس و نظارت شبکه متمرکز آسنا (ASENA Enterprise).
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.php" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    <img src="assets/images/logo.png" alt="ASENA" class="w-5 h-5 object-contain">
                    <span>قدرت‌گرفته از اکوسیستم ابری آسنا</span>
                </a>
            </div>
        </div>
    </footer>

    <!-- Mobile-First Thumb-Zone Sticky Conversion Bar (< 768px) -->
    <div class="fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-xl border-t border-slate-200/80 p-3 flex md:hidden items-center justify-between gap-3 shadow-2xl">
        <div class="flex items-center gap-2">
            <?php if (!empty($contactBlock['phone'])): ?>
            <a href="tel:<?= htmlspecialchars($contactBlock['phone']) ?>" class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 shadow-sm active:scale-95 transition-transform" title="تماس تلفنی">
                <span class="material-symbols-outlined text-xl">call</span>
            </a>
            <?php endif; ?>
            <div class="text-[11px] leading-tight">
                <span class="font-bold text-slate-800 block truncate max-w-[110px]"><?= htmlspecialchars($site['site_title']) ?></span>
                <span class="text-emerald-600 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    <span>پذیرش فعال</span>
                </span>
            </div>
        </div>

        <a href="<?= $ctaHref ?>" class="flex-1 py-3 px-4 rounded-2xl bg-tenant-primary text-white text-xs font-black shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-1.5 active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-base">calendar_month</span>
            <span><?= htmlspecialchars($mobileBarBlock['cta_text'] ?? 'رزرو آنلاین نوبت') ?></span>
        </a>
    </div>

    <script>
        function toggleMobileDrawer() {
            const drawer = document.getElementById('mobile-drawer');
            if (drawer.classList.contains('hidden')) {
                drawer.classList.remove('hidden');
                drawer.classList.add('flex');
            } else {
                drawer.classList.add('hidden');
                drawer.classList.remove('flex');
            }
        }

        <?php if ($isPreview): ?>
        // PostMessage communication with parent Customizer studio
        document.addEventListener('click', function(e) {
            const blockEl = e.target.closest('[data-block-id]');
            if (blockEl) {
                const blockId = blockEl.getAttribute('data-block-id');
                window.parent.postMessage({ type: 'BLOCK_CLICKED', blockId: blockId }, '*');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
