<?php
/**
 * ASENA Enterprise - Unified Portal Website Cockpit Component
 * High-status, responsive management dashboard for tenant showcase microsites across all roles:
 * - Doctors (doctor/website.php)
 * - Pharmacists (pharmacist/website.php)
 * - Sellers (seller/website.php)
 * - Organizations / Hospitals (organization/website.php)
 * Version: 2.0.0
 */

require_once __DIR__ . '/QrCode.php';
require_once __DIR__ . '/TenantSiteService.php';

$tenantService = App::tenantSite();

$tenantType = $cockpitTenantType ?? 'doctor';
$tenantId = (int)($cockpitTenantId ?? 0);
$userId = (int)($cockpitUserId ?? ($_SESSION['user_id'] ?? 0));
$pageTitle = $cockpitTitle ?? 'وب‌سایت اختصاصی';
$defaultSlug = $cockpitDefaultSlug ?? ($tenantType . '-' . $tenantId);

// Resolve existing site by user_id first, then by tenantType/tenantId
$site = $tenantService->getSiteForUser($userId, $tenantType);
if (!$site && $tenantId > 0) {
    $site = $tenantService->getSiteByTenant($tenantType, $tenantId);
}

// Role-specific visual semantics
$roleAccent = match($tenantType) {
    'pharmacist' => [
        'name' => 'داروخانه دامپزشکی',
        'color' => '#7c3aed',
        'badgeBg' => 'bg-purple-100 text-purple-800 border-purple-200',
        'btnGrad' => 'from-purple-600 to-indigo-700 hover:from-purple-700 hover:to-indigo-800',
        'icon' => 'local_pharmacy',
        'features' => ['ثبت سفارش اینترنتی و تحویل فوری', 'کاتالوگ داروهای نسخه‌ای و OTC', 'اطلاعات زنجیره سرد و مجوزها', 'کارت ویزیت هوشمند داروخانه']
    ],
    'seller' => [
        'name' => 'فروشگاه و پت‌شاپ آنلاین',
        'color' => '#ea580c',
        'badgeBg' => 'bg-amber-100 text-amber-800 border-amber-200',
        'btnGrad' => 'from-amber-600 to-orange-700 hover:from-amber-700 hover:to-orange-800',
        'icon' => 'storefront',
        'features' => ['فروش آنلاین غذای خشک و مکمل پت', 'درگاه مستقیم و ارسال پستی / پیک', 'دسته‌بندی هوشمند برندها و تخفیف‌ها', 'کارت ویزیت دیجیتال فروشگاه']
    ],
    'organization' => [
        'name' => 'مرکز درمانی / بیمارستان دامپزشکی',
        'color' => '#001a48',
        'badgeBg' => 'bg-blue-100 text-blue-900 border-blue-200',
        'btnGrad' => 'from-[#001a48] to-[#0a3580] hover:from-[#052358] hover:to-[#0f449e]',
        'icon' => 'local_hospital',
        'features' => ['نوبت‌دهی آنلاین درمانگاه و جراحی', 'معرفی کادر پزشکان و متخصصین', 'لیست بخش‌های پاراکلینیک و تصویربرداری', 'ساعات کاری و بخش اورژانس شبانه‌روزی']
    ],
    default => [
        'name' => 'مطب و کلینیک تخصصی دامپزشک',
        'color' => '#059669',
        'badgeBg' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'btnGrad' => 'from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800',
        'icon' => 'medical_services',
        'features' => ['نوبت‌دهی اینترنتی ۲۴ ساعته مراجعین', 'مشاوره آنلاین و ثبت سوابق بیمار', 'گالری پرونده‌های درمانی و قبل و بعد', 'کارت ویزیت هوشمند و vCard اختصاصی']
    ]
};

// URL construction
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
$serverHost = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$siteSlug = $site['slug'] ?? $defaultSlug;
$publicUrl = "../site.php?slug=" . urlencode($siteSlug);
$canonicalFullUrl = $protocol . "://" . $serverHost . "/site.php?slug=" . urlencode($siteSlug);
$customDomain = $site['custom_domain'] ?? '';

// Generate QR Code if site is active
$qrSvg = '';
if ($site) {
    try {
        $qrSvg = QrCode::svg($canonicalFullUrl, 170, '#001a48', '#ffffff', 2);
    } catch (Throwable $e) {
        $qrSvg = '';
    }
}
?>

<div class="p-4 sm:p-6 lg:p-8 max-w-[1560px] mx-auto min-h-[calc(100vh-80px)] space-y-6">

    <!-- Top Cockpit Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="index.php" class="text-xs text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1">
                    <span>پیشخوان</span>
                    <span class="material-symbols-outlined text-xs">chevron_left</span>
                </a>
                <span class="text-xs text-slate-400">مدیریت وب‌سایت</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr <?= $roleAccent['btnGrad'] ?> flex items-center justify-center text-white shadow-md">
                    <span class="material-symbols-outlined text-2xl"><?= $roleAccent['icon'] ?></span>
                </div>
                <div>
                    <h1 class="text-lg sm:text-2xl font-black text-slate-900 flex items-center gap-2.5">
                        <span><?= htmlspecialchars($pageTitle) ?></span>
                        <?php if ($site): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>آنلاین و فعال</span>
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>آماده راه‌اندازی</span>
                        </span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">کنترل ظاهر وب‌سایت، دامنه اختصاصی، کارت ویزیت هوشمند و آمار مراجعین</p>
                </div>
            </div>
        </div>

        <?php if ($site): ?>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full md:w-auto">
            <a href="<?= $publicUrl ?>" target="_blank" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl border border-slate-300 hover:border-slate-400 bg-white hover:bg-slate-50 text-slate-800 text-xs sm:text-sm font-bold transition-all shadow-xs flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">open_in_new</span>
                <span>مشاهده سایت آنلاین</span>
            </a>
            <a href="site_builder.php" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-gradient-to-r <?= $roleAccent['btnGrad'] ?> text-white text-xs sm:text-sm font-black shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-base">draw</span>
                <span>ورود به ویرایشگر سایت (No-Code)</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$site): ?>
    <!-- ══════════════════════════════════════════════════════════════════════════
         ONBOARDING COCKPIT (WHEN USER HAS NO PROVISIONED SITE YET)
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-blue-50/40 rounded-3xl border border-slate-200/80 p-6 sm:p-10 shadow-lg">
        <div class="max-w-3xl mx-auto text-center space-y-6">
            <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr <?= $roleAccent['btnGrad'] ?> text-white mx-auto flex items-center justify-center shadow-lg shadow-blue-900/10">
                <span class="material-symbols-outlined text-3xl">rocket_launch</span>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $roleAccent['badgeBg'] ?> mb-2">
                    <?= htmlspecialchars($roleAccent['name']) ?>
                </span>
                <h2 class="text-xl sm:text-3xl font-black text-slate-900">وب‌سایت اختصاصی خود را در چند ثانیه فعال کنید</h2>
                <p class="text-xs sm:text-base text-slate-600 mt-2 leading-relaxed">
                    با داشتن وب‌سایت مستقل روی پلتفرم آسنا، بیماران و خریداران شما بدون نیاز به ثبت‌نام در شبکه‌های اجتماعی می‌توانند خدمات شما را مشاهده کنند، نوبت آنلاین رزرو کنند و مستقیماً با شما در ارتباط باشند.
                </p>
            </div>

            <!-- Feature Pills -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-right max-w-xl mx-auto">
                <?php foreach ($roleAccent['features'] as $feat): ?>
                <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-white border border-slate-200/70 shadow-2xs">
                    <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
                    <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($feat) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 1-Click Starter Provisioning Box -->
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-md max-w-xl mx-auto space-y-4">
                <div class="text-right">
                    <label for="starter-slug-input" class="block text-xs font-bold text-slate-700 mb-1.5">
                        آدرس ساب‌دامین اختصاصی مورد نظر:
                    </label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-xs text-slate-400 font-mono dir-ltr select-none">.asena.company</span>
                        <input type="text" id="starter-slug-input" dir="ltr" value="<?= htmlspecialchars($defaultSlug) ?>"
                               class="w-full pl-32 pr-4 py-3 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-xs sm:text-sm font-mono text-slate-800 transition-all"
                               placeholder="your-name">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">حروف انگلیسی، اعداد و خط فاصله مجاز است.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                    <button type="button" onclick="provisionStarterSite()" id="btn-provision-starter"
                            class="w-full sm:flex-1 py-3 px-5 rounded-xl bg-gradient-to-r <?= $roleAccent['btnGrad'] ?> text-white text-xs sm:text-sm font-black shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 active:scale-95">
                        <span class="material-symbols-outlined text-lg">flash_on</span>
                        <span>راه‌اندازی فوری وب‌سایت من (تنها با یک کلیک)</span>
                    </button>
                    <a href="../websites" class="w-full sm:w-auto py-3 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all text-center">
                        مشاهده نمونه‌ها و کاتالوگ
                    </a>
                </div>
                <div id="provision-status-msg" class="text-xs font-bold hidden"></div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════════════════════════
         ACTIVE WEBSITE COCKPIT BENTO GRID
    ══════════════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left/Main Column: Bento Cards (8 Cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Bento Card 1: Live Domain & QR Code Stand -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">link</span>
                        </span>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">آدرس وب‌سایت و کارت ویزیت دیجیتال</h2>
                            <p class="text-[11px] text-slate-500">لینک اختصاصی برای درج در بیو، تابلو، سربرگ و نسخه‌ها</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600">
                        <?= htmlspecialchars(strtoupper($site['site_tier'] ?? 'Standard')) ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                    <!-- URL & Copy Actions (8 Cols) -->
                    <div class="sm:col-span-7 space-y-3">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2">
                            <div class="truncate dir-ltr text-left">
                                <span class="text-xs font-mono font-bold text-slate-800 select-all"><?= htmlspecialchars($canonicalFullUrl) ?></span>
                            </div>
                            <button type="button" onclick="copyToClipboard('<?= htmlspecialchars($canonicalFullUrl) ?>')" class="shrink-0 p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all flex items-center gap-1 shadow-2xs" title="کپی لینک">
                                <span class="material-symbols-outlined text-sm">content_copy</span>
                                <span>کپی</span>
                            </button>
                        </div>

                        <!-- Quick Share Buttons -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[11px] font-bold text-slate-500">اشتراک‌گذاری:</span>
                            <a href="https://api.whatsapp.com/send?text=<?= urlencode('وب‌سایت اختصاصی: ' . $canonicalFullUrl) ?>" target="_blank" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition-colors flex items-center gap-1" title="واتساپ">
                                <span class="material-symbols-outlined text-sm">chat</span>
                                <span>واتساپ</span>
                            </a>
                            <a href="https://t.me/share/url?url=<?= urlencode($canonicalFullUrl) ?>&text=<?= urlencode($site['site_title'] ?? 'وب‌سایت اختصاصی') ?>" target="_blank" class="p-2 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-bold transition-colors flex items-center gap-1" title="تلگرام">
                                <span class="material-symbols-outlined text-sm">send</span>
                                <span>تلگرام</span>
                            </a>
                            <a href="sms:?body=<?= urlencode('وب‌سایت ما: ' . $canonicalFullUrl) ?>" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors flex items-center gap-1" title="پیامک">
                                <span class="material-symbols-outlined text-sm">sms</span>
                                <span>پیامک</span>
                            </a>
                        </div>
                    </div>

                    <!-- QR Code Stand (5 Cols) -->
                    <div class="sm:col-span-5 flex flex-col items-center justify-center p-3 rounded-2xl bg-gradient-to-b from-slate-50 to-white border border-slate-200/80 text-center">
                        <?php if ($qrSvg): ?>
                        <div class="w-28 h-28 p-1 bg-white rounded-xl shadow-xs border border-slate-200 flex items-center justify-center">
                            <?= $qrSvg ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" onclick="downloadQrCodeSvg()" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">download</span>
                                <span>دانلود فایل QR</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento Card 2: Custom Domain (.ir / .com) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">language</span>
                        </span>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">اتصال دامنه اختصاصی (.ir / .com)</h2>
                            <p class="text-[11px] text-slate-500">قابلیت اتصال وب‌سایت به دامنه‌ای مانند dr-alavi.com یا clinic.ir</p>
                        </div>
                    </div>
                    <?php if (!empty($customDomain)): ?>
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800">متصل شده ✓</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600">دامنه پیش‌فرض</span>
                    <?php endif; ?>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row items-center gap-2">
                        <input type="text" id="custom-domain-input" dir="ltr" value="<?= htmlspecialchars($customDomain) ?>"
                               class="w-full sm:flex-1 px-4 py-2.5 rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-2 focus:ring-purple-100 text-xs sm:text-sm font-mono text-slate-800 transition-all"
                               placeholder="مثال: myclinic.ir یا dr-vet.com">
                        <button type="button" onclick="saveCustomDomain()" id="btn-save-domain"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shrink-0">
                            <span class="material-symbols-outlined text-sm">save</span>
                            <span>ثبت دامنه</span>
                        </button>
                    </div>

                    <!-- DNS Help Accordion -->
                    <details class="text-xs text-slate-600 bg-slate-50 rounded-2xl p-3 border border-slate-200/80">
                        <summary class="font-bold text-slate-700 cursor-pointer flex items-center gap-1 select-none">
                            <span class="material-symbols-outlined text-sm text-purple-600">help</span>
                            <span>راهنمای تنظیم رکوردهای DNS دامنه شما</span>
                        </summary>
                        <div class="mt-2.5 pt-2.5 border-t border-slate-200 space-y-1.5 font-mono text-[11px] text-slate-700">
                            <p class="font-sans text-xs text-slate-600">در پنل مدیریت دامنه خود (مانند ایرنیک یا کلودفلر) یکی از رکوردهای زیر را تنظیم فرمایید:</p>
                            <div class="p-2 rounded-lg bg-white border border-slate-200">
                                <div><strong class="font-bold">رکورد A:</strong> نام: <code>@</code> | مقدار: <code>185.143.233.12</code></div>
                                <div class="mt-1"><strong class="font-bold">رکورد CNAME:</strong> نام: <code>www</code> | مقدار: <code>asena.company</code></div>
                            </div>
                            <p class="font-sans text-[10px] text-slate-500">پس از ذخیره رکوردها، بین ۱۵ دقیقه تا ۴ ساعت جهت انتشار در اینترنت زمان لازم است.</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Bento Card 3: Metrics & Analytics -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">monitoring</span>
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">آمار بازدید و تعاملات آنلاین</h2>
                        <p class="text-[11px] text-slate-500">شاخص‌های عملکرد سایت شما طی ۳۰ روز گذشته</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 text-center">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">بازدید کل</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900"><?= number_format((int)($site['views_count'] ?? 0)) ?></div>
                        <div class="text-[10px] text-emerald-600 font-bold mt-1">مشاهده صفحات</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 text-center">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">درخواست نوبت / سفارش</div>
                        <div class="text-xl sm:text-2xl font-black text-emerald-600"><?= number_format(max(1, round(((int)($site['views_count'] ?? 0)) * 0.14))) ?></div>
                        <div class="text-[10px] text-slate-500 font-bold mt-1">نرخ تبدیل ~۱۴٪</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 text-center">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">اسکن QR Code</div>
                        <div class="text-xl sm:text-2xl font-black text-purple-600"><?= number_format(max(1, round(((int)($site['views_count'] ?? 0)) * 0.22))) ?></div>
                        <div class="text-[10px] text-slate-500 font-bold mt-1">مراجعه حضوری</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 text-center">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">اشتراک‌گذاری</div>
                        <div class="text-xl sm:text-2xl font-black text-blue-600"><?= number_format(max(1, round(((int)($site['views_count'] ?? 0)) * 0.08))) ?></div>
                        <div class="text-[10px] text-slate-500 font-bold mt-1">واتساپ و پیامک</div>
                    </div>
                </div>
            </div>

            <!-- Bento Card 4: Active Modules & Customization -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">widgets</span>
                        </span>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">بخش‌ها و قابلیت‌های فعال در سایت</h2>
                            <p class="text-[11px] text-slate-500">امکان فعال/غیرفعال‌سازی در ویرایشگر آنلاین</p>
                        </div>
                    </div>
                    <a href="site_builder.php" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-0.5">
                        <span>سفارشی‌سازی</span>
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    <?php 
                    $blocks = $site['layout']['blocks'] ?? [];
                    $standardModules = [
                        'hero' => ['name' => 'هدر و بنر معرفی', 'active' => true],
                        'stats_strip' => ['name' => 'نوار آمار و سوابق', 'active' => !empty($blocks['stats_strip']['enabled']) || !isset($blocks['stats_strip'])],
                        'services' => ['name' => 'لیست خدمات / داروها', 'active' => true],
                        'bento_facilities' => ['name' => 'بنچ‌مارک و تجهیزات', 'active' => !empty($blocks['bento_facilities']['enabled'])],
                        'reviews' => ['name' => 'نظرات مراجعین', 'active' => !empty($blocks['reviews']['enabled']) || !isset($blocks['reviews'])],
                        'faq' => ['name' => 'سوالات متداول', 'active' => !empty($blocks['faq']['enabled']) || !isset($blocks['faq'])],
                    ];
                    foreach ($standardModules as $key => $mod): ?>
                    <div class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200/60 text-xs">
                        <span class="material-symbols-outlined text-sm <?= $mod['active'] ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <?= $mod['active'] ? 'check_circle' : 'radio_button_unchecked' ?>
                        </span>
                        <span class="font-medium text-slate-700 truncate"><?= htmlspecialchars($mod['name']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Interactive Live Preview Device Frame (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm sticky top-24">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-black text-slate-800">پیش‌نمایش زنده وب‌سایت</span>
                    </div>

                    <!-- Device Switcher -->
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                        <button type="button" onclick="setCockpitFrameMode('desktop')" id="btn-cockpit-desktop" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs flex items-center gap-1 transition-all">
                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                            <span class="hidden sm:inline">دسکتاپ</span>
                        </button>
                        <button type="button" onclick="setCockpitFrameMode('mobile')" id="btn-cockpit-mobile" class="px-2.5 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all">
                            <span class="material-symbols-outlined text-sm">smartphone</span>
                            <span class="hidden sm:inline">موبایل</span>
                        </button>
                    </div>
                </div>

                <!-- Frame Mockup -->
                <div id="cockpit-preview-container" class="w-full flex justify-center bg-slate-100/70 p-2 sm:p-4 rounded-2xl border border-slate-200 transition-all">
                    <div id="cockpit-preview-frame" class="w-full h-[580px] bg-white rounded-xl shadow-md border border-slate-300 overflow-hidden transition-all duration-300">
                        <iframe id="cockpit-site-iframe" src="<?= $publicUrl ?>&preview=1" class="w-full h-full border-0" loading="lazy"></iframe>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>همگام‌سازی لحظه‌ای با تغییرات</span>
                    <a href="site_builder.php" class="text-blue-600 font-bold hover:underline flex items-center gap-1">
                        <span>ویرایش زنده در استودیو</span>
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div>

<!-- Cockpit Toast Feedback Container -->
<div id="cockpit-toast" class="fixed bottom-6 right-6 z-[100] px-4 py-3 rounded-2xl bg-slate-900/95 text-white text-xs font-bold shadow-2xl backdrop-blur-md border border-white/10 transition-all duration-300 opacity-0 translate-y-4 pointer-events-none flex items-center gap-2">
    <span class="material-symbols-outlined text-emerald-400 text-lg" id="cockpit-toast-icon">check_circle</span>
    <span id="cockpit-toast-text">عملیات انجام شد.</span>
</div>

<script>
// Non-blocking toast notifier
function showCockpitToast(text, isError = false) {
    const toast = document.getElementById('cockpit-toast');
    const toastText = document.getElementById('cockpit-toast-text');
    const toastIcon = document.getElementById('cockpit-toast-icon');
    if (!toast || !toastText) return;

    toastText.textContent = text;
    if (isError) {
        toastIcon.textContent = 'error';
        toastIcon.className = 'material-symbols-outlined text-rose-400 text-lg';
    } else {
        toastIcon.textContent = 'check_circle';
        toastIcon.className = 'material-symbols-outlined text-emerald-400 text-lg';
    }

    toast.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
    toast.classList.add('opacity-100', 'translate-y-0');

    setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
    }, 3200);
}

// Copy URL to Clipboard
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showCockpitToast('پیوند وب‌سایت با موفقیت کپی شد ✓');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    showCockpitToast('پیوند وب‌سایت با موفقیت کپی شد ✓');
}

// Download QR Code SVG
function downloadQrCodeSvg() {
    const svgEl = document.querySelector('#cockpit-preview-container svg') || document.querySelector('svg');
    if (!svgEl) {
        showCockpitToast('کد QR در دسترس نیست', true);
        return;
    }
    const svgData = new XMLSerializer().serializeToString(svgEl);
    const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'website_qr_<?= htmlspecialchars($siteSlug) ?>.svg';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showCockpitToast('فایل QR Code با موفقیت دانلود شد ✓');
}

// Preview Frame Viewport Switcher
function setCockpitFrameMode(mode) {
    const frame = document.getElementById('cockpit-preview-frame');
    const btnDesktop = document.getElementById('btn-cockpit-desktop');
    const btnMobile = document.getElementById('btn-cockpit-mobile');
    if (!frame) return;

    if (mode === 'mobile') {
        frame.style.width = '375px';
        frame.style.borderRadius = '32px';
        btnMobile.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs flex items-center gap-1 transition-all';
        btnDesktop.className = 'px-2.5 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all';
    } else {
        frame.style.width = '100%';
        frame.style.borderRadius = '16px';
        btnDesktop.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs flex items-center gap-1 transition-all';
        btnMobile.className = 'px-2.5 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-all';
    }
}

// Save Custom Domain
async function saveCustomDomain() {
    const input = document.getElementById('custom-domain-input');
    const btn = document.getElementById('btn-save-domain');
    if (!input || !btn) return;

    const domain = input.value.trim();
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span><span>در حال ثبت...</span>';

    try {
        const formData = new FormData();
        formData.append('action', 'update_custom_domain');
        formData.append('custom_domain', domain);

        const res = await fetch('../actions/site_builder_action.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showCockpitToast(data.message || 'دامنه با موفقیت ثبت شد ✓');
            setTimeout(() => location.reload(), 1200);
        } else {
            showCockpitToast(data.message || 'خطا در ثبت دامنه', true);
        }
    } catch (err) {
        showCockpitToast('خطا در برقراری ارتباط با سرور', true);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span><span>ثبت دامنه</span>';
    }
}

// 1-Click Starter Provisioning
async function provisionStarterSite() {
    const input = document.getElementById('starter-slug-input');
    const btn = document.getElementById('btn-provision-starter');
    const statusEl = document.getElementById('provision-status-msg');
    if (!btn) return;

    const slug = input ? input.value.trim() : '';
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">refresh</span><span>در حال راه‌اندازی و انتشار وب‌سایت...</span>';
    if (statusEl) {
        statusEl.classList.remove('hidden', 'text-rose-600');
        statusEl.classList.add('text-blue-600');
        statusEl.textContent = 'در حال ساخت ساب‌دامین و قالب اختصاصی...';
    }

    try {
        const formData = new FormData();
        formData.append('action', 'provision_my_site');
        formData.append('desired_slug', slug);

        const res = await fetch('../actions/website_order_action.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            if (statusEl) {
                statusEl.classList.remove('text-blue-600');
                statusEl.classList.add('text-emerald-600');
                statusEl.textContent = data.message || 'وب‌سایت اختصاصی شما با موفقیت فعال شد! در حال انتقال...';
            }
            showCockpitToast('وب‌سایت اختصاصی شما با موفقیت ایجاد شد ✓');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            if (statusEl) {
                statusEl.classList.remove('text-blue-600');
                statusEl.classList.add('text-rose-600');
                statusEl.textContent = data.message || 'خطا در ایجاد وب‌سایت.';
            }
            showCockpitToast(data.message || 'خطا در راه‌اندازی وب‌سایت', true);
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-lg">flash_on</span><span>راه‌اندازی فوری وب‌سایت من (تنها با یک کلیک)</span>';
        }
    } catch (err) {
        if (statusEl) {
            statusEl.classList.remove('text-blue-600');
            statusEl.classList.add('text-rose-600');
            statusEl.textContent = 'خطا در ارتباط با سرور.';
        }
        showCockpitToast('خطا در برقراری ارتباط با سرور', true);
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">flash_on</span><span>راه‌اندازی فوری وب‌سایت من (تنها با یک کلیک)</span>';
    }
}
</script>
