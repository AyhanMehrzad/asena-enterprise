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
        
        $taxRate = max(0, min(100, (float)($_POST['tax_rate_percent'] ?? 9)));
        $commissionRate = max(0, min(100, (float)($_POST['platform_commission_percent'] ?? 5)));
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

            $success = "تنظیمات حساب بانکی آسنا، مالیات و زمان‌بندی تسویه با موفقیت ذخیره شد.";
        }
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

$taxRate        = (float)get_setting($pdo, 'tax_rate_percent', 9);
$commissionRate = (float)get_setting($pdo, 'platform_commission_percent', 5);
$taxOnAppts     = get_setting($pdo, 'tax_on_appointments_enabled', '1');

$autoPayoutEnabled = get_setting($pdo, 'auto_payout_enabled', '1');
$autoPayoutDay     = (int)get_setting($pdo, 'auto_payout_day', 4);
$autoPayoutTime    = get_setting($pdo, 'auto_payout_time', '09:00');

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

<div class="space-y-8 max-w-[1400px] mx-auto p-4 lg:p-6 rtl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-[#1E293B] p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#001a48]/10 text-[#001a48] flex items-center justify-center font-black">
                <span class="material-symbols-outlined text-2xl">account_balance</span>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">تنظیمات خزانه‌داری آسنا، کارت بانکی، مالیات و تسویه خودکار</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">مدیریت شماره کارت و شبای آسنا، اعمال نرخ مالیات ۹٪، کارمزد ۵٪ و زمان‌بندی تسویه پنج‌شنبه‌ها ساعت ۹:۰۰ صبح تهران</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="payouts.php" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-1.5 transition">
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                <span>بازگشت به کنسول تسویه پایا</span>
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl flex items-center gap-3 font-bold text-xs shadow-sm">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl flex items-center gap-3 font-bold text-xs shadow-sm">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Visual Asena Card Preview & Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Visual Corporate Debit Card (Col 5) -->
        <div class="lg:col-span-5 flex flex-col justify-between">
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#001a48] via-[#002d72] to-[#1e3a8a] text-white shadow-xl relative overflow-hidden h-[230px] flex flex-col justify-between">
                <!-- Watermark Background Logo -->
                <div class="absolute -left-8 -bottom-8 opacity-10 pointer-events-none">
                    <span class="material-symbols-outlined text-[160px]">pets</span>
                </div>

                <div class="flex justify-between items-start z-10">
                    <div class="flex items-center gap-2.5">
                        <img src="../assets/images/logo.png" alt="آسنا" class="w-8 h-8 object-contain bg-white/10 rounded-lg p-1">
                        <span class="font-black text-sm tracking-wide">آسنا تجارت (حساب حقوقی)</span>
                    </div>
                    <span class="bg-white/15 px-3 py-1 rounded-full text-[11px] font-bold backdrop-blur-sm">
                        <?= htmlspecialchars($detectedBank) ?>
                    </span>
                </div>

                <!-- EMV Chip -->
                <div class="flex items-center gap-3 z-10">
                    <div class="w-10 h-8 rounded-md bg-gradient-to-r from-amber-300 to-amber-500 border border-amber-200 shadow-inner flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-900 text-sm">memory</span>
                    </div>
                    <span class="material-symbols-outlined text-white/50 text-xl">contactless</span>
                </div>

                <!-- Card Number -->
                <div class="z-10 dir-ltr text-center">
                    <span class="font-mono text-xl sm:text-2xl font-bold tracking-[0.2em] text-white drop-shadow">
                        <?= chunk_split(preg_replace('/[^\d]/', '', $adminCard), 4, ' ') ?>
                    </span>
                </div>

                <!-- Cardholder & Shaba -->
                <div class="flex justify-between items-end z-10 text-[11px]">
                    <div>
                        <span class="text-white/60 block text-[9px]">نام صاحب کارت:</span>
                        <span class="font-bold text-white"><?= htmlspecialchars($adminHolder) ?></span>
                    </div>
                    <div class="text-left dir-ltr font-mono text-[10px] text-white/80">
                        <?= htmlspecialchars($adminSheba) ?>
                    </div>
                </div>
            </div>

            <!-- Quick Metrics Grid -->
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1E293B] border border-slate-100 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] text-slate-500 block mb-1">آماده تسویه پنج‌شنبه پیش‌رو:</span>
                    <span class="text-lg font-black text-emerald-600 font-mono"><?= number_format($metrics['total_available_payout']) ?> تومان</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5"><?= $metrics['eligible_sellers_count'] ?> ذینفع واجد شرایط</span>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1E293B] border border-slate-100 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] text-slate-500 block mb-1">وجوه امانی مهلت ۷ روزه تست:</span>
                    <span class="text-lg font-black text-blue-600 font-mono"><?= number_format($metrics['total_pending_escrow']) ?> تومان</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5"><?= $metrics['active_in_inspection_count'] ?> مرسوله در بازه تست</span>
                </div>
            </div>
        </div>

        <!-- Settings Form (Col 7) -->
        <div class="lg:col-span-7 bg-white dark:bg-[#1E293B] border border-slate-100 dark:border-slate-800 rounded-3xl p-6 lg:p-8 shadow-sm">
            <form method="POST" action="finance_settings.php" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_finance_settings">

                <!-- Section 1: Bank Card & Shaba -->
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                        <span class="material-symbols-outlined text-[#001a48] dark:text-blue-400 text-xl">credit_card</span>
                        اطلاعات حساب بانکی و کارت متمرکز آسنا
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Card Number -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">شماره کارت بانکی آسنا (۱۶ رقم):</label>
                            <input type="text" name="admin_bank_card" id="adminBankCardInput" value="<?= htmlspecialchars($adminCard) ?>" maxlength="19" placeholder="6037-9911-XXXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-center">
                            <p class="text-[10px] text-slate-400 mt-1">شماره کارت در سربرگ رسیدهای واریز و فرم‌های واریزی درج خواهد شد.</p>
                        </div>

                        <!-- Bank Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نام بانک عامل:</label>
                            <input type="text" name="admin_bank_name" value="<?= htmlspecialchars($adminBank) ?>" placeholder="e.g. بانک سامان یا بانک ملت" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">بانک متصل به شماره کارت و شبای آسنا</p>
                        </div>

                        <!-- Shaba / IBAN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">شماره شبا خزانه‌داری مبدا (IBAN):</label>
                            <input type="text" name="admin_bank_sheba" value="<?= htmlspecialchars($adminSheba) ?>" maxlength="26" placeholder="IR120560..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-left">
                            <p class="text-[10px] text-slate-400 mt-1">شبای ۲۴ رقمی متمرکز شرکت با پیشوند IR</p>
                        </div>

                        <!-- Account Holder -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نام کامل صاحب حساب / شرکت:</label>
                            <input type="text" name="admin_bank_holder" value="<?= htmlspecialchars($adminHolder) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">عنوان درج‌شده در حساب بانکی حقوقی</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Taxes & Platform Commission -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                        <span class="material-symbols-outlined text-[#fd8100] text-xl">percent</span>
                        محاسبه مالیات بر ارزش افزوده و سهم کارمزد پلتفرم
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tax Rate -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نرخ مالیات بر ارزش افزوده برای خریدار (درصد):</label>
                            <div class="relative">
                                <input type="number" name="tax_rate_percent" value="<?= $taxRate ?>" step="0.5" min="0" max="25" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none pl-8">
                                <span class="absolute left-3 top-2.5 text-slate-400 text-xs font-bold">٪</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">پیش‌فرض ۹٪ یا نرخ قانونی ۱۰٪ سال ۱۴۰۳؛ این مبلغ در سبد خرید خریدار اضافه و نمایش داده می‌شود.</p>
                        </div>

                        <!-- Platform Commission -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">کارمزد پلتفرم آسنا از فروشنده / پزشک (درصد):</label>
                            <div class="relative">
                                <input type="number" name="platform_commission_percent" value="<?= $commissionRate ?>" step="0.5" min="0" max="50" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none pl-8">
                                <span class="absolute left-3 top-2.5 text-slate-400 text-xs font-bold">٪</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">پیش‌فرض ۵٪؛ از مبلغ فروشنده کسر می‌شود و برای خریدار پنهان است.</p>
                        </div>
                    </div>

                    <!-- Medical Exemption Toggle -->
                    <div class="mt-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                        <input type="checkbox" name="tax_on_appointments_enabled" id="taxAppts" value="1" <?= $taxOnAppts === '1' ? 'checked' : '' ?> class="mt-1 w-4 h-4 rounded text-primary focus:ring-primary">
                        <div>
                            <label for="taxAppts" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                                اعمال مالیات ارزش افزوده روی نوبت‌های ویزیت دامپزشکی
                            </label>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                نکته حقوقی: بر اساس بند ۹ ماده ۹ قانون مالیات بر ارزش افزوده، خدمات درمانی و پزشکی دارای معافیت هستند. در صورت فعال بودن این گزینه، مالیات ارزش افزوده به مبلغ پرداختی ویزیت بیمار اضافه خواهد شد.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Automated Payout Scheduling -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                        <span class="material-symbols-outlined text-emerald-600 text-xl">schedule</span>
                        زمان‌بندی واریز خودکار هفتگی پایا (الگوی بازارگاه دیجی‌کالا)
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Auto Payout Enable -->
                        <div class="flex items-center gap-3 pt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="auto_payout_enabled" value="1" <?= $autoPayoutEnabled === '1' ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">تسویه هفتگی خودکار</span>
                                <span class="text-[10px] text-slate-400">اجرا در موعد مقرر</span>
                            </div>
                        </div>

                        <!-- Day of Week -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">روز تسویه هفتگی:</label>
                            <select name="auto_payout_day" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold focus:border-primary focus:bg-white outline-none">
                                <option value="4" <?= $autoPayoutDay === 4 ? 'selected' : '' ?>>پنج‌شنبه‌ها (پیش‌فرض شاپرک و پایا)</option>
                                <option value="3" <?= $autoPayoutDay === 3 ? 'selected' : '' ?>>چهارشنبه‌ها</option>
                                <option value="0" <?= $autoPayoutDay === 0 ? 'selected' : '' ?>>یکشنبه‌ها</option>
                            </select>
                        </div>

                        <!-- Time of Day -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">ساعت تسویه (به وقت تهران):</label>
                            <input type="text" name="auto_payout_time" value="<?= htmlspecialchars($autoPayoutTime) ?>" placeholder="09:00" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-mono font-bold focus:border-primary focus:bg-white outline-none dir-ltr text-center">
                            <p class="text-[10px] text-slate-400 mt-1">ساعت ۰۹:۰۰ صبح (آغاز اولین سیکل روزانه پایا)</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <span class="text-xs text-slate-500">تمامی تغییرات بلافاصله بر روی رسیدها، درگاه پرداخت و زمان‌بندی اعمال می‌شوند.</span>
                    <button type="submit" class="w-full sm:w-auto bg-[#001a48] hover:bg-[#002d72] text-white px-8 py-3 rounded-xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span>
                        <span>ذخیره تنظیمات خزانه‌داری آسنا</span>
                    </button>
                </div>
            </form>

            <!-- Test Trigger Button -->
            <div class="mt-8 pt-6 border-t border-dashed border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl">
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-white">اجرای فوری و شبیه‌سازی تسویه هفتگی پایا:</h4>
                    <p class="text-[10px] text-slate-500 mt-0.5">آزادسازی مهلت‌های گذشته، جمع‌آوری مبالغ آماده و صدور فایل پایا و پیامک بدون در نظر گرفتن شرط ساعت</p>
                </div>
                <form method="POST" action="finance_settings.php" onsubmit="return confirm('آیا از اجرای فوری تسویه حساب برای کلیه ذینفعان اطمینان دارید؟');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="force_test_payout">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">play_arrow</span>
                        <span>اجرای آزمایشی تسویه پایا</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-format bank card input
document.getElementById('adminBankCardInput')?.addEventListener('input', function(e) {
    let val = e.target.value.replace(/\D/g, '').substring(0, 16);
    let chunks = val.match(/.{1,4}/g);
    e.target.value = chunks ? chunks.join('-') : val;
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
