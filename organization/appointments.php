<?php
require_once 'includes/organization_header.php';

$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Appointment Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'update_status') {
        $aptId = (int)($_POST['appointment_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['pending', 'approved', 'completed', 'cancelled'];

        if ($aptId > 0 && in_array($newStatus, $allowedStatuses)) {
            // Verify appointment belongs to this organization (direct org_id or via org's doctors)
            $stmt = $pdo->prepare("
                UPDATE appointments a
                LEFT JOIN organization_doctors od ON a.doctor_id = od.doctor_id
                SET a.status = ?,
                    a.settlement_status = CASE 
                        WHEN ? = 'completed' THEN 'available_for_payout'
                        WHEN ? = 'cancelled' THEN 'cancelled'
                        ELSE a.settlement_status 
                    END
                WHERE a.id = ? AND (a.organization_id = ? OR od.organization_id = ?)
            ");
            if ($stmt->execute([$newStatus, $newStatus, $newStatus, $aptId, $orgId, $orgId])) {
                $statusLabels = [
                    'approved' => 'نوبت تأیید شد و به وضعیت آماده ویزیت تغییر یافت.',
                    'completed' => 'ویزیت پایان یافت و سهم خالص مرکز به موجودی آماده تسویه (پایا) منتقل شد.',
                    'cancelled' => 'نوبت لغو شد.'
                ];
                $message = $statusLabels[$newStatus] ?? 'وضعیت نوبت به‌روزرسانی شد.';
                $messageType = 'success';
            } else {
                $message = 'خطا در تغییر وضعیت نوبت.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'add_walkin') {
        $patientName = trim($_POST['patient_name'] ?? '');
        $patientPhone = trim($_POST['patient_phone'] ?? '');
        $petName = trim($_POST['pet_name'] ?? '');
        $petType = trim($_POST['pet_type'] ?? 'سگ');
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $aptDate = trim($_POST['appointment_date'] ?? date('Y-m-d'));
        $aptTime = trim($_POST['appointment_time'] ?? date('H:i'));
        $fee = (int)($_POST['fee'] ?? 350000);
        if ($fee <= 0) $fee = 350000;
        $comm = round($fee * 0.05); // 5% platform interest
        $net = $fee - $comm; // 95% clinic net
        $visitPurpose = trim($_POST['visit_purpose'] ?? 'ویزیت حضوری');
        $serviceType = trim($_POST['service_type'] ?? 'general_checkup');

        if (!empty($patientName) && $doctorId > 0) {
            // Check if patient user exists, or create placeholder user
            $usrStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $usrStmt->execute([$patientPhone]);
            $existingUserId = $usrStmt->fetchColumn();

            if (!$existingUserId) {
                $insUsr = $pdo->prepare("
                    INSERT INTO users (phone, name, password, role, loyalty_points, created_at)
                    VALUES (?, ?, 'NOPASS', 'user', 10, NOW())
                ");
                $insUsr->execute([$patientPhone ?: ('walkin_' . time()), $patientName]);
                $existingUserId = (int)$pdo->lastInsertId();
            }

            $insApt = $pdo->prepare("
                INSERT INTO appointments (user_id, doctor_id, organization_id, appointment_date, appointment_time, status, pet_name, pet_type, fee, commission_amount, net_amount, visit_purpose, service_type, settlement_status, created_at)
                VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?, ?, 'held_in_escrow', NOW())
            ");
            if ($insApt->execute([$existingUserId, $doctorId, $orgId, $aptDate, $aptTime, $petName, $petType, $fee, $comm, $net, $visitPurpose, $serviceType])) {
                $message = 'نوبت پذیرش حضوری با موفقیت ثبت شد (سهم مرکز: ' . number_format($net) . ' تومان، کارمزد ۵٪ پلتفرم: ' . number_format($comm) . ' تومان).';
                $messageType = 'success';
            } else {
                $message = 'خطا در ثبت نوبت حضوری.';
                $messageType = 'error';
            }
        } else {
            $message = 'لطفاً نام بیمار و متخصص معالج را مشخص نمایید.';
            $messageType = 'error';
        }
    }
}

// Fetch doctors and groomers linked to this organization
$docStmt = $pdo->prepare("
    SELECT d.id, d.name, d.specialty, d.image_url, d.provider_type, d.price as consultation_fee
    FROM doctors d
    JOIN organization_doctors od ON d.id = od.doctor_id
    WHERE od.organization_id = ?
");
$docStmt->execute([$orgId]);
$orgDoctors = $docStmt->fetchAll(PDO::FETCH_ASSOC);

$orgDoctorIds = array_column($orgDoctors, 'id');

// Current filters
$filter = $_GET['filter'] ?? 'all';
$roleFilter = $_GET['role'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$dateToday = date('Y-m-d');

// Build strict multi-tenant query: only appointments booked directly with this organization OR its affiliated practitioners
$orgScopeClauses = ["a.organization_id = ?"];
$orgScopeParams = [$orgId];
if (!empty($orgDoctorIds)) {
    $docPlaceholders = implode(',', array_fill(0, count($orgDoctorIds), '?'));
    $orgScopeClauses[] = "a.doctor_id IN ($docPlaceholders)";
    $orgScopeParams = array_merge($orgScopeParams, $orgDoctorIds);
}

$whereClauses = ["(" . implode(' OR ', $orgScopeClauses) . ")"];
$params = $orgScopeParams;

if ($filter === 'today') {
    $whereClauses[] = "a.appointment_date = ?";
    $params[] = $dateToday;
} elseif ($filter === 'pending') {
    $whereClauses[] = "a.status = 'pending'";
} elseif ($filter === 'approved') {
    $whereClauses[] = "a.status = 'approved'";
} elseif ($filter === 'completed') {
    $whereClauses[] = "a.status = 'completed'";
}

if ($roleFilter === 'doctor') {
    $whereClauses[] = "d.provider_type = 'doctor'";
} elseif ($roleFilter === 'groomer') {
    $whereClauses[] = "d.provider_type = 'groomer'";
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(u.name LIKE ? OR u.phone LIKE ? OR a.pet_name LIKE ? OR d.name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $whereClauses);
$aptQuery = "
    SELECT a.*, d.name as doctor_name, d.specialty as doctor_specialty, d.image_url as doctor_image, d.provider_type,
           u.name as user_full_name, u.phone as user_phone
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE {$whereSql}
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 60
";
$stmt = $pdo->prepare($aptQuery);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Quick Stats (Strictly scoped to this organization)
$statScopeSql = "a.organization_id = " . (int)$orgId;
if (!empty($orgDoctorIds)) {
    $statScopeSql .= " OR a.doctor_id IN (" . implode(',', array_map('intval', $orgDoctorIds)) . ")";
}
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN a.appointment_date = '{$dateToday}' THEN 1 ELSE 0 END) as today_count,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN d.provider_type = 'groomer' THEN 1 ELSE 0 END) as groomer_count,
        SUM(CASE WHEN d.provider_type = 'doctor' THEN 1 ELSE 0 END) as doctor_count
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    WHERE ({$statScopeSql})
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_count' => 0, 'today_count' => 0, 'pending_count' => 0,
    'completed_count' => 0, 'groomer_count' => 0, 'doctor_count' => 0
];

$fmtDate = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-sky-600 text-3xl">calendar_month</span>
                <span>مدیریت نوبت‌دهی و پذیرش بیماران</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مشاهده تقویم ویزیت پزشکان، خدمات گرومرها، محاسبه کارمزد ۵٪ و تسویه پایا</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="shifts.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-slate-500">schedule</span>
                <span>شیفت‌ها و مرخصی</span>
            </a>
            <button onclick="document.getElementById('walkinModal').classList.remove('hidden')" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-sky-600/20 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                <span>ثبت نوبت حضوری جدید</span>
            </button>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">today</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">نوبت‌های امروز</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['today_count'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">در انتظار تأیید</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['pending_count'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">content_cut</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">نوبت‌های گرومینگ</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['groomer_count'] ?></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">check_circle</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-bold block">ویزیت‌های موفق</span>
                <span class="text-2xl font-black text-slate-900"><?= (int)$stats['completed_count'] ?></span>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status & Role Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
            <a href="appointments.php?filter=all&role=<?= urlencode($roleFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="px-3.5 py-2 rounded-xl transition-all <?= $filter === 'all' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                همه
            </a>
            <a href="appointments.php?filter=today&role=<?= urlencode($roleFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="px-3.5 py-2 rounded-xl transition-all <?= $filter === 'today' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                امروز (<?= (int)$stats['today_count'] ?>)
            </a>
            <a href="appointments.php?filter=pending&role=<?= urlencode($roleFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="px-3.5 py-2 rounded-xl transition-all <?= $filter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                در انتظار تأیید (<?= (int)$stats['pending_count'] ?>)
            </a>
            <a href="appointments.php?filter=approved&role=<?= urlencode($roleFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="px-3.5 py-2 rounded-xl transition-all <?= $filter === 'approved' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                آماده ویزیت
            </a>
            <a href="appointments.php?filter=completed&role=<?= urlencode($roleFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="px-3.5 py-2 rounded-xl transition-all <?= $filter === 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                تکمیل شده
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <!-- Role Selector -->
            <a href="appointments.php?filter=<?= urlencode($filter) ?>&role=all&q=<?= urlencode($searchQuery) ?>" class="px-3 py-2 rounded-xl text-xs <?= $roleFilter === 'all' ? 'bg-slate-800 text-white font-black' : 'text-slate-500 hover:text-slate-800' ?>">
                تمام کادر
            </a>
            <a href="appointments.php?filter=<?= urlencode($filter) ?>&role=doctor&q=<?= urlencode($searchQuery) ?>" class="px-3 py-2 rounded-xl text-xs flex items-center gap-1 <?= $roleFilter === 'doctor' ? 'bg-indigo-600 text-white font-black' : 'text-indigo-600 hover:bg-indigo-50' ?>">
                <span>🩺 فقط پزشکان</span>
            </a>
            <a href="appointments.php?filter=<?= urlencode($filter) ?>&role=groomer&q=<?= urlencode($searchQuery) ?>" class="px-3 py-2 rounded-xl text-xs flex items-center gap-1 <?= $roleFilter === 'groomer' ? 'bg-pink-600 text-white font-black' : 'text-pink-600 hover:bg-pink-50' ?>">
                <span>✂️ فقط گرومرها</span>
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" class="relative min-w-[220px]">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
            <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-lg">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="جستجوی بیمار، پت، پزشک..." class="w-full pr-9 pl-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
        </form>
    </div>

    <!-- Appointments Table / Cards -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-16 text-slate-400 space-y-3">
                <span class="material-symbols-outlined text-5xl text-slate-300">event_busy</span>
                <p class="text-sm font-bold text-slate-600">هیچ نوبتی مطابق با فیلترهای انتخابی یافت نشد.</p>
                <p class="text-xs text-slate-400">نوبت‌های رزرو شده توسط کاربران آنلاین یا پذیرش حضوری در اینجا لیست می‌شوند.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-4">شناسه / زمان</th>
                            <th class="p-4">سرپرست و تماس</th>
                            <th class="p-4">مشخصات پت</th>
                            <th class="p-4">متخصص معالج</th>
                            <th class="p-4">خدمت / تعرفه</th>
                            <th class="p-4">سهم مرکز / کارمزد ۵٪</th>
                            <th class="p-4">وضعیت نوبت</th>
                            <th class="p-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($appointments as $apt): ?>
                            <?php
                            $isGroomer = ($apt['provider_type'] ?? '') === 'groomer';
                            $fee = (int)($apt['fee'] ?: 0);
                            $comm = (int)($apt['commission_amount'] ?: round($fee * 0.05));
                            $net = (int)($apt['net_amount'] ?: ($fee - $comm));

                            $statusBadge = match($apt['status']) {
                                'pending'   => '<span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-bold border border-amber-200">در انتظار تأیید</span>',
                                'approved'  => '<span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 font-bold border border-indigo-200">تأیید شده / در کلینیک</span>',
                                'completed' => '<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">انجام شده (آماده پایا)</span>',
                                'cancelled' => '<span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 font-bold border border-rose-200">لغو شده</span>',
                                default     => '<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold">' . htmlspecialchars($apt['status']) . '</span>',
                            };
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-4">
                                    <span class="font-bold text-slate-800 font-mono">#APT-<?= $apt['id'] ?></span>
                                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                        <?= $fmtDate->format(new DateTime($apt['appointment_date'])) ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">schedule</span>
                                        ساعت <?= substr($apt['appointment_time'], 0, 5) ?>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($apt['user_full_name'] ?? 'مراجعه‌کننده') ?></div>
                                    <?php if (!empty($apt['user_phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($apt['user_phone']) ?>" class="text-[11px] text-sky-600 hover:underline dir-ltr inline-block font-mono mt-0.5">
                                            <?= htmlspecialchars($apt['user_phone']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg <?= ($apt['pet_type'] === 'گربه') ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' ?> flex items-center justify-center font-bold text-xs">
                                            <?= ($apt['pet_type'] === 'گربه') ? '🐱' : '🐶' ?>
                                        </span>
                                        <div>
                                            <div class="font-bold text-slate-800"><?= htmlspecialchars($apt['pet_name'] ?: ($apt['pet_type'] ?: 'حیوان خانگی')) ?></div>
                                            <span class="text-[11px] text-slate-400"><?= htmlspecialchars($apt['pet_type'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($apt['doctor_image'])): ?>
                                            <img src="../<?= htmlspecialchars($apt['doctor_image']) ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200" onerror="this.src='https://via.placeholder.com/32?text=Doc'">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full <?= $isGroomer ? 'bg-pink-100 text-pink-700' : 'bg-indigo-100 text-indigo-700' ?> flex items-center justify-center font-bold text-xs">
                                                <?= $isGroomer ? '✂️' : '🩺' ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-bold text-slate-800"><?= htmlspecialchars($apt['doctor_name'] ?? 'متخصص مرکز') ?></div>
                                            <span class="text-[10px] font-bold <?= $isGroomer ? 'text-pink-600' : 'text-indigo-600' ?>">
                                                <?= $isGroomer ? '✂️ گرومر و آرایشگر' : '🩺 ' . htmlspecialchars($apt['doctor_specialty'] ?? 'دامپزشک') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($apt['visit_purpose'] ?? 'معاینه عمومی') ?></div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5"><?= number_format($fee) ?> تومان</div>
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-emerald-600 font-mono"><?= number_format($net) ?> تومان</div>
                                    <div class="text-[10px] text-rose-500 font-mono">کارمزد ۵٪: <?= number_format($comm) ?> تومان</div>
                                </td>

                                <td class="p-4">
                                    <?= $statusBadge ?>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <?php if ($apt['status'] === 'pending'): ?>
                                            <form method="POST" class="inline m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold shadow-sm transition-all">
                                                    تأیید نوبت
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($apt['status'] === 'approved'): ?>
                                            <form method="POST" class="inline m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold shadow-sm transition-all">
                                                    پایان و تسویه
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($apt['status'], ['pending', 'approved'])): ?>
                                            <form method="POST" class="inline m-0" onsubmit="return confirm('آیا از لغو این نوبت اطمینان دارید؟');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold transition-all">
                                                    لغو
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Add Walk-in Modal -->
<div id="walkinModal" class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-sky-600">person_add</span>
                ثبت نوبت مراجعه‌کننده حضوری (Walk-in)
            </h3>
            <button onclick="document.getElementById('walkinModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_walkin">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام سرپرست / بیمار</label>
                    <input type="text" name="patient_name" required placeholder="مثال: آقای علوی" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل</label>
                    <input type="text" name="patient_phone" placeholder="0912..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none dir-ltr">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام پت</label>
                    <input type="text" name="pet_name" placeholder="مثال: تدی" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع حیوان</label>
                    <select name="pet_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="سگ">سگ</option>
                        <option value="گربه">گربه</option>
                        <option value="پرنده">پرنده</option>
                        <option value="جونده">خرگوش / همستر</option>
                        <option value="اگزوتیک">سایر / اگزوتیک</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">متخصص معالج (پزشک یا گرومر)</label>
                <select name="doctor_id" id="walkin_doctor_select" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none" onchange="updateWalkinFee()">
                    <?php foreach ($orgDoctors as $d): ?>
                        <option value="<?= $d['id'] ?>" data-type="<?= $d['provider_type'] ?>" data-fee="<?= (int)$d['consultation_fee'] ?>">
                            <?= ($d['provider_type'] === 'groomer' ? '✂️ [گرومر] ' : '🩺 [پزشک] ') . htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['specialty']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع خدمت و دلیل مراجعه</label>
                    <select name="visit_purpose" id="walkin_purpose_select" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="معاینه عمومی و چکاپ">معاینه عمومی و چکاپ</option>
                        <option value="واکسیناسیون و انگل‌زدایی">واکسیناسیون و انگل‌زدایی</option>
                        <option value="اصلاح مو و گرومینگ بهداشتی">اصلاح مو و گرومینگ بهداشتی</option>
                        <option value="شستشو و اسپای تخصصی">شستشو و اسپای تخصصی</option>
                        <option value="سرم‌تراپی و تزریقات">سرم‌تراپی و تزریقات</option>
                        <option value="جراحی و عقیم‌سازی">جراحی و عقیم‌سازی</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تعرفه ویزیت / خدمت (تومان)</label>
                    <input type="number" name="fee" id="walkin_fee_input" value="350000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none font-mono">
                    <span class="text-[10px] text-slate-400 mt-1 block">۵٪ کارمزد پلتفرم، ۹۵٪ سهم مستقیم مرکز</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاریخ نوبت</label>
                    <input type="date" name="appointment_date" value="<?= date('Y-m-d') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none dir-ltr">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">ساعت ویزیت</label>
                    <input type="time" name="appointment_time" value="<?= date('H:i') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-sky-500 outline-none dir-ltr">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('walkinModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition-all shadow-md shadow-sky-600/20">
                    ثبت و پذیرش نوبت
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateWalkinFee() {
    const sel = document.getElementById('walkin_doctor_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt) {
        const fee = opt.getAttribute('data-fee');
        if (fee && parseInt(fee) > 0) {
            document.getElementById('walkin_fee_input').value = fee;
        }
    }
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
