<?php
$currentPage = 'sms_settings';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';
require_once '../includes/SmsService.php';

$success = '';
$error = '';
$testResult = null;

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'save_notifications') {
        $adminPhones             = trim($_POST['admin_notification_phones'] ?? '');
        $smsOnOrder              = isset($_POST['admin_sms_on_order']) ? '1' : '0';
        $smsOnBooking            = isset($_POST['admin_sms_on_booking']) ? '1' : '0';
        $doctorSmsOnBooking      = isset($_POST['doctor_sms_on_booking']) ? '1' : '0';
        $doctorSmsOnTelehealth   = isset($_POST['doctor_sms_on_telehealth']) ? '1' : '0';
        $userSmsOnChat           = isset($_POST['user_sms_on_chat']) ? '1' : '0';

        // Clean and normalize phones
        $phoneArray = preg_split('/[,\s;]+/', $adminPhones);
        $cleanPhones = [];
        foreach ($phoneArray as $p) {
            $p = SmsService::normalizePhone($p);
            if (!empty($p) && strlen($p) >= 10) {
                $cleanPhones[] = $p;
            }
        }
        $savedPhonesStr = implode(', ', array_unique($cleanPhones));

        set_setting($pdo, 'admin_notification_phones', $savedPhonesStr);
        set_setting($pdo, 'admin_sms_on_order', $smsOnOrder);
        set_setting($pdo, 'admin_sms_on_booking', $smsOnBooking);
        set_setting($pdo, 'doctor_sms_on_booking', $doctorSmsOnBooking);
        set_setting($pdo, 'doctor_sms_on_telehealth', $doctorSmsOnTelehealth);
        set_setting($pdo, 'user_sms_on_chat', $userSmsOnChat);

        $success = "تنظیمات رویدادها، هشدارهای تله‌هلث و اعلان‌های پیامکی با موفقیت به‌روزرسانی شد.";
    } elseif ($action === 'save_gateway_credentials') {
        $apiKey   = trim($_POST['melipayamak_api_key'] ?? '');
        $username = trim($_POST['melipayamak_username'] ?? '');
        $password = trim($_POST['melipayamak_password'] ?? '');
        $fromNum  = trim($_POST['melipayamak_from'] ?? '');
        $sandbox  = isset($_POST['melipayamak_sandbox']) ? '1' : '0';

        set_setting($pdo, 'melipayamak_api_key', $apiKey);
        set_setting($pdo, 'melipayamak_username', $username);
        set_setting($pdo, 'melipayamak_password', $password);
        set_setting($pdo, 'melipayamak_from', $fromNum);
        set_setting($pdo, 'melipayamak_sandbox', $sandbox);

        $success = "مشخصات وب‌سرویس ملی‌پیامک و خط اختصاصی ارسال با موفقیت ذخیره شد.";
    } elseif ($action === 'save_pattern_ids') {
        $patternKeys = [
            'otp', 'doctor_telehealth', 'user_chat', 'booking',
            'reschedule', 'shipping', 'subscription', 'charity',
            'admin_order', 'doctor_booking'
        ];
        foreach ($patternKeys as $key) {
            if (isset($_POST['body_id_' . $key])) {
                $val = trim((string)$_POST['body_id_' . $key]);
                set_setting($pdo, 'melipayamak_body_id_' . $key, $val);
            }
        }
        $success = "شناسه‌های الگوهای خدماتی (Body IDs) با موفقیت ذخیره گردید.";
    } elseif ($action === 'test_sms') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        $testType  = $_POST['test_type'] ?? 'direct';
        $testPhone = SmsService::normalizePhone($testPhone);

        if (empty($testPhone) || strlen($testPhone) < 10) {
            $error = "لطفاً یک شماره موبایل معتبر جهت تست وارد نمایید.";
        } else {
            $sms = new SmsService();
            if ($sms->isMock()) {
                $testResult = [
                    'ok' => false,
                    'type' => 'حالت شبیه‌ساز (Sandbox / Mock)',
                    'message' => "⚠️ درگاه در حالت شبیه‌ساز (Sandbox) قرار دارد و پیامک واقعی به مخابرات ارسال نمی‌شود. جهت فعال‌سازی ارسال واقعی، حالت شبیه‌ساز را در تنظیمات وب‌سرویس غیرفعال نمایید."
                ];
            } elseif ($testType === 'otp') {
                $code = rand(100000, 999999);
                $res = $sms->sendOtp($testPhone, $code);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'کد تایید ورود/ثبت‌نام (OTP)',
                    'message' => $res ? "کد تایید اعتبارسنجی ($code) با موفقیت به شماره $testPhone ارسال شد." : ("خطا در ارسال کد تایید: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'doctor_telehealth') {
                $res = $sms->sendDoctorTelehealthAlert($testPhone, 'دکتر رضایی', 'میلو (کاربر آزمایشی)', 1);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار پیام تله‌هلث به پزشک',
                    'message' => $res ? "پیامک هشدار دریافت پیام بیمار در تله‌هلث با موفقیت به شماره پزشک $testPhone ارسال شد." : ("خطا در ارسال پیامک پزشک: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'user_chat') {
                $res = $sms->sendUserChatMessageAlert($testPhone, 'کاربر محترم', 'دکتر رضایی (متخصص داخلی)', 'https://asena.company/chat.php');
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار پیام جدید به کاربر/بیمار',
                    'message' => $res ? "پیامک اطلاع‌رسانی پیام جدید با موفقیت به شماره کاربر $testPhone ارسال شد." : ("خطا در ارسال پیامک کاربر: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'admin_order') {
                $fakeOrderId = rand(1050, 1999);
                $fakeAmount = 485000;
                $res = $sms->sendAdminNewOrderAlert($testPhone, $fakeOrderId, $fakeAmount);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار سفارش جدید به مدیر',
                    'message' => $res ? "پیامک هشدار سفارش (#PC-$fakeOrderId) با موفقیت به شماره مدیر $testPhone ارسال شد." : ("خطا در ارسال هشدار مدیر: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'doctor_booking') {
                $res = $sms->sendDoctorNewAppointmentAlert($testPhone, 'دکتر رضایی', 'میلو', '1404/06/25', '18:00');
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار نوبت جدید به پزشک',
                    'message' => $res ? "پیامک نوبت ویزیت با موفقیت به شماره پزشک $testPhone ارسال شد." : ("خطا در ارسال پیامک نوبت پزشک: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'seller_order') {
                $fakeOrderId = rand(1050, 1999);
                $res = $sms->sendSellerNewOrderAlert($testPhone, $fakeOrderId);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار سفارش جدید به فروشنده پت‌شاپ',
                    'message' => $res ? "پیامک سفارش جدید به فروشنده (#PC-$fakeOrderId) با موفقیت به شماره $testPhone ارسال شد." : ("خطا در ارسال پیامک فروشنده: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } else {
                $text = "تست موفقیت‌آمیز ارتباط پنل پیامک با سامانه آسنا.\nasena.company\nزمان: " . date('H:i:s');
                $res = $sms->sendDirectSms($testPhone, $text);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'پیامک مستقیم عمومی',
                    'message' => $res ? "پیامک مستقیم با موفقیت به شماره $testPhone ارسال شد." : ("خطا در ارسال پیامک مستقیم: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            }

            if ($testResult['ok']) {
                $success = $testResult['message'];
            } else {
                $error = $testResult['message'];
            }
        }
    }
}

// Retrieve current notification settings
$adminNotificationPhones = get_setting($pdo, 'admin_notification_phones', '09146676978');
$adminSmsOnOrder         = get_setting($pdo, 'admin_sms_on_order', '1');
$adminSmsOnBooking       = get_setting($pdo, 'admin_sms_on_booking', '1');
$doctorSmsOnBooking      = get_setting($pdo, 'doctor_sms_on_booking', '1');
$doctorSmsOnTelehealth   = get_setting($pdo, 'doctor_sms_on_telehealth', '1');
$userSmsOnChat           = get_setting($pdo, 'user_sms_on_chat', '1');

// Retrieve gateway settings
$mApiKey   = get_setting($pdo, 'melipayamak_api_key', getenv('MELIPAYAMAK_API_KEY') ?: '');
$mUsername = get_setting($pdo, 'melipayamak_username', getenv('MELIPAYAMAK_USERNAME') ?: '');
$mPassword = get_setting($pdo, 'melipayamak_password', getenv('MELIPAYAMAK_PASSWORD') ?: '');
$mFrom     = get_setting($pdo, 'melipayamak_from', getenv('MELIPAYAMAK_FROM') ?: '2170002198');
$mSandbox  = get_setting($pdo, 'melipayamak_sandbox', '0');

$smsInstance = new SmsService();
$liveCredit = null;
try {
    $liveCredit = $smsInstance->getCredit();
} catch (Throwable $e) {}

$phoneList = array_filter(array_map('trim', explode(',', $adminNotificationPhones)));

// Patterns catalog list
$patterns = [
    [
        'key'     => 'otp',
        'name'    => 'کد تایید OTP (ورود / ثبت‌نام / فراموشی رمز)',
        'target'  => 'کاربران و مشتریان',
        'body_id' => SmsService::getBodyId('otp'),
        'vars'    => '{0} = کد تایید عددی',
        'sample'  => "کد تایید ورود به سامانه آسنا:\n{0}\nasena.company"
    ],
    [
        'key'     => 'doctor_telehealth',
        'name'    => 'اطلاع‌رسانی پیام تله‌هلث به پزشک',
        'target'  => 'پزشک معالج',
        'body_id' => SmsService::getBodyId('doctor_telehealth'),
        'vars'    => '{0} = نام پزشک, {1} = نام بیمار/پت',
        'sample'  => "دکتر {0} گرامی،\nپیام جدیدی از بیمار {1} در سامانه تله‌هلث آسنا ثبت شد.\nورود و پاسخگویی:\nhttps://asena.company/doctor/telehealth.php"
    ],
    [
        'key'     => 'user_chat',
        'name'    => 'اطلاع‌رسانی پیام جدید به کاربر / بیمار',
        'target'  => 'کاربران و بیماران',
        'body_id' => SmsService::getBodyId('user_chat'),
        'vars'    => '{0} = نام کاربر, {1} = نام فرستنده',
        'sample'  => "{0} گرامی،\nپیام جدیدی از طرف «{1}» در سامانه آسنا دریافت شد.\nمشاهده و پاسخ:\nhttps://asena.company/chat.php"
    ],
    [
        'key'     => 'booking',
        'name'    => 'تایید رزرو نوبت ویزیت به کاربر',
        'target'  => 'کاربر / بیمار',
        'body_id' => SmsService::getBodyId('booking'),
        'vars'    => '{0} = تاریخ, {1} = ساعت',
        'sample'  => "کاربر گرامی، نوبت ویزیت شما در آسنا برای تاریخ {0} ساعت {1} با موفقیت تایید شد.\nasena.company"
    ],
    [
        'key'     => 'reschedule',
        'name'    => 'تغییر زمان نوبت ویزیت',
        'target'  => 'کاربر / بیمار',
        'body_id' => SmsService::getBodyId('reschedule'),
        'vars'    => '{0} = نام پزشک, {1} = نام پت, {2} = تاریخ جدید, {3} = ساعت جدید',
        'sample'  => "کاربر گرامی آسنا، زمان نوبت ویزیت پت شما ({1}) با دکتر {0} به تاریخ {2} ساعت {3} تغییر یافت.\nasena.company"
    ],
    [
        'key'     => 'doctor_booking',
        'name'    => 'اطلاع‌رسانی نوبت جدید به پزشک',
        'target'  => 'پزشک معالج',
        'body_id' => SmsService::getBodyId('doctor_booking'),
        'vars'    => '{0} = نام پزشک, {1} = نام پت, {2} = تاریخ, {3} = ساعت',
        'sample'  => "دکتر {0} گرامی، نوبت جدید برای پت ({1}) در تاریخ {2} ساعت {3} در آسنا ثبت شد.\nasena.company"
    ],
    [
        'key'     => 'shipping',
        'name'    => 'ارسال سفارش و کد رهگیری پستی',
        'target'  => 'خریدار / مشتری',
        'body_id' => SmsService::getBodyId('shipping'),
        'vars'    => '{0} = شماره سفارش',
        'sample'  => "سفارش شما به شماره {0} در آسنا پردازش و تحویل واحد ارسال شد.\nasena.company"
    ],
    [
        'key'     => 'seller_order',
        'name'    => 'اطلاع‌رسانی سفارش جدید به فروشنده (پت‌شاپ)',
        'target'  => 'فروشندگان مارکت‌پلیس',
        'body_id' => SmsService::getBodyId('seller_order'),
        'vars'    => '{0} = شماره سفارش',
        'sample'  => "فروشنده گرامی، سفارش جدید با شماره {0} در آسنا ثبت گردید."
    ],
    [
        'key'     => 'subscription',
        'name'    => 'فعال‌سازی بسته اشتراک دوره ای',
        'target'  => 'مشترک',
        'body_id' => SmsService::getBodyId('subscription'),
        'vars'    => '{0} = نام اشتراک',
        'sample'  => "اشتراک {0} شما در سامانه آسنا با موفقیت فعال گردید.\nasena.company"
    ],
    [
        'key'     => 'admin_order',
        'name'    => 'اطلاع‌رسانی سفارش جدید به مدیر',
        'target'  => 'مدیران سیستم',
        'body_id' => SmsService::getBodyId('admin_order'),
        'vars'    => '{0} = شماره سفارش, {1} = مبلغ کل',
        'sample'  => "مدیر گرامی، سفارش جدید به شماره {0} با مبلغ {1} تومان در سامانه آسنا ثبت شد.\nasena.company"
    ],
    [
        'key'     => 'charity',
        'name'    => 'تشکر واریز خیریه و حمایت حیوانات',
        'target'  => 'نیکوکار',
        'body_id' => SmsService::getBodyId('charity'),
        'vars'    => '{0} = مبلغ واریزی',
        'sample'  => "کاربر گرامی، از حمایت ارزشمند شما به مبلغ {0} تومان به پویش خیریه حیوانات آسنا سپاسگزاریم.\nasena.company"
    ],
];
?>

<div class="p-6 lg:p-8 max-w-[1400px] mx-auto rtl">
    <!-- Page Title & Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
                    <span class="material-symbols-outlined text-[30px]" style="font-variation-settings: 'FILL' 1;">sms</span>
                </div>
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-primary font-bold">تنظیمات وب‌سرویس پیامک و اعلان‌های سیستم</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">مرکز پایش، مدیریت خطوط خدماتی ملی‌پیامک، اعلان‌های تله‌هلث و پیام‌های کاربران</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="https://login.melipayamak.com/?module=ShareService" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant hover:border-primary/50 px-4 py-2.5 rounded-xl font-bold text-xs text-primary shadow-sm hover:shadow transition-all">
                <span class="material-symbols-outlined text-[18px] text-primary">open_in_new</span>
                <span>پنل خطوط اشتراکی ملی‌پیامک</span>
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl flex items-center gap-3 font-bold text-sm shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl flex items-center gap-3 font-bold text-sm shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- System Status & Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- 1. Gateway Status -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">وضعیت درگاه پیامک</p>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full <?= $mSandbox === '1' ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' ?>"></span>
                    <p class="text-sm font-black text-primary">
                        <?= $mSandbox === '1' ? 'شبیه‌ساز (Sandbox)' : 'عملیاتی مستقیم (Live)' ?>
                    </p>
                </div>
            </div>
            <div class="w-12 h-12 <?= $mSandbox === '1' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">cell_tower</span>
            </div>
        </div>

        <!-- 2. Melipayamak Credit -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">مانده اعتبار ملی‌پیامک</p>
                <p class="text-base font-black <?= ($liveCredit !== null) ? 'text-emerald-700 font-mono' : 'text-slate-600' ?>">
                    <?= ($liveCredit !== null) ? number_format($liveCredit) . ' ریال' : ($mSandbox === '1' ? 'حالت شبیه‌ساز' : 'متصل به وب‌سرویس') ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <span class="material-symbols-outlined text-[26px]">account_balance_wallet</span>
            </div>
        </div>

        <!-- 3. Telehealth Alerts Status -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">پیامک تله‌هلث به پزشک</p>
                <p class="text-sm font-black <?= $doctorSmsOnTelehealth === '1' ? 'text-emerald-600' : 'text-slate-400' ?>">
                    <?= $doctorSmsOnTelehealth === '1' ? 'فعال (تراتل ۱۵ دقیقه)' : 'غیرفعال' ?>
                </p>
            </div>
            <div class="w-12 h-12 <?= $doctorSmsOnTelehealth === '1' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">medical_services</span>
            </div>
        </div>

        <!-- 4. User Chat Alerts Status -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">پیامک پیام جدید به کاربر</p>
                <p class="text-sm font-black <?= $userSmsOnChat === '1' ? 'text-emerald-600' : 'text-slate-400' ?>">
                    <?= $userSmsOnChat === '1' ? 'فعال (پاسخ دکتر/مدیر)' : 'غیرفعال' ?>
                </p>
            </div>
            <div class="w-12 h-12 <?= $userSmsOnChat === '1' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">chat</span>
            </div>
        </div>
    </div>

    <!-- Section 1: Gateway Connection Credentials -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow mb-8">
        <div class="flex items-center justify-between gap-3 mb-6 pb-4 border-b border-outline-variant/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">vpn_key</span>
                </div>
                <div>
                    <h3 class="font-bold text-primary text-base">پیکربندی اتصال مستقیم به وب‌سرویس ملی‌پیامک</h3>
                    <p class="text-xs text-on-surface-variant">تنظیمات اعتبارسنجی درگاه، کلید API Key کنسول و شماره خط اختصاصی شرکت آسنا</p>
                </div>
            </div>
            <span class="text-[11px] font-mono bg-surface-container px-3 py-1 rounded-lg text-outline">
                Gateway Engine: REST + SOAP
            </span>
        </div>

        <form method="POST" action="sms_settings.php" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_gateway_credentials">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- API Key -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-primary mb-2">کلید وب‌سرویس کنسول (API Key):</label>
                    <input type="text" name="melipayamak_api_key" value="<?= htmlspecialchars($mApiKey) ?>" placeholder="efaec6c8-2daf-4473-9080-df7ac67eea89" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">کلید REST API صادرشده از پنل جدید console.melipayamak.com</p>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">نام کاربری / شماره همراه پنل:</label>
                    <input type="text" name="melipayamak_username" value="<?= htmlspecialchars($mUsername) ?>" placeholder="9146676978" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">شناسه کاربری ورود به پنل ملی‌پیامک</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">رمز عبور / توکن وب‌سرویس:</label>
                    <input type="password" name="melipayamak_password" value="<?= htmlspecialchars($mPassword) ?>" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">رمز وب‌سرویس یا رمز ورود به حساب کاربری</p>
                </div>

                <!-- From Number -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-primary mb-2">شماره خط اختصاصی ارسال‌کننده (From):</label>
                    <input type="text" name="melipayamak_from" value="<?= htmlspecialchars($mFrom) ?>" placeholder="2170002198 یا 50004001" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">خط پیامکی اختصاصی شرکت آسنا جهت ارسال پیام‌های مستقیم</p>
                </div>

                <!-- Sandbox Mode Toggle -->
                <div class="sm:col-span-2 flex items-center gap-3 pt-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="melipayamak_sandbox" value="1" <?= $mSandbox === '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">حالت شبیه‌ساز (Sandbox / Mock Mode)</span>
                        <span class="text-[10px] text-slate-500">در صورت فعال بودن، پیامک‌ها در لاگ سیستم ثبت می‌شوند اما به مخابرات ارسال نمی‌گردند (مناسب تست محلی).</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/30 flex justify-between items-center">
                <span class="text-xs text-on-surface-variant">تنظیمات در جدول settings دیتابیس با اولویت بالاتر از .env ذخیره می‌شوند.</span>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>ذخیره مشخصات درگاه پیامک</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: Main 2-Column Grid (Notification Policies + Live Tester) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Notification Events Form (Col 7) -->
        <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
                <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">notifications_active</span>
                </div>
                <div>
                    <h3 class="font-bold text-primary text-base">رویدادها و اعلان‌های پیامکی خودکار</h3>
                    <p class="text-xs text-on-surface-variant">فعال‌سازی یا غیرفعال‌سازی پیامک‌های خودکار سیستم برای پزشکان، کاربران و مدیران</p>
                </div>
            </div>

            <form method="POST" action="sms_settings.php" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_notifications">

                <!-- Admin Notification Numbers -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">
                        شماره‌های همراه مدیران جهت دریافت پیامک‌های پلتفرم:
                    </label>
                    <textarea name="admin_notification_phones" rows="2" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-left focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" placeholder="09146676978, 09123456789" dir="ltr"><?= htmlspecialchars($adminNotificationPhones) ?></textarea>
                    <p class="text-[11px] text-on-surface-variant mt-1 leading-relaxed">
                        شماره‌های مدیران را با کاما (,) یا فاصله جدا کنید. هشدارهای سفارشات و نوبت‌ها به تمام این شماره‌ها ارسال خواهد شد.
                    </p>

                    <!-- Active Phone Chips -->
                    <?php if (!empty($phoneList)): ?>
                    <div class="flex flex-wrap gap-2 mt-2.5">
                        <?php foreach ($phoneList as $p): ?>
                            <span class="inline-flex items-center gap-1.5 bg-primary/5 text-primary text-[11px] font-bold px-3 py-1 rounded-lg border border-primary/10" dir="ltr">
                                <span class="material-symbols-outlined text-[13px]">phone_iphone</span>
                                <span><?= htmlspecialchars($p) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-4 border-t border-outline-variant/30 space-y-3.5">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">سیاست‌های ارسال اعلان پیامکی</p>

                    <!-- Toggle 1: Telehealth SMS to Doctor -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">medical_services</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-primary">ارسال پیامک به پزشک معالج هنگام پیام جدید بیمار در تله‌هلث</p>
                                <p class="text-[11px] text-on-surface-variant">به محض ارسال پیام بیمار، پیامک اطلاع‌رسانی با تراتل آنتی‌اسپم ۱۵ دقیقه‌ای به موبایل پزشک ارسال می‌شود.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="doctor_sms_on_telehealth" value="1" <?= $doctorSmsOnTelehealth === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-primary rounded cursor-pointer">
                    </label>

                    <!-- Toggle 2: User SMS on reply from Doctor/Admin/Org -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">mark_chat_unread</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-primary">ارسال پیامک به کاربر هنگام پاسخ پزشک، مدیریت یا کلینیک</p>
                                <p class="text-[11px] text-on-surface-variant">اطلاع‌رسانی پیام جدید در چت با تراتل هوشمند ۱۵ دقیقه‌ای به بیمار جهت پاسخگویی سریع.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="user_sms_on_chat" value="1" <?= $userSmsOnChat === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-primary rounded cursor-pointer">
                    </label>

                    <!-- Toggle 3: Doctor SMS on new booking -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">stethoscope</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-primary">ارسال پیامک به پزشک هنگام رزرو نوبت جدید</p>
                                <p class="text-[11px] text-on-surface-variant">ارسال جزئیات تاریخ، ساعت و نام پت به شماره موبایل پزشک معالج.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="doctor_sms_on_booking" value="1" <?= $doctorSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-primary rounded cursor-pointer">
                    </label>

                    <!-- Toggle 4: Admin SMS on new order -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-primary">ارسال پیامک به مدیران هنگام ثبت سفارش جدید</p>
                                <p class="text-[11px] text-on-surface-variant">اطلاع‌رسانی آنی شماره سفارش و مبلغ به مدیران پس از پرداخت موفق سبد خرید.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_order" value="1" <?= $adminSmsOnOrder === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-primary rounded cursor-pointer">
                    </label>

                    <!-- Toggle 5: Admin SMS on new booking -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-primary/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-primary">ارسال پیامک به مدیران هنگام ثبت نوبت کلینیک</p>
                                <p class="text-[11px] text-on-surface-variant">اطلاع‌رسانی به مدیران سیستم هنگام ثبت وقت ملاقات حضوری یا آنلاین.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_booking" value="1" <?= $adminSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-primary rounded cursor-pointer">
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-md active:scale-[0.99] transition-all text-xs">
                        <span class="material-symbols-outlined text-sm">save</span>
                        <span>ذخیره تنظیمات اعلان‌های خودکار</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live SMS Tester (Col 5) -->
        <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[24px]">send_to_mobile</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-primary text-base">ابزار تست زنده ارسال پیامک</h3>
                        <p class="text-xs text-on-surface-variant">بررسی صحت اتصال به خطوط خدماتی و تحویل پیامک روی سیم‌کارت</p>
                    </div>
                </div>

                <form method="POST" action="sms_settings.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="test_sms">

                    <div>
                        <label class="block text-xs font-bold text-primary mb-2">شماره موبایل گیرنده تست:</label>
                        <div class="relative">
                            <input type="text" name="test_phone" value="<?= htmlspecialchars($phoneList[0] ?? '09146676978') ?>" class="w-full p-3 pl-10 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-left focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none transition-all" placeholder="09123456789" dir="ltr" required>
                            <span class="material-symbols-outlined absolute left-3 top-2.5 text-outline text-[20px]">phone_android</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-primary mb-2">نوع سناریوی پیامک تستی:</label>
                        <select name="test_type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-primary focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none transition-all">
                            <option value="direct">پیامک مستقیم عمومی (Direct SMS)</option>
                            <option value="otp">کد تایید اعتبارسنجی ورود (OTP)</option>
                            <option value="doctor_telehealth">پیامک هشدار تله‌هلث به پزشک</option>
                            <option value="user_chat">پیامک هشدار پیام جدید به کاربر/بیمار</option>
                            <option value="seller_order">هشدار سفارش جدید به فروشنده (پت‌شاپ)</option>
                            <option value="admin_order">هشدار ثبت سفارش جدید به مدیر</option>
                            <option value="doctor_booking">هشدار ثبت نوبت جدید به پزشک</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-md active:scale-[0.99] transition-all text-xs">
                        <span class="material-symbols-outlined text-sm">rocket_launch</span>
                        <span>ارسال پیامک آزمایشی فوری</span>
                    </button>
                </form>

                <!-- Help Tip Box -->
                <div class="mt-6 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 text-xs leading-relaxed">
                    <div class="flex items-center gap-1.5 font-bold mb-1 text-primary">
                        <span class="material-symbols-outlined text-[16px] text-emerald-600">verified</span>
                        <span>مزیت خطوط خدماتی اشتراکی (BaseService):</span>
                    </div>
                    پیامک‌های ارسالی از طریق الگوهای خدماتی، حتی به شماره‌هایی که دریافت پیامک‌های تبلیغاتی را مسدود کرده‌اند (بلک‌لیست مخابرات) به صورت آنی تحویل داده می‌شوند.
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Patterns Catalog & Inline Body ID Management -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow mb-8">
        <form method="POST" action="sms_settings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_pattern_ids">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-outline-variant/30">
                <div>
                    <h3 class="font-bold text-primary text-base flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">format_list_bulleted</span>
                        <span>کاتالوگ الگوها و شناسه‌های خدماتی اشتراکی (Melipayamak Body IDs)</span>
                    </h3>
                    <p class="text-xs text-on-surface-variant">می‌توانید کدهای الگوهای تأییدشده در پنل ملی‌پیامک را مستقیماً در این جدول ویرایش و ذخیره فرمایید.</p>
                </div>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-5 py-2 rounded-xl font-bold text-xs shadow transition-all flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>ذخیره شناسه‌های پترن</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant/40">
                            <th class="p-3.5 rounded-r-xl">عنوان الگو و سناریو</th>
                            <th class="p-3.5">مخاطب</th>
                            <th class="p-3.5 w-44">شناسه الگو (Body ID)</th>
                            <th class="p-3.5">متغیرهای الگو</th>
                            <th class="p-3.5 rounded-l-xl">متن مصوب / نمونه قالب</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($patterns as $pat): ?>
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="p-3.5 font-bold text-primary">
                                <?= htmlspecialchars($pat['name']) ?>
                            </td>
                            <td class="p-3.5">
                                <span class="bg-primary/5 text-primary font-bold px-2.5 py-1 rounded-md text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($pat['target']) ?>
                                </span>
                            </td>
                            <td class="p-3.5">
                                <input type="text" name="body_id_<?= $pat['key'] ?>" value="<?= htmlspecialchars($pat['body_id']) ?>" class="w-36 px-3 py-1.5 bg-surface rounded-lg border border-outline-variant text-xs font-mono font-bold text-center focus:border-primary outline-none" dir="ltr" placeholder="12345">
                            </td>
                            <td class="p-3.5 font-mono text-slate-600 text-[11px]" dir="ltr">
                                <?= htmlspecialchars($pat['vars']) ?>
                            </td>
                            <td class="p-3.5 text-slate-700 leading-relaxed font-sans max-w-md">
                                <div class="bg-surface-container-low p-2 rounded-lg text-[11px] border border-outline-variant/20 whitespace-pre-line font-sans">
                                    <?= htmlspecialchars($pat['sample']) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- Section 4: Live Diagnostic Logs & Monitoring -->
    <?php
    $logFile = dirname(__DIR__, 2) . '/logs/sms.log';
    $recentLogs = [];
    if (file_exists($logFile) && is_readable($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $slice = array_slice($lines, -20);
            $slice = array_reverse($slice);
            foreach ($slice as $l) {
                $decoded = json_decode($l, true);
                if ($decoded) $recentLogs[] = $decoded;
            }
        }
    }
    ?>
    <div class="bg-surface-container-lowest p-6 lg:p-8 rounded-2xl shadow-sm border border-outline-variant/30">
        <div class="flex items-center justify-between mb-4 border-b border-outline-variant/20 pb-4">
            <div>
                <h3 class="font-bold text-primary text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[22px]">history_edu</span>
                    <span>مانیتورینگ و لاگ‌های تشخیصی ارسال پیامک (SMS Diagnostic Logs)</span>
                </h3>
                <p class="text-xs text-on-surface-variant">رهگیری زنده کدهای وضعیت مخابراتی، متغیرهای ارسالی و پاسخ‌های درگاه ملی‌پیامک</p>
            </div>
            <span class="text-xs font-mono bg-surface-container-low px-3 py-1 rounded-full text-outline">
                <?= count($recentLogs) ?> رویداد ثبت‌شده اخیر
            </span>
        </div>

        <?php if (empty($recentLogs)): ?>
            <div class="p-8 text-center text-on-surface-variant text-xs bg-surface-container-low/50 rounded-xl">
                هنوز رویدادی در فایل لاگ ثبت نشده است. با ارسال یک پیامک تستی از فرم بالا، اولین لاگ ایجاد خواهد شد.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant/40">
                            <th class="p-3 rounded-r-xl">زمان ثبت</th>
                            <th class="p-3">نوع عملیات</th>
                            <th class="p-3">شماره گیرنده</th>
                            <th class="p-3">وضعیت HTTP</th>
                            <th class="p-3">تحلیل و وضعیت تحویل</th>
                            <th class="p-3 rounded-l-xl">پاسخ فنی درگاه</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($recentLogs as $log): 
                            $isSuccess = strpos($log['interpretation'] ?? '', 'موفق') !== false;
                            $toPhone = $log['payload']['to'] ?? '—';
                        ?>
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="p-3 font-mono text-slate-500 whitespace-nowrap" dir="ltr"><?= htmlspecialchars($log['time'] ?? '') ?></td>
                            <td class="p-3">
                                <span class="bg-primary/10 text-primary font-bold px-2 py-0.5 rounded text-[11px]">
                                    <?= htmlspecialchars($log['action'] ?? '') ?>
                                </span>
                            </td>
                            <td class="p-3 font-mono font-bold" dir="ltr"><?= htmlspecialchars($toPhone) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono <?= ($log['http_code'] ?? 0) === 200 ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-red-100 text-red-800' ?>">
                                    HTTP <?= htmlspecialchars((string)($log['http_code'] ?? '—')) ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <span class="inline-flex items-center gap-1 font-semibold <?= $isSuccess ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <span class="material-symbols-outlined text-[15px]"><?= $isSuccess ? 'check_circle' : 'error' ?></span>
                                    <?= htmlspecialchars($log['interpretation'] ?? '') ?>
                                </span>
                            </td>
                            <td class="p-3 font-mono text-[10px] text-slate-500 max-w-xs truncate" dir="ltr">
                                <?= htmlspecialchars(is_array($log['response'] ?? null) ? json_encode($log['response']) : ($log['response'] ?? '')) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
