<?php
/**
 * ASENA Enterprise - Organization Public Profile
 * Comprehensive public profile for veterinary hospitals, clinics, and medical centers.
 * Features affiliated physician rosters, 1-click booking, clinic pharmacy inventory, verified client reviews, and direct routing.
 * Version: 2.0.0
 */

$current_page = 'organization_profile.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';

$orgService = App::organization();

$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

$org = null;
if (!empty($slug)) {
    $org = $orgService->getBySlug($slug);
} elseif ($id > 0) {
    $org = $orgService->getById($id);
}

if (!$org) {
    header("Location: organizations.php");
    exit;
}

$orgId = (int)$org['id'];
$doctors   = $orgService->getDoctors($orgId);
$inventory = $orgService->getInventory($orgId);
$reviews   = $orgService->getReviews($orgId);
$isOpen    = $orgService->isOpenNow($org['operating_hours'] ?? '', (int)$org['is_24_7']);
$isTop5    = App::leaderboard()->isTopOrganization($orgId);

// Facility types Persian map
$typePersian = match($org['type']) {
    'hospital' => 'بیمارستان فوق‌تخصصی دامپزشکی',
    'clinic' => 'کلینیک تخصصی و جراحی',
    'pharmacy' => 'داروخانه تخصصی دامپزشکی',
    'shelter_charity' => 'پناهگاه و نقاهتگاه حمایتی حیوانات',
    'emergency_center' => 'مرکز اورژانس شبانه‌روزی ۲۴ ساعته',
    'diagnostic_lab' => 'آزمایشگاه و تصویربرداری تشخیصی',
    default => 'مرکز درمانی دامپزشکی'
};

// Parse facilities
$facilitiesList = !empty($org['facilities']) ? array_map('trim', explode(',', $org['facilities'])) : [];

// Dynamic Rich SEO, Local GEO & Profile Metadata
$orgCity = htmlspecialchars($org['city'] ?? 'تهران');
$orgProv = htmlspecialchars($org['province'] ?? 'تهران');
$orgName = htmlspecialchars($org['name']);
$lat = !empty($org['latitude']) ? $org['latitude'] : '35.7350';
$lng = !empty($org['longitude']) ? $org['longitude'] : '51.4110';

$page_title = "{$orgName} ({$typePersian}) | نوبت‌دهی و نشانی در {$orgCity} - آسنا";
$page_description = "اطلاعات کامل، آدرس دقیق، لوکیشن نقشه، کادر پزشکان متخصص، داروخانه داخلی و رزرو آنلاین نوبت در {$orgName} ({$typePersian}) واقع در {$orgProv}، {$orgCity}.";
$og_image = !empty($org['banner_url']) ? $org['banner_url'] : (!empty($org['logo_url']) ? $org['logo_url'] : 'assets/images/og-asena.png');
$og_type = 'business.business';

// Dynamic Geographic Meta Tags for Local Pack Ranking
$geo_region = 'IR-07';
$geo_placename = "{$orgCity}, Iran";
$geo_position = "{$lat};{$lng}";
$geo_icbm = "{$lat}, {$lng}";

require_once __DIR__ . '/includes/header.php';
?>

<!-- Schema.org Structured Data for Google Rich Results (VeterinaryCare & Local Pack) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VeterinaryCare",
  "name": "<?= addslashes($org['name']) ?>",
  "description": "<?= addslashes($org['description'] ?? '') ?>",
  "telephone": "<?= addslashes($org['phone'] ?? '+98-914-667-6978') ?>",
  "priceRange": "$$",
  "currenciesAccepted": "IRR",
  "paymentAccepted": "Cash, Credit Card, Online",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= addslashes($org['address'] ?? '') ?>",
    "addressLocality": "<?= addslashes($org['city'] ?? 'تهران') ?>",
    "addressRegion": "<?= addslashes($org['province'] ?? 'تهران') ?>",
    "addressCountry": "IR"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": <?= (float)$lat ?>,
    "longitude": <?= (float)$lng ?>
  },
  "hasMap": "https://www.google.com/maps/dir/?api=1&destination=<?= $lat ?>,<?= $lng ?>",
  "openingHours": "<?= !empty($org['is_24_7']) ? 'Mo-Su 00:00-24:00' : 'Mo-Sa 08:00-22:00' ?>",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= number_format((float)($org['rating'] ?? 5.0), 1) ?>",
    "reviewCount": "<?= (int)($org['review_count'] ?? 1) ?>"
  }
}
</script>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <a href="index.php" class="hover:text-sky-600 transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <a href="organizations.php" class="hover:text-sky-600 transition-colors">مراکز درمانی و بیمارستان‌ها</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <span class="text-slate-800 font-black"><?= htmlspecialchars($org['name']) ?></span>
        </nav>

        <!-- Hospital Hero Banner Card -->
        <div class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden shadow-sm">
            
            <!-- Banner Image or Gradient Atmosphere -->
            <div class="h-48 sm:h-64 bg-gradient-to-r from-sky-950 via-indigo-950 to-slate-900 relative p-6 flex flex-col justify-between overflow-hidden bg-cover bg-center" style="<?= !empty($org['banner_url']) ? "background-image: url('" . htmlspecialchars($org['banner_url']) . "');" : '' ?>">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:20px_20px] <?= !empty($org['banner_url']) ? 'bg-black/50' : '' ?>"></div>

                <div class="flex items-center justify-between relative z-10">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md border border-white/20 shadow-sm">
                            <span class="material-symbols-outlined text-sm text-sky-300">verified</span>
                            <span>مرکز دارای پروانه رسمی و تاییدشده</span>
                        </span>

                        <?php if ($isTop5): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-black bg-gradient-to-r from-amber-400 to-yellow-300 text-slate-950 border border-amber-300 shadow-md animate-pulse">
                                <span class="material-symbols-outlined text-sm">military_tech</span>
                                <span>جزو ۵ مرکز برتر کشور</span>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($org['license_number'])): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-black/30 text-sky-200 backdrop-blur-md border border-white/10">
                                نظام دامپزشکی: <?= htmlspecialchars($org['license_number']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if (!empty($org['is_24_7'])): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-black bg-rose-500 text-white shadow-lg animate-pulse">
                                <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                                <span>بخش اورژانس ۲۴ ساعته فعال</span>
                            </span>
                        <?php elseif ($isOpen): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-white shadow-md">
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                <span>الان باز است</span>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-700 text-slate-200 shadow-md">
                                <span>اکنون بسته است</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Info Body -->
            <div class="px-6 sm:px-10 pb-8 relative">
                <!-- Logo & Heading Strip -->
                <div class="mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4 sm:gap-6">
                        <div class="-mt-14 sm:-mt-18 w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white shadow-xl border-4 border-white flex items-center justify-center overflow-hidden shrink-0 z-20 p-2">
                            <?php if (!empty($org['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="w-full h-full object-contain">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-5xl text-sky-600">local_hospital</span>
                            <?php endif; ?>
                        </div>
                        <div class="pt-2">
                            <span class="text-xs font-bold text-sky-600 block"><?= $typePersian ?></span>
                            <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                <h1 class="text-2xl sm:text-3xl font-black text-slate-900"><?= htmlspecialchars($org['name']) ?></h1>
                                <?php if ($isTop5): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-black bg-gradient-to-r from-amber-400 to-yellow-300 text-slate-950 border border-amber-400 shadow-sm" title="جزو ۵ مرکز برتر کشور">
                                        <span class="material-symbols-outlined text-sm">trophy</span>
                                        <span>مرکز برگزیده کشور</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($org['manager_name'])): ?>
                                <span class="text-xs text-slate-500 font-medium block mt-1">
                                    مدیریت و مسئول فنی: <strong class="text-slate-700"><?= htmlspecialchars($org['manager_name']) ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Direct Contact & Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2 pb-2">
                        <?php if (!empty($org['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($org['phone']) ?>" class="px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center gap-2 transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base">call</span>
                                <span class="dir-ltr"><?= htmlspecialchars($org['phone']) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($org['emergency_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($org['emergency_phone']) ?>" class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-black text-xs flex items-center gap-2 transition-colors shadow-sm animate-pulse">
                                <span class="material-symbols-outlined text-base">e911_emergency</span>
                                <span>خط اورژانس: <span class="dir-ltr"><?= htmlspecialchars($org['emergency_phone']) ?></span></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($org['instagram'])): 
                            $igUser = ltrim($org['instagram'], '@');
                        ?>
                            <a href="https://instagram.com/<?= htmlspecialchars($igUser) ?>" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold text-xs flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-base">photo_camera</span>
                                <span class="dir-ltr">@<?= htmlspecialchars($igUser) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($org['website'])): ?>
                            <a href="<?= htmlspecialchars($org['website']) ?>" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-base">public</span>
                                <span>وب‌سایت</span>
                            </a>
                        <?php endif; ?>

                        <a href="#tab-location" onclick="switchTab('tab-location')" class="px-3.5 py-2.5 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold text-xs flex items-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-base">near_me</span>
                            <span>مسیریابی</span>
                        </a>
                    </div>
                </div>

                <!-- Bio & Description -->
                <p class="text-sm text-slate-600 leading-relaxed max-w-4xl">
                    <?= nl2br(htmlspecialchars($org['description'] ?? 'این مرکز با تجهیزات تشخیصی پیشرفته و اتاق‌های جراحی مجهز به بیهوشی استنشاقی، آماده ارائه خدمات همه‌جانبه سلامت به کلیه حیوانات خانگی و پرندگان زینتی می‌باشد.')) ?>
                </p>

                <!-- Facility Highlights Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100 text-xs text-slate-600">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-xl">location_on</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">موقعیت و نشانی:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($org['city']) ?>، <?= htmlspecialchars($org['address'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-xl">schedule</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">ساعات پذیرش و کارکرد:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($org['operating_hours'] ?? '۹:۰۰ الی ۲۲:۰۰') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-xl">hotel_class</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">امتیاز و رضایت مراجعین:</span>
                            <span class="font-black text-slate-900"><?= number_format((float)($org['rating'] ?? 5.0), 1) ?> از ۵ (<?= count($reviews) ?> نظر ثبت شده)</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Interactive Tabs Bar -->
        <div class="bg-white rounded-2xl p-2 border border-slate-200/90 shadow-sm flex items-center gap-2 overflow-x-auto scrollbar-none">
            <button type="button" onclick="switchTab('tab-doctors')" id="btn-tab-doctors" class="tab-btn active px-5 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 transition-all bg-sky-600 text-white shadow-sm">
                <span class="material-symbols-outlined text-base">stethoscope</span>
                <span>کادر پزشکان و متخصصین</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20"><?= count($doctors) ?></span>
            </button>

            <button type="button" onclick="switchTab('tab-facilities')" id="btn-tab-facilities" class="tab-btn px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all text-slate-600 hover:bg-slate-100">
                <span class="material-symbols-outlined text-base">medical_information</span>
                <span>امکانات و بخش‌های درمانی</span>
            </button>

            <?php if (!empty($inventory)): ?>
                <button type="button" onclick="switchTab('tab-pharmacy')" id="btn-tab-pharmacy" class="tab-btn px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all text-slate-600 hover:bg-slate-100">
                    <span class="material-symbols-outlined text-base">medication</span>
                    <span>داروخانه اختصاصی مرکز</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700"><?= count($inventory) ?></span>
                </button>
            <?php endif; ?>

            <button type="button" onclick="switchTab('tab-reviews')" id="btn-tab-reviews" class="tab-btn px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all text-slate-600 hover:bg-slate-100">
                <span class="material-symbols-outlined text-base">rate_review</span>
                <span>نظرات مراجعین و ثبت نظر</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700"><?= count($reviews) ?></span>
            </button>

            <button type="button" onclick="switchTab('tab-location')" id="btn-tab-location" class="tab-btn px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all text-slate-600 hover:bg-slate-100">
                <span class="material-symbols-outlined text-base">map</span>
                <span>موقعیت، ساعات کاری و نقشه</span>
            </button>
        </div>

        <!-- TAB 1: DOCTORS ROSTER -->
        <div id="tab-doctors" class="tab-content space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600 text-2xl">stethoscope</span>
                        <span>کادر پزشکان و جراحان همکار در این مرکز</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">امکان انتخاب پزشک مورد نظر و رزرو مستقیم نوبت ویزیت حضوری یا مشاوره آنلاین</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">
                    <?= count($doctors) ?> پزشک متخصص
                </span>
            </div>

            <?php if (empty($doctors)): ?>
                <div class="bg-white rounded-3xl p-10 text-center border border-slate-200">
                    <p class="text-xs text-slate-500">لیست پزشکان همکار این مرکز به زودی تکمیل خواهد شد.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($doctors as $doc): ?>
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all flex flex-col justify-between space-y-4 group">
                        
                        <div class="space-y-3.5">
                            <div class="flex items-start gap-3.5">
                                <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold overflow-hidden shrink-0 shadow-inner">
                                    <?php if (!empty($doc['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($doc['image_url']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-3xl">person</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="font-black text-slate-900 text-sm truncate group-hover:text-indigo-600 transition-colors">
                                            <?= htmlspecialchars($doc['name']) ?>
                                        </h3>
                                        <?php if (!empty($doc['is_head_physician'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 shrink-0">رییس بخش</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-indigo-600 font-bold block mt-0.5 truncate leading-snug"><?= htmlspecialchars($doc['specialty']) ?></span>
                                    <div class="flex items-center gap-1 text-[11px] text-amber-500 font-black mt-1">
                                        <span class="material-symbols-outlined text-xs">star</span>
                                        <span><?= number_format((float)($doc['rating'] ?? 5.0), 1) ?></span>
                                        <span class="text-slate-400 font-normal">(<?= (int)($doc['review_count'] ?? 0) ?> نظر)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Working Hours & Schedule -->
                            <div class="bg-slate-50 p-3 rounded-2xl text-[11px] text-slate-600 space-y-1.5 border border-slate-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">calendar_today</span>
                                        <span>روزهای حضور:</span>
                                    </span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_days'] ?? 'شنبه تا چهارشنبه') ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">schedule</span>
                                        <span>ساعت ویزیت:</span>
                                    </span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_hours'] ?? '۱۶ الی ۲۱') ?></span>
                                </div>
                                <?php if (!empty($doc['price'])): ?>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-400">تعرفه ویزیت:</span>
                                    <span class="font-black text-slate-900"><?= number_format((float)$doc['price']) ?> تومان</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 1-Click Action Buttons -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <a href="booking.php?doctor_id=<?= (int)$doc['id'] ?>" class="w-full py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm shadow-indigo-600/20 transition-all">
                                <span class="material-symbols-outlined text-base">calendar_month</span>
                                <span>رزرو نوبت با این پزشک</span>
                            </a>
                            <a href="doctor_profile.php?id=<?= (int)$doc['id'] ?>" class="w-full py-2 px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-colors">
                                <span>مشاهده رزومه و سوابق پزشک</span>
                                <span class="material-symbols-outlined text-sm">arrow_back</span>
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: CLINICAL DEPARTMENTS & FACILITIES -->
        <div id="tab-facilities" class="tab-content hidden space-y-6">
            <div>
                <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600 text-2xl">medical_information</span>
                    <span>امکانات و بخش‌های تخصصی مرکز</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">تجهیزات مدرن تشخیصی، دستگاه‌های بیهوشی پیشرفته و محیط‌های ایزوله بیمارستان</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">vital_signs</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">بخش مراقبت‌های ویژه (ICU)</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            مجهز به انکوباتورهای تامین اکسیژن، گرمکن اتوماتیک و پایش ممتد علائم حیاتی برای بیماران پس از عمل یا بیماران با تروما.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">healing</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">اتاق عمل جراحی استریل</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            دستگاه بیهوشی استنشاقی ایزوفلوران، ونتیلاتور مکانیکی، الکتروکوتر و ست‌های کامل جراحی ارتوپدی و بافت نرم.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">biotech</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">آزمایشگاه بیوشیمی و هماتولوژی</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            انجام آزمایش‌های اورژانسی CBC، پنل بیوشیمی کبد و کلیه، گازهای خونی و کیت‌های تشخیص سریع در کمتر از ۳۰ دقیقه.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">radiology</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">رادیولوژی دیجیتال و سونوگرافی</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            رادیوگرافی با دوز پرتوی حداقل و سونوگرافی کالر داپلر پیشرفته جهت بررسی اندام‌های شکمی و اکوکاردیوگرافی قلب.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">e911_emergency</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">پذیرش اورژانس شبانه‌روزی ۲۴ ساعته</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            پزشک مقیم و پرستار در کلیه ساعات شبانه‌روز و ایام تعطیل جهت مسمومیت‌ها، شکستگی‌ها و زایمان‌های پرخطر.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">hotel</span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-black text-sm text-slate-900">بستری و پانسیون نظارت‌شده</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            فضای تفکیک‌شده برای سگ و گربه جهت جلوگیری از استرس صوتی و چشمی با تغذیه درمانی و تهویه هپا (HEPA).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: CLINIC PHARMACY & INVENTORY -->
        <?php if (!empty($inventory)): ?>
        <div id="tab-pharmacy" class="tab-content hidden space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-teal-600 text-2xl">medication</span>
                        <span>داروخانه و اقلام موجود در مرکز</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">داروهای تخصصی، واکسن‌های زنجیره سرد و رژیم‌های درمانی آماده تحویل حضوری یا ارسال سریع</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($inventory as $item): ?>
                <div class="bg-white rounded-3xl border border-slate-200 p-3.5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                    <div>
                        <div class="h-28 rounded-2xl bg-slate-50 mb-2.5 flex items-center justify-center overflow-hidden border border-slate-100">
                            <?php if (!empty($item['item_image'])): ?>
                                <img src="<?= htmlspecialchars($item['item_image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-3xl text-teal-600">vaccines</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[9px] text-teal-700 font-black bg-teal-50 px-2 py-0.5 rounded-md inline-block mb-1">
                            <?= htmlspecialchars($item['item_category'] ?? 'داروی تخصصی') ?>
                        </span>
                        <h4 class="text-xs font-bold text-slate-900 line-clamp-2 leading-snug">
                            <?= htmlspecialchars($item['item_name']) ?>
                        </h4>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-black text-slate-900">
                            <?= number_format((float)$item['effective_price']) ?> تومان
                        </span>
                        <a href="pharmacy.php" class="p-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white transition-colors" title="خرید یا استعلام نسخه">
                            <span class="material-symbols-outlined text-sm">shopping_cart</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 4: REVIEWS & FEEDBACK -->
        <div id="tab-reviews" class="tab-content hidden space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left: Overall Rating & Review Submit Form -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- Rating Card -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center space-y-3">
                        <span class="text-4xl font-black text-slate-900 block"><?= number_format((float)($org['rating'] ?? 5.0), 1) ?></span>
                        <div class="flex items-center justify-center gap-1 text-amber-500">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="material-symbols-outlined text-xl">star</span>
                            <?php endfor; ?>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">
                            بر اساس <strong><?= count($reviews) ?> نظر ثبت‌شده</strong> توسط مراجعین تاییدشده
                        </p>
                    </div>

                    <!-- Review Form -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                        <div>
                            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-sky-600 text-lg">rate_review</span>
                                <span>ثبت نظر و تجربه مراجعه به مرکز</span>
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-1">تجربه شما به بهبود کیفیت خدمات و راهنمایی سایر سرپرستان پت کمک می‌کند.</p>
                        </div>

                        <form id="reviewForm" class="space-y-3.5">
                            <input type="hidden" name="action" value="submit_review">
                            <input type="hidden" name="organization_id" value="<?= $orgId ?>">

                            <!-- Rating Select Stars -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">امتیاز شما به مرکز درمانی:</label>
                                <div class="flex items-center gap-1 text-amber-400 cursor-pointer" id="starRatingSelector">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <span class="material-symbols-outlined text-2xl hover:scale-110 transition-transform star-btn" data-val="<?= $s ?>">star</span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="selectedRating" value="5">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">نام شما</label>
                                <input type="text" name="reviewer_name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" placeholder="مثال: مریم محمدی" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs focus:border-sky-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">متن نظر و تجربه شما</label>
                                <textarea name="comment" rows="3" required placeholder="توضیح دهید نحوه برخورد کادر، مهارت پزشک، بهداشت محیط و زمان انتظار چگونه بود..." class="w-full p-3 rounded-xl border border-slate-300 text-xs focus:border-sky-500"></textarea>
                            </div>

                            <button type="submit" id="submitReviewBtn" class="w-full py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm transition-all">
                                <span>ثبت و انتشار نظر</span>
                                <span class="material-symbols-outlined text-sm">send</span>
                            </button>

                            <div id="reviewAlert" class="hidden p-3 rounded-xl text-xs font-bold"></div>
                        </form>
                    </div>

                </div>

                <!-- Right: List of Reviews -->
                <div class="lg:col-span-7 space-y-4">
                    <h3 class="font-black text-slate-900 text-base">دیدگاه‌ها و تجارب ثبت‌شده</h3>

                    <?php if (empty($reviews)): ?>
                        <div class="bg-white rounded-3xl p-10 text-center border border-slate-200">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">chat_bubble_outline</span>
                            <p class="text-xs text-slate-500">هنوز نظری برای این مرکز ثبت نشده است. اولین نفری باشید که نظر می‌دهد!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3.5" id="reviewsContainer">
                            <?php foreach ($reviews as $rev): ?>
                            <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-full bg-sky-50 text-sky-700 flex items-center justify-center font-black text-xs">
                                            <?= mb_substr($rev['user_name'], 0, 1, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-slate-900 block"><?= htmlspecialchars($rev['user_name']) ?></span>
                                            <span class="text-[10px] text-emerald-600 flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-xs">verified</span>
                                                <span>مراجعه‌کننده تاییدشده</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 text-amber-400 text-xs">
                                        <?php for ($r = 1; $r <= (int)$rev['rating']; $r++): ?>
                                            <span class="material-symbols-outlined text-sm">star</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- TAB 5: LOCATION & WORKING HOURS -->
        <div id="tab-location" class="tab-content hidden space-y-6">
            <div>
                <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sky-600 text-2xl">map</span>
                    <span>ساعات کاری، اطلاعات دسترسی و مسیریابی</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">برنامه زمانی حضور پزشکان، تلفن‌های تماس و هدایت به نقشه</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Working Hours Table -->
                <div class="lg:col-span-6 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">schedule</span>
                        <span>جدول ساعات کاری و پذیرش</span>
                    </h3>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                            <span class="font-bold text-slate-700">شنبه تا چهارشنبه</span>
                            <span class="text-slate-900 font-bold"><?= htmlspecialchars($org['operating_hours'] ?? '۹ الی ۲۲') ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                            <span class="font-bold text-slate-700">پنجشنبه‌ها</span>
                            <span class="text-slate-900 font-bold"><?= htmlspecialchars($org['operating_hours'] ?? '۹ الی ۲۱') ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl <?= !empty($org['is_24_7']) ? 'bg-rose-50 text-rose-800' : 'bg-slate-50' ?>">
                            <span class="font-bold">جمعه‌ها و ایام تعطیل</span>
                            <span class="font-bold"><?= !empty($org['is_24_7']) ? 'اورژانس ۲۴ ساعته باز است' : 'با هماهنگی قبلی تلفنی' ?></span>
                        </div>
                    </div>

                    <?php if (!empty($org['emergency_phone'])): ?>
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-rose-700 text-xs font-bold">
                                <span class="material-symbols-outlined text-xl">e911_emergency</span>
                                <span>خط اضطراری شبانه‌روزی</span>
                            </div>
                            <a href="tel:<?= htmlspecialchars($org['emergency_phone']) ?>" class="px-3.5 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-black dir-ltr">
                                <?= htmlspecialchars($org['emergency_phone']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation & Map Routing -->
                <div class="lg:col-span-6 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
                    <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">near_me</span>
                        <span>مسیریابی و آدرس دقیق</span>
                    </h3>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <span class="text-xs text-slate-400 block font-bold">نشانی مرکز:</span>
                        <p class="text-xs sm:text-sm font-bold text-slate-800 leading-relaxed">
                            <?= htmlspecialchars($org['province'] ?? 'تهران') ?>، <?= htmlspecialchars($org['city']) ?>، <?= htmlspecialchars($org['address'] ?? '') ?>
                        </p>
                    </div>

                    <!-- Direct Navigation Apps Buttons -->
                    <?php 
                        $lat = $org['latitude'] ?? '35.7350';
                        $lng = $org['longitude'] ?? '51.4110';
                        $neshanUrl = "https://neshan.org/maps/@{$lat},{$lng},15z";
                        $gmapsUrl  = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
                        $wazeUrl   = "https://waze.com/ul?ll={$lat},{$lng}&navigate=yes";
                    ?>
                    <div>
                        <span class="text-xs font-bold text-slate-600 block mb-2.5">انتخاب نرم‌افزار مسیریاب:</span>
                        <div class="grid grid-cols-3 gap-3">
                            <a href="<?= htmlspecialchars($neshanUrl) ?>" target="_blank" class="py-3 px-2 rounded-2xl bg-slate-100 hover:bg-sky-600 hover:text-white text-slate-800 text-xs font-bold flex flex-col items-center justify-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-lg">navigation</span>
                                <span>نشان (Neshan)</span>
                            </a>
                            <a href="<?= htmlspecialchars($gmapsUrl) ?>" target="_blank" class="py-3 px-2 rounded-2xl bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-800 text-xs font-bold flex flex-col items-center justify-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-lg">map</span>
                                <span>گوگل مپ</span>
                            </a>
                            <a href="<?= htmlspecialchars($wazeUrl) ?>" target="_blank" class="py-3 px-2 rounded-2xl bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-800 text-xs font-bold flex flex-col items-center justify-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-lg">directions_car</span>
                                <span>ویز (Waze)</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
// Tab switcher logic
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById(tabId);
    if (target) {
        target.classList.remove('hidden');
    }

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-sky-600', 'text-white', 'shadow-sm');
        btn.classList.add('text-slate-600');
    });

    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.add('bg-sky-600', 'text-white', 'shadow-sm');
        activeBtn.classList.remove('text-slate-600');
    }
}

// Star rating interactive selector
document.querySelectorAll('.star-btn').forEach(star => {
    star.addEventListener('click', function() {
        const val = parseInt(this.getAttribute('data-val'));
        document.getElementById('selectedRating').value = val;
        
        document.querySelectorAll('.star-btn').forEach(s => {
            const sVal = parseInt(s.getAttribute('data-val'));
            if (sVal <= val) {
                s.textContent = 'star';
                s.classList.add('text-amber-400');
                s.classList.remove('text-slate-300');
            } else {
                s.textContent = 'star';
                s.classList.remove('text-amber-400');
                s.classList.add('text-slate-300');
            }
        });
    });
});

// Review AJAX Submission
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitReviewBtn');
        const alert = document.getElementById('reviewAlert');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span><span>در حال ثبت...</span>';

        try {
            const formData = new FormData(reviewForm);
            const res = await fetch('actions/organization_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            alert.classList.remove('hidden', 'bg-rose-50', 'text-rose-700', 'bg-emerald-50', 'text-emerald-700');
            if (data.success) {
                alert.classList.add('bg-emerald-50', 'text-emerald-700');
                alert.textContent = data.message;
                reviewForm.reset();
                setTimeout(() => location.reload(), 1500);
            } else {
                alert.classList.add('bg-rose-50', 'text-rose-700');
                alert.textContent = data.message || 'خطایی رخ داد.';
                btn.disabled = false;
                btn.innerHTML = '<span>ثبت و انتشار نظر</span><span class="material-symbols-outlined text-sm">send</span>';
            }
        } catch (err) {
            alert.classList.remove('hidden');
            alert.classList.add('bg-rose-50', 'text-rose-700');
            alert.textContent = 'خطا در برقراری ارتباط با سرور.';
            btn.disabled = false;
            btn.innerHTML = '<span>ثبت و انتشار نظر</span><span class="material-symbols-outlined text-sm">send</span>';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
