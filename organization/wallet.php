<?php
require_once __DIR__ . '/includes/organization_header.php';

$escrowService = App::escrow();
$sellerId = (int)$currentUser['id'];
$orgId = (int)$currentOrg['id'];

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

// Fetch current wallet details for products
$wallet = $escrowService->getSellerWallet($sellerId);
$recentProductTx = $wallet['recent_transactions'] ?? [];

// Fetch appointment transactions for this organization
$aptStmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, d.specialty, d.provider_type, u.name as customer_name, u.phone as customer_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.organization_id = ? OR a.doctor_id IN (
        SELECT doctor_id FROM organization_doctors WHERE organization_id = ?
    )
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 50
");
$aptStmt->execute([$orgId, $orgId]);
$orgAppointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Appointment Financials (5% Platform Interest / Commission)
$totalApptGross = 0;
$totalApptCommission = 0;
$totalApptNet = 0;
$availableApptNet = 0;
$pendingApptNet = 0;

foreach ($orgAppointments as $apt) {
    $fee = (int)($apt['fee'] ?: 0);
    $comm = (int)($apt['commission_amount'] ?: round($fee * 0.05));
    $net = (int)($apt['net_amount'] ?: ($fee - $comm));

    $totalApptGross += $fee;
    $totalApptCommission += $comm;
    $totalApptNet += $net;

    if ($apt['settlement_status'] === 'available_for_payout' || $apt['status'] === 'completed') {
        $availableApptNet += $net;
    } else {
        $pendingApptNet += $net;
    }
}

// Total Combined Figures
$combinedAvailable = (int)$wallet['balance_available_for_payout'] + $availableApptNet;
$combinedPending = (int)$wallet['balance_pending_escrow'] + $pendingApptNet;
$combinedGross = (int)$wallet['balance_settled_lifetime'] + (int)$wallet['balance_available_for_payout'] + $totalApptGross;
$combinedPlatformInterest = (int)$totalApptCommission + round((int)$wallet['balance_settled_lifetime'] * 0.05);

$activeTab = $_GET['tab'] ?? 'appointments';
?>

<div class="space-y-6 max-w-6xl mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center font-black">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900">مدیریت مالی، کارمزد پلتفرم و تسویه پایا</h1>
                <p class="text-xs text-slate-500 mt-1">گردش مالی نوبت‌های ویزیت، خدمات گرومینگ و فروش کالا، با محاسبه دقیق کارمزد ۵٪ پلتفرم و واریز هفتگی</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center gap-1.5 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>تسویه خودکار: هر پنج‌شنبه (پایا بانک مرکزی)</span>
            </span>
        </div>
    </div>

    <!-- Flash Notification -->
    <?php if ($flashMessage): ?>
        <div class="p-4 rounded-2xl text-xs font-bold flex items-center gap-3 <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <span class="material-symbols-outlined"><?= $flashType === 'success' ? 'check_circle' : 'error' ?></span>
            <span><?= htmlspecialchars($flashMessage) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 Main Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ready for Payout -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">موجودی آماده تسویه (پایا)</span>
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                </div>
                <div class="text-2xl font-black text-emerald-600 mt-2 font-mono">
                    <?= number_format($combinedAvailable) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 leading-relaxed border-t border-slate-50 pt-2">
                نوبت‌های انجام‌شده و سفارشات گذشته از تست ۷ روزه
            </p>
        </div>

        <!-- Pending Service / Escrow Guarantee -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">در انتظار انجام ویزیت یا مهلت تست</span>
                    <span class="material-symbols-outlined text-amber-500">lock_clock</span>
                </div>
                <div class="text-2xl font-black text-slate-800 mt-2 font-mono">
                    <?= number_format($combinedPending) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 leading-relaxed border-t border-slate-50 pt-2">
                پس از انجام ویزیت یا پایان مهلت تست، به موجودی آماده افزوده می‌شود.
            </p>
        </div>

        <!-- 5% Platform Commission / Interest -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">کارمزد ۵٪ پلتفرم آسنا</span>
                    <span class="material-symbols-outlined text-indigo-500">percent</span>
                </div>
                <div class="text-2xl font-black text-indigo-700 mt-2 font-mono">
                    <?= number_format($totalApptCommission) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 leading-relaxed border-t border-slate-50 pt-2">
                پوشش زیرساخت، ارسال پیامک نوبت، و ضمانت بازپرداخت
            </p>
        </div>

        <!-- Total Lifetime Gross -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">کل کارکرد ناخالص مرکز</span>
                    <span class="material-symbols-outlined text-sky-500">paid</span>
                </div>
                <div class="text-2xl font-black text-slate-800 mt-2 font-mono">
                    <?= number_format($totalApptGross + $wallet['balance_settled_lifetime']) ?> <span class="text-xs font-normal text-slate-400">تومان</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 leading-relaxed border-t border-slate-50 pt-2">
                مجموع فروش داروخانه و نوبت‌های ثبت شده کلینیک
            </p>
        </div>
    </div>

    <!-- Bank Configuration Form -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sky-600">credit_card</span>
                <h2 class="text-sm font-black text-slate-800">اطلاعات حساب بانکی مرکز جهت واریز حواله پایا (بانک مرکزی)</h2>
            </div>
            <span class="text-[11px] text-slate-400">واریز بدون کارمزد بانکی</span>
        </div>

        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="hidden" name="csrf_token" value="<?= SecurityMiddleware::generateCsrfToken() ?>">
            <input type="hidden" name="action" value="update_bank">

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نام بانک</label>
                <input type="text" name="bank_name" value="<?= htmlspecialchars($wallet['bank_name'] ?? '') ?>" placeholder="مثلاً سامان، ملت، پاسارگاد" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">نام صاحب حساب</label>
                <input type="text" name="bank_account_holder" value="<?= htmlspecialchars($wallet['bank_account_holder'] ?? $currentUser['name']) ?>" placeholder="نام کامل صاحب حساب یا مرکز" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-sky-500 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">شماره شبا (IBAN) *</label>
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
                    <span>ذخیره اطلاعات بانکی</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Financial Ledger & Breakdown Tabs -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden space-y-4">
        <!-- Tabs Header -->
        <div class="px-6 pt-5 border-b border-slate-100 flex items-center gap-4">
            <a href="wallet.php?tab=appointments" class="pb-3 text-xs font-black transition-all flex items-center gap-1.5 <?= $activeTab === 'appointments' ? 'text-sky-600 border-b-2 border-sky-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <span class="material-symbols-outlined text-base">calendar_month</span>
                <span>درآمد نوبت‌های کلینیک و کارمزد ۵٪ پلتفرم</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-50 text-sky-700"><?= count($orgAppointments) ?></span>
            </a>

            <a href="wallet.php?tab=products" class="pb-3 text-xs font-black transition-all flex items-center gap-1.5 <?= $activeTab === 'products' ? 'text-sky-600 border-b-2 border-sky-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <span class="material-symbols-outlined text-base">storefront</span>
                <span>درآمد فروش کالا و امانت پستکس</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600"><?= count($recentProductTx) ?></span>
            </a>
        </div>

        <?php if ($activeTab === 'appointments'): ?>
            <!-- Appointments Financial Ledger -->
            <div class="p-6 pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">کد نوبت</th>
                                <th class="px-4 py-3">بیمار و مراجعه‌کننده</th>
                                <th class="px-4 py-3">پزشک / گرومر</th>
                                <th class="px-4 py-3">نوع خدمت</th>
                                <th class="px-4 py-3">تاریخ و ساعت</th>
                                <th class="px-4 py-3">تعرفه ناخالص</th>
                                <th class="px-4 py-3">کارمزد پلتفرم (۵٪)</th>
                                <th class="px-4 py-3">سهم خالص مرکز</th>
                                <th class="px-4 py-3">وضعیت تسویه</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php if (empty($orgAppointments)): ?>
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-400 font-bold">
                                        هنوز نوبتی برای این مرکز ثبت نشده است.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orgAppointments as $apt): 
                                    $fee = (int)($apt['fee'] ?: 0);
                                    $comm = (int)($apt['commission_amount'] ?: round($fee * 0.05));
                                    $net = (int)($apt['net_amount'] ?: ($fee - $comm));
                                    $isGr = ($apt['provider_type'] ?? '') === 'groomer';
                                ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 font-mono font-black text-slate-900">#APT-<?= $apt['id'] ?></td>
                                        <td class="px-4 py-3">
                                            <span class="font-bold text-slate-900 block"><?= htmlspecialchars($apt['pet_name'] ?? 'پت') ?></span>
                                            <span class="text-[11px] text-slate-400"><?= htmlspecialchars($apt['pet_type'] ?? '') ?> (<?= htmlspecialchars($apt['customer_name'] ?? 'مشتری') ?>)</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-bold text-slate-900 block"><?= htmlspecialchars($apt['doctor_name']) ?></span>
                                            <span class="text-[10px] <?= $isGr ? 'text-pink-600' : 'text-indigo-600' ?>">
                                                <?= $isGr ? '✂️ گرومر پت' : '🩺 پزشک' ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($apt['visit_purpose'] ?? 'معاینه عمومی') ?></td>
                                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                                            <?= htmlspecialchars($apt['appointment_date']) ?> <?= substr($apt['appointment_time'] ?? '', 0, 5) ?>
                                        </td>
                                        <td class="px-4 py-3 font-mono font-bold text-slate-900"><?= number_format($fee) ?> تومان</td>
                                        <td class="px-4 py-3 font-mono text-rose-500 font-bold">-<?= number_format($comm) ?> تومان</td>
                                        <td class="px-4 py-3 font-mono font-black text-emerald-600"><?= number_format($net) ?> تومان</td>
                                        <td class="px-4 py-3">
                                            <?php if ($apt['settlement_status'] === 'available_for_payout' || $apt['status'] === 'completed'): ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">آماده تسویه پایا</span>
                                            <?php elseif ($apt['status'] === 'cancelled'): ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">لغو شده</span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">امانت (در انتظار انجام)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Products Escrow Ledger -->
            <div class="p-6 pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">شماره سفارش</th>
                                <th class="px-4 py-3">تاریخ سفارش</th>
                                <th class="px-4 py-3">کد رهگیری پست</th>
                                <th class="px-4 py-3">مبلغ فروش</th>
                                <th class="px-4 py-3">کارمزد پلتفرم (۵٪)</th>
                                <th class="px-4 py-3">سهم خالص مرکز</th>
                                <th class="px-4 py-3">وضعیت وجه</th>
                                <th class="px-4 py-3">موعد آزادسازی</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php if (empty($recentProductTx)): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-400 font-bold">
                                        هنوز سفارشی برای کالاهای این مرکز ثبت نشده است.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentProductTx as $tx): 
                                    $isDelivered = !empty($tx['delivered_at']);
                                    $eligibleTime = !empty($tx['payout_eligible_at']) ? strtotime($tx['payout_eligible_at']) : null;
                                    $now = time();
                                    $daysLeft = $eligibleTime ? max(0, ceil(($eligibleTime - $now) / 86400)) : 7;
                                ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 font-bold text-slate-900 font-mono">#<?= $tx['order_id'] ?></td>
                                        <td class="px-4 py-3 font-mono text-[11px]"><?= $tx['order_date'] ?? $tx['created_at'] ?></td>
                                        <td class="px-4 py-3 font-mono text-[11px]">
                                            <?= htmlspecialchars($tx['post_tracking_code'] ?: 'در انتظار ارسال') ?>
                                        </td>
                                        <td class="px-4 py-3 font-mono"><?= number_format($tx['gross_amount']) ?> تومان</td>
                                        <td class="px-4 py-3 font-mono text-rose-500 font-bold">-<?= number_format($tx['commission_amount']) ?> تومان</td>
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/organization_footer.php'; ?>
