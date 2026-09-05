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

// Fetch orders
$stmt = $pdo->query("SELECT o.*, u.name as user_name, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach items to orders
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
               COALESCE(pm.is_autoship, p.is_autoship) as is_autoship 
        FROM order_items oi 
        LEFT JOIN pharmacy_medicines pm ON oi.product_id = pm.id
        LEFT JOIN products p ON oi.product_id = p.id 
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

// Date Formatter for Jalali
$fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd HH:mm');
?>

<div class="p-8 max-w-[1400px] mx-auto rtl text-right" dir="rtl">
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-2xl font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl">local_shipping</span>
                مدیریت سفارشات و خط ارسال سازمانی (Amazon & Digikala Fulfillment)
            </h2>
            <p class="text-on-surface-variant font-body-md mt-1 text-xs">
                خط لوله ۸ مرحله‌ای پردازش سفارش، تخصیص شرکت حمل و نقل (تیپاکس/پست)، ارسال پیامک خودکار و صدور فاکتور رسمی
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="subscriptions.php?filter=today" class="flex items-center gap-2 bg-primary text-white px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-primary-container transition-all text-xs">
                <span class="material-symbols-outlined text-base">event_repeat</span>
                نوبت‌های اتوشیپ امروز
            </a>
            <a href="export_orders.php" class="flex items-center gap-2 bg-secondary-container text-white px-4 py-2.5 rounded-xl font-bold shadow-sm hover:opacity-90 transition-opacity text-xs">
                <span class="material-symbols-outlined text-base">download</span>
                خروجی اکسل / CSV
            </a>
        </div>
    </header>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-xl mb-6 flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
            <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
            <span class="font-bold text-sm"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden border border-outline-variant/30">
        <div class="p-6 border-b border-outline-variant bg-white flex items-center justify-between">
            <h3 class="font-bold text-base text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-base">inventory</span>
                لیست سفارشات فعال
            </h3>
            <span class="text-xs text-on-surface-variant">تعداد سفارشات اخیر: <?php echo count($orders); ?></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-surface-container text-on-surface-variant border-b border-outline-variant">
                    <tr>
                        <th class="px-5 py-3.5">شناسه و اقلام</th>
                        <th class="px-5 py-3.5">مشتری</th>
                        <th class="px-5 py-3.5 text-center">مبلغ کل (تومان)</th>
                        <th class="px-5 py-3.5 text-center">مرحله چرخه ارسال (Fulfillment Stage)</th>
                        <th class="px-5 py-3.5 text-center">حمل و نقل و رهگیری</th>
                        <th class="px-5 py-3.5 text-center">فاکتور رسمی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">هیچ سفارشی یافت نشد.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $order): 
                            $meta = OrderLifecycleService::getStatusMeta($order['status']);
                            $allowedNext = OrderLifecycleService::getAllowedNextStatuses($order['status']);
                        ?>
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="px-5 py-4 align-top" dir="ltr">
                                <div class="font-bold text-primary font-mono text-sm">#ORD-<?= $order['id'] ?></div>
                                <?php if (!empty($order['items'])): ?>
                                    <div class="mt-2 space-y-1 text-right" dir="rtl">
                                        <?php foreach($order['items'] as $item): 
                                            $is_pharma = (str_contains($item['category'] ?? '', 'دارو') || str_contains($item['category'] ?? '', 'مکمل') || !empty($item['pharmacy_tag']));
                                            $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : '../assets/images/toy-mouse.jpg';
                                        ?>
                                            <div class="flex items-center gap-2 bg-surface-container-low p-1.5 rounded-lg border border-outline-variant/20 text-xs">
                                                <img src="<?= $img ?>" class="w-8 h-8 rounded-md object-cover bg-white shrink-0 border border-outline-variant/30">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1">
                                                        <?php if($is_pharma): ?>
                                                            <span class="bg-purple-100 text-purple-800 px-1 rounded text-[9px] font-bold">💊 داروخانه</span>
                                                        <?php else: ?>
                                                            <span class="bg-blue-100 text-blue-800 px-1 rounded text-[9px] font-bold">🛍️ پت‌شاپ</span>
                                                        <?php endif; ?>
                                                        <?php if(!empty($item['is_autoship'])): ?>
                                                            <span class="bg-amber-100 text-amber-800 px-1 rounded text-[9px] font-bold">🔄 Autoship</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-[11px] font-bold text-primary truncate mt-0.5"><?= htmlspecialchars($item['product_name_snapshot']) ?></p>
                                                    <p class="text-[10px] text-on-surface-variant"><?= $item['quantity'] ?> عدد × <?= number_format($item['price_at_purchase']) ?> ت</p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <strong class="text-on-surface block"><?= htmlspecialchars($order['user_name']) ?></strong>
                                <span class="text-on-surface-variant font-mono text-[11px]" dir="ltr"><?= htmlspecialchars($order['user_phone']) ?></span>
                                <?php if (!empty($order['shipping_address'])): ?>
                                    <p class="text-[10px] text-on-surface-variant mt-1 line-clamp-2" title="<?= htmlspecialchars($order['shipping_address']) ?>">
                                        📍 <?= htmlspecialchars($order['shipping_address']) ?>
                                    </p>
                                <?php endif; ?>
                            </td>

                            <td class="px-5 py-4 font-bold align-top text-center text-sm">
                                <?= number_format($order['total_amount']) ?>
                                <span class="text-[10px] font-normal text-on-surface-variant block">تومان</span>
                            </td>

                            <!-- 8-Stage Interactive Status Switcher -->
                            <td class="px-5 py-4 align-top text-center">
                                <form action="orders.php" method="POST" class="flex flex-col gap-1.5 items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold <?= $meta['badge'] ?>">
                                        <span><?= $meta['title'] ?></span>
                                    </div>

                                    <div class="flex items-center gap-1 mt-1">
                                        <select name="status" class="border border-outline-variant/40 rounded-lg py-1 px-2 text-[11px] font-bold bg-surface-container-low focus:outline-primary">
                                            <option value="" disabled selected>تغییر مرحله...</option>
                                            <option value="confirmed">تأیید انبارداری</option>
                                            <option value="picking">در حال جمع‌آوری اقلام</option>
                                            <option value="packed">بسته‌بندی و الصاق بارکد</option>
                                            <option value="handed_over">تحویل به پست / تیپاکس</option>
                                            <option value="shipped">در مسیر ارسال</option>
                                            <option value="out_for_delivery">پیک در مسیر تحویل</option>
                                            <option value="delivered">تحویل نهایی به مشتری</option>
                                            <option value="cancelled">لغو سفارش</option>
                                        </select>
                                        <button type="submit" class="bg-primary text-white p-1 rounded-lg hover:bg-primary-container" title="اعمال تغییر وضعیت">
                                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                        </button>
                                    </div>
                                </form>
                            </td>

                            <!-- Carrier & Tracking Info -->
                            <td class="px-5 py-4 align-top text-center">
                                <?php if (!empty($order['tracking_code'])): ?>
                                    <div class="bg-blue-50 border border-blue-200 p-2 rounded-xl text-center inline-block">
                                        <span class="text-[10px] text-blue-800 font-bold block"><?= htmlspecialchars($order['carrier_name'] ?: 'پست پیشتاز') ?></span>
                                        <span class="font-mono text-xs font-bold text-primary block mt-0.5" dir="ltr"><?= htmlspecialchars($order['tracking_code']) ?></span>
                                        <?php if (stripos($order['carrier_name'] ?? '', 'tipax') !== false): ?>
                                            <a href="https://tipaxco.com/tracking?id=<?= urlencode($order['tracking_code']) ?>" target="_blank" class="text-[10px] text-blue-600 underline font-bold mt-1 inline-block">رهگیری تیپاکس ↗</a>
                                        <?php else: ?>
                                            <a href="https://tracking.post.ir/?id=<?= urlencode($order['tracking_code']) ?>" target="_blank" class="text-[10px] text-blue-600 underline font-bold mt-1 inline-block">رهگیری پست ↗</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <form action="orders.php" method="POST" class="flex flex-col gap-1 text-[10px]">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <input type="hidden" name="status" value="handed_over">
                                        
                                        <input type="text" name="carrier_name" placeholder="نام شرکت (تیپاکس/پست)" class="border border-outline-variant/40 rounded px-1.5 py-0.5 text-[10px] bg-white">
                                        <input type="text" name="tracking_code" placeholder="کد رهگیری مرسوله" class="border border-outline-variant/40 rounded px-1.5 py-0.5 text-[10px] bg-white" dir="ltr">
                                        <button type="submit" class="bg-cyan-700 hover:bg-cyan-800 text-white font-bold py-1 px-2 rounded text-[10px] transition-colors">
                                            ثبت کد و ارسال پیامک
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                            <!-- Official Tax Invoice (Digikala standard) -->
                            <td class="px-5 py-4 align-top text-center">
                                <a href="../actions/generate_invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="inline-flex items-center gap-1 bg-surface-container hover:bg-primary hover:text-white text-primary px-3 py-1.5 rounded-xl font-bold transition-all border border-outline-variant/30 text-[11px] shadow-sm">
                                    <span class="material-symbols-outlined text-xs">receipt_long</span>
                                    فاکتور رسمی (ماده ۱۶۹)
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

<?php require_once 'includes/admin_footer.php'; ?>
