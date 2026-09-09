<?php
$currentPage = 'orders';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';
require_once '../includes/OrderLifecycleService.php';

$lifecycle = new OrderLifecycleService($pdo);
$message = '';
$messageType = '';

// Handle status update via OrderLifecycleService
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    csrf_verify();
    
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'] ?? '';
    $carrier = trim($_POST['carrier_name'] ?? '');
    $tracking = trim($_POST['tracking_code'] ?? '');
    $notes = trim($_POST['operator_notes'] ?? '');
    $actorId = (int)($_SESSION['user_id'] ?? 1);

    if ($order_id > 0 && !empty($new_status)) {
        $res = $lifecycle->transition($order_id, $new_status, 'admin', $actorId, $carrier, $tracking, $notes);
        if ($res['success']) {
            $message = $res['message'];
            $messageType = 'success';
        } else {
            $message = $res['message'];
            $messageType = 'error';
        }
    }
}

// Fetch orders with recipient detailed address and postal code
$stmt = $pdo->query("
    SELECT o.*, 
           u.name as user_name, u.phone as user_phone, u.city as user_city, 
           u.postal_code as user_postal_code, u.address as user_home_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 100
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach items and seller/shipper attribution to orders
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($order_ids), '?'));
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, 
               COALESCE(pm.image_url, p.image_url) as image_url, 
               COALESCE(pm.category, p.category) as category, 
               COALESCE(pm.brand, p.brand) as brand, 
               COALESCE(pm.target_animal, p.target_animal) as target_animal, 
               COALESCE(pm.pharmacy_tag, p.pharmacy_tag) as pharmacy_tag, 
               COALESCE(pm.is_autoship, p.is_autoship) as is_autoship,
               s.name as seller_name,
               s.phone as seller_phone
        FROM order_items oi 
        LEFT JOIN pharmacy_medicines pm ON oi.product_id = pm.id
        LEFT JOIN products p ON oi.product_id = p.id 
        LEFT JOIN users s ON oi.seller_id = s.id
        WHERE oi.order_id IN ($ph)
    ");
    $itemsStmt->execute($order_ids);
    $all_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    $items_by_order = [];
    foreach ($all_items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
    foreach ($orders as &$order) {
        $order['items'] = $items_by_order[$order['id']] ?? [];
    }
    unset($order);
}

// Summary Statistics for Logistics Overview
$totalOrders = count($orders);
$shippedCount = 0;
$deliveredCount = 0;
$pendingPostCodeCount = 0;
foreach ($orders as $ord) {
    if ($ord['status'] === 'delivered') $deliveredCount++;
    if (in_array($ord['status'], ['shipped', 'handed_over', 'out_for_delivery'])) $shippedCount++;
    if (empty($ord['post_tracking_code']) && empty($ord['tracking_code']) && in_array($ord['status'], ['confirmed', 'picking', 'packed'])) {
        $pendingPostCodeCount++;
    }
}
?>

<div class="p-6 md:p-8 max-w-[1600px] mx-auto rtl text-right space-y-6" dir="rtl">
    
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl text-primary">local_shipping</span>
                <h1 class="text-2xl font-black text-slate-900">سفارشات سراسری، نشانی خریداران و رهگیری پستکس</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800">
                    پلتفرم ملی لجستیک
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                نظارت بر ارسال کالا توسط فروشندگان، مشاهده نشانی کامل و کد پستی خریداران، و استعلام برخط وب‌سرویس پستکس و پست پیشتاز.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="subscriptions.php?filter=today" class="flex items-center gap-1.5 bg-primary text-white px-3.5 py-2 rounded-xl text-xs font-bold hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-sm">event_repeat</span>
                <span>نوبت‌های اتوشیپ امروز</span>
            </a>
            <a href="export_orders.php" class="flex items-center gap-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold transition-colors">
                <span class="material-symbols-outlined text-sm">download</span>
                <span>خروجی اکسل</span>
            </a>
        </div>
    </header>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 text-xs font-bold <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
            <span class="material-symbols-outlined text-base"><?= $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
            <span><?= htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Logistics Metric Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-slate-200/80 space-y-1">
            <span class="text-xs text-slate-500 font-bold">کل سفارشات اخیر</span>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900"><?= number_format($totalOrders) ?></span>
                <span class="text-xs text-slate-400">سفارش</span>
            </div>
            <p class="text-[10px] text-slate-400">در سطح کشور</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-blue-200 bg-blue-50/20 space-y-1">
            <span class="text-xs text-blue-700 font-bold">مرسولات در حال ترانزیت پستی</span>
            <div class="flex items-baseline gap-1.5 text-blue-600">
                <span class="text-2xl font-black"><?= number_format($shippedCount) ?></span>
                <span class="text-xs font-bold">بسته پستی</span>
            </div>
            <p class="text-[10px] text-blue-500">دارای بارکد پستی و در مسیر مقصد</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-emerald-200 bg-emerald-50/20 space-y-1">
            <span class="text-xs text-emerald-700 font-bold">تحویل قطعی شده (تایید پستکس)</span>
            <div class="flex items-baseline gap-1.5 text-emerald-600">
                <span class="text-2xl font-black"><?= number_format($deliveredCount) ?></span>
                <span class="text-xs font-bold">بسته</span>
            </div>
            <p class="text-[10px] text-emerald-600">در پنجره تضمین ۷ روزه یا تسویه‌شده</p>
        </div>

        <div class="bg-white p-4 rounded-2xl stat-card-shadow border border-amber-200 bg-amber-50/20 space-y-1">
            <span class="text-xs text-amber-700 font-bold">در انتظار صدور بارکد توسط فروشنده</span>
            <div class="flex items-baseline gap-1.5 text-amber-600">
                <span class="text-2xl font-black"><?= number_format($pendingPostCodeCount) ?></span>
                <span class="text-xs font-bold">مورد</span>
            </div>
            <p class="text-[10px] text-amber-600">نیازمند تسریع در تحویل به پست</p>
        </div>
    </div>

    <!-- Orders Table -->
    <section class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">inventory_2</span>
                <h2 class="font-black text-sm text-slate-900">فهرست جامع سفارشات و اطلاعات ارسال</h2>
            </div>
            <span class="text-xs text-slate-400">تعداد ردیف‌ها: <?= count($orders) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 font-black">شناسه و اقلام سفارش</th>
                        <th class="px-4 py-3.5 font-black">خریدار و نشانی پستی (Home Location)</th>
                        <th class="px-4 py-3.5 font-black">تامین‌کننده (فروشنده / پت‌شاپ)</th>
                        <th class="px-4 py-3.5 font-black text-center">مبلغ کل</th>
                        <th class="px-4 py-3.5 font-black text-center">مرحله چرخه سفارش</th>
                        <th class="px-4 py-3.5 font-black text-center">رهگیری برخط وب‌سرویس پستکس</th>
                        <th class="px-4 py-3.5 font-black text-center">فاکتور</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">هیچ سفارشی در سامانه یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $order): 
                            $meta = OrderLifecycleService::getStatusMeta($order['status']);
                            $trackingCode = $order['post_tracking_code'] ?: ($order['tracking_code'] ?: '');
                            $hasTracking = !empty($trackingCode);
                            $carrierName = $order['carrier_name'] ?: 'پست پیشتاز (پستکس)';
                            $addressText = $order['shipping_address'] ?: ($order['user_home_address'] ?: 'نشانی ثبت نشده');
                            $cityText = $order['user_city'] ?: 'نامشخص';
                            $postalCode = $order['user_postal_code'] ?: 'ثبت‌نشده';
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors" id="order-row-<?= $order['id'] ?>">
                            
                            <!-- Order ID & Items -->
                            <td class="px-4 py-4 align-top min-w-[200px]">
                                <div class="font-black text-primary font-mono text-sm" dir="ltr">#ORD-<?= $order['id'] ?></div>
                                <span class="text-[10px] text-slate-400 block mt-0.5">
                                    ثبت: <?= date('Y/m/d H:i', strtotime($order['created_at'])) ?>
                                </span>

                                <?php if (!empty($order['items'])): ?>
                                    <div class="mt-2.5 space-y-1.5">
                                        <?php foreach($order['items'] as $item): 
                                            $is_pharma = (str_contains($item['category'] ?? '', 'دارو') || str_contains($item['category'] ?? '', 'مکمل') || !empty($item['pharmacy_tag']));
                                            $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : '../assets/images/toy-mouse.jpg';
                                            if (!str_starts_with($img, 'http') && !str_starts_with($img, '../')) {
                                                $img = '../' . $img;
                                            }
                                        ?>
                                            <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200/60 text-xs">
                                                <img src="<?= $img ?>" class="w-8 h-8 rounded-lg object-cover bg-white shrink-0 border border-slate-200">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1">
                                                        <?php if($is_pharma): ?>
                                                            <span class="bg-purple-100 text-purple-800 px-1 rounded text-[8px] font-bold">💊 دارو</span>
                                                        <?php else: ?>
                                                            <span class="bg-blue-100 text-blue-800 px-1 rounded text-[8px] font-bold">🛍️ پت‌شاپ</span>
                                                        <?php endif; ?>
                                                        <?php if(!empty($item['is_autoship'])): ?>
                                                            <span class="bg-amber-100 text-amber-800 px-1 rounded text-[8px] font-bold">🔄 اتوشیپ</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-[11px] font-bold text-slate-900 truncate mt-0.5"><?= htmlspecialchars($item['product_name_snapshot'] ?: 'کالا') ?></p>
                                                    <p class="text-[10px] text-slate-500"><?= $item['quantity'] ?> عدد × <?= number_format($item['price_at_purchase']) ?> ت</p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Buyer Info & Detailed Home Location & Postal Code -->
                            <td class="px-4 py-4 align-top max-w-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-slate-400">person</span>
                                    <strong class="text-slate-900 text-xs font-black"><?= htmlspecialchars($order['user_name']) ?></strong>
                                </div>
                                <span class="text-slate-500 font-mono text-[11px] block mt-0.5" dir="ltr">
                                    📞 <?= htmlspecialchars($order['user_phone']) ?>
                                </span>

                                <!-- Address & Postal Code Card -->
                                <div class="mt-2 p-2 rounded-xl bg-slate-100/90 border border-slate-200 text-[11px] space-y-1">
                                    <div class="flex items-center gap-1 text-slate-700 font-bold">
                                        <span class="material-symbols-outlined text-xs text-sky-600">location_on</span>
                                        <span>شهر: <?= htmlspecialchars($cityText) ?></span>
                                    </div>
                                    <p class="text-slate-600 text-[10px] leading-relaxed line-clamp-2" title="<?= htmlspecialchars($addressText) ?>">
                                        <?= htmlspecialchars($addressText) ?>
                                    </p>
                                    <div class="flex items-center justify-between pt-1 border-t border-slate-200 text-[10px]">
                                        <span class="text-slate-500 font-bold">کد پستی ۱۰ رقمی:</span>
                                        <code class="font-mono font-black text-indigo-700 dir-ltr bg-white px-1.5 py-0.5 rounded border border-indigo-200">
                                            <?= htmlspecialchars($postalCode) ?>
                                        </code>
                                    </div>
                                </div>
                            </td>

                            <!-- Shipper / Seller Attribution -->
                            <td class="px-4 py-4 align-top">
                                <?php 
                                    $firstItem = $order['items'][0] ?? null;
                                    $sellerName = $firstItem['seller_name'] ?? 'فروشگاه رسمی آسنا';
                                    $sellerPhone = $firstItem['seller_phone'] ?? '---';
                                ?>
                                <div class="flex items-center gap-1.5 text-amber-700 font-bold">
                                    <span class="material-symbols-outlined text-sm">storefront</span>
                                    <span class="text-xs"><?= htmlspecialchars($sellerName) ?></span>
                                </div>
                                <span class="text-slate-400 font-mono text-[10px] block mt-0.5" dir="ltr">
                                    <?= htmlspecialchars($sellerPhone) ?>
                                </span>
                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    تامین‌کننده مستقیم
                                </span>
                            </td>

                            <!-- Total Amount & Escrow Guarantee -->
                            <td class="px-4 py-4 align-top text-center">
                                <div class="font-black text-sm text-slate-900">
                                    <?= number_format($order['total_amount']) ?>
                                    <span class="text-[10px] font-normal text-slate-400 block">تومان</span>
                                </div>
                                <div class="mt-1.5">
                                    <?php if ($order['escrow_status'] === 'cleared_for_payout' || $order['escrow_status'] === 'settled'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            تسویه شده پایا
                                        </span>
                                    <?php elseif ($order['escrow_status'] === 'delivered_in_inspection'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            مهلت ۷ روزه تضمین
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">
                                            در انتظار تحویل
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Fulfillment Stage Interactive -->
                            <td class="px-4 py-4 align-top text-center">
                                <form action="orders.php" method="POST" class="flex flex-col gap-1.5 items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black <?= $meta['badge'] ?>">
                                        <span><?= $meta['title'] ?></span>
                                    </div>

                                    <div class="flex items-center gap-1 mt-1">
                                        <select name="status" class="border border-slate-300 rounded-lg py-1 px-2 text-[10px] font-bold bg-white focus:outline-primary">
                                            <option value="" disabled selected>تغییر مرحله...</option>
                                            <option value="confirmed">تأیید انبارداری</option>
                                            <option value="picking">جمع‌آوری اقلام</option>
                                            <option value="packed">بسته‌بندی و الصاق بارکد</option>
                                            <option value="handed_over">تحویل به پست / تیپاکس</option>
                                            <option value="shipped">در مسیر ارسال</option>
                                            <option value="out_for_delivery">پیک در مسیر تحویل</option>
                                            <option value="delivered">تحویل نهایی به مشتری</option>
                                            <option value="cancelled">لغو سفارش</option>
                                        </select>
                                        <button type="submit" class="bg-primary text-white p-1 rounded-lg hover:bg-primary-container" title="اعمال مرحله">
                                            <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                        </button>
                                    </div>
                                </form>
                            </td>

                            <!-- Carrier & Live Postex Tracking Trigger -->
                            <td class="px-4 py-4 align-top text-center min-w-[170px]">
                                <?php if ($hasTracking): ?>
                                    <div class="bg-sky-50/80 border border-sky-200 p-2.5 rounded-2xl text-center inline-flex flex-col items-center gap-1 w-full">
                                        <span class="text-[10px] text-sky-900 font-bold block"><?= htmlspecialchars($carrierName) ?></span>
                                        <code class="font-mono text-xs font-black text-sky-700 block dir-ltr tracking-wider select-all"><?= htmlspecialchars($trackingCode) ?></code>
                                        
                                        <!-- Instant Postex Live Trigger Button -->
                                        <button type="button" 
                                                onclick="openPostexTrackingModal(<?= (int)$order['id'] ?>, '<?= htmlspecialchars(addslashes($trackingCode)) ?>')"
                                                class="mt-1 w-full py-1.5 px-2 bg-sky-600 hover:bg-sky-700 active:scale-95 text-white rounded-xl text-[10px] font-black flex items-center justify-center gap-1 shadow-sm transition-all">
                                            <span class="material-symbols-outlined text-xs">search</span>
                                            <span>استعلام آنی وضعیت پستکس</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <form action="orders.php" method="POST" class="flex flex-col gap-1 text-[10px]">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <input type="hidden" name="status" value="shipped">
                                        
                                        <input type="text" name="carrier_name" value="پست پیشتاز (پستکس)" placeholder="شرکت پستی" class="border border-slate-300 rounded px-1.5 py-0.5 text-[10px] bg-white">
                                        <input type="text" name="tracking_code" placeholder="بارکد ۲۴ رقمی پست" class="border border-slate-300 rounded px-1.5 py-0.5 text-[10px] bg-white font-mono" dir="ltr">
                                        <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-1 px-2 rounded text-[10px] transition-colors">
                                            ثبت بارکد پستی
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                            <!-- Tax Invoice -->
                            <td class="px-4 py-4 align-top text-center">
                                <a href="../actions/generate_invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="inline-flex items-center gap-1 bg-slate-100 hover:bg-primary hover:text-white text-slate-700 px-2.5 py-1.5 rounded-xl font-bold transition-all border border-slate-200 text-[10px] shadow-sm">
                                    <span class="material-symbols-outlined text-xs">receipt_long</span>
                                    فاکتور رسمی
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- ==========================================================================
     LIVE POSTEX & IRAN POST TRACKING MODAL
     ========================================================================== -->
<div id="postex-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-100 text-right animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-sky-50 to-indigo-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-600 text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-xl">markunread_mailbox</span>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">استعلام برخط سامانه پستکس و پست پیشتاز</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">بارکد ۲۴ رقمی رهگیری مرسوله پستی</p>
                </div>
            </div>
            <button onclick="closePostexModal()" class="w-8 h-8 rounded-full bg-white text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors shadow-sm">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-5 custom-scrollbar" id="postex-modal-body">
            <!-- Loading State -->
            <div id="postex-loading" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
                <div class="w-10 h-10 border-4 border-sky-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-bold text-slate-600">در حال اتصال به وب‌سرویس پستکس و دریافت آخرین وضعیت...</span>
            </div>

            <!-- Content Area (injected dynamically) -->
            <div id="postex-result-container" class="hidden space-y-4">
                <!-- Recipient & Barcode Overview Strip -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">شماره سفارش:</span>
                        <strong class="text-slate-900 font-mono" id="m-order-id">#ORD-0</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">بارکد ثبتی پست:</span>
                        <code class="font-mono text-sky-700 font-black tracking-wider dir-ltr" id="m-barcode">---</code>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">گیرنده مرسوله:</span>
                        <strong class="text-slate-800" id="m-recipient-name">---</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">تلفن گیرنده:</span>
                        <span class="font-mono" id="m-recipient-phone">---</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200">
                        <span class="text-slate-500 block text-[11px]">نشانی و کد پستی ۱۰ رقمی:</span>
                        <p class="text-slate-800 font-bold text-[11px] mt-0.5 leading-relaxed" id="m-recipient-address">---</p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-mono font-bold text-[11px]" id="m-postal-code">کد پستی: ---</span>
                    </div>
                </div>

                <!-- Current Delivery Status Banner -->
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs" id="m-status-banner">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-lg">local_shipping</span>
                        <span class="font-bold text-emerald-900" id="m-status-title">در مسیر توزیع</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-white" id="m-status-badge">برخط</span>
                </div>

                <!-- Tracking Events Timeline -->
                <div>
                    <h4 class="font-bold text-xs text-slate-800 mb-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-sky-600">history</span>
                        <span>گاه‌شمار رویدادهای پستی (Checkpoints)</span>
                    </h4>
                    <div class="relative pr-6 border-r-2 border-sky-200 space-y-4 text-xs" id="m-events-timeline">
                        <!-- Events injected here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[10px] text-slate-400">تایید شده توسط سرویس ملی پستکس آسنا</span>
            <button onclick="closePostexModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition-colors">
                بستن پنجره
            </button>
        </div>

    </div>
</div>

<script>
function openPostexTrackingModal(orderId, barcode) {
    const modal = document.getElementById('postex-modal');
    const loading = document.getElementById('postex-loading');
    const resultContainer = document.getElementById('postex-result-container');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loading.classList.remove('hidden');
    resultContainer.classList.add('hidden');

    fetch(`../actions/postex_track_action.php?order_id=${encodeURIComponent(orderId)}&tracking_code=${encodeURIComponent(barcode)}`)
        .then(res => res.json())
        .then(data => {
            loading.classList.add('hidden');
            resultContainer.classList.remove('hidden');

            if (data.success) {
                document.getElementById('m-order-id').innerText = `#ORD-${data.order_id}`;
                document.getElementById('m-barcode').innerText = data.tracking_code;
                document.getElementById('m-recipient-name').innerText = data.recipient.name;
                document.getElementById('m-recipient-phone').innerText = data.recipient.phone;
                document.getElementById('m-recipient-address').innerText = `${data.recipient.city} - ${data.recipient.address}`;
                document.getElementById('m-postal-code').innerText = `کد پستی: ${data.recipient.postal_code}`;
                document.getElementById('m-status-title').innerText = data.current_status;

                if (data.is_delivered) {
                    document.getElementById('m-status-badge').innerText = 'تحویل قطعی';
                    document.getElementById('m-status-banner').className = 'p-3.5 rounded-2xl bg-emerald-50 border border-emerald-300 flex items-center justify-between text-xs';
                } else {
                    document.getElementById('m-status-badge').innerText = 'در مسیر';
                    document.getElementById('m-status-banner').className = 'p-3.5 rounded-2xl bg-sky-50 border border-sky-300 flex items-center justify-between text-xs';
                }

                // Render Timeline
                const timeline = document.getElementById('m-events-timeline');
                timeline.innerHTML = '';

                if (data.events && data.events.length > 0) {
                    data.events.forEach((evt, idx) => {
                        const isFirst = (idx === 0);
                        const isLast = (idx === data.events.length - 1);
                        timeline.insertAdjacentHTML('beforeend', `
                            <div class="relative group">
                                <div class="absolute -right-[31px] top-1 w-3.5 h-3.5 rounded-full ${isLast ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-sky-500'}"></div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <strong class="text-slate-900 font-bold text-xs">${escapeHtml(evt.status || evt.description)}</strong>
                                        <span class="text-[10px] text-slate-400 font-mono">${escapeHtml(evt.event_time || evt.date || '')}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">${escapeHtml(evt.location || '')} ${evt.description && evt.description !== evt.status ? ' - ' + escapeHtml(evt.description) : ''}</p>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    timeline.innerHTML = '<div class="text-slate-400 text-xs">هیچ رویدادی تاکنون در پایانه ثبت نشده است.</div>';
                }
            } else {
                resultContainer.innerHTML = `
                    <div class="p-6 text-center text-rose-600 bg-rose-50 rounded-2xl border border-rose-200 text-xs font-bold">
                        ${escapeHtml(data.message || 'خطا در برقراری ارتباط با وب‌سرویس')}
                    </div>
                `;
            }
        })
        .catch(err => {
            loading.classList.add('hidden');
            resultContainer.classList.remove('hidden');
            resultContainer.innerHTML = `
                <div class="p-6 text-center text-rose-600 bg-rose-50 rounded-2xl border border-rose-200 text-xs font-bold">
                    خطا در ارتباط با سرور رهگیری. لطفاً مجدداً تلاش فرمایید.
                </div>
            `;
        });
}

function closePostexModal() {
    const modal = document.getElementById('postex-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
