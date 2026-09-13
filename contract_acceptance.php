<?php
/**
 * ASENA Enterprise - Automated Non-Bypassable Contract Acceptance Gate
 * Electronic Signature & Multi-Role Compliance Interface
 * Compliant with Iranian E-Commerce Law (Articles 6 & 12)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/AuthGuard.php';
require_once __DIR__ . '/includes/ContractService.php';
require_once __DIR__ . '/includes/SmsService.php';

// Authenticate session without recurring contract check
$currentUser = AuthGuard::requireAuth(null, false);
$userId      = (int)$currentUser['id'];
$userRole    = $currentUser['role'] ?? 'user';
$rawRole     = ContractService::normalizeRole($userRole);

// Determine return URL safely
$rawReturnUrl = $_GET['return_url'] ?? ($_POST['return_url'] ?? '');
if (!empty($rawReturnUrl) && !preg_match('#(contract_acceptance|logout\.php)#i', $rawReturnUrl)) {
    $returnUrl = $rawReturnUrl;
} else {
    // Default dashboard per role
    switch ($rawRole) {
        case 'organization':
            $returnUrl = 'organization/index.php';
            break;
        case 'doctor':
            $returnUrl = 'doctor/index.php';
            break;
        case 'pharmacist':
            $returnUrl = 'pharmacist/index.php';
            break;
        case 'seller':
            $returnUrl = 'seller/index.php';
            break;
        default:
            $returnUrl = 'index.php';
            break;
    }
}

$contractService = new ContractService($pdo);

// If already accepted, redirect immediately
if ($contractService->hasAcceptedCurrentContract($userId, $userRole)) {
    header("Location: " . $returnUrl);
    exit;
}

$contract = ContractService::getContractData($rawRole);
$errorMsg = '';
$successMsg = '';

// Handle Contract Signing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthGuard::verifyCsrf()) {
        $errorMsg = 'اعتبارسنجی امنیتی نشست منقضی شده است. لطفاً صفحه را مجدداً بارگذاری فرمایید.';
    } elseif (empty($_POST['confirm_agreement'])) {
        $errorMsg = 'جهت ادامه و استفاده از امکانات سامانه، علامت‌زدن کادر پذیرش کلیه شرایط و قوانین قرارداد الزامی است.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

        $signResult = $contractService->recordAcceptance($userId, $userRole, $ip, $ua);

        if ($signResult['success']) {
            // Optional Melipayamak confirmation SMS
            if (!empty($currentUser['phone'])) {
                try {
                    $sms = new SmsService();
                    $shortHash = substr($signResult['signature_hash'], 0, 8);
                    $smsMsg = "آسنا: قرارداد همکاری الکترونیک شما (کد رهگیری: {$shortHash}) با موفقیت ثبت گردید.";
                    $sms->send($currentUser['phone'], $smsMsg);
                } catch (Throwable $t) {}
            }

            header("Location: " . $returnUrl);
            exit;
        } else {
            $errorMsg = 'خطایی در ثبت امضای الکترونیک رخ داد. لطفاً مجدداً تلاش نمایید.';
        }
    }
}

$page_title = 'تأیید و امضای الکترونیک قرارداد رسمی | سامانه جامع آسنا';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Ambient Glow Background -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-4xl mx-auto relative z-10">

        <!-- Top Alert Notice (Non-Bypassable Badge) -->
        <div class="bg-amber-500/15 border border-amber-500/30 rounded-2xl p-4 mb-6 flex items-start gap-3 backdrop-blur-md">
            <span class="material-symbols-outlined text-amber-400 text-2xl shrink-0 mt-0.5">verified_user</span>
            <div class="text-xs text-amber-200/90 leading-relaxed">
                <strong class="text-amber-300 block mb-0.5 text-sm">احراز هویت حقوقی و الزام پذیرش قرارداد نسخه <?= htmlspecialchars(ContractService::CURRENT_VERSION) ?></strong>
                طبق مواد ۶ و ۱۲ قانون تجارت الکترونیک، کلیه کاربران و ارائه‌دهندگان محترم جهت ادامه فعالیت، ثبت سفارش یا ورود به پنل تخصصی خود ملزم به مطالعه و ثبت امضای دیجیتال در توافق‌نامه خدمات می‌باشند. این قرارداد منافع و حقوق تجاری هر دو طرف را به صورت صددرصدی تضمین می‌نماید.
            </div>
        </div>

        <!-- Main Contract Container -->
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl shadow-2xl overflow-hidden backdrop-blur-xl">
            
            <!-- Header Banner -->
            <div class="p-6 sm:p-8 border-b border-slate-700/80 bg-gradient-to-r from-slate-800 via-slate-800/80 to-slate-900">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary/20 text-primary-light border border-primary/30 mb-2">
                            <span class="material-symbols-outlined text-xs">gavel</span>
                            طرف قرارداد: <?= htmlspecialchars($contract['role_fa']) ?>
                        </span>
                        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            <?= htmlspecialchars($contract['title']) ?>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1 leading-relaxed">
                            <?= htmlspecialchars($contract['summary']) ?>
                        </p>
                    </div>

                    <div class="text-left shrink-0 bg-slate-900/60 border border-slate-700 p-3 rounded-2xl">
                        <span class="text-[10px] text-slate-400 block font-mono">شناسه نسخه:</span>
                        <span class="text-xs font-bold text-amber-400 font-mono"><?= htmlspecialchars(ContractService::CURRENT_VERSION) ?></span>
                        <span class="text-[10px] text-emerald-400 block mt-0.5 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            معتبر و لازم‌الاجرا
                        </span>
                    </div>
                </div>
            </div>

            <!-- Error Notification -->
            <?php if (!empty($errorMsg)): ?>
                <div class="mx-6 sm:mx-8 mt-6 p-4 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-400">error</span>
                    <span><?= htmlspecialchars($errorMsg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Mutual Profit Highlights (سود دو طرفه) -->
            <div class="p-6 sm:p-8 border-b border-slate-700/80 bg-slate-850/40">
                <div class="text-center mb-6">
                    <span class="text-xs font-bold text-amber-400 bg-amber-400/10 px-3 py-1 rounded-full inline-block mb-1 border border-amber-400/20">
                        🤝 منافع و تضمین‌های دوطرفه پلتفرم و شما (Win-Win)
                    </span>
                    <h3 class="text-base font-bold text-white">این توافق‌نامه چگونه منافع تجاری و حقوقی شما را تضمین می‌کند؟</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- User / Provider Profit -->
                    <div class="bg-emerald-950/30 border border-emerald-500/30 rounded-2xl p-4 space-y-2.5">
                        <div class="flex items-center gap-2 font-bold text-emerald-300 text-sm border-b border-emerald-500/20 pb-2">
                            <span class="material-symbols-outlined text-emerald-400">verified</span>
                            <span>سود و پوشش‌های انحصاری برای شما (ارائه‌دهنده / کاربر):</span>
                        </div>
                        <ul class="space-y-2 text-slate-300">
                            <?php foreach ($contract['win_win']['profit_for_user'] as $pt): ?>
                                <li class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-emerald-400 text-sm shrink-0 mt-0.5">check_circle</span>
                                    <span><?= htmlspecialchars($pt) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Platform Profit & Safety -->
                    <div class="bg-blue-950/30 border border-blue-500/30 rounded-2xl p-4 space-y-2.5">
                        <div class="flex items-center gap-2 font-bold text-blue-300 text-sm border-b border-blue-500/20 pb-2">
                            <span class="material-symbols-outlined text-blue-400">shield</span>
                            <span>تعهدات، ایمنی و کارمزد عادلانه پلتفرم آسنا:</span>
                        </div>
                        <ul class="space-y-2 text-slate-300">
                            <?php foreach ($contract['win_win']['profit_for_platform'] as $pp): ?>
                                <li class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-blue-400 text-sm shrink-0 mt-0.5">task_alt</span>
                                    <span><?= htmlspecialchars($pp) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Full Legal Articles Box -->
            <div class="p-6 sm:p-8 space-y-4">
                <h4 class="text-sm font-bold text-slate-200 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base">article</span>
                    متن کامل مواد و شرایط قانونی قرارداد:
                </h4>

                <div class="max-h-72 overflow-y-auto space-y-4 p-4 rounded-2xl bg-slate-900/80 border border-slate-700/80 text-xs text-slate-300 leading-relaxed font-sans">
                    <?php foreach ($contract['articles'] as $art): ?>
                        <div class="p-3.5 rounded-xl bg-slate-800/50 border border-slate-700/50 space-y-1.5">
                            <div class="flex items-center gap-2 text-amber-400 font-bold">
                                <span class="w-5 h-5 rounded-md bg-amber-400/20 flex items-center justify-center text-[11px]"><?= $art['num'] ?></span>
                                <span>ماده <?= $art['num'] ?>: <?= htmlspecialchars($art['title']) ?></span>
                            </div>
                            <p class="text-slate-300 pr-7">
                                <?= htmlspecialchars($art['content']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pt-2 flex items-center justify-between text-xs text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-xs text-amber-400">policy</span>
                        <span>منطبق بر قانون تجارت الکترونیک، قانون حمایت از مصرف‌کننده، نظام دامپزشکی و سازمان غذا و دارو</span>
                    </span>
                    <a href="terms.php?role=<?= urlencode($rawRole) ?>" target="_blank" class="text-primary-light hover:underline font-bold flex items-center gap-1">
                        <span>مطالعه منشور مشروح قوانین و چاپ نسخه رسمی</span>
                        <span class="material-symbols-outlined text-xs">open_in_new</span>
                    </a>
                </div>
            </div>

            <!-- Digital Signature Form (Non-Bypassable Gate) -->
            <form method="POST" action="" class="p-6 sm:p-8 bg-slate-850 border-t border-slate-700/80 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">

                <!-- Signer Identity Preview -->
                <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-700/80 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400">
                    <div>
                        <span class="block text-[11px] text-slate-500">مشخصات امضاکننده:</span>
                        <strong class="text-white text-sm"><?= htmlspecialchars($currentUser['name'] ?? 'کاربر محترم') ?></strong>
                        <span class="text-slate-400 text-xs font-mono mr-2">(<?= htmlspecialchars($currentUser['phone'] ?? '') ?>)</span>
                    </div>

                    <div>
                        <span class="block text-[11px] text-slate-500">نشانی آی‌پی ثبت دیجیتال:</span>
                        <span class="text-amber-400 font-mono"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></span>
                    </div>

                    <div>
                        <span class="block text-[11px] text-slate-500">تاریخ و ساعت ثبت:</span>
                        <span class="text-slate-300 font-mono"><?= date('Y-m-d H:i:s') ?></span>
                    </div>
                </div>

                <!-- Mandatory Checkbox -->
                <div class="bg-primary/10 border border-primary/30 p-4 rounded-2xl">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" name="confirm_agreement" value="1" required class="w-5 h-5 mt-0.5 rounded text-primary focus:ring-primary border-slate-600 bg-slate-700 cursor-pointer">
                        <span class="text-xs text-slate-200 leading-relaxed">
                            اینجانب با مطالعه کامل کلیه مفاد و مندرجات فوق، <strong>قرارداد رسمی و ضوابط همکاری سامانه آسنا</strong> را با کمال میل و اراده آزاد پذیرفته و امضای الکترونیک آن را به منزله سند رسمی، قطعی و لازم‌الاجرا تأیید می‌نمایم.
                        </span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                    <a href="logout.php" class="text-xs text-rose-400 hover:text-rose-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">logout</span>
                        <span>انصراف و خروج از حساب کاربری</span>
                    </a>

                    <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-primary via-primary to-primary-light hover:brightness-110 text-white px-8 py-3.5 rounded-2xl font-bold text-sm shadow-xl shadow-primary/20 flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-98">
                        <span class="material-symbols-outlined text-lg">draw</span>
                        <span>ثبت امضای دیجیتال و ادامه فعالیت</span>
                    </button>
                </div>
            </form>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
