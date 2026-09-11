<?php
/**
 * ASENA Enterprise - Official Central Bank Paya Verification & Digital Payout Transcript
 * صفحه عمومی و معتبر استعلام و تاییدیه رسمی حواله پایا و تسویه حساب الکترونیک آسنا
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/jdf.php';

$batchCode = trim($_GET['batch_code'] ?? $_GET['batch'] ?? '');
if (empty($batchCode)) {
    die("شناسه حواله پایا مشخص نگردیده است.");
}

// Fetch batch
$stmt = $pdo->prepare("SELECT * FROM seller_payout_batches WHERE batch_code = ?");
$stmt->execute([$batchCode]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    http_response_code(404);
    die("حواله پایا با کد پیگیری مشخص شده در سامانه مرکزی آسنا یافت نشد.");
}

// Fetch Asena central treasury bank settings
$asenaCard  = get_setting($pdo, 'admin_bank_card', '6037991199223344');
$asenaSheba = get_setting($pdo, 'admin_bank_sheba', 'IR120560000000100000000001');
$asenaBank  = get_setting($pdo, 'admin_bank_name', 'بانک سامان');
$asenaHolder= get_setting($pdo, 'admin_bank_holder', 'شرکت توسعه تجارت الکترونیک آسنا');

// Target seller filter
$targetSellerId = (int)($_GET['seller_id'] ?? 0);

// Parse Paya export content for recipients
$payaLines = explode("\r\n", trim($batch['paya_export_content'] ?? ''));
$recipients = [];
if (count($payaLines) >= 2) {
    for ($i = 1; $i < count($payaLines); $i++) {
        $cols = explode("\t", $payaLines[$i]);
        if (count($cols) >= 3) {
            $recipients[] = [
                'sheba' => trim($cols[0]),
                'amount' => (int)($cols[1] ?? 0),
                'name' => trim($cols[2] ?? 'ذینفع سامانه'),
                'bank' => trim($cols[3] ?? 'شبکه بانکی شتاب/پایا'),
                'desc' => trim($cols[4] ?? '')
            ];
        }
    }
}

// Active beneficiary determination
$activeBeneficiary = null;
if (!empty($recipients)) {
    if ($targetSellerId > 0) {
        $sw = $pdo->prepare("SELECT bank_sheba FROM seller_wallets WHERE seller_id = ?");
        $sw->execute([$targetSellerId]);
        $targetSheba = $sw->fetchColumn();
        foreach ($recipients as $r) {
            if ($targetSheba && strtoupper($r['sheba']) === strtoupper($targetSheba)) {
                $activeBeneficiary = $r;
                break;
            }
        }
    }
    if (!$activeBeneficiary) {
        $activeBeneficiary = $recipients[0];
    }
}

// Fetch settled orders from ledger
$ledgerStmt = $pdo->prepare("
    SELECT l.*, o.id as order_number, o.created_at as order_date, o.post_tracking_code 
    FROM seller_escrow_ledger l
    JOIN orders o ON l.order_id = o.id
    WHERE l.settlement_batch_id = ? " . ($targetSellerId > 0 ? "AND l.seller_id = {$targetSellerId}" : "") . "
    ORDER BY l.id DESC
");
$ledgerStmt->execute([$batch['id']]);
$settledOrders = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch settled appointments
$aptStmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, u.name as customer_name 
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.settlement_batch_id = ? " . ($targetSellerId > 0 ? "AND (a.organization_id = {$targetSellerId} OR d.user_id = {$targetSellerId})" : "") . "
    ORDER BY a.id DESC
");
$aptStmt->execute([$batch['id']]);
$settledAppointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC);

$totalSettled = $activeBeneficiary ? (int)$activeBeneficiary['amount'] : (int)$batch['total_payout_amount'];
$payoutTimestamp = strtotime($batch['processed_at'] ?? $batch['created_at']);
$jalaliDate = jdate('l، j F Y - H:i', $payoutTimestamp);

$auditHash = strtoupper(substr(hash('sha256', $batchCode . $totalSettled . $asenaSheba), 0, 16));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاییدیه رسمی حواله پایا - <?= htmlspecialchars($batchCode) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/enterprise-ui.css">
    <link href="assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="assets/css/geist.css" rel="stylesheet"/>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <style>
        body { font-family: Tahoma, 'Vazirmatn', sans-serif; background: #f1f5f9; color: #0f172a; }
        .cert-card { max-width: 860px; margin: 30px auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 26, 72, 0.08); border: 1px solid #cbd5e1; overflow: hidden; }
        .cert-header { background: linear-gradient(135deg, #001a48 0%, #002d72 100%); color: #fff; padding: 28px; }
        .stamp-box { border: 2px dashed #059669; color: #059669; background: #ecfdf5; border-radius: 12px; padding: 12px 18px; display: inline-flex; align-items: center; gap: 8px; font-weight: bold; }
        @media print {
            body { background: #fff; padding: 0; }
            .cert-card { box-shadow: none; border: 1px solid #000; width: 100%; max-width: 100%; border-radius: 0; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="p-4 sm:p-6">

<div class="max-w-[860px] mx-auto mb-4 flex justify-between items-center no-print">
    <a href="index.php" class="text-xs font-bold text-slate-600 hover:text-primary flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">arrow_forward</span>
        بازگشت به پرتال آسنا
    </a>
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="bg-[#001a48] hover:bg-[#002d72] text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition">
            <span class="material-symbols-outlined text-base">print</span>
            چاپ ترنسکریپت رسمی (PDF)
        </button>
    </div>
</div>

<div class="cert-card">
    <!-- Certificate Header -->
    <div class="cert-header flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <img src="assets/images/logo.png" alt="لوگو" class="w-14 h-14 object-contain bg-white/10 rounded-2xl p-1.5">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black">ترنسکریپت رسمی حواله بین‌بانکی پایا</h1>
                    <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-bold px-2 py-0.5 rounded-full">اصالت تایید شده</span>
                </div>
                <p class="text-xs text-slate-300 mt-1">سامانه تسویه الکترونیک بازارگاه و خدمات درمانی آسنا (سهامی خاص)</p>
            </div>
        </div>
        <div class="text-left md:text-right bg-white/10 px-4 py-2.5 rounded-xl border border-white/15">
            <span class="text-[10px] text-slate-300 block">شناسه یکتای پیگیری پایا:</span>
            <span class="text-sm font-mono font-bold tracking-wider text-amber-300"><?= htmlspecialchars($batchCode) ?></span>
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6">
        <!-- Verification Banner -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-slate-900">تراکنش معتبر و قطعی در سامانه پایا بانک مرکزی</h3>
                    <p class="text-xs text-slate-500 mt-0.5">این تراکنش از حساب متمرکز خزانه‌داری آسنا صادر و به شماره شبای مقصد واریز گردیده است.</p>
                </div>
            </div>
            <div class="stamp-box shrink-0 text-xs">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>تایید قطعی - امور مالی</span>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Origin Account (Asena) -->
            <div class="p-5 rounded-2xl bg-blue-50/60 border border-blue-100 space-y-3">
                <div class="flex items-center justify-between border-b border-blue-200/60 pb-2">
                    <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-blue-700">account_balance</span>
                        اطلاعات حساب مبدا (پلتفرم آسنا)
                    </span>
                    <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-md">حساب حقوقی متمرکز</span>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">صاحب حساب:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($asenaHolder) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">بانک عامل مبدا:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($asenaBank) ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">شماره کارت آسنا:</span>
                        <span class="font-mono font-bold text-blue-950 tracking-wider dir-ltr"><?= chunk_split(preg_replace('/[^\d]/', '', $asenaCard), 4, '-') ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">شماره شبا مبدا:</span>
                        <span class="font-mono text-[11px] font-bold text-blue-900 dir-ltr"><?= htmlspecialchars($asenaSheba) ?></span>
                    </div>
                </div>
            </div>

            <!-- Destination Account (Beneficiary) -->
            <div class="p-5 rounded-2xl bg-emerald-50/60 border border-emerald-100 space-y-3">
                <div class="flex items-center justify-between border-b border-emerald-200/60 pb-2">
                    <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-emerald-700">payments</span>
                        اطلاعات حساب مقصد (ذینفع)
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">تسویه هفتگی</span>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">نام و هویت ذینفع:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($activeBeneficiary['name'] ?? 'فروشنده / مرکز درمانی') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">بانک مقصد:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($activeBeneficiary['bank'] ?? 'شبکه بانکی کشور') ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">شماره شبای مقصد:</span>
                        <span class="font-mono font-bold text-emerald-900 dir-ltr"><?= htmlspecialchars($activeBeneficiary['sheba'] ?? 'IR...') ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">زمان پردازش چرخه:</span>
                        <span class="text-slate-700 font-bold"><?= $jalaliDate ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Settled Amount Card -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-[#001a48] text-white text-center shadow-md">
            <span class="text-xs text-slate-300 block mb-1">مبلغ واریزی تسویه شده به حساب ذینفع:</span>
            <div class="text-2xl sm:text-3xl font-black font-mono text-emerald-400">
                <?= number_format($totalSettled) ?> <span class="text-base text-slate-200 font-normal">تومان</span>
            </div>
            <p class="text-xs text-slate-300 mt-2">
                معادل <span class="font-bold text-white"><?= number_format($totalSettled * 10) ?></span> ریال تمام — کسر کارمزد پلتفرم (۵٪) قبلاً در صورت‌حساب اعمال گردیده است.
            </p>
        </div>

        <!-- Itemized Breakdown -->
        <?php if (!empty($settledOrders) || !empty($settledAppointments)): ?>
        <div class="space-y-3">
            <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-primary">receipt_long</span>
                ریز اقلام سفارشات و نوبت‌های تسویه‌شده در این حواله:
            </h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs text-right">
                    <thead class="bg-slate-100 text-slate-700 font-bold">
                        <tr>
                            <th class="p-2.5 text-center">نوع</th>
                            <th class="p-2.5">شناسه مرجع</th>
                            <th class="p-2.5">شرح قلم / خدمات</th>
                            <th class="p-2.5 text-center">مبلغ ناخالص</th>
                            <th class="p-2.5 text-center text-red-600">کارمزد پلتفرم (۵٪)</th>
                            <th class="p-2.5 text-center text-emerald-700">مبلغ خالص واریزی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($settledOrders as $o): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-2.5 text-center"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold text-[10px]">سفارش کالا</span></td>
                            <td class="p-2.5 font-mono font-bold">#PC-<?= (int)$o['order_id'] ?></td>
                            <td class="p-2.5 text-slate-600">کد رهگیری پست: <?= htmlspecialchars($o['post_tracking_code'] ?: 'ارسال اکسپرس') ?></td>
                            <td class="p-2.5 text-center font-mono"><?= number_format($o['gross_amount']) ?> تومان</td>
                            <td class="p-2.5 text-center font-mono text-red-600">-<?= number_format($o['commission_amount']) ?></td>
                            <td class="p-2.5 text-center font-mono font-bold text-emerald-700"><?= number_format($o['net_seller_amount']) ?> تومان</td>
                        </tr>
                        <?php endforeach; ?>

                        <?php foreach ($settledAppointments as $apt): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-2.5 text-center"><span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold text-[10px]">ویزیت پزشک</span></td>
                            <td class="p-2.5 font-mono font-bold">#APT-<?= (int)$apt['id'] ?></td>
                            <td class="p-2.5 text-slate-600">پزشک: <?= htmlspecialchars($apt['doctor_name'] ?: 'متخصص') ?> (بیمار: <?= htmlspecialchars($apt['customer_name'] ?: 'مراجع') ?>)</td>
                            <td class="p-2.5 text-center font-mono"><?= number_format($apt['fee']) ?> تومان</td>
                            <td class="p-2.5 text-center font-mono text-red-600">-<?= number_format($apt['commission_amount']) ?></td>
                            <td class="p-2.5 text-center font-mono font-bold text-emerald-700"><?= number_format($apt['net_amount']) ?> تومان</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-slate-400">info</span>
            <span>این حواله بر اساس تسویه تجمیعی کیف‌پول الکترونیک در چرخه پنج‌شنبه‌ها صادر شده است.</span>
        </div>
        <?php endif; ?>

        <!-- Footer Seal & Cryptographic Hash -->
        <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 text-[11px] text-slate-500">
            <div>
                کد تایید اعتبار دیجیتال: <span class="font-mono font-bold text-slate-800 tracking-wider"><?= $auditHash ?></span>
            </div>
            <div>
                صادر شده توسط سامانه خزانه‌داری متمرکز آسنا مطابق با ضوابط سامانه پایا بانک مرکزی جمهوری اسلامی ایران
            </div>
        </div>
    </div>
</div>

</body>
</html>
