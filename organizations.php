<?php
/**
 * ASENA Enterprise - Veterinary Organizations & Clinics Directory
 * Comprehensive directory of verified hospitals, emergency centers, polyclinics, specialty surgeries, and pharmacies.
 * Version: 2.0.0
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
    'sort'    => trim($_GET['sort'] ?? 'featured'),
    'is_24_7' => isset($_GET['is_24_7']) && $_GET['is_24_7'] == '1' ? 1 : 0
];

$organizations = $orgService->getOrganizations($filters);
$activeCities  = $orgService->getActiveCities();
$stats         = $orgService->getStats();

// Page SEO Metadata
$page_title = 'مراکز درمانی، بیمارستان‌ها و کلینیک‌های دامپزشکی | ASENA';
$page_desc = 'دایرکتوری جامع مراکز درمانی و بیمارستان‌های تخصصی دامپزشکی کشور با امکان رزرو آنلاین نوبت با پزشکان همکار و خدمات اورژانس ۲۴ ساعته.';

require_once __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Hero Header with Visual Atmosphere & Live Stats -->
        <div class="bg-gradient-to-r from-sky-950 via-indigo-950 to-slate-900 rounded-3xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden border border-white/10">
            <div class="absolute -left-10 -bottom-10 w-96 h-96 bg-sky-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -right-10 -top-10 w-80 h-80 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Left/Main Hero Content -->
                <div class="lg:col-span-8 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-sky-500/20 text-sky-300 text-xs font-bold backdrop-blur-md border border-sky-400/20 shadow-sm">
                        <span class="material-symbols-outlined text-base">local_hospital</span>
                        <span>شبکه رسمی درمانگاه‌ها، بیمارستان‌ها و داروخانه‌های دامپزشکی کشور</span>
                    </div>
                    
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                        مراکز درمانی و بیمارستان‌های تخصصی پت
                    </h1>
                    
                    <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl">
                        یافتن معتبرترین مراکز جراحی، آزمایشگاه‌های تشخیصی و بیمارستان‌های شبانه‌روزی با کادر متخصص، نوبت‌دهی متمرکز آنلاین، استعلام دارو و خدمات اورژانس سریع.
                    </p>

                    <!-- Quick Action Links -->
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <a href="#directory" class="px-5 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-black text-xs shadow-lg shadow-sky-500/30 flex items-center gap-2 transition-all">
                            <span>مشاهده مراکز درمانی</span>
                            <span class="material-symbols-outlined text-sm">south</span>
                        </a>
                        <a href="register.php?role=organization" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs backdrop-blur-md border border-white/20 flex items-center gap-2 transition-all">
                            <span class="material-symbols-outlined text-sm">add_business</span>
                            <span>ثبت کلینیک یا بیمارستان در سامانه</span>
                        </a>
                    </div>
                </div>

                <!-- Right Hero Metrics Grid -->
                <div class="lg:col-span-4 grid grid-cols-2 gap-3.5">
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center">
                        <span class="text-2xl sm:text-3xl font-black text-sky-400 block mb-0.5">
                            <?= number_format($stats['total_organizations']) ?>+
                        </span>
                        <span class="text-[11px] text-slate-300 font-bold block">مراکز درمانی معتبر</span>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center">
                        <span class="text-2xl sm:text-3xl font-black text-rose-400 block mb-0.5">
                            <?= number_format($stats['emergency_24_7']) ?>
                        </span>
                        <span class="text-[11px] text-slate-300 font-bold block">اورژانس ۲۴ ساعته فعال</span>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center">
                        <span class="text-2xl sm:text-3xl font-black text-indigo-300 block mb-0.5">
                            <?= number_format($stats['affiliated_doctors']) ?>+
                        </span>
                        <span class="text-[11px] text-slate-300 font-bold block">پزشکان همکار متخصص</span>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center">
                        <span class="text-2xl sm:text-3xl font-black text-emerald-400 block mb-0.5">
                            <?= number_format($stats['active_cities']) ?>
                        </span>
                        <span class="text-[11px] text-slate-300 font-bold block">شهرهای تحت پوشش</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Filter Pills / Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <?php 
                $types = [
                    '' => ['label' => 'همه مراکز', 'icon' => 'domain'],
                    'hospital' => ['label' => 'بیمارستان‌های فوق‌تخصصی', 'icon' => 'local_hospital'],
                    'clinic' => ['label' => 'کلینیک‌های تخصصی و جراحی', 'icon' => 'medical_services'],
                    'pharmacy' => ['label' => 'داروخانه‌های تخصصی', 'icon' => 'medication'],
                    'shelter_charity' => ['label' => 'پناهگاه‌ها و خیریه‌ها', 'icon' => 'volunteer_activism'],
                ];
            ?>
            <?php foreach ($types as $typeKey => $tData): 
                $isActive = ($filters['type'] === $typeKey && !$filters['is_24_7']);
                $url = 'organizations.php?' . http_build_query(array_merge($filters, ['type' => $typeKey, 'is_24_7' => 0]));
            ?>
                <a href="<?= htmlspecialchars($url) ?>" class="px-4 py-2 rounded-full text-xs font-bold shrink-0 flex items-center gap-1.5 transition-all <?= $isActive ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-sm"><?= $tData['icon'] ?></span>
                    <span><?= $tData['label'] ?></span>
                </a>
            <?php endforeach; ?>

            <!-- 24/7 Shortcut Pill -->
            <?php 
                $is247Active = ($filters['is_24_7'] == 1);
                $url247 = 'organizations.php?' . http_build_query(array_merge($filters, ['is_24_7' => $is247Active ? 0 : 1]));
            ?>
            <a href="<?= htmlspecialchars($url247) ?>" class="px-4 py-2 rounded-full text-xs font-black shrink-0 flex items-center gap-1.5 transition-all <?= $is247Active ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 animate-pulse' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' ?>">
                <span class="material-symbols-outlined text-sm">e911_emergency</span>
                <span>اورژانس‌های شبانه‌روزی ۲۴ ساعته</span>
            </a>
        </div>

        <!-- Filter & Search Bar -->
        <div id="directory" class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80">
            <form method="GET" action="organizations.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                
                <!-- Search Query -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">جستجوی نام مرکز، نشانی یا خدمات</label>
                    <div class="relative">
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="مثال: بیمارستان پایتخت، سونوگرافی، ونک، قصرالدشت..." class="w-full h-11 pr-10 pl-3 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-xs">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                    </div>
                </div>

                <!-- City Filter -->
                <div class="lg:col-span-2">
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
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">نوع مرکز</label>
                    <select name="type" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs bg-white">
                        <option value="">همه انواع مراکز</option>
                        <option value="hospital" <?= $filters['type'] === 'hospital' ? 'selected' : '' ?>>بیمارستان فوق‌تخصصی</option>
                        <option value="clinic" <?= $filters['type'] === 'clinic' ? 'selected' : '' ?>>کلینیک تخصصی و جراحی</option>
                        <option value="pharmacy" <?= $filters['type'] === 'pharmacy' ? 'selected' : '' ?>>داروخانه تخصصی دامپزشکی</option>
                        <option value="shelter_charity" <?= $filters['type'] === 'shelter_charity' ? 'selected' : '' ?>>پناهگاه و نقاهتگاه حمایتی</option>
                    </select>
                </div>

                <!-- Sorting Option -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">مرتب‌سازی</label>
                    <select name="sort" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs bg-white">
                        <option value="featured" <?= $filters['sort'] === 'featured' ? 'selected' : '' ?>>پیش‌فرض (اورژانس و ویژه)</option>
                        <option value="rating" <?= $filters['sort'] === 'rating' ? 'selected' : '' ?>>بیشترین امتیاز مراجعین</option>
                        <option value="reviews" <?= $filters['sort'] === 'reviews' ? 'selected' : '' ?>>بیشترین تعداد نظرات</option>
                        <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>نام مرکز (الفبا)</option>
                        <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>جدیدترین مراکز</option>
                    </select>
                </div>

                <!-- 24/7 Checkbox & Action Buttons -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <label class="flex items-center gap-1.5 cursor-pointer bg-slate-50 border border-slate-200 h-11 px-3 rounded-xl flex-1 justify-center">
                        <input type="checkbox" name="is_24_7" value="1" <?= $filters['is_24_7'] ? 'checked' : '' ?> class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                        <span class="text-[11px] font-bold text-slate-700 whitespace-nowrap">۲۴ ساعته</span>
                    </label>
                    <button type="submit" class="h-11 px-4 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 shadow-sm transition-all shrink-0">
                        <span>فیلتر</span>
                        <span class="material-symbols-outlined text-base">tune</span>
                    </button>
                    <?php if (!empty($filters['q']) || !empty($filters['city']) || !empty($filters['type']) || !empty($filters['is_24_7'])): ?>
                        <a href="organizations.php" class="h-11 w-11 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl flex items-center justify-center shrink-0 transition-colors" title="پاک کردن فیلترها">
                            <span class="material-symbols-outlined text-base">restart_alt</span>
                        </a>
                    <?php endif; ?>
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
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                    لطفاً فیلترهای جستجو را تغییر دهید یا عبارت جستجوی خود را کوتاه‌تر نمایید.
                </p>
                <a href="organizations.php" class="inline-block px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors">
                    پاک کردن همه فیلترها
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($organizations as $org): 
                    $docList = $org['doctors'] ?? [];
                    $docCount = count($docList);
                    $isOpen = $org['is_open'] ?? false;
                    
                    $typeBadge = match($org['type']) {
                        'hospital' => ['label' => 'بیمارستان فوق‌تخصصی', 'bg' => 'bg-sky-50 text-sky-700 border-sky-200'],
                        'clinic' => ['label' => 'کلینیک تخصصی و جراحی', 'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                        'pharmacy' => ['label' => 'داروخانه مرجع دامپزشکی', 'bg' => 'bg-teal-50 text-teal-700 border-teal-200'],
                        'shelter_charity' => ['label' => 'پناهگاه و نقاهتگاه حمایتی', 'bg' => 'bg-amber-50 text-amber-800 border-amber-200'],
                        default => ['label' => 'مرکز درمانی', 'bg' => 'bg-slate-50 text-slate-700 border-slate-200']
                    };

                    // Parse facilities list for pill tags
                    $facilitiesList = !empty($org['facilities']) ? array_map('trim', explode(',', $org['facilities'])) : [];
                ?>
                <div class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden shadow-sm hover:shadow-xl hover:border-sky-300 transition-all duration-300 flex flex-col justify-between group relative">
                    
                    <!-- Top Card Body -->
                    <div>
                        <!-- Header Banner & Badges -->
                        <div class="h-36 bg-gradient-to-br from-slate-800 via-sky-950 to-indigo-950 relative p-4 flex items-start justify-between overflow-hidden">
                            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
                            
                            <!-- Badges Left/Right -->
                            <div class="relative z-10 flex flex-wrap items-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/95 backdrop-blur-md text-slate-800 shadow-sm border border-white/20">
                                    <?= $typeBadge['label'] ?>
                                </span>
                                <?php if (!empty($org['license_number'])): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/30 text-sky-200 border border-sky-400/30">
                                        پروانه رسمی
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="relative z-10 flex flex-col items-end gap-1.5">
                                <?php if (!empty($org['is_24_7'])): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-lg animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        <span>اورژانس ۲۴ ساعته</span>
                                    </span>
                                <?php elseif ($isOpen): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/90 text-white backdrop-blur-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        <span>الان باز است</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-700/90 text-slate-200 backdrop-blur-md">
                                        <span>اکنون بسته</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Logo Overlay -->
                            <div class="absolute -bottom-6 right-6 w-16 h-16 rounded-2xl bg-white shadow-lg border-2 border-white flex items-center justify-center overflow-hidden z-20 group-hover:scale-105 transition-transform">
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
                                <div class="flex items-start justify-between gap-2">
                                    <h2 class="text-base font-black text-slate-900 group-hover:text-sky-600 transition-colors line-clamp-1">
                                        <a href="organization_profile.php?slug=<?= urlencode($org['slug']) ?>">
                                            <?= htmlspecialchars($org['name']) ?>
                                        </a>
                                    </h2>
                                    <div class="flex items-center gap-1 text-amber-500 text-xs font-black shrink-0 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                        <span class="material-symbols-outlined text-sm">star</span>
                                        <span><?= number_format((float)($org['rating'] ?? 5.0), 1) ?></span>
                                        <span class="text-[10px] text-slate-400 font-normal">(<?= (int)($org['review_count'] ?? 0) ?>)</span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($org['manager_name'])): ?>
                                    <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                                        مدیریت: <?= htmlspecialchars($org['manager_name']) ?>
                                    </span>
                                <?php endif; ?>

                                <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($org['description'] ?? 'ارائه کلیه خدمات درمانی، جراحی، واکسیناسیون و مراقبت‌های بالینی حیوانات خانگی.') ?>
                                </p>
                            </div>

                            <!-- Facilities Quick Tags -->
                            <?php if (!empty($facilitiesList)): ?>
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    <?php foreach (array_slice($facilitiesList, 0, 3) as $fac): ?>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
                                            <?= htmlspecialchars($fac) ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($facilitiesList) > 3): ?>
                                        <span class="px-1.5 py-0.5 rounded-lg text-[9px] font-bold bg-sky-50 text-sky-700">
                                            +<?= count($facilitiesList) - 3 ?> دیگر
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Meta Info Strip -->
                            <div class="space-y-2 text-xs text-slate-600 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-slate-400 shrink-0">location_on</span>
                                    <span class="truncate font-medium text-slate-700"><?= htmlspecialchars($org['city']) ?>، <?= htmlspecialchars($org['address'] ?? '') ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-base text-slate-400 shrink-0">call</span>
                                        <a href="tel:<?= htmlspecialchars($org['phone'] ?? '') ?>" class="dir-ltr font-bold text-slate-800 hover:text-sky-600 transition-colors">
                                            <?= htmlspecialchars($org['phone'] ?? '۰۲۱-۸۸۰۰۰۰۰۰') ?>
                                        </a>
                                    </div>

                                    <!-- Doctor Preview Avatars & Count -->
                                    <div class="flex items-center gap-1.5 text-indigo-700 font-bold">
                                        <div class="flex -space-x-2 -space-x-reverse overflow-hidden">
                                            <?php foreach (array_slice($docList, 0, 3) as $doc): ?>
                                                <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-slate-200 overflow-hidden" title="<?= htmlspecialchars($doc['name']) ?>">
                                                    <?php if (!empty($doc['image_url'])): ?>
                                                        <img class="h-full w-full object-cover" src="<?= htmlspecialchars($doc['image_url']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>">
                                                    <?php else: ?>
                                                        <span class="material-symbols-outlined text-sm text-slate-600 flex items-center justify-center h-full">person</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-[11px]"><?= $docCount ?> پزشک</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-slate-500 text-[11px]">
                                    <span class="material-symbols-outlined text-sm text-slate-400 shrink-0">schedule</span>
                                    <span class="truncate">پذیرش: <?= htmlspecialchars($org['operating_hours'] ?? '۹ الی ۲۲') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="p-5 pt-0 space-y-2">
                        <div class="grid grid-cols-4 gap-2">
                            <a href="organization_profile.php?slug=<?= urlencode($org['slug']) ?>" class="col-span-3 py-2.5 px-3 bg-slate-100 hover:bg-sky-600 hover:text-white text-slate-800 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all group-hover:shadow-sm">
                                <span>پروفایل و نوبت‌دهی</span>
                                <span class="material-symbols-outlined text-base">arrow_back</span>
                            </a>
                            
                            <?php if (!empty($org['phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($org['phone']) ?>" class="py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 flex items-center justify-center transition-all shadow-sm" title="تماس مستقیم">
                                    <span class="material-symbols-outlined text-base">call</span>
                                </a>
                            <?php else: ?>
                                <a href="organization_profile.php?slug=<?= urlencode($org['slug']) ?>#location" class="py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all" title="مسیریابی">
                                    <span class="material-symbols-outlined text-base">near_me</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Partner Banner CTA -->
        <div class="bg-gradient-to-r from-sky-900 via-indigo-900 to-slate-900 rounded-3xl p-8 sm:p-10 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 border border-white/10">
            <div class="space-y-2 text-center md:text-right max-w-xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-sky-200 text-xs font-bold backdrop-blur-md">
                    <span class="material-symbols-outlined text-sm">handshake</span>
                    همکاری با شبکه کلینیکی ASENA Enterprise
                </span>
                <h3 class="text-xl sm:text-2xl font-black">مدیر یا موسس مرکز درمانی و بیمارستان دامپزشکی هستید؟</h3>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    با ثبت مرکز خود در سامانه، به پنل اختصاصی مدیریت نوبت‌ها، پزشکان، داروخانه داخلی و اتصال مستقیم به نسخه الکترونیک دسترسی پیدا کنید.
                </p>
            </div>

            <div class="shrink-0">
                <a href="register.php?role=organization" class="px-6 py-3.5 bg-sky-500 hover:bg-sky-400 text-white rounded-2xl font-black text-xs sm:text-sm shadow-xl shadow-sky-500/30 flex items-center gap-2 transition-all">
                    <span>ثبت‌نام مرکز درمانی شما</span>
                    <span class="material-symbols-outlined text-base">arrow_back</span>
                </a>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
