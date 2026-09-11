<?php
/**
 * ASENA Enterprise - Official Paya Payout Remittance Receipt
 * Printable digital receipt for seller & clinic weekly settlements (رسید رسمی حواله پایا و تسویه هفتگی)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/jdf.php';
require_once __DIR__ . '/../includes/QrCode.php';

if (!isset($_SESSION['user_id'])) {
    die("لطفاً ابتدا وارد حساب کاربری خود شوید.");
}

$batchCode = trim($_GET['batch_code'] ?? '');
if (empty($batchCode)) {
    die("شناسه حواله پایا مشخص نگردیده است.");
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';
$isAdmin = ($userRole === 'admin');

// Fetch payout batch
$stmt = $pdo->prepare("SELECT * FROM seller_payout_batches WHERE batch_code = ?");
$stmt->execute([$batchCode]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die("حواله پایا با کد مشخص شده یافت نشد.");
}

// Fetch Asena Central Treasury bank settings
$asenaCard  = get_setting($pdo, 'admin_bank_card', '6037991199223344');
$asenaSheba = get_setting($pdo, 'admin_bank_sheba', 'IR120560000000100000000001');
$asenaBank  = get_setting($pdo, 'admin_bank_name', 'بانک سامان');
$asenaHolder= get_setting($pdo, 'admin_bank_holder', 'شرکت توسعه تجارت الکترونیک آسنا');

// Parse all recipients from Paya export content
$tsvLines = explode("\r\n", trim($batch['paya_export_content'] ?? ''));
$payaRecipients = [];
if (count($tsvLines) >= 2) {
    for ($idx = 1; $idx < count($tsvLines); $idx++) {
        $c = explode("\t", $tsvLines[$idx]);
        if (count($c) >= 3) {
            $payaRecipients[] = [
                'index' => $idx,
                'sheba' => trim($c[0]),
                'amount' => (int)($c[1] ?? 0),
                'name' => trim($c[2] ?? 'فروشنده همکار'),
                'bank' => trim($c[3] ?? 'شبکه بانکی شتاب/پایا'),
                'desc' => trim($c[4] ?? '')
            ];
        }
    }
}

// Target recipient selection
$requestedSheba = trim($_GET['sheba'] ?? '');
$activeRecipient = null;

if (!empty($payaRecipients)) {
    if (!$isAdmin) {
        // Non-admin can only see their own Sheba
        $swStmt = $pdo->prepare("SELECT bank_sheba FROM seller_wallets WHERE seller_id = ?");
        $swStmt->execute([$userId]);
        $userSheba = strtoupper(trim($swStmt->fetchColumn() ?: ''));
        foreach ($payaRecipients as $pr) {
            if ($userSheba && strtoupper($pr['sheba']) === $userSheba) {
                $activeRecipient = $pr;
                break;
            }
        }
        if (!$activeRecipient) {
            // Check by user name or first
            $activeRecipient = $payaRecipients[0];
        }
    } else {
        if ($requestedSheba) {
            foreach ($payaRecipients as $pr) {
                if (strtoupper($pr['sheba']) === strtoupper($requestedSheba)) {
                    $activeRecipient = $pr;
                    break;
                }
            }
        }
        if (!$activeRecipient) {
            $activeRecipient = $payaRecipients[0];
        }
    }
}

// Fetch beneficiary seller record from DB if possible
$beneficiarySheba = $activeRecipient['sheba'] ?? '';
$seller = null;
if ($beneficiarySheba) {
    $sw = $pdo->prepare("
        SELECT u.id as user_id, u.name, u.phone, u.national_id, w.* 
        FROM seller_wallets w
        JOIN users u ON w.seller_id = u.id
        WHERE w.bank_sheba = ?
        LIMIT 1
    ");
    $sw->execute([$beneficiarySheba]);
    $seller = $sw->fetch(PDO::FETCH_ASSOC);
}

if (!$seller) {
    $seller = [
        'user_id' => 0,
        'name' => $activeRecipient['name'] ?? 'فروشنده همکار آسنا',
        'bank_account_holder' => $activeRecipient['name'] ?? 'فروشنده همکار آسنا',
        'bank_sheba' => $beneficiarySheba ?: 'IR000000000000000000000000',
        'bank_name' => $activeRecipient['bank'] ?? 'سامانه پایا بانک مرکزی',
        'phone' => '-',
        'bank_card_number' => '-'
    ];
}

// Fetch settled items in this batch for this beneficiary
$sellerIdFilter = (int)($seller['user_id'] ?? 0);

$ledgerItems = [];
if ($sellerIdFilter > 0) {
    $lStmt = $pdo->prepare("
        SELECT l.*, o.id as order_number, o.created_at as order_date, o.post_tracking_code 
        FROM seller_escrow_ledger l
        JOIN orders o ON l.order_id = o.id
        WHERE l.settlement_batch_id = ? AND l.seller_id = ?
        ORDER BY l.id DESC
    ");
    $lStmt->execute([$batch['id'], $sellerIdFilter]);
    $ledgerItems = $lStmt->fetchAll(PDO::FETCH_ASSOC);
}
if (empty($ledgerItems) && $isAdmin && count($payaRecipients) <= 1) {
    // Single seller batch fallback
    $lStmt = $pdo->prepare("
        SELECT l.*, o.id as order_number, o.created_at as order_date, o.post_tracking_code 
        FROM seller_escrow_ledger l
        JOIN orders o ON l.order_id = o.id
        WHERE l.settlement_batch_id = ?
        ORDER BY l.id DESC
    ");
    $lStmt->execute([$batch['id']]);
    $ledgerItems = $lStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch settled appointments
$settledAppointments = [];
if ($sellerIdFilter > 0) {
    $aptStmt = $pdo->prepare("
        SELECT a.*, d.name as doctor_name, u.name as customer_name 
        FROM appointments a
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.settlement_batch_id = ? AND (a.organization_id = ? OR d.user_id = ?)
        ORDER BY a.id DESC
    ");
    $aptStmt->execute([$batch['id'], $sellerIdFilter, $sellerIdFilter]);
    $settledAppointments = $aptStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Amount calculation
$totalSettledForRecipient = $activeRecipient ? (int)$activeRecipient['amount'] : (int)$batch['total_payout_amount'];

$payoutTimestamp = strtotime($batch['processed_at'] ?? $batch['created_at']);
$jalaliDate = jdate('Y/m/d - H:i', $payoutTimestamp);

// Base URL for verification
$baseUrl = get_app_base_url();
$verifyUrl = $baseUrl . '/verify_payout.php?batch=' . urlencode($batchCode) . ($sellerIdFilter > 0 ? '&seller_id=' . $sellerIdFilter : '');

// Generate Standalone Vector QR Code locally
$qrSvg = QrCode::svg($verifyUrl, 110, '#001a48', 'transparent', 1);

// Master View Mode (Admin toggle)
$viewMode = $_GET['view'] ?? 'beneficiary'; // 'beneficiary' or 'master'
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>رسید رسمی تسویه حساب پایا - <?= htmlspecialchars($batchCode) ?></title>
    <link rel="stylesheet" href="../assets/css/material-symbols.css">
    <style>
        * { box-sizing: border-box; font-family: Tahoma, 'Vazirmatn', sans-serif; }
        body { background: #f0f2f5; margin: 0; padding: 20px; color: #111; font-size: 12px; }
        .receipt-box { max-width: 860px; margin: auto; background: #fff; padding: 30px; border: 2px solid #001a48; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); position: relative; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #001a48; padding-bottom: 15px; }
        .header-table td { vertical-align: middle; }
        .title { font-size: 20px; font-weight: bold; color: #001a48; text-align: center; }
        .sub-title { font-size: 11px; text-align: center; color: #555; margin-top: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #cbd5e1; }
        .meta-table th { background: #eef2f8; border: 1px solid #cbd5e1; padding: 8px 12px; font-weight: bold; text-align: right; width: 25%; font-size: 11px; }
        .meta-table td { border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 12px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #001a48; color: #fff; padding: 8px; font-size: 11px; text-align: center; }
        .items-table td { border: 1px solid #e2e8f0; padding: 8px; font-size: 11px; text-align: center; }
        .total-box { background: #f8fafc; border: 2px dashed #001a48; padding: 16px; border-radius: 8px; margin-top: 15px; text-align: center; }
        .total-amount { font-size: 24px; font-weight: bold; color: #059669; font-family: monospace; }
        .footer-signs { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .footer-signs td { width: 50%; text-align: center; padding: 20px; vertical-align: top; border: 1px dashed #cbd5e1; height: 100px; font-size: 11px; }
        .no-print-bar { max-width: 860px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .btn-action { background: #001a48; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-action:hover { background: #002d72; }
        .badge { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; }
        .stamp-treasury { border: 2px solid #001a48; border-radius: 8px; padding: 6px 12px; display: inline-block; color: #001a48; font-weight: bold; font-size: 11px; margin-top: 15px; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-box { border: 1px solid #000; box-shadow: none; border-radius: 0; width: 100%; max-width: 100%; padding: 15px; }
            .no-print-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div style="display: flex; align-items: center; gap: 10px;">
        <strong>سامانه تسویه متمرکز پایا و بازارگاه آسنا</strong>
        <?php if ($isAdmin && count($payaRecipients) > 1): ?>
            <div style="display: flex; gap: 4px; background: #e2e8f0; padding: 3px; border-radius: 8px;">
                <a href="?batch_code=<?= urlencode($batchCode) ?>&view=beneficiary" class="btn-action" style="<?= $viewMode !== 'master' ? 'background: #001a48;' : 'background: transparent; color: #333;' ?>">رسید ذینفع</a>
                <a href="?batch_code=<?= urlencode($batchCode) ?>&view=master" class="btn-action" style="<?= $viewMode === 'master' ? 'background: #001a48;' : 'background: transparent; color: #333;' ?>">فهرست تجمیعی پایا (کل بسته)</a>
            </div>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?= htmlspecialchars($verifyUrl) ?>" target="_blank" class="btn-action" style="background: #0284c7;">
            🔗 صفحه استعلام بانکی
        </a>
        <button class="btn-action" onclick="window.print();">🖨️ چاپ رسید رسمی (Print / PDF)</button>
    </div>
</div>

<div class="receipt-box">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 18%; text-align: right;">
                <img src="../assets/images/logo.png" alt="لوگو" style="width: 55px; height: 55px; object-fit: contain;">
            </td>
            <td style="width: 64%; text-align: center;">
                <div class="title">
                    <?= ($viewMode === 'master') ? 'فهرست تجمیعی حواله بین‌بانکی پایا (سیکل هفتگی)' : 'رسید رسمی حواله پایا و تسویه حساب هفتگی' ?>
                </div>
                <div class="sub-title">سامانه جامع تجارت الکترونیک و خدمات حیوانات خانگی آسنا (سهامی خاص)</div>
            </td>
            <td style="width: 18%; text-align: left;">
                <div style="display: inline-block; border: 1px solid #cbd5e1; border-radius: 8px; padding: 4px; background: #fff;">
                    <?= $qrSvg ?>
                </div>
                <div style="font-size: 9px; text-align: center; color: #64748b; margin-top: 2px;">اسکن جهت اعتبارسنجی</div>
            </td>
        </tr>
    </table>

    <!-- Treasury & Beneficiary Information -->
    <table class="meta-table">
        <tr>
            <th>شناسه یکتای حواله پایا:</th>
            <td style="font-family: monospace; font-weight: bold; color: #001a48; font-size: 13px;">
                <?= htmlspecialchars($batchCode) ?>
            </td>
            <th>تاریخ و چرخه تسویه:</th>
            <td><?= $jalaliDate ?> (سیکل پایا پنج‌شنبه)</td>
        </tr>
        <tr>
            <th>حساب و کارت مبدا (آسنا):</th>
            <td style="font-size: 11px;">
                <strong><?= htmlspecialchars($asenaHolder) ?></strong> (<?= htmlspecialchars($asenaBank) ?>)
                <br>
                <span dir="ltr" style="font-family: monospace; color: #001a48; font-weight: bold;">
                    کارت: <?= chunk_split(preg_replace('/[^\d]/', '', $asenaCard), 4, '-') ?>
                </span>
                <br>
                <span dir="ltr" style="font-family: monospace; color: #555; font-size: 10px;">
                    شبا: <?= htmlspecialchars($asenaSheba) ?>
                </span>
            </td>
            <th>نام و هویت ذینفع واریز:</th>
            <td>
                <?php if ($viewMode === 'master'): ?>
                    <strong>فهرست کلی ذینفعان بسته (<?= count($payaRecipients) ?> فروشنده و مرکز درمانی)</strong>
                <?php else: ?>
                    <strong><?= htmlspecialchars($activeRecipient['name'] ?? $seller['bank_account_holder'] ?? $seller['name']) ?></strong>
                    <?php if ($isAdmin && count($payaRecipients) > 1): ?>
                        <br>
                        <select onchange="window.location.href='?batch_code=<?= urlencode($batchCode) ?>&sheba='+this.value;" style="margin-top: 4px; font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1;">
                            <?php foreach ($payaRecipients as $pr): ?>
                                <option value="<?= htmlspecialchars($pr['sheba']) ?>" <?= (strtoupper($pr['sheba']) === strtoupper($beneficiarySheba)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pr['name']) ?> (<?= number_format($pr['amount']) ?> تومان)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>شماره شبا مقصد (IBAN):</th>
            <td dir="ltr" style="font-family: monospace; font-weight: bold; text-align: right; color: #059669;">
                <?= ($viewMode === 'master') ? 'واریز چندگانه به حساب‌های شبا ذینفعان' : htmlspecialchars($beneficiarySheba ?: 'ثبت نشده') ?>
            </td>
            <th>بانک عامل مقصد:</th>
            <td><?= ($viewMode === 'master') ? 'شبکه بین‌بانکی شاپرک و پایا' : htmlspecialchars($activeRecipient['bank'] ?? $seller['bank_name'] ?: 'بانک متصل شبا') ?></td>
        </tr>
        <tr>
            <th>وضعیت در پایا:</th>
            <td>
                <span class="badge">✔ پرداخت و تسویه قطعی پایا (تایید بانک مرکزی)</span>
            </td>
            <th>کارمزد پلتفرم:</th>
            <td style="color: #475569; font-weight: bold;">
                ۵٪ کارمزد بازاریابی و زیرساخت پلتفرم آسنا کسر گردید
            </td>
        </tr>
    </table>

    <?php if ($viewMode === 'master'): ?>
        <!-- Master Batch Recipients Table -->
        <h4 style="margin: 15px 0 8px 0; font-size: 12px; color: #001a48;">ریز کلیه واریزی‌های این بسته حواله پایا به تفکیک ذینفع:</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">ردیف</th>
                    <th style="width: 25%;">نام و هویت ذینفع</th>
                    <th style="width: 30%;">شماره شبای مقصد</th>
                    <th style="width: 17%;">بانک مقصد</th>
                    <th style="width: 20%;">مبلغ واریزی خالص</th>
                </tr>
            </thead>
            <tbody>
                <?php $rIdx = 1; foreach ($payaRecipients as $pr): ?>
                <tr>
                    <td><?= $rIdx++ ?></td>
                    <td style="font-weight: bold; text-align: right; padding-right: 10px;"><?= htmlspecialchars($pr['name']) ?></td>
                    <td dir="ltr" style="font-family: monospace; font-size: 11px;"><?= htmlspecialchars($pr['sheba']) ?></td>
                    <td><?= htmlspecialchars($pr['bank']) ?></td>
                    <td style="font-weight: bold; color: #059669; font-family: monospace;"><?= number_format($pr['amount']) ?> تومان</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- Beneficiary Breakdown (Orders and Appointments) -->
        <h4 style="margin: 15px 0 8px 0; font-size: 12px; color: #001a48;">ریز اقلام سفارشات و خدمات ویزیت تسویه‌شده در این حواله:</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">ردیف</th>
                    <th style="width: 12%;">نوع</th>
                    <th style="width: 18%;">شناسه مرجع</th>
                    <th style="width: 24%;">کد رهگیری / شرح</th>
                    <th style="width: 14%;">مبلغ ناخالص</th>
                    <th style="width: 12%;">کارمزد (۵٪)</th>
                    <th style="width: 16%;">مبلغ خالص واریزی</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                if (!empty($ledgerItems) || !empty($settledAppointments)):
                    foreach ($ledgerItems as $item): 
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><span style="background: #e0f2fe; color: #0369a1; padding: 2px 5px; border-radius: 4px; font-weight: bold; font-size: 9px;">سفارش کالا</span></td>
                    <td style="font-weight: bold; font-family: monospace;">#PC-<?= (int)$item['order_id'] ?></td>
                    <td style="font-family: monospace; font-size: 10px;"><?= htmlspecialchars($item['post_tracking_code'] ?: 'ارسال اکسپرس') ?></td>
                    <td><?= number_format($item['gross_amount']) ?> تومان</td>
                    <td style="color: #dc2626;">-<?= number_format($item['commission_amount']) ?></td>
                    <td style="font-weight: bold; color: #059669;"><?= number_format($item['net_seller_amount']) ?> تومان</td>
                </tr>
                <?php 
                    endforeach;
                    foreach ($settledAppointments as $apt):
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><span style="background: #dcfce7; color: #15803d; padding: 2px 5px; border-radius: 4px; font-weight: bold; font-size: 9px;">ویزیت پزشکی</span></td>
                    <td style="font-weight: bold; font-family: monospace;">#APT-<?= (int)$apt['id'] ?></td>
                    <td>ویزیت دکتر <?= htmlspecialchars($apt['doctor_name'] ?: 'متخصص') ?></td>
                    <td><?= number_format($apt['fee']) ?> تومان</td>
                    <td style="color: #dc2626;">-<?= number_format($apt['commission_amount']) ?></td>
                    <td style="font-weight: bold; color: #059669;"><?= number_format($apt['net_amount']) ?> تومان</td>
                </tr>
                <?php 
                    endforeach;
                else: 
                ?>
                <tr>
                    <td>1</td>
                    <td><span style="background: #f1f5f9; color: #475569; padding: 2px 5px; border-radius: 4px; font-weight: bold; font-size: 9px;">کیف‌پول</span></td>
                    <td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($batchCode) ?></td>
                    <td>تسویه هفتگی کیف‌پول الکترونیک (پایا بانک مرکزی)</td>
                    <td><?= number_format(round($totalSettledForRecipient / 0.95)) ?> تومان</td>
                    <td style="color: #dc2626;">-<?= number_format(round($totalSettledForRecipient / 0.95 * 0.05)) ?></td>
                    <td style="font-weight: bold; color: #059669;"><?= number_format($totalSettledForRecipient) ?> تومان</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Total Settled Box -->
    <div class="total-box">
        <div style="font-size: 13px; color: #555; margin-bottom: 5px;">
            <?= ($viewMode === 'master') ? 'مجموع کل مبالغ واریز شده در این بسته تجمیعی پایا:' : 'مبلغ کل واریز شده به شماره شبای ذینفع:' ?>
        </div>
        <div class="total-amount"><?= number_format($totalSettledForRecipient) ?> تومان</div>
        <div style="font-size: 11px; color: #666; margin-top: 4px;">
            معادل <strong><?= number_format($totalSettledForRecipient * 10) ?></strong> ریال تمام، واریز شده از طریق چرخه هفتگی پایا بانک مرکزی جمهوری اسلامی ایران
        </div>
    </div>

    <!-- Signatures and Official Stamp -->
    <table class="footer-signs">
        <tr>
            <td>
                <strong>امضا و مهر واحد خزانه‌داری و امور مالی آسنا:</strong>
                <div class="stamp-treasury">
                    تایید شد - امور مالی و خزانه‌داری آسنا
                    <br>
                    <span style="font-size: 9px; font-family: monospace;">PAYA-SEAL: <?= strtoupper(substr(md5($batchCode), 0, 10)) ?></span>
                </div>
            </td>
            <td>
                <strong>امضا و تایید دریافت‌کننده / ذینفع:</strong>
                <div style="margin-top: 40px; color: #475569; font-weight: bold;">
                    <?= htmlspecialchars($seller['bank_account_holder'] ?: $seller['name'] ?: $activeRecipient['name'] ?? 'ذینفع') ?>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 10px; color: #64748b; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px;">
        این رسید دیجیتال دارای امضای الکترونیک معتبر و بارکد استعلام آنی است و مطابق با ضوابط سامانه پایا بانک مرکزی جمهوری اسلامی ایران دارای اعتبار قانونی می‌باشد.
    </div>
</div>

</body>
</html>
