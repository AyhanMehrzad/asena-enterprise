<?php
require_once 'includes/organization_header.php';

$orgService = App::organization();
$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'block_slot') {
        $doctorId  = (int)($_POST['doctor_id'] ?? 0);
        $blockDate = trim($_POST['block_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '00:00');
        $endTime   = trim($_POST['end_time'] ?? '23:59');
        $reason    = trim($_POST['reason'] ?? 'مرخصی یا مسدودی توسط مرکز');

        if ($doctorId > 0 && !empty($blockDate)) {
            $ins = $pdo->prepare("
                INSERT INTO doctor_blocked_slots (doctor_id, organization_id, block_date, start_time, end_time, reason, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($ins->execute([$doctorId, $orgId, $blockDate, $startTime, $endTime, $reason])) {
                $message = 'بازه زمانی یا روز انتخابی با موفقیت مسدود شد و در سامانه نوبت‌دهی بسته گردید.';
                $messageType = 'success';
            } else {
                $message = 'خطا در ثبت مسدودی نوبت.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'unblock_slot') {
        $slotId = (int)($_POST['slot_id'] ?? 0);
        if ($slotId > 0) {
            $del = $pdo->prepare("DELETE FROM doctor_blocked_slots WHERE id = ? AND (organization_id = ? OR organization_id IS NULL)");
            if ($del->execute([$slotId, $orgId])) {
                $message = 'مسدودی با موفقیت لغو شد و نوبت‌ها مجدداً باز شدند.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'update_weekly_shifts') {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        if ($doctorId > 0) {
            $days = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
            $newSchedule = [];
            foreach ($days as $d) {
                $mActive = isset($_POST[$d . '_m_active']);
                $aActive = isset($_POST[$d . '_a_active']);
                $mStart = trim($_POST[$d . '_m_start'] ?? '09:00');
                $mEnd   = trim($_POST[$d . '_m_end'] ?? '13:00');
                $aStart = trim($_POST[$d . '_a_start'] ?? '16:00');
                $aEnd   = trim($_POST[$d . '_a_end'] ?? '21:00');

                if ($mActive || $aActive) {
                    $newSchedule[$d] = [
                        'm_active' => $mActive,
                        'm_start'  => $mStart,
                        'm_end'    => $mEnd,
                        'a_active' => $aActive,
                        'a_start'  => $aStart,
                        'a_end'    => $aEnd
                    ];
                }
            }

            $jsonSched = json_encode($newSchedule, JSON_UNESCAPED_UNICODE);
            $up = $pdo->prepare("UPDATE doctors SET schedule_info = ? WHERE id = ?");
            if ($up->execute([$jsonSched, $doctorId])) {
                $message = 'برنامه شیفت‌های هفتگی این همکار با موفقیت در سامانه نوبت‌دهی ذخیره شد.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch all affiliated doctors and groomers
$staffList = $orgService->getDoctors($orgId);

// Selected doctor for shift editing
$selectedDocId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : (!empty($staffList) ? (int)$staffList[0]['id'] : 0);
$selectedDoc = null;
foreach ($staffList as $st) {
    if ((int)$st['id'] === $selectedDocId) {
        $selectedDoc = $st;
        break;
    }
}
if (!$selectedDoc && !empty($staffList)) {
    $selectedDoc = $staffList[0];
    $selectedDocId = (int)$selectedDoc['id'];
}

$currentSchedule = [];
if ($selectedDoc) {
    $currentSchedule = json_decode($selectedDoc['schedule_info'] ?? '{}', true) ?: [];
}

// Fetch active blocked slots
$bStmt = $pdo->prepare("
    SELECT bs.*, d.name as doctor_name, d.specialty, d.provider_type
    FROM doctor_blocked_slots bs
    JOIN doctors d ON bs.doctor_id = d.id
    WHERE bs.organization_id = ? OR bs.doctor_id IN (
        SELECT doctor_id FROM organization_doctors WHERE organization_id = ?
    )
    ORDER BY bs.block_date ASC
");
$bStmt->execute([$orgId, $orgId]);
$blockedSlots = $bStmt->fetchAll(PDO::FETCH_ASSOC);

$weekdays = [
    'sat' => 'شنبه',
    'sun' => 'یکشنبه',
    'mon' => 'دوشنبه',
    'tue' => 'سه‌شنبه',
    'wed' => 'چهارشنبه',
    'thu' => 'پنجشنبه',
    'fri' => 'جمعه'
];
?>

<div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-indigo-600 text-3xl">schedule</span>
                <span>مدیریت زمان و تقویم شیفت‌های مرکز</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">زمان‌بندی شیفت صبح و عصر پزشکان و گرومرها، مدیریت روزهای حضور، و مسدودسازی نوبت‌ها برای مرخصی یا جراحی</p>
        </div>

        <button onclick="document.getElementById('block-slot-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-md shadow-rose-600/20 flex items-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">event_busy</span>
            <span>مسدودسازی روز یا بازه زمانی</span>
        </button>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Modal for Blocked Slots / Leave -->
    <div id="block-slot-modal" class="hidden bg-white p-6 rounded-3xl border border-rose-200 shadow-xl animate-fade-in space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600">event_busy</span>
                <h3 class="text-sm font-black text-slate-800">مسدودسازی نوبت برای مرخصی، جراحی اختصاصی یا ضدعفونی سالن</h3>
            </div>
            <button type="button" onclick="document.getElementById('block-slot-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="shifts.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="block_slot">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب همکار *</label>
                <select name="doctor_id" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <?php foreach ($staffList as $st): 
                        $isGr = ($st['role_type'] ?? '') === 'groomer';
                    ?>
                        <option value="<?= $st['id'] ?>" <?= $selectedDocId === (int)$st['id'] ? 'selected' : '' ?>>
                            <?= $isGr ? '✂️ گرومر: ' : '🩺 دکتر: ' ?><?= htmlspecialchars($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تاریخ مسدودی *</label>
                <input type="date" name="block_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">از ساعت (اختیاری)</label>
                <input type="time" name="start_time" value="00:00" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تا ساعت (اختیاری)</label>
                <input type="time" name="end_time" value="23:59" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-mono">
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">علت مسدودی *</label>
                <input type="text" name="reason" required placeholder="مثال: مرخصی سالانه، سمینار، ضدعفونی اتاق عمل یا رزرو تلفنی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-2 lg:col-span-1 flex items-end">
                <button type="submit" class="w-full h-11 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">lock</span>
                    <span>ثبت مسدودی در سامانه</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Staff Shift Selector Tabs -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3 flex-wrap">
        <span class="text-xs font-black text-slate-700">انتخاب همکار جهت تنظیم شیفت:</span>
        <div class="flex items-center gap-2 overflow-x-auto">
            <?php foreach ($staffList as $st): 
                $isSelected = ((int)$st['id'] === $selectedDocId);
                $isGr = ($st['role_type'] ?? '') === 'groomer';
            ?>
                <a href="shifts.php?doctor_id=<?= (int)$st['id'] ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= $isSelected ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="material-symbols-outlined text-xs"><?= $isGr ? 'content_cut' : 'stethoscope' ?></span>
                    <span><?= htmlspecialchars($st['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($selectedDoc): ?>
    <!-- Weekly Shifts Configuration Grid -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">calendar_month</span>
                    <span>برنامه حضور هفتگی: <?= htmlspecialchars($selectedDoc['name']) ?></span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">شیفت‌های فعال در تقویم عمومی نوبت‌دهی به همراه اسلات‌های ۳۰ الی ۴۵ دقیقه‌ای نمایش داده می‌شوند.</p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                <?= htmlspecialchars($selectedDoc['specialty']) ?>
            </span>
        </div>

        <form method="POST" action="shifts.php?doctor_id=<?= $selectedDocId ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_weekly_shifts">
            <input type="hidden" name="doctor_id" value="<?= $selectedDocId ?>">

            <div class="space-y-3">
                <?php foreach ($weekdays as $dayKey => $dayFa): 
                    $dayData = $currentSchedule[$dayKey] ?? null;
                    $mActive = !empty($dayData['m_active']);
                    $aActive = !empty($dayData['a_active']);
                    $mStart = $dayData['m_start'] ?? '09:00';
                    $mEnd   = $dayData['m_end'] ?? '13:00';
                    $aStart = $dayData['a_start'] ?? '16:00';
                    $aEnd   = $dayData['a_end'] ?? '21:00';
                ?>
                <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 hover:bg-white transition-all grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <!-- Day Name -->
                    <div class="md:col-span-2 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= ($mActive || $aActive) ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                        <span class="font-black text-sm text-slate-800"><?= $dayFa ?></span>
                    </div>

                    <!-- Morning Shift -->
                    <div class="md:col-span-5 bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="<?= $dayKey ?>_m_active" value="1" <?= $mActive ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded">
                                <span class="text-xs font-bold text-slate-700">شیفت صبح</span>
                            </label>
                            <span class="material-symbols-outlined text-amber-500 text-sm">light_mode</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                            <div>
                                <span class="text-[10px] text-slate-400 block mb-0.5">شروع:</span>
                                <input type="time" name="<?= $dayKey ?>_m_start" value="<?= htmlspecialchars($mStart) ?>" class="w-full px-2 py-1 rounded-lg border border-slate-200 text-xs text-center">
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block mb-0.5">پایان:</span>
                                <input type="time" name="<?= $dayKey ?>_m_end" value="<?= htmlspecialchars($mEnd) ?>" class="w-full px-2 py-1 rounded-lg border border-slate-200 text-xs text-center">
                            </div>
                        </div>
                    </div>

                    <!-- Afternoon Shift -->
                    <div class="md:col-span-5 bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="<?= $dayKey ?>_a_active" value="1" <?= $aActive ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded">
                                <span class="text-xs font-bold text-slate-700">شیفت بعد از ظهر / عصر</span>
                            </label>
                            <span class="material-symbols-outlined text-indigo-500 text-sm">wb_twilight</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                            <div>
                                <span class="text-[10px] text-slate-400 block mb-0.5">شروع:</span>
                                <input type="time" name="<?= $dayKey ?>_a_start" value="<?= htmlspecialchars($aStart) ?>" class="w-full px-2 py-1 rounded-lg border border-slate-200 text-xs text-center">
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block mb-0.5">پایان:</span>
                                <input type="time" name="<?= $dayKey ?>_a_end" value="<?= htmlspecialchars($aEnd) ?>" class="w-full px-2 py-1 rounded-lg border border-slate-200 text-xs text-center">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>ذخیره تغییرات شیفت‌های هفتگی</span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Active Blocked Slots Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-600">block</span>
                <h3 class="text-sm font-black text-slate-800">لیست روزها و زمان‌های مسدود شده (مرخصی‌ها و رزروهای خاص)</h3>
            </div>
            <span class="text-xs font-bold text-slate-400"><?= count($blockedSlots) ?> مورد فعال</span>
        </div>

        <?php if (empty($blockedSlots)): ?>
            <div class="text-center py-8 text-slate-400 text-xs font-bold">
                در حال حاضر هیچ بازه مسدودی برای همکاران این مرکز ثبت نشده است و تمامی شیفت‌ها در دسترس مراجعین هستند.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">نام همکار</th>
                            <th class="px-4 py-3">نقش</th>
                            <th class="px-4 py-3">تاریخ مسدودی</th>
                            <th class="px-4 py-3">بازه زمانی</th>
                            <th class="px-4 py-3">علت مسدودی</th>
                            <th class="px-4 py-3 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach ($blockedSlots as $bs): 
                            $isGr = ($bs['provider_type'] ?? '') === 'groomer';
                        ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-black text-slate-900"><?= htmlspecialchars($bs['doctor_name']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isGr ? 'bg-pink-50 text-pink-700' : 'bg-indigo-50 text-indigo-700' ?>">
                                        <?= $isGr ? 'گرومر پت' : 'دامپزشک' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-700"><?= htmlspecialchars($bs['block_date']) ?></td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    <?= ($bs['start_time'] === '00:00' && $bs['end_time'] === '23:59') ? 'تمام روز' : htmlspecialchars($bs['start_time'] . ' الی ' . $bs['end_time']) ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($bs['reason'] ?? 'مرخصی') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="shifts.php" onsubmit="return confirm('آیا از بازگشایی مجدد این زمان اطمینان دارید؟')" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="unblock_slot">
                                        <input type="hidden" name="slot_id" value="<?= (int)$bs['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-colors">
                                            رفع مسدودی
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
