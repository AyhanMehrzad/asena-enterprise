<?php
require_once 'includes/organization_header.php';

$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Order Fulfillment Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'dispatch_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $trackingCode = trim($_POST['post_tracking_code'] ?? '');
        $carrier = trim($_POST['carrier_name'] ?? 'شرکت ملی پست / پستکس');

        if ($orderId > 0 && !empty($trackingCode)) {
            $up = $pdo->prepare("
                UPDATE orders 
                SET status = 'shipped', post_tracking_code = ?, carrier_name = ?
                WHERE id = ?
            ");
            if ($up->execute([$trackingCode, $carrier, $orderId])) {
                // Update ledger items to delivered_in_inspection or mark carrier
                $pdo->prepare("UPDATE seller_escrow_ledger SET status = 'in_inspection' WHERE order_id = ? AND status = 'pending_delivery'")->execute([$orderId]);

                $message = "سفارش #PC-{$orderId} با کد رهگیری {$trackingCode} به عنوان ارسال شده ثبت گردید.";
                $messageType = 'success';
            } else {
                $message = 'خطا در ثبت اطلاعات ارسال.';
                $messageType = 'error';
            }
        } else {
            $message = 'لطفاً کد رهگیری پستی مرسوله را وارد نمایید.';
            $messageType = 'error';
        }
    } elseif ($action === 'update_order_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowed = ['processing', 'shipped', 'delivered', 'cancelled'];

        if ($orderId > 0 && in_array($newStatus, $allowed)) {
            $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($up->execute([$newStatus, $orderId])) {
                if ($newStatus === 'delivered') {
                    $pdo->prepare("UPDATE orders SET delivered_at = NOW(), post_delivery_verified = 1 WHERE id = ?")->execute([$orderId]);
                }
                $message = "وضعیت سفارش #PC-{$orderId} به‌روزرسانی شد.";
                $messageType = 'success';
            }
        }
    }
}

// Current Filter
$filter = $_GET['filter'] ?? 'all';
$whereClauses = ["1=1"];
$params = [];

if ($filter === 'pending') {
    $whereClauses[] = "o.status IN ('pending_payment', 'processing')";
} elseif ($filter === 'shipped') {
    $whereClauses[] = "o.status = 'shipped'";
} elseif ($filter === 'delivered') {
    $whereClauses[] = "o.status = 'delivered'";
}

$whereSql = implode(' AND ', $whereClauses);

// Fetch orders
$ordersQuery = "
    SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.city as customer_city
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE {$whereSql}
    ORDER BY o.id DESC
    LIMIT 50
";
$stmt = $pdo->query($ordersQuery);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach items to each order
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.image_url, p.category 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($ph)
    ");
    $itemStmt->execute($orderIds);
    $itemsByOrder = [];
    foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
        $itemsByOrder[$it['order_id']][] = $it;
    }
    foreach ($orders as &$ord) {
        $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
    }
    unset($ord);
}

// Quick stats
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status IN ('pending_payment', 'processing') THEN 1 ELSE 0 END) as pending_dispatch,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(total_amount) as gross_revenue
    FROM orders
")->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'pending_dispatch' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'gross_revenue' => 0];

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-sky-600 text-3xl">local_shipping</span>
                <span>سفارشات فروشگاهی، دارویی و ارسال کالا</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت مرسولات مشتریان، صدور فاکتور رسمی ماده ۱۶۹ و ثبت کدهای رهگیری پستکس</p>
        </div>

        <a href="inventory.php" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
            <span class="material-symbols-outlined text-lg">inventory_2</span>
            <span>مدیریت انبار دارو و کالا</span>
        </a>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">pending</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">در انتظار ارسال</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['pending_dispatch'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">تحویل به ناوگان پست</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['shipped_orders'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">تحویل قطعی شده</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['delivered_orders'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">مجموع درآمد فروش</span>
                <span class="text-lg font-black text-slate-900"><?= number_format((float)$stats['gross_revenue']) ?> <span class="text-[10px] font-bold text-slate-400">تومان</span></span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
        <a href="orders.php?filter=all" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'all' ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            همه سفارشات (<?= (int)$stats['total_orders'] ?>)
        </a>
        <a href="orders.php?filter=pending" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            در انتظار آماده‌سازی (<?= (int)$stats['pending_dispatch'] ?>)
        </a>
        <a href="orders.php?filter=shipped" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'shipped' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            ارسال شده با رهگیری (<?= (int)$stats['shipped_orders'] ?>)
        </a>
        <a href="orders.php?filter=delivered" class="px-4 py-2 rounded-xl transition-all <?= $filter === 'delivered' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            تحویل موفق به خریدار (<?= (int)$stats['delivered_orders'] ?>)
        </a>
    </div>

    <!-- Orders Feed -->
    <div class="space-y-4">
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 text-center py-16 text-slate-400 space-y-3">
                <span class="material-symbols-outlined text-5xl">shopping_cart_checkout</span>
                <p class="text-sm font-bold">هیچ سفارشی در این بخش وجود ندارد.</p>
                <p class="text-xs text-slate-400">به محض ثبت سفارش مشتریان در پت‌شاپ یا داروخانه، اطلاعات در این بخش قابل مدیریت خواهد بود.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
                <?php
                $statusMeta = match($ord['status']) {
                    'pending_payment' => ['title' => 'در انتظار پرداخت', 'bg' => 'bg-amber-50 text-amber-800 border-amber-200'],
                    'processing'      => ['title' => 'در حال بسته‌بندی در انبار', 'bg' => 'bg-blue-50 text-blue-800 border-blue-200'],
                    'shipped'         => ['title' => 'ارسال شده با پستکس / پست', 'bg' => 'bg-indigo-50 text-indigo-800 border-indigo-200'],
                    'delivered'       => ['title' => 'تحویل داده شده', 'bg' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                    'cancelled'       => ['title' => 'لغو شده', 'bg' => 'bg-rose-50 text-rose-800 border-rose-200'],
                    default           => ['title' => $ord['status'], 'bg' => 'bg-slate-100 text-slate-800 border-slate-200'],
                };
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm hover:border-slate-300 transition-all space-y-4">
                    <!-- Top Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span class="font-black text-slate-900 font-mono text-sm">#PC-<?= $ord['id'] ?></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-500 flex items-center gap-1 font-medium">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <?= $fmtDate->format(new DateTime($ord['created_at'])) ?>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="font-bold text-slate-700">خریدار: <?= htmlspecialchars($ord['customer_name'] ?? 'مشتری پلتفرم') ?></span>
                            <?php if (!empty($ord['customer_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($ord['customer_phone']) ?>" class="text-sky-600 font-mono dir-ltr hover:underline">
                                    <?= htmlspecialchars($ord['customer_phone']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusMeta['bg'] ?>">
                                <?= $statusMeta['title'] ?>
                            </span>
                            <a href="../actions/generate_invoice.php?order_id=<?= $ord['id'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1 transition-all" title="چاپ فاکتور رسمی">
                                <span class="material-symbols-outlined text-sm">receipt_long</span>
                                <span>فاکتور رسمی</span>
                            </a>
                        </div>
                    </div>

                    <!-- Shipping Address & Info -->
                    <?php if (!empty($ord['shipping_address'])): ?>
                        <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-start gap-2">
                            <span class="material-symbols-outlined text-slate-400 text-base flex-shrink-0 mt-0.5">location_on</span>
                            <div>
                                <span class="font-bold text-slate-800">نشانی تحویل گیرنده:</span>
                                <span><?= htmlspecialchars($ord['shipping_address']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Items Grid -->
                    <?php if (!empty($ord['items'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <?php foreach ($ord['items'] as $item): ?>
                                <div class="p-3 bg-slate-50/70 border border-slate-100 rounded-xl flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white border border-slate-200 overflow-hidden flex-shrink-0">
                                        <img src="../<?= htmlspecialchars($item['image_url'] ?? 'assets/images/toy-mouse.jpg') ?>" class="w-full h-full object-cover" onerror="this.src='../assets/images/toy-mouse.jpg'">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="text-xs font-bold text-slate-800 truncate" title="<?= htmlspecialchars($item['product_name_snapshot']) ?>">
                                            <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                        </h5>
                                        <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                            <?= (int)$item['quantity'] ?> عدد × <?= number_format((float)$item['price_at_purchase']) ?> تومان
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Dispatch & Tracking Action Box -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <!-- Postal Code Form -->
                        <form method="POST" class="flex flex-wrap items-center gap-2 m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="dispatch_order">
                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-600">کد رهگیری پستی / پستکس:</span>
                                <input type="text" name="post_tracking_code" value="<?= htmlspecialchars($ord['post_tracking_code'] ?? '') ?>" placeholder="مثال: 184590203001..." class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-sky-500 outline-none w-48">
                            </div>

                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">local_shipping</span>
                                <span>ثبت ارسال و رهگیری</span>
                            </button>
                        </form>

                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500">مبلغ کل سفارش:</span>
                            <span class="font-black text-base text-slate-900 font-mono">
                                <?= number_format((float)$ord['total_amount']) ?> <span class="text-xs font-bold text-slate-500">تومان</span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
