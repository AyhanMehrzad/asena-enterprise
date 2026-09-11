<?php
/**
 * ASENA Enterprise - Process-Based Multi-Role Registration Wizard
 * Supports Customer, Doctor, Pharmacist, Veterinary Clinic/Hospital, and Wholesaler onboarding.
 * Features mobile SMS OTP verification, credential file uploads, and admin queue routing.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/SmsService.php';
require_once __DIR__ . '/includes/RoleVerificationService.php';

// Initialize session and security
App::boot();

$error = '';
$success = '';
$step = 1; // 1: Role, 2: Info & OTP, 3: Credentials, 4: Complete

// Handle URL query for step transitions
if (isset($_GET['step'])) {
    $step = (int)$_GET['step'];
}
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_flow']);
    header("Location: register.php");
    exit;
}

// Handle AJAX or POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 2: Send OTP
    if ($action === 'send_otp') {
        $phone = SmsService::normalizePhone($_POST['phone'] ?? '');
        $name  = trim($_POST['name'] ?? '');
        $role  = trim($_POST['role'] ?? 'customer');

        if (empty($phone) || strlen($phone) < 10) {
            $error = 'شماره موبایل وارد شده نامعتبر است.';
            $step = 2;
        } else {
            // Check if phone already registered
            $chk = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $chk->execute([$phone]);
            if ($chk->fetchColumn()) {
                $error = 'این شماره موبایل قبلاً در سامانه ثبت شده است. لطفاً وارد شوید.';
                $step = 2;
            } else {
                $rate_error = check_rate_limit($pdo, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $phone);
                if ($rate_error) {
                    $error = $rate_error;
                    $step = 2;
                } else {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $_SESSION['reg_flow'] = [
                        'role'           => $role,
                        'phone'          => $phone,
                        'name'           => $name,
                        'password'       => $_POST['password'] ?? '',
                        'otp'            => $otp,
                        'otp_expires_at' => time() + 180,
                        'phone_verified' => false
                    ];

                    $sms = App::sms();
                    $sent = $sms->sendOtp($phone, $otp);
                    $success = "کد تایید ۶ رقمی به شماره $phone پیامک شد.";
                    $step = 2;
                }
            }
        }
    }

    // Step 2: Verify OTP
    elseif ($action === 'verify_otp') {
        $enteredOtp = SmsService::sanitizeCode($_POST['otp'] ?? '');
        if (isset($_SESSION['reg_flow'])) {
            $flow = $_SESSION['reg_flow'];
            if (time() > ($flow['otp_expires_at'] ?? 0)) {
                $error = 'کد تایید منقضی شده است. لطفاً کد جدید دریافت کنید.';
                $step = 2;
            } elseif (!empty($enteredOtp) && $enteredOtp === $flow['otp']) {
                $_SESSION['reg_flow']['phone_verified'] = true;
                
                // If standard customer, create account immediately
                if ($flow['role'] === 'customer') {
                    $hash = password_hash($flow['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (phone, name, password, role, loyalty_points, verification_status, created_at)
                        VALUES (?, ?, ?, 'customer', 50, 'approved', NOW())
                    ");
                    if ($stmt->execute([$flow['phone'], $flow['name'], $hash])) {
                        $newUserId = (int)$pdo->lastInsertId();
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['user_role'] = 'customer';
                        $_SESSION['user_name'] = $flow['name'];
                        $_SESSION['password_hash'] = hash('sha256', $hash);
                        unset($_SESSION['reg_flow']);
                        $_SESSION['login_success'] = 'ثبت‌نام شما با موفقیت انجام شد و ۵۰ امتیاز باشگاه مشتریان دریافت نمودید!';
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = 'خطا در ثبت اطلاعات حساب کاربری.';
                        $step = 2;
                    }
                } else {
                    // Professional role -> move to Step 3 (Credential Uploads)
                    $step = 3;
                }
            } else {
                $error = 'کد وارد شده صحیح نمی‌باشد.';
                $step = 2;
            }
        } else {
            $error = 'جلسه شما منقضی شده است. لطفاً فرآیند را از ابتدا آغاز کنید.';
            $step = 1;
        }
    }

    // Step 3: Complete Professional Registration with Credentials
    elseif ($action === 'submit_credentials') {
        if (!isset($_SESSION['reg_flow']) || empty($_SESSION['reg_flow']['phone_verified'])) {
            $error = 'شماره موبایل هنوز تایید نشده است.';
            $step = 2;
        } else {
            $flow = $_SESSION['reg_flow'];
            $role = $flow['role'];
            $phone = $flow['phone'];
            $name = $flow['name'];
            $password = $flow['password'];

            // 1. Create Base User with pending_role and pending status
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("
                INSERT INTO users (phone, name, password, role, pending_role, verification_status, created_at)
                VALUES (?, ?, ?, 'user', ?, 'pending', NOW())
            ");
            
            if ($ins->execute([$phone, $name, $hash, $role])) {
                $userId = (int)$pdo->lastInsertId();

                // 2. Submit application via RoleVerificationService
                $verificationService = App::roleVerification();
                $appData = [
                    'applied_role'      => $role,
                    'full_name'         => $name,
                    'phone'             => $phone,
                    'license_number'    => trim($_POST['license_number'] ?? ''),
                    'specialty'         => trim($_POST['specialty'] ?? ''),
                    'organization_name' => trim($_POST['organization_name'] ?? ''),
                    'organization_type' => trim($_POST['organization_type'] ?? 'clinic'),
                    'city'              => trim($_POST['city'] ?? 'تهران'),
                    'address'           => trim($_POST['address'] ?? ''),
                    'website'           => trim($_POST['website'] ?? ''),
                    'instagram'         => trim($_POST['instagram'] ?? '')
                ];

                $res = $verificationService->submitApplication($userId, $appData, $_FILES);
                if ($res['success']) {
                    $step = 4;
                    unset($_SESSION['reg_flow']);
                } else {
                    $error = $res['error'] ?? 'خطا در ثبت مدارک.';
                    $step = 3;
                }
            } else {
                $error = 'خطا در ایجاد رکورد کاربری.';
                $step = 3;
            }
        }
    }
}

// Check current session state if returning
if (isset($_SESSION['reg_flow']) && $step === 1) {
    if (!empty($_SESSION['reg_flow']['phone_verified'])) {
        $step = 3;
    } elseif (!empty($_SESSION['reg_flow']['phone'])) {
        $step = 2;
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>ثبت‌نام</title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="assets/css/geist.css" rel="stylesheet"/>
    <script src="assets/js/tailwind-config.js"></script>
    <style>
        .role-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .role-card.active, .role-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(14, 165, 233, 0.25);
        }
        .step-bubble.active {
            background-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.2);
        }
        .step-bubble.completed {
            background-color: #10b981;
            color: #ffffff;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col justify-between">

    <!-- Top Navigation Bar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <img src="assets/images/logo.png" alt="ASENA Logo" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform" onerror="this.src='https://via.placeholder.com/36?text=A'">
                <div>
                    <span class="text-xl font-black bg-gradient-to-r from-sky-600 to-indigo-600 bg-clip-text text-transparent">ASENA</span>
                    <span class="text-xs text-slate-600 block -mt-1 font-medium">پلتفرم سلامت و درمان حیوانات خانگی</span>
                </div>
            </a>
            
            <div class="flex items-center gap-4 text-sm">
                <span class="text-slate-600 hidden sm:inline">قبلاً ثبت‌نام کرده‌اید؟</span>
                <a href="login.php" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-colors">
                    ورود به حساب
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-8 sm:py-12">
        
        <!-- Stepper Progress Bar -->
        <div class="mb-10">
            <div class="flex items-center justify-between max-w-xl mx-auto relative">
                <div class="absolute top-1/2 left-0 right-0 h-1 bg-slate-200 -translate-y-1/2 z-0"></div>
                <div class="absolute top-1/2 right-0 h-1 bg-sky-600 -translate-y-1/2 z-0 transition-all duration-500" 
                     style="width: <?= ($step == 1 ? '0%' : ($step == 2 ? '33%' : ($step == 3 ? '66%' : '100%'))) ?>"></div>
                
                <!-- Step 1 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="step-bubble w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-white border-2 border-slate-300 <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                        <?= $step > 1 ? '<span class="material-symbols-outlined text-base">check</span>' : '۱' ?>
                    </div>
                    <span class="text-xs mt-2 font-bold <?= $step == 1 ? 'text-sky-600' : 'text-slate-600' ?>">انتخاب نقش</span>
                </div>

                <!-- Step 2 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="step-bubble w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-white border-2 border-slate-300 <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                        <?= $step > 2 ? '<span class="material-symbols-outlined text-base">check</span>' : '۲' ?>
                    </div>
                    <span class="text-xs mt-2 font-bold <?= $step == 2 ? 'text-sky-600' : 'text-slate-600' ?>">تایید موبایل</span>
                </div>

                <!-- Step 3 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="step-bubble w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-white border-2 border-slate-300 <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                        <?= $step > 3 ? '<span class="material-symbols-outlined text-base">check</span>' : '۳' ?>
                    </div>
                    <span class="text-xs mt-2 font-bold <?= $step == 3 ? 'text-sky-600' : 'text-slate-600' ?>">احراز مدارک</span>
                </div>

                <!-- Step 4 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="step-bubble w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-white border-2 border-slate-300 <?= $step == 4 ? 'completed' : '' ?>">
                        <span class="material-symbols-outlined text-base">verified</span>
                    </div>
                    <span class="text-xs mt-2 font-bold <?= $step == 4 ? 'text-emerald-600' : 'text-slate-600' ?>">تایید نهایی</span>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600 text-2xl">error</span>
                <div class="text-sm font-bold"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">check_circle</span>
                <div class="text-sm font-bold"><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- STEP 1: ROLE SELECTION -->
        <?php if ($step === 1): 
            $initialRole = $_GET['role'] ?? ($_SESSION['reg_flow']['role'] ?? 'customer');
        ?>
            <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/80">
                <div class="text-center max-w-xl mx-auto mb-8">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2">نقش خود را در سامانه انتخاب کنید</h1>
                    <p class="text-slate-500 text-sm">متناسب با فعالیت تخصصی خود در حوزه سلامت حیوانات خانگی، حساب کاربری متناسب را برگزینید.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Option 1: Customer -->
                    <label class="role-card flex items-start gap-4 p-5 rounded-2xl border-2 <?= $initialRole === 'customer' ? 'border-sky-500 bg-sky-50/40' : 'border-slate-200 bg-slate-50/50' ?> cursor-pointer transition-all" onclick="selectRole('customer')">
                        <input type="radio" name="selected_role" value="customer" class="mt-1 w-5 h-5 text-sky-600 focus:ring-sky-500" <?= $initialRole === 'customer' ? 'checked' : '' ?>>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-sky-600">pets</span>
                                <span class="font-black text-slate-900">صاحب پت (کاربر عمومی)</span>
                                <span class="text-[11px] bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full font-bold">عضویت آنی</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">خرید آنلاین، ثبت پرونده سلامت و واکسیناسیون، نوبت‌دهی آنلاین با پزشکان و اورژانس ۲۴ ساعته.</p>
                        </div>
                    </label>

                    <!-- Option 2: Doctor -->
                    <label class="role-card flex items-start gap-4 p-5 rounded-2xl border-2 border-slate-200 hover:border-indigo-500 cursor-pointer bg-slate-50/50 hover:bg-indigo-50/30 transition-all" onclick="selectRole('doctor')">
                        <input type="radio" name="selected_role" value="doctor" class="mt-1 w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-indigo-600">stethoscope</span>
                                <span class="font-black text-slate-900">پزشک دامپزشک</span>
                                <span class="text-[11px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">احراز نظام</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">ایجاد پروفایل رسمی، مدیریت تقویم نوبت‌دهی، صدور نسخه الکترونیک و ارتباط با بیمارستان‌ها.</p>
                        </div>
                    </label>

                    <!-- Option 3: Pharmacist -->
                    <label class="role-card flex items-start gap-4 p-5 rounded-2xl border-2 border-slate-200 hover:border-teal-500 cursor-pointer bg-slate-50/50 hover:bg-teal-50/30 transition-all" onclick="selectRole('pharmacist')">
                        <input type="radio" name="selected_role" value="pharmacist" class="mt-1 w-5 h-5 text-teal-600 focus:ring-teal-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-teal-600">medication</span>
                                <span class="font-black text-slate-900">داروساز دامپزشکی</span>
                                <span class="text-[11px] bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full font-bold">احراز پروانه</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">دریافت و آماده‌سازی نسخه‌های دارویی، عرضه مکمل‌های تخصصی و مدیریت موجودی داروخانه.</p>
                        </div>
                    </label>

                    <!-- Option 4: Organization -->
                    <label class="role-card flex items-start gap-4 p-5 rounded-2xl border-2 <?= $initialRole === 'organization' ? 'border-rose-500 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' ?> hover:border-rose-500 cursor-pointer hover:bg-rose-50/30 transition-all" onclick="selectRole('organization')">
                        <input type="radio" name="selected_role" value="organization" class="mt-1 w-5 h-5 text-rose-600 focus:ring-rose-500" <?= $initialRole === 'organization' ? 'checked' : '' ?>>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-rose-600">local_hospital</span>
                                <span class="font-black text-slate-900">مراکز درمانی، داروخانه‌ها و پناهگاه‌ها</span>
                                <span class="text-[11px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">حقوقی / درمانی</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">بیمارستان‌ها، کلینیک‌ها، مراکز اورژانس شبانه‌روزی، داروخانه‌های تخصصی، پناهگاه‌ها و آزمایشگاه‌ها.</p>
                        </div>
                    </label>

                    <!-- Option 5: Supplier / Wholesaler -->
                    <label class="role-card md:col-span-2 flex items-start gap-4 p-5 rounded-2xl border-2 border-slate-200 hover:border-amber-500 cursor-pointer bg-slate-50/50 hover:bg-amber-50/30 transition-all" onclick="selectRole('supplier')">
                        <input type="radio" name="selected_role" value="supplier" class="mt-1 w-5 h-5 text-amber-600 focus:ring-amber-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-amber-600">inventory_2</span>
                                <span class="font-black text-slate-900">تأمین‌کننده و پخش عمده (B2B)</span>
                                <span class="text-[11px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">پروانه کسب</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">فروش مستقیم محصولات، غذا و ملزومات پت با قیمت همکار به کلینیک‌ها و پت‌شاپ‌ها.</p>
                        </div>
                    </label>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="button" onclick="goToStep2()" class="px-8 py-3.5 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-white rounded-2xl font-bold shadow-lg shadow-sky-600/20 flex items-center gap-2 transition-all">
                        <span>مرحله بعد: اطلاعات تماس و تایید پیامکی</span>
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- STEP 2: USER INFO & SMS OTP -->
        <?php if ($step === 2): ?>
            <?php 
                $currRole = $_GET['role'] ?? ($_SESSION['reg_flow']['role'] ?? 'customer');
            ?>
            <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/80">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <span class="material-symbols-outlined text-3xl">smartphone</span>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 mb-1">تایید شماره تلفن همراه</h2>
                        <p class="text-slate-500 text-xs">کد فعال‌سازی ۶ رقمی جهت احراز هویت از طریق پیامک ارسال می‌شود.</p>
                    </div>

                    <?php if (!isset($_SESSION['reg_flow']['otp'])): ?>
                        <!-- Form 2A: Collect Name, Phone & Password to Send OTP -->
                        <form method="POST" action="register.php?step=2" class="space-y-4">
                            <input type="hidden" name="action" value="send_otp">
                            <input type="hidden" name="role" value="<?= htmlspecialchars($currRole) ?>">

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی / نام مدیر</label>
                                <input type="text" name="name" required class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-sm" placeholder="مثال: دکتر رامین سعادت">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن همراه (جهت دریافت پیامک)</label>
                                <input type="tel" name="phone" dir="ltr" required class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-sm text-center tracking-wider" placeholder="09121234567">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">کلمه عبور امن</label>
                                <input type="password" name="password" minlength="6" required class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-sm dir-ltr text-center" placeholder="••••••••">
                            </div>

                            <button type="submit" class="w-full h-12 mt-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-bold shadow-md shadow-sky-600/20 flex items-center justify-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-lg">sms</span>
                                <span>ارسال کد تایید پیامکی (OTP)</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Form 2B: Enter OTP -->
                        <form method="POST" action="register.php?step=2" class="space-y-5">
                            <input type="hidden" name="action" value="verify_otp">
                            
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-center">
                                <span class="text-xs text-slate-500">ارسال شده به شماره:</span>
                                <span class="text-sm font-black text-slate-800 dir-ltr inline-block mx-1"><?= htmlspecialchars($_SESSION['reg_flow']['phone'] ?? '') ?></span>
                            </div>

                            <div>
                                <label class="block text-center text-xs font-bold text-slate-700 mb-2">کد تایید ۶ رقمی را وارد کنید</label>
                                <input type="text" name="otp" maxlength="6" autofocus required class="w-full h-14 text-center text-2xl font-black tracking-widest text-sky-700 rounded-xl border-2 border-slate-300 focus:border-sky-600 focus:ring-2 focus:ring-sky-600/20 dir-ltr bg-slate-50/50" placeholder="------">
                            </div>

                            <button type="submit" class="w-full h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-lg">check_circle</span>
                                <span>تایید و ادامه</span>
                            </button>

                            <div class="flex items-center justify-between text-xs pt-2">
                                <span id="countdown_timer" class="text-slate-500">زمان باقیمانده: ۱۲۰ ثانیه</span>
                                <a href="register.php?reset=1" class="text-sky-600 hover:underline font-bold">تغییر شماره تلفن</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- STEP 3: PROFESSIONAL CREDENTIALS & UPLOAD -->
        <?php if ($step === 3): ?>
            <?php 
                $selectedRole = $_SESSION['reg_flow']['role'] ?? 'doctor'; 
            ?>
            <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/80">
                <div class="max-w-2xl mx-auto">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <span class="material-symbols-outlined text-3xl">verified_user</span>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 mb-1">احراز صلاحیت و بارگذاری مدارک قانونی</h2>
                        <p class="text-slate-500 text-xs">جهت حفظ سلامت حیوانات خانگی و امنیت شبکه، مدارک هویتی و پروانه فعالیت شما بررسی می‌شود.</p>
                    </div>

                    <form method="POST" action="register.php?step=3" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="submit_credentials">

                        <!-- Role specifics: Doctor -->
                        <?php if ($selectedRole === 'doctor'): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره نظام دامپزشکی *</label>
                                    <input type="text" name="license_number" required placeholder="مثال: ۱۲۳۴۵" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-indigo-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تخصص و گرایش *</label>
                                    <select name="specialty" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-indigo-500 text-sm bg-white">
                                        <option value="دامپزشک عمومی">دامپزشک عمومی (حیوانات کوچک)</option>
                                        <option value="متخصص جراحی دامپزشکی">متخصص جراحی دامپزشکی</option>
                                        <option value="متخصص داخلی دام‌های کوچک">متخصص بیماری‌های داخلی</option>
                                        <option value="متخصص رادیولوژی و سونوگرافی">رادیولوژی و تصویربرداری تشخیصی</option>
                                        <option value="متخصص پرندگان و حیوانات اگزوتیک">پرندگان و حیوانات اگزوتیک</option>
                                        <option value="دندانپزشک دامپزشکی">دندانپزشکی دامپزشکی</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">تصویر کارت نظام دامپزشکی یا مدرک تحصیلی (PDF/JPG/PNG) *</label>
                                <input type="file" name="degree_document" accept=".jpg,.jpeg,.png,.pdf" required class="w-full p-2.5 rounded-xl border border-dashed border-slate-400 bg-slate-50 text-xs">
                                <span class="text-[11px] text-slate-600 block mt-1">فایل‌های معتبر: تصاویر یا PDF حداکثر تا حجم ۵ مگابایت</span>
                            </div>

                        <!-- Role specifics: Organization / Clinic / Hospital -->
                        <?php elseif ($selectedRole === 'organization'): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام بیمارستان / کلینیک *</label>
                                    <input type="text" name="organization_name" required placeholder="مثال: بیمارستان تخصصی دامپزشکی پارس" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-rose-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">رسته حقوقی و تخصصی مرکز *</label>
                                    <select name="organization_type" required class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-rose-500 text-sm bg-white font-bold">
                                        <option value="hospital">🏥 بیمارستان فوق‌تخصصی دامپزشکی</option>
                                        <option value="clinic" selected>🩺 کلینیک تخصصی و جراحی</option>
                                        <option value="pharmacy">💊 داروخانه مرجع دامپزشکی</option>
                                        <option value="shelter_charity">🐾 پناهگاه و خیریه حمایتی حیوانات</option>
                                        <option value="emergency_center">🚨 مرکز اورژانس شبانه‌روزی ۲۴ ساعته</option>
                                        <option value="diagnostic_lab">🔬 آزمایشگاه و تصویربرداری تشخیصی</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Organization Type Lock Notice -->
                            <div class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-600 text-lg shrink-0 mt-0.5">lock</span>
                                <div class="leading-relaxed">
                                    <span class="font-black block mb-0.5">قانون عدم تغییر مستقیم رسته پس از احراز:</span>
                                    رسته انتخابی مرکز بر اساس پروانه بهره‌برداری شما بررسی و در پنل مدیریت قفل خواهد شد. تغییر بعدی آن در پنل غیرقابل تغییر مستقیم بوده و صرفاً منوط به ارسال تیکت رسمی به مدیریت کلان و تایید کارشناسان ارشد خواهد بود.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره پروانه بهره‌برداری / تاسیس *</label>
                                    <input type="text" name="license_number" required placeholder="مثال: ۱۰۲۳-الف" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-rose-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شهر *</label>
                                    <input type="text" name="city" required value="تهران" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-rose-500 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">نشانی دقیق مرکز *</label>
                                <textarea name="address" rows="2" required placeholder="خیابان، پلاک، طبقه و واحد" class="w-full p-3 rounded-xl border border-slate-300 focus:border-rose-500 text-sm"></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">آیدی صفحه اینستاگرام (اختیاری)</label>
                                    <input type="text" name="instagram" dir="ltr" placeholder="@clinic_sample" class="w-full h-12 px-4 rounded-xl border border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس وب‌سایت (اختیاری)</label>
                                    <input type="url" name="website" dir="ltr" placeholder="https://example.com" class="w-full h-12 px-4 rounded-xl border border-slate-300 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">تصویر پروانه بهره‌برداری مرکز (PDF/JPG/PNG) *</label>
                                <input type="file" name="license_document" accept=".jpg,.jpeg,.png,.pdf" required class="w-full p-2.5 rounded-xl border border-dashed border-slate-400 bg-slate-50 text-xs">
                            </div>

                        <!-- Role specifics: Pharmacist -->
                        <?php elseif ($selectedRole === 'pharmacist'): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام داروخانه دامپزشکی *</label>
                                    <input type="text" name="organization_name" required placeholder="داروخانه دامپزشکی سینا" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-teal-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره پروانه داروخانه *</label>
                                    <input type="text" name="license_number" required placeholder="مثال: د-۹۸۷۶" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-teal-500 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">تصویر پروانه مسئول فنی یا پروانه تأسیس داروخانه *</label>
                                <input type="file" name="license_document" accept=".jpg,.jpeg,.png,.pdf" required class="w-full p-2.5 rounded-xl border border-dashed border-slate-400 bg-slate-50 text-xs">
                            </div>

                        <!-- Role specifics: Supplier / Wholesaler -->
                        <?php elseif ($selectedRole === 'supplier'): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام شرکت بازرگانی یا برند *</label>
                                    <input type="text" name="organization_name" required placeholder="شرکت بازرگانی پیشرو پت" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-amber-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره ثبت شرکت / شناسه ملی *</label>
                                    <input type="text" name="license_number" required placeholder="مثال: ۵۴۲۱۹۰" class="w-full h-12 px-4 rounded-xl border border-slate-300 focus:border-amber-500 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">تصویر روزنامه رسمی یا مجوز پخش عمده *</label>
                                <input type="file" name="license_document" accept=".jpg,.jpeg,.png,.pdf" required class="w-full p-2.5 rounded-xl border border-dashed border-slate-400 bg-slate-50 text-xs">
                            </div>
                        <?php endif; ?>

                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-xs text-slate-600 flex items-center gap-1">
                                <span class="material-symbols-outlined text-base text-emerald-600">lock</span>
                                اسناد شما با پروتکل‌های امنیتی رمزنگاری نگهداری می‌شوند.
                            </span>
                            <button type="submit" class="px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-sky-600 hover:from-indigo-700 hover:to-sky-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition-all">
                                <span>ارسال مدارک و ثبت نهایی</span>
                                <span class="material-symbols-outlined text-lg">check</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- STEP 4: CELEBRATION & PENDING CONFIRMATION SCREEN -->
        <?php if ($step === 4): ?>
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-sm border border-slate-200/80 text-center max-w-xl mx-auto">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <span class="material-symbols-outlined text-5xl">task_alt</span>
                </div>
                
                <span class="inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs mb-3">
                    درخواست با موفقیت در سامانه ثبت شد
                </span>

                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3">مدارک شما در دست بررسی کارشناسان است</h2>
                
                <p class="text-slate-600 text-sm leading-relaxed mb-8">
                    کارشناسان ارشد نظارت و ممیزی سامانه جامع ASENA مدارک ارسالی شما را بررسی خواهند کرد. به محض تایید صلاحیت و فعال‌سازی نقش تخصصی شما، پیامک حاوی تاییدیه و لینک ورود به پنل اختصاصی برای شما ارسال خواهد شد.
                </p>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-right space-y-2 mb-8 text-xs text-slate-600">
                    <div class="flex items-center justify-between">
                        <span class="font-bold">میانگین زمان بررسی مدارک:</span>
                        <span class="text-slate-900 font-black">حداکثر ۲۴ ساعت کاری</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-bold">اطلاع‌رسانی وضعیت:</span>
                        <span class="text-emerald-700 font-bold">از طریق پیامک و پنل کاربری</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="index.php" class="px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-colors">
                        بازگشت به صفحه اصلی
                    </a>
                    <a href="login.php" class="px-6 py-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm shadow-md shadow-sky-600/20 transition-all">
                        ورود به حساب کاربری
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-600">
        <p>© <?= date('Y') ?> سامانه جامع سلامت و درمان حیوانات خانگی ASENA. تمامی حقوق محفوظ است.</p>
    </footer>

    <script>
        let chosenRole = 'customer';
        function selectRole(role) {
            chosenRole = role;
            document.querySelectorAll('.role-card').forEach(c => {
                c.classList.remove('border-sky-500', 'bg-sky-50/40');
            });
            const card = event.currentTarget;
            card.classList.add('border-sky-500', 'bg-sky-50/40');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }

        function goToStep2() {
            const roleEl = document.querySelector('input[name="selected_role"]:checked');
            const val = roleEl ? roleEl.value : 'customer';
            window.location.href = 'register.php?step=2&role=' + val;
        }

        // Countdown Timer for OTP
        const cdEl = document.getElementById('countdown_timer');
        if (cdEl) {
            let left = 120;
            const iv = setInterval(() => {
                left--;
                if (left <= 0) {
                    clearInterval(iv);
                    cdEl.innerText = 'کد منقضی شد. لطفاً دوباره تلاش کنید.';
                } else {
                    cdEl.innerText = 'زمان باقیمانده: ' + left + ' ثانیه';
                }
            }, 1000);
        }
    </script>
</body>
</html>
