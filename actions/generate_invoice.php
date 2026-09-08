<?php
/**
 * ASENA Enterprise - Official Iranian Tax Invoice Generator
 * Standard Tax Compliance: Article 169 Direct Taxation Code (صورتحساب الکترونیکی فروش کالا و خدمات)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/jdf.php';

if (empty($_GET['order_id'])) {
    die("شناسه سفارش مشخص نگردیده است.");
}

$orderId = (int)$_GET['order_id'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

// Fetch order
$stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.phone as user_phone, u.national_id, u.address as user_address, u.city as user_city, u.postal_code as user_postal FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("سفارش مورد نظر یافت نشد.");
}

// Access guard: owner or admin only
if (!$isAdmin && (int)$order['user_id'] !== $userId) {
    die("شما دسترسی لازم برای مشاهده این فاکتور را ندارید.");
}

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT oi.*, 
           COALESCE(pm.name, p.name) as title,
           COALESCE(pm.category, p.category) as category
    FROM order_items oi
    LEFT JOIN pharmacy_medicines pm ON oi.product_id = pm.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Date formatting
$orderTimestamp = strtotime($order['created_at']);
$jalaliDate = jdate('Y/m/d', $orderTimestamp);
$invoiceSerial = 'ASENA-INV-' . jdate('Y', $orderTimestamp) . '-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);

// Calculate VAT breakdown (10% VAT in Iran)
$vatRate = 0.10;
$grossTotal = (int)$order['total_amount'];
$taxableBase = (int)round($grossTotal / (1.0 + $vatRate));
$calculatedVat = $grossTotal - $taxableBase;

// QR Code payload (Standard tax verification format)
$qrText = "ASENA;INV:{$invoiceSerial};DATE:{$jalaliDate};AMOUNT:{$grossTotal};BUYER:{$order['user_name']}";
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrText);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاکتور</title>
    <style>
        * { box-sizing: border-box; font-family: Tahoma, 'Vazirmatn', sans-serif; }
        body { background: #f0f2f5; margin: 0; padding: 20px; color: #111; font-size: 12px; }
        .invoice-box { max-width: 900px; margin: auto; background: #fff; padding: 25px; border: 2px solid #222; border-radius: 8px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table td { padding: 4px; vertical-align: middle; }
        .title { font-size: 18px; font-weight: bold; text-align: center; }
        .sub-title { font-size: 11px; text-align: center; color: #555; }
        .info-card { width: 100%; border: 1px solid #333; border-collapse: collapse; margin-bottom: 12px; }
        .info-card th { background: #e9ecef; border: 1px solid #333; padding: 6px; font-size: 11px; font-weight: bold; text-align: right; }
        .info-card td { border: 1px solid #333; padding: 6px; font-size: 11px; }
        .items-table { width: 100%; border: 1px solid #333; border-collapse: collapse; margin-bottom: 12px; }
        .items-table th { background: #e9ecef; border: 1px solid #333; padding: 7px; font-size: 11px; text-align: center; }
        .items-table td { border: 1px solid #333; padding: 7px; font-size: 11px; text-align: center; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .bold { font-weight: bold; }
        .footer-signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .footer-signatures td { width: 50%; text-align: center; padding: 20px; height: 90px; vertical-align: top; border: 1px dashed #aaa; }
        .no-print-bar { max-width: 900px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; }
        .btn-print { background: #002d72; color: #fff; padding: 8px 18px; border: none; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-box { border: 1px solid #000; padding: 10px; width: 100%; max-width: 100%; }
            .no-print-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>صورتحساب رسمی سامانه جامع آسنا</strong>
    </div>
    <div>
        <button class="btn-print" onclick="window.print();">🖨️ چاپ فاکتور رسمی (Print / PDF)</button>
    </div>
</div>

<div class="invoice-box">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                <img src="../assets/images/logo.png" alt="لوگو آسنا" style="width: 60px; height: 60px; object-fit: contain;">
            </td>
            <td style="width: 50%;">
                <div class="title">صورتحساب رسمی فروش کالا و خدمات</div>
                <div class="sub-title">مطابق با ماده ۱۶۹ و ۱۶۹ مکرر قانون مالیات‌های مستقیم و قانون مالیات بر ارزش افزوده</div>
            </td>
            <td style="width: 25%; text-align: left;">
                <div><strong>شماره سریال:</strong> <?php echo $invoiceSerial; ?></div>
                <div style="margin-top: 4px;"><strong>تاریخ:</strong> <?php echo $jalaliDate; ?></div>
                <div style="margin-top: 4px;"><strong>پیگیری درگاه:</strong> <?php echo htmlspecialchars($order['gateway_ref_id'] ?: 'پرداخت داخلی'); ?></div>
            </td>
        </tr>
    </table>

    <!-- Seller Information -->
    <table class="info-card">
        <tr>
            <th colspan="4">مشخصات فروشنده (Seller Information)</th>
        </tr>
        <tr>
            <td style="width: 20%;" class="bold">نام شخص حقوقی:</td>
            <td style="width: 30%;">شرکت پیشگامان سلامت حیوانات آسنا (سهامی خاص)</td>
            <td style="width: 20%;" class="bold">شناسه ملی:</td>
            <td style="width: 30%;">۱۴۰۰۹۸۷۶۵۴۳</td>
        </tr>
        <tr>
            <td class="bold">شماره اقتصادی:</td>
            <td>۴۱۱۵۶۷۸۹۱۲۳۴</td>
            <td class="bold">شماره ثبت:</td>
            <td>۵۸۴۱۲۰</td>
        </tr>
        <tr>
            <td class="bold">نشانی و کد پستی:</td>
            <td>تهران، خیابان ولیعصر، برج فناوری آسنا - کد پستی: ۱۹۳۹۵-۴۴۱۱</td>
            <td class="bold">تلفن و پشتیبانی:</td>
            <td dir="ltr">+98 21 8888 4400</td>
        </tr>
    </table>

    <!-- Buyer Information -->
    <table class="info-card">
        <tr>
            <th colspan="4">مشخصات خریدار (Buyer Information)</th>
        </tr>
        <tr>
            <td style="width: 20%;" class="bold">نام شخص حقیقی/حقوقی:</td>
            <td style="width: 30%;"><?php echo htmlspecialchars($order['user_name'] ?: 'مشتری آزاد'); ?></td>
            <td style="width: 20%;" class="bold">کد ملی / شناسه ملی:</td>
            <td style="width: 30%;"><?php echo htmlspecialchars($order['national_id'] ?: 'ثبت نشده'); ?></td>
        </tr>
        <tr>
            <td class="bold">شماره تماس:</td>
            <td dir="ltr"><?php echo htmlspecialchars($order['user_phone']); ?></td>
            <td class="bold">کد پستی خریدار:</td>
            <td><?php echo htmlspecialchars($order['user_postal'] ?: 'ثبت نشده'); ?></td>
        </tr>
        <tr>
            <td class="bold">نشانی تحویل:</td>
            <td colspan="3"><?php echo htmlspecialchars($order['shipping_address'] ?: ($order['user_address'] ?: 'تحویل حضوری در کلینیک مرکزی')); ?></td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">ردیف</th>
                <th style="width: 45%;">شرح کالا یا خدمت</th>
                <th style="width: 10%;">تعداد / مقدار</th>
                <th style="width: 13%;">مبلغ واحد (تومان)</th>
                <th style="width: 13%;">مبلغ کل (تومان)</th>
                <th style="width: 14%;">مالیات و عوارض (۱۰٪)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($items as $item): 
                $lineTotal = (int)$item['price_at_purchase'] * (int)$item['quantity'];
                $lineTaxable = (int)round($lineTotal / (1.0 + $vatRate));
                $lineTax = $lineTotal - $lineTaxable;
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td class="text-right bold">
                        <?php echo htmlspecialchars($item['product_name_snapshot'] ?: $item['title']); ?>
                        <span style="font-size: 10px; color: #555; display: block;">دسته‌بندی: <?php echo htmlspecialchars($item['category'] ?? 'عمومی'); ?></span>
                    </td>
                    <td class="bold"><?php echo (int)$item['quantity']; ?></td>
                    <td><?php echo number_format((int)$item['price_at_purchase']); ?></td>
                    <td class="bold"><?php echo number_format($lineTotal); ?></td>
                    <td><?php echo number_format($lineTax); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary & Totals -->
    <table class="info-card">
        <tr>
            <td style="width: 70%; text-align: left;" class="bold">جمع بهای کالاها و خدمات مشمول (مبنای مالیاتی):</td>
            <td style="width: 30%;" class="bold"><?php echo number_format($taxableBase); ?> تومان</td>
        </tr>
        <tr>
            <td style="text-align: left;" class="bold">مالیات بر ارزش افزوده و عوارض قانونی (۱۰٪):</td>
            <td class="bold"><?php echo number_format($calculatedVat); ?> تومان</td>
        </tr>
        <?php if (!empty($order['shipping_cost'])): ?>
        <tr>
            <td style="text-align: left;" class="bold">هزینه بسته‌بندی و ارسال (<?php echo htmlspecialchars($order['carrier_name'] ?: 'پست/تیپاکس'); ?>):</td>
            <td class="bold"><?php echo number_format((int)$order['shipping_cost']); ?> تومان</td>
        </tr>
        <?php endif; ?>
        <tr style="background: #e8f4fd;">
            <td style="text-align: left; font-size: 13px;" class="bold">مبلغ نهایی قابل پرداخت / پرداخت شده:</td>
            <td style="font-size: 14px; color: #002d72;" class="bold"><?php echo number_format($grossTotal + (int)($order['shipping_cost'] ?? 0)); ?> تومان</td>
        </tr>
    </table>

    <!-- Signatures & Verification Stamp -->
    <table class="footer-signatures">
        <tr>
            <td>
                <strong>مهر و امضای فروشنده:</strong>
                <div style="margin-top: 15px; color: #777; font-size: 10px;">امضای دیجیتال صادر شده توسط سامانه رسمی آسنا</div>
            </td>
            <td>
                <div style="display: flex; justify-content: space-around; align-items: center;">
                    <div>
                        <strong>امضای تحویل گیرنده (خریدار):</strong>
                        <div style="margin-top: 15px; color: #777; font-size: 10px;">رویت فاکتور و تایید سلامت فیزیکی اقلام</div>
                    </div>
                    <div>
                        <img src="<?php echo $qrApiUrl; ?>" alt="QR Code" style="width: 75px; height: 75px;">
                        <div style="font-size: 9px; color: #666; margin-top: 2px;">تأییدیه اصالت فاکتور</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
