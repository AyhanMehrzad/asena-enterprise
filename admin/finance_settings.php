<?php
$currentPage = 'finance_settings';
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$escrowService = App::escrow();

$success = '';
$error = '';
$testRunOutput = null;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    SecurityMiddleware::validateCsrfToken($_POST['csrf_token'] ?? '');
    $action = $_POST['action'];

    if ($action === 'save_finance_settings') {
        $cardRaw = preg_replace('/[^\d]/', '', $_POST['admin_bank_card'] ?? '');
        $shebaRaw = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['admin_bank_sheba'] ?? ''));
        $bankName = trim($_POST['admin_bank_name'] ?? 'بانک سامان');
        $holderName = trim($_POST['admin_bank_holder'] ?? 'شرکت توسعه تجارت الکترونیک آسنا');
        
        $taxRate = max(0, min(100, (float)($_POST['tax_rate_percent'] ?? 10)));
        $commissionRate = max(0, min(100, (float)($_POST['platform_commission_percent'] ?? 15)));
        $taxOnAppts = isset($_POST['tax_on_appointments_enabled']) ? '1' : '0';

        $autoPayoutEnabled = isset($_POST['auto_payout_enabled']) ? '1' : '0';
        $autoPayoutDay = (int)($_POST['auto_payout_day'] ?? 4); // 4 = Thursday
        $autoPayoutTime = trim($_POST['auto_payout_time'] ?? '09:00');

        // Validation
        if (!empty($cardRaw) && strlen($cardRaw) !== 16) {
            $error = "شماره کارت بانکی باید دقیقاً ۱۶ رقم باشد.";
        } elseif (!empty($shebaRaw) && !preg_match('/^IR\d{24}$/', $shebaRaw)) {
            $error = "شماره شبا باید با حروف IR و ۲۴ رقم عدد معتبر باشد.";
        } else {
            set_setting($pdo, 'admin_bank_card', $cardRaw);
            set_setting($pdo, 'admin_bank_sheba', $shebaRaw);
            set_setting($pdo, 'admin_bank_name', $bankName);
            set_setting($pdo, 'admin_bank_holder', $holderName);

            set_setting($pdo, 'tax_rate_percent', $taxRate);
            set_setting($pdo, 'platform_commission_percent', $commissionRate);
            set_setting($pdo, 'tax_on_appointments_enabled', $taxOnAppts);

            set_setting($pdo, 'auto_payout_enabled', $autoPayoutEnabled);
            set_setting($pdo, 'auto_payout_day', $autoPayoutDay);
            set_setting($pdo, 'auto_payout_time', $autoPayoutTime);

            // Payment Gateway & Zero-Tax Card Engine Settings
            $activeGateway = trim($_POST['active_payment_gateway'] ?? 'zarinpal');
            set_setting($pdo, 'active_payment_gateway', $activeGateway);

            $cardGatewayNum = preg_replace('/[^\d]/', '', $_POST['card_gateway_number'] ?? '');
            if (!empty($cardGatewayNum)) set_setting($pdo, 'card_gateway_number', $cardGatewayNum);

            $cardGatewayHolder = trim($_POST['card_gateway_holder'] ?? '');
            if (!empty($cardGatewayHolder)) set_setting($pdo, 'card_gateway_holder', $cardGatewayHolder);

            $cardGatewayBank = trim($_POST['card_gateway_bank'] ?? '');
            if (!empty($cardGatewayBank)) set_setting($pdo, 'card_gateway_bank', $cardGatewayBank);

            $cardGatewayShaba = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['card_gateway_shaba'] ?? ''));
            if (!empty($cardGatewayShaba)) set_setting($pdo, 'card_gateway_shaba', $cardGatewayShaba);

            $cardAutoThreshold = max(0, (int)($_POST['card_auto_verify_threshold'] ?? 0));
            set_setting($pdo, 'card_auto_verify_threshold', $cardAutoThreshold);

            $cryptoWallet = trim($_POST['crypto_usdt_trc20_wallet'] ?? '');
            if (!empty($cryptoWallet)) set_setting($pdo, 'crypto_usdt_trc20_wallet', $cryptoWallet);

            $cryptoRate = max(1000, (int)($_POST['crypto_usdt_toman_rate'] ?? 65000));
            set_setting($pdo, 'crypto_usdt_toman_rate', $cryptoRate);

            $enamadCode = trim($_POST['enamad_html_code'] ?? '');
            set_setting($pdo, 'enamad_html_code', $enamadCode);

            // Clinical Calculator Free vs Paid Mode
            $calcMode = trim($_POST['calculator_mode'] ?? '');
            if ($calcMode === 'free') {
                $calculatorIsPaid = '0';
            } elseif ($calcMode === 'paid') {
                $calculatorIsPaid = '1';
            } else {
                $calculatorIsPaid = (isset($_POST['calculator_is_paid']) && $_POST['calculator_is_paid'] === '1') ? '1' : '0';
            }

            $calculatorPrice = max(0, (int)($_POST['calculator_price_toman'] ?? 49000));

            set_setting($pdo, 'calculator_is_paid', $calculatorIsPaid);
            set_setting($pdo, 'calculator_price_toman', $calculatorPrice);

            // Logistics, Shipping Costs & Free Shipping Threshold
            $freeShippingEnabled = isset($_POST['free_shipping_enabled']) ? '1' : '0';
            $freeShippingThreshold = max(0, (int)($_POST['free_shipping_threshold_toman'] ?? 600000));
            $standardShippingCost = max(0, (int)($_POST['standard_shipping_cost_toman'] ?? 49000));
            $supportPhone = trim($_POST['support_phone_fixed'] ?? '02191000000');

            set_setting($pdo, 'free_shipping_enabled', $freeShippingEnabled);
            set_setting($pdo, 'free_shipping_threshold_toman', $freeShippingThreshold);
            set_setting($pdo, 'standard_shipping_cost_toman', $standardShippingCost);
            set_setting($pdo, 'support_phone_fixed', $supportPhone);

            $success = "تنظیمات خزانه‌داری، امور مالی، وضعیت محاسبه‌گر، درگاه پرداخت و لجستیک با موفقیت ذخیره شد.";
        }
    } elseif ($action === 'approve_receipt') {
        $subId = (int)$_POST['submission_id'];
        require_once __DIR__ . '/../includes/PaymentService.php';
        $paymentService = new PaymentService($pdo);
        $res = $paymentService->approveCardReceipt($subId, (int)$_SESSION['user_id'], 'تأیید دستی مدیریت سامانه');
        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['message'];
        }
    } elseif ($action === 'reject_receipt') {
        $subId = (int)$_POST['submission_id'];
        $reason = trim($_POST['rejection_reason'] ?? 'اطلاعات واریزی همخوانی ندارد');
        $pdo->prepare("UPDATE card_receipt_submissions SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$reason, (int)$_SESSION['user_id'], $subId]);
        $success = "رسید واریز رد شد.";
    } elseif ($action === 'force_test_payout') {
        $res = $escrowService->checkAndExecuteScheduledWeeklyPayout(true, 'manual_admin');
        if ($res['executed']) {
            $b = $res['batch_res'];
            $success = "چرخه تسویه با موفقیت تست و صادر گردید! شناسه پایا: {$b['batch_code']} | مبلغ کل: " . number_format($b['total_amount']) . " تومان برای {$b['seller_count']} ذینفع.";
        } else {
            $error = $res['reason'] ?? 'خطا در اجرای آزمایشی چرخه تسویه.';
        }
    }
}

// Current Settings
$adminCard      = get_setting($pdo, 'admin_bank_card', '6037991199223344');
$adminSheba     = get_setting($pdo, 'admin_bank_sheba', 'IR120560000000100000000001');
$adminBank      = get_setting($pdo, 'admin_bank_name', 'بانک سامان');
$adminHolder    = get_setting($pdo, 'admin_bank_holder', 'شرکت توسعه تجارت الکترونیک آسنا');

$activeGateway     = get_setting($pdo, 'active_payment_gateway', 'zarinpal');
$cardGatewayNum    = get_setting($pdo, 'card_gateway_number', '6037997512345678');
$cardGatewayHolder = get_setting($pdo, 'card_gateway_holder', 'آسنا — حساب متمرکز امانی');
$cardGatewayBank   = get_setting($pdo, 'card_gateway_bank', 'بانک ملی ایران');
$cardGatewayShaba  = get_setting($pdo, 'card_gateway_shaba', 'IR120170000000123456789012');
$cardAutoThreshold = (int)get_setting($pdo, 'card_auto_verify_threshold', 0);
$cryptoWallet      = get_setting($pdo, 'crypto_usdt_trc20_wallet', 'TYDskj3920sdfkJSHdf98234JHskfjh2');
$cryptoRate        = (int)get_setting($pdo, 'crypto_usdt_toman_rate', 65000);

$taxRate        = (float)get_setting($pdo, 'tax_rate_percent', 10.0);
$commissionRate = (float)get_setting($pdo, 'platform_commission_percent', 15);
$taxOnAppts     = get_setting($pdo, 'tax_on_appointments_enabled', '1');

$autoPayoutEnabled = get_setting($pdo, 'auto_payout_enabled', '1');
$autoPayoutDay     = (int)get_setting($pdo, 'auto_payout_day', 4);
$autoPayoutTime    = get_setting($pdo, 'auto_payout_time', '09:00');

$calculatorIsPaid = (int)get_setting($pdo, 'calculator_is_paid', 0);
$calculatorPrice  = (int)get_setting($pdo, 'calculator_price_toman', 49000);

$freeShippingEnabled   = (int)get_setting($pdo, 'free_shipping_enabled', 1);
$freeShippingThreshold = (int)get_setting($pdo, 'free_shipping_threshold_toman', 600000);
$standardShippingCost  = (int)get_setting($pdo, 'standard_shipping_cost_toman', 49000);
$supportPhone          = get_setting($pdo, 'support_phone_fixed', '02191000000');

// Pending Card Receipts Queue
$pendingSubmissions = [];
try {
    $pendingSubmissionsStmt = $pdo->query("
        SELECT s.*, t.authority_or_ref, u.name as user_name, u.phone as user_phone 
        FROM card_receipt_submissions s
        JOIN payment_transactions t ON s.payment_transaction_id = t.id
        JOIN users u ON s.user_id = u.id
        WHERE s.status = 'pending'
        ORDER BY s.id DESC
    ");
    if ($pendingSubmissionsStmt) {
        $pendingSubmissions = $pendingSubmissionsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log("finance_settings card_receipt_submissions error: " . $e->getMessage());
    $pendingSubmissions = [];
}

$defaultEnamadCode = "<a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=7706608&Code=qBmonKZeAe36PvBvs1zpTGrrRb7uFJs8'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=7706608&Code=qBmonKZeAe36PvBvs1zpTGrrRb7uFJs8' alt='' style='cursor:pointer' code='qBmonKZeAe36PvBvs1zpTGrrRb7uFJs8'></a>";
$enamadCode        = get_setting($pdo, 'enamad_html_code', $defaultEnamadCode);
if (empty(trim((string)$enamadCode))) {
    $enamadCode = $defaultEnamadCode;
}

// Iranian Bank Card Prefix Detection
function detectBankName(string $card): string {
    $card = preg_replace('/[^\d]/', '', $card);
    $bin = substr($card, 0, 6);
    $map = [
        '603799' => 'بانک ملی ایران',
        '610433' => 'بانک ملت',
        '621986' => 'بانک سامان',
        '502229' => 'بانک پاسارگاد',
        '627412' => 'بانک اقتصاد نوین',
        '627381' => 'بانک انصار / سپه',
        '589210' => 'بانک سپه',
        '627760' => 'پست بانک ایران',
        '628023' => 'بانک مسکن',
        '502908' => 'بانک توسعه تعاون',
        '627353' => 'بانک تجارت',
        '603769' => 'بانک صادرات ایران',
        '639346' => 'بانک سینا',
        '639607' => 'بانک سرمایه',
        '636214' => 'بانک آینده',
        '505785' => 'بانک ایران زمین',
        '505416' => 'بانک گردشگری',
        '639599' => 'بانک قوامین',
        '504706' => 'بانک شهر',
        '505801' => 'بانک کوثر',
        '606373' => 'بانک قرض‌الحسنه مهر ایران',
        '504172' => 'بانک رسالت',
        '621986' => 'بلوبانک (سامان)'
    ];
    return $map[$bin] ?? 'شبکه بانکی شتاب';
}

$detectedBank = detectBankName($adminCard);

// Escrow Stats
$metrics = $escrowService->getEscrowMetrics();

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-8 rtl">

    <!-- Header & Quick Action Bar -->
    <div class="bg-white dark:bg-[#1E293B] p-6 lg:p-8 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#001a48] text-white flex items-center justify-center font-black shadow-md shadow-blue-950/20 shrink-0">
                <span class="material-symbols-outlined text-3xl">account_balance</span>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">کنسول مدیریت خزانه‌داری، مالیات و محاسبه‌گر</h1>
                    <span class="px-3 py-1 rounded-full text-xs font-bold font-mono <?= $calculatorIsPaid ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300' ?>">
                        محاسبه‌گر: <?= $calculatorIsPaid ? '🔴 پولی' : '🟢 رایگان' ?>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    تنظیم کلید رایگان/پولی محاسبه‌گر تغذیه بالینی پت، شماره حساب و کارت شرکت، نرخ مالیات ارزش افزوده، کارمزد و تسویه پایا
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="payouts.php" class="px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-2 transition">
                <span class="material-symbols-outlined text-base">account_balance_wallet</span>
                <span>کنسول تسویه پایا</span>
            </a>
            <button type="button" onclick="document.getElementById('mainFinanceForm').submit()" class="px-6 py-3 rounded-2xl bg-[#001a48] hover:bg-[#002d72] text-white font-bold text-xs flex items-center gap-2 transition shadow-md shadow-blue-950/20 cursor-pointer">
                <span class="material-symbols-outlined text-base">save</span>
                <span>ذخیره تنظیمات</span>
            </button>
        </div>
    </div>

    <!-- Quick Navigation Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 text-xs font-bold text-slate-600 dark:text-slate-300 no-scrollbar">
        <a href="#calculator-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-emerald-500 hover:text-emerald-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-emerald-600 text-sm">calculate</span>
            <span>۱. وضعیت محاسبه‌گر (رایگان/پولی)</span>
        </a>
        <a href="#bank-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-blue-500 hover:text-blue-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-blue-600 text-sm">credit_card</span>
            <span>۲. کارت و حساب بانکی</span>
        </a>
        <a href="#gateway-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-indigo-500 hover:text-indigo-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-indigo-600 text-sm">payments</span>
            <span>۳. درگاه پرداخت شاپرک</span>
        </a>
        <a href="#tax-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-amber-500 hover:text-amber-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-amber-600 text-sm">percent</span>
            <span>۴. مالیات و کارمزد</span>
        </a>
        <a href="#shipping-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-cyan-500 hover:text-cyan-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-cyan-600 text-sm">local_shipping</span>
            <span>۵. لجستیک و ارسال رایگان</span>
        </a>
        <a href="#payout-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-emerald-500 hover:text-emerald-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-emerald-600 text-sm">schedule</span>
            <span>۶. تسویه خودکار پایا</span>
        </a>
        <a href="#enamad-section" class="px-4 py-2 rounded-xl bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 hover:border-purple-500 hover:text-purple-600 transition flex items-center gap-1.5 shrink-0 shadow-2xs">
            <span class="material-symbols-outlined text-purple-600 text-sm">verified</span>
            <span>۷. نماد اعتماد اینماد</span>
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($success): ?>
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 p-5 rounded-3xl flex items-center gap-3 font-bold text-xs shadow-sm">
            <span class="material-symbols-outlined text-emerald-600 text-xl">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200 p-5 rounded-3xl flex items-center gap-3 font-bold text-xs shadow-sm">
            <span class="material-symbols-outlined text-rose-600 text-xl">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- High-Level Financial KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-[#1E293B] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">تسویه پایا این پنج‌شنبه:</span>
                <strong class="text-lg font-black text-slate-900 dark:text-white font-mono"><?= number_format($metrics['total_available_payout']) ?> <span class="text-xs font-normal text-slate-500">تومان</span></strong>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block mt-0.5"><?= $metrics['eligible_sellers_count'] ?> ذینفع واجد شرایط</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">shield</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">امانی مهلت ۷ روزه تست:</span>
                <strong class="text-lg font-black text-slate-900 dark:text-white font-mono"><?= number_format($metrics['total_pending_escrow']) ?> <span class="text-xs font-normal text-slate-500">تومان</span></strong>
                <span class="text-[10px] text-blue-600 dark:text-blue-400 block mt-0.5"><?= $metrics['active_in_inspection_count'] ?> مرسوله در جریان</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl <?= $calculatorIsPaid ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60' ?> flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">calculate</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">وضعیت محاسبه‌گر تغذیه:</span>
                <strong class="text-base font-black <?= $calculatorIsPaid ? 'text-rose-600' : 'text-emerald-600' ?> block">
                    <?= $calculatorIsPaid ? '🔴 پولی (مستلزم پرداخت)' : '🟢 کاملاً رایگان (Free)' ?>
                </strong>
                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $calculatorIsPaid ? number_format($calculatorPrice) . ' تومان' : '۱۰۰٪ آزاد و بدون هزینه' ?></span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">credit_card</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">بانک و درگاه رسمی:</span>
                <strong class="text-sm font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($detectedBank) ?></strong>
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-mono block mt-0.5"><?= $activeGateway === 'zarinpal' ? 'زرین‌پال شاپرک' : 'شبیه‌ساز تستی' ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN SETTINGS FORM -->
    <form method="POST" action="finance_settings.php" id="mainFinanceForm" class="space-y-8">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_finance_settings">

        <!-- ========================================================================= -->
        <!-- SECTION 1: CLINICAL CALCULATOR FREE VS PAID MONETIZATION (HERO COMPONENT) -->
        <!-- ========================================================================= -->
        <div id="calculator-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border-2 <?= $calculatorIsPaid ? 'border-rose-300 dark:border-rose-900' : 'border-emerald-300 dark:border-emerald-800' ?> rounded-3xl p-6 sm:p-8 shadow-sm transition-all relative overflow-hidden">
            
            <!-- Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5 mb-6">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center font-black">
                        <span class="material-symbols-outlined text-2xl">calculate</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>۱. تنظیم کلید وضعیت محاسبه‌گر بالینی پت (رایگان یا پولی)</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            انتخاب کنید که محاسبه کالری و صدور کارنامه بالینی رژیم غذایی برای کاربران <strong>کاملاً رایگان (Free)</strong> باشد یا <strong>مستلزم پرداخت وجه (Paid)</strong>.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span id="calcModeStatusBadge" class="px-4 py-1.5 rounded-full text-xs font-black font-mono <?= $calculatorIsPaid ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' ?>">
                        <?= $calculatorIsPaid ? '🔴 حالت فعلی: پولی (Paid)' : '🟢 حالت فعلی: کاملاً رایگان (Free)' ?>
                    </span>
                </div>
            </div>

            <!-- Free vs Paid Big Interactive Option Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                
                <!-- CARD A: 100% FREE MODE -->
                <label id="cardCalcFree" onclick="setCalculatorMode('free')" class="p-6 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-4 relative <?= !$calculatorIsPaid ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20 shadow-sm ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 opacity-80 hover:opacity-100' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="calculator_mode" id="calcModeRadioFree" value="free" <?= !$calculatorIsPaid ? 'checked' : '' ?> onchange="setCalculatorMode('free')" class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <div>
                                <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white block">🟢 کاملاً رایگان (Free Mode)</span>
                                <span class="text-[11px] text-emerald-700 dark:text-emerald-400 font-bold block mt-0.5">پیشنهادی — حداکثر جذب کاربر و ثبت پرونده</span>
                            </div>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-xl">card_giftcard</span>
                        </span>
                    </div>

                    <div class="text-xs text-slate-600 dark:text-slate-300 space-y-1.5 leading-relaxed pt-2 border-t border-emerald-200/50 dark:border-emerald-900/40">
                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-bold">
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            <span>بدون نیاز به ورود به درگاه یا پرداخت حتی ۱ ریال</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            کاربران با وارد کردن وزن، گونه و شرایط پت، جدول استاندارد رژیم غذایی را محاسبه کرده و با کلیک روی دکمه سبز رنگ <strong>«صدور و ذخیره رایگان در پرونده سلامت»</strong>، فایل را مستقیماً در پرونده ذخیره می‌کنند.
                        </p>
                    </div>
                </label>

                <!-- CARD B: PAID MODE -->
                <label id="cardCalcPaid" onclick="setCalculatorMode('paid')" class="p-6 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-4 relative <?= $calculatorIsPaid ? 'border-rose-500 bg-rose-50/60 dark:bg-rose-950/20 shadow-sm ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 opacity-80 hover:opacity-100' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="calculator_mode" id="calcModeRadioPaid" value="paid" <?= $calculatorIsPaid ? 'checked' : '' ?> onchange="setCalculatorMode('paid')" class="w-5 h-5 text-rose-600 focus:ring-rose-500 cursor-pointer">
                            <div>
                                <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white block">🔴 پولی (Paid Mode)</span>
                                <span class="text-[11px] text-rose-700 dark:text-rose-400 font-bold block mt-0.5">دریافت هزینه خدمات پیش از صدور کارنامه</span>
                            </div>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-xl">payments</span>
                        </span>
                    </div>

                    <div class="text-xs text-slate-600 dark:text-slate-300 space-y-1.5 leading-relaxed pt-2 border-t border-rose-200/50 dark:border-rose-900/40">
                        <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-bold">
                            <span class="material-symbols-outlined text-sm">lock</span>
                            <span>صدور کارنامه پس از پرداخت در درگاه</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            کاربر برای ثبت نهایی و صدور فایل کامل شناسنامه بالینی تغذیه، به درگاه پرداخت شاپرک متصل شده و پس از تکمیل واریز، دسترسی صادر می‌گردد.
                        </p>
                    </div>
                </label>

            </div>

            <!-- Hidden Legacy Sync Input for 100% Backward Compatibility with #calcPaidToggle -->
            <input type="checkbox" name="calculator_is_paid" id="calcPaidToggle" value="1" <?= $calculatorIsPaid ? 'checked' : '' ?> class="hidden">

            <!-- Sub-settings: Paid Configuration Panel (Conditionally visible when Paid mode is selected) -->
            <div id="calcPaidDetailsPanel" class="<?= $calculatorIsPaid ? 'block' : 'hidden' ?> p-6 rounded-2xl bg-gradient-to-r from-rose-50/50 via-slate-50 to-amber-50/40 dark:from-slate-900 dark:to-slate-800/80 border border-rose-200 dark:border-slate-700 space-y-4">
                <div class="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-black text-sm border-b border-rose-200/60 dark:border-slate-700 pb-3">
                    <span class="material-symbols-outlined text-base">tune</span>
                    <span>تنظیم مبلغ خدمات محاسبه‌گر بالینی:</span>
                </div>

                <div class="max-w-md">
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">مبلغ کارنامه تغذیه بالینی (تومان):</label>
                    <div class="relative">
                        <input type="number" name="calculator_price_toman" id="calcPriceInput" value="<?= $calculatorPrice ?>" min="0" step="1000" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold focus:border-rose-500 outline-none pl-14 text-left dir-ltr">
                        <span class="absolute left-3 top-3 text-slate-400 text-xs font-bold">تومان</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        مبلغی که روی دکمه صدور در صفحه محاسبه‌گر درج می‌شود و کاربر پیش از صدور و ثبت در پرونده، از طریق درگاه پرداخت آنلاین شاپرک پرداخت خواهد نمود (پیش‌فرض: ۴۹,۰۰۰ تومان).
                    </p>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 2: CENTRAL TREASURY BANK CARD & SHABA (2-COLUMN SPACIOUS LAYOUT)  -->
        <!-- ========================================================================= -->
        <div id="bank-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[#001a48] dark:text-blue-400 text-2xl">credit_card</span>
                    <span>۲. اطلاعات حساب بانکی و کارت متمرکز حقوقی شرکت</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">مشخصات شماره کارت و شبای خزانه‌داری آسنا جهت درج در رسیدهای واریز و حواله‌های پایا</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Visual Card (Col 5) -->
                <div class="lg:col-span-5">
                    <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#001a48] via-[#002d72] to-[#1e3a8a] text-white shadow-xl relative overflow-hidden h-[240px] flex flex-col justify-between border border-white/10">
                        <div class="absolute -left-10 -bottom-10 opacity-10 pointer-events-none">
                            <span class="material-symbols-outlined text-[180px]">pets</span>
                        </div>

                        <div class="flex justify-between items-start z-10">
                            <div class="flex items-center gap-2.5">
                                <img src="../assets/images/logo.png" alt="آسنا" class="w-8 h-8 object-contain bg-white/10 rounded-lg p-1">
                                <span class="font-black text-sm tracking-wide">آسنا تجارت (حساب حقوقی)</span>
                            </div>
                            <span id="previewBankBadge" class="bg-white/15 px-3 py-1 rounded-full text-[11px] font-bold backdrop-blur-sm">
                                <?= htmlspecialchars($detectedBank) ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-3 z-10">
                            <div class="w-10 h-8 rounded-md bg-gradient-to-r from-amber-300 to-amber-500 border border-amber-200 shadow-inner flex items-center justify-center">
                                <span class="material-symbols-outlined text-amber-900 text-sm">memory</span>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-xl">contactless</span>
                        </div>

                        <div class="z-10 dir-ltr text-center">
                            <span id="previewCardNumber" class="font-mono text-xl sm:text-2xl font-bold tracking-[0.2em] text-white drop-shadow">
                                <?= chunk_split(preg_replace('/[^\d]/', '', $adminCard), 4, ' ') ?>
                            </span>
                        </div>

                        <div class="flex justify-between items-end z-10 text-[11px]">
                            <div>
                                <span class="text-white/60 block text-[9px]">نام صاحب کارت:</span>
                                <span id="previewCardHolder" class="font-bold text-white"><?= htmlspecialchars($adminHolder) ?></span>
                            </div>
                            <div class="text-left dir-ltr font-mono text-[10px] text-white/80">
                                <?= htmlspecialchars($adminSheba) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inputs Grid (Col 7) -->
                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">شماره کارت بانکی آسنا (۱۶ رقم):</label>
                        <input type="text" name="admin_bank_card" id="adminBankCardInput" value="<?= htmlspecialchars($adminCard) ?>" maxlength="19" placeholder="6037-9911-XXXX-XXXX" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-center">
                        <p class="text-[10px] text-slate-400 mt-1">شماره کارت متمرکز حقوقی جهت صدور پیش‌فاکتور و واریز</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نام بانک عامل:</label>
                        <input type="text" name="admin_bank_name" id="adminBankNameInput" value="<?= htmlspecialchars($adminBank) ?>" placeholder="بانک سامان" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                        <p class="text-[10px] text-slate-400 mt-1">بانک متصل به شماره کارت فوق</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">شماره شبا متمرکز خزانه‌داری (IBAN):</label>
                        <input type="text" name="admin_bank_sheba" value="<?= htmlspecialchars($adminSheba) ?>" maxlength="26" placeholder="IR120560..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-left">
                        <p class="text-[10px] text-slate-400 mt-1">شناسه شبای ۲۴ رقمی مبدأ برای تسویه‌های هفتگی پایا</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نام کامل صاحب حساب حقوقی:</label>
                        <input type="text" name="admin_bank_holder" id="adminBankHolderInput" value="<?= htmlspecialchars($adminHolder) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                        <p class="text-[10px] text-slate-400 mt-1">عنوان کامل ثبت‌شده در روزنامه رسمی</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 3: OFFICIAL PAYMENT GATEWAY (SHAPARAK / ZARINPAL / SANDBOX)       -->
        <!-- ========================================================================= -->
        <div id="gateway-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">payments</span>
                    <span>۳. درگاه پرداخت الکترونیک رسمی شاپرک</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">اتصال به درگاه‌های پرداخت اینترنتی شاپرک (IPG) و تنظیم حالت آزمایشگاهی</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="p-5 rounded-2xl border-2 cursor-pointer transition-all flex items-start gap-4 <?= $activeGateway === 'zarinpal' ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/20 shadow-sm' : 'border-slate-200 dark:border-slate-700 opacity-70 hover:opacity-100' ?>">
                    <input type="radio" name="active_payment_gateway" value="zarinpal" <?= $activeGateway === 'zarinpal' ? 'checked' : '' ?> class="mt-1 text-blue-600 focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-black text-slate-900 dark:text-white block">درگاه رسمی اینترنتی شاپرک (زرین‌پال / زیبال)</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">اتصال به شبکه شاپرک با ارجاع مستقیم به صفحه پرداخت الکترونیک بانکی با نماد اعتماد.</p>
                        <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">درگاه پیش‌فرض فعال</span>
                    </div>
                </label>

                <label class="p-5 rounded-2xl border-2 cursor-pointer transition-all flex items-start gap-4 <?= $activeGateway === 'mock' ? 'border-slate-700 bg-slate-100 dark:bg-slate-800 shadow-sm' : 'border-slate-200 dark:border-slate-700 opacity-70 hover:opacity-100' ?>">
                    <input type="radio" name="active_payment_gateway" value="mock" <?= $activeGateway === 'mock' ? 'checked' : '' ?> class="mt-1 text-slate-600 focus:ring-slate-500">
                    <div>
                        <span class="text-sm font-black text-slate-900 dark:text-white block">شبیه‌ساز تستی (Sandbox Simulator)</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">آزمایش بدون کسر واقعی موجودی از کارت بانکی برای تست جریان‌های سفارش و ثبت فاکتور.</p>
                        <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">محیط توسعه و تست</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 4: TAXES & PLATFORM COMMISSION (VAT + COMMISSION)                 -->
        <!-- ========================================================================= -->
        <div id="tax-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[#fd8100] text-2xl">percent</span>
                    <span>۴. محاسبه مالیات بر ارزش افزوده و سهم کارمزد پلتفرم</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">اعمال قوانین سازمان امور مالیاتی کشور و تقسیم سهم بازارگاه بین خریدار و فروشنده</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نرخ مالیات بر ارزش افزوده برای خریدار (درصد):</label>
                    <div class="relative">
                        <input type="number" name="tax_rate_percent" value="<?= $taxRate ?>" step="0.5" min="0" max="25" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none pl-10 text-left dir-ltr">
                        <span class="absolute left-3 top-3 text-slate-400 text-xs font-bold">٪</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">نرخ مصوب قانونی مالیات بر ارزش افزوده در سال ۱۴۰۳ (۱۰٪)؛ در سبد خرید محاسبه و در فاکتور رسمی درج می‌شود.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">کارمزد پلتفرم آسنا از فروشنده / پت‌شاپ (درصد):</label>
                    <div class="relative">
                        <input type="number" name="platform_commission_percent" value="<?= $commissionRate ?>" step="0.5" min="0" max="50" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none pl-10 text-left dir-ltr">
                        <span class="absolute left-3 top-3 text-slate-400 text-xs font-bold">٪</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">سهم درآمد شرکت از هر سفارش تامین‌کننده (پیش‌فرض ۱۵٪؛ هنگام تسویه پایا از موجودی کسر می‌گردد).</p>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex items-start gap-3.5">
                <input type="checkbox" name="tax_on_appointments_enabled" id="taxAppts" value="1" <?= $taxOnAppts === '1' ? 'checked' : '' ?> class="mt-1 w-5 h-5 rounded text-primary focus:ring-primary cursor-pointer">
                <div>
                    <label for="taxAppts" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                        اعمال مالیات ارزش افزوده روی نوبت‌های ویزیت دامپزشکی
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        نکته حقوقی: بر اساس بند ۹ ماده ۹ قانون مالیات بر ارزش افزوده، خدمات سلامت و درمان دارای معافیت قانونی هستند. در صورت تیک زدن این گزینه، مالیات به هزینه ویزیت بیمار افزوده خواهد شد.
                    </p>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 5: LOGISTICS, SHIPPING RATES & FREE SHIPPING THRESHOLD            -->
        <!-- ========================================================================= -->
        <div id="shipping-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-cyan-600 text-2xl">local_shipping</span>
                    <span>۵. پیکربندی انبارداری، نرخ کرایه و سقف ارسال رایگان مرسولات</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">تنظیم شرط ارسال رایگان خرید بالای سقف معین و شماره تلفن ثابت استعلامات پستی</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Free Shipping Toggle & Threshold -->
                <div class="p-6 rounded-2xl bg-cyan-50/40 dark:bg-slate-900/60 border border-cyan-200 dark:border-slate-700 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">فعال‌سازی سقف ارسال رایگان:</span>
                            <span class="text-[10px] text-slate-500">برای خریدهای بالاتر از مبلغ معین</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="free_shipping_enabled" value="1" <?= $freeShippingEnabled ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-12 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:start-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">حداقل مبلغ سبد برای ارسال رایگان (تومان):</label>
                        <div class="relative">
                            <input type="number" name="free_shipping_threshold_toman" value="<?= $freeShippingThreshold ?>" min="0" step="10000" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold focus:border-cyan-500 outline-none pl-12 text-left dir-ltr">
                            <span class="absolute left-3 top-3 text-slate-400 text-xs font-bold">تومان</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">پیش‌فرض: ۶۰۰,۰۰۰ تومان (در صورت خرید بیشتر، کرایه پست رایگان محاسبه می‌شود).</p>
                    </div>
                </div>

                <!-- Standard Shipping Cost & Support Phone -->
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">هزینه ثابت ارسال برای خریدهای زیر سقف (تومان):</label>
                        <div class="relative">
                            <input type="number" name="standard_shipping_cost_toman" value="<?= $standardShippingCost ?>" min="0" step="1000" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary outline-none pl-12 text-left dir-ltr">
                            <span class="absolute left-3 top-3 text-slate-400 text-xs font-bold">تومان</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">کرایه پایه بسته‌بندی و پست پیشتاز برای سبدهای زیر سقف (مثلاً ۴۹,۰۰۰ تومان).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">شماره تلفن ثابت پشتیبانی (الزام اینماد):</label>
                        <input type="text" name="support_phone_fixed" value="<?= htmlspecialchars($supportPhone) ?>" placeholder="02191000000" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary outline-none pl-3 text-left dir-ltr">
                        <p class="text-[10px] text-slate-400 mt-1">شماره خط ثابت استان یا دفتر مرکزی جهت درج در رسیدها و هدر/فوتر</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 6: AUTOMATED PAYOUT SCHEDULING (PAYA WEEKLY CYCLES)               -->
        <!-- ========================================================================= -->
        <div id="payout-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-emerald-600 text-2xl">schedule</span>
                    <span>۶. زمان‌بندی واریز خودکار هفتگی پایا به حساب فروشندگان</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">تسویه مبالغ تایید شده پس از انقضای دوره ۷ روزه تست مشتریان مطابق استانداردهای بانک مرکزی</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block">تسویه هفتگی خودکار</span>
                        <span class="text-[10px] text-slate-400">صدور خودکار حواله پایا</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_payout_enabled" value="1" <?= $autoPayoutEnabled === '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-12 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:start-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">روز تسویه هفتگی:</label>
                    <select name="auto_payout_day" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                        <option value="4" <?= $autoPayoutDay === 4 ? 'selected' : '' ?>>پنج‌شنبه‌ها (پیش‌فرض شاپرک و پایا)</option>
                        <option value="3" <?= $autoPayoutDay === 3 ? 'selected' : '' ?>>چهارشنبه‌ها</option>
                        <option value="0" <?= $autoPayoutDay === 0 ? 'selected' : '' ?>>یکشنبه‌ها</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">ساعت تسویه (تهران):</label>
                    <input type="text" name="auto_payout_time" value="<?= htmlspecialchars($autoPayoutTime) ?>" placeholder="09:00" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-center">
                    <p class="text-[10px] text-slate-400 mt-1">ساعت ۰۹:۰۰ صبح (آغاز اولین سیکل روزانه پایا)</p>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 7: ENAMAD & ELECTRONIC TRUST BADGES                               -->
        <!-- ========================================================================= -->
        <div id="enamad-section" class="scroll-mt-6 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[#001a48] dark:text-blue-400 text-2xl">verified</span>
                    <span>۷. کد نماد اعتماد الکترونیکی (اینماد enamad.ir)</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">کد اسکریپت دریافتی از سامانه اینماد وزارت صمت جهت نمایش زنده و معتبر در فوتر سایت</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">کد HTML اختصاصی اینماد:</label>
                <textarea name="enamad_html_code" rows="3" placeholder="<a referrerpolicy='origin' target='_blank' href='...'>..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono focus:border-primary focus:bg-white outline-none dir-ltr text-left"><?= htmlspecialchars($enamadCode) ?></textarea>
                <p class="text-[10px] text-slate-400 mt-1.5 leading-relaxed">
                    این کد مستقیماً در فوتر رسمی وب‌سایت در کنار لوگوی درگاه پرداخت و نمادهای تجارت الکترونیک رندر خواهد شد.
                </p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- STICKY ACTION BAR AT BOTTOM                                               -->
        <!-- ========================================================================= -->
        <div class="sticky bottom-4 z-40 bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-5 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <span class="material-symbols-outlined text-emerald-600 text-base">verified</span>
                <span>تغییرات به صورت آنی و متمرکز روی تمامی فاکتورها، رسیدها و صفحه محاسبه‌گر اعمال خواهند شد.</span>
            </div>
            <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-[#001a48] via-[#002d72] to-[#1e3a8a] hover:from-[#002d72] hover:to-blue-800 text-white px-8 py-3.5 rounded-2xl font-black text-xs sm:text-sm shadow-xl shadow-blue-950/25 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                <span class="material-symbols-outlined text-lg">save</span>
                <span>ذخیره تمامی تنظیمات خزانه‌داری و محاسبه‌گر</span>
            </button>
        </div>

    </form>

    <!-- Test Trigger Button (Outside main form to prevent accidental submission) -->
    <div class="p-6 rounded-3xl bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
            <h3 class="text-xs font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-base">play_circle</span>
                <span>آزمایش و صدور فوری سیکل تسویه پایا (شبیه‌ساز تست):</span>
            </h3>
            <p class="text-[11px] text-slate-500 mt-1">تست فوری آزادسازی مبالغ منقضی شده مهلت تست ۷ روزه و تجمیع مبالغ آماده فروشندگان در قالب پایا</p>
        </div>
        <form method="POST" action="finance_settings.php" onsubmit="return confirm('آیا از اجرای فوری تسویه حساب برای کلیه ذینفعان اطمینان دارید؟');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="force_test_payout">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-sm">play_arrow</span>
                <span>اجرای آزمایشی چرخه پایا</span>
            </button>
        </form>
    </div>

</div>

<!-- Interactive Switcher & Live Preview Script -->
<script>
function setCalculatorMode(mode) {
    const isPaid = (mode === 'paid');
    
    // Update Radio Elements
    const rFree = document.getElementById('calcModeRadioFree');
    const rPaid = document.getElementById('calcModeRadioPaid');
    if (rFree && rPaid) {
        rFree.checked = !isPaid;
        rPaid.checked = isPaid;
    }

    // Sync legacy checkbox
    const legacyCheck = document.getElementById('calcPaidToggle');
    if (legacyCheck) {
        legacyCheck.checked = isPaid;
    }

    // Toggle visual borders
    const cardFree = document.getElementById('cardCalcFree');
    const cardPaid = document.getElementById('cardCalcPaid');
    const container = document.getElementById('calculator-section');
    const statusBadge = document.getElementById('calcModeStatusBadge');
    const panel = document.getElementById('calcPaidDetailsPanel');

    if (isPaid) {
        cardFree?.classList.remove('border-emerald-500', 'bg-emerald-50/60', 'ring-2', 'ring-emerald-500/20');
        cardFree?.classList.add('border-slate-200', 'opacity-80');
        
        cardPaid?.classList.add('border-rose-500', 'bg-rose-50/60', 'ring-2', 'ring-rose-500/20');
        cardPaid?.classList.remove('border-slate-200', 'opacity-80');

        container?.classList.add('border-rose-300');
        container?.classList.remove('border-emerald-300');

        if (statusBadge) {
            statusBadge.className = 'px-4 py-1.5 rounded-full text-xs font-black font-mono bg-rose-100 text-rose-800 border border-rose-300';
            statusBadge.textContent = '🔴 حالت فعلی: پولی (Paid)';
        }

        if (panel) {
            panel.classList.remove('hidden');
        }
    } else {
        cardPaid?.classList.remove('border-rose-500', 'bg-rose-50/60', 'ring-2', 'ring-rose-500/20');
        cardPaid?.classList.add('border-slate-200', 'opacity-80');

        cardFree?.classList.add('border-emerald-500', 'bg-emerald-50/60', 'ring-2', 'ring-emerald-500/20');
        cardFree?.classList.remove('border-slate-200', 'opacity-80');

        container?.classList.add('border-emerald-300');
        container?.classList.remove('border-rose-300');

        if (statusBadge) {
            statusBadge.className = 'px-4 py-1.5 rounded-full text-xs font-black font-mono bg-emerald-100 text-emerald-800 border border-emerald-300';
            statusBadge.textContent = '🟢 حالت فعلی: کاملاً رایگان (Free)';
        }

        if (panel) {
            panel.classList.add('hidden');
        }
    }
}

// Live card formatting
document.getElementById('adminBankCardInput')?.addEventListener('input', function(e) {
    let val = e.target.value.replace(/\D/g, '').substring(0, 16);
    let chunks = val.match(/.{1,4}/g);
    let formatted = chunks ? chunks.join(' ') : val;
    e.target.value = chunks ? chunks.join('-') : val;
    
    const preview = document.getElementById('previewCardNumber');
    if (preview) {
        preview.textContent = formatted || '•••• •••• •••• ••••';
    }
});

document.getElementById('adminBankHolderInput')?.addEventListener('input', function(e) {
    const preview = document.getElementById('previewCardHolder');
    if (preview) {
        preview.textContent = e.target.value || 'آسنا تجارت';
    }
});

document.getElementById('adminBankNameInput')?.addEventListener('input', function(e) {
    const preview = document.getElementById('previewBankBadge');
    if (preview) {
        preview.textContent = e.target.value || 'بانک سامان';
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
