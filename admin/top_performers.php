<?php
$currentPage = 'top_performers';
require_once 'includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/App.php';
require_once dirname(__DIR__) . '/includes/LeaderboardService.php';

$leaderboardService = App::leaderboard();
$summaryStats = $leaderboardService->getSummaryStats();

$category = $_GET['tab'] ?? 'organizations';
if (!in_array($category, ['organizations', 'doctors', 'sellers'], true)) {
    $category = 'organizations';
}

$sort = $_GET['sort'] ?? 'best';
$topOnly = !empty($_GET['top_only']);
$searchQuery = trim($_GET['q'] ?? '');
$cityFilter = trim($_GET['city'] ?? '');

$filters = [
    'sort'     => $sort,
    'top_only' => $topOnly,
    'q'        => $searchQuery,
    'city'     => $cityFilter,
];

$items = $leaderboardService->getLeaderboard($category, $filters);

// Cities list for dropdown
$cities = [];
try {
    $cities = $pdo->query("SELECT DISTINCT city FROM organizations WHERE status = 'approved' AND city IS NOT NULL ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}
?>

<div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header & Action Row -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">military_tech</span>
                </span>
                <h1 class="text-xl font-black text-slate-900">تالار برگزیدگان و موتور رتبه‌بندی پویا (Top 5)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                ارزیابی زنده عملکرد مراکز، پزشکان و فروشندگان؛ ۵ رتبه اول هر دسته نشان طلایی اختصاصی دریافت می‌کنند و در صورت افت عملکرد نشان خودکار سلب می‌گردد.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>الگوریتم رتبه‌بندی بیزی فعال</span>
            </span>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Top Org -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50/40 p-5 rounded-2xl border border-amber-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-amber-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-amber-600">domain</span>
                    <span>برترین مرکز درمانی کشور</span>
                </span>
                <h3 class="text-base font-black text-slate-900 line-clamp-1"><?= htmlspecialchars($summaryStats['best_org_name']) ?></h3>
                <p class="text-[11px] text-amber-700 font-medium">دارنده رتبه ۱ کشوری در الگوریتم بیزی</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/30 text-xl font-black shrink-0">
                🥇
            </div>
        </div>

        <!-- Top Doctor -->
        <div class="bg-gradient-to-br from-sky-50 to-blue-50/40 p-5 rounded-2xl border border-sky-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-sky-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-sky-600">stethoscope</span>
                    <span>پزشک و متخصص برگزیده</span>
                </span>
                <h3 class="text-base font-black text-slate-900 line-clamp-1"><?= htmlspecialchars($summaryStats['best_doc_name']) ?></h3>
                <p class="text-[11px] text-sky-700 font-medium">بالاترین رضایت مراجعین و نوبت موفق</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center shadow-md shadow-sky-600/30 text-xl font-black shrink-0">
                🩺
            </div>
        </div>

        <!-- Top Seller -->
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50/40 p-5 rounded-2xl border border-emerald-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-emerald-600">storefront</span>
                    <span>فروشگاه و پت‌شاپ برتر</span>
                </span>
                <h3 class="text-base font-black text-slate-900 line-clamp-1"><?= htmlspecialchars($summaryStats['best_seller_name']) ?></h3>
                <p class="text-[11px] text-emerald-700 font-medium">بالاترین حجم تامین سفارشات سراسری</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/30 text-xl font-black shrink-0">
                🛍️
            </div>
        </div>
    </div>

    <!-- Category Tabs Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
        <a href="top_performers.php?tab=organizations<?= !empty($sort) ? '&sort=' . urlencode($sort) : '' ?>" class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all shrink-0 <?= $category === 'organizations' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-lg">local_hospital</span>
            <span>مراکز درمانی و بیمارستان‌ها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $category === 'organizations' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                <?= $summaryStats['top_orgs_count'] ?> برتر
            </span>
        </a>

        <a href="top_performers.php?tab=doctors<?= !empty($sort) ? '&sort=' . urlencode($sort) : '' ?>" class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all shrink-0 <?= $category === 'doctors' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-lg">groups</span>
            <span>پزشکان، جراحان و گرومرها</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $category === 'doctors' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                <?= $summaryStats['top_docs_count'] ?> برگزیده
            </span>
        </a>

        <a href="top_performers.php?tab=sellers<?= !empty($sort) ? '&sort=' . urlencode($sort) : '' ?>" class="flex items-center gap-2 px-5 py-3 rounded-2xl text-xs font-bold transition-all shrink-0 <?= $category === 'sellers' ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <span class="material-symbols-outlined text-lg">store</span>
            <span>فروشندگان پت‌شاپ و دارو</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $category === 'sellers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                <?= $summaryStats['top_sellers_count'] ?> منتخب
            </span>
        </a>
    </div>

    <!-- Filters & Sorting Toolbar -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
        <form method="GET" action="top_performers.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($category) ?>">

            <!-- Search -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">جستجوی نام یا مشخصات</label>
                <div class="relative">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="نام مرکز، پزشک یا فروشنده..." class="w-full h-11 pr-10 pl-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs">
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-lg">search</span>
                </div>
            </div>

            <!-- Sorting Options (THE KEY USER REQUIREMENT) -->
            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">مرتب‌سازی بر اساس</label>
                <select name="sort" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:border-sky-500 text-xs bg-white font-bold">
                    <option value="best" <?= $sort === 'best' ? 'selected' : '' ?>>🏆 رتبه‌بندی عملکردی (Top 5 اولویت)</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>⭐ بالاترین امتیاز رضایت</option>
                    <option value="reviews" <?= $sort === 'reviews' ? 'selected' : '' ?>>💬 بیشترین تعداد نظرات مراجعین</option>
                    <option value="created_desc" <?= $sort === 'created_desc' ? 'selected' : '' ?>>⏱️ جدیدترین همکاران (تاریخ ثبت‌نام)</option>
                    <option value="created_asc" <?= $sort === 'created_asc' ? 'selected' : '' ?>>🏛️ قدیمی‌ترین همکاران سامانه</option>
                    <option value="activity" <?= $sort === 'activity' ? 'selected' : '' ?>>📈 بیشترین فعالیت (نوبت و سفارش)</option>
                </select>
            </div>

            <!-- City Filter (if applicable) -->
            <?php if ($category === 'organizations' && !empty($cities)): ?>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">شهر</label>
                    <select name="city" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                        <option value="">همه شهرها</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $cityFilter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Top Only Checkbox & Actions -->
            <div class="lg:col-span-3 flex items-center gap-2">
                <label class="flex items-center gap-2 cursor-pointer bg-amber-50/80 border border-amber-200 h-11 px-3 rounded-xl flex-1 justify-center">
                    <input type="checkbox" name="top_only" value="1" <?= $topOnly ? 'checked' : '' ?> class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                    <span class="text-xs font-black text-amber-900 whitespace-nowrap">فقط ۵ تای برتر</span>
                </label>

                <button type="submit" class="h-11 px-5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 shadow-sm transition-all shrink-0">
                    <span>اعمال</span>
                    <span class="material-symbols-outlined text-base">tune</span>
                </button>

                <?php if (!empty($searchQuery) || $sort !== 'best' || $topOnly || !empty($cityFilter)): ?>
                    <a href="top_performers.php?tab=<?= urlencode($category) ?>" class="h-11 w-11 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl flex items-center justify-center shrink-0" title="پاکسازی فیلترها">
                        <span class="material-symbols-outlined text-base">restart_alt</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-sky-600">leaderboard</span>
                <span>جدول رتبه‌بندی زنده: <?= match($category) {
                    'organizations' => 'مراکز درمانی و بیمارستان‌ها',
                    'doctors' => 'پزشکان و گرومرها',
                    'sellers' => 'فروشندگان و پت‌شاپ‌ها'
                } ?></span>
            </h2>
            <span class="text-xs text-slate-400">تعداد ردیف‌های منطبق: <?= count($items) ?></span>
        </div>

        <?php if (empty($items)): ?>
            <div class="p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl mb-2">sentiment_dissatisfied</span>
                <p class="text-xs font-bold text-slate-600">هیچ موردی با این فیلترها یافت نشد.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-20">رتبه</th>
                            <th class="py-3.5 px-4">عنوان و مشخصات</th>
                            <th class="py-3.5 px-4 text-center">وضعیت نشان برتر (Top 5)</th>
                            <th class="py-3.5 px-4 text-center">امتیاز رضایت</th>
                            <th class="py-3.5 px-4 text-center">آمار فعالیت / نوبت‌ها</th>
                            <th class="py-3.5 px-4 text-center">تاریخ عضویت</th>
                            <th class="py-3.5 px-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $row): 
                            $rank = (int)$row['rank'];
                            $isTop = !empty($row['is_top_5']);

                            // Medal icons
                            $medal = match($rank) {
                                1 => '<span class="text-lg">🥇</span>',
                                2 => '<span class="text-lg">🥈</span>',
                                3 => '<span class="text-lg">🥉</span>',
                                4, 5 => '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">#'.$rank.'</span>',
                                default => '<span class="text-slate-400 font-bold">#'.$rank.'</span>'
                            };
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors <?= $isTop ? 'bg-amber-50/20' : '' ?>">
                                <!-- Rank Column -->
                                <td class="py-4 px-4 text-center font-black">
                                    <div class="flex items-center justify-center">
                                        <?= $medal ?>
                                    </div>
                                </td>

                                <!-- Title & Details -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($category === 'organizations'): ?>
                                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center shrink-0 p-1">
                                                <?php if (!empty($row['logo_url'])): ?>
                                                    <img src="../<?= htmlspecialchars($row['logo_url']) ?>" alt="Logo" class="w-full h-full object-contain">
                                                <?php else: ?>
                                                    <span class="material-symbols-outlined text-slate-400">local_hospital</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h3 class="font-black text-slate-900 flex items-center gap-1.5">
                                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                                    <?php if (!empty($row['is_24_7'])): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-100 text-rose-700">۲۴ ساعته</span>
                                                    <?php endif; ?>
                                                </h3>
                                                <p class="text-[11px] text-slate-400 mt-0.5">
                                                    <?= htmlspecialchars($row['city']) ?> <?= !empty($row['manager_name']) ? ' | مدیریت: ' . htmlspecialchars($row['manager_name']) : '' ?>
                                                </p>
                                            </div>
                                        <?php elseif ($category === 'doctors'): ?>
                                            <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                                <?php if (!empty($row['image_url'])): ?>
                                                    <img src="../<?= htmlspecialchars($row['image_url']) ?>" alt="Doctor" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="material-symbols-outlined text-slate-400">stethoscope</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h3 class="font-black text-slate-900"><?= htmlspecialchars($row['name']) ?></h3>
                                                <p class="text-[11px] text-slate-400 mt-0.5">
                                                    <?= htmlspecialchars($row['specialty'] ?? 'پزشک') ?>
                                                    <?= !empty($row['organization_name']) ? ' | ' . htmlspecialchars($row['organization_name']) : '' ?>
                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined">storefront</span>
                                            </div>
                                            <div>
                                                <h3 class="font-black text-slate-900"><?= htmlspecialchars($row['name']) ?></h3>
                                                <p class="text-[11px] text-slate-400 mt-0.5 dir-ltr text-right">
                                                    <?= htmlspecialchars($row['phone'] ?? '') ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Top 5 Badge Column -->
                                <td class="py-4 px-4 text-center">
                                    <?php if ($isTop): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black bg-gradient-to-r from-amber-400 to-yellow-300 text-slate-950 border border-amber-400 shadow-sm animate-pulse" title="نشان فعال پویا بر اساس رتبه">
                                            <span><?= htmlspecialchars($row['badge_label'] ?? 'نشان برتر') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px]">عادی (فاقد نشان)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Rating Column -->
                                <td class="py-4 px-4 text-center">
                                    <?php if ($category !== 'sellers'): ?>
                                        <div class="inline-flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-xl text-amber-600 font-black border border-amber-200/60">
                                            <span class="material-symbols-outlined text-sm">star</span>
                                            <span><?= number_format((float)($row['rating'] ?? 5.0), 1) ?></span>
                                            <span class="text-[10px] text-slate-400 font-normal">(<?= (int)($row['review_count'] ?? 0) ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs font-bold text-slate-600"><?= (int)($row['products_count'] ?? 0) ?> کالا در انبار</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Activity / Appointments -->
                                <td class="py-4 px-4 text-center font-bold text-slate-700">
                                    <?php if ($category === 'organizations'): ?>
                                        <span><?= (int)($row['appointments_count'] ?? 0) ?> نوبت</span>
                                        <span class="text-[11px] text-slate-400 block"><?= (int)($row['doctors_count'] ?? 0) ?> پزشک همکار</span>
                                    <?php elseif ($category === 'doctors'): ?>
                                        <span><?= (int)($row['appointments_count'] ?? 0) ?> نوبت موفق</span>
                                    <?php else: ?>
                                        <span><?= number_format((int)($row['total_sales_volume'] ?? 0)) ?> ریال</span>
                                        <span class="text-[10px] text-emerald-600 block">تسویه شده</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Created Date -->
                                <td class="py-4 px-4 text-center text-slate-400 text-[11px]">
                                    <?= !empty($row['created_at']) ? substr($row['created_at'], 0, 10) : '—' ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <?php if ($category === 'organizations'): ?>
                                            <a href="../organization_profile.php?slug=<?= urlencode($row['slug']) ?>" target="_blank" class="p-2 bg-slate-100 hover:bg-sky-50 text-slate-600 hover:text-sky-600 rounded-xl transition-colors" title="مشاهده پروفایل">
                                                <span class="material-symbols-outlined text-base">visibility</span>
                                            </a>
                                            <a href="organizations.php?edit=<?= (int)$row['id'] ?>" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors" title="مدیریت مرکز">
                                                <span class="material-symbols-outlined text-base">edit</span>
                                            </a>
                                        <?php elseif ($category === 'doctors'): ?>
                                            <a href="../doctor_profile.php?id=<?= (int)$row['id'] ?>" target="_blank" class="p-2 bg-slate-100 hover:bg-sky-50 text-slate-600 hover:text-sky-600 rounded-xl transition-colors" title="مشاهده پروفایل">
                                                <span class="material-symbols-outlined text-base">visibility</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="user_details.php?id=<?= (int)$row['id'] ?>" class="p-2 bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 rounded-xl transition-colors" title="جزئیات فروشنده">
                                                <span class="material-symbols-outlined text-base">manage_accounts</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/admin_footer.php'; ?>
