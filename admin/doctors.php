<?php
require_once __DIR__ . '/../includes/App.php';
App::boot();
AuthGuard::requireRole('admin');

$pdo = App::db();
$currentPage = 'doctors';

// ── Search & Filter ───────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$specialtyFilter = trim($_GET['specialty'] ?? '');

$query = "
    SELECT 
        d.*,
        u.phone as user_phone,
        u.email as user_email,
        u.vet_council_number,
        u.is_verified_vet,
        o.name as organization_name,
        o.city as organization_city,
        (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) as total_appointments,
        (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id AND a.status = 'completed') as completed_appointments,
        COALESCE((SELECT COUNT(*) FROM prescriptions p WHERE (p.doctor_id IS NOT NULL AND p.doctor_id = d.id) OR (p.vet_phone IS NOT NULL AND p.vet_phone != '' AND p.vet_phone = d.phone)), 0) as total_prescriptions,
        (SELECT COALESCE(SUM(a.fee), 0) FROM appointments a WHERE a.doctor_id = d.id) as gross_consultation_value
    FROM doctors d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN organization_doctors od ON d.id = od.doctor_id
    LEFT JOIN organizations o ON od.organization_id = o.id
    WHERE 1=1
";

$params = [];
if (!empty($search)) {
    $query .= " AND (d.name LIKE ? OR d.specialty LIKE ? OR o.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($specialtyFilter)) {
    $query .= " AND d.specialty = ?";
    $params[] = $specialtyFilter;
}

$query .= " ORDER BY completed_appointments DESC, d.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Macro Stats
$totalDoctorsCount = count($doctors);
$totalAppointmentsCount = array_sum(array_column($doctors, 'total_appointments'));
$totalCompletedCount = array_sum(array_column($doctors, 'completed_appointments'));
$totalPrescriptionsCount = array_sum(array_column($doctors, 'total_prescriptions'));

// Fetch distinct specialties for filter
$specialties = $pdo->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != ''")->fetchAll(PDO::FETCH_COLUMN);

// Pre-fetch recent 10 appointments per doctor for the modal
$docAppointments = [];
foreach ($doctors as $doc) {
    $dId = (int)$doc['id'];
    $aptStmt = $pdo->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.pet_name, a.pet_notes as notes, a.fee,
               u.name as patient_name, u.phone as patient_phone
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10
    ");
    $aptStmt->execute([$dId]);
    $docAppointments[$dId] = $aptStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="p-4 lg:p-8 space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-on-surface flex items-center gap-2.5">
                <span class="material-symbols-outlined text-secondary-container text-2xl">stethoscope</span>
                <span>فهرست جامع پزشکان و تعاملات درمانی</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت کلان متخصصین دامپزشکی سراسر کشور، پایش ویزیت‌ها، ثبت نسخه‌ها و بررسی تعاملات با مراجعین</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>تسویه ویزیت‌ها: متمرکز در کلینیک‌ها</span>
            </span>
        </div>
    </div>

    <!-- 4 Top KPI Cards Matching Reference Screenshot -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">کل پزشکان فعال</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalDoctorsCount) ?></span>
                    <span class="text-xs text-secondary-container font-bold">پزشک</span>
                </div>
                <p class="text-[11px] text-slate-400">دارای پروانه و تایید صلاحیت</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">groups</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">ویزیت‌ها و نوبت‌های انجام شده</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-on-surface"><?= number_format($totalCompletedCount) ?></span>
                    <span class="text-xs text-blue-600 font-bold">مراجعه</span>
                </div>
                <p class="text-[11px] text-slate-400">از مجموع <?= number_format($totalAppointmentsCount) ?> رزرو</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">نسخه‌های الکترونیک صادر شده</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-600"><?= number_format($totalPrescriptionsCount) ?></span>
                    <span class="text-xs text-emerald-600 font-bold">نسخه Rx</span>
                </div>
                <p class="text-[11px] text-slate-400">تحویل به شبکه داروسازان</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">prescriptions</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl stat-card-shadow border border-outline-variant/10 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-on-surface-variant">ارزش خدمات درمانی ارائه شده</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-slate-800"><?= number_format(array_sum(array_column($doctors, 'gross_consultation_value'))) ?></span>
                    <span class="text-xs text-on-surface-variant font-bold">تومان</span>
                </div>
                <p class="text-[11px] text-slate-400">تضمین شده توسط مراکز درمانی</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">savings</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl stat-card-shadow border border-outline-variant/10 flex flex-col md:flex-row gap-3 items-center justify-between">
        <form method="GET" class="flex flex-1 flex-wrap gap-2 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 text-lg">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="جستجوی نام پزشک، تخصص یا کلینیک..." class="w-full pr-10 pl-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-secondary-container outline-none">
            </div>

            <select name="specialty" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-secondary-container outline-none">
                <option value="">همه تخصص‌ها</option>
                <?php foreach ($specialties as $sp): ?>
                <option value="<?= htmlspecialchars($sp) ?>" <?= $specialtyFilter === $sp ? 'selected' : '' ?>><?= htmlspecialchars($sp) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-slate-800 transition-all flex items-center gap-1">
                <span>اعمال فیلتر</span>
            </button>

            <?php if (!empty($search) || !empty($specialtyFilter)): ?>
            <a href="doctors.php" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-all">
                حذف فیلترها
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Doctors Table -->
    <div class="bg-surface-container-lowest rounded-2xl stat-card-shadow border border-outline-variant/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                    <tr>
                        <th class="p-3.5">پزشک / متخصص</th>
                        <th class="p-3.5">تخصص درمانی</th>
                        <th class="p-3.5">مرکز یا کلینیک وابسته</th>
                        <th class="p-3.5">کد نظام / تعرفه</th>
                        <th class="p-3.5 text-center">کل نوبت‌ها</th>
                        <th class="p-3.5 text-center">ویزیت موفق</th>
                        <th class="p-3.5 text-center">امتیاز بیماران</th>
                        <th class="p-3.5 text-center">تعاملات و سوابق</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($doctors as $doc): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center overflow-hidden">
                                    <?php if (!empty($doc['image_url'])): ?>
                                        <img src="<?= htmlspecialchars(str_starts_with($doc['image_url'], 'http') ? $doc['image_url'] : '../' . ltrim($doc['image_url'], '/')) ?>" class="w-full h-full object-cover" alt="Doctor">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-lg">person</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-sm">دکتر <?= htmlspecialchars($doc['name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($doc['phone'] ?: ($doc['user_phone'] ?: '-')) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5">
                            <span class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                <?= htmlspecialchars($doc['specialty'] ?: 'پزشک عمومی') ?>
                            </span>
                        </td>
                        <td class="p-3.5">
                            <div class="font-bold text-slate-800"><?= htmlspecialchars($doc['organization_name'] ?: 'کلینیک مستقل / مطب شخصی') ?></div>
                            <div class="text-[11px] text-slate-400"><?= htmlspecialchars($doc['organization_city'] ?: 'سراسری') ?></div>
                        </td>
                        <td class="p-3.5">
                            <div class="font-mono text-xs font-bold text-slate-700"><?= htmlspecialchars(!empty($doc['vet_council_number']) ? 'نظام: ' . $doc['vet_council_number'] : (!empty($doc['medical_council_code']) ? $doc['medical_council_code'] : 'نظام معتبر')) ?></div>
                            <div class="text-[11px] text-emerald-600 font-bold"><?= number_format((int)($doc['price'] ?? 0)) ?> تومان</div>
                        </td>
                        <td class="p-3.5 text-center font-bold text-slate-600">
                            <?= (int)$doc['total_appointments'] ?>
                        </td>
                        <td class="p-3.5 text-center font-black text-emerald-600">
                            <?= (int)$doc['completed_appointments'] ?>
                        </td>
                        <td class="p-3.5 text-center font-bold text-amber-500">
                            ★ <?= number_format((float)($doc['rating_cache'] ?? $doc['rating'] ?? 5.0), 1) ?>
                        </td>
                        <td class="p-3.5 text-center">
                            <button onclick="viewDoctorInteractions(<?= $doc['id'] ?>)" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-secondary-container hover:text-white text-slate-700 font-bold text-xs transition-all flex items-center gap-1 mx-auto">
                                <span class="material-symbols-outlined text-sm">history_edu</span>
                                <span>تعاملات بیماران</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── DETAIL MODAL: RECENT INTERACTIONS ────────────────────────────────────── -->
<div id="docInteractionsModal" class="fixed inset-0 bg-black/50 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-4 text-right max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 id="modalDocName" class="font-black text-slate-900 text-base">سوابق تعاملات و ویزیت‌های پزشک</h3>
                <p class="text-xs text-slate-400">آخرین نوبت‌های ثبت شده، اطلاعات بیمار و وضعیت معاینات</p>
            </div>
            <button onclick="closeDocModal()" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div id="modalAptList" class="space-y-2 text-xs">
            <!-- Injected via JS -->
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button type="button" onclick="closeDocModal()" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all">بستن</button>
        </div>
    </div>
</div>

<script>
const docData = <?= json_encode($doctors) ?>;
const docApts = <?= json_encode($docAppointments) ?>;

function viewDoctorInteractions(docId) {
    const doc = docData.find(d => parseInt(d.id) === parseInt(docId));
    if (!doc) return;

    const apts = docApts[docId] || [];

    document.getElementById('modalDocName').innerText = 'تعاملات و پرونده‌های دکتر ' + doc.name;
    const container = document.getElementById('modalAptList');

    if (apts.length === 0) {
        container.innerHTML = '<p class="text-slate-400 italic p-6 text-center">هنوز تعاملی برای این پزشک ثبت نشده است.</p>';
    } else {
        container.innerHTML = apts.map(a => `
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-black text-slate-900">${a.patient_name || 'بیمار بدون نام'}</span>
                        <span class="text-slate-400 font-mono text-[11px]">(${a.patient_phone || '-'})</span>
                        <span class="text-xs font-bold text-secondary-container bg-amber-50 px-2 py-0.5 rounded-md">حیوان خانگی: ${a.pet_name || 'ثبت نشده'}</span>
                    </div>
                    <div class="text-[11px] text-slate-500">${a.notes ? 'شرح ویزیت: ' + a.notes : 'ویزیت حضوری / معاینه عمومی'}</div>
                </div>
                <div class="text-left sm:text-left flex sm:flex-col items-center sm:items-end justify-between gap-1">
                    <div class="text-[11px] font-bold text-slate-500">${a.appointment_date} ساعت ${a.appointment_time}</div>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black ${a.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'}">${a.status === 'completed' ? 'انجام شده' : 'رزرو شده'}</span>
                </div>
            </div>
        `).join('');
    }

    document.getElementById('docInteractionsModal').classList.remove('hidden');
}

function closeDocModal() {
    document.getElementById('docInteractionsModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
