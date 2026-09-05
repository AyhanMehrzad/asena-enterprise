<?php
$currentPage = 'rfq_management';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';
require_once '../includes/WholesaleService.php';

$wholesale = new WholesaleService($pdo);
$message = '';
$messageType = '';

// Handle issue quote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_quote') {
    csrf_verify();
    $rfqId = (int)($_POST['rfq_id'] ?? 0);
    $totalAmount = (int)($_POST['quoted_total_amount'] ?? 0);
    $validUntil = $_POST['quote_valid_until'] ?? date('Y-m-d', strtotime('+7 days'));
    $notes = trim($_POST['admin_notes'] ?? '');
    $itemPrices = $_POST['item_price'] ?? [];

    if ($rfqId > 0 && $totalAmount > 0) {
        $ok = $wholesale->issueQuote($rfqId, $totalAmount, $validUntil, $notes, $itemPrices);
        if ($ok) {
            $message = 'پیش‌فاکتور رسمی با موفقیت صادر و پیامک قیمت به کلینیک ارسال گردید.';
            $messageType = 'success';
        } else {
            $message = 'خطا در صدور پیش‌فاکتور.';
            $messageType = 'error';
        }
    }
}

$statusFilter = $_GET['status'] ?? null;
if ($statusFilter === 'all') $statusFilter = null;
$rfqs = $wholesale->getRfqs($statusFilter);

// Detail view if requested
$selectedRfq = null;
if (!empty($_GET['view_id'])) {
    $selectedRfq = $wholesale->getRfqDetails((int)$_GET['view_id']);
}
?>

<div class="p-6 rtl text-right" dir="rtl">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">request_quote</span>
                مدیریت استعلام قیمت عمده و مناقصات کلینیک‌ها (Alibaba B2B RFQ Hub)
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">
                بررسی استعلام‌های عمده کلینیک‌ها و پت‌شاپ‌ها، تعیین قیمت پیشنهادی و صدور پیش‌فاکتور رسمی
            </p>
        </div>

        <div class="flex items-center gap-2 bg-surface-container p-1 rounded-xl text-xs">
            <a href="rfq_management.php" class="px-3 py-1.5 rounded-lg font-bold transition-all <?php echo empty($statusFilter) ? 'bg-primary text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                همه
            </a>
            <a href="?status=submitted" class="px-3 py-1.5 rounded-lg font-bold transition-all <?php echo $statusFilter === 'submitted' ? 'bg-amber-600 text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                در انتظار بررسی
            </a>
            <a href="?status=quoted" class="px-3 py-1.5 rounded-lg font-bold transition-all <?php echo $statusFilter === 'quoted' ? 'bg-blue-600 text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                پیش‌فاکتور صادرشده
            </a>
            <a href="?status=accepted" class="px-3 py-1.5 rounded-lg font-bold transition-all <?php echo $statusFilter === 'accepted' ? 'bg-status-active text-white shadow-sm' : 'text-on-surface hover:bg-white/50'; ?>">
                تایید و خریداری شده
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-xl mb-6 flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
            <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
            <span class="font-bold text-sm"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- If detailed RFQ selected for review/quote -->
    <?php if ($selectedRfq): ?>
        <div class="bg-white rounded-2xl p-6 border border-outline-variant/30 shadow-md mb-8">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/10">
                <div class="flex items-center gap-3">
                    <span class="p-2 bg-primary/10 text-primary rounded-xl material-symbols-outlined">description</span>
                    <div>
                        <h2 class="text-lg font-bold text-on-surface">
                            پیش‌فاکتور شماره: <?php echo htmlspecialchars($selectedRfq['proforma_invoice_num']); ?>
                        </h2>
                        <span class="text-xs text-on-surface-variant">کلینیک: <?php echo htmlspecialchars($selectedRfq['clinic_name']); ?> | نظام دامپزشکی: <?php echo htmlspecialchars($selectedRfq['vet_license_num'] ?: 'ثبت نشده'); ?></span>
                    </div>
                </div>
                <a href="rfq_management.php" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_forward</span> بازگشت به لیست
                </a>
            </div>

            <!-- Contact Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs mb-6 bg-surface-container-low p-4 rounded-xl">
                <div>
                    <span class="text-on-surface-variant block mb-1">رابط کلینیک:</span>
                    <strong><?php echo htmlspecialchars($selectedRfq['contact_person']); ?></strong>
                </div>
                <div>
                    <span class="text-on-surface-variant block mb-1">شماره تماس:</span>
                    <strong dir="ltr"><?php echo htmlspecialchars($selectedRfq['contact_phone']); ?></strong>
                </div>
                <div>
                    <span class="text-on-surface-variant block mb-1">تاریخ تحویل درخواستی:</span>
                    <strong><?php echo htmlspecialchars($selectedRfq['target_delivery_date'] ?: 'در اولین فرصت'); ?></strong>
                </div>
            </div>

            <!-- RFQ Items Table with Quote Inputs -->
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="issue_quote">
                <input type="hidden" name="rfq_id" value="<?php echo $selectedRfq['id']; ?>">

                <h3 class="font-bold text-sm text-on-surface mb-3">اقلام استعلام‌شده و تعیین قیمت پیشنهادی (Quotation):</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-xs text-right border-collapse">
                        <thead>
                            <tr class="bg-surface-container text-on-surface-variant border-b border-outline-variant/20">
                                <th class="p-3">شرح کالا / دارو</th>
                                <th class="p-3 text-center">تعداد درخواستی</th>
                                <th class="p-3 text-center">قیمت مدنظر کلینیک</th>
                                <th class="p-3 text-center">قیمت پیشنهادی واحد آسنا (تومان)</th>
                                <th class="p-3 text-center">مجموع ردیف (تومان)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedRfq['items'] as $it): 
                                $currentPrice = $it['quoted_unit_price'] ?: ($it['target_unit_price'] ?: 0);
                            ?>
                                <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/50">
                                    <td class="p-3 font-bold text-on-surface">
                                        <?php echo htmlspecialchars($it['product_title']); ?>
                                        <span class="text-[10px] text-on-surface-variant block"><?php echo $it['is_pharmacy'] ? 'داروخانه دامپزشکی' : 'پت‌شاپ عمده'; ?></span>
                                    </td>
                                    <td class="p-3 text-center font-bold text-base"><?php echo number_format($it['requested_quantity']); ?></td>
                                    <td class="p-3 text-center text-on-surface-variant"><?php echo $it['target_unit_price'] ? number_format($it['target_unit_price']) . ' تومان' : '-'; ?></td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="item_price[<?php echo $it['id']; ?>]" value="<?php echo $currentPrice; ?>" class="item-calc-price border border-outline-variant/40 rounded-lg px-2 py-1.5 w-32 text-center font-bold bg-white focus:outline-primary" data-qty="<?php echo $it['requested_quantity']; ?>" oninput="recalcRfqTotal()">
                                    </td>
                                    <td class="p-3 text-center font-bold text-primary item-row-total">
                                        <?php echo number_format($currentPrice * $it['requested_quantity']); ?> تومان
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quote Summary & Actions -->
                <div class="bg-surface-container p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="block text-xs text-on-surface-variant font-bold mb-1">مبلغ کل پیش‌فاکتور (تومان):</label>
                            <input type="number" id="rfq_total_input" name="quoted_total_amount" value="<?php echo $selectedRfq['quoted_total_amount'] ?: 0; ?>" class="border border-outline-variant/40 rounded-xl px-3 py-2 text-base font-bold bg-white focus:outline-primary w-48 text-center text-primary" required>
                        </div>
                        <div>
                            <label class="block text-xs text-on-surface-variant font-bold mb-1">اعتبار پیش‌فاکتور تا تاریخ:</label>
                            <input type="date" name="quote_valid_until" value="<?php echo $selectedRfq['quote_valid_until'] ?: date('Y-m-d', strtotime('+7 days')); ?>" class="border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-bold bg-white focus:outline-primary" required>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="bg-primary hover:bg-primary-container text-white text-xs font-bold px-6 py-3 rounded-xl flex items-center gap-2 shadow-md transition-all">
                            <span class="material-symbols-outlined text-sm">send</span>
                            ثبت و ارسال پیش‌فاکتور رسمی به کلینیک (با پیامک)
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <script>
        function recalcRfqTotal() {
            let grandTotal = 0;
            document.querySelectorAll('.item-calc-price').forEach(function(input) {
                let unit = parseInt(input.value) || 0;
                let qty = parseInt(input.getAttribute('data-qty')) || 1;
                let rowTotal = unit * qty;
                grandTotal += rowTotal;
                let rowEl = input.closest('tr').querySelector('.item-row-total');
                if (rowEl) rowEl.textContent = rowTotal.toLocaleString() + ' تومان';
            });
            let totalInput = document.getElementById('rfq_total_input');
            if (totalInput) totalInput.value = grandTotal;
        }
        </script>
    <?php endif; ?>

    <!-- RFQs Master Table -->
    <div class="bg-white rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-right border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface-variant border-b border-outline-variant/20">
                        <th class="p-3.5">شماره پیش‌فاکتور</th>
                        <th class="p-3.5">کلینیک و متقاضی</th>
                        <th class="p-3.5 text-center">شماره نظام دامپزشکی</th>
                        <th class="p-3.5 text-center">تعداد اقلام / واحد</th>
                        <th class="p-3.5 text-center">مبلغ پیشنهادی</th>
                        <th class="p-3.5 text-center">وضعیت</th>
                        <th class="p-3.5 text-center">تاریخ ثبت</th>
                        <th class="p-3.5 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <?php if (empty($rfqs)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-on-surface-variant">
                                موردی برای نمایش یافت نشد.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rfqs as $r): ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <td class="p-3.5 font-bold text-primary" dir="ltr"><?php echo htmlspecialchars($r['proforma_invoice_num']); ?></td>
                                <td class="p-3.5">
                                    <strong class="text-on-surface block"><?php echo htmlspecialchars($r['clinic_name']); ?></strong>
                                    <span class="text-on-surface-variant text-[11px]"><?php echo htmlspecialchars($r['contact_person']); ?> (<?php echo htmlspecialchars($r['contact_phone']); ?>)</span>
                                </td>
                                <td class="p-3.5 text-center font-mono"><?php echo htmlspecialchars($r['vet_license_num'] ?: '-'); ?></td>
                                <td class="p-3.5 text-center font-bold">
                                    <?php echo (int)$r['item_count']; ?> ردیف
                                    <span class="text-on-surface-variant block font-normal">(<?php echo number_format((int)$r['total_units']); ?> عدد)</span>
                                </td>
                                <td class="p-3.5 text-center font-bold text-sm">
                                    <?php echo $r['quoted_total_amount'] ? number_format($r['quoted_total_amount']) . ' تومان' : '<span class="text-on-surface-variant font-normal">تعیین نشده</span>'; ?>
                                </td>
                                <td class="p-3.5 text-center">
                                    <?php
                                    $rfqBadges = [
                                        'submitted'    => '<span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full font-bold">در انتظار بررسی</span>',
                                        'under_review' => '<span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full font-bold">در حال کارشناسی</span>',
                                        'quoted'       => '<span class="bg-purple-100 text-purple-800 px-2.5 py-1 rounded-full font-bold">پیش‌فاکتور ارسال شد</span>',
                                        'accepted'     => '<span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full font-bold">تایید و پرداخت شده</span>',
                                        'rejected'     => '<span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full font-bold">رد شده</span>',
                                        'expired'      => '<span class="bg-gray-100 text-gray-800 px-2.5 py-1 rounded-full font-bold">منقضی شده</span>',
                                    ];
                                    echo $rfqBadges[$r['status']] ?? $r['status'];
                                    ?>
                                </td>
                                <td class="p-3.5 text-center text-on-surface-variant" dir="ltr"><?php echo substr($r['created_at'], 0, 10); ?></td>
                                <td class="p-3.5 text-center">
                                    <a href="?view_id=<?php echo $r['id']; ?>" class="bg-primary/10 hover:bg-primary text-primary hover:text-white px-3 py-1.5 rounded-lg font-bold transition-colors inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">edit_note</span>
                                        بررسی و قیمت‌گذاری
                                    </a>
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
