<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SmsService.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';

// Capture and sanitize return URL
$returnUrl = trim($_GET['return_url'] ?? $_POST['return_url'] ?? '');
if (!empty($returnUrl)) {
    if (preg_match('#^(https?:)?//#i', $returnUrl) || !preg_match('#^[a-zA-Z0-9_\-\./\?=&%]+$#', $returnUrl)) {
        $returnUrl = '';
    }
}
$returnQuery = !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '';
$clientIp = get_client_ip();

// Generate OAuth URLs
$google_oauth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'response_type' => 'code',
    'client_id' => defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '',
    'redirect_uri' => defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : '',
    'scope' => 'email profile',
    'access_type' => 'online'
]);

$apple_oauth_url = "https://appleid.apple.com/auth/authorize?" . http_build_query([
    'response_type' => 'code id_token',
    'client_id' => defined('APPLE_CLIENT_ID') ? APPLE_CLIENT_ID : '',
    'redirect_uri' => defined('APPLE_REDIRECT_URI') ? APPLE_REDIRECT_URI : '',
    'scope' => 'name email',
    'response_mode' => 'form_post'
]);

// Cancel handlers
if (isset($_GET['cancel_signup'])) {
    unset($_SESSION['signup_data']);
    header("Location: login.php?tab=signup" . (!empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : ''));
    exit;
}

if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['otp_login_data']);
    header("Location: login.php?tab=otp" . (!empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : ''));
    exit;
}

$error = '';
$success = '';
if (isset($_SESSION['login_success'])) {
    $success = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}

// Active tab determination
$activeTab = $_GET['tab'] ?? 'password';
if (isset($_SESSION['otp_login_data'])) {
    $activeTab = 'otp';
} elseif (isset($_SESSION['signup_data'])) {
    $activeTab = 'signup';
}
if (!in_array($activeTab, ['password', 'otp', 'signup'])) {
    $activeTab = 'password';
}

// Helper: redirect authenticated user based on role or returnUrl
function redirectAfterLogin(?array $user, string $returnUrl = ''): void {
    if (!empty($returnUrl)) {
        header("Location: " . $returnUrl);
        exit;
    }
    $role = $user['role'] ?? 'user';
    if (in_array($role, ['organization', 'organization_manager'])) {
        header("Location: organization/index.php");
    } elseif ($role === 'seller') {
        header("Location: seller/index.php");
    } elseif ($role === 'doctor') {
        header("Location: doctor/index.php");
    } elseif (in_array($role, ['pharmacist', 'pharmacy'])) {
        header("Location: pharmacist/index.php");
    } elseif ($role === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ----------------------------------------------------
    // 1. Password Login
    // ----------------------------------------------------
    if ($action === 'login_password') {
        $activeTab = 'password';
        $rawPhone = trim($_POST['phone'] ?? '');
        $phone = SmsService::normalizePhone($rawPhone);
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            $error = 'لطفاً شماره موبایل و رمز عبور خود را وارد کنید.';
        } else {
            $rate_error = check_rate_limit($pdo, $clientIp, $phone);
            if ($rate_error) {
                $error = $rate_error;
            } else {
                $stmt = $pdo->prepare("SELECT id, phone, role, password, name FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_role'] = $user['role'] ?: 'user';
                    $_SESSION['role'] = $user['role'] ?: 'user';
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['password_hash'] = hash('sha256', $user['password']);
                    $_SESSION['contract_accepted_version'] = 'v2.0-2026';
                    
                    if (!empty($_POST['remember'])) {
                        require_once __DIR__ . '/includes/AuthGuard.php';
                        AuthGuard::setRememberCookie((int)$user['id'], $user['phone'] ?? $phone, $user['password'] ?? '', 30);
                    }

                    $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$clientIp]);
                    
                    redirectAfterLogin($user, $returnUrl);
                } else {
                    $error = 'شماره موبایل یا رمز عبور وارد شده نادرست است.';
                }
            }
        }
    }
    
    // ----------------------------------------------------
    // 2. OTP SMS Login - Send OTP Code
    // ----------------------------------------------------
    elseif ($action === 'send_otp') {
        $activeTab = 'otp';
        $rawPhone = trim($_POST['phone'] ?? '');
        $phone = SmsService::normalizePhone($rawPhone);
        $remember = !empty($_POST['remember']) ? 1 : 0;
        
        if (empty($phone) || strlen($phone) !== 11) {
            $error = 'شماره موبایل وارد شده نامعتبر است (مثال: 09123456789).';
        } else {
            try {
                $rate_error = check_rate_limit($pdo, $clientIp, $phone);
                if ($rate_error) {
                    $error = $rate_error;
                } else {
                    $sms = new SmsService();
                    $otp = sprintf("%06d", random_int(100000, 999999));
                    $sent = $sms->sendOtp($phone, $otp);
                    
                    if ($sent) {
                        $_SESSION['otp_login_data'] = [
                            'phone'      => $phone,
                            'otp'        => (string)$otp,
                            'expires_at' => time() + 180, // 3 minutes validity
                            'remember'   => $remember
                        ];
                        $success = 'کد تأیید ۶ رقمی با موفقیت برای شماره ' . htmlspecialchars($phone) . ' پیامک شد.';
                    } else {
                        $error = 'خطا در ارسال پیامک: ' . ($sms->getLastError() ?: 'لطفاً دقایقی دیگر مجدداً تلاش نمایید.');
                    }
                }
            } catch (Throwable $e) {
                error_log('[send_otp error] ' . $e->getMessage());
                $error = 'خطایی در ارسال پیامک رخ داد. لطفاً مجدداً تلاش فرمایید.';
            }
        }
    }
    
    // ----------------------------------------------------
    // 3. OTP SMS Login - Verify Code & Login/Register
    // ----------------------------------------------------
    elseif ($action === 'verify_otp') {
        $activeTab = 'otp';
        $otp = SmsService::sanitizeCode($_POST['otp'] ?? '');
        
        if (empty($_SESSION['otp_login_data'])) {
            $error = 'جلسه تأیید پیامکی منقضی شده است. لطفاً شماره خود را مجدداً وارد کنید.';
        } elseif (time() > ($_SESSION['otp_login_data']['expires_at'] ?? 0)) {
            $error = 'کد تأیید منقضی گردید. لطفاً کد جدید دریافت نمایید.';
        } elseif ($otp !== $_SESSION['otp_login_data']['otp']) {
            $error = 'کد تأیید وارد شده اشتباه است. لطفاً دوباره بررسی کنید.';
        } else {
            $phone = $_SESSION['otp_login_data']['phone'];
            $isRemember = !empty($_POST['remember']) || !empty($_SESSION['otp_login_data']['remember']);
            unset($_SESSION['otp_login_data']);
            
            try {
                $stmt = $pdo->prepare("SELECT id, phone, role, password, name FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    // Seamlessly auto-register regular pet parent user
                    $dummyPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $newUserId = 0;
                    try {
                        $ins = $pdo->prepare("
                            INSERT INTO users (phone, name, password, role, verification_status, loyalty_points, created_at)
                            VALUES (?, 'کاربر آسنا', ?, 'user', 'approved', 50, NOW())
                        ");
                        $ins->execute([$phone, $dummyPassword]);
                        $newUserId = (int)$pdo->lastInsertId();
                    } catch (Throwable $insErr) {
                        // Resilient fallback with minimal required columns
                        $ins = $pdo->prepare("
                            INSERT INTO users (phone, name, password, role)
                            VALUES (?, 'کاربر آسنا', ?, 'user')
                        ");
                        $ins->execute([$phone, $dummyPassword]);
                        $newUserId = (int)$pdo->lastInsertId();
                    }
                    
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['role'] = 'user';
                    $_SESSION['name'] = 'کاربر آسنا';
                    $_SESSION['user_name'] = 'کاربر آسنا';
                    $_SESSION['password_hash'] = hash('sha256', $dummyPassword);
                    $_SESSION['contract_accepted_version'] = 'v2.0-2026';

                    if ($isRemember) {
                        require_once __DIR__ . '/includes/AuthGuard.php';
                        AuthGuard::setRememberCookie($newUserId, $phone, $dummyPassword, 30);
                    }
                    
                    redirectAfterLogin(['id' => $newUserId, 'phone' => $phone, 'role' => 'user'], $returnUrl);
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_role'] = $user['role'] ?: 'user';
                    $_SESSION['role'] = $user['role'] ?: 'user';
                    $_SESSION['name'] = $user['name'] ?: 'کاربر آسنا';
                    $_SESSION['user_name'] = $user['name'] ?: 'کاربر آسنا';
                    $_SESSION['password_hash'] = hash('sha256', $user['password'] ?? '');
                    $_SESSION['contract_accepted_version'] = 'v2.0-2026';

                    if ($isRemember) {
                        require_once __DIR__ . '/includes/AuthGuard.php';
                        AuthGuard::setRememberCookie((int)$user['id'], $user['phone'] ?? $phone, $user['password'] ?? '', 30);
                    }
                    
                    try {
                        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$clientIp]);
                    } catch (Throwable $ignored) {}
                    
                    redirectAfterLogin($user, $returnUrl);
                }
            } catch (Throwable $e) {
                error_log('[verify_otp error] ' . $e->getMessage());
                $error = 'خطایی در ورود به سامانه رخ داد: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
    
    // ----------------------------------------------------
    // 4. Fast User Signup - Request OTP
    // ----------------------------------------------------
    elseif ($action === 'signup') {
        $activeTab = 'signup';
        $name = trim($_POST['name'] ?? '');
        $rawPhone = trim($_POST['phone'] ?? '');
        $phone = SmsService::normalizePhone($rawPhone);
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($phone) || empty($password)) {
            $error = 'لطفاً نام، شماره موبایل و رمز عبور را وارد نمایید.';
        } elseif (strlen($password) < 6) {
            $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetchColumn()) {
                $error = 'این شماره موبایل قبلاً در آسنا ثبت‌نام شده است. لطفاً وارد شوید.';
            } else {
                $rate_error = check_rate_limit($pdo, $clientIp, $phone);
                if ($rate_error) {
                    $error = $rate_error;
                } else {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $sms = new SmsService();
                    $sent = $sms->sendOtp($phone, $otp);

                    if (!$sent) {
                        $error = 'خطا در ارسال پیامک فعال‌سازی: ' . ($sms->getLastError() ?: 'عدم دسترسی به درگاه پیامک.');
                    } else {
                        $_SESSION['signup_data'] = [
                            'name'       => $name,
                            'phone'      => $phone,
                            'password'   => $password,
                            'otp'        => $otp,
                            'expires_at' => time() + 180,
                            'remember'   => !empty($_POST['remember'])
                        ];
                    }
                }
            }
        }
    }
    
    // ----------------------------------------------------
    // 5. Fast User Signup - Verify OTP & Create Account
    // ----------------------------------------------------
    elseif ($action === 'verify_signup') {
        $activeTab = 'signup';
        $otp = SmsService::sanitizeCode($_POST['otp'] ?? '');
        
        if (empty($_SESSION['signup_data'])) {
            $error = 'جلسه ثبت‌نام منقضی شده است. لطفاً دوباره اطلاعات خود را وارد کنید.';
        } elseif (time() > ($_SESSION['signup_data']['expires_at'] ?? 0)) {
            $error = 'کد تأیید منقضی گردید. لطفاً کد جدید دریافت نمایید.';
        } elseif ($otp !== $_SESSION['signup_data']['otp']) {
            $error = 'کد تأیید وارد شده نادرست است.';
        } else {
            $name = $_SESSION['signup_data']['name'];
            $phone = $_SESSION['signup_data']['phone'];
            $password = $_SESSION['signup_data']['password'];
            $isRemember = !empty($_POST['remember']) || !empty($_SESSION['signup_data']['remember']);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            unset($_SESSION['signup_data']);
            
            $ins = $pdo->prepare("
                INSERT INTO users (phone, name, password, role, verification_status, loyalty_points, created_at)
                VALUES (?, ?, ?, 'user', 'approved', 50, NOW())
            ");
            if ($ins->execute([$phone, $name, $hash])) {
                $userId = (int)$pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = 'user';
                $_SESSION['role'] = 'user';
                $_SESSION['name'] = $name;
                $_SESSION['user_name'] = $name;
                $_SESSION['password_hash'] = hash('sha256', $hash);
                $_SESSION['contract_accepted_version'] = 'v2.0-2026';

                if ($isRemember) {
                    require_once __DIR__ . '/includes/AuthGuard.php';
                    AuthGuard::setRememberCookie($userId, $phone, $password, 30);
                }
                
                redirectAfterLogin(['id' => $userId, 'role' => 'user'], $returnUrl);
            } else {
                $error = 'خطایی در ایجاد حساب رخ داد. لطفاً مجدداً تلاش کنید.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>ورود به حساب کاربری | پلتفرم سلامت و خدمات حیوانات خانگی آسنا</title>
    <link rel="stylesheet" href="assets/css/tailwind.output.css?v=<?= time() ?>">
    <link href="assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="assets/css/geist.css" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/login.css?v=<?= time() ?>">
    <script>
    function switchAuthTab(tabId) {
        if (!tabId) return;
        var panes = document.querySelectorAll('.tab-pane');
        for (var i = 0; i < panes.length; i++) {
            panes[i].classList.add('hidden');
            panes[i].classList.remove('active');
        }
        var tabs = document.querySelectorAll('.tab-btn');
        for (var j = 0; j < tabs.length; j++) {
            tabs[j].classList.remove('bg-white', 'text-primary', 'shadow-sm', 'font-black');
            tabs[j].classList.add('text-slate-500', 'font-medium');
        }
        var targetPane = document.getElementById('pane-' + tabId);
        var targetBtn = document.getElementById('tab-' + tabId);
        if (targetPane) {
            targetPane.classList.remove('hidden');
            targetPane.classList.add('active');
        }
        if (targetBtn) {
            targetBtn.classList.add('bg-white', 'text-primary', 'shadow-sm', 'font-black');
            targetBtn.classList.remove('text-slate-500', 'font-medium');
        }
        try {
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({}, '', url.toString());
            }
        } catch (e) {}
    }

    function togglePasswordVisibility(inputId, btnEl) {
        var input = document.getElementById(inputId);
        if (!input) return;
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (btnEl) {
            var icon = btnEl.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = isPassword ? 'visibility_off' : 'visibility';
            }
        }
    }

    function initOtpCountdown(timerId, btnId, seconds) {
        seconds = typeof seconds === 'number' ? seconds : 120;
        var timerEl = document.getElementById(timerId);
        var btnEl = btnId ? document.getElementById(btnId) : null;
        if (!timerEl) return;
        var remaining = seconds;
        if (btnEl) btnEl.disabled = true;
        var interval = setInterval(function() {
            remaining--;
            var mins = Math.floor(remaining / 60);
            var secs = remaining % 60;
            timerEl.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (remaining <= 0) {
                clearInterval(interval);
                timerEl.textContent = '00:00';
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnEl.classList.add('text-primary', 'hover:text-secondary-container', 'hover:underline', 'cursor-pointer');
                }
            }
        }, 1000);
    }

    function toggleQuickLogin() {
        var drawer = document.getElementById('quick-login-drawer');
        if (drawer) {
            drawer.classList.toggle('hidden');
        }
    }
    </script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased selection:bg-secondary-container selection:text-white">
<main class="min-h-screen w-full flex flex-row items-stretch">

    <!-- Left Brand Showcase (Desktop) - Styled in ASENA Deep Navy & Amber/Orange Accents -->
    <section class="hidden lg:flex lg:w-6/12 xl:w-7/12 relative overflow-hidden bg-gradient-to-br from-[#00102e] via-[#001a48] to-[#002d72] items-center justify-center p-12 text-white">
        <!-- Background Ambient Glow -->
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-primary-container/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-secondary-container/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-xl">
            <!-- Brand Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 text-xs font-bold mb-8 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"></span>
                <span>اکوسیستم جامع سلامت و خدمات حیوانات خانگی آسنا</span>
            </div>

            <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight mb-6 text-white tracking-tight">
                مراقبتی هوشمندانه و آسوده برای همراهان همیشگی شما
            </h1>

            <p class="text-base xl:text-lg text-blue-100/85 leading-relaxed mb-10 font-normal">
                در آسنا، برترین متخصصین دامپزشکی، کلینیک‌ها، داروخانه‌ها و تأمین‌کنندگان ملزومات حیوانات گرد هم آمده‌اند تا بهترین تجربه درمانی و نگهداری را فراهم آورند.
            </p>

            <!-- Feature Bento Highlights -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/10 backdrop-blur-md p-5 rounded-2xl border border-white/15 hover:bg-white/15 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-primary-container/60 flex items-center justify-center mb-3 text-blue-200 border border-blue-400/20">
                        <span class="material-symbols-outlined text-2xl">medical_services</span>
                    </div>
                    <h3 class="font-bold text-white text-base mb-1">پرونده پزشکی یکپارچه</h3>
                    <p class="text-xs text-blue-100/75 leading-relaxed">دسترسی دائم به سوابق واکسیناسیون، نسخ الکترونیک و آزمایش‌ها</p>
                </div>

                <div class="bg-white/10 backdrop-blur-md p-5 rounded-2xl border border-white/15 hover:bg-white/15 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container/20 flex items-center justify-center mb-3 text-secondary-container border border-secondary-container/30">
                        <span class="material-symbols-outlined text-2xl">event_available</span>
                    </div>
                    <h3 class="font-bold text-white text-base mb-1">نوبت‌دهی آنلاین ۲۴/۷</h3>
                    <p class="text-xs text-blue-100/75 leading-relaxed">رزرو سریع ویزیت حضوری یا آنلاین با برترین دامپزشکان کشور</p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="mt-10 pt-6 border-t border-white/10 flex items-center justify-between text-xs text-blue-200/80">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-secondary-container">verified_user</span>
                    <span>ضمانت پرداخت امن و تسویه رسمی پایا</span>
                </div>
                <div>پشتیبانی برخط ۲۴ ساعته</div>
            </div>
        </div>
    </section>

    <!-- Right Authentication Form Section -->
    <section class="w-full lg:w-6/12 xl:w-5/12 bg-white flex flex-col justify-between px-6 sm:px-12 md:px-16 py-8 sm:py-12 relative overflow-y-auto">
        
        <!-- Top Bar: Logo & Return -->
        <div class="flex items-center justify-between mb-8">
            <a href="index.php" class="flex items-center gap-2.5 group" title="بازگشت به صفحه اصلی آسنا">
                <img src="assets/images/logo.png" alt="لوگوی آسنا" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform">
                <span class="text-primary font-black text-xl tracking-tight">ASENA</span>
            </a>
            <a href="<?= !empty($returnUrl) ? htmlspecialchars($returnUrl) : 'index.php' ?>" class="text-xs font-bold text-slate-500 hover:text-primary flex items-center gap-1 transition-colors">
                <span>بازگشت به سایت</span>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
        </div>

        <!-- Form Card Container -->
        <div class="max-w-md w-full mx-auto my-auto">
            
            <!-- Heading -->
            <div class="mb-6 text-center">
                <h2 class="text-2xl sm:text-3xl font-black text-primary mb-2 tracking-tight">ورود به آسنا</h2>
                <p class="text-xs sm:text-sm text-slate-500">برای دسترسی به پنل و خدمات خود، یکی از روش‌های زیر را انتخاب کنید</p>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
                <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 p-3.5 rounded-xl mb-5 text-xs animate-fade-in shadow-sm">
                    <span class="material-symbols-outlined text-red-600 text-lg shrink-0 mt-0.5">error</span>
                    <div class="leading-relaxed flex-1"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success) && empty($_SESSION['otp_login_data']) && empty($_SESSION['signup_data'])): ?>
                <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl mb-5 text-xs animate-fade-in shadow-sm">
                    <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0 mt-0.5">check_circle</span>
                    <div class="leading-relaxed flex-1"><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <!-- Segmented Tab Navigation -->
            <div class="p-1 bg-slate-100/90 rounded-2xl flex items-center gap-1 mb-6 text-xs border border-slate-200/60 select-none">
                <a href="login.php?tab=password<?= $returnQuery ?>" role="button" data-tab="password" onclick="switchAuthTab('password'); return false;" id="tab-password" class="tab-btn flex-1 py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer <?= $activeTab === 'password' ? 'bg-white text-primary shadow-sm font-black' : 'text-slate-500 font-medium hover:text-primary' ?>">
                    <span class="material-symbols-outlined text-base pointer-events-none">lock</span>
                    <span class="pointer-events-none">ورود با رمز</span>
                </a>
                <a href="login.php?tab=otp<?= $returnQuery ?>" role="button" data-tab="otp" onclick="switchAuthTab('otp'); return false;" id="tab-otp" class="tab-btn flex-1 py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer <?= $activeTab === 'otp' ? 'bg-white text-primary shadow-sm font-black' : 'text-slate-500 font-medium hover:text-primary' ?>">
                    <span class="material-symbols-outlined text-base pointer-events-none">sms</span>
                    <span class="pointer-events-none">ورود پیامکی (OTP)</span>
                </a>
                <a href="login.php?tab=signup<?= $returnQuery ?>" role="button" data-tab="signup" onclick="switchAuthTab('signup'); return false;" id="tab-signup" class="tab-btn flex-1 py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer <?= $activeTab === 'signup' ? 'bg-white text-primary shadow-sm font-black' : 'text-slate-500 font-medium hover:text-primary' ?>">
                    <span class="material-symbols-outlined text-base pointer-events-none">person_add</span>
                    <span class="pointer-events-none">ثبت‌نام سریع</span>
                </a>
            </div>

            <!-- ======================================================= -->
            <!-- TAB PANE 1: Password Login                              -->
            <!-- ======================================================= -->
            <div id="pane-password" class="tab-pane <?= $activeTab === 'password' ? 'active' : 'hidden' ?>">
                <form method="POST" action="login.php?tab=password<?= $returnQuery ?>" class="space-y-4">
                    <input type="hidden" name="action" value="login_password">
                    <?php if (!empty($returnUrl)): ?>
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل</label>
                        <div class="relative">
                            <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required class="w-full h-11 pr-10 pl-4 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all text-left dir-ltr font-mono">
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">smartphone</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">رمز عبور</label>
                            <a href="forgot_password.php" class="text-xs text-primary hover:text-secondary-container font-bold transition-colors">فراموشی رمز عبور؟</a>
                        </div>
                        <div class="relative">
                            <input type="password" id="input-password" name="password" placeholder="••••••••" required class="w-full h-11 pr-10 pl-10 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all text-left dir-ltr">
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">key</span>
                            <button type="button" onclick="togglePasswordVisibility('input-password', this)" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                            <span class="text-xs text-slate-600 font-medium">مرا به خاطر بسپار</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-primary to-primary-container hover:from-[#001437] hover:to-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                        <span>ورود به حساب کاربری</span>
                        <span class="material-symbols-outlined text-base">login</span>
                    </button>
                </form>
            </div>

            <!-- ======================================================= -->
            <!-- TAB PANE 2: Instant OTP SMS Login                       -->
            <!-- ======================================================= -->
            <div id="pane-otp" class="tab-pane <?= $activeTab === 'otp' ? 'active' : 'hidden' ?>">
                <?php if (!empty($_SESSION['otp_login_data'])): ?>
                    <!-- Step 2: Verify OTP Code -->
                    <form method="POST" action="login.php?tab=otp<?= $returnQuery ?>" class="space-y-4">
                        <input type="hidden" name="action" value="verify_otp">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>

                        <div class="p-3.5 bg-emerald-50 border border-emerald-200/80 rounded-xl text-xs text-emerald-900 flex items-center justify-between shadow-2xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="material-symbols-outlined text-emerald-600 text-base shrink-0">check_circle</span>
                                <span class="truncate">کد تأیید ورود به شماره <strong class="font-mono text-xs dir-ltr"><?= htmlspecialchars($_SESSION['otp_login_data']['phone']) ?></strong> ارسال شد</span>
                            </div>
                            <a href="login.php?cancel_otp=1<?= $returnQuery ?>" class="text-emerald-700 hover:text-emerald-800 underline font-bold shrink-0 text-xs mr-2">تغییر شماره</a>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 text-center">کد تأیید ۶ رقمی را وارد کنید</label>
                            <input type="text" name="otp" maxlength="6" autofocus placeholder="------" required class="w-full h-12 rounded-xl border border-slate-200 text-xl font-bold tracking-[0.6em] text-center focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all font-mono">
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="text-slate-500">زمان باقی‌مانده تا دریافت مجدد:</span>
                            <span id="otp-login-countdown" class="font-bold font-mono text-secondary-container">02:00</span>
                        </div>

                        <div class="flex items-center justify-between py-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" <?= (!isset($_SESSION['otp_login_data']) || !empty($_SESSION['otp_login_data']['remember'])) ? 'checked' : '' ?> class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                                <span class="text-xs text-slate-600 font-medium">مرا به خاطر بسپار (ماندن در حساب تا ۳۰ روز)</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full h-11 bg-gradient-to-r from-primary to-primary-container hover:from-[#001437] hover:to-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                            <span>تأیید و ورود به سامانه</span>
                            <span class="material-symbols-outlined text-base">verified</span>
                        </button>
                    </form>

                    <!-- Resend form -->
                    <form method="POST" action="login.php?tab=otp<?= $returnQuery ?>" class="mt-2 text-center">
                        <input type="hidden" name="action" value="send_otp">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars($_SESSION['otp_login_data']['phone']) ?>">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>
                        <button type="submit" id="btn-resend-otp" disabled class="text-xs opacity-50 cursor-not-allowed font-medium transition-all inline-flex items-center gap-1 text-primary">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                            <span>ارسال مجدد پیامک کد تأیید</span>
                        </button>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            initOtpCountdown('otp-login-countdown', 'btn-resend-otp', 120);
                        });
                    </script>
                <?php else: ?>
                    <!-- Step 1: Enter Phone Number -->
                    <form method="POST" action="login.php?tab=otp<?= $returnQuery ?>" class="space-y-4">
                        <input type="hidden" name="action" value="send_otp">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل جهت دریافت پیامک</label>
                            <div class="relative">
                                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required class="w-full h-11 pr-10 pl-4 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all text-left dir-ltr font-mono">
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">smartphone</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                اگر قبلاً در آسنا ثبت‌نام نکرده باشید، با ورود اولین کد به صورت خودکار حساب شما ایجاد می‌شود.
                            </p>
                        </div>

                        <div class="flex items-center justify-between py-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" checked class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                                <span class="text-xs text-slate-600 font-medium">مرا به خاطر بسپار (ورود پایدار تا ۳۰ روز)</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full h-11 bg-gradient-to-r from-primary to-primary-container hover:from-[#001437] hover:to-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                            <span>ارسال کد تأیید یک‌بار مصرف (OTP)</span>
                            <span class="material-symbols-outlined text-base">sms</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- TAB PANE 3: Fast Registration                           -->
            <!-- ======================================================= -->
            <div id="pane-signup" class="tab-pane <?= $activeTab === 'signup' ? 'active' : 'hidden' ?>">
                <?php if (!empty($_SESSION['signup_data'])): ?>
                    <!-- Verify Registration OTP -->
                    <form method="POST" action="login.php?tab=signup<?= $returnQuery ?>" class="space-y-4">
                        <input type="hidden" name="action" value="verify_signup">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>

                        <div class="p-3.5 bg-emerald-50 border border-emerald-200/80 rounded-xl text-xs text-emerald-900 flex items-center justify-between shadow-2xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="material-symbols-outlined text-emerald-600 text-base shrink-0">check_circle</span>
                                <span class="truncate">کد عضویت به شماره <strong class="font-mono text-xs dir-ltr"><?= htmlspecialchars($_SESSION['signup_data']['phone']) ?></strong> ارسال شد</span>
                            </div>
                            <a href="login.php?cancel_signup=1<?= $returnQuery ?>" class="text-emerald-700 hover:text-emerald-800 underline font-bold shrink-0 text-xs mr-2">تغییر شماره</a>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 text-center">کد تأیید ۶ رقمی</label>
                            <input type="text" name="otp" maxlength="6" autofocus placeholder="------" required class="w-full h-12 rounded-xl border border-slate-200 text-xl font-bold tracking-[0.6em] text-center focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all font-mono">
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="text-slate-500">زمان باقی‌مانده:</span>
                            <span id="signup-countdown" class="font-bold font-mono text-secondary-container">02:00</span>
                        </div>

                        <div class="flex items-center justify-between py-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" <?= (!isset($_SESSION['signup_data']) || !empty($_SESSION['signup_data']['remember'])) ? 'checked' : '' ?> class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                                <span class="text-xs text-slate-600 font-medium">مرا به خاطر بسپار (ماندن در حساب تا ۳۰ روز)</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full h-11 bg-gradient-to-r from-primary to-primary-container hover:from-[#001437] hover:to-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                            <span>تکمیل ثبت‌نام و ورود</span>
                            <span class="material-symbols-outlined text-base">how_to_reg</span>
                        </button>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            initOtpCountdown('signup-countdown', null, 120);
                        });
                    </script>
                <?php else: ?>
                    <!-- Registration Fields -->
                    <form method="POST" action="login.php?tab=signup<?= $returnQuery ?>" class="space-y-3.5">
                        <input type="hidden" name="action" value="signup">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی</label>
                            <div class="relative">
                                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="مثال: علی احمدی" required class="w-full h-11 pr-10 pl-4 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all">
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل</label>
                            <div class="relative">
                                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required class="w-full h-11 pr-10 pl-4 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all text-left dir-ltr font-mono">
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">smartphone</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">رمز عبور دلخواه (حداقل ۶ نویسه)</label>
                            <div class="relative">
                                <input type="password" id="input-signup-password" name="password" placeholder="••••••••" required class="w-full h-11 pr-10 pl-10 rounded-xl border border-slate-200 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/50 hover:bg-white transition-all text-left dir-ltr">
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                                <button type="button" onclick="togglePasswordVisibility('input-signup-password', this)" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" checked class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                                <span class="text-xs text-slate-600 font-medium">مرا به خاطر بسپار (ماندن در حساب تا ۳۰ روز)</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full h-11 bg-gradient-to-r from-primary to-primary-container hover:from-[#001437] hover:to-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2 mt-2">
                            <span>ثبت‌نام و دریافت کد تأیید</span>
                            <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Partner Registration Note -->
            <div class="mt-6 p-3.5 bg-gradient-to-r from-blue-50/80 to-amber-50/60 border border-blue-100 rounded-2xl flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-xl">stethoscope</span>
                    <span class="text-xs font-bold text-slate-800">پزشک، کلینیک، داروخانه یا فروشگاه هستید؟</span>
                </div>
                <a href="register.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : '' ?>" class="px-3.5 py-1.5 bg-primary hover:bg-primary-container text-white rounded-xl text-xs font-bold transition-all shadow-sm shrink-0">
                    پنل همکاران
                </a>
            </div>

            <!-- Social Logins Divider -->
            <div class="relative my-7 text-center">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                <span class="relative px-3 bg-white text-slate-400 text-xs font-medium">یا ورود با حساب</span>
            </div>

            <!-- Social OAuth Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <a href="<?= htmlspecialchars($google_oauth_url) ?>" class="flex items-center justify-center gap-2 h-10 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all text-xs font-bold text-slate-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"></path>
                    </svg>
                    <span>گوگل</span>
                </a>
                <a href="<?= htmlspecialchars($apple_oauth_url) ?>" class="flex items-center justify-center gap-2 h-10 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all text-xs font-bold text-slate-700">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M17.05 20.28c-.96.95-2.06 1.72-3.32 1.72-1.18 0-1.6-.74-2.95-.74-1.37 0-1.87.72-2.96.72-1.2 0-2.2-.76-3.19-1.72-2.01-1.96-3.53-5.54-3.53-8.8 0-3.3 1.6-5.06 3.19-5.06 1.03 0 1.83.6 2.65.6.83 0 1.4-.6 2.62-.6 1.34 0 2.5.76 3.1 1.72-2.73 1.65-2.28 5.6.43 6.7-.6 1.43-1.35 2.83-2.54 3.76zm-3.54-15.65c.6-.73 1-1.74 1-2.75 0-.14-.02-.28-.04-.41-.95.04-2.1.64-2.78 1.43-.6.7-.85 1.65-.85 2.65 0 .15.02.3.06.41.05 0 .1 0 .15 0 .9 0 1.9-.45 2.46-1.33z"></path>
                    </svg>
                    <span>اپل</span>
                </a>
            </div>

            <!-- Developer Quick Switch Pill & 1-Click Role Switcher -->
            <div class="mt-8 text-center space-y-3">
                <div class="flex items-center justify-center gap-2">
                    <a href="auto_login.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : '' ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-primary text-[11px] font-bold transition-all border border-slate-200/80 shadow-2xs">
                        <span class="material-symbols-outlined text-[15px] text-amber-500">bolt</span>
                        <span>سوئیچ سریع توسعه‌دهندگان (Auto-Login Hub)</span>
                    </a>
                    <button type="button" onclick="toggleQuickLogin()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-200 transition-colors shadow-2xs cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">flash_on</span>
                        <span>ورود ۱-کلیک</span>
                    </button>
                </div>

                <!-- Quick Role Drawer Tray -->
                <div id="quick-login-drawer" class="hidden animate-fade-in bg-slate-50/90 border border-slate-200 rounded-2xl p-3 text-right shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[11px] font-black text-slate-700">انتخاب سریع نقش (بدون پسورد):</p>
                        <a href="auto_login.php<?= !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : '' ?>" class="text-[10px] text-primary hover:text-secondary-container font-bold">میزکار کامل &larr;</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <a href="auto_login.php?role=doctor<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-emerald-600 text-sm">stethoscope</span>
                            <span>دکتر دامپزشک</span>
                        </a>
                        <a href="auto_login.php?role=pharmacist<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-purple-500 hover:bg-purple-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-purple-600 text-sm">prescriptions</span>
                            <span>دکتر داروساز</span>
                        </a>
                        <a href="auto_login.php?role=organization<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-indigo-600 text-sm">domain</span>
                            <span>مرکز درمانی</span>
                        </a>
                        <a href="auto_login.php?role=seller<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-amber-600 text-sm">storefront</span>
                            <span>فروشنده</span>
                        </a>
                        <a href="auto_login.php?role=admin<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-rose-600 text-sm">shield_person</span>
                            <span>مدیر ارشد</span>
                        </a>
                        <a href="auto_login.php?role=user<?= !empty($returnUrl) ? '&return_url=' . urlencode($returnUrl) : '' ?>" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-sky-500 hover:bg-sky-50/50 transition-all text-xs font-bold text-slate-800 flex items-center gap-1.5 shadow-2xs">
                            <span class="material-symbols-outlined text-sky-600 text-sm">pets</span>
                            <span>کاربر عادی</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Legal Links -->
        <footer class="mt-8 pt-4 border-t border-slate-100 text-center">
            <div class="flex items-center justify-center gap-4 text-xs text-slate-400 font-medium">
                <a href="terms.php" class="hover:text-primary transition-colors">قوانین و شرایط</a>
                <span>•</span>
                <a href="privacy.php" class="hover:text-primary transition-colors">حریم خصوصی</a>
                <span>•</span>
                <a href="user_tickets.php" class="hover:text-primary transition-colors">پشتیبانی</a>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">© <?= date('Y') ?> سامانه هوشمند حیوانات خانگی آسنا. کلیه حقوق محفوظ است.</p>
        </footer>
    </section>
</main>

<script src="assets/js/login.js?v=<?= time() ?>"></script>
</body>
</html>