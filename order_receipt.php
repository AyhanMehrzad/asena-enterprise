<?php
/**
 * order_receipt.php - ASENA Enterprise Official Digital Payment Receipt & Customer Invoice
 * Strictly displays customer costs, applied promo code discount, and 10% VAT.
 * Zero disclosure of internal 15% platform commission.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if ($orderId <= 0) {
    header('Location: profile.php');
    exit;
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || (!$isAdmin && (int)$order['user_id'] !== $userId)) {
    die("دسترسی به این رسید مجاز نیست یا سفارش یافت نشد.");
}

// Fetch order items
$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch buyer details
$uStmt = $pdo->prepare("SELECT name, phone, email, city, address, postal_code FROM users WHERE id = ?");
$uStmt->execute([(int)$order['user_id']]);
$buyer = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Compute subtotal of items
$itemsSubtotal = 0;
foreach ($items as $it) {
    $itemsSubtotal += ((int)$it['price_at_purchase'] * (int)$it['quantity']);
}

$discountAmount = (int)($order['discount_amount'] ?? 0);
$taxAmount = (int)($order['tax_amount'] ?? 0);
$totalPaid = (int)$order['total_amount'];
$refId = $order['gateway_ref_id'] ?? '—';
$promoCode = $order['promo_code'] ?? null;

// Date formatter
$persianDate = date('Y/m/d - H:i', strtotime($order['created_at']));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسید رسمی پرداخت و صورت‌حساب سفارش #PC-<?= $orderId ?> | سامانه آسنا</title>
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <link rel="stylesheet" href="assets/css/vazirmatn.css">
    <link rel="stylesheet" href="assets/css/material-symbols.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .receipt-card { box-shadow: none !important; border: 1px solid #ddd !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 sm:px-6 lg:px-8">

<div class="max-w-3xl mx-auto space-y-4">

    <!-- Action Bar (No Print) -->
    <div class="no-print flex items-center justify-between bg-white px-6 py-3.5 rounded-2xl shadow-sm border border-slate-200">
        <a href="profile.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-primary transition">
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
            <span>بازگشت به حساب کاربری</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition cursor-pointer">
                <span class="material-symbols-outlined text-sm">print</span>
                <span>چاپ فاکتور و رسید</span>
            </button>
        </div>
    </div>

    <!-- Main Receipt Document -->
    <div class="receipt-card bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
        
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-[#001a48] to-[#002d72] text-white p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-3xl text-[#fd8100]">verified</span>
                    <h1 class="text-xl font-black">رسید رسمی پرداخت و فاکتور فروش</h1>
                </div>
                <p class="text-xs text-blue-200">شرکت توسعه تجارت و سلامت آسنا (سهامی خاص)</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-white/20 text-left">
                <span class="text-[11px] text-blue-200 block">وضعیت تراکنش:</span>
                <span class="text-xs font-bold text-emerald-300 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                    <span>پرداخت موفق با زرین‌پال</span>
                </span>
            </div>
        </div>

        <!-- Meta Information Grid -->
        <div class="p-6 sm:p-8 bg-slate-50/70 border-b border-slate-200">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-500 block mb-1">شماره سفارش:</span>
                    <strong class="font-mono font-bold text-slate-800 text-sm">#PC-<?= $orderId ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block mb-1">کد رهگیری زرین‌پال:</span>
                    <strong class="font-mono font-bold text-emerald-700 text-sm" dir="ltr"><?= htmlspecialchars($refId) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block mb-1">تاریخ و ساعت:</span>
                    <span class="font-mono text-slate-800"><?= $persianDate ?></span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-1">درگاه پرداخت:</span>
                    <span class="font-bold text-slate-800">درگاه امن الکترونیک بانکی (زرین‌پال)</span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-700">
                <div>
                    <span class="text-slate-500 block mb-0.5">مشخصات خریدار:</span>
                    <strong class="text-slate-900"><?= htmlspecialchars($buyer['name'] ?: 'کاربر گرامی') ?></strong>
                    <span class="text-slate-500 mr-2 font-mono">(<?= htmlspecialchars($buyer['phone'] ?: '') ?>)</span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-0.5">آدرس ارسال مرسوله:</span>
                    <span><?= htmlspecialchars($order['shipping_address'] ?: ($buyer['city'] . '، ' . $buyer['address'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="p-6 sm:p-8 space-y-6">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 pb-2">
                            <th class="py-2.5 font-bold">#</th>
                            <th class="py-2.5 font-bold">شرح کالا یا خدمات</th>
                            <th class="py-2.5 font-bold text-center">تعداد</th>
                            <th class="py-2.5 font-bold text-left">قیمت واحد (تومان)</th>
                            <th class="py-2.5 font-bold text-left">مبلغ کل (تومان)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(!empty($items)): ?>
                            <?php $idx = 1; foreach ($items as $item): ?>
                            <tr>
                                <td class="py-3 text-slate-400 font-mono"><?= $idx++ ?></td>
                                <td class="py-3 font-bold text-slate-800"><?= htmlspecialchars($item['product_name_snapshot'] ?: 'محصول فروشگاه') ?></td>
                                <td class="py-3 text-center font-mono font-bold"><?= (int)$item['quantity'] ?></td>
                                <td class="py-3 text-left font-mono"><?= number_format((int)$item['price_at_purchase']) ?></td>
                                <td class="py-3 text-left font-mono font-bold text-slate-800"><?= number_format((int)$item['price_at_purchase'] * (int)$item['quantity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">سفارش عمومی سامانه آسنا</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary Box -->
            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 space-y-3 text-xs max-w-sm mr-auto">
                <div class="flex justify-between items-center text-slate-600">
                    <span>جمع اقلام:</span>
                    <span class="font-mono font-bold text-slate-800"><?= number_format($itemsSubtotal ?: ($totalPaid - $taxAmount + $discountAmount)) ?> تومان</span>
                </div>

                <?php if ($discountAmount > 0): ?>
                <div class="flex justify-between items-center text-emerald-700 bg-emerald-50 px-2.5 py-1.5 rounded-xl border border-emerald-200">
                    <span class="font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">confirmation_number</span>
                        <span>تخفیف کد <?= htmlspecialchars($promoCode ?: 'تبلیغاتی') ?>:</span>
                    </span>
                    <span class="font-mono font-bold">-<?= number_format($discountAmount) ?> تومان</span>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center text-slate-600">
                    <span>مالیات بر ارزش افزوده (۱۰٪ قانونی):</span>
                    <span class="font-mono font-bold text-slate-800">+<?= number_format($taxAmount) ?> تومان</span>
                </div>

                <div class="flex justify-between items-center text-slate-600">
                    <span>هزینه بسته‌بندی و ارسال:</span>
                    <span class="font-bold text-emerald-600">رایگان</span>
                </div>

                <div class="border-t border-slate-200 pt-3 flex justify-between items-center text-sm font-black text-[#001a48]">
                    <span>مبلغ پرداخت شده:</span>
                    <span class="font-mono text-base text-emerald-700"><?= number_format($totalPaid) ?> تومان</span>
                </div>
            </div>

            <!-- Legal Footer Note -->
            <div class="pt-4 border-t border-slate-200 text-[11px] text-slate-500 leading-relaxed space-y-1">
                <p>• این سند به منزله فاکتور رسمی الکترونیک و تأییدیه قطعی پرداخت مشتری در سامانه آسنا می‌باشد.</p>
                <p>• طبق قوانین تجارت الکترونیک، سفارشات شامل ۷ روز مهلت بازرسی و ضمانت اصالت کالا می‌باشند.</p>
            </div>

        </div>

    </div>

</div>

</body>
</html>
