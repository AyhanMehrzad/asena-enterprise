<?php
$currentPage = 'reviews';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

$msg = '';
$msgType = '';

// Handle Moderation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($reviewId > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?")->execute([$reviewId]);
            $msg = "نظر شماره #{$reviewId} با موفقیت تایید و در سایت منتشر شد.";
            $msgType = 'success';
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?")->execute([$reviewId]);
            $msg = "نظر شماره #{$reviewId} به عنوان نامناسب رد شد.";
            $msgType = 'warning';
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
            $msg = "نظر با موفقیت حذف گردید.";
            $msgType = 'error';
        }
    }
}

// Filters
$targetFilter = trim($_GET['type'] ?? '');
$ratingFilter = trim($_GET['rating'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($targetFilter) && in_array($targetFilter, ['doctor', 'product'])) {
    $where[] = "r.target_type = ?";
    $params[] = $targetFilter;
}

if ($ratingFilter === 'low') {
    $where[] = "r.rating <= 2";
} elseif ($ratingFilter === '3') {
    $where[] = "r.rating = 3";
} elseif ($ratingFilter === 'high') {
    $where[] = "r.rating >= 4";
}

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Fetch reviews
$sql = "
    SELECT r.*, u.name as user_name, u.phone as user_phone, u.role as user_role,
           CASE 
               WHEN r.target_type = 'doctor' THEN d.name
               WHEN r.target_type = 'product' THEN COALESCE(p.name, pm.name)
               ELSE 'نامشخص'
           END as target_name,
           CASE 
               WHEN r.target_type = 'doctor' THEN 'پزشک / متخصص'
               WHEN r.target_type = 'product' THEN COALESCE(p.category, pm.category, 'کالا')
               ELSE 'آیتم'
           END as target_category
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN doctors d ON r.target_type = 'doctor' AND r.target_id = d.id
    LEFT JOIN products p ON r.target_type = 'product' AND r.target_id = p.id
    LEFT JOIN pharmacy_medicines pm ON r.target_type = 'product' AND r.target_id = pm.id
    WHERE {$whereClause}
    ORDER BY r.created_at DESC
    LIMIT 100
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overview Metrics
$totalReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$pendingReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
$lowRatingReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE rating <= 2")->fetchColumn();
$avgDoctorRating = (float)$pdo->query("SELECT COALESCE(AVG(rating), 5.0) FROM reviews WHERE target_type = 'doctor'")->fetchColumn();
$avgProductRating = (float)$pdo->query("SELECT COALESCE(AVG(rating), 5.0) FROM reviews WHERE target_type = 'product'")->fetchColumn();
?>

<div class="p-6 md:p-8 space-y-6 max-w-[1500px] mx-auto rtl text-right" dir="rtl">

    <!-- Header -->
    <header class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-amber-500">rate_review</span>
                <h1 class="text-2xl font-black text-slate-900">پایش کیفیت، نظرات و رتبه‌بندی‌ها</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    رادار نظارت بر مراجعین و خریداران
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                بررسی تجربیات کاربران، شکایات مراجعین از پزشکان یا کالاهای فروشندگان، و تایید کیفی نظرات منتشر شده در پلتفرم.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">dashboard</span>
                <span>پیشخوان کلان</span>
            </a>
            <a href="doctors.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">stethoscope</span>
                <span>پزشکان</span>
            </a>
            <a href="sellers.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">store</span>
                <span>فروشندگان</span>
            </a>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 text-xs font-bold <?= $msgType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($msgType === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200') ?>">
            <span class="material-symbols-outlined text-base"><?= $msgType === 'success' ? 'check_circle' : 'info' ?></span>
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-slate-200/80 space-y-1">
            <span class="text-xs text-slate-500 font-bold">کل نظرات ثبت‌شده</span>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalReviews) ?></span>
                <span class="text-xs text-slate-400">نظر</span>
            </div>
            <p class="text-[10px] text-slate-400">در سراسر پزشکان و کالاها</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-slate-200/80 space-y-1">
            <span class="text-xs text-slate-500 font-bold">میانگین امتیاز پزشکان</span>
            <div class="flex items-center gap-1.5 text-amber-500">
                <span class="text-2xl font-black"><?= number_format($avgDoctorRating, 1) ?></span>
                <span class="text-xs">★</span>
                <span class="text-[11px] text-slate-400 font-normal mr-1">از ۵٫۰</span>
            </div>
            <p class="text-[10px] text-slate-400">رضایت بالینی و نحوه برخورد</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-slate-200/80 space-y-1">
            <span class="text-xs text-slate-500 font-bold">میانگین امتیاز کالاها</span>
            <div class="flex items-center gap-1.5 text-blue-600">
                <span class="text-2xl font-black"><?= number_format($avgProductRating, 1) ?></span>
                <span class="text-xs">★</span>
                <span class="text-[11px] text-slate-400 font-normal mr-1">از ۵٫۰</span>
            </div>
            <p class="text-[10px] text-slate-400">کیفیت ملزومات پت‌شاپ و دارو</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-rose-200 bg-rose-50/20 space-y-1">
            <span class="text-xs text-rose-700 font-bold">شکایات و امتیازات ضعیف (۱-۲)</span>
            <div class="flex items-baseline gap-1.5 text-rose-600">
                <span class="text-2xl font-black"><?= number_format($lowRatingReviews) ?></span>
                <span class="text-xs font-bold">مورد</span>
            </div>
            <p class="text-[10px] text-rose-500">نیازمند پیگیری بازرسی و پشتیبانی</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-amber-200 bg-amber-50/20 space-y-1">
            <span class="text-xs text-amber-700 font-bold">نظرات در انتظار بررسی</span>
            <div class="flex items-baseline gap-1.5 text-amber-600">
                <span class="text-2xl font-black"><?= number_format($pendingReviews) ?></span>
                <span class="text-xs font-bold">دیدگاه</span>
            </div>
            <p class="text-[10px] text-amber-600">جهت تایید پیش از انتشار عمومی</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 stat-card-shadow border border-slate-200/80">
        <form method="GET" action="reviews.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نوع هدف</label>
                <select name="type" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:bg-white">
                    <option value="">همه اهداف (پزشکان و کالاها)</option>
                    <option value="doctor" <?= $targetFilter === 'doctor' ? 'selected' : '' ?>>🩺 نظرات پزشکان و متخصصین</option>
                    <option value="product" <?= $targetFilter === 'product' ? 'selected' : '' ?>>🛍️ نظرات کالاها و داروها</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">دامنه امتیاز ستاره‌ای</label>
                <select name="rating" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:bg-white">
                    <option value="">همه امتیازها (۱ تا ۵)</option>
                    <option value="low" <?= $ratingFilter === 'low' ? 'selected' : '' ?>>⚠️ امتیازات ضعیف (۱ و ۲ ستاره - شکایات)</option>
                    <option value="3" <?= $ratingFilter === '3' ? 'selected' : '' ?>>متوسط (۳ ستاره)</option>
                    <option value="high" <?= $ratingFilter === 'high' ? 'selected' : '' ?>>عالی و رضایت‌بخش (۴ و ۵ ستاره)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">وضعیت تایید انتشار</label>
                <select name="status" class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:bg-white">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>در انتظار بررسی</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>تایید شده (منتشر شده)</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>رد شده</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 h-10 bg-primary text-white rounded-xl text-xs font-black hover:bg-primary-container transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>اعمال فیلتر</span>
                </button>
                <a href="reviews.php" class="h-10 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center justify-center" title="پاک کردن فیلتر">
                    <span class="material-symbols-outlined text-base">close</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Reviews Table / Stream -->
    <div class="bg-white rounded-2xl stat-card-shadow border border-slate-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">format_quote</span>
                <span>فهرست دیدگاه‌ها و بازخوردهای ثبتی</span>
            </h2>
            <span class="text-xs text-slate-400">تعداد یافته‌ها: <?= count($reviews) ?> مورد</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 font-black">نویسنده و نقش</th>
                        <th class="px-5 py-3 font-black">هدف نظر (پزشک / کالا)</th>
                        <th class="px-5 py-3 font-black text-center">امتیاز</th>
                        <th class="px-5 py-3 font-black">متن دیدگاه / تجربه</th>
                        <th class="px-5 py-3 font-black text-center">وضعیت انتشار</th>
                        <th class="px-5 py-3 font-black text-center">اقدام مدیر</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                هیچ نظر یا شکایتی با معیارهای انتخابی یافت نشد.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): 
                            $isLow = ($rev['rating'] <= 2);
                            $targetIcon = ($rev['target_type'] === 'doctor') ? 'stethoscope' : 'shopping_bag';
                            $targetColor = ($rev['target_type'] === 'doctor') ? 'text-blue-600 bg-blue-50' : 'text-amber-600 bg-amber-50';
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors <?= $isLow ? 'bg-rose-50/20' : '' ?>">
                                <!-- Author -->
                                <td class="px-5 py-4 align-top">
                                    <strong class="text-slate-900 block"><?= htmlspecialchars($rev['user_name']) ?></strong>
                                    <span class="text-slate-400 font-mono text-[11px] block mt-0.5" dir="ltr"><?= htmlspecialchars($rev['user_phone']) ?></span>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">
                                        نقش: <?= htmlspecialchars($rev['user_role']) ?>
                                    </span>
                                    <?php if (!empty($rev['is_verified_buyer'])): ?>
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ نوبت / خرید تاییدشده
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Target -->
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-xl <?= $targetColor ?> flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-sm"><?= $targetIcon ?></span>
                                        </div>
                                        <div>
                                            <strong class="text-slate-800 block text-xs font-black"><?= htmlspecialchars($rev['target_name'] ?: 'بدون نام') ?></strong>
                                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($rev['target_category']) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rating Stars -->
                                <td class="px-5 py-4 align-top text-center">
                                    <div class="inline-flex items-center gap-1 font-black text-xs px-2.5 py-1 rounded-xl <?= $isLow ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                        <span><?= (int)$rev['rating'] ?></span>
                                        <span class="text-amber-500">★</span>
                                    </div>
                                    <?php if ($isLow): ?>
                                        <span class="block text-[9px] text-rose-600 font-extrabold mt-1">نیازمند بازرسی</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Comment Text -->
                                <td class="px-5 py-4 align-top max-w-xs">
                                    <p class="text-slate-700 text-xs leading-relaxed line-clamp-3" title="<?= htmlspecialchars($rev['comment'] ?? '') ?>">
                                        <?= htmlspecialchars($rev['comment'] ?: 'بدون متن (فقط ثبت امتیاز)') ?>
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">
                                        ثبت شده در: <?= date('Y/m/d H:i', strtotime($rev['created_at'])) ?>
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-4 align-top text-center">
                                    <?php if ($rev['status'] === 'approved'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            منتشر شده
                                        </span>
                                    <?php elseif ($rev['status'] === 'rejected'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            رد شده
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                            در انتظار بررسی
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 align-top text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <?php if ($rev['status'] !== 'approved'): ?>
                                            <form method="POST" action="reviews.php" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                                <button type="submit" class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-600 hover:text-white transition-colors" title="تایید و انتشار عمومی">
                                                    <span class="material-symbols-outlined text-sm">check</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($rev['status'] !== 'rejected'): ?>
                                            <form method="POST" action="reviews.php" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                                <button type="submit" class="p-1.5 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-600 hover:text-white transition-colors" title="رد انتشار دیدگاه">
                                                    <span class="material-symbols-outlined text-sm">block</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="reviews.php" class="inline" onsubmit="return confirm('آیا از حذف دائم این نظر اطمینان دارید؟');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-100 text-rose-800 hover:bg-rose-600 hover:text-white transition-colors" title="حذف دائم">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/admin_footer.php'; ?>
