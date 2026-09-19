<?php
$currentPage = 'promo_codes';
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$promoService = App::promo();

$success = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    SecurityMiddleware::validateCsrfToken($_POST['csrf_token'] ?? '');
    $action = $_POST['action'];

    if ($action === 'save_promo') {
        $res = $promoService->savePromoCode($_POST);
        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['message'];
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['is_active'];
        $promoService->togglePromoStatus($id, $status);
        $success = "وضعیت کد تخفیف بروزرسانی شد.";
    } elseif ($action === 'end_promo') {
        $id = (int)$_POST['id'];
        if ($promoService->endPromoCode($id)) {
            $success = "مهلت کد تخفیف فوراً پایان یافت و غیرفعال شد.";
        } else {
            $error = "خطا در پایان دادن به کد تخفیف.";
        }
    } elseif ($action === 'delete_promo') {
        $id = (int)$_POST['id'];
        $delRes = $promoService->deletePromoCode($id);
        if ($delRes['success']) {
            $success = $delRes['message'];
        } else {
            $error = $delRes['message'];
        }
    } elseif ($action === 'broadcast_promo') {
        $promoId = (int)$_POST['promo_id'];
        $title = trim($_POST['title'] ?? '');
        $messageText = trim($_POST['message'] ?? '');
        $audience = trim($_POST['target_audience'] ?? 'all');
        $sendSms = !empty($_POST['send_sms']);

        if (empty($title) || empty($messageText)) {
            $error = "عنوان اعلان و متن پیام الزامی است.";
        } else {
            $broadRes = $promoService->broadcastPromoNotification($promoId, $title, $messageText, $audience, $sendSms);
            if (!empty($broadRes['success'])) {
                $smsInfo = $sendSms ? " (همچنین پیامک به {$broadRes['sms_sent_count']} شماره ارسال شد)" : "";
                $success = "اعلان کد تخفیف با موفقیت به کاربران هدف ارسال گردید.{$smsInfo}";
            } else {
                $error = "خطا در ارسال اعلان: " . ($broadRes['error'] ?? 'نامشخص');
            }
        }
    }
}

// Fetch all codes
$allPromos = $promoService->getAllPromoCodes();
$auditLogs = $promoService->getPromoAuditLogs(25);

// Calculate summary stats
$totalActiveCodes = 0;
$totalRedemptions = 0;
$totalPlatformDiscountGranted = 0;

foreach ($allPromos as $p) {
    if (!empty($p['is_active'])) $totalActiveCodes++;
    $totalRedemptions += (int)($p['total_redemptions'] ?? 0);
    $totalPlatformDiscountGranted += (int)($p['total_discount_granted'] ?? 0);
}

require_once 'includes/admin_header.php';
?>

<div class="space-y-6">

    <!-- Header & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-2xl text-[#fd8100]">confirmation_number</span>
                <h1 class="text-lg sm:text-xl font-bold text-slate-800">مدیریت کدهای تخفیف و پروموشن‌ها</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">تعریف، ویرایش، پایان دادن به کدهای تخفیف و ارسال اعلان هوشمند به کاربران سامانه</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openCreatePromoModal()" class="px-5 py-2.5 rounded-2xl bg-[#001a48] hover:bg-[#002d72] text-white font-bold text-xs flex items-center gap-2 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                <span>ایجاد کد تخفیف جدید</span>
            </button>
        </div>
    </div>

    <?php if (!empty($success)): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs flex items-center gap-2 shadow-2xs">
        <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs flex items-center gap-2 shadow-2xs">
        <span class="material-symbols-outlined text-base text-rose-600">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- KPI Statistics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">local_offer</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">کدهای تخفیف فعال</span>
                <strong class="text-lg font-bold text-slate-800 font-mono"><?= number_format($totalActiveCodes) ?> <span class="text-xs text-slate-500 font-normal">کد فعال</span></strong>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">how_to_reg</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">تعداد دفعات استفاده خریداران</span>
                <strong class="text-lg font-bold text-slate-800 font-mono"><?= number_format($totalRedemptions) ?> <span class="text-xs text-slate-500 font-normal">بار خرید</span></strong>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">volunteer_activism</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">تخفیف اعطا شده (سهم پلتفرم)</span>
                <strong class="text-lg font-bold text-emerald-700 font-mono"><?= number_format($totalPlatformDiscountGranted) ?> <span class="text-xs text-slate-500 font-normal">تومان</span></strong>
            </div>
        </div>
    </div>

    <!-- Active Promo Codes Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400">format_list_bulleted</span>
                <span>فهرست پروموشن‌ها و کدهای تخفیف</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono"><?= count($allPromos) ?> مورد</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="p-4 font-bold">کد تخفیف</th>
                        <th class="p-4 font-bold">عنوان کمپین</th>
                        <th class="p-4 font-bold">نوع و ارزش تخفیف</th>
                        <th class="p-4 font-bold">سقف / حداقل خرید</th>
                        <th class="p-4 font-bold text-center">استفاده‌ها</th>
                        <th class="p-4 font-bold text-center">مهلت اعتبار</th>
                        <th class="p-4 font-bold text-center">وضعیت</th>
                        <th class="p-4 font-bold text-center">عملیات مدیریتی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($allPromos)): ?>
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400">هیچ کد تخفیفی ثبت نشده است.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($allPromos as $promo): 
                            $isExpired = !empty($promo['expires_at']) && $promo['expires_at'] < date('Y-m-d H:i:s');
                            $isLive = !empty($promo['is_active']) && !$isExpired;
                        ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2.5 py-1 rounded-lg border border-slate-300" dir="ltr">
                                        <?= htmlspecialchars($promo['code']) ?>
                                    </span>
                                    <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($promo['code']) ?>')" class="text-slate-400 hover:text-primary transition" title="کپی کد">
                                        <span class="material-symbols-outlined text-xs">content_copy</span>
                                    </button>
                                </div>
                            </td>
                            <td class="p-4 font-medium text-slate-800">
                                <?= htmlspecialchars($promo['title']) ?>
                                <?php if (!empty($promo['first_order_only'])): ?>
                                    <span class="mr-1 text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">فقط خرید اول</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($promo['discount_type'] === 'percentage'): ?>
                                    <span class="font-bold font-mono text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        <?= (int)$promo['discount_value'] ?>٪ تخفیف
                                    </span>
                                <?php else: ?>
                                    <span class="font-bold font-mono text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
                                        <?= number_format((int)$promo['discount_value']) ?> تومان
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono text-slate-600 leading-relaxed">
                                <div>سقف: <span class="font-bold"><?= !empty($promo['max_discount_amount']) ? number_format((int)$promo['max_discount_amount']) . ' ت' : 'نامحدود' ?></span></div>
                                <div class="text-[11px] text-slate-400">حداقل: <?= !empty($promo['min_order_amount']) ? number_format((int)$promo['min_order_amount']) . ' ت' : 'بدون شرط' ?></div>
                            </td>
                            <td class="p-4 text-center font-mono">
                                <span class="font-bold text-slate-800"><?= number_format((int)$promo['total_redemptions']) ?></span>
                                <span class="text-[10px] text-slate-400">/ <?= !empty($promo['usage_limit_total']) ? number_format((int)$promo['usage_limit_total']) : '∞' ?></span>
                            </td>
                            <td class="p-4 text-center text-slate-600 font-mono text-[11px]">
                                <?php if(!empty($promo['expires_at'])): ?>
                                    <span class="<?= $isExpired ? 'text-rose-600 font-bold' : '' ?>">
                                        <?= date('Y/m/d', strtotime($promo['expires_at'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">نامحدود</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($isLive): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>فعال</span>
                                    </span>
                                <?php elseif ($isExpired): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>منقضی‌شده</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>غیرفعال</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- Send Notification to Users Button -->
                                    <button type="button" 
                                        onclick="openBroadcastModal(<?= htmlspecialchars(json_encode($promo), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition border border-amber-200 hover:border-amber-300"
                                        title="ارسال اعلان هوشمند به کاربران">
                                        <span class="material-symbols-outlined text-sm">campaign</span>
                                    </button>

                                    <!-- Edit Button -->
                                    <button type="button" 
                                        onclick="openEditPromoModal(<?= htmlspecialchars(json_encode($promo), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition border border-blue-200 hover:border-blue-300"
                                        title="ویرایش کد تخفیف">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>

                                    <!-- End Code Immediately Button -->
                                    <?php if ($isLive): ?>
                                    <form method="POST" class="inline m-0" onsubmit="return confirm('آیا مطمئن هستید می‌خواهید فوراً به مهلت این کد تخفیف پایان دهید؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="end_promo">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition border border-rose-200 hover:border-rose-300" title="پایان دادن فوری به اعتبار">
                                            <span class="material-symbols-outlined text-sm">timer_off</span>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Toggle Active/Inactive -->
                                    <form method="POST" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= empty($promo['is_active']) ? 1 : 0 ?>">
                                        <button type="submit" class="p-1.5 <?= empty($promo['is_active']) ? 'text-emerald-600 hover:bg-emerald-50 border-emerald-200' : 'text-slate-500 hover:bg-slate-100 border-slate-200' ?> rounded-lg transition border" title="<?= empty($promo['is_active']) ? 'فعال‌سازی' : 'غیرفعال‌سازی' ?>">
                                            <span class="material-symbols-outlined text-sm"><?= empty($promo['is_active']) ? 'check_circle' : 'block' ?></span>
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" class="inline m-0" onsubmit="return confirm('آیا از حذف این کد تخفیف اطمینان دارید؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_promo">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="حذف">
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

    <!-- Redemptions Audit Log -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400">history</span>
                <span>لاگ و تاریخچه آخرین استفاده‌های کاربران</span>
            </h2>
            <span class="text-xs text-slate-500"><?= count($auditLogs) ?> تراکنش اخیر</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="p-4 font-bold">کد تخفیف</th>
                        <th class="p-4 font-bold">کاربر خریدار</th>
                        <th class="p-4 font-bold">شماره سفارش</th>
                        <th class="p-4 font-bold text-left">مبلغ تخفیف پوشش داده شده</th>
                        <th class="p-4 font-bold text-left">تاریخ و ساعت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400">هنوز هیچ تراکنشی با کد تخفیف ثبت نشده است.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4">
                                <span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-300">
                                    <?= htmlspecialchars($log['promo_code']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-slate-800">
                                <strong class="block"><?= htmlspecialchars($log['user_name'] ?: 'کاربر آسنا') ?></strong>
                                <span class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($log['user_phone']) ?></span>
                            </td>
                            <td class="p-4">
                                <a href="../order_receipt.php?order_id=<?= (int)$log['order_id'] ?>" target="_blank" class="font-mono font-bold text-primary hover:underline flex items-center gap-1">
                                    <span>#PC-<?= (int)$log['order_id'] ?></span>
                                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                                </a>
                            </td>
                            <td class="p-4 text-left font-mono font-bold text-emerald-700">
                                -<?= number_format((int)$log['discount_amount']) ?> تومان
                            </td>
                            <td class="p-4 text-left font-mono text-slate-500">
                                <?= date('Y/m/d - H:i', strtotime($log['created_at'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: Create / Edit Promo Code                                           -->
<!-- ========================================================================= -->
<div id="promoModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-4 backdrop-blur-xs">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#fd8100]" id="promoModalIcon">add_circle</span>
                <span id="promoModalTitle">ایجاد کد تخفیف جدید</span>
            </h3>
            <button type="button" onclick="closePromoModal()" class="text-slate-400 hover:text-slate-700 transition cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" id="promoForm" class="space-y-4 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_promo">
            <input type="hidden" name="id" id="field_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">کد تخفیف (لاتین):</label>
                    <input type="text" name="code" id="field_code" required placeholder="مثال: SPRING1403" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono uppercase tracking-wider outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">عنوان کمپین:</label>
                    <input type="text" name="title" id="field_title" required placeholder="مثال: جشنواره بهاره آسنا" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">نوع تخفیف:</label>
                    <select name="discount_type" id="field_discount_type" onchange="toggleMaxCapField()" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition">
                        <option value="percentage">درصدی (٪)</option>
                        <option value="fixed_amount">مبلغ ثابت (تومان)</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">مقدار تخفیف (درصد یا تومان):</label>
                    <input type="number" name="discount_value" id="field_discount_value" required min="1" placeholder="مثال: 15" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="cap_and_min_row">
                <div class="space-y-1" id="max_cap_col">
                    <label class="font-bold text-slate-700 block">سقف تخفیف (تومان - برای درصدی):</label>
                    <input type="number" name="max_discount_amount" id="field_max_discount_amount" placeholder="مثال: 100000" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">حداقل مبلغ خرید (تومان):</label>
                    <input type="number" name="min_order_amount" id="field_min_order_amount" value="0" placeholder="مثال: 200000" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">سقف کل دفعات استفاده:</label>
                    <input type="number" name="usage_limit_total" id="field_usage_limit_total" placeholder="خالی = نامحدود" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">حداکثر دفعات هر کاربر:</label>
                    <input type="number" name="usage_limit_per_user" id="field_usage_limit_per_user" value="1" min="1" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" dir="ltr">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">تاریخ شروع اعتبار (اختیاری):</label>
                    <input type="datetime-local" name="starts_at" id="field_starts_at" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-xs bg-slate-50 focus:bg-white transition">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-slate-700 block">تاریخ انقضا (اختیاری):</label>
                    <input type="datetime-local" name="expires_at" id="field_expires_at" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-xs bg-slate-50 focus:bg-white transition">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="first_order_only" id="field_first_order_only" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="font-bold text-slate-700">فقط برای سفارش اول کاربران جدید معتبر باشد</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" id="field_is_active" value="1" checked class="rounded text-primary focus:ring-primary">
                    <span class="font-bold text-slate-700">کد فعال باشد</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" onclick="closePromoModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 font-bold transition cursor-pointer">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white font-bold transition shadow-sm cursor-pointer" id="promoSubmitBtn">
                    ذخیره و انتشار کد
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Broadcast Promo Notification to Users                             -->
<!-- ========================================================================= -->
<div id="broadcastModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-4 backdrop-blur-xs">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">campaign</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">ارسال اعلان کد تخفیف به کاربران</h3>
                    <p class="text-[11px] text-slate-500">انتشار همزمان در وب‌اپلیکیشن (PWA)، پنل و پیامک</p>
                </div>
            </div>
            <button type="button" onclick="closeBroadcastModal()" class="text-slate-400 hover:text-slate-700 transition cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" class="space-y-4 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="broadcast_promo">
            <input type="hidden" name="promo_id" id="bc_promo_id" value="">

            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-slate-500 block text-[11px]">کد تخفیف انتخابی:</span>
                    <strong class="font-mono font-bold text-primary text-sm" id="bc_promo_code">WELCOME10</strong>
                </div>
                <div class="text-left">
                    <span class="text-slate-500 block text-[11px]">میزان تخفیف:</span>
                    <strong class="font-mono font-bold text-emerald-700" id="bc_promo_val">10%</strong>
                </div>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-slate-700 block">عنوان اعلان (Title):</label>
                <input type="text" name="title" id="bc_title" required class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition" placeholder="مثال: تخفیف ویژه آسنا برای شما">
            </div>

            <div class="space-y-1">
                <label class="font-bold text-slate-700 block">متن پیام اعلان (Message):</label>
                <textarea name="message" id="bc_message" rows="3" required class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition leading-relaxed" placeholder="متن پیام..."></textarea>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-slate-700 block">جامعه هدف اعلان:</label>
                <select name="target_audience" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-primary text-xs bg-slate-50 focus:bg-white transition">
                    <option value="all">همه کاربران پلتفرم و وب‌اپلیکیشن (All Users)</option>
                    <option value="buyers">فقط خریداران دارای سفارش قبلی (Previous Buyers)</option>
                    <option value="pwa_only">کاربران دارای اپلیکیشن نصب‌شده (PWA Only)</option>
                </select>
            </div>

            <div class="p-3 bg-amber-50/70 rounded-2xl border border-amber-200/80">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="send_sms" value="1" class="rounded text-amber-600 focus:ring-amber-500">
                    <div class="text-xs">
                        <span class="font-bold text-amber-900 block">ارسال پیامک انبوه اطلاع‌رسانی (SMS Broadcast)</span>
                        <span class="text-[11px] text-amber-700 block mt-0.5">پیامک کد تخفیف به شماره تماس کاربران ارسال خواهد شد (سامانه ملی‌پیامک).</span>
                    </div>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" onclick="closeBroadcastModal()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 font-bold transition cursor-pointer">
                    انصراف
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">send</span>
                    <span>ارسال آنی اعلان</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePromoModal() {
    document.getElementById('promoModalTitle').textContent = 'ایجاد کد تخفیف جدید';
    document.getElementById('promoModalIcon').textContent = 'add_circle';
    document.getElementById('promoSubmitBtn').textContent = 'ذخیره و انتشار کد';
    
    // Reset fields
    document.getElementById('field_id').value = '';
    document.getElementById('field_code').value = '';
    document.getElementById('field_title').value = '';
    document.getElementById('field_discount_type').value = 'percentage';
    document.getElementById('field_discount_value').value = '';
    document.getElementById('field_max_discount_amount').value = '';
    document.getElementById('field_min_order_amount').value = '0';
    document.getElementById('field_usage_limit_total').value = '';
    document.getElementById('field_usage_limit_per_user').value = '1';
    document.getElementById('field_starts_at').value = '';
    document.getElementById('field_expires_at').value = '';
    document.getElementById('field_first_order_only').checked = false;
    document.getElementById('field_is_active').checked = true;
    
    toggleMaxCapField();
    const modal = document.getElementById('promoModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openEditPromoModal(promo) {
    document.getElementById('promoModalTitle').textContent = 'ویرایش کد تخفیف: ' + promo.code;
    document.getElementById('promoModalIcon').textContent = 'edit';
    document.getElementById('promoSubmitBtn').textContent = 'ذخیره تغییرات';

    // Populate fields
    document.getElementById('field_id').value = promo.id;
    document.getElementById('field_code').value = promo.code;
    document.getElementById('field_title').value = promo.title;
    document.getElementById('field_discount_type').value = promo.discount_type || 'percentage';
    document.getElementById('field_discount_value').value = promo.discount_value;
    document.getElementById('field_max_discount_amount').value = promo.max_discount_amount || '';
    document.getElementById('field_min_order_amount').value = promo.min_order_amount || 0;
    document.getElementById('field_usage_limit_total').value = promo.usage_limit_total || '';
    document.getElementById('field_usage_limit_per_user').value = promo.usage_limit_per_user || 1;
    document.getElementById('field_starts_at').value = promo.starts_at ? promo.starts_at.replace(' ', 'T').substring(0, 16) : '';
    document.getElementById('field_expires_at').value = promo.expires_at ? promo.expires_at.replace(' ', 'T').substring(0, 16) : '';
    document.getElementById('field_first_order_only').checked = (parseInt(promo.first_order_only) === 1);
    document.getElementById('field_is_active').checked = (parseInt(promo.is_active) === 1);

    toggleMaxCapField();
    const modal = document.getElementById('promoModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePromoModal() {
    const modal = document.getElementById('promoModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function toggleMaxCapField() {
    const select = document.getElementById('field_discount_type');
    const capCol = document.getElementById('max_cap_col');
    if (select.value === 'fixed_amount') {
        capCol.style.opacity = '0.3';
        capCol.querySelector('input').disabled = true;
    } else {
        capCol.style.opacity = '1';
        capCol.querySelector('input').disabled = false;
    }
}

function openBroadcastModal(promo) {
    document.getElementById('bc_promo_id').value = promo.id;
    document.getElementById('bc_promo_code').textContent = promo.code;
    
    let discountValText = (promo.discount_type === 'percentage') 
        ? promo.discount_value + '٪' 
        : parseInt(promo.discount_value).toLocaleString('fa-IR') + ' تومان';
    document.getElementById('bc_promo_val').textContent = discountValText;

    // Pre-populate engaging notification text
    document.getElementById('bc_title').value = '🎁 تخفیف ویژه آسنا: کد ' + promo.code;
    document.getElementById('bc_message').value = 'با استفاده از کد تخفیف ' + promo.code + ' از ' + discountValText + ' تخفیف ویژه در خرید محصولات پت‌شاپ و داروخانه آنلاین آسنا بهره‌مند شوید!';

    const modal = document.getElementById('broadcastModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeBroadcastModal() {
    const modal = document.getElementById('broadcastModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
