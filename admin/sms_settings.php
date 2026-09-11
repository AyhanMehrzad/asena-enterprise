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

    if ($action === 'save_settings') {
        $adminPhones = trim($_POST['admin_notification_phones'] ?? '');
        $smsOnOrder  = isset($_POST['admin_sms_on_order']) ? '1' : '0';
        $smsOnBooking = isset($_POST['admin_sms_on_booking']) ? '1' : '0';
        $doctorSmsOnBooking = isset($_POST['doctor_sms_on_booking']) ? '1' : '0';

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

        $success = "تنظیمات پیامک و اعلان‌های مدیران با موفقیت ذخیره شد.";
    } elseif ($action === 'save_gateway_credentials') {
        $apiKey   = trim($_POST['melipayamak_api_key'] ?? '');
        $username = trim($_POST['melipayamak_username'] ?? '');
        $password = trim($_POST['melipayamak_password'] ?? '');
        $fromNum  = trim($_POST['melipayamak_from'] ?? '');
        $sandbox  = isset($_POST['melipayamak_sandbox']) ? '1' : '0';
        $smsPrice = max(100, (int)($_POST['sms_price_per_unit'] ?? 850));
        $pack100  = max(1000, (int)($_POST['sms_pack_100_price'] ?? 85000));
        $pack500  = max(1000, (int)($_POST['sms_pack_500_price'] ?? 375000));
        $pack1000 = max(1000, (int)($_POST['sms_pack_1000_price'] ?? 680000));

        set_setting($pdo, 'melipayamak_api_key', $apiKey);
        set_setting($pdo, 'melipayamak_username', $username);
        set_setting($pdo, 'melipayamak_password', $password);
        set_setting($pdo, 'melipayamak_from', $fromNum);
        set_setting($pdo, 'melipayamak_sandbox', $sandbox);
        set_setting($pdo, 'sms_price_per_unit', $smsPrice);
        set_setting($pdo, 'sms_pack_100_price', $pack100);
        set_setting($pdo, 'sms_pack_500_price', $pack500);
        set_setting($pdo, 'sms_pack_1000_price', $pack1000);

        $success = "اطلاعات وب‌سرویس، تعرفه هر پیامک و قیمت بسته‌های پیامک با حاشیه سود آسنا به‌روزرسانی شد.";
    } elseif ($action === 'test_sms') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        $testType  = $_POST['test_type'] ?? 'direct';
        $testPhone = SmsService::normalizePhone($testPhone);

        if (empty($testPhone) || strlen($testPhone) < 10) {
            $error = "لطفاً یک شماره موبایل معتبر جهت تست وارد نمایید.";
        } else {
            $sms = new SmsService();
            if ($testType === 'otp') {
                $code = rand(100000, 999999);
                $res = $sms->sendOtp($testPhone, $code);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'کد تایید اعتبارسنجی (OTP)',
                    'message' => $res ? "کد تایید آزمایشی ($code) با موفقیت به شماره $testPhone ارسال شد." : ("خطا در ارسال کد تایید: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'admin_order') {
                $fakeOrderId = rand(1050, 1999);
                $fakeAmount = 485000;
                $res = $sms->sendAdminNewOrderAlert($testPhone, $fakeOrderId, $fakeAmount);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار سفارش جدید به مدیر',
                    'message' => $res ? "پیامک هشدار سفارش فرضی (#PC-$fakeOrderId) به شماره $testPhone ارسال شد." : ("خطا در ارسال هشدار مدیر: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } elseif ($testType === 'doctor_booking') {
                $res = $sms->sendDoctorNewAppointmentAlert($testPhone, 'دکتر نامی', 'میلو', '1404/06/20', '17:30');
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'هشدار رزرو نوبت به پزشک',
                    'message' => $res ? "پیامک نوبت فرضی با موفقیت به شماره $testPhone ارسال شد." : ("خطا در ارسال هشدار پزشک: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
                ];
            } else {
                $text = "تست موفقیت‌آمیز ارتباط پنل پیامک با سامانه آسنا.\nasena.company\nزمان: " . date('H:i:s');
                $res = $sms->sendDirectSms($testPhone, $text);
                $testResult = [
                    'ok' => (bool)$res,
                    'type' => 'پیامک مستقیم',
                    'message' => $res ? "پیامک مستقیم با موفقیت به $testPhone ارسال شد." : ("خطا در ارسال پیامک مستقیم: " . ($sms->getLastError() ?: 'پاسخ ناموفق درگاه'))
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

// Retrieve current settings
$adminNotificationPhones = get_setting($pdo, 'admin_notification_phones', '09146676978');
$adminSmsOnOrder         = get_setting($pdo, 'admin_sms_on_order', '1');
$adminSmsOnBooking       = get_setting($pdo, 'admin_sms_on_booking', '1');
$doctorSmsOnBooking      = get_setting($pdo, 'doctor_sms_on_booking', '1');

$mApiKey      = get_setting($pdo, 'melipayamak_api_key', getenv('MELIPAYAMAK_API_KEY') ?: '');
$mUsername    = get_setting($pdo, 'melipayamak_username', getenv('MELIPAYAMAK_USERNAME') ?: '');
$mPassword    = get_setting($pdo, 'melipayamak_password', getenv('MELIPAYAMAK_PASSWORD') ?: '');
$mFrom        = get_setting($pdo, 'melipayamak_from', getenv('MELIPAYAMAK_FROM') ?: '50004001');
$smsUnitPrice = (int)get_setting($pdo, 'sms_price_per_unit', 850);
$pack100Price = (int)get_setting($pdo, 'sms_pack_100_price', 85000);
$pack500Price = (int)get_setting($pdo, 'sms_pack_500_price', 375000);
$pack1000Price = (int)get_setting($pdo, 'sms_pack_1000_price', 680000);

$smsInstance = new SmsService();
$liveCredit = $smsInstance->getCredit();

$phoneList = array_filter(array_map('trim', explode(',', $adminNotificationPhones)));

// Patterns list for display
$patterns = [
    [
        'id' => 'otp',
        'name' => 'کد تایید OTP (ورود / بازیابی)',
        'vars' => '{0} = کد تایید',
        'body_id' => SmsService::getBodyId('otp'),
        'sample' => "کد تایید ورود به آسنا:\n{0}\nasena.company",
        'target' => 'کاربر / مشتری'
    ],
    [
        'id' => 'booking',
        'name' => 'تایید رزرو نوبت ویزیت',
        'vars' => '{0} = تاریخ, {1} = ساعت',
        'body_id' => SmsService::getBodyId('booking'),
        'sample' => "کاربر گرامی، نوبت ویزیت شما در آسنا برای تاریخ {0} ساعت {1} با موفقیت تایید شد.\nasena.company",
        'target' => 'کاربر / بیمار'
    ],
    [
        'id' => 'reschedule',
        'name' => 'تغییر زمان نوبت ویزیت',
        'vars' => '{0} = نام پزشک, {1} = نام پت, {2} = تاریخ جدید, {3} = ساعت جدید',
        'body_id' => SmsService::getBodyId('reschedule'),
        'sample' => "کاربر گرامی آسنا، زمان نوبت ویزیت پت شما ({1}) با دکتر {0} به تاریخ {2} ساعت {3} تغییر یافت.\nasena.company",
        'target' => 'کاربر / بیمار'
    ],
    [
        'id' => 'shipping',
        'name' => 'ارسال سفارش فروشگاه / داروخانه',
        'vars' => '{0} = شماره سفارش',
        'body_id' => SmsService::getBodyId('shipping'),
        'sample' => "سفارش شما به شماره {0} در آسنا پردازش و تحویل واحد ارسال شد.\nasena.company",
        'target' => 'خریدار'
    ],
    [
        'id' => 'subscription',
        'name' => 'فعال‌سازی بسته اشتراک',
        'vars' => '{0} = نام اشتراک (ماهانه/طلایی)',
        'body_id' => SmsService::getBodyId('subscription'),
        'sample' => "اشتراک {0} شما در سامانه آسنا با موفقیت فعال گردید.\nasena.company",
        'target' => 'مشترک'
    ],
    [
        'id' => 'charity',
        'name' => 'تشکر واریز خیریه حیوانات',
        'vars' => '{0} = مبلغ واریزی به تومان',
        'body_id' => SmsService::getBodyId('charity'),
        'sample' => "کاربر گرامی، از حمایت ارزشمند شما به مبلغ {0} تومان به پویش خیریه حیوانات آسنا سپاسگزاریم.\nasena.company",
        'target' => 'نیکوکار'
    ],
    [
        'id' => 'admin_order',
        'name' => 'اطلاع‌رسانی سفارش جدید به مدیر',
        'vars' => '{0} = شماره سفارش, {1} = مبلغ کل به تومان',
        'body_id' => SmsService::getBodyId('admin_order'),
        'sample' => "مدیر گرامی، سفارش جدید به شماره {0} با مبلغ {1} تومان در سامانه آسنا ثبت شد.\nasena.company",
        'target' => 'مدیران سیستم'
    ],
    [
        'id' => 'doctor_booking',
        'name' => 'اطلاع‌رسانی نوبت جدید به پزشک',
        'vars' => '{0} = نام پزشک, {1} = نام پت, {2} = تاریخ, {3} = ساعت',
        'body_id' => SmsService::getBodyId('doctor_booking'),
        'sample' => "دکتر {0} گرامی، نوبت جدید برای پت ({1}) در تاریخ {2} ساعت {3} در آسنا ثبت شد.\nasena.company",
        'target' => 'پزشک معالج'
    ],
];
?>

<div class="p-6 lg:p-8 max-w-[1400px] mx-auto rtl">
    <!-- Page Title & Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-[30px]" style="font-variation-settings: 'FILL' 1;">sms</span>
                </div>
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-primary font-bold">تنظیمات پیامک و اعلان‌های سیستم</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">مدیریت خطوط خدماتی ملی‌پیامک، شماره‌های مدیران و اعلان‌های خودکار</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="https://login.melipayamak.com/?module=ShareService" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant hover:border-secondary-container px-4 py-2.5 rounded-xl font-bold text-sm text-primary shadow-sm hover:shadow transition-all">
                <span class="material-symbols-outlined text-[20px] text-secondary-container">open_in_new</span>
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

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">شماره‌های مدیران متصل</p>
                <p class="text-2xl font-black text-primary"><?= count($phoneList) ?> <span class="text-xs font-normal text-on-surface-variant">شماره</span></p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[28px]">contact_phone</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">پیامک سفارشات جدید</p>
                <p class="text-lg font-black <?= $adminSmsOnOrder === '1' ? 'text-emerald-600' : 'text-slate-400' ?>">
                    <?= $adminSmsOnOrder === '1' ? 'فعال (ارسال آنی)' : 'غیرفعال' ?>
                </p>
            </div>
            <div class="w-12 h-12 <?= $adminSmsOnOrder === '1' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">shopping_cart_checkout</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">پیامک نوبت به پزشکان</p>
                <p class="text-lg font-black <?= $doctorSmsOnBooking === '1' ? 'text-secondary-container' : 'text-slate-400' ?>">
                    <?= $doctorSmsOnBooking === '1' ? 'فعال (پیامک به دکتر)' : 'غیرفعال' ?>
                </p>
            </div>
            <div class="w-12 h-12 <?= $doctorSmsOnBooking === '1' ? 'bg-secondary-container/10 text-secondary-container' : 'bg-slate-100 text-slate-400' ?> rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">medical_information</span>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 stat-card-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-on-surface-variant mb-1">مانده شارژ پنل ملی‌پیامک</p>
                <p class="text-lg font-black <?= ($liveCredit !== null) ? 'text-emerald-600 font-mono' : 'text-amber-600' ?>">
                    <?= ($liveCredit !== null) ? number_format($liveCredit) . ' ریال' : ($mSandbox === '1' ? 'حالت سندباکس' : 'متصل / بدون پاسخ مانده') ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <span class="material-symbols-outlined text-[28px]">account_balance_wallet</span>
            </div>
        </div>
    </div>

    <!-- Administrative Gateway Credentials Card (Exclusive to Admin) -->
    <div class="bg-surface-container-lowest border-2 border-primary/20 rounded-2xl p-6 lg:p-8 stat-card-shadow mb-8 relative overflow-hidden">
        <div class="absolute top-0 left-0 bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-br-xl">
            اختصاصی مدیر ارشد آسنا
        </div>
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
            <span class="material-symbols-outlined text-primary text-[28px]">key</span>
            <div>
                <h3 class="font-bold text-primary text-lg">پیکربندی وب‌سرویس و API Key ملی‌پیامک (خزانه‌داری آسنا)</h3>
                <p class="text-xs text-on-surface-variant">تنظیمات اتصال مستقیم به درگاه ملی‌پیامک، کلید اختصاصی و تعیین تعرفه فروش پیامک به فروشندگان و پزشکان</p>
            </div>
        </div>

        <form method="POST" action="sms_settings.php" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_gateway_credentials">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- API Key -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-bold text-primary mb-2">کلید وب‌سرویس (API Key):</label>
                    <input type="text" name="melipayamak_api_key" value="<?= htmlspecialchars($mApiKey) ?>" placeholder="e.g. 5ab7... یا خالی بگذارید" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">کلید API ایجاد شده در کنسول ملی‌پیامک</p>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">نام کاربری پنل ملی‌پیامک (شماره موبایل/یوزر):</label>
                    <input type="text" name="melipayamak_username" value="<?= htmlspecialchars($mUsername) ?>" placeholder="0914... یا نام کاربری" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">شماره موبایل یا شناسه کاربری ثبت‌شده در ملی‌پیامک</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">رمز عبور / توکن وب‌سرویس:</label>
                    <input type="password" name="melipayamak_password" value="<?= htmlspecialchars($mPassword) ?>" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">رمز ورود به وب‌سرویس ارسال پیامک</p>
                </div>

                <!-- Dedicated From Number -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">شماره خط اختصاصی ارسال‌کننده (From):</label>
                    <input type="text" name="melipayamak_from" value="<?= htmlspecialchars($mFrom) ?>" placeholder="50004001..." class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none dir-ltr text-left">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">خط خدماتی یا تبلیغاتی شرکت آسنا</p>
                </div>

                <!-- SMS Price Per Unit for Roles -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">تعرفه پایه هر پیامک آزاد (تومان):</label>
                    <input type="number" name="sms_price_per_unit" value="<?= $smsUnitPrice ?>" min="100" step="50" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-bold focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <p class="text-[10px] text-on-surface-variant mt-1.5">مبلغ کسر شده به ازای هر پیامک ارسالی مستقیم</p>
                </div>

                <!-- Package 100 Price -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">قیمت بسته ۱۰۰ پیامک (تومان):</label>
                    <input type="number" name="sms_pack_100_price" value="<?= $pack100Price ?>" min="5000" step="1000" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-bold focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <p class="text-[10px] text-emerald-700 font-bold mt-1.5">هر پیامک <?= number_format(round($pack100Price/100)) ?> ت — سود آسنا: <?= number_format($pack100Price - 15000) ?> ت</p>
                </div>

                <!-- Package 500 Price -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">قیمت بسته ۵۰۰ پیامک (تومان):</label>
                    <input type="number" name="sms_pack_500_price" value="<?= $pack500Price ?>" min="20000" step="5000" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-bold focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <p class="text-[10px] text-emerald-700 font-bold mt-1.5">هر پیامک <?= number_format(round($pack500Price/500)) ?> ت — سود آسنا: <?= number_format($pack500Price - 75000) ?> ت</p>
                </div>

                <!-- Package 1000 Price -->
                <div>
                    <label class="block text-xs font-bold text-primary mb-2">قیمت بسته ۱۰۰۰ پیامک طلایی (تومان):</label>
                    <input type="number" name="sms_pack_1000_price" value="<?= $pack1000Price ?>" min="50000" step="5000" class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface text-xs font-bold focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <p class="text-[10px] text-emerald-700 font-bold mt-1.5">هر پیامک <?= number_format(round($pack100Price/1000 * 0.8)) ?> ت — سود آسنا: <?= number_format($pack1000Price - 150000) ?> ت</p>
                </div>

                <!-- Sandbox Mode Toggle -->
                <div class="flex items-center gap-3 pt-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="melipayamak_sandbox" value="1" <?= $mSandbox === '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">حالت شبیه‌ساز (Sandbox / Mock)</span>
                        <span class="text-[10px] text-slate-500">در صورت فعال بودن، پیامک به صورت آزمایشی در لاگ ثبت می‌شود.</span>
                    </div>
                </div>
            </div>

            <!-- Profit & Revenue Margin Analytics Box -->
            <div class="mt-6 bg-gradient-to-r from-emerald-50 via-teal-50 to-blue-50 border border-emerald-200/80 rounded-2xl p-5">
                <div class="flex items-center justify-between gap-3 mb-3 pb-2 border-b border-emerald-200/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-700 text-lg">monetization_on</span>
                        <h4 class="text-xs font-black text-emerald-900">تحلیل حاشیه سود اختصاصی پلتفرم آسنا از فروش پیامک به مراکز و پزشکان</h4>
                    </div>
                    <span class="text-[10px] font-extrabold bg-emerald-600 text-white px-2.5 py-0.5 rounded-full">سود ناخالص تا ۵۰۰٪+</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="bg-white/80 p-3 rounded-xl border border-emerald-100 shadow-2xs">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">بسته ۱۰۰ پیامک (<?= number_format($pack100Price) ?> ت)</div>
                        <div class="flex justify-between items-center text-[10px] text-slate-600 mb-1">
                            <span>هزینه خرید ملی‌پیامک:</span>
                            <span class="font-mono">۱۵,۰۰۰ ت</span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-emerald-700 pt-1 border-t border-slate-100">
                            <span>سود خالص آسنا:</span>
                            <span class="font-mono text-sm">+<?= number_format($pack100Price - 15000) ?> ت</span>
                        </div>
                    </div>
                    <div class="bg-white/80 p-3 rounded-xl border border-emerald-100 shadow-2xs">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">بسته ۵۰۰ پیامک (<?= number_format($pack500Price) ?> ت)</div>
                        <div class="flex justify-between items-center text-[10px] text-slate-600 mb-1">
                            <span>هزینه خرید ملی‌پیامک:</span>
                            <span class="font-mono">۷۵,۰۰۰ ت</span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-emerald-700 pt-1 border-t border-slate-100">
                            <span>سود خالص آسنا:</span>
                            <span class="font-mono text-sm">+<?= number_format($pack500Price - 75000) ?> ت</span>
                        </div>
                    </div>
                    <div class="bg-white/80 p-3 rounded-xl border border-emerald-100 shadow-2xs">
                        <div class="text-[11px] text-slate-500 font-bold mb-1">بسته ۱۰۰۰ پیامک طلایی (<?= number_format($pack1000Price) ?> ت)</div>
                        <div class="flex justify-between items-center text-[10px] text-slate-600 mb-1">
                            <span>هزینه خرید ملی‌پیامک:</span>
                            <span class="font-mono">۱۵۰,۰۰۰ ت</span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-emerald-700 pt-1 border-t border-slate-100">
                            <span>سود خالص آسنا:</span>
                            <span class="font-mono text-sm">+<?= number_format($pack1000Price - 150000) ?> ت</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/30 flex justify-between items-center">
                <span class="text-xs text-slate-500">اطلاعات در جدول تنظیمات پایگاه‌داده با اولویت بالاتر از .env ذخیره می‌شوند.</span>
                <button type="submit" class="bg-primary hover:bg-primary-container text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>ذخیره مشخصات وب‌سرویس ملی‌پیامک</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Configuration Form (Col 7) -->
        <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
                <span class="material-symbols-outlined text-secondary-container text-[26px]">manage_accounts</span>
                <div>
                    <h3 class="font-bold text-primary text-lg">شماره‌های اعلان و تنظیمات خودکار</h3>
                    <p class="text-xs text-on-surface-variant">تعیین شماره مدیران برای دریافت پیامک سفارشات و نوبت‌ها</p>
                </div>
            </div>

            <form method="POST" action="sms_settings.php" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">

                <div>
                    <label class="block text-sm font-bold text-primary mb-2">
                        شماره‌های موبایل مدیران جهت دریافت پیامک:
                    </label>
                    <textarea name="admin_notification_phones" rows="2" class="w-full p-3.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono text-left focus:ring-2 focus:ring-secondary-container outline-none transition-all" placeholder="09146676978, 09123456789" dir="ltr"><?= htmlspecialchars($adminNotificationPhones) ?></textarea>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        می‌توانید چند شماره موبایل را با کاما (,) یا فاصله جدا کنید. هنگام ثبت سفارش یا نوبت جدید، به همه این شماره‌ها پیامک ارسال خواهد شد.
                    </p>

                    <!-- Active Phone Chips -->
                    <?php if (!empty($phoneList)): ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php foreach ($phoneList as $p): ?>
                            <span class="inline-flex items-center gap-1.5 bg-primary/5 text-primary text-xs font-bold px-3 py-1.5 rounded-lg border border-primary/10" dir="ltr">
                                <span class="material-symbols-outlined text-[14px]">phone_iphone</span>
                                <span><?= htmlspecialchars($p) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-4 border-t border-outline-variant/30 space-y-4">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">رویدادهای ارسال پیامک خودکار</p>

                    <!-- Toggle 1: Order Alert to Admin -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ارسال پیامک به مدیر هنگام سفارش جدید</p>
                                <p class="text-xs text-on-surface-variant">به محض پرداخت موفق هر سبد خرید، شماره سفارش و مبلغ به مدیر پیامک می‌شود.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_order" value="1" <?= $adminSmsOnOrder === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-secondary-container rounded cursor-pointer">
                    </label>

                    <!-- Toggle 2: Booking Alert to Admin -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ارسال پیامک به مدیر هنگام رزرو نوبت</p>
                                <p class="text-xs text-on-surface-variant">اطلاع‌رسانی به مدیر سیستم در هنگام ثبت ویزیت جدید کلینیک.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="admin_sms_on_booking" value="1" <?= $adminSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-secondary-container rounded cursor-pointer">
                    </label>

                    <!-- Toggle 3: Booking Alert to Doctor -->
                    <label class="flex items-center justify-between p-4 rounded-xl bg-surface-container-low border border-outline-variant/30 hover:border-secondary-container/50 cursor-pointer transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-secondary-container/10 text-secondary-container flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">stethoscope</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-primary">ارسال پیامک مستقیم به پزشک معالج</p>
                                <p class="text-xs text-on-surface-variant">هنگامی که بیماری نوبتی با پزشک ثبت می‌کند، پیامک حاوی ساعت و تاریخ به شماره پزشک ارسال می‌شود.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="doctor_sms_on_booking" value="1" <?= $doctorSmsOnBooking === '1' ? 'checked' : '' ?> class="w-5 h-5 accent-secondary-container rounded cursor-pointer">
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-secondary-container hover:bg-secondary-container/90 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-secondary-container/20 active:scale-[0.99] transition-all">
                        <span class="material-symbols-outlined">save</span>
                        <span>ذخیره تغییرات تنظیمات پیامک</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live SMS Tester (Col 5) -->
        <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
                    <span class="material-symbols-outlined text-emerald-600 text-[26px]">send_to_mobile</span>
                    <div>
                        <h3 class="font-bold text-primary text-lg">ابزار تست زنده ارسال پیامک</h3>
                        <p class="text-xs text-on-surface-variant">بررسی صحت اتصال به خطوط خدماتی ملی‌پیامک</p>
                    </div>
                </div>

                <form method="POST" action="sms_settings.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="test_sms">

                    <div>
                        <label class="block text-sm font-bold text-primary mb-2">شماره موبایل گیرنده تست:</label>
                        <div class="relative">
                            <input type="text" name="test_phone" value="<?= htmlspecialchars($phoneList[0] ?? '09146676978') ?>" class="w-full p-3 pl-10 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono text-left focus:ring-2 focus:ring-emerald-500 outline-none transition-all" placeholder="09123456789" dir="ltr" required>
                            <span class="material-symbols-outlined absolute left-3 top-3 text-outline text-[20px]">phone_android</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-primary mb-2">نوع پیامک تستی:</label>
                        <select name="test_type" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold text-primary focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                            <option value="direct">پیامک مستقیم عمومی (Direct SMS)</option>
                            <option value="otp">تست کد اعتبارسنجی OTP</option>
                            <option value="admin_order">تست پیامک هشدار سفارش جدید مدیر</option>
                            <option value="doctor_booking">تست پیامک رزرو نوبت به پزشک</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-primary/20 active:scale-[0.99] transition-all">
                        <span class="material-symbols-outlined">rocket_launch</span>
                        <span>ارسال پیامک آزمایشی فوری</span>
                    </button>
                </form>

                <!-- Help Tip Box -->
                <div class="mt-6 p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-xs leading-relaxed">
                    <div class="flex items-center gap-1.5 font-bold mb-1 text-amber-800">
                        <span class="material-symbols-outlined text-[16px]">info</span>
                        <span>نکته مهم خطوط خدماتی اشتراکی:</span>
                    </div>
                    چنانچه شناسه‌های پترن (Body ID) در پنل ملی‌پیامک در انتظار تایید باشند، سامانه به طور خودکار پیامک را از طریق متد خط مستقیم ارسال خواهد کرد تا هیچ اعلانی در سیستم متوقف نماند.
                </div>
            </div>
        </div>
    </div>

    <!-- Patterns & Variables Directory Table -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-6 lg:p-8 stat-card-shadow">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6 pb-4 border-b border-outline-variant/30">
            <div>
                <h3 class="font-bold text-primary text-lg">کاتالوگ الگوها و پترن‌های سامانه (Melipayamak Patterns)</h3>
                <p class="text-xs text-on-surface-variant">قالب‌های استاندارد خط خدماتی اشتراکی ثبت‌شده جهت عبور از بلک‌لیست مخابرات</p>
            </div>
            <a href="https://login.melipayamak.com/?module=ShareService" target="_blank" class="text-xs font-bold text-secondary-container hover:underline flex items-center gap-1">
                <span>ثبت پترن در سامانه ملی‌پیامک</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant/40">
                        <th class="p-3.5 rounded-r-xl">عنوان الگو</th>
                        <th class="p-3.5">مخاطب</th>
                        <th class="p-3.5">شناسه پترن (Body ID)</th>
                        <th class="p-3.5">متغیرها</th>
                        <th class="p-3.5 rounded-l-xl">متن مصوب / نمونه</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($patterns as $pat): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="p-3.5 font-bold text-primary"><?= htmlspecialchars($pat['name']) ?></td>
                        <td class="p-3.5">
                            <span class="bg-primary/5 text-primary font-bold px-2.5 py-1 rounded-md text-[11px]">
                                <?= htmlspecialchars($pat['target']) ?>
                            </span>
                        </td>
                        <td class="p-3.5 font-mono text-left" dir="ltr">
                            <span class="<?= $pat['body_id'] !== '12345' ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-slate-100 text-slate-500' ?> px-2.5 py-1 rounded text-[11px]">
                                <?= htmlspecialchars($pat['body_id']) ?>
                            </span>
                        </td>
                        <td class="p-3.5 font-mono text-slate-600" dir="ltr"><?= htmlspecialchars($pat['vars']) ?></td>
                        <td class="p-3.5 text-slate-700 leading-relaxed font-sans max-w-md">
                            <div class="bg-surface-container-low p-2 rounded-lg text-[11px] border border-outline-variant/20 whitespace-pre-line">
                                <?= htmlspecialchars($pat['sample']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Diagnostic Logs Section -->
    <?php
    $logFile = dirname(__DIR__, 2) . '/logs/sms.log';
    $recentLogs = [];
    if (file_exists($logFile) && is_readable($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $slice = array_slice($lines, -15);
            $slice = array_reverse($slice);
            foreach ($slice as $l) {
                $decoded = json_decode($l, true);
                if ($decoded) $recentLogs[] = $decoded;
            }
        }
    }
    ?>
    <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant/30 mt-6">
        <div class="flex items-center justify-between mb-4 border-b border-outline-variant/20 pb-4">
            <div>
                <h3 class="font-bold text-primary text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">history_edu</span>
                    لاگ‌های تشخیصی ارسال پیامک (SMS Diagnostic Logs)
                </h3>
                <p class="text-xs text-on-surface-variant">رهگیری زنده پاسخ درگاه، کدهای خطا، متغیرهای ارسالی و وضعیت تحویل</p>
            </div>
            <span class="text-xs font-mono bg-surface-container-low px-3 py-1 rounded-full text-outline">
                <?= count($recentLogs) ?> رویداد اخیر
            </span>
        </div>

        <?php if (empty($recentLogs)): ?>
            <div class="p-8 text-center text-on-surface-variant text-sm bg-surface-container-low/50 rounded-xl">
                هنوز رویدادی در فایل لاگ ثبت نشده است. با ارسال تست از فرم بالا، اولین لاگ ایجاد خواهد شد.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant/40">
                            <th class="p-3 rounded-r-xl">زمان</th>
                            <th class="p-3">عملیات</th>
                            <th class="p-3">شماره گیرنده</th>
                            <th class="p-3">وضعیت HTTP</th>
                            <th class="p-3">تحلیل و تفسیر درگاه</th>
                            <th class="p-3 rounded-l-xl">جزئیات فنی</th>
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
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono <?= ($log['http_code'] ?? 0) === 200 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
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
