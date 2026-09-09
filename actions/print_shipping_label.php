<?php
/**
 * ASENA Enterprise - Standard Postal Parcel Label Generator
 * Generates official Iranian postal shipping labels (لیبل استاندارد پستی مرسوله)
 * Formatted for thermal label printers and standard A5/A6 paper printouts.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/functions.php';
App::boot();

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');

if (!$userId) {
    die("لطفاً برای چاپ برچسب مرسوله وارد حساب کاربری خود شوید.");
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    die("شناسه سفارش نامعتبر است.");
}

// Fetch order and buyer details
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name as buyer_name, 
           u.phone as buyer_phone, 
           u.city as buyer_city, 
           u.postal_code as buyer_postal_code, 
           u.address as buyer_address,
           u.latitude as buyer_lat,
           u.longitude as buyer_lng
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("سفارش مورد نظر یافت نشد.");
}

// Authorization check (Admin, Buyer, Seller of item, Organization manager)
$authorized = ($userRole === 'admin' || (int)$order['user_id'] === $userId);
if (!$authorized) {
    $chkSeller = $pdo->prepare("SELECT 1 FROM order_items WHERE order_id = ? AND seller_id = ? LIMIT 1");
    $chkSeller->execute([$orderId, $userId]);
    if ($chkSeller->fetchColumn()) {
        $authorized = true;
    }
}
if (!$authorized && in_array($userRole, ['organization_admin', 'clinic_manager', 'pharmacist'])) {
    $authorized = true; // Authorized staff
}

if (!$authorized) {
    die("شما مجوز چاپ برچسب پستی این سفارش را ندارید.");
}

// Fetch items
$itemStmt = $pdo->prepare("
    SELECT oi.*, 
           COALESCE(pm.name, p.name, oi.product_name_snapshot) as item_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN pharmacy_medicines pm ON oi.product_id = pm.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Sender defaults (ASENA Central Logistics or Merchant)
$senderName = "سامانه جامع خدمات دامپزشکی و پت‌شاپ آسنا";
$senderPhone = "۰۲۱-۸۸۸۸۸۸۸۸ / ۰۹۱۴۶۶۷۶۹۷۸";
$senderCity = "تهران";
$senderPostal = "۱۴۳۳۸۹۳۱۱۱";
$senderAddress = "خیابان ولیعصر، بالاتر از پارک ساعی، پلاک ۲۱۴، مرکز پردازش مرسولات آسنا";

// Recipient details
$recipientName = htmlspecialchars($order['buyer_name'] ?: 'مشتری محترم');
$recipientPhone = htmlspecialchars($order['buyer_phone'] ?: '-');
$recipientCity = htmlspecialchars($order['buyer_city'] ?: 'تهران');
$recipientPostal = !empty($order['buyer_postal_code']) ? htmlspecialchars($order['buyer_postal_code']) : 'ثبت نشده در پروفایل';
$recipientAddress = htmlspecialchars($order['shipping_address'] ?: ($order['buyer_address'] ?: 'نشانی ثبت نشده'));

$trackingCode = $order['post_tracking_code'] ?: ($order['tracking_code'] ?: 'PC-' . $order['id']);
$carrierName = $order['carrier_name'] ?: 'شرکت ملی پست (پیشتاز) / پستکس';
$orderDate = substr($order['created_at'], 0, 10);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برچسب پستی مرسوله #PC-<?= $order['id'] ?> | آسنا</title>
    <link rel="stylesheet" href="../assets/fonts/fonts.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Vazirmatn', Tahoma, sans-serif; }
        body { background-color: #f1f5f9; padding: 20px; color: #0f172a; direction: rtl; text-align: right; }
        .label-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #000000;
            padding: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .header-title { font-size: 16px; font-weight: 900; }
        .header-carrier { font-size: 11px; font-weight: 700; background: #e2e8f0; padding: 4px 8px; border-radius: 4px; }
        .barcode-box {
            text-align: center;
            margin: 10px 0;
            padding: 8px;
            background: #f8fafc;
            border: 1px dashed #64748b;
            border-radius: 6px;
        }
        .barcode-lines {
            display: inline-block;
            height: 40px;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: 900;
            line-height: 40px;
            filter: drop-shadow(0 0 1px #000);
        }
        .barcode-text { font-size: 12px; font-weight: 900; font-family: monospace; letter-spacing: 2px; }
        .party-box {
            border: 1.5px solid #000000;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .party-title {
            font-size: 12px;
            font-weight: 900;
            background: #0f172a;
            color: #ffffff;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .party-row { font-size: 12px; line-height: 1.6; margin-bottom: 3px; }
        .party-label { font-weight: 800; color: #334155; }
        .postal-code-badge {
            display: inline-block;
            font-size: 16px;
            font-weight: 900;
            font-family: monospace;
            background: #fef08a;
            border: 1.5px solid #ca8a04;
            padding: 2px 10px;
            border-radius: 4px;
            letter-spacing: 2px;
            color: #713f12;
            margin-top: 4px;
        }
        .items-summary {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            font-size: 11px;
            margin-bottom: 10px;
            background: #f8fafc;
        }
        .items-title { font-weight: 800; margin-bottom: 4px; }
        .footer-note {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .print-actions {
            max-width: 600px;
            margin: 16px auto 0;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
        }
        .btn-close {
            background: #e2e8f0;
            color: #334155;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .print-actions { display: none; }
            .label-container {
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="label-container">
        <!-- Header -->
        <div class="header-bar">
            <div>
                <div class="header-title">برچسب رسمی ارسال مرسوله پستی</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">سامانه ملی لجستیک، کلینیک و پت‌شاپ آسنا (ASENA)</div>
            </div>
            <div style="text-align: left;">
                <span class="header-carrier"><?= htmlspecialchars($carrierName) ?></span>
                <div style="font-size: 11px; font-weight: 800; margin-top: 4px; font-family: monospace;">سفارش: #PC-<?= $order['id'] ?></div>
            </div>
        </div>

        <!-- Barcode Block -->
        <div class="barcode-box">
            <div class="barcode-lines">||||| | |||| ||| || ||||| | ||||</div>
            <div class="barcode-text">کد رهگیری: <?= htmlspecialchars($trackingCode) ?></div>
        </div>

        <!-- Sender Details -->
        <div class="party-box" style="background: #fafafa;">
            <span class="party-title">فرستنده (مبدا)</span>
            <div class="party-row">
                <span class="party-label">نام فرستنده:</span>
                <span><?= htmlspecialchars($senderName) ?></span>
                <span style="margin: 0 8px;">|</span>
                <span class="party-label">تلفن:</span>
                <span style="font-family: monospace;"><?= $senderPhone ?></span>
            </div>
            <div class="party-row">
                <span class="party-label">نشانی فرستنده:</span>
                <span><?= htmlspecialchars($senderAddress) ?></span>
            </div>
            <div class="party-row">
                <span class="party-label">کد پستی فرستنده:</span>
                <strong style="font-family: monospace;"><?= $senderPostal ?></strong>
            </div>
        </div>

        <!-- Recipient Details -->
        <div class="party-box" style="border-width: 2px; border-color: #000;">
            <span class="party-title" style="background: #0284c7;">گیرنده (مقصد)</span>
            <div class="party-row">
                <span class="party-label">نام و نام خانوادگی گیرنده:</span>
                <strong style="font-size: 14px;"><?= $recipientName ?></strong>
                <span style="margin: 0 8px;">|</span>
                <span class="party-label">شماره تماس:</span>
                <strong style="font-family: monospace; font-size: 13px;"><?= $recipientPhone ?></strong>
            </div>
            <div class="party-row">
                <span class="party-label">استان / شهر مقصد:</span>
                <strong><?= $recipientCity ?></strong>
            </div>
            <div class="party-row" style="margin-top: 4px;">
                <span class="party-label">نشانی کامل پستی:</span>
                <span style="font-weight: 700; line-height: 1.8;"><?= $recipientAddress ?></span>
            </div>
            <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px;">
                <span class="party-label" style="font-size: 13px;">کد پستی ۱۰ رقمی گیرنده:</span>
                <span class="postal-code-badge"><?= $recipientPostal ?></span>
            </div>
        </div>

        <!-- Package Content Summary -->
        <div class="items-summary">
            <div class="items-title">اقلام محتوای مرسوله (<?= count($items) ?> ردیف کالا):</div>
            <ul style="padding-right: 18px; line-height: 1.6;">
                <?php foreach ($items as $it): ?>
                <li>
                    <span><?= htmlspecialchars($it['item_name']) ?></span>
                    <strong style="font-family: monospace;">(<?= (int)$it['quantity'] ?> عدد)</strong>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <span>وضعیت مالی: <strong>تسویه آنلاین (کرایه پرداخت شده)</strong></span>
            <span>تاریخ چاپ: <?= jdate('Y/m/d H:i') ?></span>
            <span>شناسه سیستمی: ASENA-LBL-<?= $order['id'] ?></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="print-actions">
        <button onclick="window.print()" class="btn-print">🖨️ چاپ فوری برچسب مرسوله</button>
        <button onclick="window.close()" class="btn-close">بستن پنجره</button>
    </div>

</body>
</html>
