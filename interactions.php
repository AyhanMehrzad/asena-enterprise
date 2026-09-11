<?php
/**
 * ASENA Enterprise - Interactions with Asena (پرتال تعاملات، صورت‌حساب و خدمات متقابل با آسنا)
 * For Sellers, Doctors, and Organizations/Clinics
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/jdf.php';
require_once __DIR__ . '/includes/SmsService.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'interactions.php';
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Fetch user role and details
$uStmt = $pdo->prepare("SELECT id, name, phone, role, email FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$currentUser = $uStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: logout.php');
    exit;
}

$role = $currentUser['role'] ?? 'user';
$allowedRoles = ['seller', 'doctor', 'organization', 'organization_manager', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    $_SESSION['profile_error'] = 'پرتال تعاملات مالی با آسنا مخصوص تامین‌کنندگان، فروشندگان و مراکز درمانی همکار است.';
    header('Location: profile.php');
    exit;
}

// Fetch wallet & bank details
$wStmt = $pdo->prepare("SELECT * FROM seller_wallets WHERE seller_id = ?");
$wStmt->execute([$userId]);
$wallet = $wStmt->fetch(PDO::FETCH_ASSOC);

if (!$wallet) {
    $pdo->prepare("INSERT INTO seller_wallets (seller_id, balance_pending_escrow, balance_available_for_payout, balance_settled_lifetime, sms_credits) VALUES (?, 0, 0, 0, 0)")
        ->execute([$userId]);
    $wallet = [
        'seller_id' => $userId,
        'balance_pending_escrow' => 0,
        'balance_available_for_payout' => 0,
        'balance_settled_lifetime' => 0,
        'sms_credits' => 0,
        'bank_sheba' => '',
        'bank_card_number' => '',
        'bank_name' => '',
        'bank_account_holder' => ''
    ];
}

$flashMessage = '';
$flashType = 'info';

// SMS Package Definitions loaded dynamically from Site Settings with profitable pricing
$pack100Price = (int)get_setting($pdo, 'sms_pack_100_price', 85000);
$pack500Price = (int)get_setting($pdo, 'sms_pack_500_price', 375000);
$pack1000Price = (int)get_setting($pdo, 'sms_pack_1000_price', 680000);

$smsPackages = [
    'pack_100' => ['name' => 'بسته ۱۰۰ پیامک', 'credits' => 100, 'price' => $pack100Price, 'desc' => 'مناسب اطلاع‌رسانی نوبت‌ها و سفارشات سبک (هر پیامک ' . number_format(round($pack100Price / 100)) . ' ت)'],
    'pack_500' => ['name' => 'بسته ۵۰۰ پیامک', 'credits' => 500, 'price' => $pack500Price, 'desc' => 'صرفه‌جویی ۱۲٪ — ویژه فروشگاه‌ها و مطب‌های پرمخاطب (هر پیامک ' . number_format(round($pack500Price / 500)) . ' ت)'],
    'pack_1000' => ['name' => 'بسته ۱۰۰۰ پیامک طلایی', 'credits' => 1000, 'price' => $pack1000Price, 'desc' => 'صرفه‌جویی ۲۰٪ — ویژه کلینیک‌ها و بیمارستان‌های تخصصی (هر پیامک ' . number_format(round($pack1000Price / 1000)) . ' ت)']
];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $act = $_POST['action'];

    if ($act === 'send_direct_sms') {
        $currCredits = (int)$wallet['sms_credits'];
        $recipient = SmsService::normalizePhone(trim($_POST['recipient_phone'] ?? ''));
        $msgText = trim($_POST['sms_message'] ?? '');

        if ($currCredits <= 0) {
            $flashMessage = "خطا: اعتبار پیامک شما ۰ عدد است! ارسال پیامک محدود و مسدود می‌باشد مگر با تهیه بسته پیامکی از گزینه‌های زیر.";
            $flashType = 'error';
        } elseif (empty($recipient) || strlen($recipient) < 10) {
            $flashMessage = "لطفاً شماره تلفن همراه گیرنده را به صورت معتبر وارد فرمایید (مانند ۰۹۱۲۳۴۵۶۷۸۹).";
            $flashType = 'error';
        } elseif (empty($msgText) || mb_strlen($msgText) < 5) {
            $flashMessage = "لطفاً متن پیامک را وارد نمایید (حداقل ۵ کاراکتر).";
            $flashType = 'error';
        } else {
            // Deduct credit first (atomic overdraft protection)
            $deducted = SmsService::deductUserSmsCredits($pdo, $userId, $recipient, $msgText, 1);
            if ($deducted) {
                $smsInstance = new SmsService();
                $res = $smsInstance->sendDirectSms($recipient, $msgText);
                // Refresh wallet
                $wStmt->execute([$userId]);
                $wallet = $wStmt->fetch(PDO::FETCH_ASSOC);

                $flashMessage = "پیامک اختصاصی با موفقیت به شماره {$recipient} ارسال شد و ۱ اعتبار از بسته شما کسر گردید. مانده فعلی: {$wallet['sms_credits']} عدد.";
                $flashType = 'success';
            } else {
                $flashMessage = "خطا در کسر اعتبار پیامک یا مانده ناکافی.";
                $flashType = 'error';
            }
        }
    } elseif ($act === 'buy_sms_wallet') {
        $pkgKey = trim($_POST['package_key'] ?? '');
        if (isset($smsPackages[$pkgKey])) {
            $pkg = $smsPackages[$pkgKey];
            $cost = $pkg['price'];
            $credits = $pkg['credits'];
            $available = (int)$wallet['balance_available_for_payout'];

            if ($available >= $cost) {
                // Deduct from wallet and add credits
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE seller_wallets SET balance_available_for_payout = balance_available_for_payout - ?, sms_credits = sms_credits + ? WHERE seller_id = ?")
                        ->execute([$cost, $credits, $userId]);

                    $pdo->prepare("INSERT INTO sms_package_purchases (user_id, package_name, credits, price, payment_method, payment_ref, status) VALUES (?, ?, ?, ?, 'wallet', 'WALLET_DEDUCT', 'completed')")
                        ->execute([$userId, $pkg['name'], $credits, $cost]);

                    $pdo->commit();
                    $flashMessage = "بسته «{$pkg['name']}» با موفقیت از محل موجودی کیف‌پول شما خریداری شد و {$credits} پیامک به حسابتان افزوده گردید.";
                    $flashType = 'success';
                    // Refresh wallet
                    $wStmt->execute([$userId]);
                    $wallet = $wStmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $flashMessage = "خطا در خرید بسته از کیف پول: " . $e->getMessage();
                    $flashType = 'error';
                }
            } else {
                $flashMessage = "موجودی قابل تسویه شما (" . number_format($available) . " تومان) برای خرید این بسته (" . number_format($cost) . " تومان) کافی نیست. لطفاً از گزینه پرداخت آنلاین استفاده فرمایید.";
                $flashType = 'error';
            }
        }
    } elseif ($act === 'buy_sms_gateway') {
        $pkgKey = trim($_POST['package_key'] ?? '');
        if (isset($smsPackages[$pkgKey])) {
            $pkg = $smsPackages[$pkgKey];
            $_SESSION['pending_order'] = [
                'type' => 'sms_package',
                'package_key' => $pkgKey,
                'package_name' => $pkg['name'],
                'credits' => $pkg['credits'],
                'total_amount' => $pkg['price'],
                'created_at' => time()
            ];
            header('Location: payment.php');
            exit;
        }
    } elseif ($act === 'new_ticket_with_asena') {
        $subject = trim($_POST['subject'] ?? 'درخواست پشتیبانی و حسابرسی');
        $dept = trim($_POST['department'] ?? 'امور مالی و تسویه پایا');
        $initialMessage = trim($_POST['message'] ?? '');

        if (!empty($initialMessage)) {
            $fullSubject = "[{$dept}] {$subject}";
            $insT = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
            $insT->execute([$userId]);
            $ticketId = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'user', ?, NOW())")
                ->execute([$ticketId, "موضوع: {$fullSubject}\n\n{$initialMessage}"]);

            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', 'پیام شما به واحد خزانه‌داری و مدیریت آسنا ارسال شد. همکاران امور مالی به زودی پاسخگوی شما خواهند بود.', NOW())")
                ->execute([$ticketId]);

            header("Location: chat.php?ticket_id={$ticketId}");
            exit;
        } else {
            $flashMessage = "لطفاً متن پیام تیکت را وارد نمایید.";
            $flashType = 'error';
        }
    }
}

// 1. Fetch Debits to Asena: 5% Platform Commission from Orders & Appointments
$commOrdersStmt = $pdo->prepare("
    SELECT l.*, o.id as order_number, o.created_at as order_date 
    FROM seller_escrow_ledger l
    JOIN orders o ON l.order_id = o.id
    WHERE l.seller_id = ?
    ORDER BY l.id DESC
    LIMIT 30
");
$commOrdersStmt->execute([$userId]);
$ledgerItems = $commOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

$commAptsStmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, u.name as customer_name 
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.organization_id = ? OR d.user_id = ?
    ORDER BY a.id DESC
    LIMIT 30
");
$commAptsStmt->execute([$userId, $userId]);
$appointmentItems = $commAptsStmt->fetchAll(PDO::FETCH_ASSOC);

// Totals of Commission
$totalCommissionPaid = 0;
foreach ($ledgerItems as $li) {
    $totalCommissionPaid += (int)$li['commission_amount'];
}
foreach ($appointmentItems as $ai) {
    $totalCommissionPaid += (int)($ai['commission_amount'] ?: round((int)$ai['fee'] * 0.05));
}

// 2. Fetch SMS Purchases & Usage Logs
$smsPurchasesStmt = $pdo->prepare("SELECT * FROM sms_package_purchases WHERE user_id = ? ORDER BY id DESC LIMIT 15");
$smsPurchasesStmt->execute([$userId]);
$smsPurchases = $smsPurchasesStmt->fetchAll(PDO::FETCH_ASSOC);

$smsSpentTotal = 0;
foreach ($smsPurchases as $sp) {
    if ($sp['status'] === 'completed') {
        $smsSpentTotal += (int)$sp['price'];
    }
}

$smsUsageStmt = $pdo->prepare("SELECT * FROM sms_usage_logs WHERE user_id = ? ORDER BY id DESC LIMIT 15");
$smsUsageStmt->execute([$userId]);
$smsLogs = $smsUsageStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Paya Settlements received from Asena
$payoutsStmt = $pdo->prepare("
    SELECT b.* 
    FROM seller_payout_batches b
    WHERE b.paya_export_content LIKE ? OR b.id IN (
        SELECT DISTINCT settlement_batch_id FROM seller_escrow_ledger WHERE seller_id = ? AND settlement_batch_id IS NOT NULL
    ) OR b.id IN (
        SELECT DISTINCT settlement_batch_id FROM appointments WHERE (organization_id = ? OR doctor_id IN (SELECT id FROM doctors WHERE user_id = ?)) AND settlement_batch_id IS NOT NULL
    )
    ORDER BY b.id DESC
    LIMIT 20
");
$shebaSearch = '%' . ($wallet['bank_sheba'] ?: 'XYZ_NOT_FOUND') . '%';
$payoutsStmt->execute([$shebaSearch, $userId, $userId, $userId]);
$payoutBatches = $payoutsStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Dedicated Separated Tickets with Asena Management
$ticketsStmt = $pdo->prepare("
    SELECT t.*, 
           COALESCE(
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1),
               'پیامی ثبت نشده است'
           ) as last_message,
           (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_sender,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_time
    FROM tickets t
    WHERE t.user_id = ? AND t.mode = 'admin'
    ORDER BY (t.status = 'open') DESC, t.updated_at DESC
");
$ticketsStmt->execute([$userId]);
$myTickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

// Active Tab
$activeTab = $_GET['tab'] ?? 'overview';
if (!in_array($activeTab, ['overview', 'debits', 'payouts', 'sms', 'tickets'])) {
    $activeTab = 'overview';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرتال تعاملات، حسابرسی و خدمات با آسنا</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/enterprise-ui.css">
    <link href="assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="assets/css/geist.css" rel="stylesheet"/>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <style>
        body { font-family: Tahoma, 'Vazirmatn', sans-serif; background: #f8fafc; color: #0f172a; }
        .tab-btn.active { background: #001a48; color: #fff; box-shadow: 0 4px 12px rgba(0, 26, 72, 0.15); }
    </style>
</head>
<body class="p-4 sm:p-6 lg:p-8">

<div class="max-w-[1350px] mx-auto space-y-6">

    <!-- Back to Role Panel Navigation Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="index.php" class="flex items-center gap-2 text-primary font-bold text-xs hover:text-blue-700 transition">
                <img src="assets/images/logo.png" alt="لوگو" class="w-8 h-8 object-contain">
                <span class="font-black text-sm">پلتفرم جامع آسنا</span>
            </a>
            <span class="text-slate-300">|</span>
            <span class="text-xs text-slate-500 font-bold">پرتال اختصاصی امور مالی و تعاملات متقابل</span>
        </div>

        <div class="flex items-center gap-2">
            <?php if ($role === 'seller'): ?>
                <a href="seller/index.php" class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100 transition flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                    <span>بازگشت به پنل فروشندگان</span>
                </a>
            <?php elseif ($role === 'doctor'): ?>
                <a href="doctor/index.php" class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100 transition flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">stethoscope</span>
                    <span>بازگشت به پنل پزشکان</span>
                </a>
            <?php elseif ($role === 'organization' || $role === 'organization_manager'): ?>
                <a href="organization/index.php" class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100 transition flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">apartment</span>
                    <span>بازگشت به پنل مرکز درمانی</span>
                </a>
            <?php elseif ($role === 'admin'): ?>
                <a href="admin/index.php" class="px-3.5 py-1.5 rounded-xl bg-purple-50 text-purple-700 text-xs font-bold hover:bg-purple-100 transition flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">admin_panel_settings</span>
                    <span>کنسول مدیریت ارشد</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero Banner -->
    <div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-[#001a48] via-[#002d72] to-[#1e3a8a] text-white shadow-lg relative overflow-hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                        حساب کاربری: <?= htmlspecialchars($currentUser['name']) ?>
                    </span>
                    <span class="bg-white/10 text-slate-200 text-[10px] px-2.5 py-0.5 rounded-full">
                        نقش: <?= htmlspecialchars($role) ?>
                    </span>
                </div>
                <h1 class="text-xl lg:text-2xl font-black">تعاملات مالی، صورت‌حساب کارمزد و خدمات با پلتفرم آسنا</h1>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    شفافیت کامل در مبالغ پرداختی به آسنا (کارمزد ۵٪ کاتالوگ و بسته‌های پیامک)، واریزی‌های هفتگی پایا، مانده پیامک اختصاصی و تیکت‌های پشتیبانی با خزانه‌داری
                </p>
            </div>

            <div class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/15 text-left md:text-right shrink-0">
                <span class="text-[10px] text-slate-300 block mb-1">موعد تسویه هفتگی بعدی:</span>
                <span class="text-sm font-bold text-amber-300 flex items-center gap-1.5 justify-end">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    پنج‌شنبه ساعت ۰۹:۰۰ صبح (پایا)
                </span>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if ($flashMessage): ?>
        <div class="p-4 rounded-2xl text-xs font-bold flex items-center gap-3 <?= $flashType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
            <span class="material-symbols-outlined text-lg"><?= $flashType === 'success' ? 'check_circle' : 'error' ?></span>
            <span><?= htmlspecialchars($flashMessage) ?></span>
        </div>
    <?php endif; ?>

    <!-- Overview Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. What You Pay Asena (5% Commission + SMS) -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 block mb-1">کل پرداختی شما به آسنا (کارمزد+پیامک):</span>
                <span class="text-xl font-black text-rose-600 font-mono"><?= number_format($totalCommissionPaid + $smsSpentTotal) ?> تومان</span>
                <span class="text-[10px] text-slate-400 block mt-1">کارمزد ۵٪: <?= number_format($totalCommissionPaid) ?> ت</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">receipt</span>
            </div>
        </div>

        <!-- 2. What Asena Paid You (Lifetime Settled) -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 block mb-1">مجموع واریزی‌های پایا از آسنا:</span>
                <span class="text-xl font-black text-emerald-600 font-mono"><?= number_format((int)$wallet['balance_settled_lifetime']) ?> تومان</span>
                <span class="text-[10px] text-slate-400 block mt-1">واریز شده به شماره شبای شما</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance</span>
            </div>
        </div>

        <!-- 3. Available for Next Payout -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 block mb-1">موجودی آماده تسویه پنج‌شنبه:</span>
                <span class="text-xl font-black text-[#001a48] font-mono"><?= number_format((int)$wallet['balance_available_for_payout']) ?> تومان</span>
                <span class="text-[10px] text-slate-400 block mt-1">امانی ۷ روزه: <?= number_format((int)$wallet['balance_pending_escrow']) ?> ت</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#001a48] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
        </div>

        <!-- 4. Remaining SMS Credits -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 block mb-1">اعتبار پیامک اختصاصی آسنا:</span>
                <span class="text-xl font-black text-[#fd8100] font-mono"><?= number_format((int)$wallet['sms_credits']) ?> <span class="text-xs font-normal text-slate-500">عدد</span></span>
                <span class="text-[10px] text-slate-400 block mt-1">جهت ارسال پیامک به مشتریان/بیماران</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-[#fd8100] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">sms</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2">
        <a href="?tab=overview" class="tab-btn px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 text-slate-600 hover:text-primary transition <?= $activeTab === 'overview' ? 'active' : 'bg-white' ?>">
            <span class="material-symbols-outlined text-base">dashboard</span>
            <span>نمای کلی و تراز مالی</span>
        </a>
        <a href="?tab=debits" class="tab-btn px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 text-slate-600 hover:text-primary transition <?= $activeTab === 'debits' ? 'active' : 'bg-white' ?>">
            <span class="material-symbols-outlined text-base">point_of_sale</span>
            <span>آنچه باید به آسنا بپردازید (کارمزد ۵٪)</span>
        </a>
        <a href="?tab=payouts" class="tab-btn px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 text-slate-600 hover:text-primary transition <?= $activeTab === 'payouts' ? 'active' : 'bg-white' ?>">
            <span class="material-symbols-outlined text-base">receipt_long</span>
            <span>واریزی‌های پایا از آسنا و رسیدهای رسمی</span>
        </a>
        <a href="?tab=sms" class="tab-btn px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 text-slate-600 hover:text-primary transition <?= $activeTab === 'sms' ? 'active' : 'bg-white' ?>">
            <span class="material-symbols-outlined text-base">send_to_mobile</span>
            <span>خرید بسته پیامک و گزارش مصرف</span>
        </a>
        <a href="?tab=tickets" class="tab-btn px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 text-slate-600 hover:text-primary transition <?= $activeTab === 'tickets' ? 'active' : 'bg-white' ?>">
            <span class="material-symbols-outlined text-base">support_agent</span>
            <span>تیکت‌های اختصاصی با مدیریت آسنا</span>
            <?php if (count($myTickets) > 0): ?>
                <span class="bg-primary text-white text-[10px] px-1.5 py-0.5 rounded-full"><?= count($myTickets) ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- TAB 1: OVERVIEW & FINANCIAL STATEMENT -->
    <?php if ($activeTab === 'overview'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Account Reconciliation Box (Col 7) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-5">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
                <span class="material-symbols-outlined text-primary text-base">balance</span>
                ترازنامه حسابداری و وضعیت مالی متقابل با آسنا
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50">
                    <span class="text-slate-600">گردش کل فروش و خدمات ثبت شده برای شما:</span>
                    <span class="font-mono font-bold text-slate-900"><?= number_format((int)$wallet['balance_settled_lifetime'] + (int)$wallet['balance_available_for_payout'] + (int)$wallet['balance_pending_escrow'] + $totalCommissionPaid) ?> تومان</span>
                </div>

                <div class="flex justify-between items-center p-3 rounded-xl bg-rose-50/70 border border-rose-100 text-rose-900">
                    <span class="font-bold">سهم کارمزد پلتفرم آسنا (۵٪):</span>
                    <span class="font-mono font-bold text-rose-700">-<?= number_format($totalCommissionPaid) ?> تومان</span>
                </div>

                <div class="flex justify-between items-center p-3 rounded-xl bg-amber-50/70 border border-amber-100 text-amber-900">
                    <span>هزینه بسته‌های پیامک خریداری شده از آسنا:</span>
                    <span class="font-mono font-bold text-amber-800">-<?= number_format($smsSpentTotal) ?> تومان</span>
                </div>

                <div class="flex justify-between items-center p-3 rounded-xl bg-blue-50/70 border border-blue-100 text-blue-900">
                    <span>وجوه در حال سپری کردن مهلت ۷ روزه تست (اسکرو):</span>
                    <span class="font-mono font-bold text-blue-800"><?= number_format((int)$wallet['balance_pending_escrow']) ?> تومان</span>
                </div>

                <div class="flex justify-between items-center p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                    <span class="font-bold text-sm">مبلغ خالص آماده برای حواله پایا این پنج‌شنبه ساعت ۹:۰۰:</span>
                    <span class="font-mono font-black text-lg text-emerald-700"><?= number_format((int)$wallet['balance_available_for_payout']) ?> تومان</span>
                </div>
            </div>

            <!-- Shaba Details -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">شماره شبای ثبت‌شده جهت واریز:</span>
                    <span class="font-mono font-bold text-slate-800 dir-ltr"><?= htmlspecialchars($wallet['bank_sheba'] ?: 'ثبت نشده') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">بانک عامل:</span>
                    <span class="font-bold text-slate-800"><?= htmlspecialchars($wallet['bank_name'] ?: 'بانک متصل شبا') ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions & SMS Topup (Col 5) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- SMS Quick Recharge -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h4 class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[#fd8100] text-base">forward_to_inbox</span>
                        شارژ پیامک اختصاصی آسنا
                    </h4>
                    <span class="text-xs font-mono font-bold text-[#fd8100] bg-amber-50 px-2 py-0.5 rounded-lg">
                        <?= (int)$wallet['sms_credits'] ?> پیامک موجود
                    </span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    برای ارسال پیامک‌های رهگیری سفارشات، یادآوری نوبت‌ها یا پیام به مشتریان، نیاز به شارژ اعتبار پیامک پلتفرم دارید.
                </p>
                <a href="?tab=sms" class="w-full bg-[#fd8100] hover:bg-[#ea580c] text-white py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                    <span>مشاهده و خرید بسته‌های پیامک</span>
                </a>
            </div>

            <!-- Dedicated Support Ticket Quick Launch -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h4 class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">support_agent</span>
                        مکاتبه با واحد مالی آسنا
                    </h4>
                    <span class="text-[10px] text-slate-400">پاسخگویی مستقیم</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    هرگونه مغایرت در تسویه، تغییر شماره شبا یا سوال در مورد کارمزد ۵٪ را مستقیماً از طریق تیکت اختصاصی مطرح نمایید.
                </p>
                <a href="?tab=tickets" class="w-full bg-[#001a48] hover:bg-[#002d72] text-white py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">chat</span>
                    <span>ثبت تیکت جدید با مدیریت آسنا</span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 2: DEBITS & 5% COMMISSION BREAKDOWN -->
    <?php if ($activeTab === 'debits'): ?>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600 text-base">point_of_sale</span>
                    ریز اقلام کارمزدهای کسر شده (سهم ۵٪ پلتفرم آسنا)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">کارمزد ۵٪ بابت خدمات بازاریابی، پشتیبانی، هاستینگ و زیرساخت پرداخت از مبالغ فروش کسر می‌گردد.</p>
            </div>
            <span class="font-bold text-xs text-rose-700 bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-100">
                مجموع کارمزدهای کسر شده: <?= number_format($totalCommissionPaid) ?> تومان
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-xs text-right">
                <thead class="bg-slate-50 text-slate-700 font-bold">
                    <tr>
                        <th class="p-3">نوع خدمت</th>
                        <th class="p-3">شناسه سفارش / نوبت</th>
                        <th class="p-3">تاریخ ثبت</th>
                        <th class="p-3 text-center">مبلغ ناخالص فروش</th>
                        <th class="p-3 text-center text-rose-600">کارمزد پلتفرم آسنا (۵٪)</th>
                        <th class="p-3 text-center text-emerald-700">سهم خالص شما (۹۵٪)</th>
                        <th class="p-3 text-center">وضعیت تسویه</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($ledgerItems as $l): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold text-[10px]">فروش کالا</span></td>
                        <td class="p-3 font-mono font-bold">#PC-<?= (int)$l['order_id'] ?></td>
                        <td class="p-3 text-slate-500"><?= htmlspecialchars(substr($l['order_date'] ?? $l['created_at'], 0, 16)) ?></td>
                        <td class="p-3 text-center font-mono"><?= number_format($l['gross_amount']) ?> ت</td>
                        <td class="p-3 text-center font-mono font-bold text-rose-600">-<?= number_format($l['commission_amount']) ?> ت</td>
                        <td class="p-3 text-center font-mono font-bold text-emerald-700"><?= number_format($l['net_seller_amount']) ?> ت</td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $l['status'] === 'settled_in_batch' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= $l['status'] === 'settled_in_batch' ? 'واریز شده در پایا' : ($l['status'] === 'released_to_available' ? 'آماده پنج‌شنبه' : 'اسکرو ۷ روزه') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php foreach ($appointmentItems as $apt): 
                        $fee = (int)$apt['fee'];
                        $comm = (int)($apt['commission_amount'] ?: round($fee * 0.05));
                        $net = (int)($apt['net_amount'] ?: ($fee - $comm));
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-3"><span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px]">ویزیت پزشک</span></td>
                        <td class="p-3 font-mono font-bold">#APT-<?= (int)$apt['id'] ?></td>
                        <td class="p-3 text-slate-500"><?= htmlspecialchars($apt['appointment_date']) ?> (ساعت <?= htmlspecialchars($apt['appointment_time']) ?>)</td>
                        <td class="p-3 text-center font-mono"><?= number_format($fee) ?> ت</td>
                        <td class="p-3 text-center font-mono font-bold text-rose-600">-<?= number_format($comm) ?> ت</td>
                        <td class="p-3 text-center font-mono font-bold text-emerald-700"><?= number_format($net) ?> ت</td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $apt['settlement_status'] === 'settled_in_batch' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $apt['settlement_status'] === 'settled_in_batch' ? 'واریز شده در پایا' : ($apt['settlement_status'] === 'available_for_payout' ? 'آماده پنج‌شنبه' : 'در انتظار ویزیت') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($ledgerItems) && empty($appointmentItems)): ?>
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400">هنوز سفارشی برای کسر کارمزد ثبت نگردیده است.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 3: PAYA PAYOUTS & OFFICIAL RECEIPTS -->
    <?php if ($activeTab === 'payouts'): ?>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-base">receipt_long</span>
                    تاریخچه حواله‌های پایا و رسیدهای رسمی هفتگی صادرشده
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">واریز مستقیم از حساب خزانه‌داری آسنا به شماره شبای شما، با کیوآرکد و امضای دیجیتال</p>
            </div>
            <span class="font-bold text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100">
                مجموع تسویه‌شده مادام‌العمر: <?= number_format((int)$wallet['balance_settled_lifetime']) ?> تومان
            </span>
        </div>

        <?php if (!empty($payoutBatches)): ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-xs text-right">
                <thead class="bg-slate-50 text-slate-700 font-bold">
                    <tr>
                        <th class="p-3">شناسه یکتای پایا</th>
                        <th class="p-3">تاریخ و ساعت پردازش</th>
                        <th class="p-3 text-center">مبلغ کل بسته</th>
                        <th class="p-3 text-center">وضعیت حواله</th>
                        <th class="p-3 text-center">عملیات و مدارک رسمی</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($payoutBatches as $b): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-mono font-bold text-primary"><?= htmlspecialchars($b['batch_code']) ?></td>
                        <td class="p-3 text-slate-600"><?= jdate('Y/m/d - H:i', strtotime($b['processed_at'] ?? $b['created_at'])) ?></td>
                        <td class="p-3 text-center font-mono font-bold text-emerald-700"><?= number_format($b['total_payout_amount']) ?> تومان</td>
                        <td class="p-3 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                ✔ تسویه موفق پایا
                            </span>
                        </td>
                        <td class="p-3 text-center flex items-center justify-center gap-2">
                            <a href="actions/generate_payout_receipt.php?batch_code=<?= urlencode($b['batch_code']) ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-[#001a48] hover:bg-[#002d72] text-white font-bold text-[11px] flex items-center gap-1 shadow-sm transition">
                                <span class="material-symbols-outlined text-sm">print</span>
                                <span>چاپ رسید رسمی با QR</span>
                            </a>
                            <a href="verify_payout.php?batch=<?= urlencode($b['batch_code']) ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] flex items-center gap-1 transition">
                                <span class="material-symbols-outlined text-sm">verified</span>
                                <span>استعلام بانکی</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-slate-400 space-y-2">
            <span class="material-symbols-outlined text-4xl text-slate-300">account_balance_wallet</span>
            <p class="text-xs">هنوز حواله پایا صادر نشده است. اولین حواله در سیکل پنج‌شنبه‌ها ساعت ۹:۰۰ صبح صادر خواهد شد.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- TAB 4: SMS PACKAGES & USAGE -->
    <?php if ($activeTab === 'sms'): ?>
    <div class="space-y-6">

        <!-- Enforcement & Live Sender Console -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl <?= (int)$wallet['sms_credits'] > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?> flex items-center justify-center">
                        <span class="material-symbols-outlined"><?= (int)$wallet['sms_credits'] > 0 ? 'mark_chat_read' : 'phonelink_erase' ?></span>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-900 text-sm">ارسال مستقیم پیامک به مراجعین یا مشتریان (تست محدودیت اعتبار)</h4>
                        <p class="text-xs text-slate-500 mt-0.5">ارسال پیامک با زیرساخت وب‌سرویس ملی‌پیامک آسنا؛ هر پیامک ۱ اعتبار از حساب شما کسر می‌کند.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-bold">وضعیت حساب:</span>
                    <?php if ((int)$wallet['sms_credits'] > 0): ?>
                        <span class="px-3 py-1 rounded-xl bg-emerald-100 text-emerald-800 font-black text-xs">
                            مجاز به ارسال (<?= number_format((int)$wallet['sms_credits']) ?> اعتبار)
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-xl bg-rose-100 text-rose-800 font-black text-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">block</span>
                            <span>مسدود شده (اعتبار صفر)</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ((int)$wallet['sms_credits'] <= 0): ?>
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                <span class="material-symbols-outlined text-amber-700 mt-0.5">warning</span>
                <div class="space-y-1">
                    <p class="font-bold">محدودیت قطعی ارسال پیامک فعال است:</p>
                    <p class="text-amber-800">
                        موجودی پیامک اختصاصی شما <strong>۰ عدد</strong> است. کلیه ارسال‌های خودکار و دستی به بیماران، مراجعین و خریداران مسدود گردیده است. جهت برطرف شدن محدودیت و امکان ارسال پیامک، لطفاً یکی از بسته‌های شارژ زیر را تهیه فرمایید.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="interactions.php?tab=sms" class="grid grid-cols-1 md:grid-cols-12 gap-3 pt-1">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send_direct_sms">

                <div class="md:col-span-4">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">شماره همراه گیرنده (مشتری/بیمار):</label>
                    <input type="text" name="recipient_phone" placeholder="0912..." dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-[#001a48] outline-none" required>
                </div>

                <div class="md:col-span-6">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">متن پیامک ارسالی:</label>
                    <input type="text" name="sms_message" placeholder="سلام، نوبت شما برای فردا ساعت ۱۰ رزرو است..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#001a48] outline-none" required>
                </div>

                <div class="md:col-span-2 flex items-end">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl <?= (int)$wallet['sms_credits'] > 0 ? 'bg-[#001a48] hover:bg-[#002d72] text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed' ?> font-bold text-xs shadow-sm transition flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-sm">send</span>
                        <span>ارسال پیامک</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Packages Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($smsPackages as $key => $p): ?>
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-5 hover:border-[#fd8100] transition">
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-[#fd8100] bg-amber-50 px-2.5 py-1 rounded-full"><?= $p['credits'] ?> پیامک</span>
                        <span class="material-symbols-outlined text-slate-400">sms</span>
                    </div>
                    <h4 class="font-black text-slate-900 text-base"><?= htmlspecialchars($p['name']) ?></h4>
                    <p class="text-xs text-slate-500 leading-relaxed"><?= htmlspecialchars($p['desc']) ?></p>
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-3">
                    <div class="text-xl font-black font-mono text-slate-900">
                        <?= number_format($p['price']) ?> <span class="text-xs text-slate-400 font-normal">تومان</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <!-- Option A: Pay from Wallet -->
                        <form method="POST" action="interactions.php" onsubmit="return confirm('آیا مایلید هزینه این بسته از موجودی کیف پول شما کسر گردد؟');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="buy_sms_wallet">
                            <input type="hidden" name="package_key" value="<?= $key ?>">
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold transition">
                                کسر از کیف‌پول
                            </button>
                        </form>

                        <!-- Option B: Pay Online Gateway -->
                        <form method="POST" action="interactions.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="buy_sms_gateway">
                            <input type="hidden" name="package_key" value="<?= $key ?>">
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-[#fd8100] hover:bg-[#ea580c] text-white text-[11px] font-bold shadow-sm transition">
                                پرداخت آنلاین
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent SMS Purchases & Usage Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Purchases -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                    <span class="material-symbols-outlined text-sm text-[#fd8100]">shopping_bag</span>
                    سوابق خرید بسته‌های پیامک
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-slate-400">
                            <tr>
                                <th class="pb-2">بسته</th>
                                <th class="pb-2">مبلغ</th>
                                <th class="pb-2">روش پرداخت</th>
                                <th class="pb-2">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($smsPurchases as $sp): ?>
                            <tr>
                                <td class="py-2 font-bold"><?= htmlspecialchars($sp['package_name']) ?></td>
                                <td class="py-2 font-mono"><?= number_format($sp['price']) ?> ت</td>
                                <td class="py-2 text-[10px] text-slate-500"><?= $sp['payment_method'] === 'wallet' ? 'کیف‌پول' : 'درگاه پرداخت' ?></td>
                                <td class="py-2 text-slate-400 text-[10px]"><?= jdate('Y/m/d', strtotime($sp['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($smsPurchases)): ?>
                            <tr><td colspan="4" class="py-4 text-center text-slate-400">تاکنون بسته‌ای خریداری نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usage Logs -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                    <span class="material-symbols-outlined text-sm text-primary">history</span>
                    گزارش پیامک‌های ارسالی به مشتریان
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-slate-400">
                            <tr>
                                <th class="pb-2">گیرنده</th>
                                <th class="pb-2">متن پیامک</th>
                                <th class="pb-2">تاریخ ارسال</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($smsLogs as $sl): ?>
                            <tr>
                                <td class="py-2 font-mono dir-ltr text-right"><?= htmlspecialchars($sl['recipient']) ?></td>
                                <td class="py-2 truncate max-w-[200px]" title="<?= htmlspecialchars($sl['message']) ?>"><?= htmlspecialchars($sl['message']) ?></td>
                                <td class="py-2 text-slate-400 text-[10px]"><?= jdate('Y/m/d - H:i', strtotime($sl['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($smsLogs)): ?>
                            <tr><td colspan="3" class="py-4 text-center text-slate-400">هیچ پیامکی ثبت نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB 5: SEPARATED TICKETS WITH ASENA MANAGEMENT -->
    <?php if ($activeTab === 'tickets'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- New Ticket Form (Col 5) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-sm text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span class="material-symbols-outlined text-primary text-base">edit_note</span>
                ارسال تیکت جدید به مدیریت و خزانه‌داری آسنا
            </h4>

            <form method="POST" action="interactions.php" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="new_ticket_with_asena">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دپارتمان مربوطه:</label>
                    <select name="department" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50 outline-none">
                        <option value="امور مالی و تسویه پایا">امور مالی و تسویه پایا پنج‌شنبه‌ها</option>
                        <option value="شارژ پنل پیامک و فاکتور">شارژ پنل پیامک و فاکتور</option>
                        <option value="استعلام کارمزد و کاتالوگ">استعلام کارمزد ۵٪ و مغایرت سفارش</option>
                        <option value="پشتیبانی فنی پلتفرم">پشتیبانی فنی و دسترسی</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">موضوع تیکت:</label>
                    <input type="text" name="subject" required placeholder="عنوان درخواست..." class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">شرح درخواست یا پیام:</label>
                    <textarea name="message" required rows="4" placeholder="متن پیام خود را با جزئیات بنویسید..." class="w-full p-3 rounded-xl border border-slate-200 text-xs bg-slate-50 outline-none leading-relaxed"></textarea>
                </div>

                <button type="submit" class="w-full bg-[#001a48] hover:bg-[#002d72] text-white py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                    <span class="material-symbols-outlined text-sm">send</span>
                    <span>ارسال تیکت به مدیریت آسنا</span>
                </button>
            </form>
        </div>

        <!-- Tickets List (Col 7) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-sm text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <span class="material-symbols-outlined text-primary text-base">forum</span>
                مکاتبات و تیکت‌های تفکیک‌شده شما با مدیریت پلتفرم آسنا
            </h4>

            <?php if (!empty($myTickets)): ?>
            <div class="space-y-3">
                <?php foreach ($myTickets as $t): ?>
                <div class="p-4 rounded-2xl border border-slate-100 hover:border-slate-300 transition bg-slate-50/60 flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-xs text-slate-900">تیکت #<?= $t['id'] ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $t['status'] === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' ?>">
                                <?= $t['status'] === 'open' ? 'درحال پیگیری' : 'بسته شده' ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 truncate max-w-sm"><?= htmlspecialchars($t['last_message']) ?></p>
                        <span class="text-[10px] text-slate-400 block"><?= jdate('Y/m/d - H:i', strtotime($t['last_time'] ?? $t['created_at'])) ?></span>
                    </div>

                    <a href="chat.php?ticket_id=<?= $t['id'] ?>" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-primary hover:text-white text-primary text-xs font-bold shadow-sm transition shrink-0 flex items-center gap-1">
                        <span>مشاهده و چت</span>
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="p-8 text-center text-slate-400 space-y-2">
                <span class="material-symbols-outlined text-4xl text-slate-300">chat</span>
                <p class="text-xs">تاکنون تیکتی با مدیریت آسنا ثبت نکرده‌اید.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
