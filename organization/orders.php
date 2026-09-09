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

        // Multi-tenant authorization check
        $authorized = ($currentUser['role'] === 'admin');
        if (!$authorized && $orderId > 0) {
            $chk = $pdo->prepare("
                SELECT 1 FROM order_items oi
                WHERE oi.order_id = ? AND (oi.seller_id = ? OR oi.seller_id = ?)
                LIMIT 1
            ");
            $chk->execute([$orderId, (int)($currentOrg['user_id'] ?? 0), (int)$currentUser['id']]);
            $authorized = (bool)$chk->fetchColumn();
        }

        if (!$authorized) {
            $message = 'شما مجوز مدیریت یا ارسال این سفارش را ندارید.';
            $messageType = 'error';
        } elseif ($orderId > 0 && !empty($trackingCode)) {
            require_once __DIR__ . '/../includes/OrderLifecycleService.php';
            $lifecycle = new OrderLifecycleService($pdo);
            $transRes = $lifecycle->transition($orderId, 'shipped', 'organization', (int)$currentUser['id'], $carrier, $trackingCode, 'ارسال مرسوله توسط مرکز درمانی');

            if ($transRes['success']) {
                $pdo->prepare("UPDATE seller_escrow_ledger SET status = 'in_inspection' WHERE order_id = ? AND status = 'pending_delivery'")->execute([$orderId]);
                $message = "سفارش #PC-{$orderId} با کد رهگیری {$trackingCode} به عنوان ارسال شده ثبت گردید و پیامک رهگیری به خریدار ارسال شد.";
                $messageType = 'success';
            } else {
                $message = $transRes['message'] ?? 'خطا در ثبت اطلاعات ارسال.';
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

        // Multi-tenant authorization check
        $authorized = ($currentUser['role'] === 'admin');
        if (!$authorized && $orderId > 0) {
            $chk = $pdo->prepare("
                SELECT 1 FROM order_items oi
                WHERE oi.order_id = ? AND (oi.seller_id = ? OR oi.seller_id = ?)
                LIMIT 1
            ");
            $chk->execute([$orderId, (int)($currentOrg['user_id'] ?? 0), (int)$currentUser['id']]);
            $authorized = (bool)$chk->fetchColumn();
        }

        if (!$authorized) {
            $message = 'شما مجوز ویرایش وضعیت این سفارش را ندارید.';
            $messageType = 'error';
        } elseif ($orderId > 0 && in_array($newStatus, $allowed)) {
            require_once __DIR__ . '/../includes/OrderLifecycleService.php';
            $lifecycle = new OrderLifecycleService($pdo);
            $transRes = $lifecycle->transition($orderId, $newStatus, 'organization', (int)$currentUser['id'], null, null, 'تغییر وضعیت توسط مرکز');
            if ($transRes['success']) {
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

// Organization Multi-Tenant Restriction (Non-admin managers only see their organization's orders)
if ($currentUser['role'] !== 'admin') {
    $whereClauses[] = "(EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND (oi.seller_id = :org_user OR oi.seller_id = :curr_user)))";
    $params[':org_user'] = (int)($currentOrg['user_id'] ?? 0);
    $params[':curr_user'] = (int)$currentUser['id'];
}

$whereSql = implode(' AND ', $whereClauses);

// Fetch orders with comprehensive customer address, postal code and coordinates
$ordersQuery = "
    SELECT o.*, 
           u.name as customer_name, 
           u.phone as customer_phone, 
           u.city as customer_city,
           u.postal_code as customer_postal_code,
           u.address as customer_address,
           u.latitude as customer_lat,
           u.longitude as customer_lng
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE {$whereSql}
    ORDER BY o.id DESC
    LIMIT 50
";
$stmt = $pdo->prepare($ordersQuery);
$stmt->execute($params);
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

        <div class="flex items-center gap-2">
            <button type="button" onclick="syncPostexNow()" id="syncPostexBtn" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-base">sync</span>
                <span id="syncPostexText">استعلام زنده پستکس</span>
            </button>
            <a href="inventory.php" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-lg">inventory_2</span>
                <span>مدیریت انبار دارو و کالا</span>
            </a>
        </div>
    </div>

    <!-- 3-Step Simple Shipping Guide for Clinic & Petshop Staff -->
    <div class="bg-gradient-to-r from-sky-900 via-indigo-950 to-slate-900 text-white p-5 rounded-3xl shadow-md border border-white/10 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-400 text-2xl">help</span>
                <h3 class="font-black text-sm text-white">راهنمای ساده ۳ مرحله‌ای ارسال و بسته‌بندی سفارشات</h3>
            </div>
            <span class="text-[11px] bg-white/10 px-2.5 py-1 rounded-full text-slate-300 font-bold">ویژه پرسنل کلینیک و پت‌شاپ</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-1">
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۱</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">کپی نشانی یا چاپ برچسب</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">کد پستی و آدرس را کپی کنید یا با ۱ کلیک دکمه «چاپ برچسب مرسوله» را بزنید.</span>
                </div>
            </div>
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۲</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">تحویل به پست / تیپاکس</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">بسته را به مامور پست تحویل داده و قبض یا بارکد رهگیری را تحویل بگیرید.</span>
                </div>
            </div>
            <div class="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">۳</span>
                <div>
                    <span class="font-bold text-white block mb-0.5">ثبت بارکد و ارسال خودکار</span>
                    <span class="text-slate-300 text-[11px] leading-relaxed">بارکد را ثبت کنید. پیامک به خریدار و رهگیری هوشمند پستکس خودکار فعال می‌شود!</span>
                </div>
            </div>
        </div>
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

                    <!-- Shipping Address, Postal Code & Map Location -->
                    <?php
                    $fullAddr = htmlspecialchars($ord['shipping_address'] ?: ($ord['customer_address'] ?: 'نشانی ثبت نشده'));
                    $postalCode = htmlspecialchars($ord['customer_postal_code'] ?: '');
                    $hasCoords = (!empty($ord['customer_lat']) && !empty($ord['customer_lng']));
                    ?>
                    <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-start gap-2 text-xs text-slate-700">
                                <span class="material-symbols-outlined text-sky-600 text-lg flex-shrink-0">location_on</span>
                                <div>
                                    <span class="font-black text-slate-900 block mb-0.5">نشانی پستی تحویل گیرنده:</span>
                                    <span class="leading-relaxed"><?= $fullAddr ?></span>
                                    <?php if (!empty($ord['customer_city'])): ?>
                                        <span class="text-slate-400 mr-1">(شهر: <?= htmlspecialchars($ord['customer_city']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                                <a href="../actions/print_shipping_label.php?order_id=<?= $ord['id'] ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
                                    <span class="material-symbols-outlined text-sm">print</span>
                                    <span>چاپ برچسب کارتن</span>
                                </a>
                            </div>
                        </div>

                        <!-- Postal Code and Map Coordinates Bar -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-200/60 text-xs">
                            <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-900 px-3 py-1 rounded-xl">
                                <span class="material-symbols-outlined text-amber-700 text-base">markunread_mailbox</span>
                                <span class="font-bold">کد پستی ۱۰ رقمی:</span>
                                <span class="font-mono font-black tracking-wider text-sm"><?= $postalCode ?: 'ثبت نشده' ?></span>
                                <?php if ($postalCode): ?>
                                    <button type="button" onclick="copyText('<?= $postalCode ?>', this)" class="mr-1 px-2 py-0.5 rounded bg-amber-200/70 hover:bg-amber-300 text-amber-900 text-[10px] font-bold transition-all" title="کپی کد پستی">
                                        کپی
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasCoords): ?>
                                <a href="https://nshn.ir/?lat=<?= $ord['customer_lat'] ?>&lng=<?= $ord['customer_lng'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 font-bold text-[11px] flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-sm">near_me</span>
                                    <span>مسیریابی در نشان</span>
                                </a>
                                <a href="https://maps.google.com/?q=<?= $ord['customer_lat'] ?>,<?= $ord['customer_lng'] ?>" target="_blank" class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold text-[11px] flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-sm">map</span>
                                    <span>گوگل‌مپ</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

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
                        <?php if (in_array($ord['status'], ['pending_payment', 'processing'])): ?>
                            <!-- Action: Transition from Processing to Shipped -->
                            <form method="POST" class="flex flex-wrap items-center gap-2 m-0 bg-blue-50/50 p-3 rounded-2xl border border-blue-100">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="dispatch_order">
                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">

                                <span class="text-xs font-black text-slate-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sky-600 text-sm">local_shipping</span>
                                    <span>تحویل بسته:</span>
                                </span>

                                <select name="carrier_name" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold bg-white focus:ring-2 focus:ring-sky-500 outline-none">
                                    <option value="شرکت ملی پست (پیشتاز)">شرکت ملی پست (پیشتاز)</option>
                                    <option value="پستکس (Postex)">پستکس (Postex)</option>
                                    <option value="تیپاکس (Tipax)">تیپاکس (Tipax)</option>
                                    <option value="چاپار (Chapar)">چاپار (Chapar)</option>
                                    <option value="پیک اختصاصی کلینیک">پیک اختصاصی کلینیک</option>
                                </select>

                                <input type="text" name="post_tracking_code" placeholder="بارکد پستی ۲۴ رقمی..." required class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-mono dir-ltr focus:ring-2 focus:ring-sky-500 outline-none w-44 bg-white">

                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                                    <span>تغییر به ارسال شد</span>
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Active Shipped Tracking State -->
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-slate-500 font-bold">رهگیری پستی:</span>
                                <?php if (!empty($ord['post_tracking_code'])): ?>
                                    <a href="https://postex.ir/tracking?tracking_code=<?= urlencode($ord['post_tracking_code']) ?>" target="_blank" class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-bold text-xs border border-indigo-200 flex items-center gap-1 transition-all" title="رهگیری مستقیم مرسوله">
                                        <span class="material-symbols-outlined text-sm">search</span>
                                        <span><?= htmlspecialchars($ord['post_tracking_code']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400">کد پستی ثبت نشده</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

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

<!-- Client-side Interactive Functions -->
<script>
function copyText(text, btn) {
    if (!navigator.clipboard) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(text);
    }
    const orig = btn.innerText;
    btn.innerText = 'کپی شد!';
    btn.classList.add('bg-emerald-200', 'text-emerald-900');
    setTimeout(() => {
        btn.innerText = orig;
        btn.classList.remove('bg-emerald-200', 'text-emerald-900');
    }, 2000);
}

function syncPostexNow() {
    const btn = document.getElementById('syncPostexBtn');
    const txt = document.getElementById('syncPostexText');
    const origText = txt.innerText;

    btn.disabled = true;
    txt.innerText = 'در حال استعلام از پستکس...';

    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

    const fd = new FormData();
    fd.append('csrf_token', csrfToken);

    fetch('../actions/sync_shipping_action.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'استعلام با موفقیت انجام شد.');
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(err => {
        alert('خطا در برقراری ارتباط با وب‌سرویس استعلام پستکس.');
    })
    .finally(() => {
        btn.disabled = false;
        txt.innerText = origText;
    });
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
