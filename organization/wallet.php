<?php
require_once __DIR__ . '/includes/organization_header.php';

$escrowService = App::escrow();
$sellerId = (int)$currentUser['id'];

$flashMessage = '';
$flashType = 'info';

// Handle bank information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bank') {
    SecurityMiddleware::validateCsrfToken($_POST['csrf_token'] ?? '');

    $bankData = [
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'bank_account_holder' => trim($_POST['bank_account_holder'] ?? ''),
        'bank_sheba' => trim($_POST['bank_sheba'] ?? ''),
        'bank_card_number' => trim($_POST['bank_card_number'] ?? '')
    ];

    $updateRes = $escrowService->updateBankDetails($sellerId, $bankData);
    if ($updateRes['success']) {
        $flashMessage = $updateRes['message'];
        $flashType = 'success';
    } else {
        $flashMessage = $updateRes['message'];
        $flashType = 'error';
    }
}

// Fetch current wallet details
$wallet = $escrowService->getSellerWallet($sellerId);
$recentTx = $wallet['recent_transactions'] ?? [];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-black">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800">کیف پول و تسویه حساب هفتگی</h1>
                <p class="text-xs text-slate-500 mt-1">مدیریت وجوه امانی، گردش مالی سفارشات، رهگیری تحویل پست و شماره شبای بانکی (پایا)</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center gap-1.5 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>چرخه تسویه: هفتگی (پایا)</span>
            </span>
        </div>
    </div>

    <!-- Flash Notification -->
    <?php if ($flashMessage): ?>
        <div class="p-4 rounded-xl text-xs font-bold flex items-center gap-3 <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <span class="material-symbols-outlined"><?= $flashType === 'success' ? 'check_circle' : 'error' ?></span>
            <span><?= htmlspecialchars($flashMessage) ?></span>
        </div>
    <?php endif; ?>

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Pending 7-Day Guarantee Escrow -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">امانت شرکت (مهلت ۷ روزه تست)</span>
                <span class="material-symbols-outlined text-amber-500">lock_clock</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mt-2">
                <?= number_format($wallet['balance_pending_escrow']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                مطابق قانون تجارت الکترونیک، پس از تأیید تحویل مرسوله توسط پست، وجه به مدت ۷ روز نگهداری و سپس به موجودی آماده تسویه افزوده می‌شود.
            </p>
        </div>

        <!-- Available for Weekly Payout -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">موجودی آماده تسویه هفتگی</span>
                <span class="material-symbols-outlined text-emerald-500">check_circle</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2">
                <?= number_format($wallet['balance_available_for_payout']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                این مبلغ در پایان هفته به صورت خودکار و بدون کارمزد بانکی از طریق حواله پایا به شماره شبای شما واریز خواهد شد.
            </p>
        </div>

        <!-- Lifetime Settled -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">مجموع تسویه‌های دریافتی (پایا)</span>
                <span class="material-symbols-outlined text-sky-500">paid</span>
            </div>
            <div class="text-2xl font-black text-slate-800 mt-2">
                <?= number_format($wallet['balance_settled_lifetime']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                کل درآمد واریز شده به حساب بانکی مرکز درمانی شما از ابتدای فعالیت در پلتفرم آسنا.
            </p>
        </div>
    </div>

    <!-- Bank Details Configuration Form -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
            <span class="material-symbols-outlined text-sky-600">credit_card</span>
            <h2 class="text-sm font-black text-slate-800">اطلاعات حساب بانکی جهت تسویه پایا (بانک مرکزی)</h2>
        </div>

        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
            <input type="hidden" name="action" value="update_bank">

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نام بانک</label>
                <input type="text" name="bank_name" value="<?= htmlspecialchars($wallet['bank_name'] ?? '') ?>" placeholder="مثلاً بانک ملت، سامان، پاسارگاد" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نام صاحب حساب</label>
                <input type="text" name="bank_account_holder" value="<?= htmlspecialchars($wallet['bank_account_holder'] ?? $currentUser['name']) ?>" placeholder="نام کامل صاحب حساب" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">شماره شبا (IBAN)</label>
                <input type="text" name="bank_sheba" value="<?= htmlspecialchars($wallet['bank_sheba'] ?? '') ?>" placeholder="IR000000000000000000000000" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-mono dir-ltr text-left">
                <span class="text-[10px] text-slate-400 mt-0.5 block">شامل ۲۴ رقم پس از IR</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">شماره کارت (اختیاری)</label>
                <input type="text" name="bank_card_number" value="<?= htmlspecialchars($wallet['bank_card_number'] ?? '') ?>" placeholder="۶۰۳۷..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-mono dir-ltr text-left">
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>ذخیره مشخصات بانکی</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Escrow Breakdown & Ledger -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-600">receipt_long</span>
                <h2 class="text-sm font-black text-slate-800">ریز تراکنش‌های امانی و گردش فروش محصولات</h2>
            </div>
            <span class="text-xs text-slate-400">۲۰ گردش اخیر</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold">
                    <tr>
                        <th class="px-4 py-3">شماره سفارش</th>
                        <th class="px-4 py-3">تاریخ سفارش</th>
                        <th class="px-4 py-3">کد رهگیری پست</th>
                        <th class="px-4 py-3">مبلغ فروش</th>
                        <th class="px-4 py-3">کارمزد پلتفرم</th>
                        <th class="px-4 py-3">سهم خالص شما</th>
                        <th class="px-4 py-3">وضعیت وجه</th>
                        <th class="px-4 py-3">موعد آزادسازی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($recentTx)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                هنوز سفارشی برای محصولات مرکز درمانی شما ثبت نشده است.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTx as $tx): 
                            $isDelivered = !empty($tx['delivered_at']);
                            $eligibleTime = !empty($tx['payout_eligible_at']) ? strtotime($tx['payout_eligible_at']) : null;
                            $now = time();
                            $daysLeft = $eligibleTime ? max(0, ceil(($eligibleTime - $now) / 86400)) : 7;
                        ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-bold text-slate-900">#<?= $tx['order_id'] ?></td>
                                <td class="px-4 py-3 font-mono text-[11px]"><?= $tx['order_date'] ?? $tx['created_at'] ?></td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    <?= htmlspecialchars($tx['post_tracking_code'] ?: 'در انتظار ارسال') ?>
                                </td>
                                <td class="px-4 py-3 font-mono"><?= number_format($tx['gross_amount']) ?> تومان</td>
                                <td class="px-4 py-3 font-mono text-rose-500"><?= number_format($tx['commission_amount']) ?> تومان</td>
                                <td class="px-4 py-3 font-mono font-black text-emerald-600"><?= number_format($tx['net_seller_amount']) ?> تومان</td>
                                <td class="px-4 py-3">
                                    <?php
                                    switch ($tx['status']) {
                                        case 'held_in_escrow':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">امانت (مهلت ۷ روزه)</span>';
                                            break;
                                        case 'released_to_available':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">آماده تسویه هفتگی</span>';
                                            break;
                                        case 'settled_in_batch':
                                            echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">واریز شده به حساب</span>';
                                            break;
                                        default:
                                            echo htmlspecialchars($tx['status']);
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($tx['status'] === 'held_in_escrow'): ?>
                                        <?php if ($isDelivered): ?>
                                            <span class="text-amber-700 font-bold text-[11px]"><?= $daysLeft ?> روز تا آزادسازی</span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[11px]">پس از تحویل بسته</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px]"><?= $tx['payout_eligible_at'] ?: '-' ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/organization_footer.php'; ?>
