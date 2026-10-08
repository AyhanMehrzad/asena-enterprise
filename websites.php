<?php
/**
 * ASENA Enterprise - Websites Showcase & Purchasing Catalog
 * Dedicated showcase for customizable tenant websites across 4 distinct archetypes:
 * 1. Doctor (Clinical Authority & Booking)
 * 2. Pharmacy (Cold-Chain & Rx Upload)
 * 3. Pet Shop (E-commerce & Autoship)
 * 4. Organization (Hospital Multi-Department & Emergency)
 * 
 * Version: 2.1.0 - Luxury Overhaul & Role-Aware Adaptive Cockpit
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/AuthGuard.php';

// Only allow users with special roles (doctors, pharmacists, sellers, clinics, admins)
if (php_sapi_name() !== 'cli' && empty($GLOBALS['IS_TEST_SUITE'])) {
    if (!AuthGuard::hasSpecialRole($pdo ?? null)) {
        if (!empty($_SESSION['user_id'])) {
            safe_redirect('/index.php');
        } else {
            safe_redirect('/login.php?return_url=' . urlencode('/websites'));
        }
        exit;
    }
}

$tenantService = App::tenantSite();
$tenantService->ensureDemoSites();

// Detect logged-in user and existing website
$loggedInUserId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;
$existingUserSite = null;
if ($loggedInUserId > 0) {
    $existingUserSite = $tenantService->getSiteForUser($loggedInUserId, $userRole);
}

// Map user role to default selected archetype
$detectedArchetype = 'doctor';
$roleNameFa = 'همکار گرامی';
if ($userRole === 'pharmacist' || $userRole === 'pharmacy') {
    $detectedArchetype = 'pharmacist';
    $roleNameFa = 'داروساز گرامی';
} elseif ($userRole === 'seller') {
    $detectedArchetype = 'seller';
    $roleNameFa = 'فروشنده گرامی';
} elseif ($userRole === 'organization' || $userRole === 'clinic' || $userRole === 'organization_manager') {
    $detectedArchetype = 'organization';
    $roleNameFa = 'مدیر محترم مرکز درمانی';
} elseif ($userRole === 'doctor') {
    $detectedArchetype = 'doctor';
    $roleNameFa = 'پزشک و جراح گرامی';
}

$page_title = 'سفارش و راه‌اندازی وب‌سایت اختصاصی دامپزشکی، داروخانه و پت‌شاپ | آسنا';
$page_desc = 'ساخت فوری وب‌سایت مستقل و حرفه‌ای متناسب با حوزه فعالیت شما: ویژه پزشکان، داروخانه‌ها، پت‌شاپ‌ها و بیمارستان‌های دامپزشکی، متصل به نوبت‌دهی و درگاه شاپرک.';
$canonical_url = 'https://asena.company/websites';

$page_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'پلتفرم ساخت وب‌سایت اختصاصی دامپزشکی، داروخانه و پت‌شاپ آسنا',
    'description' => $page_desc,
    'provider' => [
        '@type' => 'Organization',
        'name' => 'آسنا | ASENA',
        'url' => 'https://asena.company/'
    ],
    'serviceType' => 'Website Development & Veterinary Digital Presence',
    'url' => 'https://asena.company/websites'
];

include __DIR__ . '/includes/header.php';
?>

<style>
    /* ==========================================================================
       ASENA WEBSITES SHOWCASE - REFINED LUXURY DESIGN SYSTEM
       ========================================================================== */
    :root {
        --as-navy: #001a48;
        --as-navy-dark: #001030;
        --as-navy-light: #08296c;
        --as-orange: #fd8100;
        --as-orange-hover: #e67300;
        --as-emerald: #059669;
        --as-purple: #7c3aed;
        --as-ink: #0f172a;
        --as-muted: #475569;
        --as-border: #e2e8f0;
        --as-surface: #ffffff;
        --as-bg: #f8fafc;
    }

    .as-showcase-shell {
        background-color: var(--as-bg);
        color: var(--as-ink);
        font-family: 'Geist', 'Vazirmatn', sans-serif;
        line-height: 1.7;
    }

    /* Hero Section */
    .as-hero-wrap {
        background: linear-gradient(145deg, #001030 0%, #001a48 55%, #08296c 100%);
        color: #ffffff;
        border-radius: 2.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 25px 60px -15px rgba(0, 26, 72, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    .as-hero-mesh {
        position: absolute;
        inset: 0;
        background-image: 
            radial-gradient(at 10% 20%, rgba(253, 129, 0, 0.18) 0px, transparent 45%),
            radial-gradient(at 90% 80%, rgba(5, 150, 105, 0.16) 0px, transparent 45%),
            radial-gradient(at 50% 50%, rgba(124, 58, 237, 0.12) 0px, transparent 50%);
        pointer-events: none;
    }
    .as-hero-grid-pattern {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
        background-size: 24px 24px;
        pointer-events: none;
    }

    /* Role Adaptive Banner */
    .as-role-banner {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1.5rem;
        backdrop-filter: blur(16px);
    }

    /* Subdomain Input Box */
    .as-subdomain-box {
        background: #ffffff;
        border-radius: 1.25rem;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.22);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .as-subdomain-box:focus-within {
        box-shadow: 0 20px 45px rgba(253, 129, 0, 0.25);
    }

    /* Tabs Navigation */
    .as-archetype-tab-btn {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        border-radius: 1rem;
        min-height: 52px;
    }
    .as-archetype-tab-btn.is-active {
        background-color: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(0, 26, 72, 0.12);
        transform: translateY(-2px);
    }

    /* Archetype Card */
    .as-archetype-card {
        background: #ffffff;
        border-radius: 2rem;
        border: 1px solid var(--as-border);
        box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    /* Preview Frames */
    .as-mockup-frame {
        background: #0f172a;
        border-radius: 1.75rem;
        border: 4px solid #1e293b;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
    }
    .as-phone-frame {
        background: #0f172a;
        border-radius: 2.75rem;
        border: 5px solid #334155;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55);
        overflow: hidden;
        max-width: 310px;
        margin: 0 auto;
    }

    /* Bento Cards */
    .as-bento-card {
        background: #ffffff;
        border-radius: 1.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .as-bento-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 40px -12px rgba(0, 26, 72, 0.12);
    }

    /* Pricing Cards */
    .as-pricing-card {
        background: #ffffff;
        border-radius: 2rem;
        border: 2px solid #e2e8f0;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }
    .as-pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 24px 50px -15px rgba(0, 26, 72, 0.14);
    }
    .as-pricing-card.is-featured {
        border-color: var(--as-navy);
        box-shadow: 0 20px 40px -10px rgba(0, 26, 72, 0.16);
    }

    /* Modal Animation */
    @keyframes asModalIn {
        from { opacity: 0; transform: scale(0.96) translateY(12px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .as-modal-content {
        animation: asModalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>

<main class="as-showcase-shell min-h-screen py-6 md:py-10" dir="rtl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16 md:space-y-24">

        <!-- 1. HERO SECTION: Value Proposition & Adaptive Role Detection -->
        <section class="as-hero-wrap p-6 sm:p-10 md:p-16 relative">
            <div class="as-hero-mesh"></div>
            <div class="as-hero-grid-pattern"></div>

            <div class="relative z-10 max-w-4xl mx-auto text-center space-y-6 md:space-y-8">

                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs sm:text-sm font-bold text-amber-300 shadow-md">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="material-symbols-outlined text-base text-amber-400">verified</span>
                    <span>سامانه راه‌اندازی وب‌سایت‌های تخصصی اکوسیستم سلامت آسنا</span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight leading-[1.25] text-white">
                    وب‌سایت اختصاصی، مستقل و مدرن؛<br class="hidden sm:inline">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-amber-300 via-orange-400 to-amber-200">
                        با برند، دامنه و هویت مستقل شما
                    </span>
                </h1>

                <!-- Subheadline -->
                <p class="text-sm sm:text-base md:text-lg text-slate-200 font-medium leading-relaxed max-w-2xl mx-auto">
                    دیگر نیازی به قالب‌های کپی‌شده و هزینه‌های گزاف طراحی نیست. در آسنا، وب‌سایت شما یک پایگاه حرفه‌ای متصل به درگاه شاپرک، نوبت‌دهی آنلاین و پرونده سلامت ابری است؛ طراحی‌شده بر اساس استانداردهای روز دنیا.
                </p>

                <!-- Role Adaptive Greeting Card (Shown for logged-in partners) -->
                <?php if ($loggedInUserId > 0): ?>
                <div class="as-role-banner p-4 sm:p-5 text-right flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center text-amber-300 shrink-0">
                            <span class="material-symbols-outlined text-2xl">account_circle</span>
                        </div>
                        <div>
                            <div class="text-xs text-amber-300 font-bold">حساب متصل: <?= htmlspecialchars($roleNameFa) ?></div>
                            <div class="text-sm sm:text-base font-black text-white">
                                <?= !empty($existingUserSite) ? 'وب‌سایت شما هم‌اکنون فعال و آنلاین است' : 'قالب ویژه حوزه کاری شما آماده راه‌اندازی است' ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($existingUserSite)): ?>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <a href="site.php?slug=<?= urlencode($existingUserSite['slug']) ?>" target="_blank" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 border border-white/20">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            <span>مشاهده وب‌سایت شما</span>
                        </a>
                        <a href="<?= $userRole === 'doctor' ? 'doctor/site_builder.php' : ($userRole === 'seller' ? 'seller/site_builder.php' : ($userRole === 'pharmacist' ? 'pharmacist/site_builder.php' : 'organization/site_builder.php')) ?>" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-[#fd8100] hover:bg-[#e67300] text-white text-xs font-black shadow-md transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">edit_note</span>
                            <span>ورود به استودیو CMS</span>
                        </a>
                    </div>
                    <?php else: ?>
                    <button type="button" onclick="openOrderModal('<?= $detectedArchetype ?>')" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#fd8100] hover:bg-[#e67300] text-white text-xs font-black shadow-lg transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">rocket_launch</span>
                        <span>راه‌اندازی فوری این نسخه</span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Subdomain Availability Checker Engine -->
                <div class="max-w-2xl mx-auto pt-2">
                    <div class="as-subdomain-box p-2 sm:p-2.5">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div class="flex-1 flex items-center bg-slate-50 rounded-xl px-3.5 py-3 border border-slate-200 text-slate-800 focus-within:border-[#fd8100] focus-within:bg-white transition-all">
                                <div class="flex items-center gap-1.5 text-slate-400 pl-2 shrink-0 border-l border-slate-200 ml-2" dir="ltr">
                                    <span class="material-symbols-outlined text-emerald-600 text-base">lock</span>
                                    <span class="font-mono text-xs text-slate-500 font-bold">https://</span>
                                </div>
                                <input type="text" id="hero-subdomain-input" placeholder="نام دلخواه انگلیسی (مثلاً dr-alavi یا petland)" class="w-full bg-transparent border-none outline-none text-right sm:text-left font-mono font-bold text-xs sm:text-sm md:text-base text-slate-900 placeholder:text-slate-400 placeholder:font-sans placeholder:text-xs" autocomplete="off" spellcheck="false">
                                <span class="text-xs sm:text-sm font-mono text-slate-500 font-bold mr-1 shrink-0" dir="ltr">.asena.company</span>
                            </div>
                            <button type="button" onclick="checkSubdomainFromHero()" id="hero-check-btn" class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-[#fd8100] to-orange-500 hover:from-[#e57400] hover:to-orange-600 text-white font-black text-xs sm:text-sm shadow-xl hover:shadow-orange-500/40 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 shrink-0">
                                <span class="material-symbols-outlined text-lg">search_check</span>
                                <span>بررسی وضعیت آدرس</span>
                            </button>
                        </div>
                    </div>

                    <!-- Domain hint & status feedback -->
                    <div class="flex items-center justify-center gap-2 text-slate-300 text-[11px] sm:text-xs mt-3 font-medium">
                        <span class="material-symbols-outlined text-sm text-amber-400">verified</span>
                        <span>امکان اتصال دامنه اختصاصی خودتان (.ir و .com) در تمامی وب‌سایت‌ها فراهم است</span>
                    </div>
                    <div id="hero-subdomain-feedback" class="mt-3 text-xs md:text-sm font-bold hidden transition-all duration-300"></div>
                </div>

                <!-- Trust Anchors Strip -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pt-8 border-t border-white/15 text-right">
                    <div class="p-3.5 rounded-2xl bg-white/[0.08] backdrop-blur-md border border-white/10 flex items-center gap-3">
                        <span class="material-symbols-outlined text-2xl text-amber-300 shrink-0">bolt</span>
                        <div>
                            <div class="text-xs sm:text-sm font-black text-white">تحویل آنلاین ۵ دقیقه‌ای</div>
                            <div class="text-[10px] text-slate-300">راه‌اندازی فوری بدون معطلی</div>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/[0.08] backdrop-blur-md border border-white/10 flex items-center gap-3">
                        <span class="material-symbols-outlined text-2xl text-emerald-300 shrink-0">credit_card</span>
                        <div>
                            <div class="text-xs sm:text-sm font-black text-white">درگاه مستقیم شاپرک</div>
                            <div class="text-[10px] text-slate-300">تسویه روزانه پایا بدون کارمزد</div>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/[0.08] backdrop-blur-md border border-white/10 flex items-center gap-3">
                        <span class="material-symbols-outlined text-2xl text-purple-300 shrink-0">qr_code_2</span>
                        <div>
                            <div class="text-xs sm:text-sm font-black text-white">کارت ویزیت هوشمند QR</div>
                            <div class="text-[10px] text-slate-300">استند رومیزی و کد vCard مطب</div>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/[0.08] backdrop-blur-md border border-white/10 flex items-center gap-3">
                        <span class="material-symbols-outlined text-2xl text-sky-300 shrink-0">domain</span>
                        <div>
                            <div class="text-xs sm:text-sm font-black text-white">دامنه و هاست ۱۰۰٪ مستقل</div>
                            <div class="text-[10px] text-slate-300">اتصال دامنه .ir و .com با SSL</div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 2. CORE SHOWCASE: 4 Dedicated Archetypes with Device Switchers -->
        <section id="archetypes-section" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold">
                    <span class="material-symbols-outlined text-sm text-[#001a48]">palette</span>
                    <span>تنوع کامل ساختاری بر اساس زمینه تخصصی شما</span>
                </div>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                    قالب‌های تخصصی ۴ حوزه فعالیت
                </h2>
                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                    هر صنف نیازمندی‌های خاص خود را دارد. وب‌سایت‌های آسنا با درک عمیق از فرآیندهای پزشکی و تجاری حیوانات، ماژول‌های کاملاً متمایزی برای پزشکان، داروخانه‌ها، پت‌شاپ‌ها و بیمارستان‌ها ارائه می‌دهند.
                </p>
            </div>

            <!-- Archetype Navigation Tabs -->
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 p-1.5 bg-slate-200/70 rounded-2xl max-w-4xl mx-auto border border-slate-300/60 shadow-inner">
                <button type="button" onclick="selectArchetypeTab('doctor')" id="arch-tab-doctor" class="as-archetype-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 text-xs sm:text-sm font-black flex items-center justify-center gap-2 cursor-pointer <?= $detectedArchetype === 'doctor' ? 'is-active text-emerald-800' : 'text-slate-600 hover:text-slate-900' ?>">
                    <span class="material-symbols-outlined text-lg text-emerald-600">stethoscope</span>
                    <span>۱. پزشکان و متخصصین</span>
                </button>
                <button type="button" onclick="selectArchetypeTab('pharmacist')" id="arch-tab-pharmacist" class="as-archetype-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 text-xs sm:text-sm font-black flex items-center justify-center gap-2 cursor-pointer <?= $detectedArchetype === 'pharmacist' ? 'is-active text-purple-800' : 'text-slate-600 hover:text-slate-900' ?>">
                    <span class="material-symbols-outlined text-lg text-purple-600">medication</span>
                    <span>۲. داروخانه‌های تخصصی</span>
                </button>
                <button type="button" onclick="selectArchetypeTab('seller')" id="arch-tab-seller" class="as-archetype-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 text-xs sm:text-sm font-black flex items-center justify-center gap-2 cursor-pointer <?= $detectedArchetype === 'seller' ? 'is-active text-orange-800' : 'text-slate-600 hover:text-slate-900' ?>">
                    <span class="material-symbols-outlined text-lg text-orange-600">storefront</span>
                    <span>۳. پت‌شاپ‌ها و فروشگاه‌ها</span>
                </button>
                <button type="button" onclick="selectArchetypeTab('organization')" id="arch-tab-organization" class="as-archetype-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 text-xs sm:text-sm font-black flex items-center justify-center gap-2 cursor-pointer <?= $detectedArchetype === 'organization' ? 'is-active text-blue-900' : 'text-slate-600 hover:text-slate-900' ?>">
                    <span class="material-symbols-outlined text-lg text-[#001a48]">apartment</span>
                    <span>۴. بیمارستان‌ها و مراکز جامع</span>
                </button>
            </div>

            <!-- Archetype Panels -->
            <div class="relative">

                <!-- 1. Doctor Panel -->
                <div id="arch-panel-doctor" class="arch-panel <?= $detectedArchetype !== 'doctor' ? 'hidden' : '' ?> transition-all duration-300">
                    <div class="as-archetype-card p-6 sm:p-8 md:p-12 border-2 border-emerald-100">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                            <div class="lg:col-span-7 space-y-6 text-right">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black">
                                    <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                                    <span>الگوی تخصصی Clinical Authority: اتوریتی بالینی و نوبت‌دهی آنلاین</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                    وب‌سایت اختصاصی پزشکان، جراحان و متخصصین دامپزشکی
                                </h3>
                                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                    طراحی شده برای معرفی باوقار سوابق علمی، مدارک جراحی، بورد تخصصی و شماره نظام دامپزشکی. مراجعین می‌توانند تایم‌اسلات‌های آزاد ویزیت را آنلاین مشاهده و رزرو کنند، تعرفه‌ها را شفاف محاسبه نمایند و نمونه‌های درمان قبل و بعد را مقایسه کنند.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calendar_month</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">تقویم رزرو نوبت آنلاین</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">بررسی تایم‌اسلات‌های خالی بدون تداخل نوبت با پیامک تایید خودکار.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">compare</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">اسلایدر قبل و بعد درمان</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">نمایش نتایج واقعی جراحی، ارتوپدی و دندانپزشکی با تقسیم‌کننده لمسی.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calculate</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">محاسبه‌گر شفاف تعرفه</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">تخمین هزینه ویزیت و جراحی بر اساس وزن و گونه با ۱۰٪ تخفیف آنلاین.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">badge</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">کارت ویزیت هوشمند QR</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">استند رومیزی پذیرش جهت ذخیره فوری شماره و لوکیشن در موبایل مراجعین.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                    <a href="site.php?slug=dr-alavi" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs sm:text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span>مشاهده دمو زنده وب‌سایت پزشک</span>
                                    </a>
                                    <button type="button" onclick="openOrderModal('doctor')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base text-emerald-400">task_alt</span>
                                        <span>سفارش و راه‌اندازی این قالب</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mockup Desktop / Mobile -->
                            <div class="lg:col-span-5 flex flex-col items-center w-full">
                                <div class="w-full flex items-center justify-between mb-3 px-1">
                                    <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs border border-slate-200">
                                        <button type="button" onclick="switchArchetypeDevice('doctor', 'desktop')" id="arch-dev-btn-doctor-desktop" class="px-3 py-1 rounded-lg font-black text-xs bg-white text-slate-900 shadow-xs flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                                            <span>دسکتاپ</span>
                                        </button>
                                        <button type="button" onclick="switchArchetypeDevice('doctor', 'mobile')" id="arch-dev-btn-doctor-mobile" class="px-3 py-1 rounded-lg font-bold text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">smartphone</span>
                                            <span>موبایل PWA</span>
                                        </button>
                                    </div>
                                    <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>دموی فعال</span>
                                    </span>
                                </div>

                                <!-- Desktop Frame -->
                                <div id="arch-mockup-desktop-doctor" class="w-full as-mockup-frame p-3">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <div class="flex items-center gap-1 text-slate-300 font-bold">
                                            <span class="material-symbols-outlined text-xs text-emerald-400">lock</span>
                                            <span>dr-alavi.asena.company</span>
                                        </div>
                                        <a href="site.php?slug=dr-alavi" target="_blank" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined text-xs">open_in_new</span></a>
                                    </div>
                                    <div class="rounded-xl bg-slate-950 p-4 text-white space-y-3">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-9 h-9 rounded-full bg-emerald-600/30 border border-emerald-500/50 flex items-center justify-center text-emerald-300">
                                                    <span class="material-symbols-outlined text-lg">stethoscope</span>
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-black">کلینیک دکتر محمدرضا علوی</h4>
                                                    <div class="text-[9px] text-slate-400">بورد تخصصی جراحی بافت نرم و ارتوپدی</div>
                                                </div>
                                            </div>
                                            <span class="bg-emerald-500/20 text-emerald-300 text-[9px] px-2 py-0.5 rounded-full font-mono">نظام: ۲۴۵۹۸</span>
                                        </div>
                                        <div class="bg-slate-900 rounded-xl p-2.5 border border-slate-800 space-y-1.5">
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="text-emerald-400 font-bold">نوبت‌های آزاد ویزیت امروز</span>
                                                <span class="text-[9px] text-slate-400">رزرو آنلاین با ۱۰٪ تخفیف</span>
                                            </div>
                                            <div class="grid grid-cols-3 gap-1 text-center text-[10px]">
                                                <div class="bg-emerald-950/80 text-emerald-300 py-1 rounded font-mono font-bold">۱۶:۳۰ امروز</div>
                                                <div class="bg-emerald-950/80 text-emerald-300 py-1 rounded font-mono font-bold">۱۷:۱۵ امروز</div>
                                                <div class="bg-slate-800 text-slate-400 py-1 rounded font-mono">۱۱:۰۰ فردا</div>
                                            </div>
                                        </div>
                                        <a href="site.php?slug=dr-alavi" target="_blank" class="w-full py-2 bg-emerald-700 hover:bg-emerald-600 rounded-lg text-center text-xs font-black transition-colors block text-white">
                                            ورود به وب‌سایت زنده دکتر علوی ↗
                                        </a>
                                    </div>
                                </div>

                                <!-- Mobile PWA Frame -->
                                <div id="arch-mockup-mobile-doctor" class="hidden as-phone-frame p-3 w-full">
                                    <div class="w-20 h-3.5 bg-black rounded-full mx-auto mb-2"></div>
                                    <div class="rounded-xl bg-slate-950 p-3 text-white space-y-2.5 text-center">
                                        <div class="w-9 h-9 rounded-full bg-emerald-600/30 border border-emerald-500/50 mx-auto flex items-center justify-center text-emerald-300">
                                            <span class="material-symbols-outlined text-lg">stethoscope</span>
                                        </div>
                                        <div class="font-black text-xs">دکتر محمدرضا علوی</div>
                                        <div class="text-[9px] text-emerald-300">جراح و متخصص ارتوپدی</div>
                                        <div class="bg-slate-900 rounded-lg p-2 border border-slate-800 text-[10px] text-emerald-400 font-bold">
                                            نوبت آزاد امروز: ۱۶:۳۰
                                        </div>
                                        <a href="site.php?slug=dr-alavi" target="_blank" class="w-full py-1.5 bg-emerald-700 rounded-lg text-center text-[10px] font-black block text-white">
                                            مشاهده در نسخه موبایل ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Pharmacy Panel -->
                <div id="arch-panel-pharmacist" class="arch-panel <?= $detectedArchetype !== 'pharmacist' ? 'hidden' : '' ?> transition-all duration-300">
                    <div class="as-archetype-card p-6 sm:p-8 md:p-12 border-2 border-purple-100">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                            <div class="lg:col-span-7 space-y-6 text-right">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-purple-800 text-xs font-black">
                                    <span class="material-symbols-outlined text-sm text-purple-600">vaccines</span>
                                    <span>الگوی تخصصی Pharma Clean: دراگ‌استور هوشمند، آپلود نسخه و زنجیره سرد</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                    وب‌سایت اختصاصی داروخانه‌های دامپزشکی و توزیع مکمل
                                </h3>
                                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                    سامانه تخصصی برای پذیرش و استعلام سریع نسخه‌های دارویی، عرضه داروهای کمیاب و واکسیناسیون. مجهز به دیده‌بان زنده پایش دمای زنجیره سرد (۲ تا ۸ درجه) و بررسی تداخلات دارویی.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                    <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">document_scanner</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">آپلود سریع عکس نسخه (Rx)</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">پذیرش نسخه با بررسی داروساز مقیم و صدور پیش‌فاکتور زیر ۱۵ دقیقه.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">ac_unit</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">پایش دمای زنجیره سرد</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">تضمین سلامت واکسن با نشانگر استاندارد ۲ تا ۸ درجه سانتی‌گراد.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">warning</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">بررسی تداخلات دارویی</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">هشدار خودکار ناسازگاری داروها و توصیه‌های احتیاطی مصرف به بیمار.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">inventory_2</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">ارسال در بسته‌بندی ایزوله</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">بسته‌بندی یونولیت با ژل یخ برای ارسال فوری داخل‌شهری و پستی.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                    <a href="site.php?slug=sina-pharmacy" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-black text-xs sm:text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span>مشاهده دمو زنده وب‌سایت داروخانه</span>
                                    </a>
                                    <button type="button" onclick="openOrderModal('pharmacist')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base text-purple-400">task_alt</span>
                                        <span>سفارش و راه‌اندازی این قالب</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mockup Desktop / Mobile -->
                            <div class="lg:col-span-5 flex flex-col items-center w-full">
                                <div class="w-full flex items-center justify-between mb-3 px-1">
                                    <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs border border-slate-200">
                                        <button type="button" onclick="switchArchetypeDevice('pharmacist', 'desktop')" id="arch-dev-btn-pharmacist-desktop" class="px-3 py-1 rounded-lg font-black text-xs bg-white text-slate-900 shadow-xs flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                                            <span>دسکتاپ</span>
                                        </button>
                                        <button type="button" onclick="switchArchetypeDevice('pharmacist', 'mobile')" id="arch-dev-btn-pharmacist-mobile" class="px-3 py-1 rounded-lg font-bold text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">smartphone</span>
                                            <span>موبایل PWA</span>
                                        </button>
                                    </div>
                                    <span class="text-xs text-purple-700 font-bold flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                        <span>دموی فعال</span>
                                    </span>
                                </div>

                                <div id="arch-mockup-desktop-pharmacist" class="w-full as-mockup-frame p-3">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <div class="flex items-center gap-1 text-slate-300 font-bold">
                                            <span class="material-symbols-outlined text-xs text-purple-400">lock</span>
                                            <span>sina-pharmacy.asena.company</span>
                                        </div>
                                        <a href="site.php?slug=sina-pharmacy" target="_blank" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined text-xs">open_in_new</span></a>
                                    </div>
                                    <div class="rounded-xl bg-slate-950 p-4 text-white space-y-3">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-9 h-9 rounded-full bg-purple-600/30 border border-purple-500/50 flex items-center justify-center text-purple-300">
                                                    <span class="material-symbols-outlined text-lg">medication</span>
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-black">داروخانه تخصصی دکتر فیروزی (سینا)</h4>
                                                    <div class="text-[9px] text-slate-400">مرکز تأمین واکسن‌ها و داروهای تخصصی</div>
                                                </div>
                                            </div>
                                            <span class="bg-purple-500/20 text-purple-300 text-[9px] px-2 py-0.5 rounded-full font-bold">مجوز رسمی</span>
                                        </div>
                                        <div class="bg-slate-900 rounded-xl p-2.5 border border-slate-800 space-y-2">
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="text-cyan-300 font-bold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">ac_unit</span>
                                                    <span>زنجیره سرد: ۳.۸°C</span>
                                                </span>
                                                <span class="text-[9px] text-emerald-400 font-bold">بازه استاندارد ۲-۸°C</span>
                                            </div>
                                            <div class="p-2 rounded-lg bg-purple-950/40 border border-dashed border-purple-500/40 text-center">
                                                <div class="text-[10px] text-purple-200 font-bold flex items-center justify-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">upload_file</span>
                                                    <span>آپلود سریع عکس نسخه پزشک (Rx)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="site.php?slug=sina-pharmacy" target="_blank" class="w-full py-2 bg-purple-700 hover:bg-purple-600 rounded-lg text-center text-xs font-black transition-colors block text-white">
                                            ورود به وب‌سایت زنده داروخانه سینا ↗
                                        </a>
                                    </div>
                                </div>

                                <div id="arch-mockup-mobile-pharmacist" class="hidden as-phone-frame p-3 w-full">
                                    <div class="w-20 h-3.5 bg-black rounded-full mx-auto mb-2"></div>
                                    <div class="rounded-xl bg-slate-950 p-3 text-white space-y-2.5 text-center">
                                        <div class="w-9 h-9 rounded-full bg-purple-600/30 border border-purple-500/50 mx-auto flex items-center justify-center text-purple-300">
                                            <span class="material-symbols-outlined text-lg">medication</span>
                                        </div>
                                        <div class="font-black text-xs">داروخانه دکتر فیروزی</div>
                                        <div class="text-[9px] text-cyan-300">زنجیره سرد فعال: ۳.۸°C</div>
                                        <div class="bg-slate-900 rounded-lg p-2 border border-slate-800 text-[10px] text-purple-300 font-bold">
                                            پذیرش فوری نسخه
                                        </div>
                                        <a href="site.php?slug=sina-pharmacy" target="_blank" class="w-full py-1.5 bg-purple-700 rounded-lg text-center text-[10px] font-black block text-white">
                                            مشاهده در نسخه موبایل ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Seller / Pet Shop Panel -->
                <div id="arch-panel-seller" class="arch-panel <?= $detectedArchetype !== 'seller' ? 'hidden' : '' ?> transition-all duration-300">
                    <div class="as-archetype-card p-6 sm:p-8 md:p-12 border-2 border-orange-100">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                            <div class="lg:col-span-7 space-y-6 text-right">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-800 text-xs font-black">
                                    <span class="material-symbols-outlined text-sm text-orange-600">shopping_bag</span>
                                    <span>الگوی تخصصی Pet Boutique: فروشگاه آنلاین، تفکیک گونه‌ها و خرید دوره‌ای اتوشیپ</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                    وب‌سایت اختصاصی پت‌شاپ‌ها و هایپرمارکت‌های ملزومات حیوانات
                                </h3>
                                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                    ویترین پیشرفته برای عرضه غذای خشک، کنسرو، تشویقی و بهداشتی. مجهز به فیلتر گونه‌ها (سگ، گربه، پرنده، آبزیان)، انبارداری آنلاین، درگاه پرداخت شاپرک و سرویس خرید ماهانه خودکار (Autoship).
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                    <div class="p-3.5 rounded-xl bg-orange-50/60 border border-orange-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">pets</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">فیلتر گونه و سن حیوان</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">دسترسی فوری مشتری به غذای متناسب با سن، وزن و نژاد حیوان.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-orange-50/60 border border-orange-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">autorenew</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">سرویس تحویل ماهانه (Autoship)</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">تثبیت وفاداری مشتری و ارسال خودکار ماهانه غذای پت با تخفیف دائمی.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-orange-50/60 border border-orange-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">local_fire_department</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">ویترین شگفت‌انگیزها</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">شمارشگر معکوس تخفیف و نمایش لحظه‌ای موجودی برای افزایش فروش.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-orange-50/60 border border-orange-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">account_balance_wallet</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">درگاه شاپرک و تسویه پایا</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">واریز روزانه مبالغ فروش مستقیماً به شماره شبای بانکی شما.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                    <a href="site.php?slug=petland-store" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs sm:text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span>مشاهده دمو زنده وب‌سایت پت‌شاپ</span>
                                    </a>
                                    <button type="button" onclick="openOrderModal('seller')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base text-orange-400">task_alt</span>
                                        <span>سفارش و راه‌اندازی این قالب</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mockup Desktop / Mobile -->
                            <div class="lg:col-span-5 flex flex-col items-center w-full">
                                <div class="w-full flex items-center justify-between mb-3 px-1">
                                    <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs border border-slate-200">
                                        <button type="button" onclick="switchArchetypeDevice('seller', 'desktop')" id="arch-dev-btn-seller-desktop" class="px-3 py-1 rounded-lg font-black text-xs bg-white text-slate-900 shadow-xs flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                                            <span>دسکتاپ</span>
                                        </button>
                                        <button type="button" onclick="switchArchetypeDevice('seller', 'mobile')" id="arch-dev-btn-seller-mobile" class="px-3 py-1 rounded-lg font-bold text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">smartphone</span>
                                            <span>موبایل PWA</span>
                                        </button>
                                    </div>
                                    <span class="text-xs text-orange-700 font-bold flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                        <span>دموی فعال</span>
                                    </span>
                                </div>

                                <div id="arch-mockup-desktop-seller" class="w-full as-mockup-frame p-3">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <div class="flex items-center gap-1 text-slate-300 font-bold">
                                            <span class="material-symbols-outlined text-xs text-orange-400">lock</span>
                                            <span>petland-store.asena.company</span>
                                        </div>
                                        <a href="site.php?slug=petland-store" target="_blank" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined text-xs">open_in_new</span></a>
                                    </div>
                                    <div class="rounded-xl bg-slate-950 p-4 text-white space-y-3">
                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-9 h-9 rounded-full bg-orange-600/30 border border-orange-500/50 flex items-center justify-center text-orange-300">
                                                    <span class="material-symbols-outlined text-lg">shopping_cart</span>
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-black">پت‌شاپ و هایپرمارکت پت‌لند</h4>
                                                    <div class="text-[9px] text-slate-400">غذای خشک، کنسرو، تشویقی و مکمل</div>
                                                </div>
                                            </div>
                                            <span class="bg-orange-500/20 text-orange-300 text-[9px] px-2 py-0.5 rounded-full font-bold">نماد اعتماد</span>
                                        </div>
                                        <div class="bg-slate-900 rounded-xl p-2.5 border border-slate-800 space-y-1.5">
                                            <div class="grid grid-cols-4 gap-1 text-center text-[9px] font-bold">
                                                <div class="bg-orange-600 text-white py-1 rounded">🐶 سگ</div>
                                                <div class="bg-slate-800 text-slate-300 py-1 rounded">🐱 گربه</div>
                                                <div class="bg-slate-800 text-slate-300 py-1 rounded">🦜 پرنده</div>
                                                <div class="bg-slate-800 text-slate-300 py-1 rounded">🐠 آبزیان</div>
                                            </div>
                                            <div class="p-1.5 rounded bg-orange-950/40 border border-orange-500/30 flex items-center justify-between text-[10px]">
                                                <span class="text-orange-300 font-bold">اشتراک ماهانه اتوشیپ</span>
                                                <span class="bg-orange-500 text-white text-[9px] px-1.5 py-0.5 rounded font-black">۱۰٪ تخفیف</span>
                                            </div>
                                        </div>
                                        <a href="site.php?slug=petland-store" target="_blank" class="w-full py-2 bg-orange-600 hover:bg-orange-500 rounded-lg text-center text-xs font-black transition-colors block text-white">
                                            ورود به وب‌سایت زنده پت‌لند ↗
                                        </a>
                                    </div>
                                </div>

                                <div id="arch-mockup-mobile-seller" class="hidden as-phone-frame p-3 w-full">
                                    <div class="w-20 h-3.5 bg-black rounded-full mx-auto mb-2"></div>
                                    <div class="rounded-xl bg-slate-950 p-3 text-white space-y-2.5 text-center">
                                        <div class="w-9 h-9 rounded-full bg-orange-600/30 border border-orange-500/50 mx-auto flex items-center justify-center text-orange-300">
                                            <span class="material-symbols-outlined text-lg">pets</span>
                                        </div>
                                        <div class="font-black text-xs">پت‌شاپ تخصصی پت‌لند</div>
                                        <div class="text-[9px] text-orange-300">ارسال اکسپرس به سراسر کشور</div>
                                        <div class="bg-slate-900 rounded-lg p-2 border border-slate-800 text-[10px] text-orange-300 font-bold">
                                            تخفیف‌های شگفت‌انگیز امروز
                                        </div>
                                        <a href="site.php?slug=petland-store" target="_blank" class="w-full py-1.5 bg-orange-600 rounded-lg text-center text-[10px] font-black block text-white">
                                            مشاهده در نسخه موبایل ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Hospital / Organization Panel -->
                <div id="arch-panel-organization" class="arch-panel <?= $detectedArchetype !== 'organization' ? 'hidden' : '' ?> transition-all duration-300">
                    <div class="as-archetype-card p-6 sm:p-8 md:p-12 border-2 border-blue-100">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                            <div class="lg:col-span-7 space-y-6 text-right">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-900 text-xs font-black">
                                    <span class="material-symbols-outlined text-sm text-blue-700">emergency</span>
                                    <span>الگوی تخصصی Hospital Nexus: اورژانس شبانه‌روزی، تریاژ و ساختار چنددپارتمانه</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                    وب‌سایت اختصاصی بیمارستان‌ها، پلی‌کلینیک‌ها و اورژانس شبانه‌روزی
                                </h3>
                                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                    ساختار قدرتمند سازمانی برای مراکز درمانی با کادر چندنفره، بخش بستری ICU، تصویربرداری و آزمایشگاه. مجهز به نوار قرمز اورژانس، جدول شیفت پزشکان و هاب مسیریابی بلد و نشان به درب بیمارستان.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-rose-600 text-xl shrink-0 mt-0.5">e911_emergency</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">نوار اورژانس و تریاژ ۲۴/۷</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">دکمه تماس اضطراری تک‌لمسی و اعزام سریع آمبولانس حیوانات خانگی.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-blue-700 text-xl shrink-0 mt-0.5">grid_view</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">چیدمان بنتو دپارتمان‌ها</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">معرفی بخش‌های جراحی ارتوپدی، سونوگرافی، رادیولوژی و بانک خون.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-teal-600 text-xl shrink-0 mt-0.5">groups</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">کارتابل معرفی پزشکان</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">نمایش روزهای حضور کادر متخصص با قابلیت رزرو نوبت به تفکیک بخش.</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100/80 flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-indigo-600 text-xl shrink-0 mt-0.5">policy</span>
                                        <div>
                                            <div class="font-black text-xs sm:text-sm text-slate-900">فاکتور رسمی و بیمه</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">صدور فاکتور رسمی نظام دامپزشکی و نمایش لیست بیمه‌های طرف قرارداد.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                    <a href="site.php?slug=razi-hospital" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-[#001a48] hover:bg-[#002666] text-white font-black text-xs sm:text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span>مشاهده دمو زنده وب‌سایت بیمارستان</span>
                                    </a>
                                    <button type="button" onclick="openOrderModal('organization')" class="px-6 py-3.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                        <span class="material-symbols-outlined text-base text-amber-300">task_alt</span>
                                        <span>سفارش و راه‌اندازی این قالب</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mockup Desktop / Mobile -->
                            <div class="lg:col-span-5 flex flex-col items-center w-full">
                                <div class="w-full flex items-center justify-between mb-3 px-1">
                                    <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs border border-slate-200">
                                        <button type="button" onclick="switchArchetypeDevice('organization', 'desktop')" id="arch-dev-btn-organization-desktop" class="px-3 py-1 rounded-lg font-black text-xs bg-white text-slate-900 shadow-xs flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                                            <span>دسکتاپ</span>
                                        </button>
                                        <button type="button" onclick="switchArchetypeDevice('organization', 'mobile')" id="arch-dev-btn-organization-mobile" class="px-3 py-1 rounded-lg font-bold text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer transition-all">
                                            <span class="material-symbols-outlined text-sm">smartphone</span>
                                            <span>موبایل PWA</span>
                                        </button>
                                    </div>
                                    <span class="text-xs text-blue-800 font-bold flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                        <span>دموی فعال</span>
                                    </span>
                                </div>

                                <div id="arch-mockup-desktop-organization" class="w-full as-mockup-frame p-3">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <div class="flex items-center gap-1 text-slate-300 font-bold">
                                            <span class="material-symbols-outlined text-xs text-teal-400">lock</span>
                                            <span>razi-hospital.asena.company</span>
                                        </div>
                                        <a href="site.php?slug=razi-hospital" target="_blank" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined text-xs">open_in_new</span></a>
                                    </div>
                                    <div class="rounded-xl bg-slate-950 p-4 text-white space-y-2.5">
                                        <div class="p-2 rounded-lg bg-rose-900/80 border border-rose-500/50 flex items-center justify-between text-xs animate-pulse">
                                            <span class="font-bold flex items-center gap-1 text-rose-200">
                                                <span class="material-symbols-outlined text-sm">emergency</span>
                                                <span>اورژانس و تریاژ ۲۴/۷ فعال</span>
                                            </span>
                                            <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded-full font-bold">تماس فوری</span>
                                        </div>
                                        <div class="border-b border-slate-800 pb-2">
                                            <h4 class="text-xs font-black">بیمارستان تخصصی دامپزشکی رازی</h4>
                                            <div class="text-[9px] text-slate-400">مجتمع درمانی ارجاعی و جراحی پیشرفته</div>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1 text-center text-[9px] font-bold">
                                            <div class="bg-blue-900/80 text-blue-200 py-1 rounded">جراحی</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded">رادیولوژی</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded">بستری ICU</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded">آزمایشگاه</div>
                                        </div>
                                        <a href="site.php?slug=razi-hospital" target="_blank" class="w-full py-2 bg-[#001a48] hover:bg-[#022869] border border-white/20 rounded-lg text-center text-xs font-black transition-colors block text-white">
                                            ورود به وب‌سایت زنده بیمارستان رازی ↗
                                        </a>
                                    </div>
                                </div>

                                <div id="arch-mockup-mobile-organization" class="hidden as-phone-frame p-3 w-full">
                                    <div class="w-20 h-3.5 bg-black rounded-full mx-auto mb-2"></div>
                                    <div class="rounded-xl bg-slate-950 p-3 text-white space-y-2.5 text-center">
                                        <div class="p-1 rounded bg-rose-900 text-[9px] text-rose-200 font-bold">🚨 تریاژ ۲۴ ساعته فعال</div>
                                        <div class="font-black text-xs">بیمارستان تخصصی رازی</div>
                                        <div class="text-[9px] text-teal-300">کادر درمانی چندتخصصی و ICU</div>
                                        <a href="site.php?slug=razi-hospital" target="_blank" class="w-full py-1.5 bg-[#001a48] rounded-lg text-center text-[10px] font-black block text-white">
                                            مشاهده در نسخه موبایل ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 3. PLATFORM CAPABILITIES: 6-Card Bento Grid -->
        <section class="space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold">
                    <span class="material-symbols-outlined text-sm">auto_awesome</span>
                    <span>امکانات استاندارد موجود در تمام وب‌سایت‌های آسنا</span>
                </div>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                    همه آنچه برای یک حضور دیجیتال بی‌نقص نیاز دارید
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    بدون هزینه‌های پنهان یا نیازمندی به هاست و سرور جداگانه؛ هر وب‌سایت آسنا به صورت یک پکیج جامع تحویل داده می‌شود.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Bento 1 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">style</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">استودیو ویرایش بدون کد (Visual Studio)</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        با استودیوی اختصاصی آسنا، در هر ساعت از شبانه‌روز می‌توانید متون، لوگو، تصاویر، رنگ‌بندی و جایگاه بلوک‌ها را با پیش‌نمایش آنی شخصی‌سازی کنید.
                    </p>
                </div>

                <!-- Bento 2 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">کارت ویزیت دیجیتال و استند رومیزی QR</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        همراه با وب‌سایت، کارت ویزیت هوشمند با فایل مستقیم مخاطب (vCard) در اختیار شماست تا مراجعین با اسکن آن در مطب، اطلاعات شما را در گوشی ذخیره کنند.
                    </p>
                </div>

                <!-- Bento 3 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">verified_user</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">درگاه رسمی شاپرک و تسویه پایا</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        تسویه حساب مستقیم بانکی، صدور فاکتور رسمی نظام دامپزشکی، گزارش فصلی ماده ۱۶۹ و امنیت مالی ۱۰۰٪ با سیستم ضمانت امن (Escrow).
                    </p>
                </div>

                <!-- Bento 4 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">travel_explore</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">سئو تضمینی گوگل (Local SEO)</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        تولید خودکار داده‌های ساختاریافته Schema.org، ثبت ساعات کاری، لوکیشن نقشه و مقالات وبلاگ برای حضور در نتایج صفحه اول جستجوی محلی گوگل.
                    </p>
                </div>

                <!-- Bento 5 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">install_mobile</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">فول PWA و عملکرد فوق‌سریع در موبایل</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        وب‌سایت شما دقیقاً شبیه یک اپلیکیشن بومی روی گوشی‌های اندروید و iOS نصب می‌شود و در شرایط اینترنت ضعیف نیز عملکرد باثباتی دارد.
                    </p>
                </div>

                <!-- Bento 6 -->
                <div class="as-bento-card p-6 md:p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">sms</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">اتوماسیون پیامکی نوبت‌ها و یادآوری واکسن</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        ارسال خودکار پیامک یادآوری وقت ویزیت، پیامک تایید سفارش دارو و یادآوری دوره‌ای واکسیناسیون و ضدانگل به مراجعین بدون نیاز به دخالت دستی.
                    </p>
                </div>
            </div>
        </section>

        <!-- 4. TRANSPARENT EDITIONS PRICING -->
        <section id="website-editions" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-black">
                    <span class="material-symbols-outlined text-sm text-indigo-600">tune</span>
                    <span>بدون هزینه پنهان و بدون قابلیت قفل‌شده</span>
                </div>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                    نسخه‌های تخصصی وب‌سایت آسنا
                </h2>
                <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto">
                    تمامی نسخه‌ها شامل ۱۰۰٪ زیرساخت کامل (هاست نامحدود، دامنه مستقل، درگاه شاپرک، SSL و پشتیبانی) هستند؛ تفاوت آن‌ها تنها در ماژول‌های ویژه متناسب با صنف کاری شماست.
                </p>
            </div>

            <!-- Shared Guarantee Banner -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900 text-white border border-slate-800 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4 text-xs sm:text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-400/40 flex items-center justify-center text-amber-300 shrink-0">
                        <span class="material-symbols-outlined text-2xl">verified</span>
                    </div>
                    <div>
                        <div class="font-black text-white text-sm sm:text-base">تضمین ۱۰۰٪ زیرساخت کامل در تمامی نسخه‌ها</div>
                        <div class="text-slate-300 text-xs mt-0.5 leading-relaxed">دامنه مستقل (.ir / .com) • هاست ابری نامحدود با SSL رایگان • درگاه مستقیم شاپرک • استودیو ویرایشگر بدون کد • پشتیبانی فنی مداوم</div>
                    </div>
                </div>
                <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white/10 text-amber-300 font-bold text-xs shrink-0">
                    <span class="material-symbols-outlined text-sm">lock_open</span>
                    <span>۱۰۰٪ فول امکانات</span>
                </div>
            </div>

            <!-- 4 Pricing Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Edition 1: Doctor -->
                <div class="as-pricing-card p-6 sm:p-7 flex flex-col justify-between space-y-6 <?= $detectedArchetype === 'doctor' ? 'is-featured ring-2 ring-emerald-500' : '' ?>">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-black flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">stethoscope</span>
                                <span>ویژه پزشکان و جراحان</span>
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-400">نسخه ۱</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900">مطب و نوبت‌دهی بالینی</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">اتوریتی پزشکی و نوبت‌دهی آنلاین بدون اتلاف وقت منشی مطب.</p>
                        </div>
                        <div class="py-3 border-y border-slate-100">
                            <div class="text-2xl sm:text-3xl font-black text-emerald-800 font-mono">۷,۹۰۰,۰۰۰</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-700">
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span><span>تقویم نوبت‌دهی آنلاین و پایش تایم‌اسلات‌ها</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span><span>اسلایدر تعاملی درمان قبل و بعد (Before/After)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span><span>محاسبه‌گر تعرفه خدمات با ۱۰٪ تخفیف آنلاین</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span><span>کارت ویزیت هوشمند QR و استند رومیزی مطب</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span><span>ارسال پیامک تایید و یادآوری ویزیت به بیمار</span></li>
                        </ul>
                    </div>
                    <button type="button" onclick="openOrderModal('doctor')" class="w-full py-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <span class="material-symbols-outlined text-base">task_alt</span>
                        <span>انتخاب نسخه مطب و پزشکان</span>
                    </button>
                </div>

                <!-- Edition 2: Pharmacy -->
                <div class="as-pricing-card p-6 sm:p-7 flex flex-col justify-between space-y-6 <?= $detectedArchetype === 'pharmacist' ? 'is-featured ring-2 ring-purple-500' : '' ?>">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-[11px] font-black flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">medication</span>
                                <span>ویژه داروخانه‌ها و مکمل‌ها</span>
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-400">نسخه ۲</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900">داروخانه و دراگ‌استور هوشمند</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">سامانه پذیرش نسخه، پایش زنجیره سرد و عرضه داروهای کمیاب.</p>
                        </div>
                        <div class="py-3 border-y border-slate-100">
                            <div class="text-2xl sm:text-3xl font-black text-purple-800 font-mono">۹,۴۰۰,۰۰۰</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-700">
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span><span>سامانه آپلود و پذیرش عکس نسخه پزشک (Rx)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span><span>دیده‌بان زنده دمای زنجیره سرد (۲ تا ۸ درجه)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span><span>کاتالوگ آنلاین واکسن‌ها، مکمل‌ها و داروهای خاص</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span><span>پایشگر هوشمند تداخلات دارویی و هشدارهای دوز</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span><span>ارسال سریع در بسته‌بندی عایق یونولیت و ژل یخ</span></li>
                        </ul>
                    </div>
                    <button type="button" onclick="openOrderModal('pharmacist')" class="w-full py-3.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <span class="material-symbols-outlined text-base">task_alt</span>
                        <span>انتخاب نسخه داروخانه تخصصی</span>
                    </button>
                </div>

                <!-- Edition 3: Pet Shop -->
                <div class="as-pricing-card p-6 sm:p-7 flex flex-col justify-between space-y-6 <?= $detectedArchetype === 'seller' ? 'is-featured ring-2 ring-orange-500' : '' ?>">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-800 text-[11px] font-black flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">pets</span>
                                <span>ویژه پت‌شاپ‌ها و فروشگاه</span>
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-400">نسخه ۳</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900">پت‌شاپ و فروشگاه ملزومات</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">فروشگاه آنلاین با امکان خرید دوره‌ای خودکار جهت ایجاد درآمد ماهانه پایدار.</p>
                        </div>
                        <div class="py-3 border-y border-slate-100">
                            <div class="text-2xl sm:text-3xl font-black text-orange-800 font-mono">۱۰,۸۰۰,۰۰۰</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-700">
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span><span>سرویس خرید ماهانه دوره‌ای با تخفیف (Autoship)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span><span>فیلتر گونه حیوان (سگ، گربه، پرنده، آبزیان)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span><span>ویترین شگفت‌انگیزها و شمارشگر معکوس تخفیف</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span><span>انبارداری آنلاین و هشدار هوشمند موجودی کالا</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span><span>تسویه مستقیم شاپرک و واریز روزانه پایا به شبا</span></li>
                        </ul>
                    </div>
                    <button type="button" onclick="openOrderModal('seller')" class="w-full py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <span class="material-symbols-outlined text-base">task_alt</span>
                        <span>انتخاب نسخه پت‌شاپ و فروشگاه</span>
                    </button>
                </div>

                <!-- Edition 4: Hospital -->
                <div class="as-pricing-card p-6 sm:p-7 flex flex-col justify-between space-y-6 bg-slate-950 text-white border-blue-900/60 <?= $detectedArchetype === 'organization' ? 'is-featured ring-2 ring-blue-500' : '' ?>">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-400/30 text-[11px] font-black flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">emergency</span>
                                <span>ویژه بیمارستان‌ها و مراکز جامع</span>
                            </span>
                            <span class="text-xs font-mono font-bold text-amber-300">نسخه ۴</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white">بیمارستان و اورژانس شبانه‌روزی</h3>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">اکوسیستم مراکز درمانی با کادر چندنفره، دپارتمان‌های تخصصی و بستری.</p>
                        </div>
                        <div class="py-3 border-y border-white/10">
                            <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono">۱۶,۵۰۰,۰۰۰</div>
                            <div class="text-[11px] text-slate-300 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی VIP)</div>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-200">
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span><span>نوار قرمز اورژانس شبانه‌روزی و اعزام آمبولانس</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span><span>ساختار چنددپارتمانه بنتو (جراحی، رادیولوژی، ICU)</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span><span>کارتابل معرفی پزشکان متخصص و جدول شیفت‌ها</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span><span>قوانین بستری، شرایط ناشتایی و بیمه‌های طرف قرارداد</span></li>
                            <li class="flex items-start gap-2"><span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span><span>پشتیبانی VIP و مانیتورینگ اختصاصی سرور با توافق SLA</span></li>
                        </ul>
                    </div>
                    <button type="button" onclick="openOrderModal('organization')" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-[#fd8100] to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs shadow-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <span class="material-symbols-outlined text-base">task_alt</span>
                        <span>انتخاب نسخه بیمارستان و اورژانس</span>
                    </button>
                </div>

            </div>
        </section>

        <!-- 5. THREE SIMPLE STEPS TO LAUNCH -->
        <section class="as-bento-card p-8 sm:p-12 space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">از انتخاب تا انتشار، تنها ۳ گام ساده</h2>
                <p class="text-xs sm:text-sm text-slate-500">بدون نیاز به کدنویسی، همه‌چیز با پشتیبانی تخصصی کارشناسان آسنا پیاده‌سازی می‌شود.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-right">
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-4">
                    <span class="w-10 h-10 rounded-full bg-[#fd8100] text-white flex items-center justify-center font-black text-base shrink-0 shadow-md">۱</span>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 mb-1">انتخاب قالب و رزرو آدرس</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">قالب مناسب صنف خود را انتخاب کرده و شناسه ساب‌دامین اختصاصی خود را استعلام و ثبت می‌کنید.</p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-4">
                    <span class="w-10 h-10 rounded-full bg-[#001a48] text-white flex items-center justify-center font-black text-base shrink-0 shadow-md">۲</span>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 mb-1">تکمیل محتوا و هویت برند</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">اطلاعات مرکز، لوگو، خدمات، کالاها و ساعات کاری وارد شده و دامنه اختصاصی .ir یا .com متصل می‌گردد.</p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-4">
                    <span class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-base shrink-0 shadow-md">۳</span>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 mb-1">تحویل فوری و آغاز فعالیت</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">سایت منتشر شده و مراجعین می‌توانند آنلاین نوبت بگیرند، خرید کنند و به درگاه متصل شوند.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ ACCORDION -->
        <section class="max-w-4xl mx-auto space-y-6">
            <div class="text-center space-y-2">
                <h2 class="text-xl sm:text-3xl font-black text-slate-900">پرسش‌های متداول</h2>
                <p class="text-xs sm:text-sm text-slate-500">پاسخ شفاف به دغدغه‌های اصلی متقاضیان راه‌اندازی وب‌سایت در آسنا</p>
            </div>

            <div class="space-y-3">
                <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-sm transition-all">
                    <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                        <span>آیا می‌توانم دامنه اختصاصی مطب یا فروشگاه خودم (.ir یا .com) را وصل کنم؟</span>
                        <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                        بله، ۱۰۰٪. تمام وب‌سایت‌های آسنا به صورت پیش‌فرض یک ساب‌دامین رایگان (yourname.asena.company) دریافت می‌کنند، اما شما در هر زمان می‌توانید دامنه ملی (.ir) یا بین‌المللی (.com) خود را به سادگی به وب‌سایت متصل نمایید و گواهی SSL امن به صورت خودکار برای شما فعال خواهد شد.
                    </div>
                </details>

                <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-sm transition-all">
                    <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                        <span>چقدر طول می‌کشد تا وب‌سایت من آماده و فعال شود؟</span>
                        <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                        وب‌سایت شما بلافاصله پس از ثبت سفارش در کمتر از ۵ دقیقه ایجاد و فعال می‌گردد. همچنین اطلاعات اولیه و شخصی‌سازی ظاهر سایت ظرف حداکثر ۲۴ ساعت کاری با همراهی کارشناسان آسنا تکمیل و تحویل داده می‌شود.
                    </div>
                </details>

                <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-sm transition-all">
                    <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                        <span>تسویه حساب مبالغ پرداختی مراجعین چگونه انجام می‌شود؟</span>
                        <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                        کلیه پرداخت‌های رزرو نوبت یا خرید کالا از طریق درگاه مستقیم بانکی شاپرک آسنا انجام شده و وجوه حاصل به صورت خودکار و روزانه از طریق حواله پایا به شماره شبای بانکی شما واریز می‌گردد.
                    </div>
                </details>

                <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-sm transition-all">
                    <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                        <span>آیا برای هاست یا سرور باید هزینه جداگانه‌ای پرداخت شود؟</span>
                        <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                        خیر. کلیه هزینه‌های میزبانی ابری با ترافیک نامحدود، پشتیبان‌گیری منظم روزانه، گواهی امنیتی SSL و پشتیبانی فنی مداوم در همان اشتراک سالانه گنجانده شده است و هیچ هزینه پنهانی وجود ندارد.
                    </div>
                </details>
            </div>
        </section>

        <!-- 7. FINAL CONVERSION BANNER -->
        <section class="rounded-3xl bg-gradient-to-r from-[#001a48] via-[#002666] to-[#001a48] p-8 sm:p-12 text-white text-center space-y-4 shadow-xl border border-white/10">
            <h2 class="text-2xl sm:text-3xl font-black">آماده‌اید وب‌سایت اختصاصی خود را تحویل بگیرید؟</h2>
            <p class="text-xs sm:text-base text-slate-200 max-w-xl mx-auto font-medium">
                همین حالا نام مورد نظرتان را رزرو کنید و با راه‌اندازی سریع وب‌سایت، یک گام بزرگ به سوی اتوماسیون مرکز خود و جذب مراجعین بیشتر بردارید.
            </p>
            <div class="pt-2">
                <button type="button" onclick="openOrderModal()" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-[#fd8100] to-orange-500 hover:from-[#e57400] hover:to-orange-600 text-white font-black text-sm shadow-xl transition-all cursor-pointer inline-flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-white text-lg">rocket_launch</span>
                    <span>ثبت سفارش و راه‌اندازی آنلاین</span>
                </button>
            </div>
        </section>

    </div>
</main>

<!-- ==========================================================================
     ORDER & PURCHASE MODAL (Matched to automated tests)
     ========================================================================== -->
<div id="websiteOrderModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-[2rem] max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6 relative text-right as-modal-content my-8">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#fd8100]">web</span>
                    <span>ثبت سفارش و راه‌اندازی وب‌سایت اختصاصی</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">تکمیل فرم در ۲ دقیقه؛ تحویل فوری با پشتیبانی کارشناسان آسنا</p>
            </div>
            <button type="button" onclick="closeOrderModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors cursor-pointer" aria-label="بستن">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="websiteOrderForm" onsubmit="submitWebsiteOrder(event)" class="space-y-4">
            
            <!-- Edition Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">۱. انتخاب نسخه تخصصی وب‌سایت *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50 hover:border-emerald-300 transition-all">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="order_archetype" value="doctor" data-tier="standard" checked class="text-emerald-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-emerald-600 text-sm">stethoscope</span>
                                    <span>مطب و نوبت‌دهی بالینی</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">پزشکان و جراحان</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-emerald-800 bg-white px-2 py-1 rounded-lg border border-emerald-100">۷,۹۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50/50 hover:border-purple-300 transition-all">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="order_archetype" value="pharmacist" data-tier="pharmacy" class="text-purple-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-purple-600 text-sm">medication</span>
                                    <span>داروخانه و دراگ‌استور</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">نسخه و زنجیره سرد</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-purple-800 bg-white px-2 py-1 rounded-lg border border-purple-100">۹,۴۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50/50 hover:border-orange-300 transition-all">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="order_archetype" value="seller" data-tier="premium" class="text-orange-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-orange-600 text-sm">pets</span>
                                    <span>پت‌شاپ و فروشگاه</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">اتوشیپ و انبارداری</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-orange-800 bg-white px-2 py-1 rounded-lg border border-orange-100">۱۰,۸۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50/50 hover:border-blue-300 transition-all">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="order_archetype" value="organization" data-tier="enterprise" class="text-blue-700" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-blue-700 text-sm">emergency</span>
                                    <span>بیمارستان و اورژانس</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">چنددپارتمانه و ۲۴ ساعته</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-blue-800 bg-white px-2 py-1 rounded-lg border border-blue-100">۱۶,۵۰۰,۰۰۰ ت</span>
                    </label>
                </div>
                <input type="hidden" name="order_tier" id="order_tier" value="standard">
            </div>

            <!-- Subdomain Slug -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">۲. شناسه و آدرس ساب‌دامین دلخواه *</label>
                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-[#fd8100]">
                    <span class="px-3 text-xs text-slate-400 font-mono bg-slate-50 border-l border-slate-200">.asena.company</span>
                    <input type="text" name="order_desired_slug" id="order_desired_slug" oninput="checkOrderSlug(this.value)" placeholder="مثلاً dr-alavi" class="flex-1 text-xs p-3 outline-none font-mono text-left font-bold" dir="ltr" required>
                </div>
                <div id="order-slug-feedback" class="text-[11px] mt-1 text-slate-400">حداقل ۳ کاراکتر انگلیسی بدون فاصله</div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">نام و نام خانوادگی / نام مرکز *</label>
                    <input type="text" name="order_full_name" required placeholder="مثلاً دکتر محمدرضا علوی" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-[#fd8100] focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">شماره تماس همراه (جهت هماهنگی) *</label>
                    <input type="tel" name="order_phone" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-[#fd8100] focus:outline-none font-mono font-bold text-left">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">ایمیل (اختیاری)</label>
                <input type="email" name="order_email" placeholder="info@example.com" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-[#fd8100] focus:outline-none font-mono text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">توضیحات یا یادداشت ویژه (اختیاری)</label>
                <textarea name="order_notes" rows="2" placeholder="اگر دامنه ملی خاصی مدنظر دارید یا توضیحی برای تیم فنی دارید بنویسید..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-[#fd8100] focus:outline-none"></textarea>
            </div>

            <div id="order-form-feedback" class="hidden text-xs font-bold p-3 rounded-xl"></div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <button type="button" onclick="closeOrderModal()" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                    انصراف
                </button>
                <button type="submit" id="order-submit-btn" class="px-7 py-3 rounded-xl bg-gradient-to-r from-[#fd8100] to-orange-500 hover:from-[#e57400] hover:to-orange-600 text-white font-black text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span>تایید و ارسال درخواست راه‌اندازی</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// ============================================================================
// ARCHETYPE TAB SWITCHER
// ============================================================================
function selectArchetypeTab(archetype) {
    document.querySelectorAll('.arch-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.as-archetype-tab-btn').forEach(b => {
        b.classList.remove('is-active', 'text-emerald-800', 'text-purple-800', 'text-orange-800', 'text-blue-900');
        b.classList.add('text-slate-600');
    });

    const activePanel = document.getElementById('arch-panel-' + archetype);
    const activeBtn = document.getElementById('arch-tab-' + archetype);

    if (activePanel) activePanel.classList.remove('hidden');
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600');
        activeBtn.classList.add('is-active');
        if (archetype === 'doctor') activeBtn.classList.add('text-emerald-800');
        else if (archetype === 'pharmacist') activeBtn.classList.add('text-purple-800');
        else if (archetype === 'seller') activeBtn.classList.add('text-orange-800');
        else if (archetype === 'organization') activeBtn.classList.add('text-blue-900');
    }
}

// ============================================================================
// DEVICE SWITCHER (DESKTOP / MOBILE PWA)
// ============================================================================
function switchArchetypeDevice(archetype, device) {
    const desktopFrame = document.getElementById('arch-mockup-desktop-' + archetype);
    const mobileFrame = document.getElementById('arch-mockup-mobile-' + archetype);
    const desktopBtn = document.getElementById('arch-dev-btn-' + archetype + '-desktop');
    const mobileBtn = document.getElementById('arch-dev-btn-' + archetype + '-mobile');

    if (!desktopFrame || !mobileFrame) return;

    if (device === 'mobile') {
        desktopFrame.classList.add('hidden');
        mobileFrame.classList.remove('hidden');

        if (desktopBtn && mobileBtn) {
            desktopBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'font-black');
            desktopBtn.classList.add('text-slate-500', 'font-bold');

            mobileBtn.classList.remove('text-slate-500', 'font-bold');
            mobileBtn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'font-black');
        }
    } else {
        mobileFrame.classList.add('hidden');
        desktopFrame.classList.remove('hidden');

        if (desktopBtn && mobileBtn) {
            mobileBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'font-black');
            mobileBtn.classList.add('text-slate-500', 'font-bold');

            desktopBtn.classList.remove('text-slate-500', 'font-bold');
            desktopBtn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'font-black');
        }
    }
}

// ============================================================================
// EDITION TIER SELECTOR
// ============================================================================
function updateSelectedEditionTier(radioEl) {
    const tierInput = document.getElementById('order_tier');
    if (radioEl && tierInput) {
        tierInput.value = radioEl.getAttribute('data-tier') || 'standard';
    }
}

// ============================================================================
// HERO SUBDOMAIN LIVE CHECKER
// ============================================================================
function checkSubdomainFromHero() {
    const input = document.getElementById('hero-subdomain-input');
    const feedback = document.getElementById('hero-subdomain-feedback');
    const slug = (input ? input.value : '').trim();

    if (!slug) {
        feedback.className = 'mt-3 text-xs md:text-sm font-bold text-amber-300 block';
        feedback.innerText = 'لطفاً یک نام انگلیسی وارد کنید (مثلاً dr-alavi).';
        return;
    }

    feedback.className = 'mt-3 text-xs md:text-sm font-bold text-slate-200 block';
    feedback.innerText = 'در حال استعلام در سامانه آسنا...';

    const formData = new FormData();
    formData.append('action', 'check_slug');
    formData.append('slug', slug);

    fetch('actions/website_order_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.available) {
            feedback.className = 'mt-3 text-xs md:text-sm font-black text-emerald-300 block';
            feedback.innerHTML = `✓ ${data.message} <button type="button" onclick="openOrderModal(null, null, '${data.slug}')" class="underline mr-2 text-white font-black hover:text-amber-200 cursor-pointer">سفارش و ثبت فوری</button>`;
        } else {
            feedback.className = 'mt-3 text-xs md:text-sm font-bold text-rose-300 block';
            feedback.innerText = '✕ ' + (data.message || 'این آدرس قبلاً ثبت شده است.');
        }
    })
    .catch(() => {
        feedback.className = 'mt-3 text-xs md:text-sm font-bold text-rose-300 block';
        feedback.innerText = 'خطا در ارتباط با سرور.';
    });
}

// ============================================================================
// ORDER MODAL MANAGEMENT
// ============================================================================
function openOrderModal(archetype = null, tier = null, slug = null) {
    const modal = document.getElementById('websiteOrderModal');
    if (!modal) return;
    
    if (archetype) {
        const rad = modal.querySelector(`input[name="order_archetype"][value="${archetype}"]`);
        if (rad) {
            rad.checked = true;
            updateSelectedEditionTier(rad);
        }
    }

    if (tier) {
        const tierInput = document.getElementById('order_tier');
        if (tierInput) tierInput.value = tier;
    }

    if (slug) {
        const slugInput = document.getElementById('order_desired_slug');
        if (slugInput) {
            slugInput.value = slug;
            checkOrderSlug(slug);
        }
    } else {
        const heroInput = document.getElementById('hero-subdomain-input');
        const slugInput = document.getElementById('order_desired_slug');
        if (heroInput && heroInput.value.trim() && slugInput && !slugInput.value.trim()) {
            slugInput.value = heroInput.value.trim();
            checkOrderSlug(slugInput.value);
        }
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    const modal = document.getElementById('websiteOrderModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Modal live slug checker
let orderSlugTimer = null;
function checkOrderSlug(val) {
    clearTimeout(orderSlugTimer);
    const feedback = document.getElementById('order-slug-feedback');
    if (!val || val.trim().length < 3) {
        if (feedback) {
            feedback.innerText = 'حداقل ۳ کاراکتر انگلیسی وارد کنید.';
            feedback.className = 'text-[11px] mt-1 text-slate-400';
        }
        return;
    }

    orderSlugTimer = setTimeout(() => {
        const formData = new FormData();
        formData.append('action', 'check_slug');
        formData.append('slug', val.trim());

        fetch('actions/website_order_action.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!feedback) return;
            if (data.available) {
                feedback.innerText = `✓ شناسه ${data.slug}.asena.company آزاد است.`;
                feedback.className = 'text-[11px] mt-1 text-emerald-600 font-bold';
            } else {
                feedback.innerText = `✕ ${data.message}`;
                feedback.className = 'text-[11px] mt-1 text-rose-600 font-bold';
            }
        })
        .catch(() => {});
    }, 350);
}

// Order Form Submission
function submitWebsiteOrder(e) {
    e.preventDefault();
    const form = document.getElementById('websiteOrderForm');
    const feedback = document.getElementById('order-form-feedback');
    const submitBtn = document.getElementById('order-submit-btn');

    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'submit_order');
    formData.append('archetype', form.querySelector('input[name="order_archetype"]:checked')?.value || 'doctor');
    formData.append('tier', form.querySelector('#order_tier')?.value || 'standard');
    formData.append('desired_slug', form.querySelector('#order_desired_slug')?.value || '');
    formData.append('full_name', form.querySelector('input[name="order_full_name"]')?.value || '');
    formData.append('phone', form.querySelector('input[name="order_phone"]')?.value || '');
    formData.append('email', form.querySelector('input[name="order_email"]')?.value || '');
    formData.append('notes', form.querySelector('textarea[name="order_notes"]')?.value || '');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">sync</span><span>در حال ثبت...</span>';
    }

    fetch('actions/website_order_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            feedback.className = 'text-xs font-bold p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 block';
            feedback.innerHTML = `
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-lg text-emerald-600">check_circle</span>
                    <span class="text-sm">سفارش شماره #${data.order_id} با موفقیت ثبت گردید!</span>
                </div>
                <div class="text-[11px] leading-relaxed text-slate-700">${data.message}</div>
            `;
            form.reset();
            if (submitBtn) submitBtn.style.display = 'none';
        } else {
            feedback.className = 'text-xs font-bold p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 block';
            feedback.innerText = '✕ ' + (data.message || 'خطا در ثبت اطلاعات.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-base">check_circle</span><span>تلاش مجدد</span>';
            }
        }
    })
    .catch(() => {
        feedback.className = 'text-xs font-bold p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 block';
        feedback.innerText = 'خطا در برقراری ارتباط با سامانه.';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-base">check_circle</span><span>تلاش مجدد</span>';
        }
    });
}
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
