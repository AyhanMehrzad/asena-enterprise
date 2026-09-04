<?php
$currentPage = 'calendar_notes';
require_once 'includes/admin_header.php';
require_once '../includes/functions.php';

// Auto-ensure admin_notes table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_notes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `admin_id` int(11) NOT NULL,
          `note_date` date NOT NULL,
          `note_time` varchar(20) DEFAULT NULL,
          `title` varchar(255) NOT NULL,
          `content` text DEFAULT NULL,
          `priority` enum('normal','important','urgent') DEFAULT 'normal',
          `is_completed` tinyint(1) DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `admin_id` (`admin_id`),
          KEY `note_date` (`note_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}

$adminId = (int)($_SESSION['user_id'] ?? 1);
$success = '';
$error = '';

// --- Pure Jalali / Shamsi Conversion Math Functions ---
function jalali_to_gregorian($jy, $jm, $jd) {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * (int)($days / 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * (int)(--$days / 36524);
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(--$days / 365);
        $days %= 365;
    }
    $gd = $days + 1;
    $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * (int)($days / 12053));
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

$persianMonths = [
    1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
    4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
    7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
    10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
];

$todayG = new DateTime();
[$todayJY, $todayJM, $todayJD] = gregorian_to_jalali((int)$todayG->format('Y'), (int)$todayG->format('m'), (int)$todayG->format('d'));

// Current viewing Jalali Year & Month
$viewJY = isset($_GET['jy']) ? (int)$_GET['jy'] : $todayJY;
$viewJM = isset($_GET['jm']) ? (int)$_GET['jm'] : $todayJM;
if ($viewJM < 1) { $viewJM = 12; $viewJY--; }
if ($viewJM > 12) { $viewJM = 1; $viewJY++; }

// Selected Day (Gregorian for DB, Jalali for UI)
$selectedGDate = $_GET['date'] ?? date('Y-m-d');
$selDateObj = new DateTime($selectedGDate);
[$selJY, $selJM, $selJD] = gregorian_to_jalali((int)$selDateObj->format('Y'), (int)$selDateObj->format('m'), (int)$selDateObj->format('d'));

// POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'add_note') {
        $title = trim($_POST['title'] ?? '');
        $noteDate = trim($_POST['note_date'] ?? $selectedGDate);
        $noteTime = trim($_POST['note_time'] ?? '');
        $priority = in_array($_POST['priority'] ?? '', ['normal', 'important', 'urgent']) ? $_POST['priority'] : 'normal';
        $content = trim($_POST['content'] ?? '');

        if (!empty($title) && !empty($noteDate)) {
            $stmt = $pdo->prepare("INSERT INTO admin_notes (admin_id, note_date, note_time, title, content, priority) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$adminId, $noteDate, $noteTime ?: null, $title, $content ?: null, $priority])) {
                $success = "یادداشت و تسک جدید با موفقیت ذخیره شد.";
                $selectedGDate = $noteDate;
            } else {
                $error = "خطا در ذخیره یادداشت.";
            }
        } else {
            $error = "لطفاً عنوان تسک و تاریخ را مشخص فرمایید.";
        }
    } elseif ($action === 'toggle_note') {
        $noteId = (int)$_POST['note_id'];
        $stmt = $pdo->prepare("UPDATE admin_notes SET is_completed = 1 - is_completed WHERE id = ?");
        $stmt->execute([$noteId]);
        $success = "وضعیت تسک بروزرسانی شد.";
    } elseif ($action === 'delete_note') {
        $noteId = (int)$_POST['note_id'];
        $stmt = $pdo->prepare("DELETE FROM admin_notes WHERE id = ?");
        $stmt->execute([$noteId]);
        $success = "یادداشت با موفقیت حذف شد.";
    }
}

// Calculate month length
$monthDaysCount = ($viewJM <= 6) ? 31 : (($viewJM <= 11) ? 30 : 29);
// Calculate 1st day of month weekday (0: Sat, 1: Sun, ..., 6: Fri)
$firstDayG = jalali_to_gregorian($viewJY, $viewJM, 1);
$firstDayWeekday = (new DateTime($firstDayG))->format('w'); // 0: Sun, 6: Sat
$jalaliFirstWeekday = ($firstDayWeekday + 1) % 7; // Convert to Sat=0, Sun=1, ..., Fri=6

$monthStartG = jalali_to_gregorian($viewJY, $viewJM, 1);
$monthEndG = jalali_to_gregorian($viewJY, $viewJM, $monthDaysCount);

// Fetch all notes for current month
$notesStmt = $pdo->prepare("SELECT * FROM admin_notes WHERE note_date BETWEEN ? AND ? ORDER BY note_time ASC, id ASC");
$notesStmt->execute([$monthStartG, $monthEndG]);
$monthNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

// Index notes by Gregorian date
$notesByDate = [];
$statsTotal = count($monthNotes);
$statsCompleted = 0;
$statsUrgent = 0;
foreach ($monthNotes as $n) {
    $notesByDate[$n['note_date']][] = $n;
    if ($n['is_completed']) $statsCompleted++;
    if ($n['priority'] === 'urgent') $statsUrgent++;
}

// Fetch notes for the selected date
$selectedDayStmt = $pdo->prepare("SELECT * FROM admin_notes WHERE note_date = ? ORDER BY is_completed ASC, note_time ASC, id ASC");
$selectedDayStmt->execute([$selectedGDate]);
$selectedDayNotes = $selectedDayStmt->fetchAll(PDO::FETCH_ASSOC);

// Navigation URLs
$prevJM = $viewJM - 1; $prevJY = $viewJY;
if ($prevJM < 1) { $prevJM = 12; $prevJY--; }
$nextJM = $viewJM + 1; $nextJY = $viewJY;
if ($nextJM > 12) { $nextJM = 1; $nextJY++; }
?>

<div class="space-y-6 md:space-y-8 pb-16">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl md:text-3xl font-black text-primary flex items-center gap-2.5">
                <span class="material-symbols-outlined text-3xl text-secondary-container">calendar_month</span>
                تقویم وظایف و یادداشت‌های مدیریت
            </h2>
            <p class="text-xs md:text-sm text-on-surface-variant mt-1">
                برنامه‌ریزی روزانه، یادداشت امور کلینیک و فروشگاه، و ثبت تسک‌های تیمی مدیران
            </p>
        </div>

        <!-- Month Navigation & Today Jump -->
        <div class="flex items-center gap-2 self-start md:self-auto bg-white p-1.5 rounded-2xl shadow-sm border border-outline-variant/30">
            <a href="?jy=<?= $prevJY ?>&jm=<?= $prevJM ?>&date=<?= $selectedGDate ?>" class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-xl transition-all" title="ماه قبل">
                <span class="material-symbols-outlined text-lg">chevron_right</span>
            </a>
            
            <div class="px-4 py-1 text-center">
                <span class="font-black text-sm text-primary block"><?= $persianMonths[$viewJM] ?> <?= $viewJY ?></span>
            </div>

            <a href="?jy=<?= $nextJY ?>&jm=<?= $nextJM ?>&date=<?= $selectedGDate ?>" class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-low rounded-xl transition-all" title="ماه بعد">
                <span class="material-symbols-outlined text-lg">chevron_left</span>
            </a>

            <a href="?jy=<?= $todayJY ?>&jm=<?= $todayJM ?>&date=<?= date('Y-m-d') ?>" class="px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">today</span>
                <span>امروز</span>
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 text-sm font-bold shadow-sm animate-in fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center gap-3 text-sm font-bold shadow-sm animate-in fade-in">
            <span class="material-symbols-outlined">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Monthly Stats Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">checklist</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">کل وظایف ماه <?= $persianMonths[$viewJM] ?></p>
                <h4 class="text-xl font-black text-primary mt-0.5"><?= $statsTotal ?> تسک</h4>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">انجام شده</p>
                <h4 class="text-xl font-black text-emerald-600 mt-0.5"><?= $statsCompleted ?> از <?= $statsTotal ?></h4>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-outline-variant/30 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">priority_high</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant">تسک‌های فوری و اضطراری</p>
                <h4 class="text-xl font-black text-rose-600 mt-0.5"><?= $statsUrgent ?> مورد</h4>
            </div>
        </div>
    </div>

    <!-- Main Content: Calendar Grid + Daily Tasks Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- 1. Interactive Shamsi Calendar (8 Cols on Desktop) -->
        <div class="lg:col-span-7 bg-white p-6 md:p-8 rounded-3xl border border-outline-variant/30 shadow-sm space-y-4">
            <div class="flex justify-between items-center border-b border-outline-variant/20 pb-4">
                <h3 class="font-bold text-base text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">date_range</span>
                    تقویم ماه <?= $persianMonths[$viewJM] ?> <?= $viewJY ?>
                </h3>
                <span class="text-xs text-on-surface-variant font-medium">برای مشاهده و ثبت یادداشت روی روز مورد نظر کلیک کنید</span>
            </div>

            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 gap-1 md:gap-2 text-center text-xs font-bold text-on-surface-variant py-2 bg-surface-container-low rounded-xl">
                <div>ش</div>
                <div>ی</div>
                <div>د</div>
                <div>س</div>
                <div>چ</div>
                <div>پ</div>
                <div class="text-rose-500">ج</div>
            </div>

            <!-- Calendar Days Grid -->
            <div class="grid grid-cols-7 gap-1 md:gap-2 text-center">
                <!-- Leading blank cells -->
                <?php for ($i = 0; $i < $jalaliFirstWeekday; $i++): ?>
                    <div class="h-16 md:h-20 rounded-2xl bg-surface-container-lowest/40 opacity-30 border border-transparent"></div>
                <?php endfor; ?>

                <!-- Actual Month Days -->
                <?php for ($d = 1; $d <= $monthDaysCount; $d++): 
                    $curG = jalali_to_gregorian($viewJY, $viewJM, $d);
                    $isToday = ($viewJY === $todayJY && $viewJM === $todayJM && $d === $todayJD);
                    $isSelected = ($curG === $selectedGDate);
                    $dayNotes = $notesByDate[$curG] ?? [];
                    $dayCount = count($dayNotes);
                    
                    $hasUrgent = false;
                    $hasImportant = false;
                    foreach ($dayNotes as $dn) {
                        if ($dn['priority'] === 'urgent') $hasUrgent = true;
                        if ($dn['priority'] === 'important') $hasImportant = true;
                    }
                ?>
                    <a href="?jy=<?= $viewJY ?>&jm=<?= $viewJM ?>&date=<?= $curG ?>" 
                       class="h-16 md:h-20 p-1.5 md:p-2 rounded-2xl border transition-all flex flex-col justify-between items-center group relative cursor-pointer
                              <?= $isSelected ? 'bg-primary text-white border-primary shadow-md scale-[1.03] z-10' : ($isToday ? 'bg-indigo-50/80 border-indigo-300 text-indigo-900' : 'bg-surface-container-lowest hover:bg-surface-container-low border-outline-variant/30 text-on-surface') ?>">
                        
                        <div class="flex items-center justify-between w-full">
                            <span class="text-xs md:text-sm font-black <?= $isSelected ? 'text-white' : ($isToday ? 'text-indigo-700' : '') ?>">
                                <?= $d ?>
                            </span>
                            <?php if ($isToday && !$isSelected): ?>
                                <span class="w-2 h-2 rounded-full bg-indigo-600" title="امروز"></span>
                            <?php endif; ?>
                        </div>

                        <!-- Notes badge & priority dots -->
                        <?php if ($dayCount > 0): ?>
                            <div class="flex items-center gap-1 mt-auto">
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded-full <?= $isSelected ? 'bg-white/20 text-white' : 'bg-primary/10 text-primary' ?>">
                                    <?= $dayCount ?> تسک
                                </span>
                            </div>
                            <div class="flex gap-0.5 mt-0.5">
                                <?php if ($hasUrgent): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <?php endif; ?>
                                <?php if ($hasImportant): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- 2. Selected Day Tasks Workspace (5 Cols on Desktop) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Day Header & Add Task -->
            <div class="bg-white p-6 rounded-3xl border border-outline-variant/30 shadow-sm space-y-5">
                <div class="flex justify-between items-start border-b border-outline-variant/20 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-secondary-container block">یادداشت‌های روز انتخاب شده</span>
                        <h3 class="text-lg font-black text-primary mt-0.5">
                            <?= $selJD ?> <?= $persianMonths[$selJM] ?> <?= $selJY ?>
                        </h3>
                        <span class="text-xs text-slate-400 font-mono" dir="ltr"><?= $selectedGDate ?></span>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold">
                        <?= count($selectedDayNotes) ?> مورد
                    </span>
                </div>

                <!-- Add Task Form -->
                <form method="POST" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="note_date" value="<?= $selectedGDate ?>">

                    <div>
                        <input type="text" name="title" placeholder="عنوان وظیفه یا یادداشت (مثال: تماس با تامین‌کننده غذا)" required class="w-full p-3 rounded-xl border border-outline-variant text-xs outline-none focus:ring-2 focus:ring-primary bg-surface-container-lowest font-medium">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <select name="priority" class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none bg-surface-container-lowest font-bold text-on-surface">
                                <option value="normal">⚪ اولویت: عادی</option>
                                <option value="important">🟡 اولویت: مهم</option>
                                <option value="urgent">🔴 اولویت: فوری</option>
                            </select>
                        </div>
                        <div>
                            <input type="time" name="note_time" placeholder="ساعت" class="w-full p-2.5 rounded-xl border border-outline-variant text-xs outline-none bg-surface-container-lowest font-mono text-left" dir="ltr">
                        </div>
                    </div>

                    <div>
                        <textarea name="content" rows="2" placeholder="توضیحات تکمیلی یا جزئیات تسک (اختیاری)..." class="w-full p-3 rounded-xl border border-outline-variant text-xs outline-none focus:ring-2 focus:ring-primary bg-surface-container-lowest resize-none"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-primary hover:bg-primary-container text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-md transition-all active:scale-[0.99]">
                        <span class="material-symbols-outlined text-base">add_task</span>
                        <span>ثبت وظیفه برای این روز</span>
                    </button>
                </form>
            </div>

            <!-- Day Task List -->
            <div class="bg-white p-6 rounded-3xl border border-outline-variant/30 shadow-sm space-y-4">
                <h4 class="font-bold text-xs text-primary flex items-center gap-2 border-b border-outline-variant/20 pb-3">
                    <span class="material-symbols-outlined text-secondary-container text-base">format_list_bulleted</span>
                    لیست امور و یادداشت‌های این روز
                </h4>

                <?php if (empty($selectedDayNotes)): ?>
                    <div class="py-8 text-center text-on-surface-variant space-y-2">
                        <span class="material-symbols-outlined text-3xl text-slate-300">event_note</span>
                        <p class="text-xs font-bold">هیچ وظیفه یا یادداشتی برای این تاریخ ثبت نشده است.</p>
                        <p class="text-[11px] text-slate-400">با فرم بالا می‌توانید اولین تسک این روز را اضافه کنید.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2.5 max-h-[420px] overflow-y-auto custom-scrollbar pr-1">
                        <?php foreach ($selectedDayNotes as $note): 
                            $isDone = (bool)$note['is_completed'];
                            $priorityClass = match($note['priority']) {
                                'urgent' => 'border-r-4 border-r-rose-500 bg-rose-50/40',
                                'important' => 'border-r-4 border-r-amber-500 bg-amber-50/40',
                                default => 'border-r-4 border-r-blue-500 bg-surface-container-lowest'
                            };
                            $priorityBadge = match($note['priority']) {
                                'urgent' => '<span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-bold">فوری</span>',
                                'important' => '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">مهم</span>',
                                default => ''
                            };
                        ?>
                            <div class="p-3.5 rounded-2xl border border-outline-variant/30 <?= $priorityClass ?> <?= $isDone ? 'opacity-60 bg-gray-50' : '' ?> flex items-start justify-between gap-3 transition-all">
                                
                                <!-- Checkbox Toggle -->
                                <form method="POST" class="m-0 mt-0.5">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_note">
                                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                    <button type="submit" class="w-5 h-5 rounded-lg border flex items-center justify-center transition-colors <?= $isDone ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 hover:border-emerald-500' ?>" title="<?= $isDone ? 'تغییر به انجام‌نشده' : 'تکمیل وظیفه' ?>">
                                        <?php if ($isDone): ?>
                                            <span class="material-symbols-outlined text-xs">check</span>
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h5 class="text-xs font-bold text-primary <?= $isDone ? 'line-through text-slate-500' : '' ?>">
                                            <?= htmlspecialchars($note['title']) ?>
                                        </h5>
                                        <?= $priorityBadge ?>
                                        <?php if (!empty($note['note_time'])): ?>
                                            <span class="text-[10px] text-slate-500 font-mono" dir="ltr">⏰ <?= substr($note['note_time'], 0, 5) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($note['content'])): ?>
                                        <p class="text-[11px] text-on-surface-variant mt-1 leading-relaxed <?= $isDone ? 'line-through' : '' ?>">
                                            <?= nl2br(htmlspecialchars($note['content'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Delete -->
                                <form method="POST" onsubmit="return confirm('آیا از حذف این یادداشت اطمینان دارید؟');" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                    <button type="submit" class="text-slate-400 hover:text-rose-500 p-1 rounded-lg transition-colors" title="حذف">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
