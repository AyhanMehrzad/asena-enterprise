<?php
/**
 * ASENA Enterprise - Veterinary Organizations & Clinics Directory
 * Comprehensive directory of verified hospitals, emergency centers, polyclinics, and specialty surgeries.
 */

$current_page = 'organizations.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';

$orgService = App::organization();

// Filter inputs
$filters = [
    'q'       => trim($_GET['q'] ?? ''),
    'city'    => trim($_GET['city'] ?? ''),
    'type'    => trim($_GET['type'] ?? ''),
    'is_24_7' => isset($_GET['is_24_7']) && $_GET['is_24_7'] == '1' ? 1 : 0
];

$organizations = $orgService->getOrganizations($filters);
$activeCities  = $orgService->getActiveCities();

// Page SEO Metadata
$page_title = 'مراکز درمانی، بیمارستان‌ها و کلینیک‌های دامپزشکی | ASENA';
$page_desc = 'دایرکتوری جامع مراکز درمانی و بیمارستان‌های تخصصی دامپزشکی کشور با امکان رزرو آنلاین نوبت با پزشکان همکار و خدمات اورژانس ۲۴ ساعته.';

require_once __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Hero Header -->
        <div class="bg-gradient-to-r from-sky-900 via-indigo-900 to-slate-900 rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -left-10 -bottom-10 w-72 h-72 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-bold mb-4 backdrop-blur-sm border border-sky-400/20">
                    <span class="material-symbols-outlined text-sm">local_hospital</span>
                    شبکه رسمی درمانگاه‌ها و بیمارستان‌های دامپزشکی
                </span>
                <h1 class="text-3xl sm:text-4xl font-black mb-3 leading-tight">مراکز درمانی و بیمارستان‌های تخصصی پت</h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    یافتن معتبرترین مراکز جراحی، آزمایشگاه‌های تشخیصی و بیمارستان‌های شبانه‌روزی با کادر متخصص، نوبت‌دهی آنلاین و داروخانه اختصاصی.
                </p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80">
            <form method="GET" action="organizations.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
                
                <!-- Search Query -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">جستجوی نام مرکز یا نشانی</label>
                    <div class="relative">
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="مثال: بیمارستان پایتخت، کلینیک پرشین، ونک..." class="w-full h-11 pr-10 pl-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                    </div>
                </div>

                <!-- City Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">شهر</label>
                    <select name="city" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs bg-white">
                        <option value="">همه شهرها</option>
                        <?php foreach ($activeCities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= $filters['city'] === $city ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">نوع مرکز</label>
                    <select name="type" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs bg-white">
                        <option value="">همه مراکز درمانی</option>
                        <option value="hospital" <?= $filters['type'] === 'hospital' ? 'selected' : '' ?>>بیمارستان فوق‌تخصصی</option>
                        <option value="clinic" <?= $filters['type'] === 'clinic' ? 'selected' : '' ?>>کلینیک تخصصی و جراحی</option>
                        <option value="polyclinic" <?= $filters['type'] === 'polyclinic' ? 'selected' : '' ?>>پلی‌کلینیک درمانی</option>
                        <option value="emergency_center" <?= $filters['type'] === 'emergency_center' ? 'selected' : '' ?>>اورژانس شبانه‌روزی ۲۴ ساعته</option>
                        <option value="diagnostic_lab" <?= $filters['type'] === 'diagnostic_lab' ? 'selected' : '' ?>>آزمایشگاه و تصویربرداری</option>
                        <option value="shelter" <?= $filters['type'] === 'shelter' ? 'selected' : '' ?>>پناهگاه و نقاهتگاه</option>
                    </select>
                </div>

                <!-- Actions: 24/7 & Submit -->
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 border border-slate-200 h-11 px-3 rounded-xl flex-1 justify-center">
                        <input type="checkbox" name="is_24_7" value="1" <?= $filters['is_24_7'] ? 'checked' : '' ?> class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-700">اورژانس ۲۴ ساعته</span>
                    </label>
                    <button type="submit" class="h-11 px-5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 shadow-sm transition-all shrink-0">
                        <span>اعمال</span>
                        <span class="material-symbols-outlined text-base">filter_alt</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Directory Grid -->
        <?php if (empty($organizations)): ?>
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl">domain_disabled</span>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">مرکز درمانی با این مشخصات یافت نشد</h3>
                <p class="text-xs text-slate-500 mb-4">لطفاً فیلترهای جستجو را تغییر دهید یا نام شهر دیگری را انتخاب نمایید.</p>
                <a href="organizations.php" class="inline-block px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                    پاک کردن همه فیلترها
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($organizations as $org): 
                    $docList = $orgService->getDoctors((int)$org['id']);
                    $docCount = count($docList);
                    $typePersian = match($org['type']) {
                        'hospital' => 'بیمارستان فوق‌تخصصی',
                        'clinic' => 'کلینیک تخصصی و جراحی',
                        'polyclinic' => 'پلی‌کلینیک',
                        'emergency_center' => 'مرکز اورژانس شبانه‌روزی',
                        'diagnostic_lab' => 'آزمایشگاه تشخیصی',
                        'shelter' => 'پناهگاه حمایتی',
                        default => 'مرکز درمانی'
                    };
                ?>
                <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                    
                    <!-- Top Card Body -->
                    <div>
                        <!-- Header Banner & Badges -->
                        <div class="h-32 bg-gradient-to-br from-slate-100 to-sky-50 relative p-4 flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-white/90 backdrop-blur-sm text-slate-800 shadow-sm border border-slate-200/60">
                                    <?= $typePersian ?>
                                </span>
                            </div>

                            <?php if (!empty($org['is_24_7'])): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-black bg-rose-500 text-white shadow-md animate-pulse">
                                    <span class="w-2 h-2 rounded-full bg-white"></span>
                                    <span>اورژانس ۲۴ ساعته</span>
                                </span>
                            <?php endif; ?>

                            <!-- Logo Overlay -->
                            <div class="absolute -bottom-6 right-6 w-16 h-16 rounded-2xl bg-white shadow-md border-2 border-white flex items-center justify-center overflow-hidden">
                                <?php if (!empty($org['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-3xl text-sky-600">local_hospital</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="p-6 pt-9 space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-black text-slate-900 group-hover:text-sky-600 transition-colors">
                                        <?= htmlspecialchars($org['name']) ?>
                                    </h2>
                                    <div class="flex items-center gap-1 text-amber-500 text-xs font-black">
                                        <span class="material-symbols-outlined text-sm">star</span>
                                        <span><?= number_format((float)($org['rating'] ?? 5.0), 1) ?></span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($org['description'] ?? 'ارائه خدمات درمانی، جراحی، واکسیناسیون و بستری تخصصی حیوانات خانگی.') ?>
                                </p>
                            </div>

                            <!-- Meta Info Strip -->
                            <div class="space-y-2 text-xs text-slate-600 pt-2 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-slate-400 shrink-0">location_on</span>
                                    <span class="truncate"><?= htmlspecialchars($org['city']) ?>، <?= htmlspecialchars($org['address'] ?? '') ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-base text-slate-400 shrink-0">call</span>
                                        <span class="dir-ltr font-bold text-slate-800"><?= htmlspecialchars($org['phone'] ?? '۰۲۱-۸۸۰۰۰۰۰۰') ?></span>
                                    </div>

                                    <div class="flex items-center gap-1 text-sky-700 font-bold">
                                        <span class="material-symbols-outlined text-base">group</span>
                                        <span><?= $docCount ?> پزشک همکار</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-slate-500">
                                    <span class="material-symbols-outlined text-base text-slate-400 shrink-0">schedule</span>
                                    <span>ساعات کاری: <?= htmlspecialchars($org['operating_hours'] ?? '۹ الی ۲۲') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer CTA -->
                    <div class="p-5 pt-0">
                        <a href="organization_profile.php?slug=<?= urlencode($org['slug']) ?>" class="w-full py-3 px-4 bg-slate-100 hover:bg-sky-600 hover:text-white text-slate-800 rounded-2xl text-xs font-bold flex items-center justify-center gap-2 transition-all group-hover:shadow-md">
                            <span>مشاهده پروفایل، کادر پزشکان و رزرو نوبت</span>
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
