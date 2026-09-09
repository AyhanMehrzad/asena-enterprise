<?php
/**
 * ASENA Enterprise - Developer Auto-Login Hub & Role Switcher
 * 
 * Provides 1-click authentication and direct panel links for all platform roles:
 * Seller, Organization/Clinic, Doctor, Pharmacist, Super Admin, and Regular User.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

// Role configurations and metadata
$rolesConfig = [
    'seller' => [
        'title'        => 'فروشنده مستقل و پت‌شاپ',
        'subtitle'     => 'Seller & Petshop Merchant',
        'badge'        => 'تجاری و فروشگاهی',
        'color'        => 'amber',
        'gradient'     => 'from-amber-500 to-orange-600',
        'bg_light'     => 'bg-amber-50 border-amber-200 text-amber-900',
        'icon'         => 'storefront',
        'phone'        => '09120000003',
        'default_name' => 'فروشگاه پت‌پارس (فروشنده رسمی)',
        'target'       => 'seller/index.php',
        'description'  => 'مدیریت محصولات، پردازش سفارشات، ثبت بارکد رهگیری پست و کیف پول امانی با تسویه پایا.',
        'features'     => ['ویترین و انبار محصولات', 'پردازش سفارشات و ارسال', 'ثبت بارکد ۲۴ رقمی پست', 'تسویه پایا بانک مرکزی']
    ],
    'organization' => [
        'title'        => 'مدیریت مرکز درمانی و بیمارستان',
        'subtitle'     => 'Clinic & Hospital Management',
        'badge'        => 'سازمانی و درمانی',
        'color'        => 'indigo',
        'gradient'     => 'from-indigo-600 to-blue-700',
        'bg_light'     => 'bg-indigo-50 border-indigo-200 text-indigo-900',
        'icon'         => 'domain',
        'phone'        => '09122193637',
        'default_name' => 'دکتر نیما ارجمند (کلینیک دامپزشکی پایتخت)',
        'target'       => 'organization/index.php',
        'description'  => 'پیشخوان بیمارستان، پزشکان همکار، نوبت‌دهی مراجعین، انبار دارویی و کیف پول مرکز درمانی.',
        'features'     => ['پیشخوان مدیریتی کلینیک', 'تقویم نوبت‌ها و مراجعین', 'کادر درمانی و پزشکان', 'انبار دارویی و ملزومات']
    ],
    'doctor' => [
        'title'        => 'پزشک و جراح متخصص دامپزشک',
        'subtitle'     => 'Veterinarian & Surgeon',
        'badge'        => 'کادر درمان',
        'color'        => 'emerald',
        'gradient'     => 'from-emerald-600 to-teal-700',
        'bg_light'     => 'bg-emerald-50 border-emerald-200 text-emerald-900',
        'icon'         => 'stethoscope',
        'phone'        => '09129972056',
        'default_name' => 'دکتر کیان رستمی (متخصص جراحی)',
        'target'       => 'doctor/index.php',
        'description'  => 'تقویم نوبت‌های روزانه، ویزیت آنلاین/حضوری، پرونده‌های بالینی و صدور نسخه الکترونیک (Rx).',
        'features'     => ['تقویم ویزیت‌های روزانه', 'صدور نسخه الکترونیک Rx', 'پرونده الکترونیک سلامت', 'بدون دغدغه تسویه مالی']
    ],
    'pharmacist' => [
        'title'        => 'دکتر داروساز و مسئول فنی داروخانه',
        'subtitle'     => 'Pharmacist & Medical Dispensary',
        'badge'        => 'داروخانه مرجع',
        'color'        => 'purple',
        'gradient'     => 'from-purple-600 to-fuchsia-700',
        'bg_light'     => 'bg-purple-50 border-purple-200 text-purple-900',
        'icon'         => 'prescriptions',
        'phone'        => '09120000004',
        'default_name' => 'دکتر مریم شمس (مسئول فنی داروخانه)',
        'target'       => 'pharmacist/index.php',
        'description'  => 'کارتابل بررسی و تایید نسخه‌ها، کنترل زنجیره سرد، نظارت بر سری ساخت و تاریخ انقضای داروها.',
        'features'     => ['کارتابل تایید نسخه‌ها', 'پایش سری ساخت و انقضا', 'کنترل زنجیره سرد واکسن', 'بررسی تداخلات دارویی']
    ],
    'admin' => [
        'title'        => 'مدیر ارشد سامانه آسنا',
        'subtitle'     => 'Super Administrator',
        'badge'        => 'دسترسی کلان',
        'color'        => 'rose',
        'gradient'     => 'from-rose-600 to-red-700',
        'bg_light'     => 'bg-rose-50 border-rose-200 text-rose-900',
        'icon'         => 'shield_person',
        'phone'        => '09123456789',
        'default_name' => 'مدیر ارشد سامانه آسنا',
        'target'       => 'admin/index.php',
        'description'  => 'نظارت بر کل اکوسیستم، تایید مراکز درمانی و پزشکان، صدور حواله‌های تسویه پایا و آمار پلتفرم.',
        'features'     => ['داشبورد جامع نظارتی', 'میزکار حواله‌های پایا', 'مدیریت کل مراکز و پزشکان', 'تنظیمات کلان اکوسیستم']
    ],
    'user' => [
        'title'        => 'کاربر عادی و سرپرست حیوان خانگی',
        'subtitle'     => 'Pet Owner & End Customer',
        'badge'        => 'مشتری نهایی',
        'color'        => 'sky',
        'gradient'     => 'from-sky-600 to-cyan-700',
        'bg_light'     => 'bg-sky-50 border-sky-200 text-sky-900',
        'icon'         => 'pets',
        'phone'        => '09000000001',
        'default_name' => 'سارا محمدی (کاربر آزمایشی)',
        'target'       => 'profile.php',
        'description'  => 'پروفایل کاربری، کیف پول اعتباری، سفارش کالا و غذا، رزرو نوبت و پیگیری درمان پت‌ها.',
        'features'     => ['پروفایل کاربری و اطلاعات پت', 'کیف پول شارژ اعتباری', 'پیگیری آنلاین سفارشات', 'نوبت‌های رزرو شده']
    ]
];

// Helper: authenticate a given role
function loginAsRole($pdo, $role, $cfg) {
    // 1. Try finding by preferred demo phone
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$cfg['phone']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Fallback: find any user with that role
    if (!$user) {
        $stmtRole = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY id ASC LIMIT 1");
        $stmtRole->execute([$role]);
        $user = $stmtRole->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Fallback: create demo user if not existing
    if (!$user) {
        $hash = password_hash('Asena1234!', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("
            INSERT INTO users (phone, name, password, role, verification_status, loyalty_points, created_at)
            VALUES (?, ?, ?, ?, 'approved', 100, NOW())
        ");
        $ins->execute([$cfg['phone'], $cfg['default_name'], $hash, $role]);
        $uid = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Set authenticated session
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['name']      = $user['name'] ?: $cfg['default_name'];
    $_SESSION['phone']     = $user['phone'] ?? $cfg['phone'];

    return $user;
}

// ── Check Query Actions ───────────────────────────────────────────────────────
$action = strtolower(trim($_GET['action'] ?? ''));
$reqRole = strtolower(trim($_GET['role'] ?? ''));

// Handle Logout
if ($action === 'logout' || $reqRole === 'logout' || $reqRole === 'guest') {
    unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['role'], $_SESSION['name'], $_SESSION['phone']);
    session_regenerate_id(true);
    header("Location: auto_login.php?logged_out=1");
    exit;
}

// Handle Direct 1-Click Login Request
if (!empty($reqRole) && isset($rolesConfig[$reqRole])) {
    $cfg = $rolesConfig[$reqRole];
    $loggedUser = loginAsRole($pdo, $reqRole, $cfg);
    
    // Redirect straight to panel
    header("Location: " . $cfg['target']);
    exit;
}

// Read Current Session Info
$currentUserId   = $_SESSION['user_id'] ?? null;
$currentUserRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
$currentUserName = $_SESSION['name'] ?? null;

// Dynamic fetch real user details for each card
$usersInfo = [];
foreach ($rolesConfig as $rKey => $cfg) {
    $stmt = $pdo->prepare("SELECT id, name, phone, role FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$cfg['phone']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        $stmt = $pdo->prepare("SELECT id, name, phone, role FROM users WHERE role = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$rKey]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $usersInfo[$rKey] = $u ?: ['id' => '-', 'name' => $cfg['default_name'], 'phone' => $cfg['phone']];
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>ورود خودکار و انتخاب سریع نقش | سامانه جامع آسنا</title>
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="assets/css/geist.css" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body class="text-slate-800 p-4 sm:p-6 lg:p-10 flex flex-col justify-between">

    <!-- Main Container -->
    <div class="max-w-7xl w-full mx-auto space-y-8">

        <!-- Top Header Bar -->
        <header class="flex flex-col md:flex-row items-center justify-between gap-4 bg-slate-900/80 border border-slate-700/80 rounded-3xl p-5 sm:p-6 backdrop-blur-xl shadow-2xl text-white">
            <div class="flex items-center gap-4">
                <a href="index.php" class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-sky-500/30 hover:scale-105 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-3xl text-white">pets</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight">میزکار توسعه: ورود خودکار به نقش‌ها</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            حالت تست و دولوپر
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        برای دسترسی بدون دردسر به پنل‌های مختلف، روی هر یک از نقش‌های زیر کلیک کنید تا سشن فعال شده و مستقیماً وارد شوید.
                    </p>
                </div>
            </div>

            <!-- Header Quick Actions & Current Session -->
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-start md:justify-end">
                <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">home</span>
                    <span>صفحه اصلی</span>
                </a>
                <a href="organizations.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">domain</span>
                    <span>مراکز درمانی</span>
                </a>
                <a href="shop.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">shopping_cart</span>
                    <span>فروشگاه</span>
                </a>
                <a href="login.php" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">login</span>
                    <span>لاگین اصلی</span>
                </a>
            </div>
        </header>

        <!-- Current Session Banner -->
        <?php if (!empty($currentUserId)): ?>
            <div class="bg-gradient-to-r from-emerald-950/80 via-slate-900 to-indigo-950/80 border border-emerald-500/40 rounded-2xl p-4 sm:p-5 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">verified_user</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-300">نشست فعال جاری:</span>
                            <strong class="text-sm font-black text-white"><?= htmlspecialchars($currentUserName ?? 'کاربر') ?></strong>
                            <span class="px-2 py-0.5 rounded-lg text-[11px] font-black bg-emerald-500 text-slate-950">
                                نقش: <?= htmlspecialchars($currentUserRole ?? 'نامشخص') ?>
                            </span>
                            <span class="text-[11px] text-slate-400">(شناسه: #<?= (int)$currentUserId ?>)</span>
                        </div>
                        <p class="text-[11px] text-emerald-300/80 mt-0.5">
                            سشن شما فعال است و می‌توانید بدون احراز مجدد وارد پنل‌های این نقش شوید.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <?php if (isset($rolesConfig[$currentUserRole])): ?>
                        <a href="<?= htmlspecialchars($rolesConfig[$currentUserRole]['target']) ?>" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow-md flex items-center gap-1.5">
                            <span>ورود به پنل فعلی</span>
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                        </a>
                    <?php endif; ?>
                    <a href="auto_login.php?action=logout" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white border border-rose-500/30 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">logout</span>
                        <span>خروج / پاکسازی سشن</span>
                    </a>
                </div>
            </div>
        <?php elseif (isset($_GET['logged_out'])): ?>
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 text-amber-200 flex items-center gap-3">
                <span class="material-symbols-outlined text-xl text-amber-400">check_circle</span>
                <span class="text-xs font-bold">نشست با موفقیت پاکسازی شد. اکنون به عنوان مهمان هستید. برای ورود هر نقشی را انتخاب کنید.</span>
            </div>
        <?php endif; ?>

        <!-- Role Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($rolesConfig as $rKey => $cfg): 
                $uInfo = $usersInfo[$rKey];
                $isCurrentRole = ($currentUserRole === $rKey);
            ?>
                <div class="glass-card rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden <?= $isCurrentRole ? 'ring-2 ring-emerald-500 ring-offset-2 ring-offset-slate-900' : '' ?>">
                    
                    <!-- Decorative Gradient Accent Bar -->
                    <div class="absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r <?= $cfg['gradient'] ?>"></div>

                    <!-- Top Card Header -->
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr <?= $cfg['gradient'] ?> text-white flex items-center justify-center shadow-md shrink-0">
                                <span class="material-symbols-outlined text-2xl"><?= $cfg['icon'] ?></span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black <?= $cfg['bg_light'] ?>">
                                    <?= $cfg['badge'] ?>
                                </span>
                                <?php if ($isCurrentRole): ?>
                                    <span class="text-[10px] font-black text-emerald-600 mt-1 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        نقش فعال شما
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Titles -->
                        <h2 class="text-base font-black text-slate-900 mb-0.5">
                            <?= $cfg['title'] ?>
                        </h2>
                        <div class="text-[11px] font-bold text-slate-400 mb-3" dir="ltr">
                            <?= $cfg['subtitle'] ?>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            <?= $cfg['description'] ?>
                        </p>

                        <!-- Demo User Info -->
                        <div class="bg-slate-100/90 rounded-2xl p-3 space-y-1.5 border border-slate-200/80 mb-4 text-xs">
                            <div class="flex items-center justify-between text-slate-500 text-[11px]">
                                <span>کاربر ثبت‌شده دمو:</span>
                                <strong class="text-slate-800 font-black"><?= htmlspecialchars($uInfo['name']) ?></strong>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[11px]">
                                <span>شماره موبایل جهت تست:</span>
                                <span class="dir-ltr font-mono font-bold text-slate-700"><?= htmlspecialchars($uInfo['phone']) ?></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[11px] pt-1 border-t border-slate-200">
                                <span>مسیر نهایی پنل:</span>
                                <code class="dir-ltr text-[10px] font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded"><?= htmlspecialchars($cfg['target']) ?></code>
                            </div>
                        </div>

                        <!-- Feature Pills -->
                        <div class="flex flex-wrap gap-1.5 mb-5">
                            <?php foreach ($cfg['features'] as $feat): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-50 text-slate-600 border border-slate-200/60">
                                    ✓ <?= $feat ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-2 border-t border-slate-100 flex flex-col gap-2">
                        <!-- 1-Click Login Button -->
                        <a href="auto_login.php?role=<?= urlencode($rKey) ?>" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r <?= $cfg['gradient'] ?> hover:opacity-95 text-white text-xs font-black flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all active:scale-[0.98]">
                            <span class="material-symbols-outlined text-base">login</span>
                            <span>ورود فوری با ۱ کلیک</span>
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                        </a>

                        <!-- Direct Panel Link -->
                        <a href="<?= htmlspecialchars($cfg['target']) ?>" target="_blank" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            <span>باز کردن مستقیم پنل در تب جدید</span>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer Notice -->
        <footer class="text-center text-xs text-slate-400 pt-4 pb-8 space-y-2">
            <p>
                💡 <strong>راهنما:</strong> پس از ورود خودکار، تمام سشن‌ها و احراز هویت‌های <code>AuthGuard</code> تنظیم شده و تا زمان خروج معتبر خواهند بود.
            </p>
            <p class="text-slate-500 text-[11px]">
                رمز عبور تمامی اکانت‌های فوق در صورت نیاز به فرم لاگین عادی: <code class="font-mono text-amber-400 bg-slate-900 px-1.5 py-0.5 rounded">Asena1234!</code> می‌باشد.
            </p>
        </footer>

    </div>

</body>
</html>
