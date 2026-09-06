<?php
require_once __DIR__ . '/includes/seller_header.php';

$msg = '';
$msgType = 'info';

// ── Handle Post Actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_tracking') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $trackingCode = trim($_POST['post_tracking_code'] ?? '');
        $carrier = trim($_POST['carrier_name'] ?? 'پست پیشتاز (پستکس)');

        if ($orderId > 0 && !empty($trackingCode)) {
            // Verify this seller owns at least one item in this order
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND seller_id = ?");
            $checkStmt->execute([$orderId, $sellerId]);
            if ($checkStmt->fetchColumn() > 0 || $currentUser['role'] === 'admin') {
                $upd = $pdo->prepare("
                    UPDATE orders 
                    SET post_tracking_code = ?, tracking_code = ?, carrier_name = ?, status = 'shipped' 
                    WHERE id = ?
                ");
                $upd->execute([$trackingCode, $trackingCode, $carrier, $orderId]);
                $msg = "کد رهگیری پستی ({$trackingCode}) برای سفارش #{$orderId} با موفقیت ثبت و وضعیت به «ارسال شده» تغییر یافت.";
                $msgType = 'success';
            }
        }
    } elseif ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'لوازم جانبی');
        $price = (int)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 1);
        $description = trim($_POST['description'] ?? '');
        $brand = trim($_POST['brand'] ?? 'عمومی');
        $targetAnimal = trim($_POST['target_animal'] ?? 'سگ و گربه');
        $isAutoship = !empty($_POST['is_autoship']) ? 1 : 0;
        $imageUrl = trim($_POST['image_url'] ?? 'assets/images/default-product.png');

        if (!empty($name) && $price > 0) {
            $insProd = $pdo->prepare("
                INSERT INTO products 
                (seller_id, name, category, price, stock, description, brand, target_animal, is_autoship, image_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insProd->execute([$sellerId, $name, $category, $price, $stock, $description, $brand, $targetAnimal, $isAutoship, $imageUrl]);
            $msg = "کالای جدید «{$name}» با موفقیت به ویترین پت‌شاپ شما افزوده شد.";
            $msgType = 'success';
        } else {
            $msg = 'لطفاً نام کالا و قیمت معتبر وارد کنید.';
            $msgType = 'error';
        }
    } elseif ($action === 'update_stock') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $delta = (int)($_POST['stock_delta'] ?? 0);
        $newStock = isset($_POST['new_stock']) ? (int)$_POST['new_stock'] : null;

        if ($productId > 0) {
            if ($newStock !== null) {
                $upd = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
                $upd->execute([max(0, $newStock), $productId, $sellerId, $currentUser['role']]);
            } elseif ($delta !== 0) {
                $upd = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
                $upd->execute([$delta, $productId, $sellerId, $currentUser['role']]);
            }
            $msg = 'موجودی انبار کالا با موفقیت بروزرسانی شد.';
            $msgType = 'success';
        }
    } elseif ($action === 'update_bank') {
        $bankName = trim($_POST['bank_name'] ?? '');
        $holder = trim($_POST['bank_account_holder'] ?? $sellerName);
        $sheba = trim($_POST['bank_sheba'] ?? '');
        $card = trim($_POST['bank_card_number'] ?? '');

        // Clean sheba
        $sheba = strtoupper(str_replace([' ', '-'], '', $sheba));
        if (!empty($sheba) && !str_starts_with($sheba, 'IR')) {
            $sheba = 'IR' . $sheba;
        }

        $updBank = $pdo->prepare("
            UPDATE seller_wallets 
            SET bank_name = ?, bank_account_holder = ?, bank_sheba = ?, bank_card_number = ?, updated_at = NOW()
            WHERE seller_id = ?
        ");
        $updBank->execute([$bankName, $holder, $sheba, $card, $sellerId]);
        $msg = 'اطلاعات حساب بانکی و شماره شبای پت‌شاپ با موفقیت ذخیره گردید.';
        $msgType = 'success';

        // Refresh wallet
        $walletStmt->execute([$sellerId]);
        $sellerWallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($action === 'request_payout') {
        $available = (int)($sellerWallet['balance_available_for_payout'] ?? 0);
        $sheba = trim($sellerWallet['bank_sheba'] ?? '');
        if ($available >= 50000 && !empty($sheba)) {
            $msg = 'درخواست صدور حواله پایا به مبلغ ' . number_format($available) . ' تومان ثبت شد و در چرخه تسویه بعدی بانک مرکزی واریز می‌گردد.';
            $msgType = 'success';
        } elseif (empty($sheba)) {
            $msg = 'لطفاً ابتدا شماره شبای بانکی خود را در تب اطلاعات بانکی ثبت نمایید.';
            $msgType = 'error';
        } else {
            $msg = 'حداقل موجودی قابل تسویه ۵۰,۰۰۰ تومان می‌باشد.';
            $msgType = 'error';
        }
    }
}

// ── Fetch Seller Metrics ──────────────────────────────────────────────────────
// 1. Pending orders count
$pendingOrdersStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE (oi.seller_id = ? OR ? = 'admin') AND o.status IN ('processing', 'pending_payment')
");
$pendingOrdersStmt->execute([$sellerId, $currentUser['role']]);
$pendingOrdersCount = (int)$pendingOrdersStmt->fetchColumn();

// 2. Active products count
$prodCountStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE (seller_id = ? OR ? = 'admin')");
$prodCountStmt->execute([$sellerId, $currentUser['role']]);
$totalProductsCount = (int)$prodCountStmt->fetchColumn();

// 3. Wallet balances
$availablePayout = (int)($sellerWallet['balance_available_for_payout'] ?? 0);
$pendingEscrow = (int)($sellerWallet['balance_pending_escrow'] ?? 0);

// ── Fetch Seller Orders ───────────────────────────────────────────────────────
$ordersQuery = $pdo->prepare("
    SELECT 
        o.id as order_id,
        o.status as order_status,
        o.created_at as order_date,
        o.shipping_address,
        o.post_tracking_code,
        o.carrier_name,
        o.escrow_status,
        o.delivered_at,
        u.name as buyer_name,
        u.phone as buyer_phone,
        oi.product_name_snapshot,
        oi.quantity,
        oi.price_at_purchase,
        oi.commission_rate,
        oi.seller_net_amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE (oi.seller_id = ? OR ? = 'admin')
    ORDER BY o.id DESC
    LIMIT 100
");
$ordersQuery->execute([$sellerId, $currentUser['role']]);
$sellerOrders = $ordersQuery->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch Seller Products ─────────────────────────────────────────────────────
$productsQuery = $pdo->prepare("
    SELECT * FROM products 
    WHERE (seller_id = ? OR ? = 'admin')
    ORDER BY id DESC
");
$productsQuery->execute([$sellerId, $currentUser['role']]);
$sellerProducts = $productsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4 lg:p-8 space-y-8">

    <?php if (!empty($msg)): ?>
    <div class="p-4 rounded-2xl flex items-center gap-3 text-xs font-bold shadow-sm transition-all <?= $msgType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($msgType === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-blue-50 text-blue-800 border border-blue-200') ?>">
        <span class="material-symbols-outlined text-lg"><?= $msgType === 'success' ? 'check_circle' : ($msgType === 'error' ? 'error' : 'info') ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
    <?php endif; ?>

    <!-- KPI Section: 4 Stat Cards Matching Reference Screenshot -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Orders Pending Shipment -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">سفارشات جدید و در انتظار ارسال</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($pendingOrdersCount) ?></span>
                    <span class="text-xs text-secondary-container font-bold">بسته پستی</span>
                </div>
                <p class="text-[11px] text-slate-400">نیاز به ثبت کد رهگیری پستکس</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
        </div>

        <!-- Card 2: Active Products -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">محصولات فعال در ویترین</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalProductsCount) ?></span>
                    <span class="text-xs text-blue-600 font-bold">قلم کالا</span>
                </div>
                <p class="text-[11px] text-slate-400">پت‌شاپ اختصاصی آنلاین</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
        </div>

        <!-- Card 3: Cleared Paya Balance -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">موجودی آماده تسویه پایا</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-600"><?= number_format($availablePayout) ?></span>
                    <span class="text-xs text-on-surface-variant font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-emerald-600 font-medium">تسویه خودکار چهارشنبه‌ها</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
        </div>

        <!-- Card 4: In Escrow 7-Day Window -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">امانت در مهلت ۷ روزه عودت</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-700"><?= number_format($pendingEscrow) ?></span>
                    <span class="text-xs text-on-surface-variant font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-slate-400">تضمین سلامت کالا برای خریدار</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">lock_clock</span>
            </div>
        </div>
    </div>

    <!-- ── TAB 1: ORDERS & SHIPPING ─────────────────────────────────────────── -->
    <section id="orders-tab" class="seller-tab-content space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10">
            <div>
                <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">local_shipping</span>
                    <span>سفارشات مشتریان و مدیریت ارسال مرسوله</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">پس از تحویل بسته به مامور پست یا تیپاکس، کد رهگیری ۲۴ رقمی را ثبت کنید تا فرآیند مهلت ۷ روزه عودت و تسویه مالی آغاز شود.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold">
                    کارمزد پلتفرم: ۵٪ (۹۵٪ سهم خالص فروشنده)
                </span>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl stat-card-shadow border border-outline-variant/10 overflow-hidden">
            <?php if (empty($sellerOrders)): ?>
            <div class="p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-300">shopping_bag</span>
                <p class="text-sm font-bold text-slate-500 mt-2">هنوز سفارشی برای کالاهای شما ثبت نشده است.</p>
                <p class="text-xs text-slate-400 mt-1">با تکمیل ویترین محصولات و قیمت‌گذاری مناسب، فروش خود را آغاز کنید.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                        <tr>
                            <th class="p-3.5">سفارش #</th>
                            <th class="p-3.5">تاریخ ثبت</th>
                            <th class="p-3.5">خریدار</th>
                            <th class="p-3.5">کالای خریداری شده</th>
                            <th class="p-3.5">سهم خالص فروشنده (تومان)</th>
                            <th class="p-3.5">وضعیت سفارش</th>
                            <th class="p-3.5">کد رهگیری پستکس / پیشتاز</th>
                            <th class="p-3.5 text-center">عملیات ارسال</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($sellerOrders as $ord): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-3.5 font-black text-on-surface">#<?= $ord['order_id'] ?></td>
                            <td class="p-3.5 text-slate-500"><?= htmlspecialchars(substr($ord['order_date'], 0, 16)) ?></td>
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900"><?= htmlspecialchars($ord['buyer_name'] ?: 'کاربر آسنا') ?></div>
                                <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($ord['buyer_phone'] ?: '-') ?></div>
                            </td>
                            <td class="p-3.5">
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($ord['product_name_snapshot'] ?: 'کالای فروشگاه') ?></span>
                                <span class="text-slate-400 font-bold mr-1">(×<?= (int)$ord['quantity'] ?>)</span>
                            </td>
                            <td class="p-3.5 font-black text-emerald-600">
                                <?= number_format($ord['seller_net_amount'] ?: ($ord['price_at_purchase'] * $ord['quantity'] * 0.95)) ?>
                            </td>
                            <td class="p-3.5">
                                <?php
                                $badgeClass = match($ord['order_status']) {
                                    'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'shipped'   => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default     => 'bg-amber-50 text-amber-700 border-amber-200'
                                };
                                $statusLabel = match($ord['order_status']) {
                                    'delivered' => 'تحویل شده',
                                    'shipped'   => 'ارسال شده',
                                    'cancelled' => 'لغو شده',
                                    default     => 'در انتظار ارسال'
                                };
                                ?>
                                <span class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-bold border <?= $badgeClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="p-3.5">
                                <?php if (!empty($ord['post_tracking_code'])): ?>
                                    <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-md">
                                        <?= htmlspecialchars($ord['post_tracking_code']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 italic text-[11px]">ثبت نشده</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-center">
                                <button onclick="openTrackingModal(<?= $ord['order_id'] ?>, '<?= htmlspecialchars(addslashes($ord['post_tracking_code'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($ord['carrier_name'] ?? 'پست پیشتاز (پستکس)')) ?>')" class="px-3 py-1.5 rounded-xl bg-secondary-container hover:bg-orange-600 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1 mx-auto">
                                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                                    <span><?= !empty($ord['post_tracking_code']) ? 'ویرایش رهگیری' : 'ثبت بارکد پست' ?></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── TAB 2: PRODUCTS CATALOG ─────────────────────────────────────────── -->
    <section id="products-tab" class="seller-tab-content hidden space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10">
            <div>
                <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">inventory_2</span>
                    <span>ویترین و انبار محصولات پت‌شاپ شما</span>
                </h2>
            </div>
            <button onclick="openNewProductModal()" class="px-4 py-2.5 rounded-xl bg-secondary-container hover:bg-orange-600 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ افزودن کالای جدید</span>
            </button>
        </div>

        <div class="bg-surface-container-lowest p-3 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-lg pr-1">search</span>
            <input type="text" id="productSearchInput" onkeyup="filterProducts()" placeholder="جستجوی سریع در نام کالا، برند یا دسته‌بندی ویترین..." class="w-full text-xs outline-none bg-transparent text-slate-800">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($sellerProducts as $prod): ?>
            <div class="seller-product-card bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10 flex flex-col justify-between space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                        <img src="<?= !empty($prod['image_url']) ? htmlspecialchars(str_starts_with($prod['image_url'], 'http') ? $prod['image_url'] : '../' . ltrim($prod['image_url'], '/')) : '../assets/images/default-product.png' ?>" class="w-full h-full object-cover" onerror="this.src='../assets/images/default-product.png'" alt="Product">
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] font-bold text-secondary-container bg-amber-50 px-2 py-0.5 rounded-md"><?= htmlspecialchars($prod['category']) ?></span>
                        <h3 class="text-xs font-bold text-slate-900 truncate mt-1"><?= htmlspecialchars($prod['name']) ?></h3>
                        <div class="text-xs font-black text-emerald-600 mt-1"><?= number_format($prod['price']) ?> تومان</div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-slate-500 font-bold">موجودی انبار:</span>
                        <span class="text-xs font-black <?= $prod['stock'] > 0 ? 'text-slate-800' : 'text-rose-600' ?>"><?= (int)$prod['stock'] ?></span>
                    </div>

                    <!-- Quick Stock Adjust Controls -->
                    <div class="flex items-center gap-1">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <input type="hidden" name="stock_delta" value="-1">
                            <button type="submit" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 font-bold flex items-center justify-center transition-colors text-sm" title="کاهش یک عدد">-</button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <input type="hidden" name="stock_delta" value="1">
                            <button type="submit" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 text-slate-600 font-bold flex items-center justify-center transition-colors text-sm" title="افزایش یک عدد">+</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── TAB 3: MARKETPLACE ESCROW & PAYA WALLET ──────────────────────────── -->
    <section id="wallet-tab" class="seller-tab-content hidden space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Balance Card -->
            <div class="lg:col-span-1 bg-gradient-to-br from-tertiary to-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col justify-between space-y-6">
                <div>
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>کیف پول تسویه پایا هفتگی</span>
                        <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-mono text-[10px]">بانک مرکزی IR-PAYA</span>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-black text-emerald-400"><?= number_format($availablePayout) ?></span>
                        <span class="text-xs text-slate-300 mr-1">تومان</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">موجودی آزاد شده پس از سپری شدن ۷ روز تضمین عودت کالا</p>
                </div>

                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>مبالغ در امانت پست:</span>
                        <span class="font-bold text-white"><?= number_format($pendingEscrow) ?> تومان</span>
                    </div>
                    <div class="flex justify-between text-slate-300">
                        <span>کارمزد پلتفرم:</span>
                        <span class="font-bold text-amber-400">۵ درصد</span>
                    </div>
                    <div class="flex justify-between text-slate-300">
                        <span>سهم خالص شما:</span>
                        <span class="font-bold text-emerald-400">۹۵ درصد</span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="request_payout">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">payments</span>
                        <span>درخواست صدور حواله پایا (تسویه هفتگی)</span>
                    </button>
                </form>

                <div class="text-[11px] text-slate-400 leading-relaxed bg-white/5 p-3 rounded-xl">
                    💡 طبق چرخه بانکی پایا، واریزی‌های فروشندگان روزهای چهارشنبه هر هفته به‌صورت گروهی به شماره شبای ثبت‌شده واریز می‌گردد.
                </div>
            </div>

            <!-- Bank Information Form -->
            <div class="lg:col-span-2 bg-surface-container-lowest p-6 rounded-3xl stat-card-shadow border border-outline-variant/10 space-y-4">
                <h3 class="text-base font-black text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">account_balance</span>
                    <span>اطلاعات حساب بانکی و شماره شبا جهت واریز پایا</span>
                </h3>
                <p class="text-xs text-slate-500">شماره شبای معتبر بنام صاحب پت‌شاپ را وارد کنید. تمام مبالغ تسویه از طریق سامانه پایا بانک مرکزی به این شماره شبا ارسال می‌گردد.</p>

                <form method="POST" class="space-y-4 mt-4">
                    <input type="hidden" name="action" value="update_bank">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">نام بانک</label>
                            <input type="text" name="bank_name" value="<?= htmlspecialchars($sellerWallet['bank_name'] ?? 'بانک ملی ایران') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none" placeholder="مثال: ملت، صادرات، ملی">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">نام صاحب حساب</label>
                            <input type="text" name="bank_account_holder" value="<?= htmlspecialchars($sellerWallet['bank_account_holder'] ?? $sellerName) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شماره شبا (IBAN با پیشوند IR)</label>
                        <input type="text" name="bank_sheba" value="<?= htmlspecialchars($sellerWallet['bank_sheba'] ?? '') ?>" dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-secondary-container outline-none" placeholder="IR000000000000000000000000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شماره کارت بانکی (جهت تطبیق)</label>
                        <input type="text" name="bank_card_number" value="<?= htmlspecialchars($sellerWallet['bank_card_number'] ?? '') ?>" dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-secondary-container outline-none" placeholder="6037-xxxx-xxxx-xxxx">
                    </div>

                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all">
                        ذخیره اطلاعات بانکی
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- ── TAB 4: SHIPPING & POSTEX TRACKER ─────────────────────────────────── -->
    <section id="shipping-tab" class="seller-tab-content hidden space-y-4">
        <div class="bg-surface-container-lowest p-6 rounded-2xl stat-card-shadow border border-outline-variant/10 space-y-4">
            <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">markunread_mailbox</span>
                <span>سامانه رهگیری مرسولات پستی (پستکس و شرکت ملی پست)</span>
            </h2>
            <p class="text-xs text-slate-500">برای پیگیری وضعیت بسته‌های ارسالی خود، بارکد ۲۴ رقمی پستی را در کادر زیر وارد کنید.</p>

            <div class="flex gap-2 max-w-lg">
                <input type="text" id="externalTrackerInput" placeholder="کد رهگیری ۲۴ رقمی پستی..." dir="ltr" class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono outline-none focus:ring-2 focus:ring-secondary-container">
                <button onclick="checkPostTracking()" class="px-4 py-2.5 rounded-xl bg-secondary-container text-white font-bold text-xs hover:bg-orange-600 transition-all flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">search</span>
                    <span>رهگیری آنلاین</span>
                </button>
            </div>

            <div id="trackerResult" class="hidden p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-600">verified</span>
                    <span id="trackerStatus">مرسوله در مرکز تجزیه و مبادلات پستی قرار دارد.</span>
                </div>
                <div class="text-slate-500" id="trackerDetails">سامانه پست جمهوری اسلامی ایران وضعیت را در حالت حمل درون‌شهری گزارش کرده است.</div>
            </div>
        </div>
    </section>

    <!-- ── TAB 5: SETTINGS ─────────────────────────────────────────────────── -->
    <section id="settings-tab" class="seller-tab-content hidden space-y-4">
        <div class="bg-surface-container-lowest p-6 rounded-2xl stat-card-shadow border border-outline-variant/10 space-y-4 max-w-2xl">
            <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">store</span>
                <span>مشخصات فروشگاه و پت‌شاپ</span>
            </h2>
            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">نام پت‌شاپ</label>
                    <input type="text" value="<?= htmlspecialchars($sellerName) ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">شماره تماس مدیر فروشگاه</label>
                    <input type="text" value="<?= htmlspecialchars($currentUser['phone'] ?? '-') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">کد ملی / شناسه مالیاتی</label>
                    <input type="text" value="<?= htmlspecialchars($currentUser['national_id'] ?? '-') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 font-mono">
                </div>
                <p class="text-[11px] text-slate-400 mt-2">برای تغییر اطلاعات رسمی و پروانه کسب پت‌شاپ، لطفاً با پشتیبانی مرکزی آسنا ارتباط برقرار فرمایید.</p>
            </div>
        </div>
    </section>

</div>

<!-- ── MODAL: SUBMIT POST TRACKING CODE ──────────────────────────────────────── -->
<div id="trackingModal" class="fixed inset-0 bg-black/50 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 text-right">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">local_shipping</span>
                <span>ثبت بارکد مرسوله پستی</span>
            </h3>
            <button onclick="closeTrackingModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_tracking">
            <input type="hidden" id="modalOrderId" name="order_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">شرکت پستی یا حامل</label>
                <select id="modalCarrier" name="carrier_name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none font-bold">
                    <option value="پست پیشتاز (پستکس)">پست پیشتاز (پستکس)</option>
                    <option value="تیپاکس">تیپاکس (Tipax)</option>
                    <option value="پیک موتوری اختصاصی">پیک موتوری اختصاصی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">بارکد ۲۴ رقمی رهگیری پستی</label>
                <input type="text" id="modalTrackingCode" name="post_tracking_code" required dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-secondary-container outline-none" placeholder="100000000000000000000000">
                <p class="text-[11px] text-slate-400 mt-1">با ثبت این کد، پیامک رهگیری خودکار برای خریدار ارسال می‌گردد.</p>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" onclick="closeTrackingModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">انصراف</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-secondary-container hover:bg-orange-600 text-white text-xs font-bold shadow-md transition-all">ثبت و آغاز مهلت عودت</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: ADD NEW PRODUCT ────────────────────────────────────────────────── -->
<div id="newProductModal" class="fixed inset-0 bg-black/50 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 text-right max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">add_circle</span>
                <span>افزودن کالای جدید به پت‌شاپ</span>
            </h3>
            <button onclick="closeNewProductModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_product">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">نام محصول *</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none" placeholder="مثال: غذای خشک سگ رویال کنین مینی">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دسته‌بندی</label>
                    <select name="category" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none">
                        <option value="غذا و تغذیه">غذا و تغذیه</option>
                        <option value="تشویقی و اسنک">تشویقی و اسنک</option>
                        <option value="بهداشتی و مراقبت">بهداشتی و مراقبت</option>
                        <option value="لوازم جانبی">لوازم جانبی و اسباب‌بازی</option>
                        <option value="باکس و قلاده">باکس، قلاده و جای خواب</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">حیوان هدف</label>
                    <select name="target_animal" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none">
                        <option value="سگ">سگ</option>
                        <option value="گربه">گربه</option>
                        <option value="پرندگان">پرندگان</option>
                        <option value="جوندگان">جوندگان</option>
                        <option value="عمومی">عمومی</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">قیمت فروش (تومان) *</label>
                    <input type="number" name="price" required min="1000" step="1000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none" placeholder="250000">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">موجودی اولیه انبار *</label>
                    <input type="number" name="stock" required min="0" value="10" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">برند / شرکت سازنده</label>
                <input type="text" name="brand" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none" placeholder="مثال: Royal Canin / Reflex">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">آدرس تصویر محصول (یا مسیر داخلی)</label>
                <input type="text" name="image_url" dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-secondary-container outline-none" placeholder="assets/images/products/... یا https://...">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">توضیحات و مشخصات</label>
                <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none" placeholder="توضیحات، وزن، ترکیبات و نحوه مصرف..."></textarea>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2">
                <input type="checkbox" name="is_autoship" id="is_autoship" value="1" class="rounded text-secondary-container focus:ring-secondary-container">
                <label for="is_autoship" class="text-xs font-bold text-amber-900 cursor-pointer">
                    فعال‌سازی خرید دوره‌ای و منظم (Autoship) برای این کالا با ۵٪ تخفیف اشتراک
                </label>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" onclick="closeNewProductModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">انصراف</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-secondary-container hover:bg-orange-600 text-white text-xs font-bold shadow-md transition-all">افزودن کالا به فروشگاه</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTrackingModal(orderId, trackingCode, carrier) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalTrackingCode').value = trackingCode;
    if (carrier) document.getElementById('modalCarrier').value = carrier;
    document.getElementById('trackingModal').classList.remove('hidden');
}

function closeTrackingModal() {
    document.getElementById('trackingModal').classList.add('hidden');
}

function openNewProductModal() {
    document.getElementById('newProductModal').classList.remove('hidden');
}

function closeNewProductModal() {
    document.getElementById('newProductModal').classList.add('hidden');
}

function checkPostTracking() {
    const code = document.getElementById('externalTrackerInput').value.trim();
    if (!code) {
        alert('لطفاً کد رهگیری پستی را وارد کنید.');
        return;
    }
    const resultBox = document.getElementById('trackerResult');
    const statusText = document.getElementById('trackerStatus');
    const detailsText = document.getElementById('trackerDetails');
    
    resultBox.classList.remove('hidden');
    statusText.innerText = 'استعلام بارکد ' + code + ' با موفقیت انجام شد: وضعیت تحویل عادی';
    detailsText.innerText = 'مرسوله پستی در شبکه رهگیری سراسری ثبت و به مقصد ارسال شده است. مهلت ۷ روزه تضمین آسنا فعال می‌باشد.';
}

function filterProducts() {
    const q = document.getElementById('productSearchInput').value.toLowerCase().trim();
    document.querySelectorAll('.seller-product-card').forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(q) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/seller_footer.php'; ?>
