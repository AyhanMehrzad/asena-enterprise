<?php
/**
 * ASENA Enterprise - Official Paya Payout Remittance Receipt
 * Printable digital receipt for seller weekly settlements (رسید رسمی حواله پایا و تسویه هفتگی)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jdf.php';

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

// Fetch seller wallet & user info
$sellerStmt = $pdo->prepare("
    SELECT u.name, u.phone, u.national_id, w.* 
    FROM users u 
    LEFT JOIN seller_wallets w ON u.id = w.seller_id 
    WHERE u.id = ?
");
$sellerStmt->execute([$userId]);
$seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);

// Fetch items settled in this batch for this seller
$ledgerStmt = $pdo->prepare("
    SELECT l.*, o.id as order_number, o.created_at as order_date, o.post_tracking_code 
    FROM seller_escrow_ledger l
    JOIN orders o ON l.order_id = o.id
    WHERE l.settlement_batch_id = ? " . ($isAdmin ? "" : "AND l.seller_id = ?") . "
    ORDER BY l.id DESC
");
$ledgerParams = $isAdmin ? [$batch['id']] : [$batch['id'], $userId];
$ledgerStmt->execute($ledgerParams);
$items = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items) && !$isAdmin) {
    die("شما دسترسی لازم برای مشاهده این رسید تسویه را ندارید.");
}

$totalSettledForUser = 0;
foreach ($items as $it) {
    $totalSettledForUser += (int)$it['net_seller_amount'];
}
if ($isAdmin && $totalSettledForUser === 0) {
    $totalSettledForUser = (int)$batch['total_payout_amount'];
}

$payoutTimestamp = strtotime($batch['processed_at'] ?? $batch['created_at']);
$jalaliDate = jdate('Y/m/d - H:i', $payoutTimestamp);

// QR Code verification URL
$qrPayload = "ASENA-PAYA;BATCH:{$batchCode};AMOUNT:{$totalSettledForUser};SHEBA:{$seller['bank_sheba']};BENEFICIARY:{$seller['name']}";
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrPayload);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>رسید</title>
    <style>
        * { box-sizing: border-box; font-family: Tahoma, 'Vazirmatn', sans-serif; }
        body { background: #f0f2f5; margin: 0; padding: 20px; color: #111; font-size: 12px; }
        .receipt-box { max-width: 850px; margin: auto; background: #fff; padding: 30px; border: 2px solid #002d72; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #002d72; padding-bottom: 15px; }
        .header-table td { vertical-align: middle; }
        .title { font-size: 20px; font-weight: bold; color: #002d72; text-align: center; }
        .sub-title { font-size: 11px; text-align: center; color: #555; margin-top: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #ddd; }
        .meta-table th { background: #eef2f8; border: 1px solid #ccc; padding: 8px 12px; font-weight: bold; text-align: right; width: 25%; font-size: 11px; }
        .meta-table td { border: 1px solid #ccc; padding: 8px 12px; font-size: 12px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #002d72; color: #fff; padding: 8px; font-size: 11px; text-align: center; }
        .items-table td { border: 1px solid #ddd; padding: 8px; font-size: 11px; text-align: center; }
        .total-box { background: #f8fafc; border: 2px dashed #002d72; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center; }
        .total-amount { font-size: 22px; font-weight: bold; color: #059669; }
        .footer-signs { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .footer-signs td { width: 50%; text-align: center; padding: 20px; vertical-align: top; border: 1px dashed #ccc; height: 100px; font-size: 11px; }
        .no-print-bar { max-width: 850px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; }
        .btn-print { background: #002d72; color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; display: inline-flex; items-center; gap: 6px; }
        .btn-print:hover { background: #001f4f; }
        .badge { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-weight: bold; font-size: 10px; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-box { border: 1px solid #000; box-shadow: none; border-radius: 0; width: 100%; max-width: 100%; padding: 15px; }
            .no-print-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>سامانه تسویه حساب پایا و بازارگاه آسنا</strong>
    </div>
    <div>
        <button class="btn-print" onclick="window.print();">🖨️ چاپ رسید واریز (Print / PDF)</button>
    </div>
</div>

<div class="receipt-box">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: right;">
                <img src="../assets/images/logo.png" alt="لوگو" style="width: 50px; height: 50px; object-fit: contain;">
            </td>
            <td style="width: 60%; text-align: center;">
                <div class="title">رسید رسمی تسویه حساب هفتگی و حواله پایا</div>
                <div class="sub-title">سامانه جامع تجارت الکترونیک و خدمات حیوانات خانگی آسنا (سهامی خاص)</div>
            </td>
            <td style="width: 20%; text-align: left;">
                <img src="<?= htmlspecialchars($qrApiUrl) ?>" alt="QR Code" style="width: 60px; height: 60px; border: 1px solid #ccc; padding: 2px;">
            </td>
        </tr>
    </table>

    <!-- Beneficiary & Bank Info -->
    <table class="meta-table">
        <tr>
            <th>شناسه یکتای حواله پایا:</th>
            <td style="font-family: monospace; font-weight: bold; color: #002d72; font-size: 13px;">
                <?= htmlspecialchars($batchCode) ?>
            </td>
            <th>تاریخ و زمان تسویه:</th>
            <td><?= $jalaliDate ?></td>
        </tr>
        <tr>
            <th>نام و نام خانوادگی ذینفع:</th>
            <td style="font-weight: bold;"><?= htmlspecialchars($seller['bank_account_holder'] ?: $seller['name'] ?: 'فروشنده همکار') ?></td>
            <th>شماره تلفن همراه:</th>
            <td dir="ltr" style="text-align: right;"><?= htmlspecialchars($seller['phone'] ?? '-') ?></td>
        </tr>
        <tr>
            <th>شماره شبا مقصد (IBAN):</th>
            <td dir="ltr" style="font-family: monospace; font-weight: bold; text-align: right;">
                <?= htmlspecialchars($seller['bank_sheba'] ?: 'ثبت نشده') ?>
            </td>
            <th>نام بانک مقصد:</th>
            <td><?= htmlspecialchars($seller['bank_name'] ?: 'بانک متصل شبا') ?></td>
        </tr>
        <tr>
            <th>شماره کارت بانکی:</th>
            <td dir="ltr" style="font-family: monospace; text-align: right;">
                <?= !empty($seller['bank_card_number']) ? htmlspecialchars(chunk_split($seller['bank_card_number'], 4, '-')) : 'ثبت نشده' ?>
            </td>
            <th>وضعیت حواله در پایا:</th>
            <td>
                <span class="badge">✔ پرداخت و تسویه موفق</span>
            </td>
        </tr>
    </table>

    <!-- Settled Items Breakdown -->
    <h4 style="margin: 15px 0 8px 0; font-size: 12px; color: #002d72;">ریز اقلام سفارشات تسویه‌شده در این حواله:</h4>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">ردیف</th>
                <th style="width: 18%;">شماره سفارش</th>
                <th style="width: 25%;">کد رهگیری پست</th>
                <th style="width: 16%;">مبلغ ناخالص</th>
                <th style="width: 15%;">کارمزد پلتفرم (۵٪)</th>
                <th style="width: 18%;">مبلغ خالص واریزی</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1; 
            foreach ($items as $item): 
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td style="font-weight: bold; font-family: monospace;">#PC-<?= (int)$item['order_id'] ?></td>
                <td style="font-family: monospace;"><?= htmlspecialchars($item['post_tracking_code'] ?: 'تحویل حضوری/پیک') ?></td>
                <td><?= number_format($item['gross_amount']) ?> تومان</td>
                <td style="color: #dc2626;"><?= number_format($item['commission_amount']) ?> تومان</td>
                <td style="font-weight: bold; color: #059669;"><?= number_format($item['net_seller_amount']) ?> تومان</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Total Settled Box -->
    <div class="total-box">
        <div style="font-size: 13px; color: #555; margin-bottom: 5px;">مبلغ کل واریز شده به شماره شبای ذینفع:</div>
        <div class="total-amount"><?= number_format($totalSettledForUser) ?> تومان</div>
        <div style="font-size: 11px; color: #666; margin-top: 4px;">
            معادل <?= number_format($totalSettledForUser * 10) ?> ریال تمام، واریز شده از طریق چرخه هفتگی پایا بانک مرکزی
        </div>
    </div>

    <!-- Signatures -->
    <table class="footer-signs">
        <tr>
            <td>
                <strong>امضا و مهر واحد امور مالی و خزانه‌داری آسنا:</strong>
                <div style="margin-top: 35px; color: #002d72; font-weight: bold;">تایید شد - امور مالی پلتفرم</div>
            </td>
            <td>
                <strong>امضا و تایید دریافت‌کننده / فروشنده:</strong>
                <div style="margin-top: 35px; color: #555;"><?= htmlspecialchars($seller['bank_account_holder'] ?: $seller['name']) ?></div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 10px; color: #888; margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px;">
        این رسید مطابق با ضوابط سامانه تسویه ناخالص آنی (ساتنا) و پایا بانک مرکزی جمهوری اسلامی ایران صادر شده و دارای اعتبار قانونی است.
    </div>
</div>

</body>
</html>
