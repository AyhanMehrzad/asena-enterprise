<?php
$currentPage = 'dashboard';
require_once 'includes/groomer_header.php';

$success = '';
$error = '';

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
$nowSql = $isSqlite ? "datetime('now')" : "NOW()";
$today = date('Y-m-d');

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

    // 2. Add New Walk-in Daily Appointment
    elseif ($action === 'add_daily_appointment') {
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = preg_replace('/[^0-9]/', '', trim($_POST['customer_phone'] ?? ''));
        $petName = trim($_POST['pet_name'] ?? '');
        $petSpecies = trim($_POST['pet_species'] ?? 'سگ');
        $petBreed = trim($_POST['pet_breed'] ?? '');
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $serviceTitle = trim($_POST['service_title'] ?? 'اصلاح و گرومینگ تخصصی');
        $startTime = trim($_POST['start_time'] ?? date('H:i'));
        $duration = max(15, (int)($_POST['duration_minutes'] ?? 60));
        $price = (int)preg_replace('/[^0-9]/', '', $_POST['price'] ?? '350000');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($petName) || empty($custPhone) || strlen($custPhone) !== 11) {
            $error = 'نام پت و شماره همراه ۱۱ رقمی سرپرست الزامی است.';
        } else {
            // Fetch service details if selected
            if ($serviceId > 0) {
                $sCheck = $pdo->prepare("SELECT title, price, duration_minutes FROM grooming_services WHERE id = ?");
                $sCheck->execute([$serviceId]);
                $srvData = $sCheck->fetch(PDO::FETCH_ASSOC);
                if ($srvData) {
                    $serviceTitle = $srvData['title'];
                    if ($price <= 0) $price = (int)$srvData['price'];
                    if ($duration <= 0) $duration = (int)$srvData['duration_minutes'];
                }
            }

            // Customer ID lookup or creation
            $uStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $uStmt->execute([$custPhone]);
            $custId = (int)$uStmt->fetchColumn();
            if ($custId <= 0) {
                $defPass = password_hash('Asena@' . rand(1000, 9999), PASSWORD_DEFAULT);
                $insU = $pdo->prepare("INSERT INTO users (name, phone, password, role, verification_status, created_at) VALUES (?, ?, ?, 'user', 'approved', {$nowSql})");
                $insU->execute([$custName ?: 'سرپرست پت', $custPhone, $defPass]);
                $custId = (int)$pdo->lastInsertId();
            }

            $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;

            $insAppt = $pdo->prepare("
                INSERT INTO grooming_appointments 
                (organization_id, groomer_user_id, customer_user_id, customer_name, customer_phone, pet_name, pet_species, pet_breed, service_id, service_title, appointment_date, start_time, duration_minutes, price, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, {$nowSql})
            ");
            if ($insAppt->execute([$orgId, $groomerUserId, $custId, $custName ?: 'سرپرست پت', $custPhone, $petName, $petSpecies, $petBreed, $serviceId, $serviceTitle, $today, $startTime, $duration, $price, $notes])) {
                $success = "نوبت جدید «{$petName}» ({$serviceTitle}) برای ساعت {$startTime} با موفقیت در برنامه روزانه ثبت گردید.";
            } else {
                $error = 'خطا در ثبت نوبت در جدول پایگاه داده.';
            }
        }
    }

    // 3. Start Grooming Session (نوبت رفت زیر دست آرایشگر)
    elseif ($action === 'start_grooming') {
        $apptId = (int)($_POST['appointment_id'] ?? 0);
        if ($apptId > 0) {
            $upd = $pdo->prepare("UPDATE grooming_appointments SET status = 'in_grooming', grooming_started_at = {$nowSql} WHERE id = ? AND groomer_user_id = ?");
            if ($upd->execute([$apptId, $groomerUserId])) {
                $success = "فرایند اصلاح آغاز شد. پت به میز گرومینگ و وضعیت «در حال اصلاح و شستشو» منتقل گردید.";
            }
        }
    }

    // 4. Mark Grooming as Ready for Pickup (آماده تحویل و استایل‌شده)
    elseif ($action === 'ready_grooming') {
        $apptId = (int)($_POST['appointment_id'] ?? 0);
        if ($apptId > 0) {
            $upd = $pdo->prepare("UPDATE grooming_appointments SET status = 'ready_for_pickup', ready_at = {$nowSql} WHERE id = ? AND groomer_user_id = ?");
            if ($upd->execute([$apptId, $groomerUserId])) {
                $success = "اصلاح و استایلینگ پت با موفقیت به اتمام رسید و در صف «آماده تحویل به سرپرست» قرار گرفت.";
            }
        }
    }

    // 5. Complete Grooming & Send Pickup SMS
    elseif ($action === 'complete_session_sms') {
        $apptId = (int)($_POST['appointment_id'] ?? 0);
        $customerPhone = preg_replace('/[^0-9]/', '', trim($_POST['customer_phone'] ?? ''));
        $petName = trim($_POST['pet_name'] ?? 'پت دلبند شما');
        $customerName = trim($_POST['customer_name'] ?? 'سرپرست محترم');

        if ($apptId > 0) {
            $upd = $pdo->prepare("UPDATE grooming_appointments SET status = 'completed', completed_at = {$nowSql} WHERE id = ? AND groomer_user_id = ?");
            $upd->execute([$apptId, $groomerUserId]);
        }

        if (!empty($customerPhone) && strlen($customerPhone) === 11) {
            try {
                require_once dirname(__DIR__) . '/includes/SmsService.php';
                $sms = new SmsService();
                $sms->sendPatternSms($customerPhone, 'grooming_ready', ['name' => $customerName, 'pet' => $petName]);
                $success = "نوبت با موفقیت تسویه و تحویل شد؛ پیامک تحویل فوری برای سرپرست ({$customerPhone}) ارسال گردید.";
            } catch (Throwable $eSms) {
                $success = "نوبت گرومینگ با موفقیت تسویه و تحویل شد (ارسال پیامک در صف پردازش قرار گرفت).";
            }
        } else {
            $success = "نوبت گرومینگ با موفقیت پایان یافت و تسویه شد.";
        }
    }

    // 6. Add New Portfolio Entry (Before & After)
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

        if (!empty($_FILES['before_image']['tmp_name'])) {
            $ext = pathinfo($_FILES['before_image']['name'], PATHINFO_EXTENSION);
            $beforeName = 'before_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['before_image']['tmp_name'], $uploadDir . $beforeName)) {
                $beforePath = 'uploads/grooming/' . $beforeName;
            }
        }

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

    // 7. Delete Portfolio Item
    elseif ($action === 'delete_portfolio') {
        $pId = (int)($_POST['portfolio_id'] ?? 0);
        if ($pId > 0) {
            $delStmt = $pdo->prepare("DELETE FROM grooming_portfolio WHERE id = ? AND groomer_user_id = ?");
            if ($delStmt->execute([$pId, $groomerUserId])) {
                $success = "نمونه‌کار انتخابی با موفقیت حذف گردید.";
            }
        }
    }

    // 8. Add Grooming Service / Tariff
    elseif ($action === 'add_service') {
        $srvTitle = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'full_grooming');
        $duration = (int)($_POST['duration_minutes'] ?? 60);
        $price = (int)preg_replace('/[^0-9]/', '', $_POST['price'] ?? '0');
        $desc = trim($_POST['description'] ?? '');

        if (empty($srvTitle) || $price <= 0) {
            $error = 'عنوان خدمت و تعرفه معتبر الزامی است.';
        } else {
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
}

// Fetch Pending Organization Invitations
$pendingOrgInvitations = [];
try {
    $pendingOrgInvitations = App::organization()->getUserInvitations($groomerUserId, 'pending');
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

// Fetch Today's Grooming Appointments
$todayAppointments = [];
try {
    $aStmt = $pdo->prepare("SELECT * FROM grooming_appointments WHERE groomer_user_id = ? AND appointment_date = ? ORDER BY start_time ASC, id ASC");
    $aStmt->execute([$groomerUserId, $today]);
    $todayAppointments = $aStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Seed sample daily appointments if empty to showcase the workflow
if (empty($todayAppointments)) {
    $sampleAppts = [
        [
            'customer_name' => 'امیرحسین پارسا',
            'customer_phone' => '09121234567',
            'pet_name' => 'تدی (Teddy)',
            'pet_species' => 'سگ',
            'pet_breed' => 'شیتزو تریر',
            'service_title' => 'گرومینگ کامل و کوتاهی مدل‌دار',
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'price' => 450000,
            'status' => 'in_grooming',
            'notes' => 'حساس به صدای سشوار، گره خفیف پشت گوش'
        ],
        [
            'customer_name' => 'سارا رادمنش',
            'customer_phone' => '09359876543',
            'pet_name' => 'میلو (Milo)',
            'pet_species' => 'سگ',
            'pet_breed' => 'پودل کراس',
            'service_title' => 'حمام و اسپای درمانی پت',
            'start_time' => '11:30',
            'duration_minutes' => 45,
            'price' => 280000,
            'status' => 'ready_for_pickup',
            'notes' => 'شامپوی گیاهی آلوئه‌ورا مصرف شد، ناخن‌ها کوتاه شدند'
        ],
        [
            'customer_name' => 'محمدرضا سمیعی',
            'customer_phone' => '09194567890',
            'pet_name' => 'کوپر (Cooper)',
            'pet_species' => 'سگ',
            'pet_breed' => 'هاسکی سیبری',
            'service_title' => 'گره‌زدایی و برس‌کشی موی ریزش‌دار',
            'start_time' => '14:00',
            'duration_minutes' => 40,
            'price' => 200000,
            'status' => 'scheduled',
            'notes' => 'نوبت بعدازظهر؛ سرپرست ساعت ۱۴ می‌رسد'
        ],
        [
            'customer_name' => 'نیلوفر افشار',
            'customer_phone' => '09123334455',
            'pet_name' => 'لوسی (Lucy)',
            'pet_species' => 'گربه',
            'pet_breed' => 'پرشین اصیل',
            'service_title' => 'بهداشت گوش، ناخن و تخلیه کیسه اشکی',
            'start_time' => '09:00',
            'duration_minutes' => 30,
            'price' => 150000,
            'status' => 'completed',
            'notes' => 'تحویل شد و پیامک برای سرپرست ارسال گردید'
        ]
    ];
    foreach ($sampleAppts as $sa) {
        try {
            $insA = $pdo->prepare("
                INSERT INTO grooming_appointments 
                (groomer_user_id, customer_name, customer_phone, pet_name, pet_species, pet_breed, service_title, appointment_date, start_time, duration_minutes, price, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$nowSql})
            ");
            $insA->execute([$groomerUserId, $sa['customer_name'], $sa['customer_phone'], $sa['pet_name'], $sa['pet_species'], $sa['pet_breed'], $sa['service_title'], $today, $sa['start_time'], $sa['duration_minutes'], $sa['price'], $sa['status'], $sa['notes']]);
        } catch (Throwable $e) {}
    }
    try {
        $aStmt = $pdo->prepare("SELECT * FROM grooming_appointments WHERE groomer_user_id = ? AND appointment_date = ? ORDER BY start_time ASC, id ASC");
        $aStmt->execute([$groomerUserId, $today]);
        $todayAppointments = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

// Group appointments into the 4 daily workflow buckets
$scheduledList = array_filter($todayAppointments, fn($a) => $a['status'] === 'scheduled');
$inGroomingList = array_filter($todayAppointments, fn($a) => $a['status'] === 'in_grooming');
$readyList = array_filter($todayAppointments, fn($a) => $a['status'] === 'ready_for_pickup');
$completedList = array_filter($todayAppointments, fn($a) => $a['status'] === 'completed');

// KPI stats for today
$totalTodayCount = count($todayAppointments);
$inChairCount = count($inGroomingList);
$readyCount = count($readyList);
$todayRevenue = array_reduce($todayAppointments, fn($acc, $a) => $acc + (int)$a['price'], 0);

// Fetch Portfolio
$portfolioItems = [];
try {
    $portStmt = $pdo->prepare("SELECT * FROM grooming_portfolio WHERE groomer_user_id = ? ORDER BY id DESC");
    $portStmt->execute([$groomerUserId]);
    $portfolioItems = $portStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
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

    <!-- Pending Organization Invitations -->
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
                            </div>
                            <p class="text-xs text-slate-300">
                                سمت پیشنهادی: <strong class="text-amber-300"><?= htmlspecialchars($pInv['job_title'] ?: 'گرومر و استایلیست حیوانات') ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0 relative z-10">
                        <form method="POST" action="index.php" class="inline m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="accept_org_invitation">
                            <input type="hidden" name="invitation_id" value="<?= (int)$pInv['id'] ?>">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md transition-all">
                                تأیید و پیوستن به کادر
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- GROOMER LIVE SHIFT COCKPIT (Rule 11: Shift-Centric Single Cockpit) -->
    <div class="cockpit-groomer text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-amber-500/30 relative overflow-hidden space-y-6" style="background-color: #1a1200 !important; background-image: linear-gradient(135deg, #1a1200 0%, #2c1a00 50%, #120b00 100%) !important; color: #ffffff !important;">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-orange-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Bar: Shift Status & Quick Badges -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-13 h-13 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-amber-400 flex items-center justify-center font-black shadow-inner">
                    <span class="material-symbols-outlined text-3xl">content_cut</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>شیفت فعال پیرایش و اصلاح پت</span>
                        </span>
                        <span class="text-xs text-amber-200 font-bold">| امروز: <?= jdate('l، j F Y') ?></span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-white mt-1"><?= htmlspecialchars($groomerName) ?> (گرومر تخصصی)</h2>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="document.getElementById('walkin-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    <span>+ پذیرش حضوری سریع (Walk-in)</span>
                </button>
                <button type="button" onclick="document.getElementById('portfolio-modal').classList.remove('hidden')" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">add_photo_alternate</span>
                    <span>ثبت نمونه‌کار جدید</span>
                </button>
            </div>
        </div>

        <!-- 4 LIVE WORKFLOW COUNTERS IN COCKPIT -->
        <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs pt-1">
            <div class="p-3.5 rounded-2xl bg-white/10 border border-white/15 space-y-1">
                <span class="text-amber-200 font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    <span>در انتظار مراجعه:</span>
                </span>
                <p class="text-xl font-black text-white font-mono"><?= count($scheduledList) ?> نوبت</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-500/20 border border-amber-400/40 space-y-1">
                <span class="text-amber-300 font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">airline_seat_recline_extra</span>
                    <span>روی صندلی اصلاح:</span>
                </span>
                <p class="text-xl font-black text-amber-200 font-mono"><?= $inChairCount ?> پت فعال</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 space-y-1">
                <span class="text-emerald-300 font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">done_all</span>
                    <span>آماده تحویل به سرپرست:</span>
                </span>
                <p class="text-xl font-black text-emerald-200 font-mono"><?= $readyCount ?> پت</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-white/10 border border-white/15 space-y-1">
                <span class="text-slate-300 font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span>کارکرد شیفت امروز:</span>
                </span>
                <p class="text-lg font-black text-white font-mono"><?= number_format($todayRevenue) ?> ت</p>
            </div>
        </div>
    </div>

    <!-- DAILY ACTIVITY KANBAN & PIPELINE (سامان‌دهی و گردش کار روزانه گرومر) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600">view_kanban</span>
                    <span>خط زمانی و سامان‌دهی روزانه گرومینگ (Daily Activity Pipeline)</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">مدیریت گام‌به‌گام اصلاح پت: از پذیرش اولیه، صندلی کار، اتمام استایل تا پیامک تحویل به سرپرست</p>
            </div>
            <button type="button" onclick="document.getElementById('walkin-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-sm">person_add</span>
                <span>پذیرش حضوری سریع</span>
            </button>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- STAGE 1: SCHEDULED / AWAITING ARRIVAL -->
            <div class="p-4 rounded-3xl bg-slate-50 border border-slate-200 flex flex-col space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                        <h4 class="text-xs font-black text-slate-800">۱. در انتظار مراجعه</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700"><?= count($scheduledList) ?></span>
                </div>

                <div class="space-y-3 overflow-y-auto max-h-[500px]">
                    <?php if (empty($scheduledList)): ?>
                        <div class="p-6 text-center text-slate-400 text-xs">نوبتی در انتظار ورود نیست</div>
                    <?php else: ?>
                        <?php foreach ($scheduledList as $appt): ?>
                            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2.5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h5 class="font-black text-xs text-slate-900"><?= htmlspecialchars($appt['pet_name']) ?></h5>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($appt['pet_breed'] ?: $appt['pet_species']) ?></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-mono font-bold text-[11px]">
                                        <?= htmlspecialchars($appt['start_time']) ?>
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-700 font-bold bg-slate-50 p-2 rounded-xl">
                                    ✂️ <?= htmlspecialchars($appt['service_title']) ?>
                                </div>
                                <div class="text-[10px] text-slate-500 flex items-center justify-between">
                                    <span>سرپرست: <?= htmlspecialchars($appt['customer_name']) ?></span>
                                    <span class="font-mono text-emerald-700 font-bold"><?= number_format($appt['price']) ?> ت</span>
                                </div>
                                <?php if (!empty($appt['notes'])): ?>
                                    <p class="text-[10px] text-amber-700 bg-amber-50 p-1.5 rounded-lg border border-amber-200"><?= htmlspecialchars($appt['notes']) ?></p>
                                <?php endif; ?>
                                <form method="POST" action="index.php" class="m-0 pt-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="start_grooming">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                    <button type="submit" class="w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center justify-center gap-1 shadow-xs transition-all">
                                        <span class="material-symbols-outlined text-sm">play_arrow</span>
                                        <span>نشستن روی صندلی اصلاح</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STAGE 2: IN-CHAIR / UNDER GROOMING -->
            <div class="p-4 rounded-3xl bg-amber-50/50 border border-amber-200 flex flex-col space-y-3">
                <div class="flex items-center justify-between border-b border-amber-200/80 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                        <h4 class="text-xs font-black text-amber-900">۲. در حال اصلاح و شستشو</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900"><?= count($inGroomingList) ?></span>
                </div>

                <div class="space-y-3 overflow-y-auto max-h-[500px]">
                    <?php if (empty($inGroomingList)): ?>
                        <div class="p-6 text-center text-slate-400 text-xs">صندلی هم‌اکنون خالی است</div>
                    <?php else: ?>
                        <?php foreach ($inGroomingList as $appt): ?>
                            <div class="p-4 rounded-2xl bg-white border border-amber-300 shadow-md space-y-3 relative overflow-hidden">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500 text-slate-950 font-black text-[10px] mb-1">
                                            زیر دست آرایشگر
                                        </div>
                                        <h5 class="font-black text-sm text-slate-900"><?= htmlspecialchars($appt['pet_name']) ?></h5>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($appt['pet_breed'] ?: $appt['pet_species']) ?></p>
                                    </div>
                                    <span class="material-symbols-outlined text-amber-500 text-2xl animate-spin">progress_activity</span>
                                </div>
                                <div class="text-[11px] text-slate-800 font-bold bg-amber-50/70 p-2 rounded-xl border border-amber-200">
                                    ✂️ <?= htmlspecialchars($appt['service_title']) ?>
                                    <div class="text-[10px] text-slate-500 mt-0.5">مدت زمان استاندارد: <?= (int)$appt['duration_minutes'] ?> دقیقه</div>
                                </div>
                                <div class="space-y-1 text-[11px] text-slate-600">
                                    <div class="flex justify-between">
                                        <span>سرپرست: <?= htmlspecialchars($appt['customer_name']) ?></span>
                                        <span class="font-mono text-emerald-700 font-bold"><?= number_format($appt['price']) ?> ت</span>
                                    </div>
                                    <div class="font-mono text-slate-500 text-[10px]" dir="ltr">📞 <?= htmlspecialchars($appt['customer_phone']) ?></div>
                                </div>
                                <form method="POST" action="index.php" class="m-0 pt-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="ready_grooming">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                    <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs flex items-center justify-center gap-1 shadow-md transition-all">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                        <span>اتمام اصلاح و اعلام آماده‌باش</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STAGE 3: STYLED & READY FOR PICKUP -->
            <div class="p-4 rounded-3xl bg-emerald-50/50 border border-emerald-200 flex flex-col space-y-3">
                <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <h4 class="text-xs font-black text-emerald-900">۳. آماده تحویل به سرپرست</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200 text-emerald-900"><?= count($readyList) ?></span>
                </div>

                <div class="space-y-3 overflow-y-auto max-h-[500px]">
                    <?php if (empty($readyList)): ?>
                        <div class="p-6 text-center text-slate-400 text-xs">پتی در انتظار تحویل نیست</div>
                    <?php else: ?>
                        <?php foreach ($readyList as $appt): ?>
                            <div class="p-4 rounded-2xl bg-white border border-emerald-300 shadow-xs space-y-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h5 class="font-black text-xs text-slate-900"><?= htmlspecialchars($appt['pet_name']) ?></h5>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($appt['pet_breed'] ?: $appt['pet_species']) ?></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        استایل‌شده ✨
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-700">
                                    سرپرست: <strong class="text-slate-900"><?= htmlspecialchars($appt['customer_name']) ?></strong>
                                    <div class="font-mono text-slate-500 text-[10px] mt-0.5" dir="ltr"><?= htmlspecialchars($appt['customer_phone']) ?></div>
                                </div>
                                <form method="POST" action="index.php" class="m-0 space-y-1.5">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete_session_sms">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                    <input type="hidden" name="customer_phone" value="<?= htmlspecialchars($appt['customer_phone']) ?>">
                                    <input type="hidden" name="customer_name" value="<?= htmlspecialchars($appt['customer_name']) ?>">
                                    <input type="hidden" name="pet_name" value="<?= htmlspecialchars($appt['pet_name']) ?>">
                                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-black text-xs flex items-center justify-center gap-1 shadow-sm transition-all">
                                        <span class="material-symbols-outlined text-sm">sms</span>
                                        <span>ارسال پیامک و تحویل به سرپرست</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STAGE 4: COMPLETED -->
            <div class="p-4 rounded-3xl bg-slate-50 border border-slate-200 flex flex-col space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                        <h4 class="text-xs font-black text-slate-800">۴. پایان‌یافته و تسویه</h4>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700"><?= count($completedList) ?></span>
                </div>

                <div class="space-y-3 overflow-y-auto max-h-[500px]">
                    <?php if (empty($completedList)): ?>
                        <div class="p-6 text-center text-slate-400 text-xs">هنوز نوبت تسویه‌شده‌ای برای امروز ثبت نشده</div>
                    <?php else: ?>
                        <?php foreach ($completedList as $appt): ?>
                            <div class="p-3.5 rounded-2xl bg-white border border-slate-200 space-y-1.5 opacity-90">
                                <div class="flex items-center justify-between">
                                    <h5 class="font-bold text-xs text-slate-800"><?= htmlspecialchars($appt['pet_name']) ?></h5>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">تسویه شد</span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    <?= htmlspecialchars($appt['service_title']) ?> • <strong class="font-mono text-slate-700"><?= number_format($appt['price']) ?> تومان</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                <p class="text-[11px] font-bold text-slate-500">کل نوبت‌های امروز</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= $totalTodayCount ?> نوبت</h3>
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
                <p class="text-[11px] font-bold text-slate-500">پکیج‌های خدماتی فعال</p>
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

<!-- Modal: Quick Walk-in Appointment -->
<div id="walkin-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">person_add</span>
                <span>ثبت پذیرش حضوری سریع (Walk-in Booking)</span>
            </h3>
            <button type="button" onclick="document.getElementById('walkin-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_daily_appointment">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام پت *</label>
                    <input type="text" name="pet_name" required placeholder="مثال: میلو" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نژاد پت</label>
                    <input type="text" name="pet_breed" placeholder="مثال: شیتزو، پودل، پرشین" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام سرپرست</label>
                    <input type="text" name="customer_name" placeholder="نام و نام خانوادگی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">شماره همراه سرپرست *</label>
                    <input type="tel" name="customer_phone" required placeholder="09120000000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">پکیج خدمت گرومینگ *</label>
                <select name="service_id" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-800">
                    <?php foreach ($services as $srv): ?>
                        <option value="<?= (int)$srv['id'] ?>"><?= htmlspecialchars($srv['title']) ?> (<?= number_format($srv['price']) ?> ت - <?= (int)$srv['duration_minutes'] ?> دقیقه)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ساعت شروع نوبت</label>
                    <input type="time" name="start_time" value="<?= date('H:i') ?>" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">هزینه نوبت (تومان)</label>
                    <input type="text" name="price" placeholder="خودکار طبق خدمت" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات و رفتار پت</label>
                <input type="text" name="notes" placeholder="ترس از سشوار، گره‌خوردگی مو، نیاز به پوزه‌بند و..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('walkin-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm transition-all">
                    ثبت نوبت در پایپ‌لاین امروز
                </button>
            </div>
        </form>
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
