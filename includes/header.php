<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/App.php';
App::boot();

// Monthly Loyalty Points Check & Role Refresh
$user_points_balance = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT last_monthly_points_date, role, loyalty_points FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_pts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_pts) {
        $_SESSION['user_role'] = $user_pts['role'];
        $user_points_balance = (int)($user_pts['loyalty_points'] ?? 0);
        $current_month = date('Y-m');
        $last_month = $user_pts['last_monthly_points_date'] ? date('Y-m', strtotime($user_pts['last_monthly_points_date'])) : '';
        
        if ($current_month !== $last_month) {
            $update_stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 20, last_monthly_points_date = CURDATE() WHERE id = ?");
            $update_stmt->execute([$_SESSION['user_id']]);
            $user_points_balance += 20;
        }
    }
}

// Calculate cart items count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);

// Smart SEO title & description fallbacks based on active page
$seo_defaults = [
    'index.php' => [
        'title' => 'پلتفرم کلینیک دامپزشکی و پت‌شاپ آنلاین آسنا | نوبت‌دهی و خرید ملزومات پت',
        'desc'  => 'کلینیک دامپزشکی و پت‌شاپ تخصصی آسنا؛ نوبت‌دهی آنلاین ویزیت پزشک، خرید غذای خشک و مکمل‌های سگ و گربه، و تحویل خودکار دوره‌ای با بهترین قیمت.'
    ],
    'shop.php' => [
        'title' => 'پت‌شاپ آنلاین آسنا | خرید غذای خشک، کنسرو، مکمل و ملزومات سگ و گربه',
        'desc'  => 'خرید اینترنتی انواع غذای سگ و گربه، لوازم بهداشتی، خاک گربه، تشویقی و مکمل‌های درمانی پت با ضمانت اصالت کالا و ارسال سریع در آسنا.'
    ],
    'booking.php' => [
        'title' => 'نوبت‌دهی آنلاین کلینیک دامپزشکی آسنا | رزرو ویزیت متخصص سگ، گربه و پرندگان',
        'desc'  => 'رزرو آنلاین نوبت دکتر دامپزشک؛ ویزیت عمومی و تخصصی، واکسیناسیون، جراحی، دندانپزشکی و چکاپ دوره‌ای پت با مجرب‌ترین کادر دامپزشکی.'
    ],
    'subscriptions.php' => [
        'title' => 'سفارش دوره‌ای و تحویل خودکار ملزومات پت (Autoship) | تخفیف ویژه آسنا',
        'desc'  => 'دیگر نگران تمام شدن غذای پت خود نباشید! با سیستم ارسال دوره‌ای و خودکار آسنا، سفارشات ماهانه شما با تخفیف دائمی و بدون نیاز به سفارش مجدد ارسال می‌شود.'
    ],
    'charity.php' => [
        'title' => 'پویش‌های حمایت و درمان حیوانات بی‌سرپرست | نقاهتگاه و درمانگاه آسنا',
        'desc'  => 'مشارکت در درمان، واکسیناسیون، عقیم‌سازی و تامین غذای حیوانات آسیب‌دیده و بی‌سرپرست با گزارش‌دهی شفاف و لحظه‌ای در سامانه خیریه آسنا.'
    ],
    'login.php' => [
        'title' => 'ورود و ثبت‌نام در سامانه آسنا | دسترسی به پرونده پزشکی و سفارشات پت',
        'desc'  => 'ورود به پنل کاربری آسنا جهت مدیریت نوبت‌های ویزیت دامپزشکی، پیگیری سفارشات پت‌شاپ و دسترسی به پرونده سلامت و واکسیناسیون حیوان خانگی.'
    ]
];

$default_seo = $seo_defaults[$current_page] ?? [
    'title' => 'کلینیک دامپزشکی و پت‌شاپ آنلاین آسنا',
    'desc'  => 'مرجع تخصصی خدمات دامپزشکی، نوبت‌دهی آنلاین و خرید ملزومات پت با تحویل دوره‌ای'
];

$effective_title = isset($page_title) ? $page_title : $default_seo['title'];
$effective_desc = isset($page_description) ? $page_description : $default_seo['desc'];

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$effective_canonical = isset($canonical_url) ? $canonical_url : "$proto://$host" . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$effective_og_image = isset($og_image) ? (strpos($og_image, 'http') === 0 ? $og_image : "$proto://$host/" . ltrim($og_image, '/')) : "$proto://$host/assets/images/og-asena.png";
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa" data-edition="standard">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover" name="viewport">
    <title><?php echo htmlspecialchars($effective_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="google-site-verification" content="google82c161050c864f06">
    <link rel="canonical" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <link rel="alternate" hreflang="fa-IR" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($effective_canonical); ?>">
    <meta name="geo.region" content="IR">
    <meta name="geo.placename" content="Iran">

    <!-- Safari / Apple & PWA Mobile App Support -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="64x64" href="assets/images/favicon-64x64.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/favicon-192x192.png?v=2">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/favicon-512x512.png?v=2">
    <link rel="shortcut icon" href="assets/images/favicon.ico?v=2">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png?v=2">
    <link rel="apple-touch-icon-precomposed" sizes="180x180" href="assets/images/apple-touch-icon.png?v=2">
    <link rel="manifest" href="/site.webmanifest?v=2">
    <meta name="theme-color" content="#002d72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ASENA">
    <meta name="application-name" content="ASENA Company">

    <!-- Preload Critical Font for Core Web Vitals (LCP) -->
    <link rel="preload" href="/assets/fonts/Dxxo8j6PP2D_kU2muijlGMWWMmk.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- Open Graph / Facebook / Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ASENA | کلینیک و پت‌شاپ تخصصی">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:title" content="<?php echo htmlspecialchars($effective_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($effective_canonical); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($effective_og_image); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($effective_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($effective_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($effective_og_image); ?>">

    <?php if (isset($page_schema) && !empty($page_schema)): ?>
    <!-- Page Specific Schema.org JSON-LD -->
    <script type="application/ld+json">
    <?php echo $page_schema; ?>
    </script>
    <?php else: ?>
    <!-- Default Organization & Breadcrumb Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "خانه",
          "item": "<?php echo $proto . '://' . $host; ?>/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "<?php echo htmlspecialchars($effective_title); ?>",
          "item": "<?php echo htmlspecialchars($effective_canonical); ?>"
        }
      ]
    }
    </script>
    <?php endif; ?>

    <!-- Fonts & Icons -->
    <link href="assets/css/material-symbols.css" rel="stylesheet">
    <link href="assets/css/vazirmatn.css" rel="stylesheet">
    <link href="assets/css/geist.css" rel="stylesheet">
    
    <!-- Custom & Tailwind CSS -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <script src="assets/js/tailwind-config.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/enterprise-ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/paw-loader.css">
    <script src="assets/js/paw-loader.js" defer></script>
    <!-- PWA Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(reg) {
                console.log('[PWA] ServiceWorker registered:', reg.scope);
            }).catch(function(err) {
                console.warn('[PWA] ServiceWorker error:', err);
            });
        });
    }
    </script>
</head>
<body class="bg-background text-on-background overflow-x-hidden">
<?php
$top_notif = null;
if (function_exists('get_curated_recommendations')) {
    $notifs = get_curated_recommendations($pdo, 'notification', 1);
    if (!empty($notifs)) {
        $top_notif = $notifs[0];
    }
}
?>
<?php if (!empty($top_notif)): ?>
<!-- Top Floating Notification Bar -->
<div id="topNotificationBar" class="bg-gradient-to-r from-secondary-container via-[#ea580c] to-secondary-container text-white py-2 px-4 text-xs font-bold shadow-sm relative z-50">
    <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 overflow-hidden">
            <span class="material-symbols-outlined text-base animate-bounce">campaign</span>
            <?php if (!empty($top_notif['custom_badge'])): ?>
                <span class="bg-white/20 px-2 py-0.5 rounded-full text-[11px] font-black shrink-0"><?= htmlspecialchars($top_notif['custom_badge']) ?></span>
            <?php endif; ?>
            <span class="truncate"><?= htmlspecialchars($top_notif['custom_title'] ?: $top_notif['product_name']) ?></span>
            <?php if (!empty($top_notif['custom_subtitle'])): ?>
                <span class="hidden md:inline font-normal opacity-90 text-[11px]">— <?= htmlspecialchars($top_notif['custom_subtitle']) ?></span>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="product_details.php?id=<?= (int)$top_notif['product_id'] ?>" class="bg-white text-secondary-container hover:bg-white/90 px-3 py-1 rounded-lg text-[11px] font-black transition-all shadow-sm flex items-center gap-1">
                <span>مشاهده و خرید</span>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
            <button type="button" onclick="document.getElementById('topNotificationBar').remove()" class="text-white/80 hover:text-white p-0.5">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

    <!-- Header Section -->
    <header class="bg-primary shadow-md sticky top-0 z-50 transition-all w-full lg:w-[96%] max-w-[1600px] mx-auto rounded-b-2xl lg:rounded-3xl mb-4 lg:mb-8 mt-0 lg:mt-6 px-3 lg:px-8 py-2.5 lg:py-4">
        <!-- Main Bar Row -->
        <div class="flex justify-between items-center w-full flex-row">
            
            <!-- Right side: Links and Search (Desktop) / Drawer (Mobile) -->
            <div class="flex items-center gap-2.5 lg:gap-8 flex-1">
                <!-- Mobile Drawer Toggle Button -->
                <button type="button" onclick="openMobileCategoriesSheet()" class="lg:hidden text-white p-1.5 hover:bg-white/10 rounded-xl transition-colors flex items-center justify-center" title="دسته‌بندی‌ها و خدمات">
                    <span class="material-symbols-outlined text-2xl">grid_view</span>
                </button>

                <!-- Desktop Links (Streamlined) -->
                <div class="hidden lg:flex gap-5 xl:gap-7 flex-row shrink-0 items-center">
                    <a class="text-white text-sm font-semibold hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'index.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="index.php">خانه</a>
                    <?php if (Feature::has('petshop_catalog')): ?>
                        <a class="text-white text-sm font-semibold hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'shop.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="shop.php">فروشگاه</a>
                    <?php endif; ?>
                    <?php if (Feature::has('pharmacy_catalog')): ?>
                        <a class="text-white text-sm font-semibold hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'pharmacy.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="pharmacy.php">داروخانه تخصصی</a>
                    <?php endif; ?>
                    <?php if (Feature::has('clinic_booking')): ?>
                        <a class="text-white text-sm font-semibold hover:text-secondary-container transition-all duration-200 <?php echo $current_page == 'booking.php' ? 'border-b-2 border-white pb-1 opacity-100' : 'opacity-90'; ?>" href="booking.php">نوبت‌دهی</a>
                    <?php endif; ?>

                    <!-- Dropdown for Other Services (Clean & Compact) -->
                    <div class="relative group">
                        <button type="button" class="text-white text-sm font-semibold hover:text-secondary-container transition-all duration-200 flex items-center gap-1 opacity-90 group-hover:opacity-100 cursor-pointer py-2">
                            <span>سایر خدمات</span>
                            <span class="material-symbols-outlined text-base transition-transform duration-200 group-hover:rotate-180">expand_more</span>
                        </button>
                        <div class="absolute right-0 top-full pt-1 w-60 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform origin-top-right">
                            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 overflow-hidden">
                                <?php if (Feature::has('clinic_booking')): ?>
                                    <a href="organizations.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">domain</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-slate-800">مراکز درمانی</div>
                                            <div class="text-[10px] text-slate-400">کلینیک‌ها و بیمارستان‌ها</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <?php if (Feature::has('autoship')): ?>
                                    <a href="subscriptions.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-secondary-container transition-colors">
                                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-secondary-container flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">autorenew</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-slate-800">اشتراک خودکار</div>
                                            <div class="text-[10px] text-slate-400">تحویل دوره‌ای با تخفیف</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <?php if (Feature::has('blog_engine')): ?>
                                    <a href="knowledge_base.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">auto_stories</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-slate-800">دانشنامه و مقالات</div>
                                            <div class="text-[10px] text-slate-400">مرجع سلامت و نگهداری پت</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <?php if (Feature::has('charity_campaigns')): ?>
                                    <a href="charity.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-rose-600 transition-colors">
                                        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">volunteer_activism</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-slate-800">خیریه و امداد</div>
                                            <div class="text-[10px] text-slate-400">پویش‌های درمانی حیوانات</div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Desktop Search with Instant Autocomplete Dropdown -->
                <div class="hidden lg:block relative flex-1 max-w-md" id="headerSearchWrapper">
                    <div class="flex items-center bg-white/10 hover:bg-white/15 focus-within:bg-white/20 border border-white/15 focus-within:border-white/40 transition-all rounded-full px-4 py-2 text-white gap-2 w-full">
                        <form action="shop.php" method="GET" class="flex items-center w-full" id="headerSearchForm">
                            <button type="submit" class="material-symbols-outlined text-lg bg-transparent border-none outline-none text-white cursor-pointer flex items-center justify-center p-0 hover:scale-110 transition-transform">search</button>
                            <input id="headerSearchInput" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder-white/70 text-white mr-2 outline-none font-medium" placeholder="جستجو در داروها، کالاها، کلینیک‌ها..." type="text" autocomplete="off">
                            <span id="headerSearchSpinner" class="material-symbols-outlined text-sm animate-spin hidden text-white/70">sync</span>
                        </form>
                    </div>
                    <!-- Live Results Dropdown -->
                    <div id="headerSearchResults" class="absolute right-0 top-full mt-2 w-full min-w-[340px] max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden hidden z-50 text-slate-800 text-right"></div>
                </div>
            </div>

            <!-- Left side: Icons, Roles, Points, and Logo -->
            <div class="flex items-center gap-2 lg:gap-5 shrink-0">
                <div class="hidden lg:flex items-center gap-2.5">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin/index.php" class="bg-secondary-container text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">admin_panel_settings</span> پنل مدیریت
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor'): ?>
                            <a href="doctor/index.php" class="bg-white text-primary px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">stethoscope</span> پنل پزشک
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'organization'): ?>
                            <a href="organization/index.php" class="bg-teal-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">domain</span> پنل مرکز درمانی
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'pharmacist'): ?>
                            <a href="pharmacist/index.php" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">medication</span> پنل داروساز
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller'): ?>
                            <a href="seller/index.php" class="bg-amber-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">storefront</span> پنل فروشنده
                            </a>
                        <?php endif; ?>

                        <!-- Loyalty Points Badge -->
                        <a href="rewards.php" class="hidden xl:inline-flex items-center gap-1 bg-white/10 hover:bg-white/20 border border-white/20 px-3 py-1.5 rounded-full text-xs font-bold text-amber-300 transition-all" title="امتیاز وفاداری باشگاه مشتریان">
                            <span class="material-symbols-outlined text-sm text-amber-400">stars</span>
                            <span><?php echo number_format($user_points_balance); ?> امتیاز</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="bg-secondary-container text-white px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">login</span> ورود / ثبت‌نام
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-1 lg:gap-2">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'; ?>" class="material-symbols-outlined text-white p-1.5 lg:p-2 hover:bg-white/10 rounded-full transition-colors flex text-xl lg:text-2xl" title="حساب کاربری">person</a>
                    
                    <a href="cart.php" class="relative material-symbols-outlined text-white p-1.5 lg:p-2 hover:bg-white/10 rounded-full transition-colors flex text-xl lg:text-2xl" title="سبد خرید">
                        shopping_cart
                        <?php if($cart_count > 0): ?>
                            <span class="absolute top-0 right-0 bg-secondary-container text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold shadow"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <a href="index.php" class="flex items-center gap-2 group" dir="ltr" title="صفحه اصلی آسنا">
                    <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-7 h-7 lg:w-9 lg:h-9 object-contain drop-shadow group-hover:scale-105 transition-transform duration-200">
                    <h1 class="text-lg lg:text-2xl font-black text-white tracking-tight group-hover:text-secondary-container transition-colors">ASENA</h1>
                </a>
            </div>
        </div>

        <!-- Row 2 (Mobile Only): Digikala App Style Omnibox Search Bar -->
        <div class="block lg:hidden relative w-full mt-2.5" id="mobileHeaderSearchWrapper">
            <form action="shop.php" method="GET" class="relative flex items-center bg-white/15 hover:bg-white/20 focus-within:bg-white focus-within:text-slate-800 border border-white/20 focus-within:border-white rounded-2xl transition-all px-3 py-2 text-white" id="mobileHeaderSearchForm">
                <span class="material-symbols-outlined text-xl text-white/80 shrink-0 ml-2 focus-within:text-primary">search</span>
                <input id="mobileHeaderSearchInput" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="w-full bg-transparent border-none outline-none text-xs sm:text-sm placeholder-white/75 focus:placeholder-slate-400 focus:text-slate-800 font-medium" placeholder="جستجو در آسنا (دارو، کالا، پزشک، کلینیک)..." autocomplete="off">
                <span id="mobileHeaderSearchSpinner" class="material-symbols-outlined text-sm animate-spin hidden text-white/70 mr-1">sync</span>
            </form>
            <!-- Mobile Live Search Autocomplete Dropdown -->
            <div id="mobileHeaderSearchResults" class="absolute right-0 left-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden hidden z-50 text-slate-800 text-right"></div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden opacity-0 transition-opacity duration-300">
        <div id="mobile-menu-panel" class="absolute top-0 right-0 h-full w-4/5 max-w-sm bg-surface-container-lowest shadow-2xl translate-x-full transition-transform duration-300 flex flex-col">
            <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center bg-primary text-white">
                <a href="index.php" class="flex items-center gap-2.5 text-white group">
                    <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-7 h-7 object-contain group-hover:scale-105 transition-transform">
                    <h2 class="text-xl font-bold">منوی کاربری</h2>
                </a>
                <button type="button" onclick="toggleMobileMenu()" class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 flex flex-col gap-6">
                <!-- Mobile Search -->
                <form action="shop.php" method="GET" class="flex items-center w-full bg-surface-container rounded-xl px-4 py-3">
                    <button type="submit" class="material-symbols-outlined text-lg text-primary bg-transparent border-none outline-none cursor-pointer flex items-center justify-center p-0">search</button>
                    <input name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder-on-surface-variant text-on-surface mr-3 font-medium" placeholder="جستجو..." type="text">
                </form>

                <!-- Mobile Links -->
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="index.php">
                        <span class="material-symbols-outlined text-outline">home</span> خانه
                    </a>
                    <?php if (Feature::has('petshop_catalog')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="shop.php">
                            <span class="material-symbols-outlined text-outline">storefront</span> فروشگاه
                        </a>
                    <?php endif; ?>
                    <?php if (Feature::has('pharmacy_catalog')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="pharmacy.php">
                            <span class="material-symbols-outlined text-outline">medication</span> داروخانه تخصصی
                        </a>
                    <?php endif; ?>
                    <?php if (Feature::has('clinic_booking')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="booking.php">
                            <span class="material-symbols-outlined text-outline">calendar_month</span> نوبت‌دهی آنلاین
                        </a>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="organizations.php">
                            <span class="material-symbols-outlined text-outline">local_hospital</span> مراکز درمانی و کلینیک‌ها
                        </a>
                    <?php endif; ?>
                    <?php if (Feature::has('autoship')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="subscriptions.php">
                            <span class="material-symbols-outlined text-outline">autorenew</span> اشتراک خودکار
                        </a>
                    <?php endif; ?>
                    <?php if (Feature::has('blog_engine')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="knowledge_base.php">
                            <span class="material-symbols-outlined text-outline">menu_book</span> دانشنامه
                        </a>
                    <?php endif; ?>
                    <?php if (Feature::has('charity_campaigns')): ?>
                        <a class="flex items-center gap-4 text-on-surface font-bold p-3 rounded-xl hover:bg-primary-container/10 hover:text-primary transition-colors" href="charity.php">
                            <span class="material-symbols-outlined text-outline">volunteer_activism</span> خیریه
                        </a>
                    <?php endif; ?>
                </nav>

                <div class="h-px w-full bg-outline-variant/20 my-2"></div>

                <!-- Auth Buttons for Mobile -->
                <div class="flex flex-col gap-3">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center justify-between bg-surface-container-high px-4 py-3 rounded-xl">
                            <a href="profile.php" class="flex items-center gap-2 text-primary text-sm font-bold">
                                <span class="material-symbols-outlined">person</span> حساب کاربری
                            </a>
                            <a href="rewards.php" class="flex items-center gap-1 text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                                <span class="material-symbols-outlined text-sm text-amber-500">stars</span>
                                <span><?php echo number_format($user_points_balance); ?> امتیاز</span>
                            </a>
                        </div>
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin/index.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-3.5 rounded-xl text-sm font-bold shadow-md">
                                <span class="material-symbols-outlined text-sm">admin_panel_settings</span> پنل مدیریت مرکزی
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor'): ?>
                            <a href="doctor/index.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-3.5 rounded-xl text-sm font-bold shadow-md">
                                <span class="material-symbols-outlined text-sm">stethoscope</span> پنل پزشک
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'organization'): ?>
                            <a href="organization/index.php" class="flex items-center justify-center gap-2 bg-teal-600 text-white px-6 py-3.5 rounded-xl text-sm font-bold shadow-md">
                                <span class="material-symbols-outlined text-sm">domain</span> پنل مرکز درمانی
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'pharmacist'): ?>
                            <a href="pharmacist/index.php" class="flex items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-3.5 rounded-xl text-sm font-bold shadow-md">
                                <span class="material-symbols-outlined text-sm">medication</span> پنل داروساز
                            </a>
                        <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller'): ?>
                            <a href="seller/index.php" class="flex items-center justify-center gap-2 bg-amber-600 text-white px-6 py-3.5 rounded-xl text-sm font-bold shadow-md">
                                <span class="material-symbols-outlined text-sm">storefront</span> پنل فروشنده مارکت‌پلیس
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="flex items-center justify-center gap-2 bg-secondary-container text-white px-6 py-4 rounded-xl text-sm font-bold shadow-md">
                            <span class="material-symbols-outlined">login</span> ورود / ثبت‌نام
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const panel = document.getElementById('mobile-menu-panel');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                void menu.offsetWidth;
                menu.classList.remove('opacity-0');
                panel.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
            } else {
                menu.classList.add('opacity-0');
                panel.classList.add('translate-x-full');
                document.body.style.overflow = '';
                
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 300);
            }
        }

        // Universal Live Search Autocomplete Controller
        // Universal Live Search Autocomplete Controller (Digikala Benchmark)
        function initLiveSearch(inputId, resultsId, spinnerId) {
            const input = document.getElementById(inputId);
            const resultsBox = document.getElementById(resultsId);
            const spinner = spinnerId ? document.getElementById(spinnerId) : null;
            if (!input || !resultsBox) return;

            let debounceTimer = null;
            let currentSelectedIndex = -1;

            function highlightText(text, q) {
                if (!q || !text) return text;
                const terms = q.trim().split(/\s+/).filter(t => t.length > 1);
                if (terms.length === 0) return text;
                let pattern = '(' + terms.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')';
                return text.replace(new RegExp(pattern, 'gi'), '<mark class="bg-amber-100 text-amber-900 font-bold px-0.5 rounded">$1</mark>');
            }

            input.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(debounceTimer);
                currentSelectedIndex = -1;

                if (query.length < 2) {
                    resultsBox.classList.add('hidden');
                    resultsBox.innerHTML = '';
                    if (spinner) spinner.classList.add('hidden');
                    return;
                }

                if (spinner) spinner.classList.remove('hidden');

                debounceTimer = setTimeout(() => {
                    fetch('actions/live_search.php?q=' + encodeURIComponent(query))
                        .then(res => res.json())
                        .then(data => {
                            if (spinner) spinner.classList.add('hidden');
                            if (data.status === 'success') {
                                renderLiveSearchResults(data, resultsBox, query, highlightText);
                            }
                        })
                        .catch(() => {
                            if (spinner) spinner.classList.add('hidden');
                        });
                }, 200);
            });

            // Keyboard Navigation (Arrow Up / Down / Enter / Escape)
            input.addEventListener('keydown', function(e) {
                const items = resultsBox.querySelectorAll('.live-search-item');
                if (!items.length || resultsBox.classList.contains('hidden')) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentSelectedIndex = (currentSelectedIndex + 1) % items.length;
                    updateItemHighlight(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentSelectedIndex = (currentSelectedIndex - 1 + items.length) % items.length;
                    updateItemHighlight(items);
                } else if (e.key === 'Enter') {
                    if (currentSelectedIndex >= 0 && currentSelectedIndex < items.length) {
                        e.preventDefault();
                        items[currentSelectedIndex].click();
                    }
                } else if (e.key === 'Escape') {
                    resultsBox.classList.add('hidden');
                }
            });

            function updateItemHighlight(items) {
                items.forEach((item, idx) => {
                    if (idx === currentSelectedIndex) {
                        item.classList.add('bg-slate-100', 'ring-1', 'ring-primary/20');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('bg-slate-100', 'ring-1', 'ring-primary/20');
                    }
                });
            }

            // Close on click outside
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
                    resultsBox.classList.add('hidden');
                }
            });

            input.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && resultsBox.innerHTML.trim() !== '') {
                    resultsBox.classList.remove('hidden');
                }
            });
        }

        function renderLiveSearchResults(data, container, query, highlightFn) {
            const h = highlightFn || ((t) => t);
            if (data.total === 0 && (!data.categories || data.categories.length === 0)) {
                container.innerHTML = `
                    <div class="p-8 text-center text-slate-500 text-xs">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block animate-pulse">search_off</span>
                        <div class="font-bold text-slate-700 text-sm mb-1">نتیجه‌ای برای «${query}» یافت نشد</div>
                        <p class="text-[11px] text-slate-400">املا کلمات را بررسی کنید یا عبارت دیگری را جستجو فرمایید.</p>
                        <a href="shop.php?q=${encodeURIComponent(query)}" class="inline-block mt-4 text-xs font-bold text-primary hover:underline">
                            جستجو در کل دسته‌بندی‌ها و پت‌شاپ &larr;
                        </a>
                    </div>
                `;
                container.classList.remove('hidden');
                return;
            }

            let html = '<div class="p-2 divide-y divide-slate-100 max-h-[520px] overflow-y-auto custom-scrollbar">';

            // 1. Digikala-Style Category Intent Suggestions
            if (data.categories && data.categories.length > 0) {
                html += '<div class="pb-2 pt-1">';
                html += '<div class="px-3 py-1 text-[11px] font-extrabold text-slate-500 flex items-center gap-1.5"><span class="material-symbols-outlined text-sm text-primary">category</span> دسته‌بندی‌های مرتبط</div>';
                data.categories.forEach(cat => {
                    html += `
                        <a href="${cat.url}" class="live-search-item flex items-center justify-between gap-2 px-3 py-2 rounded-xl hover:bg-slate-50 transition-colors group cursor-pointer text-right">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span class="material-symbols-outlined text-base text-secondary-container group-hover:scale-110 transition-transform">${cat.icon || 'search'}</span>
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors">
                                        جستجوی <span class="text-primary font-black font-sans">«${query}»</span> ${cat.subtitle}
                                    </div>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-xs text-slate-400 group-hover:text-primary transition-colors shrink-0">chevron_left</span>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // 2. Pet Shop Products
            if (data.results.products && data.results.products.length > 0) {
                html += '<div class="py-2.5"><div class="px-3 py-1 text-[11px] font-extrabold text-primary flex items-center gap-1"><span class="material-symbols-outlined text-sm text-secondary-container">storefront</span> پت‌شاپ و تغذیه</div>';
                data.results.products.forEach(p => {
                    const discountBadge = p.discount_percent > 0 ? `<span class="bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-black px-1.5 py-0.5 rounded-md mr-1.5">${p.discount_percent}٪ تخفیف</span>` : '';
                    const oldPriceHtml = p.old_price ? `<div class="text-[10px] text-slate-400 line-through">${p.old_price.toLocaleString('fa-IR')}</div>` : '';
                    html += `
                        <a href="${p.url}" class="live-search-item flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors group cursor-pointer">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <img src="${p.image}" class="w-11 h-11 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-200 group-hover:border-primary/40 transition-colors" alt="">
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors truncate">${h(p.title, query)}</div>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                        <span>برند: <b>${p.brand}</b></span>
                                        <span>•</span>
                                        <span>${p.category}</span>
                                        ${discountBadge}
                                    </div>
                                </div>
                            </div>
                            <div class="text-left shrink-0">
                                ${oldPriceHtml}
                                <div class="text-xs font-extrabold text-primary">${p.price.toLocaleString('fa-IR')} <span class="text-[9px] font-normal text-slate-500">تومان</span></div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // 3. Pharmacy Medicines
            if (data.results.pharmacy && data.results.pharmacy.length > 0) {
                html += '<div class="py-2.5"><div class="px-3 py-1 text-[11px] font-extrabold text-indigo-700 flex items-center gap-1"><span class="material-symbols-outlined text-sm text-indigo-600">medication</span> داروخانه تخصصی دامپزشکی</div>';
                data.results.pharmacy.forEach(m => {
                    const rxBadge = m.requires_prescription ? '<span class="bg-rose-100 text-rose-700 text-[9px] px-1.5 py-0.5 rounded font-bold mr-1">نیازمند نسخه</span>' : '';
                    const coldBadge = m.is_cold_chain ? '<span class="bg-sky-100 text-sky-700 text-[9px] px-1.5 py-0.5 rounded font-bold mr-1">زنجیره سرد</span>' : '';
                    html += `
                        <a href="${m.url}" class="live-search-item flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-indigo-50/40 transition-colors group cursor-pointer">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <img src="${m.image}" class="w-11 h-11 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-200 group-hover:border-indigo-300" alt="">
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-700 transition-colors truncate">
                                        ${h(m.title, query)} ${rxBadge} ${coldBadge}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">${m.category}</div>
                                </div>
                            </div>
                            <div class="text-left shrink-0">
                                <div class="text-xs font-extrabold text-indigo-700">${m.price.toLocaleString('fa-IR')} <span class="text-[9px] font-normal text-slate-500">تومان</span></div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            // 4. Best Doctors & Specialists (Direct Booking Bridge)
            if (data.results.doctors && data.results.doctors.length > 0) {
                html += '<div class="py-2.5"><div class="px-3 py-1 text-[11px] font-extrabold text-emerald-700 flex items-center gap-1"><span class="material-symbols-outlined text-sm text-emerald-600">stethoscope</span> پزشکان و جراحان متخصص</div>';
                data.results.doctors.forEach(d => {
                    const ratingHtml = `<span class="flex items-center gap-0.5 text-amber-500 text-[10px] font-bold"><span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1;">star</span>${d.rating}</span>`;
                    html += `
                        <div class="live-search-item flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-emerald-50/40 transition-colors group">
                            <a href="${d.profile_url}" class="flex items-center gap-3 overflow-hidden flex-1 cursor-pointer">
                                <img src="${d.image}" class="w-11 h-11 rounded-full object-cover bg-emerald-100 shrink-0 border-2 border-emerald-200" alt="">
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-800 group-hover:text-emerald-700 transition-colors flex items-center gap-1.5 truncate">
                                        <span>${h(d.name, query)}</span>
                                        ${ratingHtml}
                                    </div>
                                    <div class="text-[10px] text-slate-500 truncate max-w-[220px] mt-0.5">${h(d.specialty, query)}</div>
                                    <div class="text-[9px] text-slate-400 truncate">${d.clinic_name}</div>
                                </div>
                            </a>
                            <div class="text-left shrink-0">
                                <a href="${d.booking_url}" onclick="if(window.quickSelectDoctorForBooking){ window.quickSelectDoctorForBooking(${d.id}); return false; }" class="text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-xl transition-all shadow-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">calendar_month</span>
                                    <span>رزرو نوبت</span>
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            // 5. Clinics & Hospitals (Organizations)
            if (data.results.organizations && data.results.organizations.length > 0) {
                html += '<div class="py-2.5"><div class="px-3 py-1 text-[11px] font-extrabold text-teal-700 flex items-center gap-1"><span class="material-symbols-outlined text-sm text-teal-600">domain</span> مراکز درمانی و بیمارستان‌ها</div>';
                data.results.organizations.forEach(o => {
                    const badge247 = o.is_24_7 ? '<span class="bg-emerald-100 text-emerald-800 text-[9px] px-1.5 py-0.5 rounded-full font-black mr-1">شبانه‌روزی ۲۴/۷</span>' : '';
                    html += `
                        <a href="${o.profile_url}" class="live-search-item flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-teal-50/40 transition-colors group cursor-pointer">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <img src="${o.image}" class="w-11 h-11 rounded-xl object-cover bg-teal-50 shrink-0 border border-teal-200" alt="">
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-800 group-hover:text-teal-700 transition-colors truncate">
                                        ${h(o.name, query)} ${badge247}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">${o.city} — ${o.address}</div>
                                </div>
                            </div>
                            <div class="text-left shrink-0 flex items-center gap-1 text-amber-500 text-xs font-bold bg-amber-50 px-2 py-1 rounded-lg">
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                                <span>${o.rating}</span>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }

            html += `
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-medium">
                        تعداد کل نتایج: <b class="text-primary font-bold">${data.total}</b> مورد
                    </span>
                    <a href="shop.php?q=${encodeURIComponent(query)}" class="text-xs font-extrabold text-primary hover:text-secondary-container transition-colors flex items-center gap-1">
                        <span>مشاهده کلیه نتایج جستجو</span>
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                    </a>
                </div>
            `;

            container.innerHTML = html;
            container.classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            initLiveSearch('headerSearchInput', 'headerSearchResults', 'headerSearchSpinner');
            initLiveSearch('mobileHeaderSearchInput', 'mobileHeaderSearchResults', 'mobileHeaderSearchSpinner');
        });
    </script>
