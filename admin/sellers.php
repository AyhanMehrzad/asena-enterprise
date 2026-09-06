<?php
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$currentPage = 'sellers';

// ── Search & Filter ───────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT 
        u.id,
        u.name as seller_name,
        u.phone as seller_phone,
        u.email as seller_email,
        u.national_id,
        u.created_at,
        sw.bank_name,
        sw.bank_sheba,
        sw.bank_account_holder,
        sw.balance_available_for_payout,
        sw.balance_pending_escrow,
        sw.balance_settled_lifetime,
        (SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) as products_count,
        (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.seller_id = u.id) as orders_count,
        (SELECT COALESCE(SUM(oi.seller_net_amount), 0) FROM order_items oi WHERE oi.seller_id = u.id) as total_net_revenue
    FROM users u
    LEFT JOIN seller_wallets sw ON u.id = sw.seller_id
    WHERE u.role = 'seller'
";

$params = [];
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.phone LIKE ? OR sw.bank_sheba LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY u.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Macro Stats
$totalSellersCount = count($sellers);
$totalProductsCount = array_sum(array_column($sellers, 'products_count'));
$totalOrdersCount = array_sum(array_column($sellers, 'orders_count'));
$totalClearedPayouts = array_sum(array_column($sellers, 'balance_available_for_payout'));

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="p-4 lg:p-8 space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-on-surface flex items-center gap-2.5">
                <span class="material-symbols-outlined text-secondary-container text-2xl">store</span>
                <span>فهرست فروشندگان مستقل و پت‌شاپ‌ها (Single Sellers)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">پایش فروشگاه‌های تامین‌کننده کالا، کاتالوگ محصولات، رهگیری‌های پستی پستکس و وجوه امانی ۷ روزه</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>کارمزد پلتفرم: ۵٪ (۹۵٪ سهم فروشنده)</span>
            </span>
        </div>
    </div>

    <!-- 4 Top KPI Cards Matching Reference Screenshot -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">کل پت‌شاپ‌های فعال</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalSellersCount) ?></span>
                    <span class="text-xs text-secondary-container font-bold">فروشگاه</span>
                </div>
                <p class="text-[11px] text-slate-400">فروشندگان مستقل تایید شده</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">کالاهای موجود در ویترین</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalProductsCount) ?></span>
                    <span class="text-xs text-blue-600 font-bold">قلم کالا</span>
                </div>
                <p class="text-[11px] text-slate-400">تغذیه، مکمل و ملزومات حیوانات</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">سفارشات پردازش شده</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-600"><?= number_format($totalOrdersCount) ?></span>
                    <span class="text-xs text-emerald-600 font-bold">بسته پستی</span>
                </div>
                <p class="text-[11px] text-slate-400">با رهگیری پست پیشتاز / پستکس</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">موجودی آماده تسویه پایا</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-800"><?= number_format($totalClearedPayouts) ?></span>
                    <span class="text-xs text-on-surface-variant font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-slate-400">آزاد شده پس از مهلت ۷ روزه عودت</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10 flex flex-col md:flex-row gap-3 items-center justify-between">
        <form method="GET" class="flex flex-1 flex-wrap gap-2 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-lg">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="جستجوی نام پت‌شاپ، شماره تماس یا شبا..." class="w-full pr-10 pl-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none">
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-slate-800 transition-all flex items-center gap-1">
                <span>جستجو</span>
            </button>

            <?php if (!empty($search)): ?>
            <a href="sellers.php" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-all">
                حذف فیلتر
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Sellers Table -->
    <div class="bg-surface-container-lowest rounded-2xl stat-card-shadow border border-outline-variant/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">شناسه</th>
                        <th class="p-3.5">نام پت‌شاپ / فروشنده</th>
                        <th class="p-3.5">اطلاعات تماس و کد ملی</th>
                        <th class="p-3.5 text-center">اقلام ویترین</th>
                        <th class="p-3.5 text-center">سفارشات ثبت شده</th>
                        <th class="p-3.5">شماره شبا پایا</th>
                        <th class="p-3.5">موجودی آماده پایا (تومان)</th>
                        <th class="p-3.5">در مهلت امانت ۷ روزه (تومان)</th>
                        <th class="p-3.5 text-center">میزکار تسویه</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($sellers as $s): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-3.5 font-bold text-slate-400">#<?= $s['id'] ?></td>
                        <td class="p-3.5">
                            <div class="font-black text-slate-900 text-sm"><?= htmlspecialchars($s['seller_name']) ?></div>
                            <div class="text-[11px] text-emerald-600 font-bold">فروشگاه مستقل کالا</div>
                        </td>
                        <td class="p-3.5">
                            <div class="font-mono text-slate-800 font-bold"><?= htmlspecialchars($s['seller_phone'] ?: '-') ?></div>
                            <div class="text-[11px] text-slate-400 font-mono">کد ملی: <?= htmlspecialchars($s['national_id'] ?: '-') ?></div>
                        </td>
                        <td class="p-3.5 text-center font-black text-blue-600">
                            <?= (int)$s['products_count'] ?> کالا
                        </td>
                        <td class="p-3.5 text-center font-bold text-slate-700">
                            <?= (int)$s['orders_count'] ?>
                        </td>
                        <td class="p-3.5 font-mono text-slate-700 text-[11px]" dir="ltr">
                            <?= htmlspecialchars($s['bank_sheba'] ?: 'ثبت نشده') ?>
                        </td>
                        <td class="p-3.5 font-black text-emerald-600">
                            <?= number_format((int)($s['balance_available_for_payout'] ?? 0)) ?>
                        </td>
                        <td class="p-3.5 font-bold text-slate-600">
                            <?= number_format((int)($s['balance_pending_escrow'] ?? 0)) ?>
                        </td>
                        <td class="p-3.5 text-center">
                            <a href="payouts.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-secondary-container hover:text-white text-slate-700 font-bold text-xs transition-all inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">payments</span>
                                <span>صدور پایا</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
