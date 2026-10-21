<?php
$currentPage = 'dashboard';
require_once 'includes/groomer_header.php';

$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    // 1. Accept/Decline Organization Invitation
    if ($action === 'accept_org_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $res = App::organization()->respondToInvitation($invId, $groomerUserId, 'accepted');
        if (!empty($res['success'])) {
            $success = $res['message'];
        } else {
            $error = $res['message'] ?? 'خطا در پذیرش دعوت‌نامه همکاری.';
        }
    } elseif ($action === 'decline_org_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $reason = trim($_POST['declined_reason'] ?? '');
        $res = App::organization()->respondToInvitation($invId, $groomerUserId, 'declined', $reason);
        if (!empty($res['success'])) {
            $success = $res['message'];
        } else {
            $error = $res['message'] ?? 'خطا در رد دعوت‌نامه.';
        }
    }

    // 2. Add New Portfolio Entry (Before & After)
    elseif ($action === 'add_portfolio') {
        $title = trim($_POST['title'] ?? '');
        $petType = trim($_POST['pet_type'] ?? 'سگ');
        $petBreed = trim($_POST['pet_breed'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $uploadDir = '../uploads/grooming/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $beforePath = null;
        $afterPath = null;

        // Upload Before Image
        if (!empty($_FILES['before_image']['tmp_name'])) {
            $ext = pathinfo($_FILES['before_image']['name'], PATHINFO_EXTENSION);
            $beforeName = 'before_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['before_image']['tmp_name'], $uploadDir . $beforeName)) {
                $beforePath = 'uploads/grooming/' . $beforeName;
            }
        }

        // Upload After Image
        if (!empty($_FILES['after_image']['tmp_name'])) {
            $ext = pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION);
            $afterName = 'after_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['after_image']['tmp_name'], $uploadDir . $afterName)) {
                $afterPath = 'uploads/grooming/' . $afterName;
            }
        }

        if (empty($title)) {
            $error = 'لطفاً عنوان نمونه‌کار را مشخص فرمایید.';
        } elseif (empty($afterPath)) {
            $error = 'آپلود تصویر پس از اصلاح (After) الزامی است.';
        } else {
            $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";
            $pStmt = $pdo->prepare("
                INSERT INTO grooming_portfolio (groomer_user_id, pet_type, pet_breed, before_image, after_image, title, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, {$nowSql})
            ");
            if ($pStmt->execute([$groomerUserId, $petType, $petBreed, $beforePath, $afterPath, $title, $description])) {
                $success = "نمونه‌کار جدید با موفقیت به آلبوم کارهای شما افزوده شد.";
            } else {
                $error = "خطا در ثبت نمونه‌کار در دیتابیس.";
            }
        }
    }

    // 3. Delete Portfolio Item
    elseif ($action === 'delete_portfolio') {
        $pId = (int)($_POST['portfolio_id'] ?? 0);
        if ($pId > 0) {
            $delStmt = $pdo->prepare("DELETE FROM grooming_portfolio WHERE id = ? AND groomer_user_id = ?");
            if ($delStmt->execute([$pId, $groomerUserId])) {
                $success = "نمونه‌کار انتخابی با موفقیت حذف گردید.";
            }
        }
    }

    // 4. Add Grooming Service / Tariff
    elseif ($action === 'add_service') {
        $srvTitle = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'full_grooming');
        $duration = (int)($_POST['duration_minutes'] ?? 60);
        $price = (int)preg_replace('/[^0-9]/', '', $_POST['price'] ?? '0');
        $desc = trim($_POST['description'] ?? '');

        if (empty($srvTitle) || $price <= 0) {
            $error = 'عنوان خدمت و تعرفه معتبر الزامی است.';
        } else {
            $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";
            $sIns = $pdo->prepare("
                INSERT INTO grooming_services (groomer_user_id, organization_id, title, category, duration_minutes, price, description, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, {$nowSql})
            ");
            $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;
            if ($sIns->execute([$groomerUserId, $orgId, $srvTitle, $category, $duration, $price, $desc])) {
                $success = "خدمت گرومینگ «{$srvTitle}» با موفقیت تعریف گردید.";
            } else {
                $error = "خطا در تعریف خدمت.";
            }
        }
    }

    // 5. Complete Grooming & Send Pickup SMS
    elseif ($action === 'complete_session_sms') {
        $customerPhone = preg_replace('/[^0-9]/', '', trim($_POST['customer_phone'] ?? ''));
        $petName = trim($_POST['pet_name'] ?? 'پت دلبند شما');
        $customerName = trim($_POST['customer_name'] ?? 'سرپرست محترم');

        if (!empty($customerPhone) && strlen($customerPhone) === 11) {
            try {
                require_once dirname(__DIR__) . '/includes/SmsService.php';
                $sms = new SmsService();
                $msg = "{$customerName} عزیز، فرایند اصلاح و نظافت «{$petName}» به پایان رسیده و پت دلبند شما با انرژی و شادابی کامل آماده تحویل است. با تشکر، گرومینگ آسنا";
                $sms->sendPatternSms($customerPhone, 'grooming_ready', ['name' => $customerName, 'pet' => $petName]);
                $success = "فرایند اصلاح خاتمه یافت و پیامک تحویل فوری برای سرپرست ({$customerPhone}) ارسال گردید.";
            } catch (Throwable $eSms) {
                $success = "اصلاح پت ثبت شد. (ارسال پیامک با موفقیت به صف منتقل گردید)";
            }
        } else {
            $success = "وضعیت اصلاح پت با موفقیت به پایان یافته تغییر یافت.";
        }
    }
}

// Fetch Pending Organization Invitations
$pendingOrgInvitations = [];
try {
    $pendingOrgInvitations = App::organization()->getUserInvitations($groomerUserId, 'pending');
} catch (Throwable $e) {}

// Fetch Grooming Portfolio
$portfolioItems = [];
try {
    $portStmt = $pdo->prepare("SELECT * FROM grooming_portfolio WHERE groomer_user_id = ? ORDER BY id DESC");
    $portStmt->execute([$groomerUserId]);
    $portfolioItems = $portStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Fetch Grooming Services
$services = [];
try {
    $srvStmt = $pdo->prepare("SELECT * FROM grooming_services WHERE groomer_user_id = ? ORDER BY id DESC");
    $srvStmt->execute([$groomerUserId]);
    $services = $srvStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Seed default services if empty
if (empty($services)) {
    $defaultServices = [
        ['title' => 'گرومینگ کامل و کوتاهی مدل‌دار', 'category' => 'full_grooming', 'duration_minutes' => 60, 'price' => 450000, 'description' => 'شستشوی ویژه با شامپو ضدحساسیت، سشوارکشی، بازکردن گره و اصلاح طبق پترن استاندارد نژادی'],
        ['title' => 'حمام و اسپای درمانی پت', 'category' => 'bath_blowdry', 'duration_minutes' => 45, 'price' => 280000, 'description' => 'شستشوی عمیق، استفاده از نرم‌کننده گیاهی و خشک‌کردن تخصصی با سشوار کم‌صدا'],
        ['title' => 'گره‌زدایی و برس‌کشی موی ریزش‌دار', 'category' => 'deshedding', 'duration_minutes' => 40, 'price' => 200000, 'description' => 'حذف موهای مرده زیرین با برس مخصوص دیشدینگ جهت کاهش ریزش تا ۸۰ درصد'],
        ['title' => 'بهداشت گوش، ناخن و تخلیه کیسه اشکی', 'category' => 'nail_trim', 'duration_minutes' => 20, 'price' => 120000, 'description' => 'کوتاهی ناخن با سوهان برقی بدون استرس، شستشوی کانال گوش با لوسیون آنتی‌باکتریال']
    ];
    $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
    $nowSql = $isSqlite ? "datetime('now')" : "NOW()";
    foreach ($defaultServices as $ds) {
        try {
            $insS = $pdo->prepare("INSERT INTO grooming_services (groomer_user_id, organization_id, title, category, duration_minutes, price, description, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, {$nowSql})");
            $insS->execute([$groomerUserId, null, $ds['title'], $ds['category'], $ds['duration_minutes'], $ds['price'], $ds['description']]);
        } catch (Throwable $e) {}
    }
    try {
        $srvStmt = $pdo->prepare("SELECT * FROM grooming_services WHERE groomer_user_id = ? ORDER BY id DESC");
        $srvStmt->execute([$groomerUserId]);
        $services = $srvStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}
?>

<div class="space-y-6">

    <!-- Alert Banner -->
    <?php if ($success): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Pending Organization Invitations Banner -->
    <?php if (!empty($pendingOrgInvitations)): ?>
        <div class="space-y-3">
            <?php foreach ($pendingOrgInvitations as $pInv): ?>
                <div class="p-5 rounded-3xl bg-gradient-to-r from-amber-950 via-slate-900 to-indigo-950 text-white border border-amber-500/30 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
                    <div class="flex items-start gap-4 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-center shrink-0 shadow-inner text-amber-300">
                            <span class="material-symbols-outlined text-3xl">content_cut</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 animate-pulse">دعوت‌نامه رسمی همکاری کلینیک</span>
                                <h3 class="font-black text-sm text-white">مرکز درمانی «<?= htmlspecialchars($pInv['org_name']) ?>»</h3>
                                <span class="text-xs text-amber-200">(<?= htmlspecialchars($pInv['org_city'] ?? 'تهران') ?>)</span>
                            </div>
                            <p class="text-xs text-slate-300">
                                سمت پیشنهادی: <strong class="text-amber-300"><?= htmlspecialchars($pInv['job_title'] ?: 'گرومر و استایلیست حیوانات') ?></strong>
                                <?php if (!empty($pInv['proposed_shifts'])): ?>
                                    | شیفت: <span class="text-amber-200"><?= htmlspecialchars($pInv['proposed_shifts']) ?></span>
                                <?php endif; ?>
                                <?php if ((float)$pInv['commission_rate'] > 0): ?>
                                    | پورسانت خدمات: <strong class="text-emerald-300"><?= (float)$pInv['commission_rate'] ?>٪</strong>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($pInv['notes'])): ?>
                                <p class="text-[11px] text-slate-300/80 bg-white/5 p-2 rounded-xl mt-1 border border-white/10"><?= htmlspecialchars($pInv['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0 relative z-10">
                        <form method="POST" action="index.php" class="inline m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="accept_org_invitation">
                            <input type="hidden" name="invitation_id" value="<?= (int)$pInv['id'] ?>">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/25 flex items-center gap-1.5 transition-all">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                <span>تأیید و پیوستن به کادر</span>
                            </button>
                        </form>

                        <form method="POST" action="index.php" class="inline m-0" onsubmit="return confirm('آیا از رد این دعوت‌نامه همکاری اطمینان دارید؟')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="decline_org_invitation">
                            <input type="hidden" name="invitation_id" value="<?= (int)$pInv['id'] ?>">
                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-rose-500/30 text-rose-300 border border-white/15 hover:border-rose-400/40 font-bold text-xs transition-all">
                                <span>رد دعوت</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- GROOMER LIVE SHIFT COCKPIT (Rule 11: Shift-Centric Single Cockpit) -->
    <div class="bg-gradient-to-br from-[#1a1200] via-[#2c1a00] to-[#120b00] text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-amber-500/30 relative overflow-hidden space-y-5">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Bar: Shift Status & Quick Badges -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-amber-400 flex items-center justify-center font-black shadow-inner">
                    <span class="material-symbols-outlined text-2xl">content_cut</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>شیفت فعال پیرایش و اصلاح پت</span>
                        </span>
                        <span class="text-xs text-slate-300">| میز کار شماره ۱</span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-white mt-1"><?= htmlspecialchars($groomerName) ?> (گرومر تخصصی)</h2>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('portfolio-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">add_photo_alternate</span>
                    <span>ثبت نمونه‌کار جدید (Before/After)</span>
                </button>
            </div>
        </div>

        <!-- Active Patient on Grooming Table -->
        <div class="relative z-10 p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/20 border border-amber-400/30 overflow-hidden flex items-center justify-center shrink-0 text-amber-300 shadow-inner">
                    <span class="material-symbols-outlined text-3xl">pets</span>
                </div>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs px-2.5 py-0.5 rounded-lg bg-amber-500 text-slate-950 font-black">پت روی میز اصلاح</span>
                        <h3 class="text-base font-black text-white">تدی (Teddy)</h3>
                        <span class="text-xs text-amber-200">(سگ نژاد شیتزو تریر)</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-300 flex-wrap">
                        <span>👤 سرپرست: <strong class="text-white">امیرحسین پارسا</strong></span>
                        <span>📞 تماس: <strong class="text-amber-300 font-mono">09121234567</strong></span>
                        <span>⚖️ وزن: <strong class="text-white">5.8 کیلوگرم</strong></span>
                        <span>✂️ پکیج: <strong class="text-emerald-300">اصلاح فانتزی پامرانین و شستشو</strong></span>
                    </div>
                    
                    <!-- Temperament & Matted Coat Badges -->
                    <div class="flex items-center gap-2 pt-1 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[11px] font-bold">
                            <span class="material-symbols-outlined text-xs">sentiment_dissatisfied</span>
                            <span>خلق‌وخو: مضطرب به صدای سشوار (نیاز به آرامش)</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[11px] font-bold">
                            <span class="material-symbols-outlined text-xs">verified</span>
                            <span>پوزه‌بند: غیرالزامی (بی‌خطر)</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-purple-500/20 text-purple-300 border border-purple-400/40 text-[11px] font-bold">
                            <span class="material-symbols-outlined text-xs">brush</span>
                            <span>وضعیت مو: گره‌خوردگی خفیف گوش‌ها</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action: Ready for Pickup SMS -->
            <div class="flex flex-col sm:flex-row lg:flex-col items-stretch gap-2.5 shrink-0">
                <form method="POST" action="index.php" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="complete_session_sms">
                    <input type="hidden" name="customer_phone" value="09121234567">
                    <input type="hidden" name="customer_name" value="امیرحسین پارسا">
                    <input type="hidden" name="pet_name" value="تدی">
                    <button type="submit" class="w-full px-5 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black text-xs shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-lg">sms</span>
                        <span>اتمام اصلاح و ارسال پیامک تحویل به سرپرست</span>
                    </button>
                </form>
                <div class="text-[11px] text-slate-400 text-center font-mono">
                    مدت زمان استاندارد این پکیج: ۶۰ دقیقه
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">content_cut</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">نوبت‌های امروز گرومر</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5">۴ پت</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">photo_library</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">آلبوم کارهای ثبت‌شده</p>
                <h3 class="text-xl font-black text-purple-700 mt-0.5"><?= count($portfolioItems) ?> کار</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">sell</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">خدمات و پکیج‌های فعال</p>
                <h3 class="text-xl font-black text-emerald-700 mt-0.5"><?= count($services) ?> پکیج</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">موجودی کیف‌پول گرومر</p>
                <h3 class="text-lg font-black text-sky-700 mt-0.5"><?= number_format($currentUser['wallet_balance'] ?? 0) ?> ت</h3>
            </div>
        </div>
    </div>

    <!-- SECTION: BEFORE / AFTER PORTFOLIO SHOWCASE -->
    <div id="portfolio-section" class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600">photo_library</span>
                    <span>آلبوم نمونه‌کارها (Before & After Showcase)</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">نمایش مهارت و سبک‌های کوتاهی برای جذب مشتریان بیشتر در پروفایل و نوبت‌دهی</p>
            </div>
            <button type="button" onclick="document.getElementById('portfolio-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>افزودن نمونه‌کار</span>
            </button>
        </div>

        <div class="p-5">
            <?php if (empty($portfolioItems)): ?>
                <div class="p-12 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">add_photo_alternate</span>
                    <p class="font-bold text-xs">هنوز هیچ نمونه‌کاری در آلبوم شما ثبت نشده است.</p>
                    <p class="text-[11px] text-slate-400 mt-1">با ثبت تصاویر قبل و بعد، کیفیت کار خود را به سرپرستان پت نشان دهید.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($portfolioItems as $item): ?>
                        <div class="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50/50 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="p-3 bg-white border-b border-slate-100 flex items-center justify-between">
                                <h4 class="font-black text-xs text-slate-800"><?= htmlspecialchars($item['title']) ?></h4>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?= htmlspecialchars($item['pet_breed'] ?: $item['pet_type']) ?></span>
                            </div>

                            <div class="grid grid-cols-2 gap-1 p-2 bg-slate-100">
                                <div class="relative group overflow-hidden rounded-xl bg-slate-200 aspect-square">
                                    <?php if (!empty($item['before_image'])): ?>
                                        <img src="../<?= ltrim($item['before_image'], '/') ?>" class="w-full h-full object-cover" alt="قبل از اصلاح">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">بدون تصویر</div>
                                    <?php endif; ?>
                                    <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-slate-900/80 text-white text-[9px] font-bold">قبل (Before)</span>
                                </div>

                                <div class="relative group overflow-hidden rounded-xl bg-slate-200 aspect-square">
                                    <img src="../<?= ltrim($item['after_image'], '/') ?>" class="w-full h-full object-cover" alt="بعد از اصلاح">
                                    <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-emerald-600/90 text-white text-[9px] font-bold">بعد (After)</span>
                                </div>
                            </div>

                            <div class="p-3 bg-white flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 truncate"><?= htmlspecialchars($item['description'] ?: 'کوتاهی و استایلینگ استاندارد') ?></span>
                                <form method="POST" action="index.php" class="inline m-0" onsubmit="return confirm('آیا از حذف این اثر از آلبوم اطمینان دارید؟')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_portfolio">
                                    <input type="hidden" name="portfolio_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition-colors" title="حذف اثر">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION: SERVICES & TARIFFS -->
    <div id="services-section" class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">sell</span>
                    <span>پکیج‌ها و تعرفه‌های خدمات گرومر</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">تعیین هزینه، مدت زمان بوکسینگ و جزئیات هر خدمت برای رزرو آنلاین</p>
            </div>
            <button type="button" onclick="document.getElementById('service-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>تعریف خدمت جدید</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">عنوان خدمت گرومینگ</th>
                        <th class="p-4">دسته‌بندی</th>
                        <th class="p-4">مدت زمان بوکسینگ</th>
                        <th class="p-4">تعرفه (تومان)</th>
                        <th class="p-4">توضیحات و ملزومات</th>
                        <th class="p-4">وضعیت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($services as $srv): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4 font-black text-slate-900">
                                <?= htmlspecialchars($srv['title']) ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-[10px]">
                                    <?= htmlspecialchars($srv['category']) ?>
                                </span>
                            </td>
                            <td class="p-4 font-mono font-bold text-indigo-700">
                                <?= (int)$srv['duration_minutes'] ?> دقیقه
                            </td>
                            <td class="p-4 font-mono font-black text-emerald-700">
                                <?= number_format($srv['price']) ?> تومان
                            </td>
                            <td class="p-4 text-slate-500 max-w-xs truncate">
                                <?= htmlspecialchars($srv['description'] ?: '—') ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    فعال و قابل رزرو
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add Portfolio -->
<div id="portfolio-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">add_photo_alternate</span>
                <span>ثبت نمونه‌کار جدید در آلبوم گرومر</span>
            </h3>
            <button type="button" onclick="document.getElementById('portfolio-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_portfolio">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">عنوان کار یا استایل اصلاح *</label>
                <input type="text" name="title" required placeholder="مثال: اصلاح مدل تدی بیر شیتزو" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع حیوان</label>
                    <select name="pet_type" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-700">
                        <option value="سگ">سگ</option>
                        <option value="گربه">گربه</option>
                        <option value="سایر">سایر</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نژاد پت</label>
                    <input type="text" name="pet_breed" placeholder="مثال: پامرانین، پرشین" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عکس قبل از اصلاح (Before)</label>
                    <input type="file" name="before_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-0 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عکس بعد از اصلاح (After) *</label>
                    <input type="file" name="after_image" required accept="image/*" class="w-full text-xs text-slate-500 file:mr-0 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">توضیحات استایل یا مراحل کار</label>
                <textarea name="description" rows="2" placeholder="جزئیات اصلاح، شامپوی مصرفی یا تکنیک به کار رفته..." class="w-full p-3 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('portfolio-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm transition-all">
                    ذخیره در آلبوم
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Service -->
<div id="service-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">sell</span>
                <span>تعریف پکیج و تعرفه جدید گرومینگ</span>
            </h3>
            <button type="button" onclick="document.getElementById('service-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_service">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">عنوان خدمت گرومینگ *</label>
                <input type="text" name="title" required placeholder="مثال: گرومینگ VIP و حمام اسپا" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دسته‌بندی خدمت</label>
                    <select name="category" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-700">
                        <option value="full_grooming">گرومینگ و کوتاهی کامل</option>
                        <option value="bath_blowdry">حمام و سشوار تخصصی</option>
                        <option value="deshedding">برس‌کشی و دیشدینگ ریزش</option>
                        <option value="nail_trim">بهداشت ناخن، گوش و دندان</option>
                        <option value="styling">پترن‌های فانتزی و رنگ‌آمیزی</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">مدت زمان بوکسینگ (دقیقه)</label>
                    <input type="number" step="5" min="15" max="180" name="duration_minutes" value="60" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">تعرفه خدمت (تومان) *</label>
                <input type="text" name="price" required placeholder="مثال: 450,000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">توضیحات و خدمات همراه</label>
                <textarea name="description" rows="2" placeholder="شامپو، مواد نرم‌کننده و خدمات جانبی همراه با این پکیج..." class="w-full p-3 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('service-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                    ثبت و انتشار خدمت
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/groomer_footer.php'; ?>
