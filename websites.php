<?php
/**
 * ASENA Enterprise - Websites Showcase & Purchasing Catalog
 * Dedicated showcase for customizable tenant websites across 4 distinct archetypes:
 * 1. Doctor (Clinical Authority & Booking)
 * 2. Pharmacy (Cold-Chain & Rx Upload)
 * 3. Pet Shop (E-commerce & Autoship)
 * 4. Organization (Hospital Multi-Department & Emergency)
 * 
 * Version: 1.0.0
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

$page_title = 'سفارش و خرید وب‌سایت اختصاصی دامپزشکی، داروخانه و پت‌شاپ | آسنا';
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
    /* Website network v2: calm marketplace surface replacing the legacy presentation layer. */
    .website-network-shell { --wn-navy: #001a48; --wn-orange: #fd8100; --wn-ink: #122033; --wn-muted: #617087; background: #f7f9fc; color: var(--wn-ink); }
    .website-network-shell * { box-sizing: border-box; }
    .wn-container { width: min(1180px, calc(100% - 32px)); margin-inline: auto; }
    .wn-hero { background: var(--wn-navy); color: white; border-bottom: 1px solid rgba(0,26,72,.12); }
    .wn-hero-grid { display: grid; grid-template-columns: minmax(0,1.05fr) minmax(300px,.95fr); gap: 56px; align-items: center; padding: 82px 0 72px; }
    .wn-kicker { display: inline-flex; align-items: center; gap: 8px; color: #ffd19b; font-size: 12px; font-weight: 800; letter-spacing: .02em; }
    .wn-kicker::before { content: ''; width: 28px; height: 2px; background: var(--wn-orange); }
    .wn-title { margin: 18px 0 16px; max-width: 680px; font-size: clamp(2.2rem, 5vw, 4.4rem); line-height: 1.08; letter-spacing: 0; font-weight: 900; }
    .wn-lede { color: #c6d2e4; max-width: 600px; font-size: 16px; line-height: 1.9; }
    .wn-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
    .wn-btn { min-height: 48px; border-radius: 10px; padding: 0 18px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 800; transition: transform .18s ease, background .18s ease, border-color .18s ease; }
    .wn-btn:hover { transform: translateY(-2px); }
    .wn-btn-primary { background: var(--wn-orange); color: white; box-shadow: 0 10px 24px rgba(253,129,0,.22); }
    .wn-btn-primary:hover { background: #e77400; }
    .wn-btn-ghost { color: white; border: 1px solid rgba(255,255,255,.22); background: rgba(255,255,255,.06); }
    .wn-btn-ghost:hover { background: rgba(255,255,255,.12); }
    .wn-url-card { background: white; color: var(--wn-ink); border-radius: 8px; padding: 22px; box-shadow: 0 24px 60px rgba(0,0,0,.18); }
    .wn-url-card h2 { font-size: 16px; margin: 0 0 5px; font-weight: 900; }
    .wn-url-card p { color: var(--wn-muted); font-size: 12px; margin: 0 0 16px; line-height: 1.7; }
    .wn-url-input { display: flex; align-items: stretch; min-height: 52px; border: 1px solid #dbe2ec; border-radius: 10px; overflow: hidden; }
    .wn-url-input input { min-width: 0; flex: 1; border: 0; outline: 0; padding: 0 12px; font: 700 13px Geist, sans-serif; color: var(--wn-ink); }
    .wn-url-suffix { display: flex; align-items: center; padding: 0 11px; background: #f2f5f9; border-left: 1px solid #dbe2ec; color: #65748a; direction: ltr; font: 700 11px Geist, sans-serif; white-space: nowrap; }
    .wn-url-submit { width: 52px; border: 0; background: var(--wn-orange); color: white; cursor: pointer; }
    .wn-url-help { color: #738199; font-size: 11px; margin-top: 10px; }
    .wn-trust { display: grid; grid-template-columns: repeat(3,1fr); border-top: 1px solid rgba(255,255,255,.14); margin-top: 54px; padding-top: 20px; gap: 18px; }
    .wn-trust strong { display: block; font-size: 13px; color: white; }
    .wn-trust span { display: block; color: #9fb0c9; font-size: 11px; margin-top: 4px; }
    .wn-section { padding: 72px 0; }
    .wn-section-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
    .wn-section-head h2 { margin: 0; font-size: clamp(1.6rem,3vw,2.5rem); line-height: 1.15; font-weight: 900; color: var(--wn-navy); }
    .wn-section-head p { max-width: 490px; margin: 0; color: var(--wn-muted); font-size: 13px; line-height: 1.8; }
    .wn-filter { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 24px; scrollbar-width: none; }
    .wn-filter::-webkit-scrollbar { display: none; }
    .wn-filter button { min-height: 44px; white-space: nowrap; border: 1px solid #d9e1ec; background: white; color: #53647b; border-radius: 999px; padding: 0 15px; cursor: pointer; font-size: 12px; font-weight: 800; }
    .wn-filter button.is-active, .wn-filter button:hover { border-color: var(--wn-navy); background: var(--wn-navy); color: white; }
    .wn-template-grid { display: grid; grid-template-columns: repeat(12,1fr); gap: 16px; }
    .wn-template { grid-column: span 4; min-height: 340px; display: flex; flex-direction: column; overflow: hidden; background: white; border: 1px solid #e0e6ef; border-radius: 8px; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .wn-template:hover { transform: translateY(-4px); border-color: #b6c4d8; box-shadow: 0 18px 40px rgba(17,38,68,.1); }
    .wn-template.is-featured { grid-column: span 6; }
    .wn-template-visual { min-height: 152px; padding: 20px; display: flex; align-items: end; position: relative; overflow: hidden; }
    .wn-template-visual::after { content: ''; position: absolute; inset: auto 18px 18px auto; width: 94px; height: 56px; border: 1px solid rgba(255,255,255,.36); border-radius: 8px; transform: rotate(-7deg); }
    .wn-template-visual strong { position: relative; z-index: 1; color: white; font-size: 20px; font-weight: 900; }
    .wn-emerald { background: #087f68; } .wn-purple { background: #5d3a9e; } .wn-orange { background: #b95112; } .wn-navy { background: #17325e; } .wn-aurora { background: #146875; }
    .wn-template-body { display: flex; flex: 1; flex-direction: column; padding: 18px; }
    .wn-template-body h3 { margin: 0; color: var(--wn-navy); font-size: 17px; font-weight: 900; }
    .wn-template-body p { margin: 7px 0 14px; color: var(--wn-muted); font-size: 12px; line-height: 1.75; }
    .wn-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .wn-tags span { padding: 5px 8px; border-radius: 6px; background: #f1f4f8; color: #53647b; font-size: 10px; font-weight: 800; }
    .wn-template-actions { display: flex; gap: 8px; margin-top: auto; }
    .wn-template-actions a, .wn-template-actions button { min-height: 42px; flex: 1; border-radius: 8px; font-size: 11px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 5px; }
    .wn-preview { border: 1px solid #d6dfeb; color: var(--wn-navy); background: white; }
    .wn-start { border: 0; color: white; background: var(--wn-navy); }
    .wn-start:hover { background: #082a5c; }
    .wn-process { background: #eef3f8; border-top: 1px solid #dfe6ef; border-bottom: 1px solid #dfe6ef; }
    .wn-process-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; }
    .wn-step { display: flex; gap: 12px; align-items: flex-start; }
    .wn-step-number { width: 32px; height: 32px; border-radius: 50%; background: var(--wn-orange); color: white; display: grid; place-items: center; flex: none; font-weight: 900; font-size: 12px; }
    .wn-step h3 { margin: 0 0 4px; color: var(--wn-navy); font-size: 14px; font-weight: 900; }
    .wn-step p { margin: 0; color: var(--wn-muted); font-size: 12px; line-height: 1.7; }
    .wn-final { background: white; border: 1px solid #dce4ee; border-radius: 8px; padding: 34px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
    .wn-final h2 { margin: 0 0 7px; color: var(--wn-navy); font-size: 23px; font-weight: 900; }
    .wn-final p { margin: 0; color: var(--wn-muted); font-size: 13px; }
    @media (max-width: 900px) { .wn-hero-grid { grid-template-columns: 1fr; gap: 32px; padding: 58px 0 50px; } .wn-template, .wn-template.is-featured { grid-column: span 6; } }
    @media (max-width: 640px) { .wn-container { width: min(100% - 24px, 560px); } .wn-title { font-size: 2.45rem; } .wn-lede { font-size: 14px; } .wn-trust { grid-template-columns: 1fr; gap: 12px; margin-top: 34px; } .wn-section { padding: 48px 0; } .wn-section-head { display: block; } .wn-section-head p { margin-top: 10px; } .wn-template, .wn-template.is-featured { grid-column: span 12; } .wn-template-visual { min-height: 132px; } .wn-process-grid { grid-template-columns: 1fr; gap: 18px; } .wn-final { display: block; padding: 24px; } .wn-final .wn-btn { width: 100%; margin-top: 18px; } }
    #legacyShowcase { display: none !important; }
</style>

<main class="website-network-shell" dir="rtl">
    <section class="wn-hero">
        <div class="wn-container wn-hero-grid">
            <div>
                <div class="wn-kicker">ASENA WEBSITE NETWORK</div>
                <h1 class="wn-title">یک وب‌سایت واقعی برای کاری که انجام می‌دهید.</h1>
                <p class="wn-lede">قالبی انتخاب کنید که از همان اولین نگاه، نوبت‌دهی، فروش، نسخه‌پذیری یا اعتبار مرکز شما را جلو بیاورد. همه‌چیز به زیرساخت آسنا متصل می‌ماند؛ برند و تجربه، متعلق به شماست.</p>
                <div class="wn-actions"><button class="wn-btn wn-btn-primary" type="button" onclick="openOrderModal()"><span class="material-symbols-outlined">arrow_back</span>شروع راه‌اندازی</button><a class="wn-btn wn-btn-ghost" href="#wn-templates"><span class="material-symbols-outlined">visibility</span>دیدن قالب‌ها</a></div>
                <div class="wn-trust"><div><strong>آدرس اختصاصی</strong><span>yourname.asena.company</span></div><div><strong>اتصال به عملیات آسنا</strong><span>نوبت، فروش، نسخه و پرداخت</span></div><div><strong>راه‌اندازی با همراهی تیم ما</strong><span>از انتخاب قالب تا انتشار</span></div></div>
            </div>
            <div class="wn-url-card"><h2>قبل از شروع، آدرس خود را بررسی کنید</h2><p>یک نام کوتاه و قابل‌اعتماد برای برندتان انتخاب کنید. بعداً امکان اتصال دامنه .ir یا .com هم وجود دارد.</p><div class="wn-url-input"><input id="hero-subdomain-input" type="text" dir="ltr" placeholder="مثلاً dr-alavi" autocomplete="off" spellcheck="false"><span class="wn-url-suffix">.asena.company</span><button id="hero-check-btn" class="wn-url-submit" type="button" onclick="checkSubdomainFromHero()" aria-label="بررسی آدرس"><span class="material-symbols-outlined">search</span></button></div><div id="hero-subdomain-feedback" class="wn-url-help">حداقل ۳ کاراکتر انگلیسی · بدون فاصله</div></div>
        </div>
    </section>
    <section id="wn-templates" class="wn-section"><div class="wn-container"><div class="wn-section-head"><h2>قالب را بر اساس هدف‌تان انتخاب کنید</h2><p>هر قالب یک مسیر اصلی برای مشتری دارد. جزئیات بعد از انتخاب قابل شخصی‌سازی است.</p></div><div class="wn-filter" role="tablist" aria-label="فیلتر قالب‌ها"><button class="is-active" type="button" data-filter="all">همه قالب‌ها</button><button type="button" data-filter="care">پزشکی و درمان</button><button type="button" data-filter="commerce">فروش و سفارش</button><button type="button" data-filter="enterprise">سازمانی</button></div><div class="wn-template-grid">
        <article class="wn-template is-featured" data-kind="care"><div class="wn-template-visual wn-emerald"><strong>Clinical Emerald</strong></div><div class="wn-template-body"><h3>کلینیک و پزشک</h3><p>برای معرفی تخصص، نمایش خدمات و رزرو آنلاین. مسیر اصلی این قالب از اعتماد به نوبت می‌رسد.</p><div class="wn-tags"><span>نوبت‌دهی</span><span>خدمات</span><span>پروفایل پزشک</span></div><div class="wn-template-actions"><a class="wn-preview" href="site.php?slug=dr-alavi" target="_blank">پیش‌نمایش</a><button class="wn-start" type="button" onclick="openOrderModal('doctor','standard')">انتخاب قالب</button></div></div></article>
        <article class="wn-template" data-kind="care"><div class="wn-template-visual wn-purple"><strong>Pharmacy Purple</strong></div><div class="wn-template-body"><h3>داروخانه دامپزشکی</h3><p>نسخه، مشاوره داروساز و کاتالوگ کالا در یک مسیر روشن برای خرید امن.</p><div class="wn-tags"><span>آپلود نسخه</span><span>زنجیره سرد</span><span>کاتالوگ</span></div><div class="wn-template-actions"><a class="wn-preview" href="site.php?slug=sina-pharmacy" target="_blank">پیش‌نمایش</a><button class="wn-start" type="button" onclick="openOrderModal('pharmacist','pharmacy')">انتخاب قالب</button></div></div></article>
        <article class="wn-template" data-kind="commerce"><div class="wn-template-visual wn-orange"><strong>Pet Shop Orange</strong></div><div class="wn-template-body"><h3>پت‌شاپ و فروشگاه</h3><p>ویترین کالا، خرید سریع و سفارش‌های دوره‌ای برای فروشگاه‌هایی که می‌خواهند رشد کنند.</p><div class="wn-tags"><span>فروشگاه</span><span>اتوشیپ</span><span>انبار</span></div><div class="wn-template-actions"><a class="wn-preview" href="site.php?slug=petland-store" target="_blank">پیش‌نمایش</a><button class="wn-start" type="button" onclick="openOrderModal('seller','premium')">انتخاب قالب</button></div></div></article>
        <article class="wn-template" data-kind="enterprise"><div class="wn-template-visual wn-navy"><strong>Hospital Navy</strong></div><div class="wn-template-body"><h3>بیمارستان و مرکز جامع</h3><p>دپارتمان‌ها، پزشکان، اورژانس و مسیرهای متعدد خدمت برای سازمان‌های بزرگ‌تر.</p><div class="wn-tags"><span>دپارتمان‌ها</span><span>اورژانس</span><span>پزشکان</span></div><div class="wn-template-actions"><a class="wn-preview" href="site.php?slug=razi-hospital" target="_blank">پیش‌نمایش</a><button class="wn-start" type="button" onclick="openOrderModal('organization','enterprise')">انتخاب قالب</button></div></div></article>
        <article class="wn-template" data-kind="enterprise"><div class="wn-template-visual wn-aurora"><strong>Premium Aurora</strong></div><div class="wn-template-body"><h3>اکوسیستم پریمیوم</h3><p>برای برندهایی که هم‌زمان به معرفی، نوبت‌دهی، فروش، تله‌هلث و کمپین نیاز دارند.</p><div class="wn-tags"><span>تله‌هلث</span><span>فروش</span><span>کمپین</span></div><div class="wn-template-actions"><a class="wn-preview" href="site.php?slug=razi-hospital" target="_blank">نمونه تجربه</a><button class="wn-start" type="button" onclick="openOrderModal('organization','enterprise')">گفت‌وگو با ما</button></div></div></article>
    </div></div></section>
    <section class="wn-section wn-process"><div class="wn-container"><div class="wn-section-head"><h2>از انتخاب تا انتشار، سه قدم</h2><p>اطلاعات اولیه را می‌گیریم، سایت را برای نقش شما آماده می‌کنیم و قبل از انتشار با هم مرور می‌کنیم.</p></div><div class="wn-process-grid"><div class="wn-step"><span class="wn-step-number">۱</span><div><h3>قالب و آدرس</h3><p>هدف کاری و نام مناسب خود را انتخاب می‌کنید.</p></div></div><div class="wn-step"><span class="wn-step-number">۲</span><div><h3>محتوای اختصاصی</h3><p>اطلاعات مرکز، خدمات، کالاها و هویت بصری شما وارد می‌شود.</p></div></div><div class="wn-step"><span class="wn-step-number">۳</span><div><h3>پیش‌نمایش و انتشار</h3><p>نسخه موبایل و دسکتاپ را بررسی و سایت را منتشر می‌کنیم.</p></div></div></div></div></section>
    <section class="wn-section"><div class="wn-container"><div class="wn-final"><div><h2>برای ساخت سایت مناسب نقش شما آماده‌ایم</h2><p>درخواست راه‌اندازی را ثبت کنید؛ انتخاب قالب، tier و مسیر اجرا را با شما هماهنگ می‌کنیم.</p></div><button class="wn-btn wn-btn-primary" type="button" onclick="openOrderModal()">ثبت درخواست راه‌اندازی <span class="material-symbols-outlined">arrow_back</span></button></div></div></section>
</main>

<script>
document.querySelectorAll('.wn-filter button').forEach(function (button) {
    button.addEventListener('click', function () {
        document.querySelectorAll('.wn-filter button').forEach(function (item) { item.classList.remove('is-active'); });
        button.classList.add('is-active');
        var filter = button.dataset.filter;
        document.querySelectorAll('.wn-template').forEach(function (card) { card.hidden = filter !== 'all' && card.dataset.kind !== filter; });
    });
});
</script>

<div id="legacyShowcase" class="w-[96%] max-w-[1550px] mx-auto py-6 md:py-10 space-y-16 md:space-y-24">

    <!-- 1. Hero Beat: Value Proposition & Subdomain Availability Checker -->
    <section class="relative overflow-hidden rounded-[2.5rem] md:rounded-[3.5rem] bg-gradient-to-b from-[#000d27] via-[#001744] to-[#002263] text-white p-6 sm:p-10 md:p-16 shadow-2xl border border-white/10">
        <!-- Ambient Glowing Background Orbs & Luxury Grid Texture -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#fd8100]/25 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-10 right-10 w-80 h-80 bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl mx-auto text-center space-y-6 md:space-y-7">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-amber-400/30 text-xs md:text-sm font-bold text-amber-300 shadow-lg shadow-amber-500/10">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                <span class="material-symbols-outlined text-base text-amber-400">language</span>
                <span>سامانه هوشمند راه‌اندازی وب‌سایت‌های تخصصی اکوسیستم آسنا</span>
            </div>

            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight leading-[1.25] md:leading-[1.2] text-white">
                وب‌سایت اختصاصی، مستقل و مدرن؛<br class="hidden sm:inline">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-amber-300 via-orange-400 to-amber-200 drop-shadow-sm">
                    دقیقاً متناسب با تخصص و هویت برند شما
                </span>
            </h1>

            <p class="text-sm sm:text-base md:text-lg text-slate-200 font-medium leading-relaxed max-w-2xl mx-auto">
                دیگر نیازی نیست وب‌سایت شما یک قالب تکراری باشد! در آسنا، هر وب‌سایت یک پایگاه مستقل با آدرس و برند اختصاصی شماست؛ مجهز به ماژول‌های کاملاً متفاوت و متناسب با نیازهای کاری حوزه شما (مطب، داروخانه، پت‌شاپ یا بیمارستان).
            </p>

            <!-- Interactive Live Subdomain Availability Search Engine -->
            <div class="max-w-2xl mx-auto pt-2">
                <div class="bg-white/10 backdrop-blur-2xl p-2 md:p-2.5 rounded-2xl md:rounded-3xl border border-white/20 shadow-2xl">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <div class="flex-1 flex items-center bg-white rounded-xl md:rounded-2xl px-3.5 py-3 border border-slate-200 text-slate-800 shadow-inner group focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-400/20 transition-all">
                            <div class="flex items-center gap-1.5 text-slate-400 pl-2 shrink-0 border-l border-slate-200 ml-2" dir="ltr">
                                <span class="material-symbols-outlined text-emerald-600 text-base">lock</span>
                                <span class="font-mono text-xs text-slate-500 font-bold">https://</span>
                            </div>
                            <input type="text" id="hero-subdomain-input" placeholder="نام دلخواه انگلیسی (مثلاً dr-alavi یا petland)" class="w-full bg-transparent border-none outline-none text-right sm:text-left font-mono font-bold text-xs sm:text-sm md:text-base text-slate-900 placeholder:text-slate-400 placeholder:font-sans placeholder:text-xs" autocomplete="off" spellcheck="false">
                            <span class="text-xs sm:text-sm font-mono text-slate-500 font-bold mr-1 shrink-0" dir="ltr">.asena.company</span>
                        </div>
                        <button type="button" onclick="checkSubdomainFromHero()" id="hero-check-btn" class="px-6 py-3.5 rounded-xl md:rounded-2xl bg-gradient-to-r from-[#fd8100] via-orange-500 to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs sm:text-sm shadow-xl hover:shadow-orange-500/40 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 shrink-0">
                            <span class="material-symbols-outlined text-lg">search_check</span>
                            <span>بررسی وضعیت آدرس</span>
                        </button>
                    </div>
                </div>

                <!-- Subtitle Hint -->
                <div class="flex items-center justify-center gap-2 text-slate-300 text-[11px] sm:text-xs mt-2.5 font-medium">
                    <span class="material-symbols-outlined text-sm text-amber-400">verified</span>
                    <span>امکان اتصال دامنه اختصاصی خودتان (.ir و .com) در تمامی نسخه‌ها فراهم است</span>
                </div>

                <!-- Status Feedback Badge -->
                <div id="hero-subdomain-feedback" class="mt-3 text-xs md:text-sm font-bold hidden transition-all duration-300"></div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="#archetypes-section" class="px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-md text-white font-bold text-xs sm:text-sm transition-all flex items-center gap-2 border border-white/20 hover:border-white/40 shadow-sm">
                    <span class="material-symbols-outlined text-base text-amber-300">dashboard_customize</span>
                    <span>مشاهده و بررسی ۴ نسخه وب‌سایت</span>
                </a>
                <button type="button" onclick="openOrderModal()" class="px-6 py-3 rounded-xl bg-white hover:bg-amber-50 text-[#001a48] font-black text-xs sm:text-sm transition-all shadow-xl hover:shadow-2xl flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base text-[#fd8100]">rocket_launch</span>
                    <span>سفارش فوری وب‌سایت</span>
                </button>
            </div>

            <!-- Trust Anchors Strip (High-Contrast Glass Bento) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pt-8 border-t border-white/15 text-right">
                <!-- Badge 1: 5-minute setup -->
                <div class="p-4 rounded-2xl bg-white/[0.08] hover:bg-white/[0.12] backdrop-blur-md border border-white/15 transition-all shadow-lg flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400/20 to-orange-500/30 border border-amber-400/40 flex items-center justify-center text-amber-300 shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-2xl text-amber-300">bolt</span>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-white group-hover:text-amber-200 transition-colors">تحویل آنلاین ۵ دقیقه‌ای</div>
                        <div class="text-[11px] text-slate-300 mt-0.5 font-medium leading-tight">راه‌اندازی فوری بدون معطلی</div>
                    </div>
                </div>

                <!-- Badge 2: Shaparak -->
                <div class="p-4 rounded-2xl bg-white/[0.08] hover:bg-white/[0.12] backdrop-blur-md border border-white/15 transition-all shadow-lg flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400/20 to-teal-500/30 border border-emerald-400/40 flex items-center justify-center text-emerald-300 shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-2xl text-emerald-300">credit_card</span>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-white group-hover:text-emerald-200 transition-colors">درگاه مستقیم شاپرک</div>
                        <div class="text-[11px] text-slate-300 mt-0.5 font-medium leading-tight">تسویه روزانه پایا بدون کارمزد</div>
                    </div>
                </div>

                <!-- Badge 3: Smart QR Card -->
                <div class="p-4 rounded-2xl bg-white/[0.08] hover:bg-white/[0.12] backdrop-blur-md border border-white/15 transition-all shadow-lg flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400/20 to-indigo-500/30 border border-purple-400/40 flex items-center justify-center text-purple-300 shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-2xl text-purple-300">qr_code_2</span>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-white group-hover:text-purple-200 transition-colors">کارت ویزیت هوشمند</div>
                        <div class="text-[11px] text-slate-300 mt-0.5 font-medium leading-tight">استند رومیزی و کد vCard مطب</div>
                    </div>
                </div>

                <!-- Badge 4: Independent Domain -->
                <div class="p-4 rounded-2xl bg-white/[0.08] hover:bg-white/[0.12] backdrop-blur-md border border-white/15 transition-all shadow-lg flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-400/20 to-blue-500/30 border border-cyan-400/40 flex items-center justify-center text-cyan-300 shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-2xl text-cyan-300">domain</span>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-white group-hover:text-cyan-200 transition-colors">دامنه و هاست ۱۰۰٪ مستقل</div>
                        <div class="text-[11px] text-slate-300 mt-0.5 font-medium leading-tight">اتصال دامنه .ir و .com با SSL</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. The Core Beat: 4 Dedicated Archetypes Showcase Matrix -->
    <section id="archetypes-section" class="space-y-8 scroll-mt-24">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold">
                <span class="material-symbols-outlined text-sm">palette</span>
                <span>تنوع کامل ساختاری بر اساس زمینه کاری شما</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                قالب‌های اختصاصی ۴ حوزه فعالیت
            </h2>
            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                هر شغل، نیازمندی‌ها و مراجعین خاص خود را دارد. وب‌سایت‌های آسنا با شناخت عمیق از فرآیندهای دامپزشکی، ماژول‌های متفاوتی برای پزشکان، داروخانه‌ها، فروشگاه‌ها و بیمارستان‌ها ارائه می‌دهند.
            </p>
        </div>

        <!-- Archetype Navigation Tabs -->
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 p-1.5 bg-slate-100 rounded-2xl max-w-4xl mx-auto border border-slate-200/80 shadow-xs">
            <button type="button" onclick="selectArchetypeTab('doctor')" id="arch-tab-doctor" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 cursor-pointer shadow-sm bg-white text-emerald-800 border border-emerald-200">
                <span class="material-symbols-outlined text-lg text-emerald-600">stethoscope</span>
                <span>۱. پزشکان و متخصصین</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('pharmacist')" id="arch-tab-pharmacist" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-purple-700 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-purple-600">medication</span>
                <span>۲. داروخانه‌های تخصصی</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('seller')" id="arch-tab-seller" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-orange-700 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-orange-600">storefront</span>
                <span>۳. پت‌شاپ‌ها و فروشگاه‌ها</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('organization')" id="arch-tab-organization" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-blue-800 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-blue-700">apartment</span>
                <span>۴. بیمارستان‌ها و مراکز جامع</span>
            </button>
        </div>

        <!-- Archetype Panels Wrapper -->
        <div class="relative">

            <!-- Panel 1: Doctor Archetype (Clinical Authority) -->
            <div id="arch-panel-doctor" class="arch-panel transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-emerald-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                                <span>الگوی تخصصی اتوریتی بالینی و نوبت‌دهی آنلاین مراجعین</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت پزشکان، جراحان و متخصصین دامپزشکی
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                طراحی شده برای معرفی باوقار سوابق علمی، مدارک جراحی، بورد تخصصی و کد نظام دامپزشکی. مراجعین می‌توانند تایم‌اسلات‌های آزاد ویزیت را مشاهده و آنلاین رزرو کنند، تعرفه‌ها را به صورت شفاف محاسبه نمایند و نتایج درمان‌های قبلی را به صورت قبل و بعد مقایسه کنند.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calendar_month</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">تقویم رزرو نوبت آنلاین و پایش تایم‌اسلات‌ها</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">انتخاب تاریخ و ساعت توسط کاربر با دریافت پیامک تایید و لوکیشن بدون تداخل نوبت‌ها.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">compare</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اسلایدر تعاملی نتایج درمان قبل و بعد (Before & After)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">جلب اعتماد حداکثری مراجعین با نمایش نمونه‌های جراحی ارتوپدی، دندانپزشکی و پوست.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calculate</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">محاسبه‌گر شفاف تعرفه خدمات با ۱۰٪ تخفیف رزرو آنلاین</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">محاسبه برآورد هزینه چکاپ، واکسیناسیون، عقیم‌سازی و آزمایش‌ها متناسب با گونه و وزن پت.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">badge</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به پروانه نظام دامپزشکی و کارت ویزیت دیجیتال هوشمند</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">استند رومیزی با کد QR اختصاصی مطب جهت ذخیره مستقیم شماره و آدرس در تلفن بیمار.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=dr-alavi" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-emerald-700/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت پزشک</span>
                                </a>
                                <button type="button" onclick="openOrderModal('doctor')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-emerald-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase (Interactive Desktop / Mobile PWA) -->
                        <div class="lg:col-span-5 flex flex-col items-center w-full">
                            <!-- Device Switcher Controls -->
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
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>دمو فعال و آنلاین</span>
                                </div>
                            </div>

                            <!-- 1. Desktop Browser Frame -->
                            <div id="arch-mockup-desktop-doctor" class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden transition-all duration-300">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300 font-bold">
                                        <span class="material-symbols-outlined text-xs text-emerald-400">lock</span>
                                        <span>dr-alavi.asena.company</span>
                                    </div>
                                    <a href="site.php?slug=dr-alavi" target="_blank" class="text-slate-400 hover:text-white" title="باز کردن در تب جدید">
                                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    </a>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-4 text-white space-y-3.5">
                                    <!-- Doctor Profile Header -->
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-10 h-10 rounded-full bg-emerald-600/30 border border-emerald-500/50 flex items-center justify-center text-emerald-300 font-bold text-sm">
                                                <span class="material-symbols-outlined text-xl">stethoscope</span>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-black text-white">کلینیک دکتر محمدرضا علوی</h4>
                                                <div class="text-[10px] text-slate-400">بورد تخصصی جراحی بافت نرم و ارتوپدی</div>
                                            </div>
                                        </div>
                                        <span class="bg-emerald-500/20 text-emerald-300 text-[10px] px-2 py-0.5 rounded-full border border-emerald-400/30 font-mono">نظام: ۲۴۵۹۸</span>
                                    </div>
                                    <!-- Live Status & Time-Slots Widget -->
                                    <div class="bg-slate-900/90 rounded-xl p-3 border border-slate-800 space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-emerald-400 font-black flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                                <span>نوبت‌های آزاد ویزیت امروز</span>
                                            </span>
                                            <span class="text-[10px] text-slate-400">رزرو آنلاین با ۱۰٪ تخفیف</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-1.5 text-center text-[10px]">
                                            <div class="bg-emerald-950/60 text-emerald-300 py-1.5 px-2 rounded-lg border border-emerald-700/50 font-mono font-bold">۱۶:۳۰ امروز</div>
                                            <div class="bg-emerald-950/60 text-emerald-300 py-1.5 px-2 rounded-lg border border-emerald-700/50 font-mono font-bold">۱۷:۱۵ امروز</div>
                                            <div class="bg-slate-800 text-slate-300 py-1.5 px-2 rounded-lg border border-slate-700 font-mono">۱۱:۰۰ فردا</div>
                                        </div>
                                    </div>
                                    <!-- Mini Before/After & Teaser Row -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-emerald-400 text-lg">compare</span>
                                            <div class="text-[10px] leading-tight text-slate-300">اسلایدر قبل و بعد جراحی ارتوپدی</div>
                                        </div>
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-amber-400 text-lg">calculate</span>
                                            <div class="text-[10px] leading-tight text-slate-300">محاسبه تعرفه خدمات و جراحی</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=dr-alavi" target="_blank" class="w-full py-2.5 bg-emerald-700 hover:bg-emerald-600 rounded-xl text-center text-xs font-black transition-colors flex items-center justify-center gap-1.5 text-white shadow-md">
                                        <span>مشاهده وب‌سایت زنده دکتر علوی</span>
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Mobile PWA Smartphone Frame (Hidden by default) -->
                            <div id="arch-mockup-mobile-doctor" class="hidden w-full max-w-[280px] bg-slate-900 rounded-[2.8rem] p-3 shadow-2xl border-4 border-slate-700 relative group overflow-hidden transition-all duration-300">
                                <!-- Dynamic Island / Phone Notch -->
                                <div class="w-24 h-4 bg-black rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-slate-800 mr-2"></span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-3 text-white space-y-3 text-xs">
                                    <div class="text-center space-y-1">
                                        <div class="w-10 h-10 rounded-full bg-emerald-600/30 border border-emerald-500/50 mx-auto flex items-center justify-center text-emerald-300">
                                            <span class="material-symbols-outlined text-lg">stethoscope</span>
                                        </div>
                                        <div class="font-black text-xs text-white">دکتر محمدرضا علوی</div>
                                        <div class="text-[9px] text-emerald-300">جراح و متخصص ارتوپدی</div>
                                    </div>
                                    <div class="bg-slate-900 rounded-xl p-2 border border-slate-800 space-y-1 text-center">
                                        <div class="text-[10px] text-emerald-400 font-bold">رزرو آنلاین ویزیت مطب</div>
                                        <div class="text-[9px] text-slate-400">تایم‌اسلات آزاد امروز: ۱۶:۳۰</div>
                                    </div>
                                    <a href="site.php?slug=dr-alavi" target="_blank" class="w-full py-2 bg-emerald-700 hover:bg-emerald-600 rounded-lg text-center text-[11px] font-black transition-colors block text-white">
                                        ورود به نسخه موبایل PWA ↗
                                    </a>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-2.5 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                                <span>پالت اختصاصی: سبز زمردی کلینیکی (#065f46) و سفید درمانی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Pharmacist Archetype (Cold-Chain & Rx) -->
            <div id="arch-panel-pharmacist" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-purple-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-purple-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-purple-600">vaccines</span>
                                <span>الگوی تخصصی دراگ‌استور، ارسال زنجیره سرد و آپلود نسخه پزشک</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت داروخانه‌های تخصصی دامپزشکی و توزیع مکمل
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                طراحی هدفمند برای استعلام و پذیرش سریع نسخه‌های دارویی، عرضه داروهای کمیاب، مکمل‌های درمانی و واکسیناسیون. مجهز به سیستم نمایش لحظه‌ای شرایط دمایی زنجیره سرد (۲ الی ۸ درجه سانتی‌گراد) و بررسی تداخل دارویی.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">document_scanner</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">باکس تعاملی آپلود سریع نسخه پزشک (Rx Upload)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">بیمار عکس نسخه یا کد رهگیری را ثبت کرده و داروساز مقیم کمتر از ۱۵ دقیقه پاسخ و فاکتور صادر می‌کند.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">ac_unit</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">دیده‌بان زنده پایش دمای زنجیره سرد (۲ تا ۸ درجه)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">تضمین سلامت واکسن‌ها و داروهای بیولوژیک با بسته‌بندی یونولیت و ژل یخ مخصوص.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">warning</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به پایشگر تداخلات دارویی دامپزشکی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">بررسی خودکار سازگاری داروهای تجویزی با مکمل‌ها جهت پیشگیری از عوارض جانبی.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">local_shipping</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">ارسال فوری داخل‌شهری با پیک و بین‌شهری با پست پیشتاز</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">امکان انتخاب تحویل فوری با بیمه کالا و کد رهگیری مرسوله پستی.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=sina-pharmacy" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-purple-700/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت داروخانه</span>
                                </a>
                                <button type="button" onclick="openOrderModal('pharmacist')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-purple-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase (Interactive Desktop / Mobile PWA) -->
                        <div class="lg:col-span-5 flex flex-col items-center w-full">
                            <!-- Device Switcher Controls -->
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
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-800 border border-purple-200 text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                    <span>دمو فعال و آنلاین</span>
                                </div>
                            </div>

                            <!-- 1. Desktop Browser Frame -->
                            <div id="arch-mockup-desktop-pharmacist" class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden transition-all duration-300">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300 font-bold">
                                        <span class="material-symbols-outlined text-xs text-purple-400">lock</span>
                                        <span>sina-pharmacy.asena.company</span>
                                    </div>
                                    <a href="site.php?slug=sina-pharmacy" target="_blank" class="text-slate-400 hover:text-white" title="باز کردن در تب جدید">
                                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    </a>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-4 text-white space-y-3.5">
                                    <!-- Pharmacy Header -->
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-10 h-10 rounded-full bg-purple-600/30 border border-purple-500/50 flex items-center justify-center text-purple-300 font-bold text-sm">
                                                <span class="material-symbols-outlined text-xl">medication</span>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-black text-white">داروخانه تخصصی دکتر فیروزی (سینا)</h4>
                                                <div class="text-[10px] text-slate-400">مرکز تأمین واکسن‌ها، سرم‌ها و مکمل‌های حیوانات</div>
                                            </div>
                                        </div>
                                        <span class="bg-purple-500/20 text-purple-300 text-[10px] px-2 py-0.5 rounded-full border border-purple-400/30 font-bold">مجوز رسمی غذا و دارو</span>
                                    </div>
                                    <!-- Cold-Chain Telemetry & Rx Dropzone -->
                                    <div class="bg-slate-900/90 rounded-xl p-3 border border-slate-800 space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-cyan-300 font-black flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">ac_unit</span>
                                                <span>دیده‌بان زنجیره سرد: ۳.۸°C</span>
                                            </span>
                                            <span class="text-[10px] text-emerald-400 font-bold">پایدار در بازه استاندارد ۲-۸°C</span>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-purple-950/40 border border-dashed border-purple-500/40 text-center space-y-1">
                                            <div class="text-xs text-purple-200 font-bold flex items-center justify-center gap-1">
                                                <span class="material-symbols-outlined text-sm">upload_file</span>
                                                <span>آپلود سریع عکس نسخه پزشک (Rx)</span>
                                            </div>
                                            <div class="text-[9px] text-slate-400">بررسی توسط داروساز مقیم زیر ۱۵ دقیقه با ارسال فاکتور</div>
                                        </div>
                                    </div>
                                    <!-- Insulated Delivery Badge -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-purple-400 text-lg">inventory_2</span>
                                            <div class="text-[10px] leading-tight text-slate-300">بسته‌بندی ایزوله یونولیت و ژل یخ</div>
                                        </div>
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-cyan-400 text-lg">local_shipping</span>
                                            <div class="text-[10px] leading-tight text-slate-300">ارسال فوری داخل‌شهری و پستی</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=sina-pharmacy" target="_blank" class="w-full py-2.5 bg-purple-700 hover:bg-purple-600 rounded-xl text-center text-xs font-black transition-colors flex items-center justify-center gap-1.5 text-white shadow-md">
                                        <span>مشاهده وب‌سایت زنده داروخانه سینا</span>
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Mobile PWA Smartphone Frame (Hidden by default) -->
                            <div id="arch-mockup-mobile-pharmacist" class="hidden w-full max-w-[280px] bg-slate-900 rounded-[2.8rem] p-3 shadow-2xl border-4 border-slate-700 relative group overflow-hidden transition-all duration-300">
                                <div class="w-24 h-4 bg-black rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-slate-800 mr-2"></span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-3 text-white space-y-3 text-xs">
                                    <div class="text-center space-y-1">
                                        <div class="w-10 h-10 rounded-full bg-purple-600/30 border border-purple-500/50 mx-auto flex items-center justify-center text-purple-300">
                                            <span class="material-symbols-outlined text-lg">medication</span>
                                        </div>
                                        <div class="font-black text-xs text-white">داروخانه دکتر فیروزی</div>
                                        <div class="text-[9px] text-cyan-300">زنجیره سرد فعال: ۳.۸°C</div>
                                    </div>
                                    <div class="bg-slate-900 rounded-xl p-2 border border-slate-800 space-y-1 text-center">
                                        <div class="text-[10px] text-purple-400 font-bold">آپلود سریع عکس نسخه</div>
                                        <div class="text-[9px] text-slate-400">پاسخ داروساز زیر ۱۵ دقیقه</div>
                                    </div>
                                    <a href="site.php?slug=sina-pharmacy" target="_blank" class="w-full py-2 bg-purple-700 hover:bg-purple-600 rounded-lg text-center text-[11px] font-black transition-colors block text-white">
                                        ورود به نسخه موبایل PWA ↗
                                    </a>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-2.5 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-purple-600">check_circle</span>
                                <span>پالت اختصاصی: بنفش دارویی های‌تک (#7c3aed) و آبی زنجیره سرد</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 3: Pet Shop Archetype (Retail & Autoship) -->
            <div id="arch-panel-seller" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-orange-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-orange-600">shopping_bag</span>
                                <span>الگوی تخصصی فروشگاه کالا، تنوع گونه‌ها و تحویل خودکار دوره‌ای</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت پت‌شاپ‌ها و هایپرمارکت‌های ملزومات حیوانات
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                پلتفرم فروشگاهی قدرتمند برای عرضه غذای خشک، کنسرو، تشویقی، خاک و اکسسوری پت. مجهز به تفکیک سریع بر اساس گونه (سگ، گربه، پرنده، جوندگان)، شگفت‌انگیزها، انبارداری لحظه‌ای و سامانه اشتراک دوره‌ای خودکار (Autoship).
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">pets</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">فیلتر سریع گونه حیوانات (سگ، گربه، پرنده، آبزیان)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">دسترسی فوری مشتری به غذای متناسب با نژاد، سن و حساسیت‌های غذایی حیوان.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">autorenew</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">سرویس تحویل ماهانه دوره‌ای با تخفیف دائمی (Autoship)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">تثبیت وفاداری مشتری و ارسال خودکار ماهانه غذای خشک بدون نیاز به خرید مجدد.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">local_fire_department</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">ویترین شگفت‌انگیزها و شمارشگر معکوس تخفیف</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">افزایش چشمگیر نرخ تبدیل خرید با بنرهای تایمردار و نمایش موجودی انبار.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">account_balance_wallet</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به درگاه بانکی شاپرک و تسویه حساب خودکار پایا</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">واریز مستقیم و منظم وجوه حاصل از فروش کالاها به شماره شبای بانکی شما.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=petland-store" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-orange-600/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت پت‌شاپ</span>
                                </a>
                                <button type="button" onclick="openOrderModal('seller')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-orange-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase (Interactive Desktop / Mobile PWA) -->
                        <div class="lg:col-span-5 flex flex-col items-center w-full">
                            <!-- Device Switcher Controls -->
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
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 text-orange-800 border border-orange-200 text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                    <span>دمو فعال و آنلاین</span>
                                </div>
                            </div>

                            <!-- 1. Desktop Browser Frame -->
                            <div id="arch-mockup-desktop-seller" class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden transition-all duration-300">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300 font-bold">
                                        <span class="material-symbols-outlined text-xs text-orange-400">lock</span>
                                        <span>petland-store.asena.company</span>
                                    </div>
                                    <a href="site.php?slug=petland-store" target="_blank" class="text-slate-400 hover:text-white" title="باز کردن در تب جدید">
                                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    </a>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-4 text-white space-y-3.5">
                                    <!-- Store Header & Species Filter -->
                                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-10 h-10 rounded-full bg-orange-600/30 border border-orange-500/50 flex items-center justify-center text-orange-400 font-bold text-sm">
                                                <span class="material-symbols-outlined text-xl">shopping_cart</span>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-black text-white">پت‌شاپ و هایپرمارکت پت‌لند</h4>
                                                <div class="text-[10px] text-slate-400">غذای خشک، کنسرو، تشویقی و بهداشتی</div>
                                            </div>
                                        </div>
                                        <span class="bg-orange-500/20 text-orange-300 text-[10px] px-2 py-0.5 rounded-full border border-orange-400/30 font-bold">نماد اعتماد و درگاه</span>
                                    </div>
                                    <!-- Species Tabs & Autoship Teaser -->
                                    <div class="bg-slate-900/90 rounded-xl p-3 border border-slate-800 space-y-2.5">
                                        <div class="grid grid-cols-4 gap-1 text-center text-[10px] font-bold">
                                            <div class="bg-orange-600 text-white py-1 rounded-lg">🐶 سگ</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">🐱 گربه</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">🦜 پرنده</div>
                                            <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">🐠 آبزیان</div>
                                        </div>
                                        <!-- Autoship Highlight Strip -->
                                        <div class="p-2 rounded-lg bg-orange-950/40 border border-orange-500/30 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-1.5 text-orange-300 font-black text-[11px]">
                                                <span class="material-symbols-outlined text-sm">autorenew</span>
                                                <span>تحویل دوره‌ای اتوشیپ</span>
                                            </div>
                                            <span class="bg-orange-500 text-white text-[9px] px-1.5 py-0.5 rounded font-black">۱۰٪ تخفیف دائمی</span>
                                        </div>
                                    </div>
                                    <!-- Product Card Simulation -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-orange-400 text-lg">package_2</span>
                                            <div class="text-[10px] leading-tight text-slate-300">غذای رویال کنین مینی (موجود در انبار)</div>
                                        </div>
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-emerald-400 text-lg">local_shipping</span>
                                            <div class="text-[10px] leading-tight text-slate-300">ارسال به تمام کشور با پیشتاز</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=petland-store" target="_blank" class="w-full py-2.5 bg-orange-600 hover:bg-orange-500 rounded-xl text-center text-xs font-black transition-colors flex items-center justify-center gap-1.5 text-white shadow-md">
                                        <span>مشاهده وب‌سایت زنده پت‌شاپ پت‌لند</span>
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Mobile PWA Smartphone Frame (Hidden by default) -->
                            <div id="arch-mockup-mobile-seller" class="hidden w-full max-w-[280px] bg-slate-900 rounded-[2.8rem] p-3 shadow-2xl border-4 border-slate-700 relative group overflow-hidden transition-all duration-300">
                                <div class="w-24 h-4 bg-black rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-slate-800 mr-2"></span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-3 text-white space-y-3 text-xs">
                                    <div class="text-center space-y-1">
                                        <div class="w-10 h-10 rounded-full bg-orange-600/30 border border-orange-500/50 mx-auto flex items-center justify-center text-orange-300">
                                            <span class="material-symbols-outlined text-lg">pets</span>
                                        </div>
                                        <div class="font-black text-xs text-white">پت‌شاپ تخصصی پت‌لند</div>
                                        <div class="text-[9px] text-orange-300">تخفیف ۱۰٪ خرید دوره‌ای اتوشیپ</div>
                                    </div>
                                    <div class="bg-slate-900 rounded-xl p-2 border border-slate-800 space-y-1 text-center">
                                        <div class="text-[10px] text-orange-400 font-bold">خرید آنلاین و تحویل اکسپرس</div>
                                        <div class="text-[9px] text-slate-400">تنوع بیش از ۱۲۰۰ کالای معتبر</div>
                                    </div>
                                    <a href="site.php?slug=petland-store" target="_blank" class="w-full py-2 bg-orange-600 hover:bg-orange-500 rounded-lg text-center text-[11px] font-black transition-colors block text-white">
                                        ورود به نسخه موبایل PWA ↗
                                    </a>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-2.5 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-orange-600">check_circle</span>
                                <span>پالت اختصاصی: نارنجی پویا و پرانرژی (#ea580c) و زرد عسلی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 4: Hospital / Organization Archetype (Multi-Department Ecosystem) -->
            <div id="arch-panel-organization" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-slate-200 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-900 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-blue-700">emergency</span>
                                <span>الگوی تخصصی مجتمع‌های بیمارستانی، اورژانس ۲۴/۷ و دپارتمان‌های پاراکلینیک</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت بیمارستان‌ها، پلی‌کلینیک‌ها و اورژانس شبانه‌روزی
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                ساختار فوق‌پیشرفته و سازمانی برای مراکز با کادر درمانی چندنفره، بخش بستری و آزمایشگاه. مجهز به نوار اورژانس قرمز رنگ با تماس تک‌لمسی، دایرکتوری بخش‌های تخصصی (جراحی، سونوگرافی، رادیولوژی، ICU) و نمایش روزهای حضور پزشکان.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-rose-600 text-xl shrink-0 mt-0.5">e911_emergency</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">نوار قرمز اورژانس شبانه‌روزی و تریاژ ۲۴/۷ با اعزام آمبولانس</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">دکمه تماس بدون معطلی با اورژانس و نقشه مسیریابی مستقیم به درب بیمارستان.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-blue-700 text-xl shrink-0 mt-0.5">grid_view</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">چیدمان بنتو برای دپارتمان‌های تخصصی و تجهیزات تشخیصی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">معرفی بخش‌های جراحی ارتوپدی، اکوکاردیوگرافی، آندوسکوپی و بانک خون حیوانات.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-teal-600 text-xl shrink-0 mt-0.5">groups</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">کارتابل معرفی پزشکان همکار با برنامه شیفت‌های بالینی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">قابلیت رزرو آنلاین نوبت مستقیماً برای پزشک و دپارتمان مدنظر مراجع.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-indigo-600 text-xl shrink-0 mt-0.5">policy</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">صدور فاکتور رسمی بیمه و قوانین ملاقات بخش بستری</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">نمایش شرایط ناشتایی قبل از جراحی، تعرفه مصوب و لیست بیمه‌های طرف قرارداد.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=razi-hospital" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-[#001a48] hover:bg-[#002666] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-blue-900/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت بیمارستان</span>
                                </a>
                                <button type="button" onclick="openOrderModal('organization')" class="px-6 py-3.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-amber-300">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase (Interactive Desktop / Mobile PWA) -->
                        <div class="lg:col-span-5 flex flex-col items-center w-full">
                            <!-- Device Switcher Controls -->
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
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-900 border border-blue-200 text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                    <span>دمو فعال و آنلاین</span>
                                </div>
                            </div>

                            <!-- 1. Desktop Browser Frame -->
                            <div id="arch-mockup-desktop-organization" class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden transition-all duration-300">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-300 font-bold">
                                        <span class="material-symbols-outlined text-xs text-teal-400">lock</span>
                                        <span>razi-hospital.asena.company</span>
                                    </div>
                                    <a href="site.php?slug=razi-hospital" target="_blank" class="text-slate-400 hover:text-white" title="باز کردن در تب جدید">
                                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    </a>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-4 text-white space-y-3">
                                    <!-- 24/7 Red Siren Emergency Bar -->
                                    <div class="p-2 rounded-xl bg-gradient-to-r from-rose-900/80 via-red-800/80 to-rose-900/80 border border-rose-500/50 flex items-center justify-between text-xs animate-pulse">
                                        <div class="flex items-center gap-1.5 text-rose-200 font-black">
                                            <span class="material-symbols-outlined text-base text-rose-300">emergency</span>
                                            <span>اورژانس و تریاژ ۲۴/۷ شبانه‌روزی</span>
                                        </div>
                                        <span class="bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">تماس فوری: ۰۲۱-۸۸۸۸xxxx</span>
                                    </div>
                                    <!-- Hospital Name & Department Roster -->
                                    <div class="border-b border-slate-800/80 pb-2.5 space-y-1">
                                        <h4 class="text-sm font-black text-white">بیمارستان تخصصی دامپزشکی رازی</h4>
                                        <div class="text-[10px] text-slate-400">مجتمع درمانی ارجاعی، جراحی پیشرفته و رادیولوژی دیجیتال</div>
                                    </div>
                                    <!-- Department Navigation Tabs Simulation -->
                                    <div class="grid grid-cols-4 gap-1 text-center text-[10px] font-bold">
                                        <div class="bg-blue-900/80 text-blue-200 py-1 rounded-lg border border-blue-700/50">جراحی</div>
                                        <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">رادیولوژی</div>
                                        <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">بستری ICU</div>
                                        <div class="bg-slate-800 text-slate-300 py-1 rounded-lg">آزمایشگاه</div>
                                    </div>
                                    <!-- On-duty stats -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-teal-400 text-lg">medical_services</span>
                                            <div class="text-[10px] leading-tight text-slate-300">۳ پزشک مقیم شیفت شب</div>
                                        </div>
                                        <div class="bg-white/5 rounded-xl p-2 border border-white/10 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-rose-400 text-lg">airport_shuttle</span>
                                            <div class="text-[10px] leading-tight text-slate-300">اعزام فوری آمبولانس</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=razi-hospital" target="_blank" class="w-full py-2.5 bg-[#001a48] hover:bg-[#022869] border border-white/20 rounded-xl text-center text-xs font-black transition-colors flex items-center justify-center gap-1.5 text-white shadow-md">
                                        <span>مشاهده وب‌سایت زنده بیمارستان رازی</span>
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Mobile PWA Smartphone Frame (Hidden by default) -->
                            <div id="arch-mockup-mobile-organization" class="hidden w-full max-w-[280px] bg-slate-900 rounded-[2.8rem] p-3 shadow-2xl border-4 border-slate-700 relative group overflow-hidden transition-all duration-300">
                                <div class="w-24 h-4 bg-black rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-slate-800 mr-2"></span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 p-3 text-white space-y-3 text-xs">
                                    <div class="p-2 rounded-lg bg-rose-900/80 text-center text-rose-200 font-black text-[10px] border border-rose-600/40">
                                        🚨 تریاژ و اورژانس ۲۴ ساعته فعال
                                    </div>
                                    <div class="text-center space-y-1">
                                        <div class="font-black text-xs text-white">بیمارستان تخصصی رازی</div>
                                        <div class="text-[9px] text-teal-300">کادر درمانی چندتخصصی و ICU</div>
                                    </div>
                                    <div class="bg-slate-900 rounded-xl p-2 border border-slate-800 space-y-1 text-center">
                                        <div class="text-[10px] text-rose-400 font-bold">تماس فوری اورژانس</div>
                                        <div class="text-[9px] text-slate-400">مسیریابی مستقیم درب اورژانس</div>
                                    </div>
                                    <a href="site.php?slug=razi-hospital" target="_blank" class="w-full py-2 bg-[#001a48] hover:bg-[#022869] border border-white/20 rounded-lg text-center text-[11px] font-black transition-colors block text-white">
                                        ورود به نسخه موبایل PWA ↗
                                    </a>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-2.5 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-[#001a48]">check_circle</span>
                                <span>پالت اختصاصی: سرمه‌ای سازمانی (#001a48)، فیروزه‌ای و قرمز اورژانس</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 3. Beat 3: Ecosystem Advantages & Built-in Superpowers (Bento Grid) -->
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
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">style</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">استودیو ویرایش بدون کد (Visual Studio)</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    با استودیوی اختصاصی آسنا، در هر ساعت از شبانه‌روز می‌توانید متون، لوگو، تصاویر، رنگ‌بندی و جایگاه بلوک‌ها را با پیش‌نمایش آنی شخصی‌سازی کنید.
                </p>
            </div>

            <!-- Bento 2 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">کارت ویزیت دیجیتال و استند رومیزی QR</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    همراه با وب‌سایت، کارت ویزیت هوشمند با فایل مستقیم مخاطب (vCard) در اختیار شماست تا مراجعین با اسکن آن در مطب، اطلاعات شما را در گوشی ذخیره کنند.
                </p>
            </div>

            <!-- Bento 3 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">verified_user</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">درگاه رسمی شاپرک و امانت‌داری مالی</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    تسویه حساب مستقیم بانکی، صدور فاکتور رسمی نظام دامپزشکی، گزارش فصلی ماده ۱۶۹ و امنیت مالی ۱۰۰٪ با سیستم ضمانت امن (Escrow).
                </p>
            </div>

            <!-- Bento 4 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">travel_explore</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">سئو تضمینی گوگل (Local SEO)</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    تولید خودکار داده‌های ساختاریافته Schema.org، ثبت ساعات کاری، لوکیشن نقشه و مقالات وبلاگ برای حضور در نتایج صفحه اول جستجوی محلی گوگل.
                </p>
            </div>

            <!-- Bento 5 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">install_mobile</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">فول PWA و عملکرد فوق‌سریع در موبایل</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    وب‌سایت شما دقیقاً شبیه یک اپلیکیشن بومی روی گوشی‌های اندروید و iOS نصب می‌شود و در شرایط اینترنت ضعیف نیز عملکرد باثباتی دارد.
                </p>
            </div>

            <!-- Bento 6 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
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

    <!-- 4. Beat 4: Dedicated Website Editions (No Class Discrimination - Tailored for Use Cases) -->
    <section id="website-editions" class="space-y-8 scroll-mt-24">
        <div id="pricing-matrix" class="hidden"></div>
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-black">
                <span class="material-symbols-outlined text-sm text-indigo-600">tune</span>
                <span>انعطاف‌پذیری ۱۰۰٪ بر اساس نیاز واقعی و صنف شما</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                نسخه‌های تخصصی وب‌سایت آسنا
            </h2>
            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto">
                بدون طبقه‌بندی کیفی و بدون امکانات قفل‌شده! در آسنا هیچ وب‌سایتی «درجه دو» یا محدود نیست؛ همه نسخه‌ها وب‌سایت کامل، مستقل و با امکانات زیرساختی ۱۰۰٪ هستند و تفاوت آن‌ها در ماژول‌های ویژه متناسب با شیوه کاری شماست.
            </p>
        </div>

        <!-- Shared Foundation Assurance Banner -->
        <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-slate-950 via-[#001a48] to-slate-900 text-white border border-white/10 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4 text-xs sm:text-sm">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-amber-400/20 border border-amber-400/40 flex items-center justify-center text-amber-300 shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
                <div>
                    <div class="font-black text-white text-sm sm:text-base flex items-center gap-2">
                        <span>امکانات زیرساختی استاندارد در تمامی نسخه‌ها بدون استثنا</span>
                        <span class="hidden sm:inline-block px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-400/30">۱۰۰٪ فول امکانات</span>
                    </div>
                    <div class="text-slate-300 text-xs mt-1 leading-relaxed">
                        دامنه مستقل (.ir / .com) • هاست ابری نامحدود با SSL رایگان • درگاه شاپرک با تسویه مستقیم پایا • کارت هوشمند QR و استند مطب • بدون کارمزد تراکنش • استودیو ویرایشگر زنده • پشتیبانی فنی مداوم
                    </div>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/10 text-amber-300 font-bold text-xs shrink-0 border border-white/15 backdrop-blur-sm">
                <span class="material-symbols-outlined text-sm">lock_open</span>
                <span>بدون هیچ قابلیت قفل‌شده</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Edition 1: Doctor / Clinic Edition -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border-2 border-emerald-200 shadow-lg hover:shadow-xl hover:border-emerald-400 transition-all flex flex-col justify-between space-y-6 relative group">
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
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">ساختار اختصاصی اتوریتی پزشکی و نوبت‌دهی آنلاین بدون اتلاف وقت منشی مطب.</p>
                    </div>
                    <div class="py-3 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-emerald-800 font-mono">۷,۹۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span class="font-bold">سامانه تقویم نوبت‌دهی آنلاین و تایم‌اسلات‌ها</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>اسلایدر تعاملی درمان قبل و بعد (Before/After)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>محاسبه‌گر شفاف تعرفه خدمات با ۱۰٪ تخفیف آنلاین</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>تابلوی بیوگرافی و استعلام پروانه نظام دامپزشکی</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>کارت ویزیت دیجیتال و استند رومیزی هوشمند QR</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>وبلاگ و دانشنامه سئو محلی نتایج اول گوگل</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>ارسال پیامک تایید و یادآوری وقت ویزیت به بیمار</span>
                        </li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal('doctor')" class="w-full py-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs shadow-md hover:shadow-emerald-700/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                    <span>انتخاب نسخه مطب و پزشکان</span>
                </button>
            </div>

            <!-- Edition 2: Pharmacy Edition -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border-2 border-purple-200 shadow-lg hover:shadow-xl hover:border-purple-400 transition-all flex flex-col justify-between space-y-6 relative group">
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
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">سامانه پذیرش نسخه، پایش دمای زنجیره سرد و عرضه داروهای کمیاب و واکسن.</p>
                    </div>
                    <div class="py-3 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-purple-800 font-mono">۹,۴۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span class="font-bold">سامانه آپلود و پذیرش عکس نسخه پزشک (Rx)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>دیده‌بان زنده پایش دمای زنجیره سرد (۲ تا ۸ درجه)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>کاتالوگ آنلاین داروهای کمیاب، واکسن و مکمل‌ها</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>پایشگر هوشمند تداخلات دارویی و راهنمای مصرف</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>ارسال اکسپرس در بسته‌بندی عایق یونولیت و ژل یخ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>درگاه پرداخت مستقیم شاپرک و تسویه حساب پایا</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>صدور پیش‌فاکتور دیجیتال و پیگیری لحظه‌ای مرسوله</span>
                        </li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal('pharmacist')" class="w-full py-3.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-black text-xs shadow-md hover:shadow-purple-700/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                    <span>انتخاب نسخه داروخانه تخصصی</span>
                </button>
            </div>

            <!-- Edition 3: Pet Shop Edition -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border-2 border-orange-200 shadow-lg hover:shadow-xl hover:border-orange-400 transition-all flex flex-col justify-between space-y-6 relative group">
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
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">فروشگاه آنلاین با قابلیت خرید دوره‌ای خودکار جهت ایجاد درآمد مستمر ماهانه.</p>
                    </div>
                    <div class="py-3 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-orange-800 font-mono">۱۰,۸۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span class="font-bold">سرویس خرید دوره‌ای ماهانه با تخفیف (Autoship)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>فیلتر هوشمند کالاها بر اساس گونه (سگ، گربه، پرنده) و سن</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>ویترین شگفت‌انگیزها و شمارشگر معکوس تخفیف</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>انبارداری هوشمند و هشدار خودکار اتمام موجودی</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>باشگاه مشتریان، نظرات خریداران و کدهای تخفیف</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>سبد خرید هوشمند و محاسبه هزینه ارسال پست و پیک</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-orange-600 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>تسویه حساب مستقیم و آنی پایا به شماره شبا</span>
                        </li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal('seller')" class="w-full py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs shadow-md hover:shadow-orange-600/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                    <span>انتخاب نسخه پت‌شاپ و فروشگاه</span>
                </button>
            </div>

            <!-- Edition 4: Hospital Edition -->
            <div class="bg-gradient-to-b from-[#001a48] to-[#011438] text-white rounded-3xl p-6 sm:p-7 border-2 border-blue-400/30 shadow-xl hover:shadow-2xl transition-all flex flex-col justify-between space-y-6 relative group">
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
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">اکوسیستم همه‌جانبه مراکز بزرگ با کادر چندنفره، دپارتمان‌های پاراکلینیک و بستری.</p>
                    </div>
                    <div class="py-3 border-y border-white/10">
                        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono">۱۶,۵۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-300 mt-0.5">تومان / سالانه (شامل هاست، دامنه و پشتیبانی VIP)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-200">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span class="font-bold">نوار قرمز تریاژ اورژانس ۲۴ ساعته و اعزام آمبولانس</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>ساختار چنددپارتمانه (جراحی، رادیولوژی، بستری، ICU)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>کارتابل معرفی پزشکان متخصص و برنامه شیفت‌ها</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>سامانه نوبت‌دهی تفکیک‌شده به ازای هر بخش و پزشک</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>قوانین بستری، شرایط ناشتایی و بیمه‌های طرف قرارداد</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>کارتابل چندکاربره با دسترسی منشی، پذیرش و مدیریت</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>مانیتورینگ مداوم سرور، پشتیبانی VIP و توافق SLA</span>
                        </li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal('organization')" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-[#fd8100] via-orange-500 to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs shadow-lg hover:shadow-orange-500/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                    <span>انتخاب نسخه بیمارستان و اورژانس</span>
                </button>
            </div>

        </div>
    </section>

    <!-- 5. Beat 5: FAQ Accordion -->
    <section class="max-w-4xl mx-auto space-y-6">
        <div class="text-center space-y-2">
            <h2 class="text-xl sm:text-3xl font-black text-slate-900">پرسش‌های پرتکرار متقاضیان وب‌سایت</h2>
            <p class="text-xs sm:text-sm text-slate-500">پاسخ شفاف به دغدغه‌های راه‌اندازی و نگهداری وب‌سایت در آسنا</p>
        </div>

        <div class="space-y-3">
            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا می‌توانم دامنه اختصاصی مطب خودم (مثلاً dr-alavi.ir یا .com) را وصل کنم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    بله، ۱۰۰٪. تمام وب‌سایت‌های آسنا به صورت پیش‌فرض یک ساب‌دامین پرسرعت و رایگان (yourname.asena.company) دریافت می‌کنند، اما شما در هر زمان می‌توانید دامنه ملی (.ir) یا بین‌المللی (.com) خود را به سادگی از طریق تنظیم DNS به وب‌سایتتان متصل نمایید و گواهی SSL امن به رایگان برای شما فعال خواهد شد.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>چقدر طول می‌کشد تا وب‌سایت من آماده و در اینترنت منتشر شود؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    وب‌سایت شما بلافاصله پس از ثبت سفارش و تایید تلفنی در کمتر از ۵ دقیقه ایجاد و فعال می‌گردد. همچنین اطلاعات اولیه مطب، ساعات کاری و خدمات شما توسط کارشناس پشتیبانی آسنا ظرف حداکثر ۲۴ ساعت کاری در سایت بارگذاری و تحویل داده می‌شود.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا می‌توانم در آینده قالب یا حوزه فعالیتم را تغییر دهم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    بله! با ورود به «استودیو طراحی وب‌سایت آسنا»، می‌توانید در هر لحظه الگوی ساختاری و رنگ‌بندی سایت را بین قالب‌های پزشک، داروخانه، پت‌شاپ یا بیمارستان سوئیچ کنید، بلوک‌ها را جابجا نمایید و ماژول‌های جدید را تنها با یک کلیک فعال کنید.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا برای هاست، ترافیک ماهانه یا پشتیبانی فنی باید هزینه جداگانه‌ای بپردازم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    خیر، هیچ هزینه پنهانی وجود ندارد. کلیه هزینه‌های میزبانی ابری با پهنای باند نامحدود، بک‌آپ‌گیری روزانه، آپدیت‌های امنیتی مداوم و گواهی رمزنگاری SSL در اشتراک سالانه گنجانده شده است.
                </div>
            </details>
        </div>
    </section>

    <!-- 6. Beat 6: Final Conversion CTA -->
    <section class="rounded-3xl bg-gradient-to-r from-amber-500 via-[#fd8100] to-orange-600 p-8 sm:p-12 text-white text-center space-y-4 shadow-xl">
        <h2 class="text-2xl sm:text-3xl font-black">آماده‌اید وب‌سایت اختصاصی خود را تحویل بگیرید؟</h2>
        <p class="text-xs sm:text-base text-amber-100 max-w-xl mx-auto font-medium">
            همین حالا شناسه دلخواهتان را رزرو کنید و با راه‌اندازی سریع وب‌سایت، یک قدم بزرگ به سمت اتوماسیون مطب و افزایش مراجعین بردارید.
        </p>
        <div class="pt-2">
            <button type="button" onclick="openOrderModal()" class="px-8 py-4 rounded-2xl bg-slate-900 hover:bg-black text-white font-black text-sm shadow-2xl transition-all cursor-pointer inline-flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-amber-400 text-lg">rocket_launch</span>
                <span>همین الان سفارش خود را ثبت کنید</span>
            </button>
        </div>
    </section>

</div>

<!-- Interactive Order / Purchase Modal -->
<div id="websiteOrderModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-[2rem] max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6 relative text-right animate-scale-in my-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#fd8100]">web</span>
                    <span>ثبت سفارش و راه‌اندازی وب‌سایت</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">تکمیل فرم در ۲ دقیقه؛ تحویل فوری با پشتیبانی کارشناسان آسنا</p>
            </div>
            <button type="button" onclick="closeOrderModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Form -->
        <form id="websiteOrderForm" onsubmit="submitWebsiteOrder(event)" class="space-y-4">
            
            <!-- Step 1: Select Dedicated Edition -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">۱. انتخاب نسخه تخصصی وب‌سایت متناسب با حوزه فعالیت *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50 hover:border-emerald-300 transition-all">
                        <div class="flex items-center gap-2.5">
                            <input type="radio" name="order_archetype" value="doctor" data-tier="standard" checked class="text-emerald-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-emerald-600 text-sm">stethoscope</span>
                                    <span>مطب و نوبت‌دهی بالینی</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">پزشکان، جراحان و کلینیک‌های تک‌پزشک</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-emerald-800 bg-white px-2 py-1 rounded-lg border border-emerald-100 shadow-xs">۷,۹۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50/50 hover:border-purple-300 transition-all">
                        <div class="flex items-center gap-2.5">
                            <input type="radio" name="order_archetype" value="pharmacist" data-tier="pharmacy" class="text-purple-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-purple-600 text-sm">medication</span>
                                    <span>داروخانه و دراگ‌استور هوشمند</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">آپلود نسخه و پایش زنجیره سرد</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-purple-800 bg-white px-2 py-1 rounded-lg border border-purple-100 shadow-xs">۹,۴۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50/50 hover:border-orange-300 transition-all">
                        <div class="flex items-center gap-2.5">
                            <input type="radio" name="order_archetype" value="seller" data-tier="premium" class="text-orange-600" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-orange-600 text-sm">pets</span>
                                    <span>پت‌شاپ و فروشگاه ملزومات</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">خرید دوره‌ای خودکار (Autoship) و انبارداری</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-orange-800 bg-white px-2 py-1 rounded-lg border border-orange-100 shadow-xs">۱۰,۸۰۰,۰۰۰ ت</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50/50 hover:border-blue-300 transition-all">
                        <div class="flex items-center gap-2.5">
                            <input type="radio" name="order_archetype" value="organization" data-tier="enterprise" class="text-blue-700" onchange="updateSelectedEditionTier(this)">
                            <div>
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-blue-700 text-sm">emergency</span>
                                    <span>بیمارستان و اورژانس شبانه‌روزی</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">چنددپارتمانه، بستری و تریاژ ۲۴ ساعته</div>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono font-black text-blue-800 bg-white px-2 py-1 rounded-lg border border-blue-100 shadow-xs">۱۶,۵۰۰,۰۰۰ ت</span>
                    </label>
                </div>
                <!-- Hidden Tier Parameter to maintain backend compatibility -->
                <input type="hidden" name="order_tier" id="order_tier" value="standard">
            </div>

            <!-- Step 3: Desired Subdomain -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">۳. شناسه و آدرس ساب‌دامین اختصاصی مورد نظر *</label>
                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-600">
                    <span class="px-3 text-xs text-slate-400 font-mono bg-slate-50 border-l border-slate-200">.asena.company</span>
                    <input type="text" name="order_desired_slug" id="order_desired_slug" oninput="checkOrderSlug(this.value)" placeholder="مثلاً dr-alavi" class="flex-1 text-xs p-3 outline-none font-mono text-left font-bold" dir="ltr" required>
                </div>
                <div id="order-slug-feedback" class="text-[11px] mt-1 text-slate-400">شناسه اختصاصی سایت شما در اینترنت</div>
            </div>

            <!-- Step 4: Contact Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">نام و نام خانوادگی / نام مرکز *</label>
                    <input type="text" name="order_full_name" required placeholder="مثلاً دکتر محمدرضا علوی" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">شماره تماس همراه (جهت هماهنگی) *</label>
                    <input type="tel" name="order_phone" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-mono font-bold text-left">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">ایمیل (اختیاری)</label>
                <input type="email" name="order_email" placeholder="info@example.com" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-mono text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">یادداشت یا توضیحات ویژه (اختیاری)</label>
                <textarea name="order_notes" rows="2" placeholder="اگر دامنه خاصی مدنظر دارید یا توضیحی برای کادر فنی دارید بنویسید..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none"></textarea>
            </div>

            <!-- Submit Button & Feedback -->
            <div id="order-form-feedback" class="hidden text-xs font-bold p-3 rounded-xl"></div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <button type="button" onclick="closeOrderModal()" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                    انصراف
                </button>
                <button type="submit" id="order-submit-btn" class="px-7 py-3 rounded-xl bg-gradient-to-r from-[#fd8100] to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span>تایید و ارسال درخواست فعال‌سازی</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// Archetype tabs switcher
function selectArchetypeTab(archetype) {
    document.querySelectorAll('.arch-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.arch-tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'text-emerald-800', 'text-purple-800', 'text-orange-800', 'text-blue-800', 'border', 'border-emerald-200', 'border-purple-200', 'border-orange-200', 'border-blue-200', 'shadow-sm');
        b.classList.add('text-slate-600');
    });

    const activePanel = document.getElementById('arch-panel-' + archetype);
    const activeBtn = document.getElementById('arch-tab-' + archetype);

    if (activePanel) activePanel.classList.remove('hidden');
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600');
        activeBtn.classList.add('bg-white', 'shadow-sm', 'border');
        if (archetype === 'doctor') activeBtn.classList.add('text-emerald-800', 'border-emerald-200');
        else if (archetype === 'pharmacist') activeBtn.classList.add('text-purple-800', 'border-purple-200');
        else if (archetype === 'seller') activeBtn.classList.add('text-orange-800', 'border-orange-200');
        else if (archetype === 'organization') activeBtn.classList.add('text-blue-800', 'border-blue-200');
    }
}

// Archetype Device Switcher (Desktop Frame vs Mobile PWA Frame)
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

// Update tier based on chosen dedicated edition
function updateSelectedEditionTier(radioEl) {
    const tierInput = document.getElementById('order_tier');
    if (radioEl && tierInput) {
        tierInput.value = radioEl.getAttribute('data-tier') || 'standard';
    }
}

// Hero Subdomain Live Checker
let heroTimer = null;
function checkSubdomainFromHero() {
    const input = document.getElementById('hero-subdomain-input');
    const feedback = document.getElementById('hero-subdomain-feedback');
    const slug = (input ? input.value : '').trim();

    if (!slug) {
        feedback.className = 'mt-3 text-xs md:text-sm font-bold text-amber-300 block';
        feedback.innerText = 'لطفاً یک نام انگلیسی تایپ کنید.';
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

// Modal management
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

// Live slug checker inside order modal
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
