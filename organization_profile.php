<?php
/**
 * ASENA Enterprise - Organization Public Profile
 * Comprehensive public profile for veterinary hospitals, clinics, and medical centers.
 * Features affiliated physician rosters, 1-click booking, clinic pharmacy inventory, and contact info.
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

// Facility types Persian map
$typePersian = match($org['type']) {
    'hospital' => 'بیمارستان فوق‌تخصصی دامپزشکی',
    'clinic' => 'کلینیک تخصصی و جراحی',
    'polyclinic' => 'پلی‌کلینیک دامپزشکی',
    'emergency_center' => 'مرکز اورژانس شبانه‌روزی ۲۴ ساعته',
    'diagnostic_lab' => 'آزمایشگاه و تصویربرداری تشخیصی',
    'shelter' => 'پناهگاه و نقاهتگاه حمایتی',
    default => 'مرکز درمانی دامپزشکی'
};

// SEO Metadata
$page_title = htmlspecialchars($org['name']) . ' | کادر پزشکان و نوبت‌دهی - ASENA';
$page_desc = 'اطلاعات کامل، کادر پزشکان متخصص، داروخانه داخلی و رزرو آنلاین نوبت در ' . htmlspecialchars($org['name']);

require_once __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <a href="index.php" class="hover:text-sky-600 transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <a href="organizations.php" class="hover:text-sky-600 transition-colors">مراکز درمانی و بیمارستان‌ها</a>
            <span class="material-symbols-outlined text-xs">chevron_left</span>
            <span class="text-slate-800"><?= htmlspecialchars($org['name']) ?></span>
        </nav>

        <!-- Hospital Hero Banner Card -->
        <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
            
            <!-- Banner Image or Gradient -->
            <div class="h-48 sm:h-64 bg-gradient-to-r from-sky-800 via-indigo-900 to-slate-900 relative p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between relative z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md border border-white/20">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        مرکز دارای پروانه رسمی و معتبر
                    </span>

                    <?php if (!empty($org['is_24_7'])): ?>
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-black bg-rose-500 text-white shadow-lg animate-pulse">
                            <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                            <span>بخش اورژانس ۲۴ ساعته فعال</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Info Body -->
            <div class="px-6 sm:px-10 pb-8 relative">
                <!-- Logo Floating -->
                <div class="-mt-16 sm:-mt-20 mb-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div class="flex items-end gap-4">
                        <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white shadow-xl border-4 border-white flex items-center justify-center overflow-hidden shrink-0">
                            <?php if (!empty($org['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-5xl text-sky-600">local_hospital</span>
                            <?php endif; ?>
                        </div>
                        <div class="pb-2">
                            <span class="text-xs font-bold text-sky-600 block"><?= $typePersian ?></span>
                            <h1 class="text-2xl sm:text-3xl font-black text-slate-900"><?= htmlspecialchars($org['name']) ?></h1>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5 pb-2">
                        <?php if (!empty($org['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($org['phone']) ?>" class="px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center gap-2 transition-colors">
                                <span class="material-symbols-outlined text-base">call</span>
                                <span class="dir-ltr"><?= htmlspecialchars($org['phone']) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($org['instagram'])): ?>
                            <?php 
                                $igUser = ltrim($org['instagram'], '@');
                            ?>
                            <a href="https://instagram.com/<?= htmlspecialchars($igUser) ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold text-xs flex items-center gap-2 transition-colors">
                                <span class="material-symbols-outlined text-base">photo_camera</span>
                                <span class="dir-ltr">@<?= htmlspecialchars($igUser) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($org['website'])): ?>
                            <a href="<?= htmlspecialchars($org['website']) ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-2 transition-colors">
                                <span class="material-symbols-outlined text-base">public</span>
                                <span>وب‌سایت رسمی</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bio & Facility Description -->
                <p class="text-sm text-slate-600 leading-relaxed max-w-4xl">
                    <?= nl2br(htmlspecialchars($org['description'] ?? 'این مرکز با تجهیزات تشخیصی پیشرفته و اتاق‌های جراحی مجهز به بیهوشی استنشاقی، آماده ارائه خدمات همه‌جانبه سلامت به کلیه حیوانات خانگی و پرندگان زینتی می‌باشد.')) ?>
                </p>

                <!-- Facility Highlights Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100 text-xs text-slate-600">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">location_on</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">موقعیت و نشانی:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($org['city']) ?>، <?= htmlspecialchars($org['address'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">schedule</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">ساعات کاری و پذیرش:</span>
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($org['operating_hours'] ?? '۹:۰۰ الی ۲۲:۰۰') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">hotel_class</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">امتیاز مراجعین:</span>
                            <span class="font-black text-slate-900"><?= number_format((float)($org['rating'] ?? 5.0), 1) ?> از ۵ (رضایت عالی)</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- SECTION 1: AFFILIATED DOCTORS ROSTER -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600 text-2xl">stethoscope</span>
                        <span>کادر پزشکان و متخصصین همکار</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">امکان انتخاب پزشک مورد نظر و رزرو مستقیم نوبت ویزیت حضوری یا آنلاین</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">
                    <?= count($doctors) ?> پزشک متخصص
                </span>
            </div>

            <?php if (empty($doctors)): ?>
                <div class="bg-white rounded-2xl p-8 text-center border border-slate-200">
                    <p class="text-xs text-slate-500">لیست پزشکان همکار این مرکز به زودی تکمیل خواهد شد.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($doctors as $doc): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition-all flex flex-col justify-between space-y-4">
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3.5">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-indigo-600 font-bold overflow-hidden shrink-0">
                                    <?php if (!empty($doc['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($doc['avatar_url']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-2xl">person</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="font-black text-slate-900 text-sm truncate"><?= htmlspecialchars($doc['name']) ?></h3>
                                        <?php if (!empty($doc['is_head_physician'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 shrink-0">رییس بخش</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-indigo-600 font-bold block mt-0.5 truncate"><?= htmlspecialchars($doc['specialty']) ?></span>
                                    <div class="flex items-center gap-1 text-[11px] text-amber-500 font-black mt-1">
                                        <span class="material-symbols-outlined text-xs">star</span>
                                        <span><?= number_format((float)($doc['rating'] ?? 5.0), 1) ?></span>
                                        <span class="text-slate-400 font-normal">(<?= (int)($doc['review_count'] ?? 0) ?> نظر)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Working Hours & Schedule -->
                            <div class="bg-slate-50 p-2.5 rounded-xl text-[11px] text-slate-600 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">روزهای حضور:</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_days'] ?? 'شنبه تا چهارشنبه') ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">ساعت ویزیت:</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['working_hours'] ?? '۱۶ الی ۲۱') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 1-Click Action Buttons -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <a href="booking.php?doctor_id=<?= (int)$doc['id'] ?>" class="w-full py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all">
                                <span class="material-symbols-outlined text-base">calendar_month</span>
                                <span>رزرو نوبت با این پزشک</span>
                            </a>
                            <a href="doctor_profile.php?id=<?= (int)$doc['id'] ?>" class="w-full py-2 px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-colors">
                                <span>مشاهده پروفایل و سوابق</span>
                                <span class="material-symbols-outlined text-sm">arrow_back</span>
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECTION 2: CLINIC PHARMACY & INVENTORY -->
        <?php if (!empty($inventory)): ?>
        <div class="space-y-4 pt-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-teal-600 text-2xl">medication</span>
                        <span>داروخانه و محصولات موجود در مرکز</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">داروهای تخصصی و غذاهای درمانی آماده تحویل حضوری یا ارسال سریع</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($inventory as $item): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-3.5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="h-28 rounded-xl bg-slate-50 mb-2.5 flex items-center justify-center overflow-hidden">
                            <?php if (!empty($item['item_image'])): ?>
                                <img src="<?= htmlspecialchars($item['item_image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-3xl text-teal-600">vaccines</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] text-teal-700 font-bold bg-teal-50 px-2 py-0.5 rounded-full inline-block mb-1">
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
                        <a href="pharmacy.php" class="p-1 rounded-lg bg-slate-100 hover:bg-teal-600 hover:text-white transition-colors" title="استعلام نسخه یا خرید">
                            <span class="material-symbols-outlined text-sm">shopping_cart</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION 3: CLINICAL DEPARTMENTS & FACILITIES -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <h3 class="text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600 text-xl">medical_information</span>
                <span>امکانات و بخش‌های تخصصی مرکز</span>
            </h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 text-center">
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-sky-600 mb-1">vital_signs</span>
                    <span class="text-xs font-bold text-slate-800 block">بخش مراقبت ویژه (ICU)</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-indigo-600 mb-1">healing</span>
                    <span class="text-xs font-bold text-slate-800 block">اتاق عمل جراحی مجهز</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-teal-600 mb-1">biotech</span>
                    <span class="text-xs font-bold text-slate-800 block">آزمایشگاه بیوشیمی و خون</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-amber-600 mb-1">radiology</span>
                    <span class="text-xs font-bold text-slate-800 block">رادیولوژی و سونوگرافی</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-rose-600 mb-1">e911_emergency</span>
                    <span class="text-xs font-bold text-slate-800 block">پذیرش اورژانس شبانه‌روزی</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-2xl text-purple-600 mb-1">hotel</span>
                    <span class="text-xs font-bold text-slate-800 block">پانسیون و بستری نظارت‌شده</span>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
