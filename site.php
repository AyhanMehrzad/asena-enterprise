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
require_once __DIR__ . '/includes/QrCode.php';

// Resolve Slug from Subdomain, Custom Domain, or GET parameter
$host = $_SERVER['HTTP_HOST'] ?? '';
$slug = trim($_GET['slug'] ?? '');
$tenantService = App::tenantSite();
$site = null;

if (empty($slug) && !empty($host)) {
    // Check custom domain mapping
    $siteByDomain = $tenantService->getSiteByDomain($host);
    if ($siteByDomain) {
        $site = $siteByDomain;
        $slug = $site['slug'];
    } else {
        $hostParts = explode('.', $host);
        if (count($hostParts) >= 3 && $hostParts[0] !== 'www') {
            $slug = $hostParts[0];
        }
    }
}

if (empty($slug)) {
    header("Location: index.php");
    exit;
}

if (!$site) {
    $site = $tenantService->getSiteBySlug($slug);
}

if (!$site) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>وب‌سایت یافت نشد</title>
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
            <p class="text-sm text-slate-500 mb-6">این وب‌سایت در حال حاضر در دسترس نیست یا در حال آماده‌سازی است.</p>
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-6 py-3 bg-[#001a48] text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors">
                بازگشت به صفحه قبل
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
$archetype = $site['layout']['theme']['archetype'] ?? match($site['theme_palette'] ?? '') {
    'purple' => 'pharmacist',
    'orange' => 'seller',
    'navy' => 'organization',
    default => $tenantType
};
if (!in_array($archetype, ['doctor', 'pharmacist', 'seller', 'organization'])) {
    $archetype = $tenantType;
}

$savedBlocks = [];
if (!empty($site['layout']['blocks']) && is_array($site['layout']['blocks'])) {
    $savedBlocks = $site['layout']['blocks'];
} elseif (!empty($site['layout']) && is_array($site['layout'])) {
    if (isset($site['layout']['hero']) || isset($site['layout']['contact']) || isset($site['layout']['header'])) {
        $savedBlocks = $site['layout'];
    }
}

$defaultLayout = $tenantService->buildDefaultLayout($tenantType, [
    'name' => $site['site_title'],
    'phone' => $savedBlocks['header']['phone'] ?? $savedBlocks['contact']['phone'] ?? '',
    'operating_hours' => $savedBlocks['contact']['hours'] ?? '',
    'banner_url' => $site['banner_url']
], $siteTier)['blocks'];

$layout = array_replace_recursive($defaultLayout, $savedBlocks);
if (isset($savedBlocks['services']['items']) && is_array($savedBlocks['services']['items'])) {
    $layout['services']['items'] = $savedBlocks['services']['items'];
}
if (isset($savedBlocks['bento_facilities']['items']) && is_array($savedBlocks['bento_facilities']['items'])) {
    $layout['bento_facilities']['items'] = $savedBlocks['bento_facilities']['items'];
}
if (isset($savedBlocks['faq']['items']) && is_array($savedBlocks['faq']['items'])) {
    $layout['faq']['items'] = $savedBlocks['faq']['items'];
}
if (isset($savedBlocks['navigation_hub']['apps']) && is_array($savedBlocks['navigation_hub']['apps'])) {
    $layout['navigation_hub']['apps'] = $savedBlocks['navigation_hub']['apps'];
}
if (isset($savedBlocks['stats_strip']['stats']) && is_array($savedBlocks['stats_strip']['stats'])) {
    $layout['stats_strip']['stats'] = $savedBlocks['stats_strip']['stats'];
}
if (isset($savedBlocks['doctors_roster']['items']) && is_array($savedBlocks['doctors_roster']['items'])) {
    $layout['doctors_roster']['items'] = $savedBlocks['doctors_roster']['items'];
}
if (isset($savedBlocks['social_links']['items']) && is_array($savedBlocks['social_links']['items'])) {
    $layout['social_links']['items'] = $savedBlocks['social_links']['items'];
}
if (isset($savedBlocks['reviews']['items']) && is_array($savedBlocks['reviews']['items'])) {
    $layout['reviews']['items'] = $savedBlocks['reviews']['items'];
}
$headerBlock = $layout['header'] ?? [];
$emergencyBlock = $layout['emergency_bar'] ?? [];
$heroBlock = $layout['hero'] ?? [];
$statsBlock = $layout['stats_strip'] ?? [];
$dutyBlock = $layout['duty_hours'] ?? [];
$beforeAfterBlock = $layout['before_after'] ?? [];
$calculatorBlock = $layout['cost_calculator'] ?? [];
$bentoBlock = $layout['bento_facilities'] ?? [];
$aboutBlock = $layout['about'] ?? [];
$servicesBlock = $layout['services'] ?? [];
$asenaServicesBlock = $layout['asena_services'] ?? [];
$doctorsBlock = $layout['doctors_roster'] ?? [];
$bookingBlock = $layout['booking'] ?? [];
$storefrontBlock = $layout['storefront'] ?? [];
$reviewsBlock = $layout['reviews'] ?? [];
$faqBlock = $layout['faq'] ?? [];
$navHubBlock = $layout['navigation_hub'] ?? [];
$contactBlock = $layout['contact'] ?? [];
$socialLinksBlock = $layout['social_links'] ?? [];
$mobileBarBlock = $layout['sticky_mobile_bar'] ?? [];
$footerBlock = $layout['footer'] ?? [];
$themeConfig = $layout['theme'] ?? ($site['layout']['theme'] ?? []);
$ambientMode = $themeConfig['ambient_mode'] ?? 'atmospheric_glow';
$cardRadius = $themeConfig['card_radius'] ?? 'rounded-3xl';
$trustAnchorStyle = $themeConfig['trust_anchor'] ?? 'floating_pill';

// Early Coordinates, Address and Navigation Links Initialization
$targetLat = $navHubBlock['lat'] ?? '35.7219';
$targetLng = $navHubBlock['lng'] ?? '51.3347';
$rawAddress = !empty(trim($contactBlock['address'] ?? '')) ? $contactBlock['address'] : 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
$addressMapLink = trim($contactBlock['map_link'] ?? ($navHubBlock['neshan_url'] ?? ($navHubBlock['balad_url'] ?? ($navHubBlock['google_maps_url'] ?? ''))));
if (empty($addressMapLink) && !empty($targetLat) && !empty($targetLng)) {
    $addressMapLink = "https://neshan.org/maps/@{$targetLat},{$targetLng},16z";
}
$navBtnText = !empty(trim($contactBlock['nav_btn_text'] ?? '')) ? $contactBlock['nav_btn_text'] : 'مسیریابی با بلد / نشان';

// Normalizer for ASENA ecosystem live services to asena.company
$normalizeAsenaUrl = function(?string $url): string {
    if (empty($url)) return 'https://asena.company';
    $url = trim($url);
    if (str_starts_with($url, '#')) return $url;
    if (str_starts_with($url, '../')) {
        return 'https://asena.company/' . ltrim(substr($url, 3), '/');
    }
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return 'https://asena.company/' . ltrim($url, '/');
    }
    return $url;
};

// Hydrate live items from database (hydrate all available in preview mode for instantaneous zero-refresh toggling)
$tenantProducts = [];
if (!empty($storefrontBlock['enabled']) || $isPreview) {
    $itemLimit = (int)($storefrontBlock['item_limit'] ?? 8);
    $tenantProducts = $tenantService->getTenantProducts($tenantType, $tenantId, $itemLimit);
}
$tenantMgmtUrl = $tenantService->getTenantManagementUrl($tenantType, $tenantId);

$orderSuccess   = isset($_GET['order_success']) && (int)$_GET['order_success'] === 1;
$orderSuccessId = (int)($_GET['order_id'] ?? 0);
$orderRefId     = htmlspecialchars($_GET['ref_id'] ?? '');
$orderFailed    = isset($_GET['order_failed']) && (int)$_GET['order_failed'] === 1;
$orderFailMsg   = htmlspecialchars($_GET['msg'] ?? 'پرداخت سفارش لغو شد یا با خطا مواجه گردید.');

$tenantDoctors = [];
if (!empty($doctorsBlock['enabled']) || $tenantType === 'organization' || $isPreview) {
    $tenantDoctors = $tenantService->getOrganizationDoctors($tenantId);
}



$tenantReviews = [];
if (!empty($reviewsBlock['enabled']) || $isPreview) {
    $tenantReviews = $tenantService->getTenantReviews($tenantType, $tenantId, 3);
}

// Hydrate FAQs & Cost Calculator Config
$tenantFaqs = !empty($faqBlock['items']) ? $faqBlock['items'] : $tenantService->getTenantFaqs($tenantType);
$calcConfig = $tenantService->getCostCalculatorConfig($tenantType);

// Dynamic Duty Shift Status Calculation (Tehran Time)
date_default_timezone_set('Asia/Tehran');
$currentHour = (int)date('G');
$currentMinute = (int)date('i');
$currentTotalMins = $currentHour * 60 + $currentMinute;

$openTimeStr = $dutyBlock['open_time'] ?? '08:30';
$closeTimeStr = $dutyBlock['close_time'] ?? '22:30';
$openParts = explode(':', $openTimeStr);
$closeParts = explode(':', $closeTimeStr);
$openTotalMins = ((int)($openParts[0] ?? 8)) * 60 + ((int)($openParts[1] ?? 30));
$closeTotalMins = ((int)($closeParts[0] ?? 22)) * 60 + ((int)($closeParts[1] ?? 30));

$isEmergency24 = !empty($dutyBlock['emergency_open_24h']);
$isCurrentlyOpen = $isEmergency24 || ($currentTotalMins >= $openTotalMins && $currentTotalMins < $closeTotalMins);

if ($isEmergency24) {
    $dutyCountdownText = 'پذیرش اورژانس ۲۴ ساعته فعال است';
} elseif ($isCurrentlyOpen) {
    $minsRemaining = $closeTotalMins - $currentTotalMins;
    $hrsRemaining = floor($minsRemaining / 60);
    $remMins = $minsRemaining % 60;
    $dutyCountdownText = "تا پایان شیفت امروز: {$hrsRemaining} ساعت و {$remMins} دقیقه باقی‌مانده";
} else {
    $dutyCountdownText = "خارج از شیفت حضوری • شروع پذیرش فردا ساعت {$openTimeStr}";
}

// Digital VCard & Dynamic Scannable QR Codes
$siteProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$siteCanonicalUrl = $siteProtocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok($_SERVER['REQUEST_URI'] ?? '', '?') . '?slug=' . urlencode($slug);

$vcardPhone = $savedBlocks['header']['phone'] ?? $savedBlocks['contact']['phone'] ?? ($site['phone'] ?? '');
$vcardCleanPhone = preg_replace('/[^\d+]/', '', $vcardPhone);
$vcardAddress = $savedBlocks['contact']['address'] ?? ($site['address'] ?? '');
$vcardTagline = $site['site_tagline'] ?? 'مرکز خدمات تخصصی حیوانات خانگی';

// Social Media & Channels Configuration
$rawSocialLinks = [];
if (!empty($layout['social_links']['items']) && is_array($layout['social_links']['items'])) {
    $rawSocialLinks = $layout['social_links']['items'];
} elseif (!empty($savedBlocks['social_links']['items']) && is_array($savedBlocks['social_links']['items'])) {
    $rawSocialLinks = $savedBlocks['social_links']['items'];
} elseif (is_array($layout['social_links'] ?? null) && isset($layout['social_links'][0])) {
    $rawSocialLinks = $layout['social_links'];
} elseif (!empty($savedBlocks['contact']['social_links']) && is_array($savedBlocks['contact']['social_links'])) {
    $rawSocialLinks = $savedBlocks['contact']['social_links'];
}
if (empty($rawSocialLinks)) {
    $cleanPhoneDigits = preg_replace('/[^\d]/', '', $vcardPhone);
    $tenantHandle = $slug ?: ($tenantType . '_clinic');
    $rawSocialLinks = [
        ['id' => 'instagram', 'platform' => 'instagram', 'title' => 'اینستاگرام رسمی', 'handle' => '@' . $tenantHandle, 'url' => 'https://instagram.com/' . $tenantHandle, 'icon' => 'photo_camera', 'color' => '#E1306C', 'enabled' => true],
        ['id' => 'telegram', 'platform' => 'telegram', 'title' => 'کانال تلگرام', 'handle' => '@' . $tenantHandle, 'url' => 'https://t.me/' . $tenantHandle, 'icon' => 'send', 'color' => '#229ED9', 'enabled' => true],
        ['id' => 'whatsapp', 'platform' => 'whatsapp', 'title' => 'پشتیبانی واتساپ', 'handle' => $vcardPhone, 'url' => (!empty($cleanPhoneDigits) ? 'https://wa.me/' . $cleanPhoneDigits : ''), 'icon' => 'chat', 'color' => '#25D366', 'enabled' => !empty($cleanPhoneDigits)],
        ['id' => 'bale', 'platform' => 'bale', 'title' => 'پیام‌رسان بله', 'handle' => '@' . $tenantHandle, 'url' => 'https://ble.ir/' . $tenantHandle, 'icon' => 'mark_chat_read', 'color' => '#00897B', 'enabled' => true],
        ['id' => 'eitaa', 'platform' => 'eitaa', 'title' => 'کانال ایتا', 'handle' => '@' . $tenantHandle, 'url' => 'https://eitaa.com/' . $tenantHandle, 'icon' => 'forum', 'color' => '#E65100', 'enabled' => true]
    ];
}
$socialLinks = $rawSocialLinks;

// Build RFC 2426 vCard 3.0 content with UTF-8 BOM
$vcardFileContent = "\xEF\xBB\xBFBEGIN:VCARD\r\nVERSION:3.0\r\nFN;CHARSET=UTF-8:" . $site['site_title'] . "\r\nORG;CHARSET=UTF-8:" . $site['site_title'] . "\r\n";
if (!empty($vcardTagline)) {
    $vcardFileContent .= "TITLE;CHARSET=UTF-8:" . $vcardTagline . "\r\n";
}
if (!empty($vcardCleanPhone)) {
    $vcardFileContent .= "TEL;TYPE=WORK,VOICE:" . $vcardCleanPhone . "\r\n";
}
if (!empty($vcardAddress)) {
    $vcardFileContent .= "ADR;TYPE=WORK;CHARSET=UTF-8:;;" . $vcardAddress . ";;;;\r\n";
}
$vcardFileContent .= "URL:" . $siteCanonicalUrl . "\r\n";
$vcardFileContent .= "NOTE;CHARSET=UTF-8:عضو رسمی شبکه سلامت آسنا\r\n";

// Embed Social Profiles in standard vCard format for iOS and Android Contacts
foreach ($socialLinks as $slink) {
    if (!empty($slink['enabled']) && !empty($slink['url'])) {
        $pName = strtolower($slink['platform'] ?? 'social');
        $sUrl = trim($slink['url']);
        $vcardFileContent .= "X-SOCIALPROFILE;type=" . $pName . ":" . $sUrl . "\r\n";
    }
}
$vcardFileContent .= "END:VCARD\r\n";

// Handle direct vCard download
if (isset($_GET['download_vcard']) && (int)$_GET['download_vcard'] === 1) {
    $vcardFileName = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $site['site_title']) . '.vcf';
    header('Content-Type: text/vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $vcardFileName . '"; filename*="UTF-8\'\'' . rawurlencode($site['site_title']) . '.vcf"');
    header('Content-Length: ' . strlen($vcardFileContent));
    header('Cache-Control: no-cache, must-revalidate');
    echo $vcardFileContent;
    exit;
}

// Compact string for vCard QR
$vcardQrString = "BEGIN:VCARD\nVERSION:3.0\nFN;CHARSET=UTF-8:" . $site['site_title'] . "\nORG;CHARSET=UTF-8:" . $site['site_title'] . (!empty($vcardCleanPhone) ? "\nTEL;TYPE=WORK,VOICE:" . $vcardCleanPhone : "") . "\nURL:" . $siteCanonicalUrl . "\nNOTE;CHARSET=UTF-8:عضو رسمی شبکه سلامت آسنا\nEND:VCARD";

$qrWebsiteSvg = QrCode::svg($siteCanonicalUrl, 200, '#001a48', '#ffffff', 2);
$qrVcardSvg   = QrCode::svg($vcardQrString, 220, '#001a48', '#ffffff', 2);

// Handle direct QR vector download
if (isset($_GET['download_qr']) && in_array($_GET['download_qr'], ['website', 'vcard'])) {
    $downloadQrType = $_GET['download_qr'];
    $outQrSvg = ($downloadQrType === 'website') ? $qrWebsiteSvg : $qrVcardSvg;
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="qr-' . $downloadQrType . '-' . $slug . '.svg"');
    echo $outQrSvg;
    exit;
}

// Color Theme Palettes with agency-grade tokens
$paletteMap = [
    'emerald' => [
        'name' => 'سبز کلینیک لوکس (Luxury Minimal Clinic)',
        'primary' => '#059669',
        'primary_hover' => '#047857',
        'primary_light' => '#ecfdf5',
        'primary_border' => '#a7f3d0',
        'accent' => '#fd8100',
        'gradient' => 'from-emerald-600 via-teal-700 to-emerald-900',
        'subtle_glow' => 'rgba(5, 150, 105, 0.15)'
    ],
    'navy' => [
        'name' => 'سرمه‌ای کلاسیک (Hospital Corporate Navy)',
        'primary' => '#001a48',
        'primary_hover' => '#002666',
        'primary_light' => '#eff6ff',
        'primary_border' => '#bfdbfe',
        'accent' => '#fd8100',
        'gradient' => 'from-[#001a48] via-[#08296c] to-[#041535]',
        'subtle_glow' => 'rgba(0, 26, 72, 0.15)'
    ],
    'orange' => [
        'name' => 'نارنجی پت‌شاپ پویا (Vibrant Pet Companion)',
        'primary' => '#ea580c',
        'primary_hover' => '#c2410c',
        'primary_light' => '#fff7ed',
        'primary_border' => '#fed7aa',
        'accent' => '#001a48',
        'gradient' => 'from-orange-600 via-amber-600 to-rose-700',
        'subtle_glow' => 'rgba(234, 88, 12, 0.15)'
    ],
    'purple' => [
        'name' => 'بنفش لوکس دارویی (Midnight Velvet Luxury)',
        'primary' => '#7c3aed',
        'primary_hover' => '#6d28d9',
        'primary_light' => '#f5f3ff',
        'primary_border' => '#ddd6fe',
        'accent' => '#ea580c',
        'gradient' => 'from-purple-700 via-indigo-800 to-slate-950',
        'subtle_glow' => 'rgba(124, 58, 237, 0.15)'
    ],
    'aurora' => [
        'name' => 'فیروزه‌ای مینیمال و تشخیصی (Pure Aurora Cyan)',
        'primary' => '#0891b2',
        'primary_hover' => '#0e7490',
        'primary_light' => '#ecfeff',
        'primary_border' => '#a5f3fc',
        'accent' => '#001a48',
        'gradient' => 'from-cyan-600 via-teal-700 to-slate-900',
        'subtle_glow' => 'rgba(8, 145, 178, 0.15)'
    ]
];
$theme = $paletteMap[$site['theme_palette']] ?? $paletteMap['emerald'];

// Optional bespoke color customization while preserving harmony and contrast safety
$customPrimary = $layout['theme']['primary_color'] ?? ($site['primary_color'] ?? null);
$customSecondary = $layout['theme']['secondary_color'] ?? ($site['secondary_color'] ?? null);

if (!empty($customPrimary) && preg_match('/^#[a-f0-9]{6}$/i', $customPrimary)) {
    // Only apply if explicitly declared in layout or differs from legacy column default
    $isExplicitCustom = !empty($layout['theme']['primary_color']) || 
                        ($site['theme_palette'] === 'navy' && $customPrimary === '#001a48') ||
                        ($customPrimary !== '#001a48');
    if ($isExplicitCustom) {
        $hex = ltrim($customPrimary, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Luminance check: keep button text contrast WCAG AA compliant (> 4.5:1 against white text)
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        if ($lum > 0.75) {
            $r = (int)($r * 0.65);
            $g = (int)($g * 0.65);
            $b = (int)($b * 0.65);
            $customPrimary = sprintf("#%02x%02x%02x", $r, $g, $b);
        }
        
        $theme['primary'] = $customPrimary;
        $theme['primary_hover'] = sprintf("#%02x%02x%02x", max(0, (int)($r * 0.85)), max(0, (int)($g * 0.85)), max(0, (int)($b * 0.85)));
        $theme['primary_light'] = sprintf("rgba(%d, %d, %d, 0.08)", $r, $g, $b);
        $theme['primary_border'] = sprintf("rgba(%d, %d, %d, 0.22)", $r, $g, $b);
        $theme['subtle_glow'] = sprintf("rgba(%d, %d, %d, 0.15)", $r, $g, $b);
    }
}

if (!empty($customSecondary) && preg_match('/^#[a-f0-9]{6}$/i', $customSecondary)) {
    $theme['accent'] = $customSecondary;
}

// SEO & Meta - Strictly enforce optimal length (Title: 40-60 chars, Desc: 70-160 chars)
$rawTitle = trim($site['site_title'] ?? '');
if (!empty($site['site_tagline']) && (mb_strlen($rawTitle . ' - ' . $site['site_tagline'], 'UTF-8') <= 60)) {
    $rawTitle .= ' - ' . $site['site_tagline'];
} elseif (mb_strlen($rawTitle . ' | آسنا', 'UTF-8') <= 60) {
    $rawTitle .= ' | آسنا';
}
if (mb_strlen($rawTitle, 'UTF-8') > 60) {
    $rawTitle = mb_substr($rawTitle, 0, 57, 'UTF-8') . '...';
}
$metaTitle = htmlspecialchars($rawTitle);

$rawDesc = trim($site['meta_description'] ?? '');
if (empty($rawDesc) || mb_strlen($rawDesc, 'UTF-8') < 70) {
    $rawDesc = match($tenantType) {
        'doctor' => ($site['site_title'] . '؛ خدمات تخصصی دامپزشکی، ویزیت، جراحی بافت نرم و ارتوپدی، واکسیناسیون و رزرو آنلاین نوبت حیوانات خانگی در آسنا.'),
        'pharmacist' => ($site['site_title'] . '؛ مرجع تأمین داروهای تخصصی دامپزشکی، واکسن‌ها و مکمل‌های غذایی با استاندارد زنجیره سرد و ارسال سریع اکسپرس.'),
        'seller' => ($site['site_title'] . '؛ هایپرمارکت تخصصی خرید غذای خشک، کنسرو، لوازم بهداشتی و ملزومات سگ و گربه با تضمین اصالت و ارسال سریع سراسری.'),
        'organization' => ($site['site_title'] . '؛ بیمارستان و مرکز درمانی شبانه‌روزی دامپزشکی با تجهیزات جراحی، رادیولوژی دیجیتال، آزمایشگاه و بستری ۲۴ ساعته.'),
        default => ($site['site_title'] . '؛ وب‌سایت رسمی، خدمات تخصصی و نوبت‌دهی آنلاین در پلتفرم جامع سلامت و خدمات حیوانات خانگی آسنا.')
    };
}
if (mb_strlen($rawDesc, 'UTF-8') > 160) {
    $rawDesc = mb_substr($rawDesc, 0, 157, 'UTF-8') . '...';
}
$metaDesc = htmlspecialchars($rawDesc);
$siteLogo = !empty($site['logo_url']) ? $site['logo_url'] : 'assets/images/clinic-default-logo.svg';
$asenaLogo = 'assets/images/logo.png';

$ctaHref = !empty($headerBlock['cta_url']) ? $headerBlock['cta_url'] : match($tenantType) {
    'doctor', 'organization' => '#booking',
    'pharmacist', 'seller' => (!empty($storefrontBlock['enabled']) ? '#storefront' : '#contact'),
    default => '#contact'
};
$heroPrimaryHref = !empty($heroBlock['cta_primary_url']) ? $heroBlock['cta_primary_url'] : $ctaHref;
$heroSecondaryHref = !empty($heroBlock['cta_secondary_url']) ? $heroBlock['cta_secondary_url'] : (!empty($contactBlock['phone']) ? 'tel:' . preg_replace('/[^\d+]/', '', $contactBlock['phone']) : '#services');
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <link rel="canonical" href="<?= htmlspecialchars($siteCanonicalUrl) ?>">
    <link rel="icon" href="<?= htmlspecialchars($siteLogo) ?>">

    <!-- Open Graph & Social Cards -->
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($siteCanonicalUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars(!empty($siteBanner) ? $siteBanner : 'https://asena.company/assets/images/logo.png') ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">

    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": <?= json_encode($site['site_title'], JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode($metaDesc, JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode($siteCanonicalUrl, JSON_UNESCAPED_UNICODE) ?>,
        "telephone": <?= json_encode($vcardCleanPhone ?: '021-91000000', JSON_UNESCAPED_UNICODE) ?>,
        "address": {
            "@type": "PostalAddress",
            "streetAddress": <?= json_encode($rawAddress, JSON_UNESCAPED_UNICODE) ?>,
            "addressCountry": "IR"
        }
    }
    </script>
    
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
            --tenant-ambient: <?= $theme['subtle_glow'] ?>;
        }
        body { 
            font-family: 'Vazirmatn', 'Geist', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .atmospheric-bg {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 26, 72, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 0%, var(--tenant-glow) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(253, 129, 0, 0.03) 0px, transparent 50%);
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

        /* Luxury Semantic Gradient & Contrast Utility Tokens */
        .bg-gradient-dark-navy {
            background: linear-gradient(135deg, #001a48 0%, #08296c 50%, #001438 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-indigo {
            background: linear-gradient(135deg, #1e1b4b 0%, #17153b 50%, #0f172a 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-purple {
            background: linear-gradient(135deg, #3b0764 0%, #2e0854 50%, #0f172a 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-amber {
            background: linear-gradient(135deg, #d97706 0%, #ea580c 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-red {
            background: linear-gradient(135deg, #dc2626 0%, #e11d48 50%, #b91c1c 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-duty {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #001a48 100%) !important;
            color: #ffffff !important;
        }
        .bg-gradient-navhub {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
            color: #ffffff !important;
        }
        .text-orange-950 { color: #431407 !important; }
        .text-amber-950 { color: #451a03 !important; }
        .aspect-\[4\/3\] { aspect-ratio: 4 / 3 !important; min-height: 280px; }

        /* Reception Desk Countertop Stand Print Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            body > *:not(#vcard-printable-stand) {
                display: none !important;
            }
            #vcard-printable-stand {
                display: block !important;
                position: relative !important;
                width: 100% !important;
                max-width: 460px !important;
                margin: 30px auto !important;
                padding: 28px !important;
                box-shadow: none !important;
                border: 3px solid #001a48 !important;
                border-radius: 24px !important;
                page-break-inside: avoid !important;
            }
        }

        <?php if ($isPreview): ?>
        [data-block-id] {
            position: relative;
            transition: outline 0.2s ease, box-shadow 0.2s ease;
        }
        [data-block-id]:hover {
            outline: 2px dashed rgba(5, 150, 105, 0.4);
            outline-offset: 4px;
        }
        /* WYSIWYG Direct In-Place Text Editing Styles */
        [data-studio-editable] {
            position: relative;
            cursor: text !important;
            transition: outline 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
            border-radius: 6px;
        }
        [data-studio-editable]:hover {
            outline: 2px dashed #059669 !important;
            outline-offset: 3px;
            background-color: rgba(5, 150, 105, 0.08) !important;
        }
        [data-studio-editable]:focus,
        [data-studio-editable][contenteditable="true"] {
            outline: 2px solid #059669 !important;
            outline-offset: 3px;
            background-color: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 0 4px 20px rgba(5, 150, 105, 0.25) !important;
        }
        /* Floating Block Quick Controls Toolbar */
        .studio-block-floating-bar {
            position: absolute;
            top: 10px;
            left: 12px;
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(10px);
            padding: 4px 6px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            transform: translateY(-4px);
        }
        [data-block-id]:hover .studio-block-floating-bar {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .studio-block-floating-bar button {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            border-radius: 8px;
            transition: all 0.15s ease;
            border: none;
            cursor: pointer;
        }
        .studio-block-floating-bar .btn-block-quick-edit {
            background: #059669;
            padding: 4px 9px;
            font-size: 11px;
            font-weight: 800;
            gap: 3px;
        }
        .studio-block-floating-bar .btn-block-quick-edit:hover {
            background: #10b981;
        }
        .studio-block-floating-bar .btn-block-move-up,
        .studio-block-floating-bar .btn-block-move-down,
        .studio-block-floating-bar .btn-block-toggle-vis {
            width: 26px;
            height: 26px;
            background: rgba(255, 255, 255, 0.12);
        }
        .studio-block-floating-bar .btn-block-move-up:hover,
        .studio-block-floating-bar .btn-block-move-down:hover,
        .studio-block-floating-bar .btn-block-toggle-vis:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?= ($ambientMode === 'atmospheric_glow') ? 'atmospheric-bg' : 'bg-slate-50' ?> text-slate-800 antialiased selection:bg-orange-500 selection:text-white pb-20 md:pb-0">

    <!-- Top Clinic Contact Strip -->
    <div class="bg-slate-900 text-white py-2 px-4 text-xs font-medium border-b border-slate-800">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[11px] sm:text-xs font-bold" id="live-topbar-title" data-studio-editable="site_title"><?= htmlspecialchars($site['site_title']) ?> | پذیرش فعال و نوبت‌دهی آنلاین</span>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <a href="tel:<?= htmlspecialchars($contactBlock['emergency_phone'] ?? '') ?>" id="live-topbar-em-wrap" class="text-rose-300 hover:text-white flex items-center gap-1 font-bold <?= empty($contactBlock['emergency_phone']) ? 'hidden' : '' ?>">
                    <span class="material-symbols-outlined text-xs">e911_emergency</span>
                    <span>اورژانس شبانه‌روزی: <span class="font-mono" dir="ltr" id="live-topbar-em-phone"><?= htmlspecialchars($contactBlock['emergency_phone'] ?? '') ?></span></span>
                </a>
                <a href="tel:<?= htmlspecialchars($contactBlock['phone'] ?? '') ?>" id="live-topbar-phone-wrap" class="text-slate-300 hover:text-white flex items-center gap-1 <?= (empty($contactBlock['phone']) || !empty($contactBlock['emergency_phone'])) ? 'hidden' : '' ?>">
                    <span class="material-symbols-outlined text-xs">call</span>
                    <span>تماس: <span class="font-mono" dir="ltr" id="live-topbar-phone"><?= htmlspecialchars($contactBlock['phone'] ?? '') ?></span></span>
                </a>
            </div>
        </div>
    </div>

    <!-- 24/7 Red Emergency Hotline Bar -->
    <?php if (!empty($emergencyBlock['enabled']) || $isPreview): ?>
    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 bg-gradient-red text-white py-2.5 px-4 shadow-md relative overflow-hidden z-40 border-b border-red-500/50 <?= (empty($emergencyBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="background: linear-gradient(135deg, #dc2626 0%, #e11d48 50%, #b91c1c 100%) !important; color: #ffffff !important; <?= (empty($emergencyBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="emergency_bar">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-right">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 animate-pulse text-white shadow-inner">
                    <span class="material-symbols-outlined text-lg">e911_emergency</span>
                </span>
                <div>
                    <div class="flex items-center justify-center sm:justify-start gap-2">
                        <span class="px-2 py-0.5 rounded-full bg-white/25 text-[10px] font-black tracking-wide" id="live-emergency-badge" data-studio-editable="emergency_badge"><?= htmlspecialchars($emergencyBlock['badge'] ?? 'اورژانس شبانه‌روزی (۲۴/۷)') ?></span>
                        <h3 class="text-xs sm:text-sm font-black tracking-tight" id="live-emergency-headline" data-studio-editable="emergency_headline"><?= htmlspecialchars($emergencyBlock['headline'] ?? 'اورژانس ۲۴ ساعته و مراقبت‌های فوری حیوانات خانگی') ?></h3>
                    </div>
                    <p class="text-[11px] text-rose-100 hidden md:block mt-0.5" id="live-emergency-subheadline" data-studio-editable="emergency_subheadline"><?= htmlspecialchars($emergencyBlock['subheadline'] ?? 'پذیرش فوری تروما، تصادفات و مسمومیت‌ها با امکانات احیای بالینی پیشرفته') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php $emPhone = !empty($emergencyBlock['phone']) ? $emergencyBlock['phone'] : ($contactBlock['emergency_phone'] ?? $contactBlock['phone'] ?? ''); ?>
                <a href="tel:<?= htmlspecialchars($emPhone) ?>" id="live-emergency-phone-link" class="px-4 py-2 rounded-xl bg-white text-red-700 hover:bg-rose-50 text-xs font-black shadow-lg flex items-center gap-1.5 transition-transform active:scale-95 group <?= empty($emPhone) ? 'hidden' : '' ?>">
                    <span class="material-symbols-outlined text-sm group-hover:animate-bounce">call</span>
                    <span>تماس مستقیم با اورژانس:</span>
                    <span dir="ltr" class="font-mono font-bold" id="live-emergency-phone" data-studio-editable="emergency_phone"><?= htmlspecialchars($emPhone) ?></span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-xl border-b border-slate-200/80 shadow-xs transition-all" data-block-id="header">
        <div class="w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- Brand / Clinic Identity (Right side in RTL) -->
            <div class="flex items-center gap-3 shrink-0">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xs flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($siteLogo) ?>" id="live-header-logo" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h1 class="text-sm sm:text-base font-black text-slate-900 tracking-tight leading-normal whitespace-nowrap" id="live-header-title" data-studio-editable="site_title"><?= htmlspecialchars($site['site_title']) ?></h1>
                        <span class="material-symbols-outlined text-emerald-600 text-sm shrink-0" title="تایید صلاحیت رسمی">verified</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium truncate max-w-[160px] sm:max-w-xs whitespace-nowrap <?= empty($site['site_tagline']) ? 'hidden' : '' ?>" id="live-header-tagline" data-studio-editable="site_tagline"><?= htmlspecialchars($site['site_tagline'] ?? '') ?></p>
                </div>
            </div>

            <!-- Desktop Nav Links (Center) -->
            <nav class="hidden xl:flex items-center gap-4 2xl:gap-6 text-xs font-bold text-slate-600 whitespace-nowrap">
                <a href="#about" class="hover:text-tenant-primary transition-colors whitespace-nowrap">معرفی</a>
                <a href="#services" class="hover:text-tenant-primary transition-colors whitespace-nowrap">خدمات تخصصی</a>
                
                <?php if (!empty($calculatorBlock['enabled']) || $isPreview): ?>
                    <a href="#calculator" class="text-amber-600 hover:text-amber-700 transition-colors flex items-center gap-1 font-black whitespace-nowrap <?= (empty($calculatorBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="calculator">
                        <span class="material-symbols-outlined text-sm">calculate</span>
                        <span>محاسبه‌گر هزینه</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($bookingBlock['enabled']) || $isPreview): ?>
                    <a href="#booking" class="hover:text-tenant-primary transition-colors whitespace-nowrap <?= (empty($bookingBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="booking">نوبت‌دهی</a>
                <?php endif; ?>

                <?php if (!empty($storefrontBlock['enabled']) || $isPreview): ?>
                    <a href="#storefront" class="hover:text-tenant-primary transition-colors whitespace-nowrap <?= (empty($storefrontBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="storefront">محصولات و داروها</a>
                <?php endif; ?>

                <a href="#contact" class="hover:text-tenant-primary transition-colors whitespace-nowrap">تماس و آدرس</a>

                <!-- Dropdown for Additional Sections on Desktop -->
                <div class="relative group/more shrink-0">
                    <button type="button" class="hover:text-tenant-primary text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1 py-1.5 px-2 rounded-lg hover:bg-slate-100 cursor-pointer whitespace-nowrap text-xs font-bold">
                        <span>سایر بخش‌ها</span>
                        <span class="material-symbols-outlined text-sm group-hover/more:rotate-180 transition-transform">expand_more</span>
                    </button>
                    <div class="absolute top-full right-0 mt-1 w-52 bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-200/90 py-2 hidden group-hover/more:block z-50 transition-all">
                        <?php if (!empty($asenaServicesBlock['enabled']) || $isPreview): ?>
                            <a href="#asena-services" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-bold transition-colors <?= (empty($asenaServicesBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="asena_services">
                                <span class="material-symbols-outlined text-sm text-indigo-600">hub</span>
                                <span>خدمات یکپارچه آسنا</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($doctorsBlock['enabled']) || $isPreview): ?>
                            <a href="#doctors" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-tenant-primary font-bold transition-colors <?= (empty($doctorsBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="doctors">
                                <span class="material-symbols-outlined text-sm text-emerald-600">stethoscope</span>
                                <span>پزشکان مرکز</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($bentoBlock['enabled']) || $isPreview): ?>
                            <a href="#facilities" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-tenant-primary font-bold transition-colors <?= (empty($bentoBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="facilities">
                                <span class="material-symbols-outlined text-sm text-blue-600">medical_services</span>
                                <span>امکانات کلینیک</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($faqBlock['enabled']) || $isPreview): ?>
                            <a href="#faq" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-tenant-primary font-bold transition-colors <?= (empty($faqBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="faq">
                                <span class="material-symbols-outlined text-sm text-purple-600">help</span>
                                <span>پرسش‌های متداول</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($reviewsBlock['enabled']) || $isPreview): ?>
                            <a href="#reviews" class="flex items-center gap-2 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-tenant-primary font-bold transition-colors <?= (empty($reviewsBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" data-nav-link="reviews">
                                <span class="material-symbols-outlined text-sm text-amber-500">rate_review</span>
                                <span>نظرات مراجعین</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

            <!-- Actions & Official ASENA Logo (Left side in RTL) -->
            <div class="flex items-center gap-2 sm:gap-2.5 shrink-0 whitespace-nowrap">
                <!-- Official ASENA Ecosystem Logo Lockup -->
                <a href="https://asena.company" target="_blank" onclick="openTrustVerifyModal(); return false;" class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-200/90 hover:border-indigo-300 transition-all group shadow-2xs whitespace-nowrap shrink-0" title="عضو تاییدشده شبکه سلامت آسنا - استعلام اصالت">
                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white p-0.5 border border-slate-200 shadow-2xs shrink-0 flex items-center justify-center">
                        <img src="<?= htmlspecialchars($asenaLogo) ?>" alt="لوگوی رسمی آسنا" class="w-full h-full object-contain">
                    </div>
                    <div class="hidden sm:flex flex-col text-right leading-none whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            <span class="text-[11px] font-black text-slate-800 group-hover:text-indigo-600 transition-colors whitespace-nowrap">اکوسیستم آسنا</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                        </div>
                        <span class="text-[9px] text-slate-400 font-medium mt-0.5 whitespace-nowrap">شبکه رسمی سلامت</span>
                    </div>
                </a>

                <button type="button" onclick="openNavHubModal()" class="hidden 2xl:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all shadow-xs whitespace-nowrap shrink-0" title="مسیریابی با اپلیکیشن‌های بلد، نشان، ویز و گوگل مپ">
                    <span class="material-symbols-outlined text-sm text-tenant-primary">near_me</span>
                    <span class="whitespace-nowrap">مسیریابی</span>
                </button>

                <button type="button" onclick="openVCardModal()" class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-all shadow-2xs whitespace-nowrap shrink-0" title="کارت ویزیت دیجیتال و کیوآرکد">
                    <span class="material-symbols-outlined text-sm">qr_code_2</span>
                    <span class="whitespace-nowrap">کارت ویزیت</span>
                </button>

                <?php if (!empty($storefrontBlock['enabled']) || $isPreview): ?>
                <button type="button" onclick="tenantCart.openDrawer()" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 text-xs font-bold transition-all shadow-2xs relative whitespace-nowrap shrink-0 cursor-pointer" title="مشاهده سبد خرید اختصاصی">
                    <span class="material-symbols-outlined text-sm text-amber-600">shopping_cart</span>
                    <span class="hidden sm:inline whitespace-nowrap">سبد خرید</span>
                    <span id="tenant-nav-cart-badge" class="hidden px-1.5 py-0.2 rounded-full text-[10px] font-black bg-[#fd8100] text-white">0</span>
                </button>
                <?php endif; ?>

                <?php $headerPhone = !empty($headerBlock['phone']) ? $headerBlock['phone'] : ($contactBlock['phone'] ?? ''); ?>
                <a href="tel:<?= htmlspecialchars($headerPhone) ?>" id="live-header-phone-link" class="hidden 2xl:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors whitespace-nowrap shrink-0 <?= empty($headerPhone) ? 'hidden' : '' ?>">
                    <span class="material-symbols-outlined text-sm text-tenant-primary">call</span>
                    <span dir="ltr" id="live-header-phone" data-studio-editable="contact_phone" class="whitespace-nowrap"><?= htmlspecialchars($headerPhone) ?></span>
                </a>

                <a href="<?= $ctaHref ?>" id="live-header-cta-btn" class="hidden md:inline-flex px-4 py-2 rounded-xl bg-tenant-primary bg-tenant-primary-hover text-white text-xs font-bold shadow-md shadow-emerald-900/10 transition-transform active:scale-95 items-center gap-1.5 whitespace-nowrap shrink-0">
                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                    <span id="live-header-cta-text" data-studio-editable="header_cta_text" class="whitespace-nowrap"><?= htmlspecialchars($headerBlock['cta_text'] ?? 'رزرو آنلاین نوبت') ?></span>
                </a>

                <!-- Mobile Menu Button -->
                <button type="button" onclick="toggleMobileDrawer()" class="xl:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 shrink-0 cursor-pointer" title="منوی بخش‌های وب‌سایت">
                    <span class="material-symbols-outlined text-xl">menu</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex-col justify-end xl:hidden transition-opacity">
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
                    <span>خدمات تخصصی</span>
                </a>
                <?php if (!empty($asenaServicesBlock['enabled'])): ?>
                <a href="#asena-services" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-indigo-50 text-indigo-700">
                    <span class="material-symbols-outlined text-indigo-600">hub</span>
                    <span>خدمات آنلاین شبکه سلامت آسنا</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($calculatorBlock['enabled'])): ?>
                <a href="#calculator" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-amber-50 text-amber-700">
                    <span class="material-symbols-outlined text-amber-600">calculate</span>
                    <span>محاسبه‌گر هوشمند تعرفه خدمات</span>
                </a>
                <?php endif; ?>
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
                <?php if (!empty($faqBlock['enabled'])): ?>
                <a href="#faq" onclick="toggleMobileDrawer()" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50">
                    <span class="material-symbols-outlined text-tenant-primary">quiz</span>
                    <span>پرسش‌های متداول</span>
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
                <button type="button" onclick="toggleMobileDrawer(); openNavHubModal();" class="w-full flex items-center gap-3 p-3 rounded-2xl bg-indigo-50 text-indigo-700 font-black">
                    <span class="material-symbols-outlined text-indigo-600">near_me</span>
                    <span>مسیریابی در بلد / نشان / ویز</span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Hero Authority Zone (Above-the-Fold) -->
    <?php if (!empty($heroBlock['enabled'])): ?>
    <section class="relative py-12 md:py-20 overflow-hidden border-b border-slate-200/60 bg-gradient-to-b from-white via-slate-50 to-white mesh-ambient" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%) !important;" data-block-id="hero">
        <div class="max-w-6xl mx-auto px-4 relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-center lg:text-right">
                
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-tenant-light border border-tenant-light text-tenant-primary text-xs font-black shadow-sm" id="live-hero-badge-wrap" style="<?= empty($heroBlock['badge']) ? 'display: none;' : '' ?>">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span id="live-hero-badge" data-studio-editable="hero_badge"><?= htmlspecialchars($heroBlock['badge'] ?? '') ?></span>
                    </div>

                    <!-- Symbiotic ASENA Trust Anchor Pill (Clickable Trust Verification) -->
                    <div onclick="openTrustVerifyModal()" class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/95 border border-slate-200/90 shadow-2xs text-[11px] font-bold text-slate-800 hover:border-emerald-500 hover:bg-emerald-50/40 transition-all" id="live-trust-anchor" style="<?= ($trustAnchorStyle === 'none') ? 'display: none !important;' : '' ?>" title="مشاهده استعلام اصالت و گواهی رسمی آسنا">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>عضو رسمی شبکه سلامت آسنا</span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-500 text-[10px] font-normal">استعلام صلاحیت و ضمانت امانی</span>
                        <span class="material-symbols-outlined text-xs text-emerald-600">verified</span>
                    </div>

                    <!-- Live On-Duty Pulsing Indicator -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>پذیرش فعال و نوبت‌دهی آنلاین</span>
                    </div>
                </div>

                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 leading-[1.2] tracking-tight" id="live-hero-title" data-studio-editable="hero_title">
                    <?= htmlspecialchars($heroBlock['title'] ?? $site['site_title']) ?>
                </h2>

                <p class="text-sm sm:text-base md:text-lg text-slate-600 leading-relaxed font-normal max-w-2xl mx-auto lg:mx-0" id="live-hero-subtitle" data-studio-editable="hero_subtitle">
                    <?= htmlspecialchars($heroBlock['subtitle'] ?? '') ?>
                </p>

                <!-- Archetype-Specific Interactive Strip -->
                <?php if ($archetype === 'seller'): ?>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 pt-1 pb-1">
                    <span class="text-xs font-bold text-slate-500 ml-1">دسته‌بندی حیوانات:</span>
                    <a href="#storefront" class="px-3 py-1.5 rounded-xl bg-orange-600 text-white text-xs font-bold shadow-sm hover:bg-orange-700 transition flex items-center gap-1">🐶 سگ</a>
                    <a href="#storefront" class="px-3 py-1.5 rounded-xl bg-amber-500 text-white text-xs font-bold shadow-sm hover:bg-amber-600 transition flex items-center gap-1">🐱 گربه</a>
                    <a href="#storefront" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm hover:bg-emerald-700 transition flex items-center gap-1">🦜 پرندگان</a>
                    <a href="#storefront" class="px-3 py-1.5 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-sm hover:bg-purple-700 transition flex items-center gap-1">🐹 جوندگان</a>
                </div>
                <?php elseif ($archetype === 'pharmacist'): ?>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 pt-1 pb-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-cyan-50 border border-cyan-200 text-cyan-800 text-xs font-bold shadow-2xs">
                        <span class="material-symbols-outlined text-sm text-cyan-600">ac_unit</span>
                        <span>پایش مداوم دمای زنجیره سرد: ۲ الی ۸ درجه سانتی‌گراد</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold shadow-2xs">
                        <span class="material-symbols-outlined text-sm text-purple-600">prescription</span>
                        <span>پذیرش نسخه الکترونیک و ارسال با یخ خشک</span>
                    </span>
                </div>
                <?php elseif ($archetype === 'organization'): ?>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 pt-1 pb-1">
                    <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1">🏥 مرکز جراحی و بستری ۲۴ ساعته</span>
                    <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1">🩺 تیم پزشکان و متخصصین همکار</span>
                    <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1">🚑 تریاژ و اعزام آمبولانس شبانه‌روزی</span>
                </div>
                <?php elseif ($archetype === 'doctor'): ?>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                    <span class="material-symbols-outlined text-sm">stethoscope</span>
                    <span>دامپزشک و جراح متخصص حیوانات خانگی</span>
                    <span class="text-emerald-300">|</span>
                    <span class="font-mono">کد نظام: <?= htmlspecialchars($site['vet_council_number'] ?? '۲۴۹۱۸') ?></span>
                </div>
                <?php endif; ?>

                <!-- Dual High-Intent CTAs -->
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5">
                    <a href="<?= htmlspecialchars($heroPrimaryHref) ?>" id="live-hero-primary-cta" target="<?= str_starts_with($heroPrimaryHref, '#') ? '_self' : '_blank' ?>" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-tenant-primary bg-tenant-primary-hover text-white text-sm font-black shadow-xl shadow-emerald-900/15 hover:shadow-2xl transition-all flex items-center justify-center gap-2 group">
                        <span id="live-hero-cta" data-studio-editable="hero_cta"><?= htmlspecialchars($heroBlock['cta_primary_text'] ?? 'رزرو آنلاین نوبت') ?></span>
                        <span class="material-symbols-outlined text-base group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                    
                    <?php if (!empty($heroBlock['cta_secondary_text'])): ?>
                    <a href="<?= htmlspecialchars($heroSecondaryHref) ?>" id="live-hero-secondary-cta" target="<?= str_starts_with($heroSecondaryHref, '#') || str_starts_with($heroSecondaryHref, 'tel:') ? '_self' : '_blank' ?>" class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 text-sm font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-slate-500">call</span>
                        <span id="live-hero-secondary-cta-text" data-studio-editable="hero_cta_secondary"><?= htmlspecialchars($heroBlock['cta_secondary_text']) ?></span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Trust Strip Validation (100% Editable) -->
                <div class="pt-6 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-slate-500 text-xs font-semibold border-t border-slate-200/60">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">shield</span>
                        <span id="live-hero-trust-1" data-studio-editable="trust_strip_1"><?= htmlspecialchars($heroBlock['trust_strip_1'] ?? 'درگاه امن پرداخت الکترونیک شاپرک') ?></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">sms</span>
                        <span id="live-hero-trust-2" data-studio-editable="trust_strip_2"><?= htmlspecialchars($heroBlock['trust_strip_2'] ?? 'ارسال فوری پیامک تأیید نوبت') ?></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-base">support_agent</span>
                        <span id="live-hero-trust-3" data-studio-editable="trust_strip_3"><?= htmlspecialchars($heroBlock['trust_strip_3'] ?? 'پشتیبانی شبانه‌روزی ۲۴ ساعته') ?></span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 relative flex justify-center">
                <?php if ($archetype === 'pharmacist'): ?>
                <!-- Pharmacy Interactive Prescription (Rx) Dropzone Card -->
                <div class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-3xl p-6 shadow-2xl border-2 border-purple-200 relative group overflow-hidden">
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="text-xs font-black text-slate-900">پذیرش سریع نسخه آنلاین</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-bold">زنجیره سرد ۲-۸°C</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            عکس نسخه دامپزشک یا نام داروها را ثبت فرمایید تا کارشناسان داروخانه در کمتر از ۱۵ دقیقه هزینه و نحوه ارسال را اعلام نمایند.
                        </p>
                        <div class="border-2 border-dashed border-purple-300 hover:border-purple-500 rounded-2xl p-4 text-center bg-purple-50/50 hover:bg-purple-50 transition-colors cursor-pointer" onclick="document.getElementById('rx-hero-file').click()">
                            <input type="file" id="rx-hero-file" accept="image/*,.pdf" class="hidden" onchange="document.getElementById('rx-hero-status').innerText = '✓ ' + this.files[0].name;">
                            <span class="material-symbols-outlined text-3xl text-purple-600">add_a_photo</span>
                            <div class="text-xs font-bold text-purple-900 mt-1" id="rx-hero-status">انتخاب یا تصویربرداری از نسخه</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">پشتیبانی از عکس و PDF</div>
                        </div>
                        <input type="tel" id="rx-hero-phone" placeholder="شماره موبایل جهت ارسال پیامک تأیید" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:border-purple-600 focus:outline-none" dir="ltr">
                        <button type="button" onclick="alert('نسخه شما با موفقیت دریافت شد. کارشناسان داروخانه تا دقایقی دیگر با شما تماس خواهند گرفت.')" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black shadow-lg shadow-purple-600/20 active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">send</span>
                            <span>ارسال نسخه و برآورد آنلاین قیمت</span>
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <!-- Standard & Doctor/Seller/Hospital Hero Card with Badges -->
                <div class="w-full max-w-md aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 relative group" style="aspect-ratio: 4 / 3; min-height: 280px; width: 100%;">
                    <?php $heroImg = !empty($heroBlock['image']) ? $heroBlock['image'] : $site['banner_url']; ?>
                    <img src="<?= htmlspecialchars($heroImg ?: 'assets/images/clinic-banner.jpg') ?>" id="live-hero-image" alt="<?= htmlspecialchars($site['site_title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" onerror="this.onerror=null; this.src='assets/images/presentation-dog.jpg';">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 right-4 left-4 text-white p-3.5 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span id="live-hero-overlay-title"><?= htmlspecialchars($site['site_title']) ?></span>
                            </span>
                            <span class="text-amber-300 font-bold" id="live-hero-overlay-badge"><?= htmlspecialchars($heroBlock['badge'] ?? 'پذیرش رسمی') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Floating Layered Badge 1: Verified Social Proof -->
                <div class="absolute -top-3 -right-2 sm:-right-4 bg-white/95 backdrop-blur-md p-3 rounded-2xl shadow-xl border border-slate-200/90 flex items-center gap-2.5 z-20 transition-transform hover:-translate-y-1">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">star</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 flex items-center gap-1">
                            <span id="live-hero-review-score" data-studio-editable="hero_review_score"><?= htmlspecialchars($heroBlock['review_score'] ?? '۴.۹') ?></span>
                            <span class="text-amber-400 text-[10px]">★★★★★</span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-bold" id="live-hero-review-count" data-studio-editable="hero_review_count"><?= htmlspecialchars($heroBlock['review_count'] ?? 'بیش از ۱۸۰+ نظر تاییدشده') ?></div>
                    </div>
                </div>

                <!-- Floating Layered Badge 2: Accreditation Authority -->
                <div class="absolute -bottom-4 -left-2 sm:-left-4 bg-white/95 backdrop-blur-md p-3 rounded-2xl shadow-xl border border-slate-200/90 flex items-center gap-2.5 z-20 transition-transform hover:translate-y-1">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-lg">verified_user</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900" id="live-hero-cert-title" data-studio-editable="hero_cert_title">
                            <?= htmlspecialchars($heroBlock['cert_title'] ?? match($archetype) {
                                'pharmacist' => 'زنجیره سرد استاندارد (۲-۸°C)',
                                'seller' => 'تضمین ۱۰۰٪ اصالت کالا',
                                default => 'بورد تخصصی و مجهز به ICU'
                            }) ?>
                        </div>
                        <div class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span id="live-hero-cert-desc" data-studio-editable="hero_cert_desc"><?= htmlspecialchars($heroBlock['cert_desc'] ?? 'دارای پروانه و صلاحیت رسمی بالینی') ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social Proof Operational Scale Strip (100% Granularly Editable & Dynamic Repeaters) -->
    <?php if (!empty($statsBlock['enabled']) || $isPreview): ?>
    <?php
    $statsList = !empty($statsBlock['stats']) && is_array($statsBlock['stats']) ? $statsBlock['stats'] : [
        ['value' => $statsBlock['stat_1_val'] ?? '+۱۵,۰۰۰', 'label' => $statsBlock['stat_1_lbl'] ?? 'ویزیت و سفارش موفق', 'icon' => 'verified'],
        ['value' => $statsBlock['stat_2_val'] ?? '۴.۹ ★', 'label' => $statsBlock['stat_2_lbl'] ?? 'رضایت مراجعین', 'icon' => 'star'],
        ['value' => $statsBlock['stat_3_val'] ?? '۱۰۰٪', 'label' => $statsBlock['stat_3_lbl'] ?? 'تضمین بازگشت وجه و کیفیت', 'icon' => 'security'],
        ['value' => $statsBlock['stat_4_val'] ?? '۲۴ / ۷', 'label' => $statsBlock['stat_4_lbl'] ?? 'پذیرش و اورژانس فعال', 'icon' => 'e911_emergency']
    ];
    ?>
    <section class="py-6 bg-white border-b border-slate-200/80 <?= (empty($statsBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($statsBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="stats_strip">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="live-stats-grid">
                <?php foreach ($statsList as $stIdx => $stItem): ?>
                <div class="relative group/repeater-item p-4 rounded-2xl bg-slate-50 border border-slate-100 text-center flex flex-col items-center justify-center hover:bg-emerald-50/20 transition-colors" data-repeater-index="<?= $stIdx ?>">
                    <?php if ($isPreview): ?>
                    <div class="absolute top-1.5 left-1.5 hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900/90 text-white px-1.5 py-0.5 rounded-lg text-[10px] z-10 shadow-md">
                        <button type="button" onclick="notifyStudioRepeaterModal('stats_strip', <?= $stIdx ?>)" title="ویرایش شاخص" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-[13px]">edit</span>
                        </button>
                        <button type="button" onclick="notifyStudioRemoveRepeater('stats_strip', <?= $stIdx ?>)" title="حذف شاخص" class="hover:text-red-400 p-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-[13px]">delete</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="w-8 h-8 rounded-xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-1.5 shadow-2xs">
                        <span class="material-symbols-outlined text-lg"><?= htmlspecialchars($stItem['icon'] ?? 'verified') ?></span>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 tracking-tight font-mono" id="live-stat-<?= ($stIdx + 1) ?>-val" data-studio-editable="stat_<?= ($stIdx + 1) ?>_val"><?= htmlspecialchars($stItem['value'] ?? '') ?></div>
                    <div class="text-[11px] text-slate-500 font-bold" id="live-stat-<?= ($stIdx + 1) ?>-lbl" data-studio-editable="stat_<?= ($stIdx + 1) ?>_lbl"><?= htmlspecialchars($stItem['label'] ?? '') ?></div>
                </div>
                <?php endforeach; ?>
                <?php if ($isPreview): ?>
                <button type="button" onclick="notifyStudioRepeaterModal('stats_strip', -1)" class="p-4 rounded-2xl border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-white/40 hover:bg-emerald-50/30 text-slate-400 hover:text-emerald-700 flex flex-col items-center justify-center gap-1 transition-all cursor-pointer" title="افزودن شاخص جدید">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span class="text-[10px] font-black">افزودن شاخص</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Live Shift Duty & Hours Widget -->
    <?php if (!empty($dutyBlock['enabled']) || $isPreview): ?>
    <section class="py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-[#001a48] bg-gradient-duty text-white border-b border-slate-700/60 shadow-inner <?= (empty($dutyBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #001a48 100%) !important; color: #ffffff !important; <?= (empty($dutyBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="duty_hours">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl <?= $isCurrentlyOpen ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' ?> flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl"><?= $isCurrentlyOpen ? 'schedule' : 'alarm_off' ?></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="<?= $isCurrentlyOpen ? 'animate-ping bg-emerald-400' : 'bg-amber-400' ?> absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 <?= $isCurrentlyOpen ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                        </span>
                        <span class="text-xs sm:text-sm font-black <?= $isCurrentlyOpen ? 'text-emerald-300' : 'text-amber-300' ?>">
                            <?= $isCurrentlyOpen ? 'هم‌اکنون فعال و پذیرش حضوری باز است' : 'هم‌اکنون خارج از شیفت کاری حضوری' ?>
                        </span>
                        <span class="text-[11px] text-slate-300 font-mono hidden md:inline">| <?= htmlspecialchars($dutyCountdownText) ?></span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        <span id="live-duty-hours-text" data-studio-editable="duty_hours">ساعات کاری اعلامی: <?= htmlspecialchars($dutyBlock['hours_text'] ?? $contactBlock['hours'] ?? '۸:۳۰ الی ۲۲:۳۰') ?></span>
                        <?php if (!$isCurrentlyOpen): ?>
                            <span class="text-amber-200 mr-2 font-bold">(ثبت نوبت اینترنتی و درخواست مشاوره ۲۴ ساعته فعال است)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="<?= !empty($addressMapLink) ? htmlspecialchars($addressMapLink) : 'javascript:openNavHubModal()' ?>" target="<?= !empty($addressMapLink) ? '_blank' : '_self' ?>" rel="noopener" id="live-duty-nav-btn" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer" title="مسیریابی با اپلیکیشن‌های بلد، نشان، ویز و گوگل مپ" onclick="if (!this.getAttribute('href') || this.getAttribute('href').startsWith('javascript:')) openNavHubModal();">
                    <span class="material-symbols-outlined text-sm text-amber-300">near_me</span>
                    <span id="live-duty-nav-text" data-studio-editable="contact_nav_btn_text"><?= htmlspecialchars($navBtnText) ?></span>
                </a>
                <a href="<?= $ctaHref ?>" class="px-4 py-2 rounded-xl bg-tenant-primary bg-tenant-primary-hover text-white text-xs font-black shadow-md flex items-center gap-1.5 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-sm">event_available</span>
                    <span>رزرو شیفت آزاد</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Interactive Before/After Comparison Slider Module -->
    <?php if (!empty($beforeAfterBlock['enabled']) || $isPreview): ?>
    <section id="before-after" class="py-16 bg-white border-b border-slate-200/60 relative overflow-hidden <?= (empty($beforeAfterBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($beforeAfterBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="before_after">
        <div class="max-w-5xl mx-auto px-4 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black mb-3">
                    <span class="material-symbols-outlined text-sm">compare</span>
                    <span id="live-ba-service-badge" data-studio-editable="before_after_service_label"><?= htmlspecialchars($beforeAfterBlock['service_label'] ?? 'نتایج ملموس خدمات و جراحی‌ها') ?></span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900" id="live-ba-heading" data-studio-editable="before_after_heading"><?= htmlspecialchars($beforeAfterBlock['heading'] ?? 'مقایسه نتایج قبل و بعد از مراقبت تخصصی') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed" id="live-ba-subtitle" data-studio-editable="before_after_subtitle">
                    <?= htmlspecialchars($beforeAfterBlock['subtitle'] ?? 'با کشیدن نشانگر لمسی زیر، کیفیت و تفاوت ملموس درمان را به صورت زنده مقایسه نمایید.') ?>
                </p>
            </div>

            <!-- Draggable Split Comparison Container -->
            <div class="max-w-3xl mx-auto">
                <div class="relative w-full rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-900 select-none group" id="ba-comparison-wrapper" style="min-height: 420px; aspect-ratio: 16 / 9; touch-action: pan-y;">
                    <!-- AFTER Image (Base Layer) -->
                    <img src="<?= htmlspecialchars(!empty($beforeAfterBlock['image_after']) ? (str_starts_with($beforeAfterBlock['image_after'], 'http') ? $beforeAfterBlock['image_after'] : $beforeAfterBlock['image_after']) : 'assets/images/clinic-banner.jpg') ?>" 
                         id="live-ba-img-after" 
                         alt="پس از درمان" 
                         class="absolute inset-0 w-full h-full object-cover" 
                         onerror="this.src='assets/images/clinic-banner.jpg'">
                    <div class="absolute top-4 left-4 z-10 px-3 py-1.5 rounded-xl bg-slate-900/80 backdrop-blur-md text-white text-xs font-black border border-white/20 shadow-md">
                        <span id="live-ba-label-after" data-studio-editable="before_after_label_after"><?= htmlspecialchars($beforeAfterBlock['label_after'] ?? 'پس از درمان') ?></span>
                    </div>

                    <!-- BEFORE Image (Clipped Overlay Layer) -->
                    <img src="<?= htmlspecialchars(!empty($beforeAfterBlock['image_before']) ? (str_starts_with($beforeAfterBlock['image_before'], 'http') ? $beforeAfterBlock['image_before'] : $beforeAfterBlock['image_before']) : 'assets/images/presentation-dog.jpg') ?>" 
                         id="live-ba-img-before" 
                         alt="قبل از درمان" 
                         class="absolute inset-0 w-full h-full object-cover z-10" 
                         style="clip-path: inset(0 0 0 50%); -webkit-clip-path: inset(0 0 0 50%);"
                         onerror="this.src='assets/images/presentation-dog.jpg'">
                    <div class="absolute top-4 right-4 z-20 px-3 py-1.5 rounded-xl bg-black/75 backdrop-blur-md text-amber-300 text-xs font-black border border-amber-400/30 shadow-md">
                        <span id="live-ba-label-before" data-studio-editable="before_after_label_before"><?= htmlspecialchars($beforeAfterBlock['label_before'] ?? 'قبل از درمان') ?></span>
                    </div>

                    <!-- Split Handle Divider -->
                    <div class="absolute inset-y-0 z-20 flex items-center justify-center pointer-events-none transition-none" id="ba-divider-line" style="right: 50%;">
                        <div class="w-1 h-full bg-white shadow-[0_0_12px_rgba(0,0,0,0.6)]"></div>
                        <div class="absolute w-11 h-11 rounded-full bg-white shadow-2xl border-2 border-slate-300 flex items-center justify-center text-slate-800 text-xs font-bold gap-0.5 pointer-events-auto cursor-ew-resize active:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-base">code</span>
                        </div>
                    </div>

                    <!-- Hidden Full-overlay Range Slider for ultra-smooth accessibility & touch -->
                    <input type="range" min="0" max="100" value="50" class="absolute inset-0 w-full h-full opacity-0 cursor-ew-resize z-30 m-0 p-0" id="ba-range-slider" oninput="updateBeforeAfterSlider(this.value)">
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 font-bold mt-3 px-2">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">arrow_forward</span> نشانگر را به چپ و راست بکشید</span>
                    <span class="text-slate-500">تفاوت کیفیت با تکنولوژی روز</span>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bento Grid Facilities Architecture -->
    <?php if ((!empty($bentoBlock['enabled']) && !empty($bentoBlock['items'])) || $isPreview): ?>
    <section id="facilities" class="py-16 bg-slate-50 border-b border-slate-200/60 <?= (empty($bentoBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($bentoBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="bento_facilities">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">استانداردهای بالینی و درمانی</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-bento-heading" data-studio-editable="bento_heading"><?= htmlspecialchars($bentoBlock['heading']) ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($bentoBlock['subtitle'] ?? '') ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="live-bento-grid">
                <?php foreach (($bentoBlock['items'] ?? []) as $bIdx => $item): ?>
                <div class="relative group/repeater-item bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 flex items-start gap-4 group" data-repeater-index="<?= $bIdx ?>">
                    <?php if ($isPreview): ?>
                    <div class="absolute top-3 left-3 hidden group-hover/repeater-item:flex items-center gap-1.5 bg-slate-900/90 text-white px-2 py-1 rounded-xl shadow-lg border border-slate-700 z-10 text-xs">
                        <button type="button" onclick="notifyStudioRepeaterModal('bento_facilities', <?= $bIdx ?>)" title="ویرایش تجهیزات" class="hover:text-emerald-400 flex items-center gap-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            <span class="text-[10px]">ویرایش</span>
                        </button>
                        <span class="text-slate-600">|</span>
                        <button type="button" onclick="notifyStudioRemoveRepeater('bento_facilities', <?= $bIdx ?>)" title="حذف تجهیزات" class="hover:text-red-400 flex items-center gap-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            <span class="text-[10px]">حذف</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="w-14 h-14 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($item['icon'] ?? 'local_hospital') ?></span>
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200/60"><?= htmlspecialchars($item['tag'] ?? 'تخصصی') ?></span>
                        <h4 class="text-base font-black text-slate-900"><?= htmlspecialchars($item['title'] ?? '') ?></h4>
                        <p class="text-xs text-slate-600 leading-relaxed"><?= htmlspecialchars($item['desc'] ?? '') ?></p>
                        <?php if (!empty($item['url'])): ?>
                        <div class="pt-1.5">
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= str_starts_with($item['url'], '#') ? '_self' : '_blank' ?>" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-bold text-tenant-primary hover:underline">
                                <span><?= htmlspecialchars(!empty($item['btn_text']) ? $item['btn_text'] : 'اطلاعات بیشتر') ?></span>
                                <span class="material-symbols-outlined text-xs">arrow_left</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($isPreview): ?>
                <button type="button" onclick="notifyStudioRepeaterModal('bento_facilities', -1)" class="min-h-[140px] p-6 rounded-3xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group/add" title="افزودن بخش یا امکانات درمانی">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover/add:bg-emerald-100 group-hover/add:text-emerald-600 flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </div>
                    <span class="text-xs font-black">افزودن بخش یا تجهیزات جدید</span>
                    <span class="text-[10px] text-slate-400">درج امکانات بالینی و تشخیصی</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>



    <!-- About Section -->
    <?php if (!empty($aboutBlock['enabled']) || $isPreview): ?>
    <section id="about" class="py-16 bg-white border-b border-slate-200/60 <?= (empty($aboutBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($aboutBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="about">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-black text-tenant-primary">
                        <span class="w-2.5 h-2.5 rounded-full bg-tenant-primary"></span>
                        <span>معرفی و سوابق رسمی</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900" id="live-about-heading" data-studio-editable="about_heading"><?= htmlspecialchars($aboutBlock['heading'] ?? 'درباره ما') ?></h3>
                    <p class="text-slate-600 leading-relaxed text-sm sm:text-base font-normal" id="live-about-text" data-studio-editable="about_text">
                        <?= nl2br(htmlspecialchars($aboutBlock['text'] ?? '')) ?>
                    </p>

                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 flex items-center gap-3 <?= empty($aboutBlock['vet_council']) ? 'hidden' : '' ?>" id="live-about-vet-council-wrap">
                        <span class="material-symbols-outlined text-amber-600 text-2xl">badge</span>
                        <div>
                            <div class="text-xs font-bold text-slate-800">شماره مجوز و پروانه نظام دامپزشکی</div>
                            <div class="text-sm font-black text-amber-900 font-mono tracking-wider" id="live-about-vet-council" data-studio-editable="about_vet_council"><?= htmlspecialchars($aboutBlock['vet_council'] ?? '') ?></div>
                        </div>
                    </div>

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
                    <h4 class="font-bold text-slate-900 text-base">تضمین کیفیت و استانداردهای درمانی</h4>
                    <p class="text-xs text-slate-500 leading-normal">
                        تمامی خدمات تشخیصی، درمانی و جراحی با رعایت بالاترین استانداردهای بهداشتی و تجهیزات پیشرفته بالینی ارائه می‌گردد.
                    </p>
                    <div class="pt-2">
                        <a href="<?= $ctaHref ?>" class="w-full py-2.5 px-4 rounded-xl bg-tenant-primary hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm">
                            <span>رزرو مستقیم نوبت و مشاوره</span>
                            <span class="material-symbols-outlined text-xs">arrow_left</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Services Block -->
    <?php if ((!empty($servicesBlock['enabled']) && !empty($servicesBlock['items'])) || $isPreview): ?>
    <section id="services" class="py-16 bg-slate-50 border-b border-slate-200/60 <?= (empty($servicesBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($servicesBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="services">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">تخصص‌ها و ظرفیت‌ها</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-services-heading" data-studio-editable="services_heading"><?= htmlspecialchars($servicesBlock['heading'] ?? 'خدمات تخصصی') ?></h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="live-services-grid">
                <?php foreach (($servicesBlock['items'] ?? []) as $sIdx => $srv): ?>
                <div class="relative group/repeater-item bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300" data-repeater-index="<?= $sIdx ?>">
                    <?php if ($isPreview): ?>
                    <div class="absolute top-3 left-3 hidden group-hover/repeater-item:flex items-center gap-1.5 bg-slate-900/90 text-white px-2 py-1 rounded-xl shadow-lg border border-slate-700 z-10 text-xs">
                        <button type="button" onclick="notifyStudioRepeaterModal('services', <?= $sIdx ?>)" title="ویرایش خدمت" class="hover:text-emerald-400 flex items-center gap-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            <span class="text-[10px]">ویرایش</span>
                        </button>
                        <span class="text-slate-600">|</span>
                        <button type="button" onclick="notifyStudioRemoveRepeater('services', <?= $sIdx ?>)" title="حذف خدمت" class="hover:text-red-400 flex items-center gap-0.5 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            <span class="text-[10px]">حذف</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="w-12 h-12 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-2xl"><?= htmlspecialchars($srv['icon'] ?? 'star') ?></span>
                    </div>
                    <h4 class="font-black text-slate-900 text-base mb-2"><?= htmlspecialchars($srv['title'] ?? '') ?></h4>
                    <p class="text-xs text-slate-600 leading-relaxed"><?= htmlspecialchars($srv['desc'] ?? '') ?></p>
                    <?php if (!empty($srv['url'])): ?>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a href="<?= htmlspecialchars($srv['url']) ?>" target="<?= str_starts_with($srv['url'], '#') ? '_self' : '_blank' ?>" rel="noopener" class="inline-flex items-center gap-1 text-xs font-bold text-tenant-primary hover:underline">
                            <span><?= htmlspecialchars(!empty($srv['btn_text']) ? $srv['btn_text'] : 'مشاهده و رزرو خدمت') ?></span>
                            <span class="material-symbols-outlined text-xs">arrow_left</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if ($isPreview): ?>
                <button type="button" onclick="notifyStudioRepeaterModal('services', -1)" class="min-h-[160px] p-6 rounded-3xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group/add" title="افزودن خدمت جدید به کلینیک">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover/add:bg-emerald-100 group-hover/add:text-emerald-600 flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </div>
                    <span class="text-xs font-black">افزودن خدمت جدید</span>
                    <span class="text-[10px] text-slate-400">کلیک جهت ایجاد کارت خدمات جدید</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ASENA Ecosystem Services & Direct Routes Block -->
    <?php if (!empty($asenaServicesBlock['enabled']) || $isPreview): ?>
    <section id="asena-services" class="py-16 bg-gradient-to-b from-white via-indigo-50/20 to-white border-b border-slate-200/60 relative overflow-hidden <?= (empty($asenaServicesBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($asenaServicesBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="asena_services">
        <!-- Ambient Decorative Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[350px] bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-6xl mx-auto px-4 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-black mb-3 shadow-2xs">
                    <span class="material-symbols-outlined text-sm">hub</span>
                    <span id="live-asena-badge" data-studio-editable="asena_badge"><?= htmlspecialchars($asenaServicesBlock['badge'] ?? 'خدمات یکپارچه شبکه سلامت آسنا') ?></span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" id="live-asena-heading" data-studio-editable="asena_heading"><?= htmlspecialchars($asenaServicesBlock['heading'] ?? 'خدمات آنلاین و دسترسی مستقیم به اکوسیستم سلامت آسنا') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2.5 leading-relaxed font-normal" id="live-asena-subtitle" data-studio-editable="asena_subtitle">
                    <?= htmlspecialchars($asenaServicesBlock['subtitle'] ?? 'دسترسی سریع و بی‌واسطه به خدمات تخصصی مشاوره پزشکی، داروخانه ابری، سفارش دوره‌ای ملزومات و باشگاه سلامت مراجعین') ?>
                </p>
            </div>

            <!-- Bento Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Card 1: Telehealth / ویزیت آنلاین -->
                <div class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">videocam</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black border border-emerald-100">پزشکی از راه دور</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5" id="live-telehealth-title" data-studio-editable="telehealth_title">
                            <?= htmlspecialchars($asenaServicesBlock['telehealth_title'] ?? 'ویزیت و تله‌هلث آنلاین') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6" id="live-telehealth-desc" data-studio-editable="telehealth_desc">
                            <?= htmlspecialchars($asenaServicesBlock['telehealth_desc'] ?? 'مشاوره تصویری و گفتگوی آنلاین مستقیم با دامپزشکان متخصص و ثبت نسخه الکترونیک') ?>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars($normalizeAsenaUrl($asenaServicesBlock['telehealth_url'] ?? 'https://asena.company/chat.php')) ?>" id="live-telehealth-link" target="_blank" rel="noopener" class="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg">
                        <span id="live-telehealth-btn" data-studio-editable="telehealth_btn"><?= htmlspecialchars($asenaServicesBlock['telehealth_btn'] ?? 'شروع ویزیت آنلاین') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                </div>

                <!-- Card 2: Cold-Chain Pharmacy / داروخانه زنجیره سرد -->
                <div class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">medication</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-[10px] font-black border border-purple-100">زنجیره سرد (۲-۸°C)</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5" id="live-pharmacy-title" data-studio-editable="pharmacy_title">
                            <?= htmlspecialchars($asenaServicesBlock['pharmacy_title'] ?? 'داروخانه تخصصی زنجیره سرد') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6" id="live-pharmacy-desc" data-studio-editable="pharmacy_desc">
                            <?= htmlspecialchars($asenaServicesBlock['pharmacy_desc'] ?? 'تأمین مطمئن انواع داروهای کمیاب، مکمل‌های تقویتی و واکسن‌ها با شرایط استاندارد دمایی ۲ الی ۸ درجه') ?>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars($normalizeAsenaUrl($asenaServicesBlock['pharmacy_url'] ?? 'https://asena.company/pharmacy.php')) ?>" id="live-pharmacy-link" target="_blank" rel="noopener" class="w-full py-3 px-4 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg">
                        <span id="live-pharmacy-btn" data-studio-editable="pharmacy_btn"><?= htmlspecialchars($asenaServicesBlock['pharmacy_btn'] ?? 'سفارش دارو و مکمل') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                </div>

                <!-- Card 3: Autoship / تحویل دوره‌ای غذای درمانی -->
                <div class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">autorenew</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black border border-blue-100">۱۰٪ تخفیف اشتراک</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5" id="live-autoship-title" data-studio-editable="autoship_title">
                            <?= htmlspecialchars($asenaServicesBlock['autoship_title'] ?? 'تحویل دوره‌ای غذای درمانی (Autoship)') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6" id="live-autoship-desc" data-studio-editable="autoship_desc">
                            <?= htmlspecialchars($asenaServicesBlock['autoship_desc'] ?? 'ارسال خودکار و منظم غذای خشک رژیمی، ضد انگل و مکمل‌ها با تخفیف دائمی ۱۰٪ و امکان لغو در هر زمان') ?>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars($normalizeAsenaUrl($asenaServicesBlock['autoship_url'] ?? 'https://asena.company/subscriptions.php')) ?>" id="live-autoship-link" target="_blank" rel="noopener" class="w-full py-3 px-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md shadow-blue-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg">
                        <span id="live-autoship-btn" data-studio-editable="autoship_btn"><?= htmlspecialchars($asenaServicesBlock['autoship_btn'] ?? 'فعالسازی تحویل دوره‌ای') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                </div>

                <!-- Card 4: Loyalty Club / باشگاه سلامت -->
                <div class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">military_tech</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-[10px] font-black border border-amber-100">پاداش و کش‌بک</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5" id="live-rewards-title" data-studio-editable="rewards_title">
                            <?= htmlspecialchars($asenaServicesBlock['rewards_title'] ?? 'باشگاه وفاداری و پاداش سلامت') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6" id="live-rewards-desc" data-studio-editable="rewards_desc">
                            <?= htmlspecialchars($asenaServicesBlock['rewards_desc'] ?? 'کسب امتیاز وفاداری با هر نوبت ویزیت یا خرید دارو، قابل تبدیل به اعتبار درمانی و تخفیف نقدی') ?>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars($normalizeAsenaUrl($asenaServicesBlock['rewards_url'] ?? 'https://asena.company/rewards.php')) ?>" id="live-rewards-link" target="_blank" rel="noopener" class="w-full py-3 px-4 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-md shadow-amber-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg">
                        <span id="live-rewards-btn" data-studio-editable="rewards_btn"><?= htmlspecialchars($asenaServicesBlock['rewards_btn'] ?? 'مشاهده امتیازها و پاداش') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                </div>

                <!-- Card 5: Animal Rescue Charity / صندوق نیکوکاری -->
                <div class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-2xl">volunteer_activism</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-black border border-rose-100">امانت‌داری ۱۰۰٪ شفاف</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5" id="live-charity-title" data-studio-editable="charity_title">
                            <?= htmlspecialchars($asenaServicesBlock['charity_title'] ?? 'صندوق امداد و درمان حیوانات حمایتی') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6" id="live-charity-desc" data-studio-editable="charity_desc">
                            <?= htmlspecialchars($asenaServicesBlock['charity_desc'] ?? 'مشارکت مستقیم و شفاف در هزینه‌های جراحی و بستری حیوانات بی‌سرپرست و آسیب‌دیده با حساب امانی آسنا') ?>
                        </p>
                    </div>
                    <a href="<?= htmlspecialchars($normalizeAsenaUrl($asenaServicesBlock['charity_url'] ?? 'https://asena.company/charity.php')) ?>" id="live-charity-link" target="_blank" rel="noopener" class="w-full py-3 px-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-md shadow-rose-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg">
                        <span id="live-charity-btn" data-studio-editable="charity_btn"><?= htmlspecialchars($asenaServicesBlock['charity_btn'] ?? 'حمایت از درمان حیوانات') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_left</span>
                    </a>
                </div>

                <!-- Card 6: Digital VCard & Direct Sharing / کارت ویزیت دیجیتال -->
                <div onclick="openVCardModal()" class="bg-white/95 backdrop-blur-md p-6 rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:border-indigo-400/60 transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1 cursor-pointer relative overflow-hidden">
                    <!-- Subtle Corner Aura -->
                    <div class="absolute -top-12 -left-12 w-28 h-28 bg-indigo-500/10 rounded-full blur-xl group-hover:scale-125 transition-transform"></div>
                    <div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                                <span class="material-symbols-outlined text-2xl">contact_page</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-black border border-indigo-100 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">qr_code_2</span>
                                    <span>QR اختصاصی</span>
                                </span>
                            </div>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2 flex items-center gap-1.5 relative z-10" id="live-vcard-title" data-studio-editable="vcard_title">
                            <?= htmlspecialchars($asenaServicesBlock['vcard_title'] ?? 'کارت ویزیت دیجیتال و QR اختصاصی') ?>
                        </h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium mb-6 relative z-10" id="live-vcard-desc" data-studio-editable="vcard_desc">
                            <?= htmlspecialchars($asenaServicesBlock['vcard_desc'] ?? 'دانلود فوری شماره تماس، نشانی و اطلاعات کلینیک در قالب مخاطب (.vcf) و اشتراک‌گذاری در پیام‌رسان‌ها') ?>
                        </p>
                    </div>
                    <button type="button" onclick="event.stopPropagation(); openVCardModal();" class="w-full py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black shadow-md shadow-indigo-600/20 flex items-center justify-center gap-1.5 transition-all group-hover:shadow-lg cursor-pointer relative z-10">
                        <span id="live-vcard-btn" data-studio-editable="vcard_btn"><?= htmlspecialchars($asenaServicesBlock['vcard_btn'] ?? 'نمایش کارت ویزیت دیجیتال') ?></span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">qr_code_2</span>
                    </button>
                </div>

            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Interactive Cost Estimator / Service Calculator Widget -->
    <?php if ((!empty($calculatorBlock['enabled']) && !empty($calcConfig)) || $isPreview): ?>
    <section id="calculator" class="py-16 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-b border-slate-200/60 <?= (empty($calculatorBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($calculatorBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="cost_calculator">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-black mb-3">
                    <span class="material-symbols-outlined text-sm">calculate</span>
                    <span id="live-calc-badge" data-studio-editable="calc_badge"><?= htmlspecialchars($calculatorBlock['badge'] ?? 'تعرفه شفاف خدمات درمانی و جراحی') ?></span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900" id="live-calc-heading" data-studio-editable="calc_heading"><?= htmlspecialchars($calculatorBlock['heading'] ?? 'برآورد آنلاین و شفاف تعرفه خدمات و جراحی‌های تخصصی') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed" id="live-calc-subtitle" data-studio-editable="calc_subtitle">
                    <?= htmlspecialchars($calculatorBlock['subtitle'] ?? 'گونه حیوان خانگی و خدمات تشخیصی، بالینی یا جراحی مدنظر را انتخاب فرمایید تا تعرفه مصوب رسمی همراه با ۱۰٪ تخفیف رزرو آنلاین برآورد گردد.') ?>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left/Selection Column -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <!-- 1. Pet Type Selector -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                        <label class="block text-xs font-black text-slate-800 mb-3 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-lg bg-tenant-light text-tenant-primary flex items-center justify-center text-xs">۱</span>
                                <span>نوع حیوان خانگی خود را انتخاب کنید:</span>
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium" id="calc-pet-selected-title">سگ</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <?php foreach ($calcConfig['pet_types'] as $idx => $pType): ?>
                            <button type="button" 
                                    onclick="selectCalcPet('<?= $pType['id'] ?>', <?= $pType['multiplier'] ?>, '<?= htmlspecialchars($pType['title']) ?>')" 
                                    id="calc-pet-btn-<?= $pType['id'] ?>"
                                    class="calc-pet-btn p-3 rounded-2xl border-2 text-center transition-all flex flex-col items-center justify-center gap-1.5 <?= $idx === 0 ? 'border-emerald-600 bg-emerald-50 text-emerald-800 font-black shadow-sm' : 'border-slate-200 hover:border-slate-300 text-slate-700 font-bold bg-white' ?>">
                                <span class="material-symbols-outlined text-2xl"><?= htmlspecialchars($pType['icon']) ?></span>
                                <span class="text-xs"><?= htmlspecialchars($pType['title']) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 2. Service Category Selector -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                        <label class="block text-xs font-black text-slate-800 mb-3 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-lg bg-tenant-light text-tenant-primary flex items-center justify-center text-xs">۲</span>
                                <span>خدمت مورد نظر را انتخاب فرمایید:</span>
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium" id="calc-srv-selected-title">ویزیت و چکاپ کامل بالینی</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($calcConfig['services'] as $sIdx => $srv): ?>
                            <div onclick="selectCalcService('<?= $srv['id'] ?>', <?= $srv['base_price'] ?>, '<?= htmlspecialchars($srv['title']) ?>')"
                                 id="calc-srv-card-<?= $srv['id'] ?>"
                                 class="calc-srv-card p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3 <?= $sIdx === 0 ? 'border-emerald-600 bg-emerald-50 text-emerald-800 shadow-sm' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white' ?>">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center shrink-0 text-tenant-primary shadow-2xs">
                                    <span class="material-symbols-outlined text-xl"><?= htmlspecialchars($srv['icon']) ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-black text-slate-900 leading-snug"><?= htmlspecialchars($srv['title']) ?></div>
                                    <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($srv['desc']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <!-- Right/Cost Estimation Card -->
                <div class="lg:col-span-5 bg-gradient-to-tr from-[#001a48] to-[#0a3580] bg-gradient-dark-navy text-white p-6 sm:p-7 rounded-3xl shadow-2xl relative overflow-hidden border border-blue-900/50" style="background: linear-gradient(135deg, #001a48 0%, #0a3580 100%) !important; color: #ffffff !important;">
                    <div class="absolute -bottom-10 -left-10 w-44 h-44 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>
                    <div class="relative z-10 space-y-5">
                        <div class="flex items-center justify-between pb-4 border-b border-white/10">
                            <div>
                                <span class="text-[10px] font-mono text-amber-300 uppercase tracking-wider block">تعرفه هوشمند شفاف</span>
                                <h4 class="text-base font-black text-white mt-0.5">برآورد هزینه و اعمال تخفیف</h4>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-black border border-emerald-500/30 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">savings</span>
                                <span id="live-calc-discount-badge"><?= (int)($calculatorBlock['discount_percent'] ?? 10) ?>٪ تخفیف آنلاین</span>
                            </span>
                        </div>

                        <!-- Summary of Selection -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between text-slate-200">
                                <span>حیوان انتخابی:</span>
                                <span class="font-bold text-white font-mono" id="calc-display-pet">سگ</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-200">
                                <span>خدمت تشخیصی/درمانی:</span>
                                <span class="font-bold text-white" id="calc-display-service">ویزیت و چکاپ کامل بالینی</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-300 text-[11px] pt-2 border-t border-white/10">
                                <span>تعرفه مصوب پایه:</span>
                                <span class="line-through font-mono" id="calc-display-base-price">۲۵۰,۰۰۰ تومان</span>
                            </div>
                            <div class="flex items-center justify-between text-emerald-300 text-[11px]">
                                <span>تخفیف ویژه رزرو آنلاین (<span id="live-calc-discount-pct"><?= (int)($calculatorBlock['discount_percent'] ?? 10) ?></span>٪):</span>
                                <span class="font-mono font-bold" id="calc-display-discount">-۲۵,۰۰۰ تومان</span>
                            </div>
                        </div>

                        <!-- Final Estimated Amount -->
                        <div class="p-4 rounded-2xl bg-white/5 border border-white/10 text-center">
                            <div class="text-[11px] text-slate-300 font-bold mb-1">مبلغ تخمینی با تخفیف رزرو آنلاین:</div>
                            <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono tracking-tight flex items-baseline justify-center gap-1.5">
                                <span id="calc-display-final-price">۲۲۵,۰۰۰</span>
                                <span class="text-xs text-white/80 font-normal">تومان</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">تضمین کیفیت خدمات و بازگشت وجه در صورت انصراف</div>
                        </div>

                        <!-- Booking Action -->
                        <div class="pt-2">
                            <a href="<?= $ctaHref ?>" id="calc-booking-cta-btn" class="w-full py-3.5 px-4 rounded-2xl bg-[#fd8100] hover:bg-[#ea580c] text-white text-xs font-black shadow-xl shadow-orange-950/30 flex items-center justify-center gap-2 active:scale-95 transition-all">
                                <span class="material-symbols-outlined text-base">calendar_month</span>
                                <span>رزرو آنلاین این خدمت با تخفیف</span>
                                <span class="material-symbols-outlined text-sm">arrow_left</span>
                            </a>
                        </div>

                        <!-- Regulatory Note -->
                        <p class="text-[10px] text-slate-400 leading-relaxed text-justify">
                            * تعرفه‌های فوق بر مبنای جدول خدمات استاندارد دامپزشکی برآورد گردیده است. هزینه نهایی ممکن است متناسب با وزن دقیق حیوان و در صورت نیاز به داروها یا بیهوشی خاص تنظیم گردد.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Doctors & Specialists Roster Block -->
    <?php if ((!empty($doctorsBlock['enabled']) && !empty($tenantDoctors)) || $isPreview): ?>
    <section id="doctors" class="py-16 bg-white border-b border-slate-200/60 <?= (empty($doctorsBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($doctorsBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="doctors_roster">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">کادر تخصصی و پزشکان مقیم</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-doctors-heading" data-studio-editable="doctors_heading"><?= htmlspecialchars($doctorsBlock['heading'] ?? 'پزشکان و جراحان مرکز') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2" id="live-doctors-subtitle" data-studio-editable="doctors_subtitle"><?= htmlspecialchars($doctorsBlock['subtitle'] ?? 'دامپزشکان مجرب با پرونده سلامت ابری و امکان نوبت‌دهی آنلاین') ?></p>
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
                        <a href="#booking" class="w-full py-2.5 px-3 rounded-xl bg-tenant-light hover:bg-tenant-primary text-tenant-primary hover:text-white text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                            <span>هماهنگی و رزرو نوبت</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Booking Widget Block (Doctors & Clinics) -->
    <?php if (!empty($bookingBlock['enabled']) || $isPreview): ?>
    <?php 
        $bookPhone = !empty(trim($contactBlock['phone'] ?? '')) ? $contactBlock['phone'] : (!empty(trim($headerBlock['phone'] ?? '')) ? $headerBlock['phone'] : '۰۲۱-۸۸۸۸۹۹۹۹');
    ?>
    <section id="booking" class="py-16 bg-gradient-to-r from-[#001a48] to-[#042866] bg-gradient-dark-navy text-white border-b border-slate-800 <?= (empty($bookingBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="background: linear-gradient(135deg, #001a48 0%, #08296c 50%, #001438 100%) !important; color: #ffffff !important; <?= (empty($bookingBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="booking">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-3 text-center md:text-right">
                <span class="px-3 py-1 rounded-full bg-white/10 text-amber-300 text-xs font-bold">پذیرش و نوبت‌دهی مستقیم</span>
                <h3 class="text-2xl sm:text-3xl font-black" id="live-booking-heading" data-studio-editable="booking_heading"><?= htmlspecialchars($bookingBlock['heading'] ?? 'رزرو اینترنتی و تلفنی نوبت') ?></h3>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl font-normal leading-relaxed">
                    <?= htmlspecialchars($bookingBlock['subtitle'] ?? 'جهت رزرو نوبت ویزیت، مشاوره یا خدمات تشخیصی، مستقیماً با پذیرش مجموعه در ارتباط باشید.') ?>
                </p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="tel:<?= htmlspecialchars($bookPhone) ?>" class="px-7 py-3.5 rounded-2xl bg-[#fd8100] hover:bg-[#ea580c] text-white font-black text-xs shadow-2xl transition-all flex items-center gap-2" style="background-color: #fd8100 !important; color: #ffffff !important;">
                    <span class="material-symbols-outlined text-base" style="color: #ffffff !important;">phone_in_talk</span>
                    <span style="color: #ffffff !important;">تماس و رزرو فوری نوبت</span>
                </a>
                <button type="button" onclick="openNavHubModal()" class="px-5 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs backdrop-blur-md transition-all flex items-center gap-1.5 border border-white/20">
                    <span class="material-symbols-outlined text-base">near_me</span>
                    <span>مسیریابی و پیام‌رسان‌ها</span>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Storefront & Pharmacy Products Grid (Tenant Inventory) -->
    <?php if (!empty($storefrontBlock['enabled']) || $isPreview): ?>
    <section id="storefront" class="py-16 bg-white border-b border-slate-200/60 <?= (empty($storefrontBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($storefrontBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="storefront">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">انبار اختصاصی و تحویل مستقیم</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>مدیریت آنلاین انبار آسنا</span>
                        </span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-storefront-heading" data-studio-editable="storefront_heading"><?= htmlspecialchars($storefrontBlock['heading'] ?? 'ویترین محصولات و داروهای موجود') ?></h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="tenantCart.openDrawer()" class="px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 text-xs font-bold flex items-center gap-2 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-base text-amber-600">shopping_bag</span>
                        <span>مشاهده سبد خرید</span>
                        <span id="tenant-storefront-cart-count" class="hidden px-1.5 py-0.2 rounded-full text-[10px] font-black bg-[#fd8100] text-white">0</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($tenantProducts)): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <?php foreach ($tenantProducts as $p): ?>
                <?php 
                    $pPrice = (float)($p['price'] ?? 0);
                    $pImg = !empty($p['image_url']) ? $p['image_url'] : (!empty($p['image']) ? $p['image'] : 'assets/images/placeholders/placeholder-product.svg');
                    $isRx = !empty($p['requires_prescription']);
                    $pStock = (int)($p['stock'] ?? 0);
                    $pSource = htmlspecialchars($p['item_source'] ?? 'product');
                ?>
                <div class="bg-white rounded-3xl p-4 border border-slate-200 hover:border-slate-300 hover:shadow-xl transition-all flex flex-col justify-between group relative"
                     data-product-id="<?= (int)$p['id'] ?>"
                     data-product-source="<?= $pSource ?>"
                     data-product-name="<?= htmlspecialchars($p['name']) ?>"
                     data-product-price="<?= (int)$pPrice ?>"
                     data-product-image="<?= htmlspecialchars($pImg) ?>"
                     data-product-stock="<?= $pStock ?>">
                    <div>
                        <div class="aspect-square rounded-2xl bg-slate-50 overflow-hidden mb-3 relative">
                            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            <?php if ($isRx): ?>
                                <span class="absolute top-2 right-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow">نسخه‌ای (Rx)</span>
                            <?php endif; ?>
                            <div class="absolute bottom-2 right-2">
                                <?php if ($pStock > 0): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-white/95 backdrop-blur-xs px-2 py-0.5 rounded-lg shadow-2xs border border-emerald-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span><?= number_format($pStock) ?> در انبار</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center text-[10px] font-bold text-rose-700 bg-white/95 backdrop-blur-xs px-2 py-0.5 rounded-lg shadow-2xs border border-rose-200">
                                        اتمام موجودی
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h4 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2 mb-1"><?= htmlspecialchars($p['name']) ?></h4>
                        <?php if (!empty($p['brand'])): ?>
                            <div class="text-[11px] text-slate-400 font-medium mb-2"><?= htmlspecialchars($p['brand']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-slate-900"><?= number_format($pPrice) ?></span>
                            <span class="text-[10px] text-slate-400">تومان</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <?php if ($pStock > 0): ?>
                            <button type="button" onclick="tenantCart.addItem(this, false)" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 flex items-center justify-center transition-colors shadow-2xs active:scale-95" title="افزودن به سبد خرید">
                                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                            </button>
                            <button type="button" onclick="tenantCart.addItem(this, true)" class="px-2.5 py-1.5 rounded-xl bg-tenant-primary hover:opacity-90 text-white text-[11px] font-bold transition-all shadow-xs active:scale-95 flex items-center gap-1" title="خرید فوری این کالا">
                                <span>خرید</span>
                                <span class="material-symbols-outlined text-xs">flash_on</span>
                            </button>
                            <?php else: ?>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 text-[11px] font-bold">ناموجود</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Clean Empty Inventory Showcase -->
            <div class="bg-gradient-to-br from-amber-50/60 via-white to-amber-50/30 border border-amber-200/80 rounded-3xl p-8 sm:p-10 text-center max-w-2xl mx-auto shadow-sm">
                <div class="w-16 h-16 bg-amber-100 text-amber-700 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-inner">
                    <span class="material-symbols-outlined text-3xl">inventory_2</span>
                </div>
                <h4 class="font-black text-slate-900 text-base sm:text-lg mb-2">انبار اختصاصی این مرکز در پلتفرم آسنا</h4>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                    کالاها و داروهای این وب‌سایت مستقیماً بر اساس موجودی انبار شما در پنل آسنا نمایش داده می‌شوند. در حال حاضر کالایی در انبار فعال این مرکز ثبت نشده است.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="<?= htmlspecialchars($tenantMgmtUrl) ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-[#001a48] to-slate-800 text-white text-xs font-black shadow-md hover:shadow-lg transition-all active:scale-95">
                        <span class="material-symbols-outlined text-base text-amber-300">settings</span>
                        <span>مدیریت انبار در پنل اختصاصی آسنا</span>
                    </a>
                    <a href="#contact" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                        <span class="material-symbols-outlined text-base">call</span>
                        <span>تماس جهت استعلام موجودی</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Verified Patient & Client Reviews -->
    <?php if ((!empty($reviewsBlock['enabled']) && !empty($tenantReviews)) || $isPreview): ?>
    <section id="reviews" class="py-16 bg-white border-b border-slate-200/60 <?= (empty($reviewsBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($reviewsBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="reviews">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">اعتبار سنجی مراجعین</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-reviews-heading" data-studio-editable="reviews_heading"><?= htmlspecialchars($reviewsBlock['heading'] ?? 'نظرات و بازخورد سرپرستان پت') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($reviewsBlock['subtitle'] ?? 'تجربه مراجعین واقعی با استناد به ویزیت‌ها و مراجعات حضوری ثبت‌شده') ?></p>
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

    <!-- Clinical & Store FAQ Accordion Block -->
    <?php if ((!empty($faqBlock['enabled']) && !empty($tenantFaqs)) || $isPreview): ?>
    <section id="faq" class="py-16 bg-white border-b border-slate-200/60 <?= (empty($faqBlock['enabled']) && $isPreview) ? 'hidden' : '' ?>" style="<?= (empty($faqBlock['enabled']) && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="faq">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">راهنمای مراجعین و بیماران</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-faq-heading" data-studio-editable="faq_heading"><?= htmlspecialchars($faqBlock['heading'] ?? 'پرسش‌های متداول') ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2"><?= htmlspecialchars($faqBlock['subtitle'] ?? 'پاسخ به سوالات متداول پیرامون نوبت‌دهی آنلاین، نسخه‌های الکترونیک و شرایط اورژانس') ?></p>
            </div>

            <div class="space-y-3.5" id="live-faq-list">
                <?php foreach ($tenantFaqs as $fIdx => $faq): ?>
                <div class="relative group/repeater-item border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/70 hover:bg-white hover:border-slate-300 transition-all shadow-2xs" data-repeater-index="<?= $fIdx ?>">
                    <button type="button" 
                            onclick="toggleSiteFaq(<?= $fIdx ?>)" 
                            class="w-full p-4 sm:p-5 text-right flex items-center justify-between gap-4 font-black text-xs sm:text-sm text-slate-800 transition-colors">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-tenant-light text-tenant-primary flex items-center justify-center text-xs shrink-0 font-mono">؟</span>
                            <span><?= htmlspecialchars($faq['q']) ?></span>
                        </span>
                        <div class="flex items-center gap-2">
                            <?php if ($isPreview): ?>
                            <div class="hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900 text-white px-2 py-0.5 rounded-lg text-xs" onclick="event.stopPropagation()">
                                <button type="button" onclick="notifyStudioRepeaterModal('faq', <?= $fIdx ?>)" title="ویرایش پرسش" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-xs">edit</span>
                                </button>
                                <button type="button" onclick="notifyStudioRemoveRepeater('faq', <?= $fIdx ?>)" title="حذف پرسش" class="hover:text-red-400 p-0.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                </button>
                            </div>
                            <?php endif; ?>
                            <span class="material-symbols-outlined text-slate-400 text-lg transition-transform duration-300 shrink-0" id="faq-chevron-<?= $fIdx ?>">expand_more</span>
                        </div>
                    </button>
                    <div id="faq-answer-<?= $fIdx ?>" class="hidden px-5 pb-5 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100 bg-white">
                        <p><?= nl2br(htmlspecialchars($faq['a'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($isPreview): ?>
                <button type="button" onclick="notifyStudioRepeaterModal('faq', -1)" class="w-full p-3.5 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex items-center justify-center gap-2 transition-all cursor-pointer" title="افزودن پرسش جدید">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span class="text-xs font-black">افزودن پرسش متداول جدید</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Schema.org FAQPage Structured Data for SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        <?php 
        $faqJsonItems = [];
        foreach ($tenantFaqs as $fItem) {
            $faqJsonItems[] = json_encode([
                "@type" => "Question",
                "name" => $fItem['q'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $fItem['a']
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        echo implode(",\n        ", $faqJsonItems);
        ?>
      ]
    }
    </script>
    <?php endif; ?>

    <!-- Social Media & Digital Visit Card Hub Section -->
    <?php 
        $socialBlockEnabled = !empty($socialLinksBlock['enabled'] ?? true);
        $socialHeading = $socialLinksBlock['heading'] ?? 'شبکه‌های اجتماعی و ارتباط آنلاین';
        $socialSubtitle = $socialLinksBlock['subtitle'] ?? 'جهت ارتباط مستقیم، مشاهده جدیدترین ویدئوها، اخبار و رزرو مشاوره ما را دنبال فرمایید.';
    ?>
    <section id="social-links" class="py-16 bg-gradient-to-b from-white via-purple-50/20 to-white border-b border-slate-200/60 relative overflow-hidden <?= (!$socialBlockEnabled && $isPreview) ? 'hidden' : '' ?>" style="<?= (!$socialBlockEnabled && $isPreview) ? 'display: none !important;' : '' ?>" data-block-id="social_links">
        <div class="max-w-6xl mx-auto px-4 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-black text-purple-700 bg-purple-100/70 border border-purple-200/60 px-3 py-1 rounded-full uppercase tracking-wider inline-flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-sm">share</span>
                    <span>کانال‌های رسمی و ارتباط دیجیتال</span>
                </span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-3" id="live-social-heading" data-studio-editable="social_heading"><?= htmlspecialchars($socialHeading) ?></h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed" id="live-social-subtitle" data-studio-editable="social_subtitle"><?= htmlspecialchars($socialSubtitle) ?></p>
            </div>

            <!-- Social Links Symmetrical 4-Column Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="live-social-grid">
                <?php foreach ($socialLinks as $slink): ?>
                <?php if (!empty($slink['enabled']) || !isset($slink['enabled'])): ?>
                <div class="bg-white/90 backdrop-blur-md p-4 rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-lg hover:border-slate-300 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 inset-x-0 h-1 rounded-t-3xl transition-all group-hover:h-1.5" style="background: <?= htmlspecialchars($slink['color'] ?? '#7c3aed') ?>;"></div>
                    <div class="flex items-center gap-3 pt-1">
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-md transition-transform duration-300 group-hover:scale-105" style="background: <?= htmlspecialchars($slink['color'] ?? '#7c3aed') ?>;">
                            <span class="material-symbols-outlined text-xl"><?= htmlspecialchars($slink['icon'] ?? 'share') ?></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-black text-slate-800 truncate"><?= htmlspecialchars($slink['title'] ?? 'شبکه اجتماعی') ?></div>
                            <?php if (!empty($slink['badge'])): ?>
                            <div class="text-[10px] text-purple-700 font-bold truncate mt-0.5"><?= htmlspecialchars($slink['badge']) ?></div>
                            <?php elseif (!empty($slink['handle'])): ?>
                            <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" dir="ltr" title="<?= htmlspecialchars($slink['handle']) ?>"><?= htmlspecialchars($slink['handle']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <?php if (!empty($slink['handle'])): ?>
                        <button type="button" onclick="copySocialHandle('<?= htmlspecialchars($slink['handle'], ENT_QUOTES) ?>')" title="کپی آیدی" class="px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-xs">content_copy</span>
                            <span>کپی</span>
                        </button>
                        <?php endif; ?>
                        <?php if (!empty($slink['url'])): ?>
                        <a href="<?= htmlspecialchars($slink['url']) ?>" target="_blank" referrerpolicy="origin" class="flex-1 py-1.5 px-3 rounded-xl text-center text-xs font-black text-white transition-all flex items-center justify-center gap-1 shadow-sm group-hover:shadow-md" style="background: <?= htmlspecialchars($slink['color'] ?? '#7c3aed') ?>;">
                            <span>ورود</span>
                            <span class="material-symbols-outlined text-xs">arrow_left</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Digital Business Card Luxury Showcase Pill -->
            <div class="mt-10 p-6 rounded-3xl bg-gradient-to-br from-slate-900 via-[#001a48] to-slate-900 text-white shadow-xl border border-white/10 relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4 text-center md:text-right">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white flex items-center justify-center shrink-0 shadow-inner">
                            <span class="material-symbols-outlined text-3xl text-amber-400">qr_code_2</span>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 text-[10px] font-bold mb-1 border border-amber-400/30">
                                <span class="material-symbols-outlined text-xs">tap_and_play</span>
                                <span>فناوری NFC و استند QR رومیزی</span>
                            </div>
                            <h4 class="text-base font-black text-white">کارت ویزیت دیجیتال هوشمند مراجعین</h4>
                            <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                                ذخیره مستقیم اطلاعات تماس، آدرس و پیوندها در تلفن همراه مراجعین تنها با یک اسکن ساده بدون نیاز به نصب نرم‌افزار
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 shrink-0">
                        <button type="button" onclick="openVCardModal()" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-[#fd8100] to-[#ea580c] hover:from-[#ea580c] hover:to-[#fd8100] text-white text-xs font-black shadow-lg shadow-orange-500/20 active:scale-95 transition-all flex items-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-base">contact_page</span>
                            <span>نمایش کارت ویزیت و دانلود (vCard)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact & Operating Hours & Navigation Hub -->
    <?php if (!empty($contactBlock['enabled'])): ?>
    <?php 
        $targetLat = $navHubBlock['lat'] ?? '35.7219';
        $targetLng = $navHubBlock['lng'] ?? '51.3347';
        $rawAddress = !empty(trim($contactBlock['address'] ?? '')) ? $contactBlock['address'] : 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
        $hoursDisplay = !empty(trim($contactBlock['hours'] ?? '')) ? $contactBlock['hours'] : 'شنبه تا پنجشنبه ۸ الی ۲۲';
        $phoneDisplay = !empty(trim($contactBlock['phone'] ?? '')) ? $contactBlock['phone'] : (!empty(trim($headerBlock['phone'] ?? '')) ? $headerBlock['phone'] : '۰۲۱-۸۸۸۸۹۹۹۹');
        $emergencyDisplay = !empty(trim($contactBlock['emergency_phone'] ?? '')) ? $contactBlock['emergency_phone'] : (!empty(trim($emergencyBlock['phone'] ?? '')) ? $emergencyBlock['phone'] : '');
    ?>
    <section id="contact" class="py-16 bg-slate-50 border-b border-slate-200/60" data-block-id="contact">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-xl mx-auto mb-10">
                <span class="text-xs font-black text-tenant-primary uppercase tracking-wider">راه‌های ارتباطی و مسیریابی</span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1" id="live-contact-heading" data-studio-editable="contact_heading"><?= htmlspecialchars($contactBlock['heading'] ?? 'اطلاعات تماس و نشانی') ?></h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Address Card -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">location_on</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">نشانی مراجعه حضوری</h4>
                            <p class="text-xs text-slate-500 leading-relaxed" id="live-contact-address" data-studio-editable="contact_address">
                                <?php if (!empty($addressMapLink)): ?>
                                    <a href="<?= htmlspecialchars($addressMapLink) ?>" target="_blank" rel="noopener" class="hover:text-tenant-primary hover:underline transition-colors" title="مشاهده موقعیت روی نقشه">
                                        <?= htmlspecialchars($rawAddress) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($rawAddress) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" onclick="copyAddressToClipboard('<?= addslashes($rawAddress) ?>')" class="text-[11px] text-tenant-primary font-bold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">content_copy</span>
                            <span>کپی نشانی</span>
                        </button>
                        <a href="<?= !empty($addressMapLink) ? htmlspecialchars($addressMapLink) : 'javascript:openNavHubModal()' ?>" target="<?= !empty($addressMapLink) ? '_blank' : '_self' ?>" rel="noopener" id="live-contact-nav-btn" class="text-[11px] text-amber-600 font-bold hover:underline flex items-center gap-1 cursor-pointer" onclick="if (!this.getAttribute('href') || this.getAttribute('href').startsWith('javascript:')) openNavHubModal();">
                            <span class="material-symbols-outlined text-xs">near_me</span>
                            <span id="live-contact-nav-text" data-studio-editable="contact_nav_btn_text"><?= htmlspecialchars($navBtnText) ?></span>
                        </a>
                    </div>
                </div>

                <!-- Hours Card -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">schedule</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">ساعات کاری و پذیرش</h4>
                            <p class="text-xs text-slate-500 leading-relaxed" id="live-contact-hours" data-studio-editable="contact_hours"><?= htmlspecialchars($hoursDisplay) ?></p>
                        </div>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="flex items-center gap-1 <?= $isCurrentlyOpen ? 'text-emerald-600 font-bold' : 'text-slate-400' ?>">
                            <span class="w-2 h-2 rounded-full <?= $isCurrentlyOpen ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' ?>"></span>
                            <span><?= $isCurrentlyOpen ? 'هم‌اکنون باز است' : 'خارج از شیفت' ?></span>
                        </span>
                        <span class="text-slate-400 font-mono"><?= htmlspecialchars($dutyCountdownText) ?></span>
                    </div>
                </div>

                <!-- Phone Card -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">call</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">تلفن‌های تماس</h4>
                            <div class="space-y-1">
                                <div><a href="tel:<?= htmlspecialchars($phoneDisplay) ?>" class="text-xs font-bold text-slate-700 hover:text-tenant-primary font-mono" dir="ltr" id="live-contact-phone" data-studio-editable="contact_phone"><?= htmlspecialchars($phoneDisplay) ?></a></div>
                                <div class="text-[11px] text-red-600 font-bold <?= empty($emergencyDisplay) ? 'hidden' : '' ?>" id="live-contact-emergency-wrap">اورژانس: <span dir="ltr" class="font-mono" id="live-contact-emergency" data-studio-editable="contact_emergency"><?= htmlspecialchars($emergencyDisplay) ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-3 text-xs">
                        <a href="tel:<?= htmlspecialchars($phoneDisplay) ?>" class="text-[11px] text-blue-600 font-bold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">phone_in_talk</span>
                            <span>تماس تلفنی</span>
                        </a>
                        <button type="button" onclick="openNavHubModal()" class="text-[11px] text-slate-500 font-bold hover:text-slate-900 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">chat</span>
                            <span>پیام‌رسان‌ها</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 1-Tap Navigation Strip -->
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 bg-gradient-navhub rounded-3xl p-6 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important; color: #ffffff !important;" id="navigation-hub">
                <div class="flex items-center gap-4 text-center md:text-right">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shrink-0 text-amber-300">
                        <span class="material-symbols-outlined text-3xl">directions</span>
                    </div>
                    <div>
                        <h4 class="text-base font-black" id="live-navhub-heading" data-studio-editable="navhub_heading"><?= htmlspecialchars($navHubBlock['heading'] ?? 'مسیریابی ۱ کلیکه با اپلیکیشن‌های نقشه') ?></h4>
                        <p class="text-xs text-slate-300 mt-0.5" id="live-navhub-subtitle" data-studio-editable="navhub_subtitle"><?= htmlspecialchars($navHubBlock['subtitle'] ?? 'مستقیماً موقعیت دقیق مجموعه را در مسیریاب‌های محبوب ایرانی و بین‌المللی باز نمایید.') ?></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-2.5" id="live-navhub-buttons">
                    <?php
                    $targetLat = $navHubBlock['lat'] ?? '35.7219';
                    $targetLng = $navHubBlock['lng'] ?? '51.3347';
                    $navApps = !empty($navHubBlock['apps']) && is_array($navHubBlock['apps']) ? $navHubBlock['apps'] : [
                        ['id' => 'neshan', 'name' => 'مسیریابی با نشان', 'icon' => 'navigation', 'bg' => 'bg-blue-600', 'url' => $navHubBlock['neshan_url'] ?? ''],
                        ['id' => 'balad', 'name' => 'مسیریابی با بلد', 'icon' => 'map', 'bg' => 'bg-emerald-600', 'url' => $navHubBlock['balad_url'] ?? ''],
                        ['id' => 'waze', 'name' => 'ویز (Waze)', 'icon' => 'turn_right', 'bg' => 'bg-cyan-600', 'url' => $navHubBlock['waze_url'] ?? ''],
                        ['id' => 'google_maps', 'name' => 'گوگل مپ', 'icon' => 'place', 'bg' => 'bg-slate-800', 'url' => $navHubBlock['google_maps_url'] ?? ''],
                    ];
                    $resolveNavUrl = function($app, $block, $lat, $lng) {
                        if (!empty($app['url'])) return $app['url'];
                        $id = $app['id'] ?? '';
                        $name = mb_strtolower($app['name'] ?? '');
                        if ($id === 'neshan' || str_contains($name, 'نشان')) {
                            return !empty($block['neshan_url']) ? $block['neshan_url'] : "https://neshan.org/maps/@{$lat},{$lng},16z";
                        }
                        if ($id === 'balad' || str_contains($name, 'بلد')) {
                            return !empty($block['balad_url']) ? $block['balad_url'] : "https://balad.ir/location?latitude={$lat}&longitude={$lng}";
                        }
                        if ($id === 'waze' || str_contains($name, 'waze') || str_contains($name, 'ویز')) {
                            return !empty($block['waze_url']) ? $block['waze_url'] : "https://waze.com/ul?ll={$lat},{$lng}&navigate=yes";
                        }
                        if ($id === 'google_maps' || str_contains($name, 'گوگل') || str_contains($name, 'google')) {
                            return !empty($block['google_maps_url']) ? $block['google_maps_url'] : "https://maps.google.com/?q={$lat},{$lng}";
                        }
                        return "https://maps.google.com/?q={$lat},{$lng}";
                    };
                    ?>
                    <?php foreach ($navApps as $appIdx => $app): ?>
                    <?php $appFinalUrl = $resolveNavUrl($app, $navHubBlock, $targetLat, $targetLng); ?>
                    <div class="relative group/repeater-item inline-flex" data-repeater-index="<?= $appIdx ?>">
                        <a href="<?= htmlspecialchars($appFinalUrl) ?>" target="_blank" class="px-3.5 py-2.5 rounded-xl <?= htmlspecialchars($app['bg'] ?? 'bg-blue-600') ?> hover:opacity-90 text-white text-xs font-bold shadow-md transition-all flex items-center gap-1.5 active:scale-95" id="nav-btn-<?= htmlspecialchars($app['id'] ?? $appIdx) ?>">
                            <span class="material-symbols-outlined text-sm"><?= htmlspecialchars($app['icon'] ?? 'navigation') ?></span>
                            <span><?= htmlspecialchars($app['name'] ?? 'مسیریاب') ?></span>
                        </a>
                        <?php if ($isPreview): ?>
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900/95 text-white px-1.5 py-0.5 rounded-lg shadow-xl border border-white/20 z-20 text-[10px]">
                            <button type="button" onclick="notifyStudioRepeaterModal('navigation_hub', <?= $appIdx ?>)" title="ویرایش دکمه" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">edit</span>
                            </button>
                            <button type="button" onclick="notifyStudioRemoveRepeater('navigation_hub', <?= $appIdx ?>)" title="حذف دکمه" class="hover:text-red-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">delete</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($isPreview): ?>
                    <button type="button" onclick="notifyStudioRepeaterModal('navigation_hub', -1)" class="px-3 py-2 rounded-xl border-2 border-dashed border-white/40 hover:border-amber-400 hover:text-amber-300 text-white/80 text-xs font-bold transition-all flex items-center gap-1 active:scale-95 cursor-pointer" title="افزودن مسیریاب دلخواه">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        <span>افزودن مسیریاب</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Rich Agency-Grade Footer with Dual Brand Showcase (Tenant Logo on Right, ASENA Logo on Left) -->
    <footer class="bg-slate-950 text-slate-300 pt-16 pb-28 md:pb-14 border-t border-slate-800/80 relative overflow-hidden" data-block-id="footer">
        <!-- Subtle Atmospheric Background Accents -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-6xl mx-auto px-4 relative z-10 space-y-12">
            
            <!-- Tier 1: Dual Brand Showcase & Trust Strip (Tenant on Right, ASENA on Left) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-10 border-b border-slate-800/80 items-stretch">
                
                <!-- RIGHT SIDE (RTL): Tenant's Own Logo & Brand Presentation (lg:col-span-6) -->
                <div class="lg:col-span-6 p-6 rounded-3xl bg-slate-900/70 border border-slate-800/90 backdrop-blur-md flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <!-- Tenant's Custom Logo -->
                            <div class="w-14 h-14 rounded-2xl bg-white p-1 border border-slate-700/80 shadow-lg shrink-0 flex items-center justify-center overflow-hidden">
                                <img src="<?= htmlspecialchars($siteLogo) ?>" id="live-footer-logo" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover rounded-xl">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-black text-white tracking-tight truncate" id="live-footer-title"><?= htmlspecialchars($site['site_title']) ?></h3>
                                    <span class="material-symbols-outlined text-emerald-400 text-sm" title="عضو تاییدشده رسمی">verified</span>
                                </div>
                                <p class="text-xs text-slate-400 font-medium truncate mt-0.5" id="live-footer-tagline"><?= htmlspecialchars($site['site_tagline'] ?? 'مرکز تخصصی و فوق‌تخصصی سلامت و درمان حیوانات خانگی') ?></p>
                            </div>
                        </div>

                        <!-- Editable About Text -->
                        <p class="text-xs text-slate-400 leading-relaxed font-normal" id="live-footer-about" data-studio-editable="footer_about">
                            <?= htmlspecialchars($footerBlock['about_text'] ?? "ارائه خدمات تخصصی سلامت و درمان حیوانات خانگی با پیشرفته‌ترین تجهیزات تشخیصی و کادر مجرب بالینی.") ?>
                        </p>
                    </div>

                    <!-- Tenant Trust Badges & Contact Quick Pill -->
                    <div class="pt-2 flex flex-wrap items-center gap-2.5 text-[11px]">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/90 text-emerald-400 border border-slate-700 font-bold">
                            <span class="material-symbols-outlined text-xs">license</span>
                            <span>پروانه نظام دامپزشکی تاییدشده</span>
                        </span>
                        <?php if (!empty($contactBlock['phone'])): ?>
                        <a href="tel:<?= htmlspecialchars($contactBlock['phone']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 font-mono font-bold transition-colors" dir="ltr">
                            <span class="material-symbols-outlined text-xs text-emerald-400">call</span>
                            <span><?= htmlspecialchars($contactBlock['phone']) ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- LEFT SIDE (RTL): Official ASENA Ecosystem Logo & Guarantee Hub (lg:col-span-6) -->
                <div class="lg:col-span-6 p-6 rounded-3xl bg-linear-to-br from-indigo-950/40 via-slate-900/80 to-slate-900/90 border border-indigo-500/20 backdrop-blur-md flex flex-col justify-between space-y-5">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3.5">
                            <!-- Official ASENA Logo -->
                            <div class="w-14 h-14 rounded-2xl bg-white p-1.5 border border-indigo-300/40 shadow-lg shadow-indigo-950/30 shrink-0 flex items-center justify-center overflow-hidden">
                                <img src="<?= htmlspecialchars($asenaLogo) ?>" alt="لوگوی رسمی شبکه سلامت آسنا" class="w-full h-full object-contain">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-black text-white tracking-tight">اکوسیستم فناوری سلامت آسنا</h3>
                                    <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-black border border-indigo-400/30">شبکه رسمی</span>
                                </div>
                                <p class="text-xs text-indigo-200/70 font-medium mt-0.5">پلتفرم ابری پرونده الکترونیک، زنجیره سرد و نوبت‌دهی هوشمند</p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed font-normal">
                            این پایگاه به عنوان عضو تاییدشده شبکه جامع فناوری‌های سلامت و دامپزشکی آسنا فعالیت نموده و کلیه فرآیندهای مالی، رزرو و تحویل دارویی تحت گارانتی و نظارت متمرکز پلتفرم ارائه می‌گردد.
                        </p>
                    </div>

                    <!-- ASENA Verification & Portal Actions -->
                    <div class="pt-2 flex flex-wrap items-center gap-2.5">
                        <button type="button" onclick="openTrustVerifyModal()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-200 text-xs font-bold border border-indigo-500/40 transition-all shadow-xs cursor-pointer">
                            <span class="material-symbols-outlined text-sm text-indigo-400">verified_user</span>
                            <span>استعلام آنلاین اصالت عضویت</span>
                        </button>
                        <a href="https://asena.company" target="_blank" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold border border-slate-700 transition-colors">
                            <span>پرتال مرکزی آسنا</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Tier 2: 4-Column Navigation & Contact & Socials -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 pb-8 border-b border-slate-800/80">
                
                <!-- Col 1: Site Navigation (lg:col-span-3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-emerald-400">menu_book</span>
                        <span>بخش‌های وب‌سایت</span>
                    </h4>
                    <ul class="space-y-2 text-xs font-medium text-slate-400">
                        <li><a href="#about" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>›</span> معرفی و سوابق بالینی</a></li>
                        <li><a href="#services" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>›</span> خدمات تخصصی و جراحی</a></li>
                        <?php if (!empty($calculatorBlock['enabled']) || $isPreview): ?>
                        <li><a href="#calculator" class="hover:text-amber-400 transition-colors flex items-center gap-1.5"><span>›</span> محاسبه‌گر آنلاین تعرفه‌ها</a></li>
                        <?php endif; ?>
                        <?php if (!empty($doctorsBlock['enabled']) || $isPreview): ?>
                        <li><a href="#doctors" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>›</span> کادر پزشکان و متخصصان</a></li>
                        <?php endif; ?>
                        <?php if (!empty($bookingBlock['enabled']) || $isPreview): ?>
                        <li><a href="#booking" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>›</span> رزرو ۲۴ ساعته نوبت</a></li>
                        <?php endif; ?>
                        <li><a href="#contact" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>›</span> تماس، نشانی و موقعیت</a></li>
                    </ul>
                </div>

                <!-- Col 2: ASENA Ecosystem Services (lg:col-span-3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-indigo-400">hub</span>
                        <span>خدمات اکوسیستم آسنا</span>
                    </h4>
                    <ul class="space-y-2 text-xs font-medium text-slate-400">
                        <li><a href="../chat.php" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><span>🩺</span> ویزیت و تله‌هلث آنلاین</a></li>
                        <li><a href="../pharmacy.php" class="hover:text-purple-400 transition-colors flex items-center gap-1.5"><span>💊</span> داروخانه زنجیره سرد (۲-۸°C)</a></li>
                        <li><a href="../subscriptions.php" class="hover:text-blue-400 transition-colors flex items-center gap-1.5"><span>🔄</span> تحویل دوره‌ای غذای درمانی</a></li>
                        <li><a href="../rewards.php" class="hover:text-amber-400 transition-colors flex items-center gap-1.5"><span>🏆</span> باشگاه وفاداری و پاداش</a></li>
                        <li><a href="../charity.php" class="hover:text-rose-400 transition-colors flex items-center gap-1.5"><span>🐾</span> صندوق امداد حیوانات حمایتی</a></li>
                        <li><button type="button" onclick="openVCardModal()" class="hover:text-indigo-400 transition-colors flex items-center gap-1.5 text-right"><span>📱</span> کارت ویزیت و QR اختصاصی</button></li>
                    </ul>
                </div>

                <!-- Col 3: Address & Navigation Hub (lg:col-span-3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-amber-400">location_on</span>
                        <span>نشانی و ساعات کاری</span>
                    </h4>
                    <p class="text-xs text-slate-400 leading-relaxed font-normal" id="live-footer-address">
                        <?php if (!empty($addressMapLink)): ?>
                            <a href="<?= htmlspecialchars($addressMapLink) ?>" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="مشاهده موقعیت روی نقشه">
                                <?= htmlspecialchars($contactBlock['address'] ?? 'تهران، خیابان ولیعصر، نرسیده به میدان ونک') ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($contactBlock['address'] ?? 'تهران، خیابان ولیعصر، نرسیده به میدان ونک') ?>
                        <?php endif; ?>
                    </p>
                    <div class="pt-1">
                        <a href="<?= !empty($addressMapLink) ? htmlspecialchars($addressMapLink) : 'javascript:openNavHubModal()' ?>" target="<?= !empty($addressMapLink) ? '_blank' : '_self' ?>" rel="noopener" id="live-footer-nav-btn" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 flex items-center justify-center gap-1.5 transition-colors cursor-pointer" onclick="if (!this.getAttribute('href') || this.getAttribute('href').startsWith('javascript:')) openNavHubModal();">
                            <span class="material-symbols-outlined text-sm text-emerald-400">near_me</span>
                            <span id="live-footer-nav-text"><?= htmlspecialchars($navBtnText) ?></span>
                        </a>
                    </div>
                </div>

                <!-- Col 4: Social Channels & Contact (lg:col-span-3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-sky-400">share</span>
                        <span>شبکه‌های ارتباطی مرکز</span>
                    </h4>
                    <p class="text-xs text-slate-400">پاسخگویی سریع در پیام‌رسان‌ها و شبکه‌های اجتماعی:</p>
                    
                    <!-- Social icons row with rich tooltips -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button" onclick="openVCardModal()" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-indigo-900/60 text-indigo-400 hover:text-indigo-300 flex items-center justify-center border border-slate-700 hover:border-indigo-500/50 transition-all shadow-xs active:scale-95 cursor-pointer" title="کارت ویزیت دیجیتال">
                            <span class="material-symbols-outlined text-base">qr_code_2</span>
                        </button>
                        <?php if (!empty($contactBlock['instagram'])): ?>
                        <a href="https://instagram.com/<?= ltrim(htmlspecialchars($contactBlock['instagram']), '@') ?>" target="_blank" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-rose-400 hover:text-rose-300 flex items-center justify-center border border-slate-700 hover:border-rose-500/50 transition-all shadow-xs active:scale-95" title="صفحه اینستاگرام">
                            <span class="text-xs font-black">IG</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($contactBlock['telegram'])): ?>
                        <a href="https://t.me/<?= ltrim(htmlspecialchars($contactBlock['telegram']), '@') ?>" target="_blank" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-blue-900/60 text-blue-400 hover:text-blue-300 flex items-center justify-center border border-slate-700 hover:border-blue-500/50 transition-all shadow-xs active:scale-95" title="کانال یا پشتیبانی تلگرام">
                            <span class="text-xs font-black">TG</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($contactBlock['phone'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $contactBlock['phone']) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-emerald-900/60 text-emerald-400 hover:text-emerald-300 flex items-center justify-center border border-slate-700 hover:border-emerald-500/50 transition-all shadow-xs active:scale-95" title="ارتباط واتساپ">
                            <span class="text-xs font-black">WA</span>
                        </a>
                        <?php endif; ?>
                        <button type="button" onclick="shareSiteUrl()" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-amber-900/60 text-amber-400 hover:text-amber-300 flex items-center justify-center border border-slate-700 hover:border-amber-500/50 transition-all shadow-xs active:scale-95 cursor-pointer" title="اشتراک‌گذاری آدرس سایت">
                            <span class="material-symbols-outlined text-base">link</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Tier 3: Regulatory Trust, Security & Copyright Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="flex flex-wrap items-center gap-3 text-center sm:text-right">
                    <p id="live-footer-copyright" data-studio-editable="footer_copyright">
                        <?= htmlspecialchars($footerBlock['copyright_text'] ?? "کلیه حقوق برای {$site['site_title']} محفوظ است.") ?>
                    </p>
                    <span class="hidden sm:inline">|</span>
                    <span class="text-[11px] text-slate-400">قدرت‌گرفته از اکوسیستم ابری سلامت آسنا</span>
                </div>
                
                <div class="flex items-center gap-4 text-[11px] text-slate-400">
                    <button type="button" onclick="openTrustVerifyModal()" class="hover:text-emerald-400 transition-colors flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-xs text-emerald-400">lock</span>
                        <span>پرداخت امن شاپرک و امانت‌داری</span>
                    </button>
                    <span>|</span>
                    <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="hover:text-white transition-colors flex items-center gap-1 cursor-pointer">
                        <span>بازگشت به بالا</span>
                        <span class="material-symbols-outlined text-xs">keyboard_arrow_up</span>
                    </button>
                </div>
            </div>

        </div>
    </footer>

    <!-- Mobile-First Thumb-Zone Sticky Conversion Bar (< 768px) -->
    <div class="fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-xl border-t border-slate-200/80 p-3 flex md:hidden items-center justify-between gap-2.5 shadow-2xl">
        <div class="flex items-center gap-1.5">
            <?php if (!empty($storefrontBlock['enabled']) || $isPreview): ?>
            <button type="button" onclick="tenantCart.openDrawer()" class="relative w-11 h-11 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 shadow-2xs active:scale-95 transition-transform" title="سبد خرید اینترنتی">
                <span class="material-symbols-outlined text-lg">shopping_cart</span>
                <span id="tenant-mobile-cart-badge" class="hidden absolute -top-1 -right-1 bg-[#fd8100] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center border border-white">0</span>
            </button>
            <?php endif; ?>
            <button type="button" onclick="openNavHubModal()" class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shrink-0 active:scale-95 transition-transform" title="مسیریابی هوشمند">
                <span class="material-symbols-outlined text-lg">near_me</span>
            </button>
            <button type="button" onclick="openVCardModal()" class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0 shadow-2xs active:scale-95 transition-transform" title="کارت ویزیت دیجیتال">
                <span class="material-symbols-outlined text-lg">qr_code_2</span>
            </button>
            <?php if (!empty($contactBlock['phone'])): ?>
            <a href="tel:<?= htmlspecialchars($contactBlock['phone']) ?>" class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0 shadow-sm active:scale-95 transition-transform" title="تماس تلفنی">
                <span class="material-symbols-outlined text-lg">call</span>
            </a>
            <?php endif; ?>
        </div>

        <a href="<?= $ctaHref ?>" class="flex-1 py-3 px-4 rounded-2xl bg-tenant-primary text-white text-xs font-black shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-1.5 active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-base">calendar_month</span>
            <span id="live-mobile-cta" data-studio-editable="mobile_cta_text"><?= htmlspecialchars($mobileBarBlock['cta_text'] ?? 'رزرو آنلاین نوبت') ?></span>
        </a>
    </div>

    <!-- Floating Desktop Cart Trigger Button -->
    <div id="tenant-cart-trigger" class="fixed bottom-6 left-6 z-40 transition-transform duration-300 scale-0 origin-bottom-left hidden md:block">
        <button onclick="tenantCart.openDrawer()" class="bg-[#001a48] hover:bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/20 transition-all hover:scale-105 active:scale-95 group">
            <span class="relative">
                <span class="material-symbols-outlined text-2xl group-hover:rotate-12 transition-transform">shopping_bag</span>
                <span id="tenant-cart-badge" class="absolute -top-2 -right-2 bg-[#fd8100] text-white text-[11px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-[#001a48] shadow-sm">0</span>
            </span>
            <div class="text-right">
                <div class="text-[10px] text-slate-300 font-bold leading-tight">سبد خرید اختصاصی</div>
                <div class="text-xs font-black text-amber-300"><span id="tenant-cart-btn-price">۰</span> تومان</div>
            </div>
        </button>
    </div>

    <!-- Order Success Receipt Modal -->
    <?php if ($orderSuccess): ?>
    <div id="order-success-modal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl border border-emerald-200">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center mx-auto shadow-inner">
                <span class="material-symbols-outlined text-3xl">check_circle</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">سفارش شما با موفقیت ثبت شد!</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                سفارش شما به شماره <span class="font-bold text-slate-900 font-mono">#PC-<?= $orderSuccessId ?></span> در انبار اختصاصی «<?= htmlspecialchars($site['site_title']) ?>» ثبت گردید و به سامانه انبارداری و ارسال مرسولات آسنا منتقل شد.
            </p>
            <?php if (!empty($orderRefId)): ?>
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs font-mono flex items-center justify-between">
                <span class="text-slate-500 font-sans">کد رهگیری شاپرک:</span>
                <span class="font-black text-slate-900"><?= $orderRefId ?></span>
            </div>
            <?php endif; ?>
            <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200 text-right text-[11px] text-emerald-950 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-emerald-800">
                    <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                    <span>اتصال مستقیم به انبار و سامانه پستکس آسنا</span>
                </div>
                <div>کد رهگیری پستی مرسوله به محض بسته‌بندی از طریق پیامک برای شما ارسال خواهد شد.</div>
            </div>
            <div class="pt-2 flex items-center gap-2">
                <a href="site.php?slug=<?= urlencode($site['slug']) ?>" class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                    تأیید و بازگشت به سایت
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">print</span>
                    <span>چاپ رسید</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Failed Alert Modal -->
    <?php if ($orderFailed): ?>
    <div id="order-failed-modal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl border border-rose-200">
            <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto shadow-inner">
                <span class="material-symbols-outlined text-3xl">error</span>
            </div>
            <h3 class="text-lg font-black text-slate-900">پرداخت ناموفق یا لغو شد</h3>
            <p class="text-xs text-slate-600 leading-relaxed"><?= $orderFailMsg ?></p>
            <div class="pt-2 flex items-center justify-center gap-3">
                <a href="site.php?slug=<?= urlencode($site['slug']) ?>" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                    بازگشت به سایت
                </a>
                <button type="button" onclick="document.getElementById('order-failed-modal').remove(); tenantCart.openDrawer(true);" class="px-5 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition-colors">
                    تلاش مجدد برای پرداخت
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Native Tenant Cart & Quick Checkout Drawer (Connected to ASENA) -->
    <div id="tenant-cart-drawer" class="fixed inset-0 z-50 pointer-events-none transition-opacity duration-300 opacity-0">
        <!-- Backdrop -->
        <div id="tenant-cart-backdrop" onclick="tenantCart.closeDrawer()" class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300"></div>

        <!-- Drawer Panel -->
        <div id="tenant-cart-panel" class="absolute inset-y-0 left-0 max-w-md w-full bg-white shadow-2xl flex flex-col justify-between transform -translate-x-full transition-transform duration-300 ease-out pointer-events-auto border-r border-slate-200">
            <!-- Header -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200 shadow-2xs">
                        <span class="material-symbols-outlined text-xl">shopping_cart</span>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">سبد خرید و ثبت سفارش</h3>
                        <div class="text-[11px] text-slate-500 font-bold"><?= htmlspecialchars($site['site_title']) ?></div>
                    </div>
                </div>
                <button type="button" onclick="tenantCart.closeDrawer()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Drawer Body -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4" id="tenant-cart-body">
                <!-- Step 1: Items in Cart -->
                <div id="tenant-cart-step-items" class="space-y-4">
                    <div id="tenant-cart-items-container" class="space-y-3">
                        <!-- Rendered by JS -->
                    </div>

                    <!-- Empty Notice -->
                    <div id="tenant-cart-empty" class="hidden text-center py-12 px-4 space-y-3">
                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                            <span class="material-symbols-outlined text-3xl">remove_shopping_cart</span>
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm">سبد خرید شما خالی است</h4>
                        <p class="text-xs text-slate-500 max-w-xs mx-auto">کالاهای مورد نظر خود را از بخش ویترین انبار به سبد خرید اضافه فرمایید.</p>
                        <button type="button" onclick="tenantCart.closeDrawer()" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                            مشاهده کالاها و داروها
                        </button>
                    </div>

                    <!-- Cost Summary Breakdown -->
                    <div id="tenant-cart-cost-summary" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>مجموع مبالغ کالاها:</span>
                            <span class="font-bold font-mono text-slate-900" id="cart-summary-subtotal">۰ تومان</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>هزینه بسته‌بندی و ارسال:</span>
                            <span class="font-bold font-mono text-slate-900" id="cart-summary-shipping">رایگان</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="flex items-center gap-1">
                                <span>مالیات بر ارزش افزوده (۱۰٪):</span>
                                <span class="material-symbols-outlined text-xs text-slate-400" title="مطابق قانون مالیات بر ارزش افزوده بر روی کالاها">info</span>
                            </span>
                            <span class="font-bold font-mono text-slate-900" id="cart-summary-vat">۰ تومان</span>
                        </div>
                        <div class="pt-2 border-t border-slate-200 flex items-center justify-between font-black text-sm text-slate-900">
                            <span>مبلغ نهایی قابل پرداخت:</span>
                            <span class="text-emerald-700 font-mono text-base" id="cart-summary-total">۰ تومان</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Shipping & Address Form -->
                <div id="tenant-cart-step-checkout" class="hidden space-y-4">
                    <div class="p-3 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 text-base">local_shipping</span>
                        <span>مشخصات تحویل‌گیرنده و صدور بارنامه پستی</span>
                    </div>

                    <form id="tenant-checkout-form" onsubmit="tenantCart.submitOrder(event)" class="space-y-3.5">
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($site['slug']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">نام و نام خانوادگی تحویل‌گیرنده *</label>
                            <input type="text" name="customer_name" required placeholder="مثال: علی رضایی" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">شماره تلفن همراه (جهت پیامک پیگیری مرسوله) *</label>
                            <input type="tel" name="customer_phone" required dir="ltr" placeholder="09121234567" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-left text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">استان *</label>
                                <input type="text" name="province" required value="تهران" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">شهر *</label>
                                <input type="text" name="city" required value="تهران" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">نشانی دقیق پستی (خیابان، کوچه، پلاک، واحد) *</label>
                            <textarea name="address" required rows="2" placeholder="نشانی پستی دقیق..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">کد پستی ده‌رقمی</label>
                                <input type="text" name="postal_code" maxlength="10" dir="ltr" placeholder="کد پستی ۱۰ رقمی" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-left text-slate-800 focus:border-[#001a48] focus:ring-1 focus:ring-[#001a48] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">روش ارسال</label>
                                <select name="shipping_method" onchange="tenantCart.updateShippingMethod(this.value)" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] outline-none">
                                    <option value="pishtaz">شرکت ملی پست (پیشتاز)</option>
                                    <option value="express">پیک اکسپرس شهری</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">توضیحات و هماهنگی تحویل (اختیاری)</label>
                            <input type="text" name="order_notes" placeholder="یادداشت هماهنگی با مامور ارسال یا ساعات تحویل..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#001a48] outline-none">
                        </div>

                        <!-- Trust Seal -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/90 text-[11px] text-slate-600 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-lg">verified_user</span>
                            <span>پرداخت امن از طریق درگاه مرکزی شاپرک آسنا با ضمانت اصالت کالا</span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Drawer Footer Actions -->
            <div class="p-4 sm:p-5 border-t border-slate-100 bg-white space-y-2">
                <div id="tenant-cart-actions-step1">
                    <button type="button" onclick="tenantCart.goToCheckoutStep()" id="btn-goto-checkout" class="w-full py-3.5 px-4 rounded-2xl bg-[#001a48] hover:bg-slate-900 text-white text-xs font-black shadow-lg flex items-center justify-center gap-2 transition-all active:scale-98">
                        <span>ثبت سفارش و ادامه خرید</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </button>
                </div>
                <div id="tenant-cart-actions-step2" class="hidden flex items-center gap-2">
                    <button type="button" onclick="tenantCart.goToItemsStep()" class="w-1/3 py-3 px-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all text-center">
                        بازگشت
                    </button>
                    <button type="submit" form="tenant-checkout-form" id="btn-submit-order" class="w-2/3 py-3.5 px-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs font-black shadow-lg shadow-emerald-700/20 flex items-center justify-center gap-2 transition-all active:scale-98">
                        <span class="material-symbols-outlined text-sm">lock</span>
                        <span id="btn-submit-order-label">پرداخت امن شاپرک</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Hub Modal -->
    <div id="nav-hub-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">near_me</span>
                    </div>
                    <span class="font-black text-sm text-slate-800">مرکز مسیریابی و ارتباط مستقیم</span>
                </div>
                <button type="button" onclick="closeNavHubModal()" class="p-1 rounded-xl text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Address Display & Copy -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="text-[11px] text-slate-400 font-bold mb-1">نشانی ثبت‌شده:</div>
                <div class="text-xs text-slate-700 leading-relaxed font-medium"><?= htmlspecialchars($contactBlock['address'] ?? 'تهران') ?></div>
                <div class="mt-2.5 flex items-center gap-2">
                    <?php if (!empty($addressMapLink)): ?>
                    <a href="<?= htmlspecialchars($addressMapLink) ?>" target="_blank" rel="noopener" class="flex-1 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-xs">near_me</span>
                        <span>مسیریابی مستقیم</span>
                    </a>
                    <?php endif; ?>
                    <button type="button" onclick="copyAddressToClipboard('<?= addslashes($contactBlock['address'] ?? '') ?>')" class="py-2 px-3 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all flex items-center justify-center gap-1.5 <?= !empty($addressMapLink) ? '' : 'w-full' ?>">
                        <span class="material-symbols-outlined text-xs text-slate-500">content_copy</span>
                        <span>کپی آدرس</span>
                    </button>
                </div>
            </div>

            <!-- Routing Apps Grid -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">انتخاب مسیریاب مورد نظر:</label>
                <div class="grid grid-cols-2 gap-2.5">
                    <?php
                        $neshanFinal = !empty($navHubBlock['neshan_url']) ? $navHubBlock['neshan_url'] : "https://neshan.org/maps/@{$targetLat},{$targetLng},16z";
                        $baladFinal = !empty($navHubBlock['balad_url']) ? $navHubBlock['balad_url'] : "https://balad.ir/location?latitude={$targetLat}&longitude={$targetLng}";
                        $wazeFinal = !empty($navHubBlock['waze_url']) ? $navHubBlock['waze_url'] : "https://waze.com/ul?ll={$targetLat},{$targetLng}&navigate=yes";
                        $googleFinal = !empty($navHubBlock['google_maps_url']) ? $navHubBlock['google_maps_url'] : "https://maps.google.com/?q={$targetLat},{$targetLng}";
                    ?>
                    <a href="<?= htmlspecialchars($neshanFinal) ?>" target="_blank" rel="noopener" class="p-3 rounded-2xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/30 flex items-center gap-2.5 transition-all text-right">
                        <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">ن</span>
                        <div>
                            <div class="text-xs font-black text-slate-800">مسیریاب نشان</div>
                            <div class="text-[10px] text-slate-400">Neshan Maps</div>
                        </div>
                    </a>
                    <a href="<?= htmlspecialchars($baladFinal) ?>" target="_blank" rel="noopener" class="p-3 rounded-2xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/30 flex items-center gap-2.5 transition-all text-right">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs">ب</span>
                        <div>
                            <div class="text-xs font-black text-slate-800">مسیریاب بلد</div>
                            <div class="text-[10px] text-slate-400">Balad Maps</div>
                        </div>
                    </a>
                    <a href="<?= htmlspecialchars($wazeFinal) ?>" target="_blank" rel="noopener" class="p-3 rounded-2xl border border-slate-200 hover:border-cyan-500 hover:bg-cyan-50/30 flex items-center gap-2.5 transition-all text-right">
                        <span class="w-8 h-8 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center font-bold text-xs">W</span>
                        <div>
                            <div class="text-xs font-black text-slate-800">ویز (Waze)</div>
                            <div class="text-[10px] text-slate-400">Live Traffic</div>
                        </div>
                    </a>
                    <a href="<?= htmlspecialchars($googleFinal) ?>" target="_blank" rel="noopener" class="p-3 rounded-2xl border border-slate-200 hover:border-slate-500 hover:bg-slate-50 flex items-center gap-2.5 transition-all text-right">
                        <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs">G</span>
                        <div>
                            <div class="text-xs font-black text-slate-800">گوگل مپ</div>
                            <div class="text-[10px] text-slate-400">Google Maps</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Direct Messaging Links -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-2">پیام‌رسان‌های پشتیبانی:</label>
                <div class="flex items-center justify-center gap-3">
                    <?php if (!empty($contactBlock['phone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/\D/', '', $contactBlock['phone']) ?>" target="_blank" class="p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center gap-1.5 text-xs font-bold transition-colors" title="واتساپ">
                        <span class="material-symbols-outlined text-sm">chat</span>
                        <span>واتساپ</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($contactBlock['telegram'])): ?>
                    <a href="https://t.me/<?= ltrim(htmlspecialchars($contactBlock['telegram']), '@') ?>" target="_blank" class="p-2.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 flex items-center gap-1.5 text-xs font-bold transition-colors" title="تلگرام">
                        <span class="material-symbols-outlined text-sm">send</span>
                        <span>تلگرام</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?= $ctaHref ?>" class="p-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 flex items-center gap-1.5 text-xs font-bold transition-colors" title="گفتگوی آنلاین و نوبت‌دهی">
                        <span class="material-symbols-outlined text-sm">forum</span>
                        <span>مشاوره و گفتگوی آنلاین</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Digital VCard & Direct Sharing Modal -->
    <div id="vcard-modal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-md hidden items-center justify-center p-4 transition-opacity" onclick="if(event.target === this) closeVCardModal();">
        <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-2xl border border-slate-100 text-center relative overflow-hidden max-h-[92vh] overflow-y-auto">
            <!-- Top Gradient Aura -->
            <div class="absolute -top-10 -left-10 w-36 h-36 bg-indigo-500/15 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -right-10 w-36 h-36 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between pb-3 border-b border-slate-100 relative z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">badge</span>
                    </div>
                    <span class="font-black text-sm text-slate-800">کارت ویزیت دیجیتال و QR اختصاصی</span>
                </div>
                <button type="button" onclick="closeVCardModal()" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Identity Card View -->
            <div class="relative z-10 space-y-3">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl overflow-hidden border-2 border-slate-100 shadow-md mb-2 flex items-center justify-center bg-slate-50 relative">
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover">
                        <div class="absolute bottom-0 right-0 w-3.5 h-3.5 <?= $isCurrentlyOpen ? 'bg-emerald-500' : 'bg-amber-500' ?> border-2 border-white rounded-full" title="<?= $isCurrentlyOpen ? 'پذیرش فعال' : 'خارج از شیفت' ?>"></div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-base font-black text-slate-900" id="vcard-modal-title"><?= htmlspecialchars($site['site_title']) ?></h3>
                        <span class="material-symbols-outlined text-emerald-600 text-sm" title="عضو تاییدشده آسنا">verified</span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5" id="vcard-modal-tagline"><?= htmlspecialchars($site['site_tagline'] ?? 'مرکز خدمات تخصصی حیوانات خانگی') ?></p>
                </div>

                <!-- 1-Tap Quick Action Row (Call, WhatsApp, Maps) -->
                <div class="grid grid-cols-3 gap-1.5 pt-1">
                    <?php if (!empty($vcardCleanPhone)): ?>
                    <a href="tel:<?= htmlspecialchars($vcardCleanPhone) ?>" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold flex flex-col items-center justify-center gap-1 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-base text-emerald-600">call</span>
                        <span>تماس فوری</span>
                    </a>
                    <?php $waPhone = $cleanPhoneDigits ?? $vcardCleanPhone; ?>
                    <a href="https://wa.me/<?= htmlspecialchars($waPhone) ?>" target="_blank" class="p-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-[11px] font-bold flex flex-col items-center justify-center gap-1 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-base">chat</span>
                        <span>پیام واتساپ</span>
                    </a>
                    <?php else: ?>
                    <button type="button" onclick="closeVCardModal(); openBookingModal();" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold flex flex-col items-center justify-center gap-1 transition-all shadow-2xs cursor-pointer">
                        <span class="material-symbols-outlined text-base text-emerald-600">calendar_month</span>
                        <span>رزرو نوبت</span>
                    </button>
                    <a href="#about" onclick="closeVCardModal()" class="p-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-800 text-[11px] font-bold flex flex-col items-center justify-center gap-1 transition-all shadow-2xs">
                        <span class="material-symbols-outlined text-base text-indigo-600">info</span>
                        <span>درباره مرکز</span>
                    </a>
                    <?php endif; ?>
                    <button type="button" onclick="closeVCardModal(); openNavHubModal();" class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-800 text-[11px] font-bold flex flex-col items-center justify-center gap-1 transition-all shadow-2xs cursor-pointer">
                        <span class="material-symbols-outlined text-base text-blue-600">near_me</span>
                        <span>مسیریابی</span>
                    </button>
                </div>

                <!-- Social Media & Official Channels Hub -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl text-right space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                        <span class="flex items-center gap-1.5 text-slate-800">
                            <span class="material-symbols-outlined text-sm text-indigo-600">share</span>
                            <span>پل‌های ارتباطی و شبکه‌های اجتماعی:</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-medium">رسانه‌های رسمی</span>
                    </div>

                    <div id="vcard-social-links-list" class="space-y-1.5 max-h-48 overflow-y-auto">
                        <?php 
                        $hasActiveSocial = false;
                        foreach ($socialLinks as $slink): 
                            if (empty($slink['enabled']) || (empty($slink['url']) && empty($slink['handle']))) continue;
                            $hasActiveSocial = true;
                            $pIcon = $slink['icon'] ?? 'link';
                            $pTitle = $slink['title'] ?? 'شبکه اجتماعی';
                            $pHandle = $slink['handle'] ?? $slink['url'] ?? '';
                            $pUrl = $slink['url'] ?? '#';
                            $pColor = $slink['color'] ?? '#4f46e5';
                        ?>
                        <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-200/80 shadow-2xs hover:border-indigo-200 transition-all text-right group">
                            <div class="flex items-center gap-2 min-w-0 flex-1 pl-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0 shadow-2xs" style="background-color: <?= htmlspecialchars($pColor) ?>;">
                                    <span class="material-symbols-outlined text-base"><?= htmlspecialchars($pIcon) ?></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-[11px] font-black text-slate-800 truncate"><?= htmlspecialchars($pTitle) ?></div>
                                    <div class="text-[10px] text-slate-500 font-mono truncate" dir="ltr"><?= htmlspecialchars($pHandle) ?></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <?php if (!empty($pHandle)): ?>
                                <button type="button" onclick="copySocialHandle('<?= htmlspecialchars(addslashes($pHandle)) ?>')" title="کپی آیدی یا شماره" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span class="hidden sm:inline">کپی</span>
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($pUrl) && $pUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($pUrl) ?>" target="_blank" title="مشاهده و ورود مستقیم" class="px-2 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors">
                                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    <span>ورود</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (!$hasActiveSocial): ?>
                        <div class="text-[11px] text-slate-400 p-2.5 bg-white rounded-xl text-center border border-dashed border-slate-200">
                            پل ارتباطی فعالی ثبت نشده است. از استودیو می‌توانید شبکه‌های اجتماعی خود را اضافه فرمایید.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dual-Mode QR Code Tabs -->
                <div class="p-1 bg-slate-100 rounded-xl grid grid-cols-2 gap-1 text-xs font-bold">
                    <button type="button" id="tab-qr-vcard" onclick="switchQrTab('vcard')" class="py-1.5 px-2 rounded-lg bg-white text-indigo-700 shadow-xs transition-all flex items-center justify-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        <span>مخاطب (vCard)</span>
                    </button>
                    <button type="button" id="tab-qr-website" onclick="switchQrTab('website')" class="py-1.5 px-2 rounded-lg text-slate-500 hover:text-slate-800 transition-all flex items-center justify-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">language</span>
                        <span>وب‌سایت</span>
                    </button>
                </div>

                <!-- QR Display Container -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl flex flex-col items-center justify-center space-y-2">
                    <!-- Real Scannable SVG for vCard QR -->
                    <div id="qr-vcard-container" class="w-44 h-44 bg-white p-2 rounded-xl shadow-inner border border-slate-200 flex items-center justify-center relative">
                        <div class="w-full h-full flex items-center justify-center">
                            <?= $qrVcardSvg ?>
                        </div>
                    </div>

                    <!-- Real Scannable SVG for Website URL QR -->
                    <div id="qr-website-container" class="w-44 h-44 bg-white p-2 rounded-xl shadow-inner border border-slate-200 hidden items-center justify-center relative">
                        <div class="w-full h-full flex items-center justify-center">
                            <?= $qrWebsiteSvg ?>
                        </div>
                    </div>

                    <span id="qr-tab-description" class="text-[10px] text-slate-500 font-bold leading-tight">
                        اسکن بارکد با دوربین گوشی جهت ثبت خودکار در دفترچه تلفن (iOS و Android)
                    </span>
                </div>

                <!-- 1-Tap Save VCF Action -->
                <button type="button" onclick="downloadVCard()" class="w-full py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 transition-transform active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-base">person_add</span>
                    <span>افزودن به مخاطبین گوشی (دانلود مستقیم VCF)</span>
                </button>

                <!-- Auxiliary Tools: Download QR Image & Print Stand -->
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="downloadQrImage()" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold flex items-center justify-center gap-1 transition-colors cursor-pointer" title="دانلود تصویر وکتور بارکد">
                        <span class="material-symbols-outlined text-xs">download</span>
                        <span>دانلود بارکد (SVG)</span>
                    </button>
                    <button type="button" onclick="printVCardStand()" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold flex items-center justify-center gap-1 transition-colors cursor-pointer" title="چاپ استند رومیزی برای پذیرش">
                        <span class="material-symbols-outlined text-xs">print</span>
                        <span>چاپ استند پذیرش</span>
                    </button>
                </div>

                <!-- Social & Native Share Grid -->
                <div class="pt-2 border-t border-slate-100">
                    <button type="button" onclick="nativeShare()" class="w-full mb-2 py-2.5 px-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-black flex items-center justify-center gap-1.5 shadow-sm hover:opacity-95 transition-opacity cursor-pointer">
                        <span class="material-symbols-outlined text-sm">share</span>
                        <span>اشتراک‌گذاری هوشمند در موبایل</span>
                    </button>
                    <div class="text-[11px] text-slate-400 font-bold mb-2">اشتراک‌گذاری در پیام‌رسان‌ها:</div>
                    <div class="grid grid-cols-5 gap-1.5">
                        <button type="button" onclick="shareSiteUrl('whatsapp')" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold transition-colors cursor-pointer" title="واتساپ">
                            <span>واتساپ</span>
                        </button>
                        <button type="button" onclick="shareSiteUrl('telegram')" class="p-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold transition-colors cursor-pointer" title="تلگرام">
                            <span>تلگرام</span>
                        </button>
                        <button type="button" onclick="shareSiteUrl('eitaa')" class="p-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 text-[11px] font-bold transition-colors cursor-pointer" title="ایتا">
                            <span>ایتا</span>
                        </button>
                        <button type="button" onclick="shareSiteUrl('bale')" class="p-2 rounded-xl bg-cyan-50 hover:bg-cyan-100 text-cyan-700 text-[11px] font-bold transition-colors cursor-pointer" title="بله">
                            <span>بله</span>
                        </button>
                        <button type="button" onclick="shareSiteUrl('sms')" class="p-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 text-[11px] font-bold transition-colors cursor-pointer" title="پیامک">
                            <span>پیامک</span>
                        </button>
                    </div>
                    <button type="button" onclick="shareSiteUrl('copy')" class="mt-2.5 w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-xs">content_copy</span>
                        <span>کپی لینک اختصاصی وب‌سایت</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Accreditation & Trust Verification Modal -->
    <div id="trust-verify-modal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-md hidden items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-slate-100 text-right relative overflow-hidden">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">verified_user</span>
                    </div>
                    <span class="font-black text-sm text-slate-800">اعتبار بالینی و ضمانت رسمی آسنا</span>
                </div>
                <button type="button" onclick="closeTrustVerifyModal()" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl shrink-0 mt-0.5">verified</span>
                    <div>
                        <div class="text-xs font-black text-emerald-950 mb-0.5">عضو تاییدشده شبکه سلامت حیوانات خانگی آسنا</div>
                        <p class="text-[11px] text-emerald-800 leading-relaxed font-medium">
                            صلاحیت بالینی، مجوزهای دامپزشکی و شرایط پذیرش این مجموعه به صورت دوره‌ای توسط کارشناسان نظارتی آسنا راستی‌آزمایی می‌گردد.
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs text-slate-700">
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">شماره نظام دامپزشکی / مجوز:</span>
                        <span class="font-mono font-bold text-slate-900 bg-white px-2 py-0.5 rounded-lg border border-slate-200" id="live-modal-license">
                            <?= htmlspecialchars(!empty($aboutBlock['vet_council']) ? $aboutBlock['vet_council'] : 'IR-VET-98214') ?>
                        </span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">امنیت پرداخت الکترونیک:</span>
                        <span class="font-bold text-emerald-700 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">shield</span>
                            <span>شاپرک و حساب امانی آسنا</span>
                        </span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">حریم خصوصی و پرونده سلامت EMR:</span>
                        <span class="font-bold text-blue-700 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">lock</span>
                            <span>رمزنگاری ابری ۲۵۶ بیتی</span>
                        </span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">پشتیبانی و رسیدگی به شکایات:</span>
                        <span class="font-bold text-slate-800">مرکز تماس ۲۴/۷ آسنا (۹۱۰۰۰۰۰۰-۰۲۱)</span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="https://asena.company" target="_blank" class="w-full py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black shadow-md flex items-center justify-center gap-2 transition-all">
                        <span>ورود به سامانه جامع سلامت آسنا (asena.company)</span>
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>
                </div>
            </div>
    </div>

    <!-- Printable Reception Countertop Stand / Digital Business Card Plaque (Visible only on @media print) -->
    <div id="vcard-printable-stand" class="hidden bg-white text-slate-900 rounded-3xl p-8 border-4 border-[#001a48] text-center max-w-md mx-auto shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b-2 border-slate-200 mb-6" dir="rtl">
            <div class="flex items-center gap-2">
                <span class="font-black text-[11px] text-slate-500 uppercase tracking-widest">ASENA CLOUD NETWORK</span>
            </div>
            <div class="flex items-center gap-1 text-emerald-700 font-black text-xs">
                <span>عضو رسمی فناوری سلامت آسنا</span>
            </div>
        </div>
        
        <div class="w-20 h-20 mx-auto rounded-2xl overflow-hidden border-2 border-slate-200 shadow mb-3">
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($site['site_title']) ?>" class="w-full h-full object-cover">
        </div>
        <h2 class="text-2xl font-black text-[#001a48] mb-1"><?= htmlspecialchars($site['site_title']) ?></h2>
        <p class="text-xs text-slate-600 font-bold mb-6"><?= htmlspecialchars($site['site_tagline'] ?? '') ?></p>

        <div class="w-56 h-56 mx-auto p-3 bg-white rounded-2xl border-2 border-slate-300 shadow-inner flex items-center justify-center mb-4">
            <?= $qrWebsiteSvg ?>
        </div>

        <p class="text-sm font-black text-slate-800 mb-4" dir="rtl">
            جهت رزرو نوبت آنلاین، استعلام و مشاهده خدمات<br>
            <span class="text-xs font-normal text-slate-500">دوربین گوشی هوشمند خود را روبه‌روی بارکد بالا بگیرید</span>
        </p>

        <div class="pt-4 border-t-2 border-slate-200 space-y-1.5 text-xs text-slate-700 font-medium" dir="rtl">
            <?php if (!empty($vcardPhone)): ?>
            <p><strong>تلفن پذیرش:</strong> <?= htmlspecialchars($vcardPhone) ?></p>
            <?php endif; ?>
            <?php if (!empty($vcardAddress)): ?>
            <p><strong>نشانی:</strong> <?= htmlspecialchars($vcardAddress) ?></p>
            <?php endif; ?>
        </div>

        <!-- Social Media Handles on Reception Stand -->
        <div id="stand-social-links-list" class="mt-3 pt-3 border-t border-slate-200 flex flex-wrap items-center justify-center gap-2 text-[10px] text-slate-700 font-bold" dir="ltr">
            <?php foreach (array_slice($socialLinks, 0, 3) as $slink): 
                if (empty($slink['enabled']) || empty($slink['handle'])) continue;
            ?>
            <span class="bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200"><?= htmlspecialchars($slink['title']) ?>: <?= htmlspecialchars($slink['handle']) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-center gap-2 text-[10px] text-slate-400">
            <span>درگاه امن شاپرک</span>
            <span>•</span>
            <span>پرونده ابری سلامت آسنا</span>
        </div>
    </div>

    <!-- Client-Side Reactive Scripts -->
    <script>
        // Native Tenant Cart & Quick Checkout Engine (ASENA Connected)
        const tenantCart = {
            slug: '<?= addslashes($site['slug']) ?>',
            siteTitle: '<?= addslashes($site['site_title']) ?>',
            shippingMethod: 'pishtaz',
            
            getItems() {
                try {
                    return JSON.parse(localStorage.getItem('asena_cart_' + this.slug)) || [];
                } catch(e) {
                    return [];
                }
            },
            
            saveItems(items) {
                try {
                    localStorage.setItem('asena_cart_' + this.slug, JSON.stringify(items));
                } catch(e) {}
                this.render();
            },
            
            addItem(btnOrData, buyNow = false) {
                let item = null;
                if (btnOrData instanceof HTMLElement) {
                    const card = btnOrData.closest('[data-product-id]');
                    if (card) {
                        item = {
                            id: parseInt(card.dataset.productId),
                            source: card.dataset.productSource || 'product',
                            name: card.dataset.productName,
                            price: parseInt(card.dataset.productPrice),
                            image: card.dataset.productImage,
                            stock: parseInt(card.dataset.productStock) || 10,
                            qty: 1
                        };
                    }
                } else if (btnOrData && btnOrData.id) {
                    item = btnOrData;
                }
                
                if (!item || !item.id) return;
                
                let items = this.getItems();
                const existingIdx = items.findIndex(i => i.id === item.id && i.source === item.source);
                
                if (existingIdx > -1) {
                    if (items[existingIdx].qty < item.stock) {
                        items[existingIdx].qty += 1;
                    } else {
                        this.toast('حداکثر موجودی این کالا در سبد شما قرار دارد.', 'warning');
                        if (buyNow) this.openDrawer(true);
                        return;
                    }
                } else {
                    items.push({
                        id: item.id,
                        source: item.source,
                        name: item.name,
                        price: item.price,
                        image: item.image,
                        stock: item.stock,
                        qty: 1
                    });
                }
                
                this.saveItems(items);
                this.toast(`کالای «${item.name}» به سبد خرید اضافه شد.`, 'success');
                
                if (buyNow) {
                    this.openDrawer(true);
                }
            },
            
            updateQty(id, source, delta) {
                let items = this.getItems();
                const idx = items.findIndex(i => i.id === id && i.source === source);
                if (idx > -1) {
                    const newQty = items[idx].qty + delta;
                    if (newQty <= 0) {
                        items.splice(idx, 1);
                        this.toast('کالا از سبد خرید حذف شد.', 'info');
                    } else if (newQty > items[idx].stock) {
                        this.toast('تعداد درخواستی بیشتر از موجودی انبار است.', 'warning');
                        return;
                    } else {
                        items[idx].qty = newQty;
                    }
                    this.saveItems(items);
                }
            },
            
            removeItem(id, source) {
                let items = this.getItems().filter(i => !(i.id === id && i.source === source));
                this.saveItems(items);
                this.toast('کالا از سبد خرید حذف شد.', 'info');
            },
            
            clear() {
                try {
                    localStorage.removeItem('asena_cart_' + this.slug);
                } catch(e) {}
                this.render();
            },
            
            updateShippingMethod(method) {
                this.shippingMethod = method;
                this.render();
            },
            
            getTotals() {
                const items = this.getItems();
                const subtotal = items.reduce((sum, item) => sum + (item.price * item.qty), 0);
                let shipping = 0;
                if (subtotal > 0) {
                    shipping = (subtotal >= 500000) ? 0 : (this.shippingMethod === 'express' ? 65000 : 45000);
                }
                const vat = Math.round(subtotal * 0.10);
                const total = subtotal + shipping + vat;
                return { subtotal, shipping, vat, total, count: items.reduce((c, i) => c + i.qty, 0) };
            },
            
            openDrawer(goToCheckout = false) {
                const drawer = document.getElementById('tenant-cart-drawer');
                const panel = document.getElementById('tenant-cart-panel');
                if (!drawer || !panel) return;
                
                this.render();
                if (goToCheckout && this.getItems().length > 0) {
                    this.goToCheckoutStep();
                } else {
                    this.goToItemsStep();
                }
                
                drawer.classList.remove('pointer-events-none', 'opacity-0');
                drawer.classList.add('opacity-100');
                panel.classList.remove('-translate-x-full');
            },
            
            closeDrawer() {
                const drawer = document.getElementById('tenant-cart-drawer');
                const panel = document.getElementById('tenant-cart-panel');
                if (!drawer || !panel) return;
                
                panel.classList.add('-translate-x-full');
                drawer.classList.remove('opacity-100');
                drawer.classList.add('opacity-0');
                setTimeout(() => {
                    drawer.classList.add('pointer-events-none');
                }, 300);
            },
            
            goToCheckoutStep() {
                if (this.getItems().length === 0) {
                    this.toast('سبد خرید شما خالی است.', 'warning');
                    return;
                }
                document.getElementById('tenant-cart-step-items')?.classList.add('hidden');
                document.getElementById('tenant-cart-actions-step1')?.classList.add('hidden');
                document.getElementById('tenant-cart-step-checkout')?.classList.remove('hidden');
                document.getElementById('tenant-cart-actions-step2')?.classList.remove('hidden');
            },
            
            goToItemsStep() {
                document.getElementById('tenant-cart-step-checkout')?.classList.add('hidden');
                document.getElementById('tenant-cart-actions-step2')?.classList.add('hidden');
                document.getElementById('tenant-cart-step-items')?.classList.remove('hidden');
                document.getElementById('tenant-cart-actions-step1')?.classList.remove('hidden');
            },
            
            formatPrice(num) {
                return new Intl.NumberFormat('fa-IR').format(num);
            },
            
            render() {
                const items = this.getItems();
                const totals = this.getTotals();
                
                // Update Badges & Counters
                const trigger = document.getElementById('tenant-cart-trigger');
                const badge = document.getElementById('tenant-cart-badge');
                const btnPrice = document.getElementById('tenant-cart-btn-price');
                const navBadge = document.getElementById('tenant-nav-cart-badge');
                const mobileBadge = document.getElementById('tenant-mobile-cart-badge');
                const sfBadge = document.getElementById('tenant-storefront-cart-count');
                
                if (trigger) {
                    if (totals.count > 0) {
                        trigger.classList.remove('scale-0');
                        trigger.classList.add('scale-100');
                    } else {
                        trigger.classList.remove('scale-100');
                        trigger.classList.add('scale-0');
                    }
                }
                if (badge) badge.innerText = totals.count;
                if (btnPrice) btnPrice.innerText = this.formatPrice(totals.total);
                if (navBadge) {
                    navBadge.innerText = totals.count;
                    navBadge.classList.toggle('hidden', totals.count === 0);
                }
                if (mobileBadge) {
                    mobileBadge.innerText = totals.count;
                    mobileBadge.classList.toggle('hidden', totals.count === 0);
                }
                if (sfBadge) {
                    sfBadge.innerText = totals.count;
                    sfBadge.classList.toggle('hidden', totals.count === 0);
                }
                
                // Render in Drawer
                const container = document.getElementById('tenant-cart-items-container');
                const emptyNotice = document.getElementById('tenant-cart-empty');
                const costSummary = document.getElementById('tenant-cart-cost-summary');
                const btnGotoCheckout = document.getElementById('btn-goto-checkout');
                
                if (!container) return;
                
                if (items.length === 0) {
                    container.innerHTML = '';
                    emptyNotice?.classList.remove('hidden');
                    costSummary?.classList.add('hidden');
                    if (btnGotoCheckout) btnGotoCheckout.disabled = true;
                    return;
                }
                
                emptyNotice?.classList.add('hidden');
                costSummary?.classList.remove('hidden');
                if (btnGotoCheckout) btnGotoCheckout.disabled = false;
                
                let html = '';
                items.forEach(it => {
                    html += `
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all">
                        <img src="${it.image}" alt="${it.name}" class="w-12 h-12 rounded-xl object-cover bg-slate-50 border border-slate-100 shrink-0">
                        <div class="flex-1 min-w-0">
                            <h5 class="text-xs font-bold text-slate-900 truncate mb-1">${it.name}</h5>
                            <div class="text-[11px] font-mono text-emerald-700 font-bold">${this.formatPrice(it.price)} تومان</div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 bg-slate-50 border border-slate-200 rounded-xl p-1">
                            <button type="button" onclick="tenantCart.updateQty(${it.id}, '${it.source}', -1)" class="w-6 h-6 rounded-lg bg-white hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shadow-2xs active:scale-95 transition-transform">-</button>
                            <span class="w-5 text-center text-xs font-mono font-black text-slate-900">${it.qty}</span>
                            <button type="button" onclick="tenantCart.updateQty(${it.id}, '${it.source}', 1)" class="w-6 h-6 rounded-lg bg-white hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shadow-2xs active:scale-95 transition-transform">+</button>
                        </div>
                        <button type="button" onclick="tenantCart.removeItem(${it.id}, '${it.source}')" class="text-slate-400 hover:text-rose-600 p-1 transition-colors" title="حذف کالا">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>`;
                });
                container.innerHTML = html;
                
                // Update Summary Fields
                const elSubtotal = document.getElementById('cart-summary-subtotal');
                const elShipping = document.getElementById('cart-summary-shipping');
                const elVat = document.getElementById('cart-summary-vat');
                const elTotal = document.getElementById('cart-summary-total');
                const elBtnOrderLabel = document.getElementById('btn-submit-order-label');
                
                if (elSubtotal) elSubtotal.innerText = this.formatPrice(totals.subtotal) + ' تومان';
                if (elShipping) elShipping.innerText = totals.shipping === 0 ? 'رایگان (سفارش بالای ۵۰۰ هزار)' : this.formatPrice(totals.shipping) + ' تومان';
                if (elVat) elVat.innerText = this.formatPrice(totals.vat) + ' تومان';
                if (elTotal) elTotal.innerText = this.formatPrice(totals.total) + ' تومان';
                if (elBtnOrderLabel) elBtnOrderLabel.innerText = `پرداخت امن (${this.formatPrice(totals.total)} تومان)`;
            },
            
            async submitOrder(e) {
                e.preventDefault();
                const form = e.target;
                const btn = document.getElementById('btn-submit-order');
                const items = this.getItems();
                
                if (items.length === 0) {
                    this.toast('سبد خرید شما خالی است.', 'warning');
                    return;
                }
                
                const formData = new FormData(form);
                const payload = {
                    slug: this.slug,
                    csrf_token: formData.get('csrf_token'),
                    customer_name: formData.get('customer_name'),
                    customer_phone: formData.get('customer_phone'),
                    province: formData.get('province'),
                    city: formData.get('city'),
                    address: formData.get('address'),
                    postal_code: formData.get('postal_code'),
                    shipping_method: formData.get('shipping_method'),
                    order_notes: formData.get('order_notes'),
                    items: items.map(i => ({ id: i.id, source: i.source, qty: i.qty }))
                };
                
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('opacity-70', 'cursor-not-allowed');
                    btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span><span>در حال اتصال به درگاه شاپرک...</span>';
                }
                
                try {
                    const resp = await fetch('actions/tenant_order_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await resp.json();
                    
                    if (data.success && data.redirect_url) {
                        this.clear();
                        window.location.href = data.redirect_url;
                    } else {
                        this.toast(data.message || 'خطا در ثبت سفارش.', 'error');
                        if (btn) {
                            btn.disabled = false;
                            btn.classList.remove('opacity-70', 'cursor-not-allowed');
                            btn.innerHTML = '<span class="material-symbols-outlined text-sm">lock</span><span>تلاش مجدد پرداخت</span>';
                        }
                    }
                } catch (err) {
                    this.toast('خطای اتصال به سرور: ' + err.message, 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('opacity-70', 'cursor-not-allowed');
                        btn.innerHTML = '<span class="material-symbols-outlined text-sm">lock</span><span>تلاش مجدد</span>';
                    }
                }
            },
            
            toast(msg, type = 'info') {
                const old = document.getElementById('tenant-toast');
                if (old) old.remove();
                
                const colors = {
                    success: 'bg-emerald-900 text-white border-emerald-600',
                    warning: 'bg-amber-900 text-white border-amber-600',
                    error: 'bg-rose-900 text-white border-rose-600',
                    info: 'bg-slate-900 text-white border-slate-700'
                };
                const icons = {
                    success: 'check_circle',
                    warning: 'warning',
                    error: 'error',
                    info: 'info'
                };
                
                const toast = document.createElement('div');
                toast.id = 'tenant-toast';
                toast.className = `fixed top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-3 rounded-2xl border shadow-2xl flex items-center gap-2.5 text-xs font-bold backdrop-blur-md transition-all duration-300 transform scale-95 opacity-0 ${colors[type] || colors.info}`;
                toast.innerHTML = `<span class="material-symbols-outlined text-base">${icons[type] || 'info'}</span><span>${msg}</span>`;
                document.body.appendChild(toast);
                
                requestAnimationFrame(() => {
                    toast.classList.remove('scale-95', 'opacity-0');
                    toast.classList.add('scale-100', 'opacity-100');
                });
                
                setTimeout(() => {
                    toast.classList.remove('scale-100', 'opacity-100');
                    toast.classList.add('scale-95', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            }
        };

        // Initialize tenant cart on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            tenantCart.render();
        });

        // Interactive Cost Calculator State
        let currentPetMultiplier = 1.0;
        let currentPetTitle = 'سگ';
        let currentServiceBase = 250000;
        let currentServiceTitle = 'ویزیت و چکاپ کامل بالینی';
        const discountPercent = <?= (int)($calculatorBlock['discount_percent'] ?? 10) ?>;

        function selectCalcPet(id, mult, title) {
            currentPetMultiplier = mult;
            currentPetTitle = title;

            document.querySelectorAll('.calc-pet-btn').forEach(btn => {
                btn.className = 'calc-pet-btn p-3 rounded-2xl border-2 text-center transition-all flex flex-col items-center justify-center gap-1.5 border-slate-200 hover:border-slate-300 text-slate-700 font-bold bg-white';
            });
            const selBtn = document.getElementById('calc-pet-btn-' + id);
            if (selBtn) {
                selBtn.className = 'calc-pet-btn p-3 rounded-2xl border-2 text-center transition-all flex flex-col items-center justify-center gap-1.5 border-emerald-600 bg-emerald-50 text-emerald-800 font-black shadow-sm';
            }
            const petTitleEl = document.getElementById('calc-pet-selected-title');
            if (petTitleEl) petTitleEl.innerText = title;
            updateCalcDisplay();
        }

        function selectCalcService(id, base, title) {
            currentServiceBase = base;
            currentServiceTitle = title;

            document.querySelectorAll('.calc-srv-card').forEach(card => {
                card.className = 'calc-srv-card p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3 border-slate-200 hover:border-slate-300 text-slate-700 bg-white';
            });
            const selCard = document.getElementById('calc-srv-card-' + id);
            if (selCard) {
                selCard.className = 'calc-srv-card p-3.5 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3 border-emerald-600 bg-emerald-50 text-emerald-800 shadow-sm';
            }
            const srvTitleEl = document.getElementById('calc-srv-selected-title');
            if (srvTitleEl) srvTitleEl.innerText = title;
            updateCalcDisplay();
        }

        function updateCalcDisplay() {
            const rawPrice = Math.round(currentServiceBase * currentPetMultiplier);
            const discountAmt = Math.round(rawPrice * (discountPercent / 100));
            const finalPrice = rawPrice - discountAmt;

            const dispPet = document.getElementById('calc-display-pet');
            const dispSrv = document.getElementById('calc-display-service');
            const dispBase = document.getElementById('calc-display-base-price');
            const dispDisc = document.getElementById('calc-display-discount');
            const dispFinal = document.getElementById('calc-display-final-price');
            const btnCta = document.getElementById('calc-booking-cta-btn');

            if (dispPet) dispPet.innerText = currentPetTitle;
            if (dispSrv) dispSrv.innerText = currentServiceTitle;
            if (dispBase) dispBase.innerText = rawPrice.toLocaleString('fa-IR') + ' تومان';
            if (dispDisc) dispDisc.innerText = '-' + discountAmt.toLocaleString('fa-IR') + ' تومان';
            if (dispFinal) dispFinal.innerText = finalPrice.toLocaleString('fa-IR');

            if (btnCta) {
                const baseHref = '<?= $ctaHref ?>';
                const sep = baseHref.includes('?') ? '&' : '?';
                btnCta.href = `${baseHref}${sep}service=${encodeURIComponent(currentServiceTitle)}&pet=${encodeURIComponent(currentPetTitle)}`;
            }
        }

        // FAQ Accordion Toggle
        function toggleSiteFaq(idx) {
            const ans = document.getElementById('faq-answer-' + idx);
            const chevron = document.getElementById('faq-chevron-' + idx);
            if (!ans) return;

            if (ans.classList.contains('hidden')) {
                ans.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            } else {
                ans.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        }

        // Navigation Hub Modal
        function openNavHubModal() {
            const modal = document.getElementById('nav-hub-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeNavHubModal() {
            const modal = document.getElementById('nav-hub-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // Copy Address with Toast
        function copyAddressToClipboard(text) {
            if (!navigator.clipboard) {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            } else {
                navigator.clipboard.writeText(text);
            }
            showSiteToast('✓ نشانی با موفقیت در کلیپ‌بورد کپی شد.');
        }

        function showSiteToast(msg) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-20 md:bottom-8 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-2xl text-xs font-bold text-white bg-slate-900/90 shadow-2xl backdrop-blur-md flex items-center gap-2 border border-white/20 transition-all';
            toast.innerHTML = `<span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span><span>${msg}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

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

        // Digital VCard & Direct Sharing Modal Functions
        let currentQrTab = 'vcard';

        function openVCardModal() {
            const modal = document.getElementById('vcard-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeVCardModal() {
            const modal = document.getElementById('vcard-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function switchQrTab(type) {
            currentQrTab = type;
            const vcardContainer = document.getElementById('qr-vcard-container');
            const websiteContainer = document.getElementById('qr-website-container');
            const tabVcard = document.getElementById('tab-qr-vcard');
            const tabWebsite = document.getElementById('tab-qr-website');
            const desc = document.getElementById('qr-tab-description');

            if (type === 'vcard') {
                if (vcardContainer) { vcardContainer.classList.remove('hidden'); vcardContainer.classList.add('flex'); }
                if (websiteContainer) { websiteContainer.classList.add('hidden'); websiteContainer.classList.remove('flex'); }
                if (tabVcard) {
                    tabVcard.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
                    tabVcard.classList.remove('text-slate-500');
                }
                if (tabWebsite) {
                    tabWebsite.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
                    tabWebsite.classList.add('text-slate-500');
                }
                if (desc) desc.textContent = 'اسکن بارکد با دوربین گوشی جهت ذخیره خودکار در دفترچه تلفن (iOS و Android)';
            } else {
                if (vcardContainer) { vcardContainer.classList.add('hidden'); vcardContainer.classList.remove('flex'); }
                if (websiteContainer) { websiteContainer.classList.remove('hidden'); websiteContainer.classList.add('flex'); }
                if (tabWebsite) {
                    tabWebsite.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
                    tabWebsite.classList.remove('text-slate-500');
                }
                if (tabVcard) {
                    tabVcard.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
                    tabVcard.classList.add('text-slate-500');
                }
                if (desc) desc.textContent = 'اسکن بارکد با دوربین گوشی جهت ورود به وب‌سایت و رزرو آنلاین';
            }
        }

        function copySocialHandle(handle) {
            if (!handle) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(handle).then(() => {
                    showSiteToast(`✓ شناسه «${handle}» در حافظه کپی شد.`);
                }).catch(() => fallbackCopy(handle));
            } else {
                fallbackCopy(handle);
            }
            function fallbackCopy(text) {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                showSiteToast(`✓ شناسه «${text}» در حافظه کپی شد.`);
            }
        }

        function downloadVCard() {
            const slug = <?= json_encode($slug) ?>;
            const serverUrl = window.location.href.split('?')[0] + '?slug=' + encodeURIComponent(slug) + '&download_vcard=1';
            
            // Generate standard RFC 2426 vCard with UTF-8 BOM
            try {
                const name = <?= json_encode($site['site_title'], JSON_UNESCAPED_UNICODE) ?>;
                const phone = <?= json_encode($vcardCleanPhone, JSON_UNESCAPED_UNICODE) ?>;
                const address = <?= json_encode($vcardAddress, JSON_UNESCAPED_UNICODE) ?>;
                const tagline = <?= json_encode($vcardTagline, JSON_UNESCAPED_UNICODE) ?>;
                const url = window.location.href.split('?')[0] + '?slug=' + encodeURIComponent(slug);
                const socials = <?= json_encode($socialLinks, JSON_UNESCAPED_UNICODE) ?>;
                
                let vcf = `\uFEFFBEGIN:VCARD\r\nVERSION:3.0\r\nFN;CHARSET=UTF-8:${name}\r\nORG;CHARSET=UTF-8:${name}\r\nTITLE;CHARSET=UTF-8:${tagline}\r\nTEL;TYPE=WORK,VOICE:${phone}\r\nADR;TYPE=WORK;CHARSET=UTF-8:;;${address};;;;\r\nURL:${url}\r\nNOTE;CHARSET=UTF-8:عضو رسمی شبکه فناوری و سلامت آسنا\r\n`;
                if (Array.isArray(socials)) {
                    socials.forEach(s => {
                        if (s.enabled !== false && s.url) {
                            const p = (s.platform || 'social').toLowerCase();
                            vcf += `X-SOCIALPROFILE;type=${p}:${s.url}\r\n`;
                        }
                    });
                }
                vcf += `END:VCARD`;

                const blob = new Blob([vcf], { type: 'text/vcard;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${name}.vcf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                showSiteToast('✓ فایل مخاطب (.vcf) کلینیک همراه با شبکه‌های اجتماعی دانلود شد.');
            } catch (e) {
                // Fallback to server endpoint
                window.location.href = serverUrl;
            }
        }

        function downloadQrImage() {
            const slug = <?= json_encode($slug) ?>;
            const type = (currentQrTab === 'vcard') ? 'vcard' : 'website';
            const serverUrl = window.location.href.split('?')[0] + '?slug=' + encodeURIComponent(slug) + '&download_qr=' + type;
            window.location.href = serverUrl;
            showSiteToast('✓ فایل وکتور بارکد (SVG) دانلود شد.');
        }

        function printVCardStand() {
            window.print();
        }

        async function nativeShare() {
            const slug = <?= json_encode($slug) ?>;
            const title = <?= json_encode($site['site_title'], JSON_UNESCAPED_UNICODE) ?>;
            const url = window.location.href.split('?')[0] + '?slug=' + encodeURIComponent(slug);
            const text = `کارت ویزیت دیجیتال و نوبت‌دهی آنلاین ${title}`;

            if (navigator.share) {
                try {
                    await navigator.share({ title: title, text: text, url: url });
                    showSiteToast('✓ با موفقیت اشتراک‌گذاری شد.');
                    return;
                } catch(err) {
                    if (err.name !== 'AbortError') {
                        copyAddressToClipboard(url);
                    }
                }
            } else {
                copyAddressToClipboard(url);
            }
        }

        function shareSiteUrl(platform) {
            const slug = <?= json_encode($slug) ?>;
            const url = window.location.href.split('?')[0] + '?slug=' + encodeURIComponent(slug);
            const title = <?= json_encode($site['site_title'], JSON_UNESCAPED_UNICODE) ?>;
            const text = `وب‌سایت و نوبت‌دهی آنلاین ${title}:\n${url}`;
            
            if (platform === 'whatsapp') {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank');
            } else if (platform === 'telegram') {
                window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
            } else if (platform === 'eitaa') {
                window.open(`https://eitaa.com/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
            } else if (platform === 'bale') {
                window.open(`https://ble.ir/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
            } else if (platform === 'sms') {
                window.open(`sms:?body=${encodeURIComponent(text)}`, '_self');
            } else {
                copyAddressToClipboard(url);
            }
        }

        // Official Accreditation & Trust Verification Modal Functions
        function openTrustVerifyModal() {
            const modal = document.getElementById('trust-verify-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeTrustVerifyModal() {
            const modal = document.getElementById('trust-verify-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        <?php if ($isPreview): ?>
        // PostMessage communication with parent Customizer studio
        document.addEventListener('click', function(e) {
            // Ignore if clicked on an editable text or floating block action button
            if (e.target.closest('[data-studio-editable]') || e.target.closest('.studio-block-floating-bar')) {
                return;
            }
            const blockEl = e.target.closest('[data-block-id]');
            if (blockEl) {
                const blockId = blockEl.getAttribute('data-block-id');
                window.parent.postMessage({ type: 'BLOCK_CLICKED', blockId: blockId }, '*');
            }
        });

        // WYSIWYG In-Place Text Editing System
        function initPreviewDirectEditing() {
            document.querySelectorAll('[data-studio-editable]').forEach(el => {
                el.setAttribute('title', 'برای ویرایش مستقیم کلیک کنید');
                el.addEventListener('click', function(e) {
                    // Prevent link navigation while editing
                    if (this.tagName === 'A' || this.closest('a')) {
                        e.preventDefault();
                    }
                    e.stopPropagation();

                    if (this.getAttribute('contenteditable') === 'true') return;

                    this.setAttribute('contenteditable', 'true');
                    this.focus();

                    const fieldKey = this.getAttribute('data-studio-editable');
                    showPreviewFeedback('✏️ در حال ویرایش مستقیم... (برای ثبت خارج کلیک کنید)');

                    const onInput = () => {
                        const text = this.innerText.trim();
                        window.parent.postMessage({
                            type: 'FIELD_UPDATED_FROM_PREVIEW',
                            field: fieldKey,
                            value: text
                        }, '*');
                    };

                    const onBlur = () => {
                        this.removeAttribute('contenteditable');
                        this.removeEventListener('input', onInput);
                        this.removeEventListener('blur', onBlur);
                        showPreviewFeedback('✓ تغییر در پیش‌نویس ذخیره شد');
                    };

                    this.addEventListener('input', onInput);
                    this.addEventListener('blur', onBlur);
                });

                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && this.tagName !== 'P' && this.id !== 'live-about-text' && this.id !== 'live-calc-subtitle' && this.id !== 'live-hero-subtitle') {
                        e.preventDefault();
                        this.blur();
                    }
                });
            });

            // Inject floating action toolbar on each block in preview mode
            document.querySelectorAll('[data-block-id]').forEach(block => {
                const blockId = block.getAttribute('data-block-id');
                if (!blockId) return;

                const bar = document.createElement('div');
                bar.className = 'studio-block-floating-bar';
                bar.innerHTML = `
                    <button type="button" class="btn-block-quick-edit" title="ویرایش سریع تنظیمات این بخش">
                        <span class="material-symbols-outlined text-xs">edit_note</span>
                        <span>ویرایش سریع</span>
                    </button>
                    <button type="button" class="btn-block-move-up" title="انتقال بخش به بالا">
                        <span class="material-symbols-outlined text-xs">arrow_upward</span>
                    </button>
                    <button type="button" class="btn-block-move-down" title="انتقال بخش به پایین">
                        <span class="material-symbols-outlined text-xs">arrow_downward</span>
                    </button>
                    <button type="button" class="btn-block-toggle-vis" title="مخفی یا نمایش بخش">
                        <span class="material-symbols-outlined text-xs">visibility</span>
                    </button>
                `;

                bar.querySelector('.btn-block-quick-edit').addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.parent.postMessage({ type: 'OPEN_QUICK_EDIT_MODAL', blockId: blockId }, '*');
                });
                bar.querySelector('.btn-block-move-up').addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.parent.postMessage({ type: 'MOVE_BLOCK_FROM_PREVIEW', blockId: blockId, direction: 'up' }, '*');
                });
                bar.querySelector('.btn-block-move-down').addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.parent.postMessage({ type: 'MOVE_BLOCK_FROM_PREVIEW', blockId: blockId, direction: 'down' }, '*');
                });
                bar.querySelector('.btn-block-toggle-vis').addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.parent.postMessage({ type: 'TOGGLE_BLOCK_FROM_PREVIEW', blockId: blockId }, '*');
                });

                block.style.position = 'relative';
                block.appendChild(bar);
            });

            // Inject floating bottom helper bar
            if (!document.getElementById('studio-preview-helper-bar')) {
                const helper = document.createElement('div');
                helper.id = 'studio-preview-helper-bar';
                helper.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-2xl bg-slate-900/90 text-white backdrop-blur-md shadow-2xl border border-white/20 flex items-center gap-2.5 text-xs font-bold pointer-events-auto transition-all';
                helper.innerHTML = `
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>✨ ویرایش مستقیم: روی هر متنی کلیک کنید و مستقیماً ویرایش فرمایید</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white mr-1 text-sm font-bold" title="بستن پیام">✕</button>
                `;
                document.body.appendChild(helper);
            }
        }

        // Preview micro-feedback toast
        function showPreviewFeedback(text) {
            let toast = document.getElementById('studio-preview-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'studio-preview-toast';
                toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-xl bg-slate-900/90 text-white text-xs font-bold shadow-2xl backdrop-blur-md border border-white/20 transition-all pointer-events-none opacity-0';
                document.body.appendChild(toast);
            }
            toast.innerText = text;
            toast.style.opacity = '1';
            toast.style.transform = 'translate(-50%, 0)';
            clearTimeout(window.__previewToastTimer);
            window.__previewToastTimer = setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -10px)';
            }, 2500);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPreviewDirectEditing);
        } else {
            initPreviewDirectEditing();
        }

        // Interactive Before/After slider updater
        function updateBeforeAfterSlider(val) {
            const beforeImg = document.getElementById('live-ba-img-before');
            const dividerLine = document.getElementById('ba-divider-line');
            if (beforeImg && dividerLine) {
                const cutLeft = 100 - Number(val);
                beforeImg.style.clipPath = 'inset(0 0 0 ' + cutLeft + '%)';
                beforeImg.style.webkitClipPath = 'inset(0 0 0 ' + cutLeft + '%)';
                dividerLine.style.right = val + '%';
            }
        }

        // Real-time In-place Live Preview Synchronization (Zero Page Refresh)
        window.applyLiveFieldUpdate = function(field, value, extra) {
            switch (field) {
                case 'site_title': {
                    const t = (value && value.trim()) ? value : 'وب‌سایت اختصاصی';
                    const topTitle = document.getElementById('live-topbar-title');
                    if (topTitle) topTitle.innerText = `${t} | پذیرش فعال و نوبت‌دهی آنلاین`;
                    const headerTitle = document.getElementById('live-header-title');
                    if (headerTitle) headerTitle.innerText = t;
                    const footerTitle = document.getElementById('live-footer-title');
                    if (footerTitle) footerTitle.innerText = t;
                    const heroTitle = document.getElementById('live-hero-title');
                    if (heroTitle && (!heroTitle.getAttribute('data-custom') || heroTitle.innerText === '')) heroTitle.innerText = t;
                    const heroImgTitle = document.getElementById('live-hero-overlay-title');
                    if (heroImgTitle) heroImgTitle.innerText = t;
                    const footerCopy = document.getElementById('live-footer-copyright');
                    if (footerCopy) footerCopy.innerText = `کلیه حقوق برای ${t} محفوظ است.`;
                    break;
                }
                case 'site_tagline': {
                    const taglineEl = document.getElementById('live-header-tagline');
                    if (taglineEl) {
                        taglineEl.innerText = value || '';
                        if (value && value.trim()) {
                            taglineEl.classList.remove('hidden');
                        } else {
                            taglineEl.classList.add('hidden');
                        }
                    }
                    const footerTagline = document.getElementById('live-footer-tagline');
                    if (footerTagline) {
                        footerTagline.innerText = value || '';
                        if (value && value.trim()) {
                            footerTagline.classList.remove('hidden');
                        } else {
                            footerTagline.classList.add('hidden');
                        }
                    }
                    break;
                }
                case 'site_logo': {
                    const headerLogo = document.getElementById('live-header-logo');
                    if (headerLogo && value) headerLogo.src = value;
                    const footerLogo = document.getElementById('live-footer-logo');
                    if (footerLogo && value) footerLogo.src = value;
                    break;
                }
                case 'theme_palette': {
                    const palettes = {
                        emerald: { primary: '#059669', primary_hover: '#047857', primary_light: '#ecfdf5', primary_border: '#a7f3d0', accent: '#fd8100', subtle_glow: 'rgba(5, 150, 105, 0.15)' },
                        navy: { primary: '#001a48', primary_hover: '#002666', primary_light: '#eff6ff', primary_border: '#bfdbfe', accent: '#fd8100', subtle_glow: 'rgba(0, 26, 72, 0.15)' },
                        orange: { primary: '#ea580c', primary_hover: '#c2410c', primary_light: '#fff7ed', primary_border: '#fed7aa', accent: '#001a48', subtle_glow: 'rgba(234, 88, 12, 0.15)' },
                        purple: { primary: '#7c3aed', primary_hover: '#6d28d9', primary_light: '#f5f3ff', primary_border: '#ddd6fe', accent: '#ea580c', subtle_glow: 'rgba(124, 58, 237, 0.15)' },
                        aurora: { primary: '#0891b2', primary_hover: '#0e7490', primary_light: '#ecfeff', primary_border: '#a5f3fc', accent: '#001a48', subtle_glow: 'rgba(8, 145, 178, 0.15)' }
                    };
                    const p = palettes[value] || palettes.emerald;
                    const r = document.documentElement;
                    r.style.setProperty('--tenant-primary', p.primary);
                    r.style.setProperty('--tenant-primary-hover', p.primary_hover);
                    r.style.setProperty('--tenant-primary-light', p.primary_light);
                    r.style.setProperty('--tenant-primary-border', p.primary_border);
                    r.style.setProperty('--tenant-accent', p.accent);
                    r.style.setProperty('--tenant-glow', p.subtle_glow);
                    break;
                }
                case 'block_toggle': {
                    const block = document.querySelector(`[data-block-id="${extra}"]`);
                    if (block) {
                        if (value) {
                            block.style.removeProperty('display');
                            block.classList.remove('hidden');
                        } else {
                            block.style.setProperty('display', 'none', 'important');
                            block.classList.add('hidden');
                        }
                    }
                    const navLink = document.querySelector(`[data-nav-link="${extra}"]`);
                    if (navLink) {
                        navLink.classList.toggle('hidden', !value);
                    }
                    break;
                }
                case 'emergency_headline': {
                    const el = document.getElementById('live-emergency-headline');
                    if (el) el.innerText = value || 'اورژانس ۲۴ ساعته و مراقبت‌های فوری حیوانات خانگی';
                    break;
                }
                case 'emergency_subheadline': {
                    const el = document.getElementById('live-emergency-subheadline');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'emergency_phone': {
                    const el = document.getElementById('live-emergency-phone');
                    if (el) el.innerText = value || '';
                    const link = document.getElementById('live-emergency-phone-link');
                    if (link) {
                        link.href = `tel:${value}`;
                        link.classList.toggle('hidden', !value);
                    }
                    const topEm = document.getElementById('live-topbar-em-phone');
                    if (topEm) topEm.innerText = value || '';
                    const topEmWrap = document.getElementById('live-topbar-em-wrap');
                    if (topEmWrap) topEmWrap.classList.toggle('hidden', !value);
                    break;
                }
                case 'hero_badge': {
                    const el = document.getElementById('live-hero-badge');
                    if (el) el.innerText = value || '';
                    const wrap = document.getElementById('live-hero-badge-wrap');
                    if (wrap) wrap.style.display = (value && value.trim()) ? 'inline-flex' : 'none';
                    const ov = document.getElementById('live-hero-overlay-badge');
                    if (ov) ov.innerText = value || 'پذیرش رسمی';
                    break;
                }
                case 'hero_title': {
                    const el = document.getElementById('live-hero-title');
                    if (el) {
                        el.innerText = value || (document.getElementById('live-header-title')?.innerText || '');
                        el.setAttribute('data-custom', '1');
                    }
                    break;
                }
                case 'hero_subtitle': {
                    const el = document.getElementById('live-hero-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'hero_cta': {
                    const el = document.getElementById('live-hero-cta');
                    if (el) el.innerText = value || 'رزرو آنلاین نوبت';
                    break;
                }
                case 'hero_image':
                case 'banner_image': {
                    const el = document.getElementById('live-hero-image');
                    if (el && value) el.src = value;
                    break;
                }
                case 'duty_hours': {
                    const el = document.getElementById('live-duty-hours-text');
                    if (el) el.innerText = `ساعات کاری اعلامی: ${value || '۸:۳۰ الی ۲۲:۳۰'}`;
                    const contactH = document.getElementById('live-contact-hours');
                    if (contactH && value) contactH.innerText = value;
                    break;
                }
                case 'before_after_heading': {
                    const el = document.getElementById('live-ba-heading');
                    if (el) el.innerText = value || 'مقایسه نتایج قبل و بعد از مراقبت تخصصی';
                    break;
                }
                case 'before_after_subtitle': {
                    const el = document.getElementById('live-ba-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'before_after_service_label': {
                    const el = document.getElementById('live-ba-service-badge');
                    if (el) el.innerText = value || 'نتایج ملموس خدمات و جراحی‌ها';
                    break;
                }
                case 'before_after_label_before': {
                    const el = document.getElementById('live-ba-label-before');
                    if (el) el.innerText = value || 'قبل از درمان';
                    break;
                }
                case 'before_after_label_after': {
                    const el = document.getElementById('live-ba-label-after');
                    if (el) el.innerText = value || 'پس از درمان';
                    break;
                }
                case 'before_after_image_before': {
                    const el = document.getElementById('live-ba-img-before');
                    if (el && value) el.src = value;
                    break;
                }
                case 'before_after_image_after': {
                    const el = document.getElementById('live-ba-img-after');
                    if (el && value) el.src = value;
                    break;
                }
                case 'trust_anchor_toggle': {
                    const el = document.getElementById('live-trust-anchor');
                    if (el) el.style.display = value ? 'inline-flex' : 'none';
                    break;
                }
                case 'ambient_mode': {
                    document.body.classList.toggle('atmospheric-bg', value === 'atmospheric_glow');
                    break;
                }
                case 'bento_heading': {
                    const el = document.getElementById('live-bento-heading');
                    if (el) el.innerText = value || 'تجهیزات مدرن و ظرفیت‌های بالینی مرکز';
                    break;
                }
                case 'about_heading': {
                    const el = document.getElementById('live-about-heading');
                    if (el) el.innerText = value || 'درباره ما';
                    break;
                }
                case 'about_text': {
                    const el = document.getElementById('live-about-text');
                    if (el) el.innerHTML = (value || '').replace(/\n/g, '<br>');
                    break;
                }
                case 'about_vet_council': {
                    const el = document.getElementById('live-about-vet-council');
                    if (el) el.innerText = value || '';
                    const wrap = document.getElementById('live-about-vet-council-wrap');
                    if (wrap) wrap.classList.toggle('hidden', !value || !value.trim());
                    break;
                }
                case 'calc_badge': {
                    const el = document.getElementById('live-calc-badge');
                    if (el) el.innerText = value || 'تعرفه شفاف خدمات درمانی و جراحی';
                    break;
                }
                case 'calc_heading': {
                    const el = document.getElementById('live-calc-heading');
                    if (el) el.innerText = value || 'برآورد آنلاین و شفاف تعرفه خدمات و جراحی‌های تخصصی';
                    break;
                }
                case 'calc_subtitle': {
                    const el = document.getElementById('live-calc-subtitle');
                    if (el) el.innerText = value || 'گونه حیوان خانگی و خدمات تشخیصی، بالینی یا جراحی مدنظر را انتخاب فرمایید تا تعرفه مصوب رسمی همراه با ۱۰٪ تخفیف رزرو آنلاین برآورد گردد.';
                    break;
                }
                case 'calc_discount': {
                    const pct = parseInt(value) || 0;
                    const badge = document.getElementById('live-calc-discount-badge');
                    if (badge) badge.innerText = `٪${pct} تخفیف آنلاین`;
                    const pctSpan = document.getElementById('live-calc-discount-pct');
                    if (pctSpan) pctSpan.innerText = pct;
                    discountPercent = pct;
                    if (typeof recalculateCost === 'function') recalculateCost();
                    break;
                }
                case 'custom_colors': {
                    const primary = (value && value.primary) ? value.primary : null;
                    const secondary = (value && value.secondary) ? value.secondary : null;
                    const r = document.documentElement;
                    if (primary && /^#[a-f0-9]{6}$/i.test(primary)) {
                        const hex = primary.replace('#', '');
                        let red = parseInt(hex.substring(0, 2), 16);
                        let green = parseInt(hex.substring(2, 4), 16);
                        let blue = parseInt(hex.substring(4, 6), 16);

                        // Safe luminance clamp (> 0.75 is dimmed to protect white text readability)
                        const lum = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
                        if (lum > 0.75) {
                            red = Math.round(red * 0.65);
                            green = Math.round(green * 0.65);
                            blue = Math.round(blue * 0.65);
                        }
                        const safePrimary = '#' + [red, green, blue].map(x => x.toString(16).padStart(2, '0')).join('');
                        const hoverRed = Math.max(0, Math.round(red * 0.85));
                        const hoverGreen = Math.max(0, Math.round(green * 0.85));
                        const hoverBlue = Math.max(0, Math.round(blue * 0.85));
                        const hoverPrimary = '#' + [hoverRed, hoverGreen, hoverBlue].map(x => x.toString(16).padStart(2, '0')).join('');

                        r.style.setProperty('--tenant-primary', safePrimary);
                        r.style.setProperty('--tenant-primary-hover', hoverPrimary);
                        r.style.setProperty('--tenant-primary-light', `rgba(${red}, ${green}, ${blue}, 0.08)`);
                        r.style.setProperty('--tenant-primary-border', `rgba(${red}, ${green}, ${blue}, 0.22)`);
                        r.style.setProperty('--tenant-glow', `rgba(${red}, ${green}, ${blue}, 0.15)`);
                    }
                    if (secondary && /^#[a-f0-9]{6}$/i.test(secondary)) {
                        r.style.setProperty('--tenant-accent', secondary);
                    }
                    break;
                }
                case 'doctors_heading': {
                    const el = document.getElementById('live-doctors-heading');
                    if (el) el.innerText = value || 'پزشکان و جراحان مرکز';
                    break;
                }
                case 'doctors_subtitle': {
                    const el = document.getElementById('live-doctors-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'booking_heading': {
                    const el = document.getElementById('live-booking-heading');
                    if (el) el.innerText = value || 'رزرو اینترنتی نوبت';
                    break;
                }
                case 'storefront_heading': {
                    const el = document.getElementById('live-storefront-heading');
                    if (el) el.innerText = value || 'ویترین محصولات و داروها';
                    break;
                }

                case 'reviews_heading': {
                    const el = document.getElementById('live-reviews-heading');
                    if (el) el.innerText = value || 'نظرات و بازخورد سرپرستان پت';
                    break;
                }
                case 'faq_heading': {
                    const el = document.getElementById('live-faq-heading');
                    if (el) el.innerText = value || 'پرسش‌های متداول';
                    break;
                }
                case 'social_heading': {
                    const el = document.getElementById('live-social-heading');
                    if (el) el.innerText = value || 'شبکه‌های اجتماعی و ارتباط آنلاین';
                    break;
                }
                case 'social_subtitle': {
                    const el = document.getElementById('live-social-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'contact_heading': {
                    const el = document.getElementById('live-contact-heading');
                    if (el) el.innerText = value || 'اطلاعات تماس و نشانی';
                    break;
                }
                case 'contact_address': {
                    const el = document.getElementById('live-contact-address');
                    if (el) {
                        const a = el.querySelector('a');
                        if (a) a.innerText = value || 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
                        else el.innerText = value || 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
                    }
                    const fEl = document.getElementById('live-footer-address');
                    if (fEl) {
                        const a = fEl.querySelector('a');
                        if (a) a.innerText = value || 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
                        else fEl.innerText = value || 'تهران، خیابان ولیعصر، نرسیده به میدان ونک';
                    }
                    break;
                }
                case 'contact_map_link': {
                    const mapUrl = (value && value.trim()) ? value.trim() : '';
                    // Update duty nav button
                    const dutyNavBtn = document.getElementById('live-duty-nav-btn');
                    if (dutyNavBtn) {
                        dutyNavBtn.href = mapUrl ? mapUrl : 'javascript:openNavHubModal()';
                        dutyNavBtn.target = mapUrl ? '_blank' : '_self';
                    }
                    // Update contact address link
                    const contactAddr = document.getElementById('live-contact-address');
                    if (contactAddr) {
                        const existingText = contactAddr.innerText.trim();
                        if (mapUrl) {
                            contactAddr.innerHTML = `<a href="${escapePreviewHtml(mapUrl)}" target="_blank" rel="noopener" class="hover:text-tenant-primary hover:underline transition-colors" title="مشاهده موقعیت روی نقشه">${escapePreviewHtml(existingText)}</a>`;
                        } else {
                            contactAddr.innerText = existingText;
                        }
                    }
                    // Update contact nav button
                    const contactNavBtn = document.getElementById('live-contact-nav-btn');
                    if (contactNavBtn) {
                        contactNavBtn.href = mapUrl ? mapUrl : 'javascript:openNavHubModal()';
                        contactNavBtn.target = mapUrl ? '_blank' : '_self';
                    }
                    // Update footer address link
                    const footerAddr = document.getElementById('live-footer-address');
                    if (footerAddr) {
                        const existingText = footerAddr.innerText.trim();
                        if (mapUrl) {
                            footerAddr.innerHTML = `<a href="${escapePreviewHtml(mapUrl)}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="مشاهده موقعیت روی نقشه">${escapePreviewHtml(existingText)}</a>`;
                        } else {
                            footerAddr.innerText = existingText;
                        }
                    }
                    // Update footer nav button
                    const footerNavBtn = document.getElementById('live-footer-nav-btn');
                    if (footerNavBtn) {
                        footerNavBtn.href = mapUrl ? mapUrl : 'javascript:openNavHubModal()';
                        footerNavBtn.target = mapUrl ? '_blank' : '_self';
                    }
                    // Update modal direct button if present
                    const modalDirectBtn = document.getElementById('navhub-modal-direct-btn');
                    if (modalDirectBtn) {
                        modalDirectBtn.href = mapUrl || '#';
                        modalDirectBtn.classList.toggle('hidden', !mapUrl);
                    }
                    break;
                }
                case 'contact_nav_btn_text': {
                    const txt = (value && value.trim()) ? value.trim() : 'مسیریابی با بلد / نشان';
                    const dutyTxt = document.getElementById('live-duty-nav-text');
                    if (dutyTxt) dutyTxt.innerText = txt;
                    const contactTxt = document.getElementById('live-contact-nav-text');
                    if (contactTxt) contactTxt.innerText = txt;
                    const footerTxt = document.getElementById('live-footer-nav-text');
                    if (footerTxt) footerTxt.innerText = txt;
                    break;
                }
                case 'contact_hours': {
                    const el = document.getElementById('live-contact-hours');
                    if (el) el.innerText = value || 'شنبه تا پنجشنبه ۸ الی ۲۲';
                    break;
                }
                case 'contact_phone': {
                    const el = document.getElementById('live-contact-phone');
                    if (el) {
                        el.innerText = value || '۰۲۱-۸۸۸۸۹۹۹۹';
                        el.href = `tel:${value}`;
                    }
                    const topPhone = document.getElementById('live-topbar-phone');
                    if (topPhone) topPhone.innerText = value || '';
                    const topPhoneWrap = document.getElementById('live-topbar-phone-wrap');
                    if (topPhoneWrap) topPhoneWrap.classList.toggle('hidden', !value);
                    const hdrPhone = document.getElementById('live-header-phone');
                    if (hdrPhone) hdrPhone.innerText = value || '';
                    const hdrPhoneLink = document.getElementById('live-header-phone-link');
                    if (hdrPhoneLink) hdrPhoneLink.classList.toggle('hidden', !value);
                    break;
                }
                case 'contact_emergency': {
                    const el = document.getElementById('live-contact-emergency');
                    if (el) el.innerText = value || '';
                    const wrap = document.getElementById('live-contact-emergency-wrap');
                    if (wrap) wrap.classList.toggle('hidden', !value || !value.trim());
                    const topEm = document.getElementById('live-topbar-em-phone');
                    if (topEm) topEm.innerText = value || '';
                    const topEmWrap = document.getElementById('live-topbar-em-wrap');
                    if (topEmWrap) topEmWrap.classList.toggle('hidden', !value);
                    break;
                }
                case 'header_cta_text': {
                    const el = document.getElementById('live-header-cta-text');
                    if (el) el.innerText = value || 'رزرو آنلاین نوبت';
                    break;
                }
                case 'header_cta_url': {
                    const el = document.getElementById('live-header-cta-btn');
                    if (el) el.href = value || '#booking';
                    break;
                }
                case 'hero_cta_url': {
                    const el = document.getElementById('live-hero-primary-cta');
                    if (el) {
                        el.href = value || '#booking';
                        el.target = (value && value.startsWith('#')) ? '_self' : '_blank';
                    }
                    break;
                }
                case 'hero_cta_secondary': {
                    const el = document.getElementById('live-hero-secondary-cta-text');
                    if (el) el.innerText = value || 'مشاهده خدمات و تخصص‌ها';
                    break;
                }
                case 'hero_cta_secondary_url': {
                    const el = document.getElementById('live-hero-secondary-cta');
                    if (el) {
                        el.href = value || '#services';
                        el.target = (value && (value.startsWith('#') || value.startsWith('tel:'))) ? '_self' : '_blank';
                    }
                    break;
                }
                case 'hero_badge': {
                    const el = document.getElementById('live-hero-badge');
                    if (el) el.innerText = value || '';
                    const wrap = document.getElementById('live-hero-badge-wrap');
                    if (wrap) wrap.style.display = (value && value.trim()) ? 'inline-flex' : 'none';
                    const overlay = document.getElementById('live-hero-overlay-badge');
                    if (overlay) overlay.innerText = value || 'پذیرش رسمی';
                    break;
                }
                case 'hero_title': {
                    const el = document.getElementById('live-hero-title');
                    if (el) {
                        el.innerText = value || '';
                        el.setAttribute('data-custom', '1');
                    }
                    break;
                }
                case 'hero_subtitle': {
                    const el = document.getElementById('live-hero-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'hero_cta':
                case 'hero_cta_text': {
                    const el = document.getElementById('live-hero-cta');
                    if (el) el.innerText = value || 'رزرو آنلاین نوبت';
                    break;
                }
                case 'hero_review_score': {
                    const el = document.getElementById('live-hero-review-score');
                    if (el) el.innerText = value || '۴.۹';
                    break;
                }
                case 'hero_review_count': {
                    const el = document.getElementById('live-hero-review-count');
                    if (el) el.innerText = value || 'بیش از ۱۸۰+ نظر تاییدشده';
                    break;
                }
                case 'hero_cert_title': {
                    const el = document.getElementById('live-hero-cert-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'hero_cert_desc': {
                    const el = document.getElementById('live-hero-cert-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'trust_strip_1': {
                    const el = document.getElementById('live-hero-trust-1');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'trust_strip_2': {
                    const el = document.getElementById('live-hero-trust-2');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'trust_strip_3': {
                    const el = document.getElementById('live-hero-trust-3');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_1_val': {
                    const el = document.getElementById('live-stat-1-val');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_1_lbl': {
                    const el = document.getElementById('live-stat-1-lbl');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_2_val': {
                    const el = document.getElementById('live-stat-2-val');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_2_lbl': {
                    const el = document.getElementById('live-stat-2-lbl');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_3_val': {
                    const el = document.getElementById('live-stat-3-val');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_3_lbl': {
                    const el = document.getElementById('live-stat-3-lbl');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_4_val': {
                    const el = document.getElementById('live-stat-4-val');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'stat_4_lbl': {
                    const el = document.getElementById('live-stat-4-lbl');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'duty_hours': {
                    const el = document.getElementById('live-duty-hours-text');
                    if (el) el.innerText = 'ساعات کاری اعلامی: ' + (value || '۸:۳۰ الی ۲۲:۳۰');
                    break;
                }
                case 'before_after_heading': {
                    const el = document.getElementById('live-ba-heading');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'before_after_subtitle': {
                    const el = document.getElementById('live-ba-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'before_after_service_label': {
                    const el = document.getElementById('live-ba-service-badge');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'before_after_label_after': {
                    const el = document.getElementById('live-ba-label-after');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'before_after_label_before': {
                    const el = document.getElementById('live-ba-label-before');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'mobile_cta_text': {
                    const el = document.getElementById('live-mobile-cta');
                    if (el) el.innerText = value || 'رزرو آنلاین نوبت';
                    break;
                }
                case 'footer_about': {
                    const el = document.getElementById('live-footer-about');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'footer_copyright': {
                    const el = document.getElementById('live-footer-copyright');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'asena_badge': {
                    const el = document.getElementById('live-asena-badge');
                    if (el) el.innerText = value || 'خدمات یکپارچه شبکه سلامت آسنا';
                    break;
                }
                case 'asena_heading': {
                    const el = document.getElementById('live-asena-heading');
                    if (el) el.innerText = value || 'خدمات آنلاین و دسترسی مستقیم به اکوسیستم سلامت آسنا';
                    break;
                }
                case 'asena_subtitle': {
                    const el = document.getElementById('live-asena-subtitle');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'telehealth_title': {
                    const el = document.getElementById('live-telehealth-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'telehealth_desc': {
                    const el = document.getElementById('live-telehealth-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'telehealth_btn': {
                    const el = document.getElementById('live-telehealth-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'telehealth_url': {
                    const el = document.getElementById('live-telehealth-link');
                    if (el) el.href = value || 'https://asena.company/chat.php';
                    break;
                }
                case 'pharmacy_title': {
                    const el = document.getElementById('live-pharmacy-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'pharmacy_desc': {
                    const el = document.getElementById('live-pharmacy-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'pharmacy_btn': {
                    const el = document.getElementById('live-pharmacy-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'pharmacy_url': {
                    const el = document.getElementById('live-pharmacy-link');
                    if (el) el.href = value || 'https://asena.company/pharmacy.php';
                    break;
                }
                case 'autoship_title': {
                    const el = document.getElementById('live-autoship-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'autoship_desc': {
                    const el = document.getElementById('live-autoship-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'autoship_btn': {
                    const el = document.getElementById('live-autoship-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'autoship_url': {
                    const el = document.getElementById('live-autoship-link');
                    if (el) el.href = value || 'https://asena.company/subscriptions.php';
                    break;
                }
                case 'rewards_title': {
                    const el = document.getElementById('live-rewards-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'rewards_desc': {
                    const el = document.getElementById('live-rewards-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'rewards_btn': {
                    const el = document.getElementById('live-rewards-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'rewards_url': {
                    const el = document.getElementById('live-rewards-link');
                    if (el) el.href = value || 'https://asena.company/rewards.php';
                    break;
                }
                case 'charity_title': {
                    const el = document.getElementById('live-charity-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'charity_desc': {
                    const el = document.getElementById('live-charity-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'charity_btn': {
                    const el = document.getElementById('live-charity-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'charity_url': {
                    const el = document.getElementById('live-charity-link');
                    if (el) el.href = value || 'https://asena.company/charity.php';
                    break;
                }
                case 'vcard_title': {
                    const el = document.getElementById('live-vcard-title');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'vcard_desc': {
                    const el = document.getElementById('live-vcard-desc');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'vcard_btn': {
                    const el = document.getElementById('live-vcard-btn');
                    if (el) el.innerText = value || '';
                    break;
                }
                case 'storefront_limit': {
                    const limit = parseInt(value) || 6;
                    const cards = document.querySelectorAll('#storefront .grid > div');
                    cards.forEach((card, idx) => {
                        card.classList.toggle('hidden', idx >= limit);
                    });
                    break;
                }
                case 'scroll_to_block': {
                    const block = document.querySelector(`[data-block-id="${value}"]`) || document.getElementById(value);
                    if (block) {
                        block.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    break;
                }
                case 'services_heading': {
                    const el = document.getElementById('live-services-heading');
                    if (el) el.innerText = value || 'خدمات تخصصی';
                    break;
                }
                case 'navhub_heading': {
                    const el = document.getElementById('live-navhub-heading');
                    if (el) el.innerText = value || 'مسیریابی ۱ کلیکه با اپلیکیشن‌های نقشه';
                    break;
                }
                case 'navhub_subtitle': {
                    const el = document.getElementById('live-navhub-subtitle');
                    if (el) el.innerText = value || 'مستقیماً موقعیت دقیق مجموعه را در مسیریاب‌های محبوب ایرانی و بین‌المللی باز نمایید.';
                    break;
                }
                case 'update_repeater': {
                    const payload = extra || value || {};
                    const sec = payload.section;
                    const items = payload.items || [];
                    renderLiveRepeaterSection(sec, items);
                    break;
                }
                case 'reorder_blocks': {
                    if (Array.isArray(value)) {
                        const body = document.body;
                        value.forEach(blockId => {
                            const block = document.querySelector(`[data-block-id="${blockId}"]`);
                            if (block && block.parentElement === body) {
                                body.appendChild(block);
                            }
                        });
                        const footer = document.querySelector('[data-block-id="footer"]');
                        if (footer && footer.parentElement === body) {
                            body.appendChild(footer);
                        }
                    }
                    break;
                }
            }
        };

        function notifyStudioRepeaterModal(section, index) {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'OPEN_REPEATER_MODAL',
                    section: section,
                    index: index
                }, '*');
            }
        }

        function notifyStudioRemoveRepeater(section, index) {
            if (confirm('آیا از حذف این مورد اطمینان دارید؟')) {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'REMOVE_REPEATER_ITEM',
                        section: section,
                        index: index
                    }, '*');
                }
            }
        }

        function escapePreviewHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderLiveRepeaterSection(section, items) {
            if (!Array.isArray(items)) return;
            if (section === 'navigation_hub') {
                const container = document.getElementById('live-navhub-buttons');
                if (!container) return;
                let html = '';
                const lat = '<?= $targetLat ?>';
                const lng = '<?= $targetLng ?>';
                items.forEach((app, idx) => {
                    let url = app.url || '';
                    const id = app.id || '';
                    const name = (app.name || '').toLowerCase();
                    if (!url) {
                        if (id === 'neshan' || name.includes('نشان')) url = `https://neshan.org/maps/@${lat},${lng},16z`;
                        else if (id === 'balad' || name.includes('بلد')) url = `https://balad.ir/location?latitude=${lat}&longitude=${lng}`;
                        else if (id === 'waze' || name.includes('waze') || name.includes('ویز')) url = `https://waze.com/ul?ll=${lat},${lng}&navigate=yes`;
                        else if (id === 'google_maps' || name.includes('گوگل') || name.includes('google')) url = `https://maps.google.com/?q=${lat},${lng}`;
                        else url = `https://maps.google.com/?q=${lat},${lng}`;
                    }
                    html += `
                    <div class="relative group/repeater-item inline-flex" data-repeater-index="${idx}">
                        <a href="${escapePreviewHtml(url)}" target="_blank" class="px-3.5 py-2.5 rounded-xl ${escapePreviewHtml(app.bg || 'bg-blue-600')} hover:opacity-90 text-white text-xs font-bold shadow-md transition-all flex items-center gap-1.5 active:scale-95" id="nav-btn-${escapePreviewHtml(id || idx)}">
                            <span class="material-symbols-outlined text-sm">${escapePreviewHtml(app.icon || 'navigation')}</span>
                            <span>${escapePreviewHtml(app.name || 'مسیریاب')}</span>
                        </a>
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900/95 text-white px-1.5 py-0.5 rounded-lg shadow-xl border border-white/20 z-20 text-[10px]">
                            <button type="button" onclick="notifyStudioRepeaterModal('navigation_hub', ${idx})" title="ویرایش دکمه" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">edit</span>
                            </button>
                            <button type="button" onclick="notifyStudioRemoveRepeater('navigation_hub', ${idx})" title="حذف دکمه" class="hover:text-red-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">delete</span>
                            </button>
                        </div>
                    </div>`;
                });
                html += `
                <button type="button" onclick="notifyStudioRepeaterModal('navigation_hub', -1)" class="px-3 py-2 rounded-xl border-2 border-dashed border-white/40 hover:border-amber-400 hover:text-amber-300 text-white/80 text-xs font-bold transition-all flex items-center gap-1 active:scale-95 cursor-pointer" title="افزودن مسیریاب دلخواه">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>افزودن مسیریاب</span>
                </button>`;
                container.innerHTML = html;
            } else if (section === 'services') {
                const container = document.getElementById('live-services-grid');
                if (!container) return;
                let html = '';
                items.forEach((srv, idx) => {
                    html += `
                    <div class="relative group/repeater-item bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300" data-repeater-index="${idx}">
                        <div class="absolute top-3 left-3 hidden group-hover/repeater-item:flex items-center gap-1.5 bg-slate-900/90 text-white px-2 py-1 rounded-xl shadow-lg border border-slate-700 z-10 text-xs">
                            <button type="button" onclick="notifyStudioRepeaterModal('services', ${idx})" title="ویرایش خدمت" class="hover:text-emerald-400 flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <span class="text-[10px]">ویرایش</span>
                            </button>
                            <span class="text-slate-600">|</span>
                            <button type="button" onclick="notifyStudioRemoveRepeater('services', ${idx})" title="حذف خدمت" class="hover:text-red-400 flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                <span class="text-[10px]">حذف</span>
                            </button>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-2xl">${escapePreviewHtml(srv.icon || 'star')}</span>
                        </div>
                        <h4 class="font-black text-slate-900 text-base mb-2">${escapePreviewHtml(srv.title || '')}</h4>
                        <p class="text-xs text-slate-600 leading-relaxed mb-3">${escapePreviewHtml(srv.desc || '')}</p>
                        ${srv.url ? `
                        <div class="pt-3 border-t border-slate-100 mt-auto">
                            <a href="${escapePreviewHtml(srv.url)}" ${srv.url.startsWith('http') ? 'target="_blank" rel="noopener"' : ''} class="inline-flex items-center gap-1.5 text-xs font-bold text-tenant-primary hover:text-tenant-primary-hover transition-colors">
                                <span>${escapePreviewHtml(srv.btn_text || 'اطلاعات بیشتر و رزرو')}</span>
                                <span class="material-symbols-outlined text-sm rtl:rotate-180">arrow_right_alt</span>
                            </a>
                        </div>` : ''}
                    </div>`;
                });
                html += `
                <button type="button" onclick="notifyStudioRepeaterModal('services', -1)" class="min-h-[160px] p-6 rounded-3xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group/add" title="افزودن خدمت جدید به کلینیک">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover/add:bg-emerald-100 group-hover/add:text-emerald-600 flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </div>
                    <span class="text-xs font-black">افزودن خدمت جدید</span>
                    <span class="text-[10px] text-slate-400">کلیک جهت ایجاد کارت خدمات جدید</span>
                </button>`;
                container.innerHTML = html;
            } else if (section === 'bento_facilities') {
                const container = document.getElementById('live-bento-grid');
                if (!container) return;
                let html = '';
                items.forEach((item, idx) => {
                    html += `
                    <div class="relative group/repeater-item bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 flex items-start gap-4 group" data-repeater-index="${idx}">
                        <div class="absolute top-3 left-3 hidden group-hover/repeater-item:flex items-center gap-1.5 bg-slate-900/90 text-white px-2 py-1 rounded-xl shadow-lg border border-slate-700 z-10 text-xs">
                            <button type="button" onclick="notifyStudioRepeaterModal('bento_facilities', ${idx})" title="ویرایش تجهیزات" class="hover:text-emerald-400 flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <span class="text-[10px]">ویرایش</span>
                            </button>
                            <span class="text-slate-600">|</span>
                            <button type="button" onclick="notifyStudioRemoveRepeater('bento_facilities', ${idx})" title="حذف تجهیزات" class="hover:text-red-400 flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                <span class="text-[10px]">حذف</span>
                            </button>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-tenant-light text-tenant-primary flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-3xl">${escapePreviewHtml(item.icon || 'local_hospital')}</span>
                        </div>
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200/60">${escapePreviewHtml(item.badge || item.tag || 'تخصصی')}</span>
                            <h4 class="text-base font-black text-slate-900">${escapePreviewHtml(item.title || '')}</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">${escapePreviewHtml(item.desc || '')}</p>
                            ${item.url ? `
                            <div class="pt-2">
                                <a href="${escapePreviewHtml(item.url)}" ${item.url.startsWith('http') ? 'target="_blank" rel="noopener"' : ''} class="inline-flex items-center gap-1 text-[11px] font-bold text-tenant-primary hover:text-tenant-primary-hover transition-colors">
                                    <span>${escapePreviewHtml(item.btn_text || 'مشاهده و جزئیات')}</span>
                                    <span class="material-symbols-outlined text-xs rtl:rotate-180">arrow_forward</span>
                                </a>
                            </div>` : ''}
                        </div>
                    </div>`;
                });
                html += `
                <button type="button" onclick="notifyStudioRepeaterModal('bento_facilities', -1)" class="min-h-[140px] p-6 rounded-3xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group/add" title="افزودن بخش یا امکانات درمانی">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover/add:bg-emerald-100 group-hover/add:text-emerald-600 flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </div>
                    <span class="text-xs font-black">افزودن بخش یا تجهیزات جدید</span>
                    <span class="text-[10px] text-slate-400">درج امکانات بالینی و تشخیصی</span>
                </button>`;
                container.innerHTML = html;
            } else if (section === 'faq') {
                const container = document.getElementById('live-faq-list');
                if (!container) return;
                let html = '';
                items.forEach((faq, idx) => {
                    html += `
                    <div class="relative group/repeater-item border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/70 hover:bg-white hover:border-slate-300 transition-all shadow-2xs" data-repeater-index="${idx}">
                        <button type="button" onclick="toggleSiteFaq(${idx})" class="w-full p-4 sm:p-5 text-right flex items-center justify-between gap-4 font-black text-xs sm:text-sm text-slate-800 transition-colors">
                            <span class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg bg-tenant-light text-tenant-primary flex items-center justify-center text-xs shrink-0 font-mono">؟</span>
                                <span>${escapePreviewHtml(faq.q || '')}</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <div class="hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900 text-white px-2 py-0.5 rounded-lg text-xs" onclick="event.stopPropagation()">
                                    <button type="button" onclick="notifyStudioRepeaterModal('faq', ${idx})" title="ویرایش پرسش" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-xs">edit</span>
                                    </button>
                                    <button type="button" onclick="notifyStudioRemoveRepeater('faq', ${idx})" title="حذف پرسش" class="hover:text-red-400 p-0.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-xs">delete</span>
                                    </button>
                                </div>
                                <span class="material-symbols-outlined text-slate-400 text-lg transition-transform duration-300 shrink-0" id="faq-chevron-${idx}">expand_more</span>
                            </div>
                        </button>
                        <div id="faq-answer-${idx}" class="hidden px-5 pb-5 pt-1 text-xs text-slate-600 leading-relaxed border-t border-slate-100 bg-white">
                            <p>${escapePreviewHtml(faq.a || '').replace(/\\n/g, '<br>')}</p>
                        </div>
                    </div>`;
                });
                html += `
                <button type="button" onclick="notifyStudioRepeaterModal('faq', -1)" class="w-full p-3.5 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-white/50 hover:bg-emerald-50/30 text-slate-500 hover:text-emerald-700 flex items-center justify-center gap-2 transition-all cursor-pointer" title="افزودن پرسش جدید">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span class="text-xs font-black">افزودن پرسش متداول جدید</span>
                </button>`;
                container.innerHTML = html;
            } else if (section === 'stats_strip') {
                const container = document.getElementById('live-stats-grid');
                if (!container) return;
                let html = '';
                items.forEach((st, idx) => {
                    html += `
                    <div class="relative group/repeater-item p-4 rounded-2xl bg-slate-50 border border-slate-100 text-center flex flex-col items-center justify-center hover:bg-emerald-50/20 transition-colors" data-repeater-index="${idx}">
                        <div class="absolute top-1.5 left-1.5 hidden group-hover/repeater-item:flex items-center gap-1 bg-slate-900/90 text-white px-1.5 py-0.5 rounded-lg text-[10px] z-10 shadow-md">
                            <button type="button" onclick="notifyStudioRepeaterModal('stats_strip', ${idx})" title="ویرایش شاخص" class="hover:text-emerald-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">edit</span>
                            </button>
                            <button type="button" onclick="notifyStudioRemoveRepeater('stats_strip', ${idx})" title="حذف شاخص" class="hover:text-red-400 p-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">delete</span>
                            </button>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-tenant-light text-tenant-primary flex items-center justify-center mb-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-lg">${escapePreviewHtml(st.icon || 'verified')}</span>
                        </div>
                        <div class="text-lg sm:text-xl font-black text-slate-900 tracking-tight font-mono" id="live-stat-${idx+1}-val" data-studio-editable="stat_${idx+1}_val">${escapePreviewHtml(st.value || '')}</div>
                        <div class="text-[11px] text-slate-500 font-bold" id="live-stat-${idx+1}-lbl" data-studio-editable="stat_${idx+1}_lbl">${escapePreviewHtml(st.label || '')}</div>
                    </div>`;
                });
                html += `
                <button type="button" onclick="notifyStudioRepeaterModal('stats_strip', -1)" class="p-4 rounded-2xl border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-white/40 hover:bg-emerald-50/30 text-slate-400 hover:text-emerald-700 flex flex-col items-center justify-center gap-1 transition-all cursor-pointer" title="افزودن شاخص جدید">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span class="text-[10px] font-black">افزودن شاخص</span>
                </button>`;
                container.innerHTML = html;
            } else if (section === 'social_links') {
                const modalContainer = document.getElementById('vcard-social-links-list');
                const standContainer = document.getElementById('stand-social-links-list');

                if (modalContainer) {
                    let html = '';
                    const enabledItems = items.filter(it => it.enabled !== false && (it.url || it.handle));
                    if (enabledItems.length === 0) {
                        html = '<div class="text-[11px] text-slate-400 p-2.5 bg-white rounded-xl text-center border border-dashed border-slate-200">پل ارتباطی فعالی ثبت نشده است. از استودیو می‌توانید شبکه‌های اجتماعی خود را اضافه فرمایید.</div>';
                    } else {
                        enabledItems.forEach((slink, idx) => {
                            const pIcon = slink.icon || 'link';
                            const pTitle = slink.title || 'شبکه اجتماعی';
                            const pHandle = slink.handle || slink.url || '';
                            const pUrl = slink.url || '#';
                            const pColor = slink.color || '#4f46e5';

                            html += `
                            <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-200/80 shadow-2xs hover:border-indigo-200 transition-all text-right group">
                                <div class="flex items-center gap-2 min-w-0 flex-1 pl-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0 shadow-2xs" style="background-color: ${escapePreviewHtml(pColor)};">
                                        <span class="material-symbols-outlined text-base">${escapePreviewHtml(pIcon)}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[11px] font-black text-slate-800 truncate">${escapePreviewHtml(pTitle)}</div>
                                        <div class="text-[10px] text-slate-500 font-mono truncate" dir="ltr">${escapePreviewHtml(pHandle)}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    ${pHandle ? `
                                    <button type="button" onclick="copySocialHandle('${escapePreviewHtml(pHandle)}')" title="کپی آیدی یا شماره" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors cursor-pointer">
                                        <span class="material-symbols-outlined text-xs">content_copy</span>
                                        <span class="hidden sm:inline">کپی</span>
                                    </button>` : ''}
                                    ${pUrl && pUrl !== '#' ? `
                                    <a href="${escapePreviewHtml(pUrl)}" target="_blank" title="مشاهده و ورود مستقیم" class="px-2 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center gap-0.5 transition-colors">
                                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                                        <span>ورود</span>
                                    </a>` : ''}
                                </div>
                            </div>`;
                        });
                    }
                    modalContainer.innerHTML = html;
                }

                if (standContainer) {
                    let sHtml = '';
                    const topHandles = items.filter(it => it.enabled !== false && it.handle).slice(0, 3);
                    if (topHandles.length > 0) {
                        topHandles.forEach(h => {
                            sHtml += `<span class="bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">${escapePreviewHtml(h.title)}: ${escapePreviewHtml(h.handle)}</span>`;
                        });
                    }
                    standContainer.innerHTML = sHtml;
                }

                const gridContainer = document.getElementById('live-social-grid');
                if (gridContainer) {
                    let gHtml = '';
                    const enabledItems = items.filter(it => it.enabled !== false && (it.url || it.handle));
                    if (enabledItems.length === 0) {
                        gHtml = '<div class="col-span-full text-center text-xs text-slate-400 p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200">پل ارتباطی فعالی ثبت نشده است.</div>';
                    } else {
                        enabledItems.forEach(slink => {
                            const pIcon = slink.icon || 'share';
                            const pTitle = slink.title || 'شبکه اجتماعی';
                            const pHandle = slink.handle || '';
                            const pBadge = slink.badge || '';
                            const pUrl = slink.url || '#';
                            const pColor = slink.color || '#7c3aed';

                            gHtml += `
                            <div class="bg-white/90 backdrop-blur-md p-4 rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-lg hover:border-slate-300 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
                                <div class="absolute top-0 inset-x-0 h-1 rounded-t-3xl transition-all group-hover:h-1.5" style="background: ${escapePreviewHtml(pColor)};"></div>
                                <div class="flex items-center gap-3 pt-1">
                                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-md transition-transform duration-300 group-hover:scale-105" style="background: ${escapePreviewHtml(pColor)};">
                                        <span class="material-symbols-outlined text-xl">${escapePreviewHtml(pIcon)}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-black text-slate-800 truncate">${escapePreviewHtml(pTitle)}</div>
                                        ${pBadge ? `<div class="text-[10px] text-purple-700 font-bold truncate mt-0.5">${escapePreviewHtml(pBadge)}</div>` : (pHandle ? `<div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" dir="ltr" title="${escapePreviewHtml(pHandle)}">${escapePreviewHtml(pHandle)}</div>` : '')}
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                    ${pHandle ? `
                                    <button type="button" onclick="copySocialHandle('${escapePreviewHtml(pHandle)}')" title="کپی آیدی" class="px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                        <span class="material-symbols-outlined text-xs">content_copy</span>
                                        <span>کپی</span>
                                    </button>` : ''}
                                    ${pUrl && pUrl !== '#' ? `
                                    <a href="${escapePreviewHtml(pUrl)}" target="_blank" referrerpolicy="origin" class="flex-1 py-1.5 px-3 rounded-xl text-center text-xs font-black text-white transition-all flex items-center justify-center gap-1 shadow-sm group-hover:shadow-md" style="background: ${escapePreviewHtml(pColor)};">
                                        <span>ورود</span>
                                        <span class="material-symbols-outlined text-xs">arrow_left</span>
                                    </a>` : ''}
                                </div>
                            </div>`;
                        });
                    }
                    gridContainer.innerHTML = gHtml;
                }
            }
        }

        window.addEventListener('message', function(e) {
            if (e.data && e.data.type === 'STUDIO_LIVE_UPDATE') {
                window.applyLiveFieldUpdate(e.data.field, e.data.value, e.data.extra);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

