<?php
$currentPage = 'dashboard';
require_once 'includes/pansion_header.php';

$success = '';
$error = '';

$isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
$nowSql = $isSqlite ? "datetime('now')" : "NOW()";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    // 1. Accept/Decline Organization Invitation
    if ($action === 'accept_org_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $res = App::organization()->respondToInvitation($invId, $pansionUserId, 'accepted');
        if (!empty($res['success'])) {
            $success = $res['message'];
        } else {
            $error = $res['message'] ?? 'خطا در پذیرش دعوت‌نامه همکاری.';
        }
    } elseif ($action === 'decline_org_invitation') {
        $invId = (int)($_POST['invitation_id'] ?? 0);
        $reason = trim($_POST['declined_reason'] ?? '');
        $res = App::organization()->respondToInvitation($invId, $pansionUserId, 'declined', $reason);
        if (!empty($res['success'])) {
            $success = $res['message'];
        } else {
            $error = $res['message'] ?? 'خطا در رد دعوت‌نامه.';
        }
    }

    // 2. Kindergarten Hourly / Overnight Check-in
    elseif ($action === 'check_in_pet') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $petName = trim($_POST['pet_name'] ?? '');
        $petSpecies = trim($_POST['pet_species'] ?? 'سگ');
        $petBreed = trim($_POST['pet_breed'] ?? '');
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = preg_replace('/[^0-9]/', '', trim($_POST['customer_phone'] ?? ''));
        $careMode = in_array($_POST['care_mode'] ?? '', ['hourly', 'overnight']) ? $_POST['care_mode'] : 'hourly';
        
        $checkInDate = trim($_POST['check_in_date'] ?? date('Y-m-d'));
        $checkInTime = trim($_POST['check_in_time'] ?? date('H:i'));
        $hoursCount = max(1, (int)($_POST['hours_count'] ?? 3));
        $hourlyRate = (int)preg_replace('/[^0-9]/', '', $_POST['hourly_rate'] ?? '60000');
        if ($hourlyRate <= 0) $hourlyRate = 60000;

        $checkOutDate = trim($_POST['check_out_date'] ?? date('Y-m-d'));
        $expectedCheckOutTime = trim($_POST['expected_check_out_time'] ?? '');

        if ($careMode === 'hourly') {
            $checkOutDate = $checkInDate;
            if (empty($expectedCheckOutTime)) {
                $expectedCheckOutTime = date('H:i', strtotime("{$checkInDate} {$checkInTime} + {$hoursCount} hours"));
            }
            $nights = 0;
            $totalPrice = $hoursCount * $hourlyRate;
        } else {
            $nights = max(1, (int)round((strtotime($checkOutDate) - strtotime($checkInDate)) / 86400));
            $expectedCheckOutTime = '12:00';
            $hoursCount = $nights * 24;
            $totalPrice = $nights * 450000; // default nightly rate
        }

        $isIsolated = !empty($_POST['is_isolated_zone']) ? 1 : 0;
        $isolationReason = $isIsolated ? trim($_POST['isolation_reason'] ?? 'نیازمند نگهداری انفرادی / نژاد پرخاشگر یا ناآرام') : null;
        $dietNotes = trim($_POST['special_diet_notes'] ?? '');
        $medNotes = trim($_POST['medication_notes'] ?? '');
        $belongings = trim($_POST['belongings_list'] ?? '');
        $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');

        if (empty($petName) || empty($custPhone) || strlen($custPhone) !== 11) {
            $error = 'نام پت و شماره همراه ۱۱ رقمی سرپرست الزامی است.';
        } else {
            // Check or assign default zone
            if ($roomId <= 0) {
                // If isolated, look for isolation zone; otherwise look for social kindergarten hall
                $targetType = $isIsolated ? 'safety_isolation' : 'social_kindergarten';
                $findR = $pdo->prepare("SELECT id FROM pansion_rooms WHERE pansion_user_id = ? AND (zone_type = ? OR room_type = ?) LIMIT 1");
                $findR->execute([$pansionUserId, $targetType, $isIsolated ? 'hospital_box' : 'daycare_playpen']);
                $roomId = (int)$findR->fetchColumn();

                if ($roomId <= 0) {
                    $rAny = $pdo->prepare("SELECT id FROM pansion_rooms WHERE pansion_user_id = ? LIMIT 1");
                    $rAny->execute([$pansionUserId]);
                    $roomId = (int)$rAny->fetchColumn();
                }
            }

            // Find or create customer
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

            $insB = $pdo->prepare("
                INSERT INTO pansion_bookings 
                (room_id, organization_id, pansion_user_id, customer_user_id, pet_name, pet_species, pet_breed, 
                 care_mode, check_in_date, check_in_time, check_out_date, expected_check_out_time, hours_count, hourly_rate, 
                 nights_count, total_price, status, is_isolated_zone, isolation_reason, special_diet_notes, 
                 medication_notes, belongings_list, emergency_contact_phone, play_activity_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'checked_in', ?, ?, ?, ?, ?, ?, 'پذیرش شد و به بخش مربوطه منتقل گردید.', {$nowSql})
            ");
            if ($insB->execute([
                $roomId, $orgId, $pansionUserId, $custId, $petName, $petSpecies, $petBreed,
                $careMode, $checkInDate, $checkInTime, $checkOutDate, $expectedCheckOutTime, $hoursCount, $hourlyRate,
                $nights, $totalPrice, $isIsolated, $isolationReason, $dietNotes, $medNotes, $belongings, $emergencyPhone
            ])) {
                $zoneMsg = $isIsolated ? '🚨 زون ایزوله امنیتی انفرادی (پیت‌بول/پرخاشگر)' : 'تالار بازی گروهی مهد اجتماعی';
                $success = "پذیرش ساعتی «{$petName}» با موفقیت در «{$zoneMsg}» ثبت گردید. زمان ترخیص پیش‌بینی‌شده: {$expectedCheckOutTime}.";
            } else {
                $error = 'خطا در ثبت پذیرش در دیتابیس.';
            }
        }
    }

    // 3. Early Checkout / Pick-up Pet (Releases concurrent capacity immediately)
    elseif ($action === 'check_out_pet') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $actualTime = date('H:i');

        if ($bookingId > 0) {
            $updB = $pdo->prepare("UPDATE pansion_bookings SET status = 'checked_out', actual_check_out_time = ? WHERE id = ? AND pansion_user_id = ?");
            if ($updB->execute([$actualTime, $bookingId, $pansionUserId])) {
                $success = "پت با موفقیت به سرپرست تحویل داده شد (ترخیص در ساعت {$actualTime}) و یک جایگاه از ظرفیت همزمان مهد بلافاصله آزاد گردید.";
            }
        }
    }

    // 4. Stay Extension (تمدید مدت نگهداری با درخواست سرپرست)
    elseif ($action === 'extend_stay') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $extraHours = max(1, (int)($_POST['extra_hours'] ?? 1));

        $bStmt = $pdo->prepare("SELECT * FROM pansion_bookings WHERE id = ? AND pansion_user_id = ? AND status = 'checked_in'");
        $bStmt->execute([$bookingId, $pansionUserId]);
        $bk = $bStmt->fetch(PDO::FETCH_ASSOC);

        if ($bk) {
            $newHours = (int)$bk['hours_count'] + $extraHours;
            $baseDate = $bk['check_in_date'] ?: date('Y-m-d');
            $baseTime = $bk['check_in_time'] ?: '09:00';
            $newExpected = date('H:i', strtotime("{$baseDate} {$baseTime} + {$newHours} hours"));
            $rate = (int)$bk['hourly_rate'] > 0 ? (int)$bk['hourly_rate'] : 60000;
            $newTotal = $newHours * $rate;

            $updExt = $pdo->prepare("UPDATE pansion_bookings SET hours_count = ?, expected_check_out_time = ?, total_price = ? WHERE id = ?");
            if ($updExt->execute([$newHours, $newExpected, $newTotal, $bookingId])) {
                $success = "مدت اقامت «{$bk['pet_name']}» به میزان {$extraHours} ساعت تمدید شد. زمان جدید ترخیص: {$newExpected} (هزینه نهایی: " . number_format($newTotal) . " تومان).";
            }
        }
    }

    // 5. Update Pet Play & Care Activity Notes
    elseif ($action === 'update_activity_note') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $note = trim($_POST['play_activity_notes'] ?? '');
        if ($bookingId > 0) {
            $pdo->prepare("UPDATE pansion_bookings SET play_activity_notes = ? WHERE id = ? AND pansion_user_id = ?")->execute([$note, $bookingId, $pansionUserId]);
            $success = "گزارش وضعیت و فعالیت بازی پت با موفقیت به‌روزرسانی شد.";
        }
    }

    // 6. Adjust Concurrent Capacity Limit
    elseif ($action === 'update_capacity_limit') {
        $newCap = max(5, (int)($_POST['concurrent_capacity'] ?? 15));
        $pdo->prepare("UPDATE pansion_rooms SET capacity = ? WHERE pansion_user_id = ? AND (zone_type = 'social_kindergarten' OR room_type = 'daycare_playpen')")->execute([$newCap, $pansionUserId]);
        $success = "سقف مجاز پذیرش همزمان حیوانات در مهد به {$newCap} پت تغییر یافت.";
    }

    // 7. Add Room / Custom Zone
    elseif ($action === 'add_room') {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomTitle = trim($_POST['room_title'] ?? '');
        $zoneType = trim($_POST['zone_type'] ?? 'social_kindergarten');
        $capacity = max(1, (int)($_POST['capacity'] ?? 1));
        $hourlyRate = (int)preg_replace('/[^0-9]/', '', $_POST['hourly_rate'] ?? '60000');
        $priceNight = (int)preg_replace('/[^0-9]/', '', $_POST['price_per_night'] ?? '450000');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($roomNumber) && !empty($roomTitle)) {
            $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;
            $insR = $pdo->prepare("
                INSERT INTO pansion_rooms 
                (organization_id, pansion_user_id, room_number, room_title, room_type, zone_type, capacity, hourly_rate, price_per_night, description, status, created_at)
                VALUES (?, ?, ?, ?, 'daycare_playpen', ?, ?, ?, ?, ?, 'available', {$nowSql})
            ");
            if ($insR->execute([$orgId, $pansionUserId, $roomNumber, $roomTitle, $zoneType, $capacity, $hourlyRate, $priceNight, $desc])) {
                $success = "زون جدید «{$roomTitle}» با سقف ظرفیت {$capacity} پت با موفقیت تعریف شد.";
            }
        }
    }
}

// Fetch Pending Organization Invitations
$pendingOrgInvitations = [];
try {
    $pendingOrgInvitations = App::organization()->getUserInvitations($pansionUserId, 'pending');
} catch (Throwable $e) {}

// Fetch Pansion Rooms / Zones
$rooms = [];
try {
    $rQuery = $pdo->prepare("SELECT * FROM pansion_rooms WHERE pansion_user_id = ? ORDER BY id ASC");
    $rQuery->execute([$pansionUserId]);
    $rooms = $rQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Seed default Kindergarten Zones if empty
if (empty($rooms)) {
    $defaultRooms = [
        [
            'room_number' => 'HALL-01',
            'room_title'  => 'تالار و حیاط بازی مهد اجتماعی (Social Group Playpen)',
            'room_type'   => 'daycare_playpen',
            'zone_type'   => 'social_kindergarten',
            'capacity'    => 15,
            'hourly_rate' => 60000,
            'price_per_night' => 450000,
            'description' => 'فضای بازی دسته‌جمعی مهد با مربی مقیم، اسباب‌بازی‌های تعاملی، چمن ضدحساسیت و موسیقی آرامش‌بخش'
        ],
        [
            'room_number' => 'ISO-01',
            'room_title'  => 'زون ایزوله و نگهداری انفرادی امنیتی (Pitbull & Aggressive Quarantine)',
            'room_type'   => 'hospital_box',
            'zone_type'   => 'safety_isolation',
            'capacity'    => 4,
            'hourly_rate' => 85000,
            'price_per_night' => 550000,
            'description' => 'باکس‌های استاندارد ایزوله مجهز به تهویه مستقل، دوربین اختصاصی و محوطه پیاده‌روی انفرادی جهت تضمین امنیت کامل سایر حیوانات'
        ],
        [
            'room_number' => 'CAT-01',
            'room_title'  => 'مهد و سالن بازی اختصاصی گربه‌ها (Cat Lounge)',
            'room_type'   => 'cat_room',
            'zone_type'   => 'social_kindergarten',
            'capacity'    => 8,
            'hourly_rate' => 50000,
            'price_per_night' => 380000,
            'description' => 'درخت‌های چندطبقه گربه، اتاق بدون صدا و استرس، جای خواب‌های دیواری و تهویه اختصاصی'
        ]
    ];
    $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;
    foreach ($defaultRooms as $dr) {
        try {
            $insDR = $pdo->prepare("INSERT INTO pansion_rooms (organization_id, pansion_user_id, room_number, room_title, room_type, zone_type, capacity, hourly_rate, price_per_night, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', {$nowSql})");
            $insDR->execute([$orgId, $pansionUserId, $dr['room_number'], $dr['room_title'], $dr['room_type'], $dr['zone_type'], $dr['capacity'], $dr['hourly_rate'], $dr['price_per_night'], $dr['description']]);
        } catch (Throwable $e) {}
    }
    try {
        $rQuery = $pdo->prepare("SELECT * FROM pansion_rooms WHERE pansion_user_id = ? ORDER BY id ASC");
        $rQuery->execute([$pansionUserId]);
        $rooms = $rQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

// Fetch Active Boarders (Currently Checked-in Guests)
$boarders = [];
try {
    $bQuery = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_title, r.room_type, r.zone_type,
               u.name as customer_name, u.phone as customer_phone
        FROM pansion_bookings b
        LEFT JOIN pansion_rooms r ON b.room_id = r.id
        LEFT JOIN users u ON b.customer_user_id = u.id
        WHERE b.pansion_user_id = ? AND b.status = 'checked_in'
        ORDER BY b.id DESC
    ");
    $bQuery->execute([$pansionUserId]);
    $boarders = $bQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Calculate Dynamic Kindergarten Capacity Stats
$totalConcurrentCapacity = 0;
$mainPlaypenRoom = null;
foreach ($rooms as $r) {
    $totalConcurrentCapacity += (int)$r['capacity'];
    if (($r['zone_type'] ?? '') === 'social_kindergarten' || ($r['room_type'] ?? '') === 'daycare_playpen') {
        if (!$mainPlaypenRoom) $mainPlaypenRoom = $r;
    }
}
if ($totalConcurrentCapacity <= 0) $totalConcurrentCapacity = 15;

$activeCount = count($boarders);
$socialPets = array_filter($boarders, fn($b) => empty($b['is_isolated_zone']));
$isolatedPets = array_filter($boarders, fn($b) => !empty($b['is_isolated_zone']));
$socialCount = count($socialPets);
$isolatedCount = count($isolatedPets);
$availableSlots = max(0, $totalConcurrentCapacity - $activeCount);
$occupancyRate = min(100, round(($activeCount / $totalConcurrentCapacity) * 100));

// Today's total earned from daycare
$todayRevenue = 0;
foreach ($boarders as $b) {
    $todayRevenue += (int)($b['total_price'] ?? 0);
}
?>

<div class="space-y-6">

    <!-- Alerts -->
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
                <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950 via-slate-900 to-indigo-950 text-white border border-emerald-500/30 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
                    <div class="flex items-start gap-4 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center shrink-0 text-emerald-300">
                            <span class="material-symbols-outlined text-3xl">local_hospital</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-400 text-slate-950 animate-pulse">دعوت‌نامه رسمی همکاری کلینیک</span>
                                <h3 class="font-black text-sm text-white">مرکز درمانی «<?= htmlspecialchars($pInv['org_name']) ?>»</h3>
                            </div>
                            <p class="text-xs text-slate-300">
                                نقش پیشنهادی: <strong class="text-emerald-300"><?= htmlspecialchars($pInv['job_title'] ?: 'واحد پانسیون و بستری همکار') ?></strong>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0 relative z-10">
                        <form method="POST" action="index.php" class="inline m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="accept_org_invitation">
                            <input type="hidden" name="invitation_id" value="<?= (int)$pInv['id'] ?>">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs shadow-md transition-all">
                                تأیید و اتصال پانسیون
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- KINDERGARTEN DYNAMIC CAPACITY COCKPIT (Rule 11) -->
    <div class="cockpit-pansion text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-emerald-500/30 relative overflow-hidden space-y-6" style="background-color: #06241a !important; background-image: linear-gradient(135deg, #06241a 0%, #093526 50%, #041a12 100%) !important; color: #ffffff !important;">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-13 h-13 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-400 flex items-center justify-center font-black shadow-inner">
                    <span class="material-symbols-outlined text-3xl">toys</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>مهد و پانسیون تعاملی حیوانات (Daycare & Kindergarten)</span>
                        </span>
                        <span class="text-xs text-emerald-200 font-bold">| تعرفه ساعتی و مراقبت پویا</span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-white mt-1"><?= htmlspecialchars($pansionName) ?></h2>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-emerald-400 hover:bg-emerald-500 text-slate-950 font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">add_circle</span>
                    <span>+ پذیرش مهمان جدید (ساعتی / روزانه)</span>
                </button>
                <button type="button" onclick="document.getElementById('capacity-modal').classList.remove('hidden')" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all flex items-center gap-1.5" title="تغییر سقف همزمان پذیرش مهد">
                    <span class="material-symbols-outlined text-base">tune</span>
                    <span>سقف ظرفیت همزمان (<?= $totalConcurrentCapacity ?> پت)</span>
                </button>
            </div>
        </div>

        <!-- DYNAMIC CONCURRENT CAPACITY GAUGE & LIVE METRICS -->
        <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4 pt-1">
            
            <!-- Gauge 1: Real-time Concurrent Capacity -->
            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 space-y-2 md:col-span-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-emerald-100 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-emerald-400">group</span>
                        <span>سقف مجاز حیوانات همزمان در مجموعه:</span>
                    </span>
                    <strong class="text-emerald-300 font-mono text-sm">
                        <?= $activeCount ?> حاضر / <?= $totalConcurrentCapacity ?> سقف مجاز
                    </strong>
                </div>
                <div class="w-full bg-white/15 h-3 rounded-full overflow-hidden p-0.5">
                    <?php 
                        $gaugeColor = ($occupancyRate >= 90) ? 'from-rose-500 to-amber-500' : (($occupancyRate >= 70) ? 'from-amber-400 to-emerald-400' : 'from-emerald-400 to-teal-400');
                    ?>
                    <div class="bg-gradient-to-r <?= $gaugeColor ?> h-full rounded-full transition-all duration-500" style="width: <?= max(5, $occupancyRate) ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-emerald-200">
                    <span>ظرفیت پذیرش فوری: <strong class="text-white"><?= $availableSlots ?> جای خالی</strong></span>
                    <span>نرخ اشغال فضا: <strong class="text-white"><?= $occupancyRate ?>٪</strong></span>
                </div>
            </div>

            <!-- Gauge 2: Social Play Hall vs Safety Isolation Breakdown -->
            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 flex flex-col justify-between text-xs space-y-2">
                <span class="text-emerald-100 font-bold">تفکیک بخش‌های مهد:</span>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-emerald-300 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">pets</span>
                            <span>تالار بازی اجتماعی:</span>
                        </span>
                        <strong class="font-mono text-white"><?= $socialCount ?> پت</strong>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-amber-300 flex items-center gap-1" title="پیت‌بول، سگ‌های ناآرام، غیرعقیم یا تهاجمی">
                            <span class="material-symbols-outlined text-sm">security</span>
                            <span>زون ایزوله امنیتی:</span>
                        </span>
                        <strong class="font-mono text-amber-200"><?= $isolatedCount ?> پت</strong>
                    </div>
                </div>
            </div>

            <!-- Gauge 3: Health Hotline & Emergency Support -->
            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 flex flex-col justify-between text-xs space-y-1.5">
                <span class="text-emerald-100 font-bold">پشتیبانی پزشکی و اورژانس:</span>
                <p class="text-xs font-black text-teal-300 leading-tight">
                    <?= $affiliatedOrg ? htmlspecialchars($affiliatedOrg['name']) : 'پشتیبانی اورژانس ۲۴ ساعته آسنا' ?>
                </p>
                <div class="text-[11px] text-emerald-200/80 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">verified_user</span>
                    <span>پوشش بیمه مسئولیت مهد</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI SUMMARY CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">pets</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">پت‌های در حال بازی مهد</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= $socialCount ?> مهمان</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">security</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">زون ایزوله (پیت‌بول/انفرادی)</p>
                <h3 class="text-xl font-black text-amber-700 mt-0.5"><?= $isolatedCount ?> پت</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">meeting_room</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">ظرفیت آزاد پذیرش فوری</p>
                <h3 class="text-xl font-black text-teal-700 mt-0.5"><?= $availableSlots ?> جایگاه</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">کارکرد مهد امروز</p>
                <h3 class="text-lg font-black text-sky-700 mt-0.5"><?= number_format($todayRevenue) ?> ت</h3>
            </div>
        </div>
    </div>

    <!-- MAIN ROSTER: KINDERGARTEN ACTIVE RESIDENTS (پت‌های مقیم در حال بازی و مراقبت) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">sports_esports</span>
                    <span>فهرست زنده پت‌های حاضر در مهد و پانسیون</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">مشاهده ساعت ورود، ساعت ترخیص، مدیریت آزادی مدت اقامت (تمدید ساعت یا ترخیص زودهنگام)</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-slate-950 flex items-center gap-1.5 transition-all shadow-xs">
                    <span class="material-symbols-outlined text-sm">person_add</span>
                    <span>+ پذیرش پت جدید</span>
                </button>
            </div>
        </div>

        <?php if (empty($boarders)): ?>
            <div class="p-16 text-center text-slate-400 space-y-3">
                <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-3xl">
                    <span class="material-symbols-outlined text-4xl">toys</span>
                </div>
                <h4 class="text-sm font-black text-slate-700">هم‌اکنون هیچ پتی در مهد پذیرش نشده است</h4>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    سقف مجاز همزمان مهد شما <?= $totalConcurrentCapacity ?> پت است. با استفاده از دکمه «پذیرش پت جدید» می‌توانید ورود ساعتی مهمانان را ثبت فرمایید.
                </p>
                <button type="button" onclick="document.getElementById('checkin-modal').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-all">
                    ثبت اولین پذیرش ساعتی
                </button>
            </div>
        <?php else: ?>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($boarders as $b): 
                    $isIso = !empty($b['is_isolated_zone']);
                    $cardBorder = $isIso ? 'border-amber-300 bg-amber-50/20' : 'border-emerald-200 bg-emerald-50/15';
                    $zoneBadge = $isIso 
                        ? 'bg-amber-100 text-amber-900 border border-amber-300' 
                        : 'bg-emerald-100 text-emerald-900 border border-emerald-300';
                    $zoneIcon = $isIso ? 'security' : 'pets';
                    $zoneLabel = $isIso ? '🚨 زون ایزوله امنیتی (پیت‌بول/انفرادی)' : 'تالار بازی مشترک مهد';

                    // Compute elapsed / remaining time
                    $inDate = $b['check_in_date'] ?: date('Y-m-d');
                    $inTime = $b['check_in_time'] ?: '09:00';
                    $expTime = $b['expected_check_out_time'] ?: '13:00';
                    $expTimestamp = strtotime("{$inDate} {$expTime}");
                    $nowTimestamp = time();
                    $remainingSecs = $expTimestamp - $nowTimestamp;
                    $isExpired = ($remainingSecs <= 0);
                    $remHours = floor(abs($remainingSecs) / 3600);
                    $remMins = floor((abs($remainingSecs) % 3600) / 60);
                ?>
                <div class="p-5 rounded-3xl border <?= $cardBorder ?> shadow-xs flex flex-col justify-between space-y-4 transition-all hover:shadow-md">
                    
                    <!-- Pet Top Profile & Zone Badge -->
                    <div class="space-y-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-base font-black text-slate-900"><?= htmlspecialchars($b['pet_name']) ?></h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <?= htmlspecialchars($b['pet_species']) ?> • <?= htmlspecialchars($b['pet_breed'] ?: 'نژاد نامشخص') ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black <?= $zoneBadge ?>">
                                <span class="material-symbols-outlined text-xs"><?= $zoneIcon ?></span>
                                <span><?= $zoneLabel ?></span>
                            </span>
                        </div>

                        <?php if ($isIso && !empty($b['isolation_reason'])): ?>
                            <div class="p-2 rounded-xl bg-amber-100/70 text-[11px] font-bold text-amber-900 border border-amber-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-xs">warning</span>
                                <span>علت ایزوله: <?= htmlspecialchars($b['isolation_reason']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stay Timing & Live Remaining Status -->
                    <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-bold">ساعت ورود و ترخیص:</span>
                            <span class="font-mono font-bold text-slate-800">
                                <?= htmlspecialchars($inTime) ?> تا <?= htmlspecialchars($expTime) ?> (<?= (int)$b['hours_count'] ?> ساعت)
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
                            <span class="text-slate-500 font-bold">وضعیت زمانی:</span>
                            <?php if ($isExpired): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">alarm_on</span>
                                    <span>ساعت ترخیص فرا رسیده (+<?= $remHours ?>h)</span>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                    <span><?= $remHours ?> ساعت و <?= $remMins ?> دقیقه باقیمانده</span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-600">
                            <span>سرپرست: <strong class="text-slate-800"><?= htmlspecialchars($b['customer_name'] ?: 'کاربر') ?></strong></span>
                            <span class="font-mono text-[11px]" dir="ltr"><?= htmlspecialchars($b['customer_phone'] ?? '—') ?></span>
                        </div>
                    </div>

                    <!-- Activity Notes / Mood -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-500">احوال و گزارش بازی:</span>
                            <button type="button" onclick="openActivityModal(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['play_activity_notes'] ?? '')) ?>')" class="text-emerald-700 hover:underline font-bold text-[10px]">
                                ✏️ ویرایش گزارش
                            </button>
                        </div>
                        <p class="text-xs text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200/80 leading-relaxed">
                            <?= htmlspecialchars($b['play_activity_notes'] ?: 'پت در حال تعامل و بازی در محوطه مهد است.') ?>
                        </p>
                    </div>

                    <!-- Action Buttons: Early Checkout & Stay Extension -->
                    <div class="pt-3 border-t border-slate-200/60 grid grid-cols-2 gap-2">
                        <!-- Early Pickup / Checkout Button -->
                        <form method="POST" action="index.php" class="m-0" onsubmit="return confirm('آیا سرپرست برای تحویل پت مراجعه نموده است؟ ترخیص زودهنگام بلافاصله یک جایگاه به ظرفیت مهد اضافه خواهد کرد.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="check_out_pet">
                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="w-full py-2.5 px-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1 transition-all shadow-xs" title="تحویل پت به سرپرست و آزادسازی فوری ظرفیت">
                                <span class="material-symbols-outlined text-sm">logout</span>
                                <span>ترخیص و تحویل</span>
                            </button>
                        </form>

                        <!-- Extend Stay Hours Button -->
                        <button type="button" onclick="openExtendModal(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['pet_name'])) ?>', <?= (int)$b['hours_count'] ?>, '<?= htmlspecialchars($expTime) ?>')" class="w-full py-2.5 px-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs flex items-center justify-center gap-1 transition-all shadow-xs" title="تمدید ساعت اقامت پت در مهد">
                            <span class="material-symbols-outlined text-sm">more_time</span>
                            <span>تمدید اقامت</span>
                        </button>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PET OWNER TIME RESERVATION PREVIEW CALCULATOR (راهنمای رزرو سرپرستان) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-lg border border-indigo-500/20 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/10 pb-4">
            <div>
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400">calculate</span>
                    <span>ماشین‌حساب شفاف رزرو زمانی مهد پت (ساعتی)</span>
                </h3>
                <p class="text-xs text-slate-300 mt-0.5">آزادی کامل سرپرستان در انتخاب ساعات حضور، پیش‌بینی هزینه و ترخیص زودهنگام یا تمدید اقامت</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                تعرفه ساعتی پایه: ۶۰,۰۰۰ تومان
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 space-y-1">
                <span class="text-slate-400">۲ ساعت بازی و نگهداری:</span>
                <p class="text-base font-black text-amber-300">۱۲۰,۰۰۰ تومان</p>
                <span class="text-[10px] text-slate-400">مناسب خرید و کارهای اداری سرپرست</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 space-y-1">
                <span class="text-slate-400">۴ ساعت مهد نیم‌روز:</span>
                <p class="text-base font-black text-amber-300">۲۴۰,۰۰۰ تومان</p>
                <span class="text-[10px] text-slate-400">همراه با ۱ وعده میان‌وعده و بازی گروهی</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 space-y-1">
                <span class="text-slate-400">۸ ساعت مهد تمام‌روز:</span>
                <p class="text-base font-black text-amber-300">۴۵۰,۰۰۰ تومان</p>
                <span class="text-[10px] text-slate-400">تخفیف ویژه روز کامل کاری سرپرست</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 space-y-1">
                <span class="text-slate-400">زون ایزوله پیت‌بول/پرخاشگر:</span>
                <p class="text-base font-black text-rose-300">ساعتی ۸۵,۰۰۰ تومان</p>
                <span class="text-[10px] text-slate-400">مراقبت اختصاصی ۱ به ۱ بدون ریسک</span>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Quick Hourly Check-in Pet -->
<div id="checkin-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-xl rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">person_add</span>
                <span>پذیرش مهمان جدید در مهد (Check-in Form)</span>
            </h3>
            <button type="button" onclick="document.getElementById('checkin-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check_in_pet">

            <!-- Care Mode Selector -->
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                <label class="block text-xs font-bold text-slate-700">نوع خدمت اقامت و نگهداری:</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-emerald-300 bg-white cursor-pointer">
                        <input type="radio" name="care_mode" value="hourly" checked onchange="toggleCareModeInputs(this.value)">
                        <span class="text-xs font-black text-slate-800">مهد ساعتی (Daycare Hourly)</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 bg-white cursor-pointer">
                        <input type="radio" name="care_mode" value="overnight" onchange="toggleCareModeInputs(this.value)">
                        <span class="text-xs font-black text-slate-800">اقامت شبانه / چندروزه (Overnight)</span>
                    </label>
                </div>
            </div>

            <!-- Pet Basic Info -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام پت *</label>
                    <input type="text" name="pet_name" required placeholder="مثال: لوسی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">گونه حیوان</label>
                    <select name="pet_species" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-700">
                        <option value="سگ">سگ</option>
                        <option value="گربه">گربه</option>
                        <option value="پرنده">پرنده</option>
                        <option value="خرگوش">خرگوش</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نژاد پت</label>
                    <input type="text" name="pet_breed" placeholder="مثال: پیت‌بول، تریر، گلدن" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <!-- Safety Isolation Screening for Aggressive Breeds (Pitbulls, etc.) -->
            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 space-y-2">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_isolated_zone" value="1" id="is-isolated-check" onchange="document.getElementById('iso-reason-wrap').classList.toggle('hidden', !this.checked)" class="mt-0.5 w-4 h-4 text-amber-600 rounded">
                    <div>
                        <span class="text-xs font-black text-amber-900 block">🚨 زون ایزوله امنیتی و نگهداری انفرادی (پیت‌بول یا نژادهای پرخاشگر)</span>
                        <p class="text-[11px] text-amber-700 mt-0.5">در صورت علامت زدن، پت به جای تالار بازی مشترک، در باکس و محوطه اختصاصی ایزوله نگهداری می‌شود تا خطری متوجه سایر حیوانات نباشد.</p>
                    </div>
                </label>
                <div id="iso-reason-wrap" class="hidden pt-2 border-t border-amber-200">
                    <label class="block text-[11px] font-bold text-amber-800 mb-1">علت و ملاحظات ایزوله انفرادی:</label>
                    <input type="text" name="isolation_reason" placeholder="مثال: پرخاشگری نژادی پیت‌بول، استرس شدید یا عدم عقیم‌سازی" class="w-full h-10 px-3 rounded-xl border border-amber-300 text-xs bg-white">
                </div>
            </div>

            <!-- Customer Guardian Info -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام سرپرست پت</label>
                    <input type="text" name="customer_name" placeholder="نام و نام خانوادگی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">شماره همراه سرپرست *</label>
                    <input type="tel" name="customer_phone" required placeholder="09120000000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
                </div>
            </div>

            <!-- Hourly Specific Timing -->
            <div id="hourly-inputs" class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ساعت ورود</label>
                    <input type="time" name="check_in_time" value="<?= date('H:i') ?>" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">مدت زمان اقامت (ساعت)</label>
                    <input type="number" name="hours_count" min="1" max="14" value="3" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نرخ هر ساعت (تومان)</label>
                    <input type="text" name="hourly_rate" value="60,000" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
                </div>
            </div>

            <!-- Overnight Specific Timing -->
            <div id="overnight-inputs" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ ورود</label>
                    <input type="date" name="check_in_date" value="<?= date('Y-m-d') ?>" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ خروج</label>
                    <input type="date" name="check_out_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
            </div>

            <!-- Notes -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">وسایل امانی همراه پت</label>
                    <input type="text" name="belongings_list" placeholder="قلاده، تشک، اسباب‌بازی..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تغذیه و دارو</label>
                    <input type="text" name="special_diet_notes" placeholder="دستور غذا، ساعت مصرف دارو..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-emerald-500 hover:bg-emerald-600 text-slate-950 shadow-sm transition-all">
                    تأیید پذیرش و ورود پت به مهد
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Extend Stay (تمدید مدت نگهداری) -->
<div id="extend-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">more_time</span>
                <span>تمدید ساعت اقامت پت در مهد</span>
            </h3>
            <button type="button" onclick="document.getElementById('extend-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="extend_stay">
            <input type="hidden" name="booking_id" id="extend-booking-id" value="">

            <p class="text-xs text-slate-600">
                در صورت تمایل سرپرست به ماندگاری بیشتر پت «<strong id="extend-pet-name" class="text-slate-900"></strong>»، تعداد ساعات تمدید را مشخص فرمایید:
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">تعداد ساعت تمدید اقامت:</label>
                <select name="extra_hours" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-800">
                    <option value="1">+ ۱ ساعت اضافه</option>
                    <option value="2">+ ۲ ساعت اضافه</option>
                    <option value="3">+ ۳ ساعت اضافه</option>
                    <option value="4">+ ۴ ساعت اضافه (نیم‌روز)</option>
                    <option value="6">+ ۶ ساعت اضافه</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('extend-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm transition-all">
                    تأیید و اعمال تمدید
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Update Activity Notes -->
<div id="activity-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">edit_note</span>
                <span>ثبت گزارش بازی و وضعیت پت</span>
            </h3>
            <button type="button" onclick="document.getElementById('activity-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_activity_note">
            <input type="hidden" name="booking_id" id="activity-booking-id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">یادداشت احوال و بازی پت:</label>
                <textarea name="play_activity_notes" id="activity-text" rows="3" class="w-full p-3 rounded-xl border border-slate-300 text-xs" placeholder="مثال: غذا را کامل خورد، در حیاط بازی کرد، با مربی تعامل خوبی داشت."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('activity-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                    ذخیره گزارش
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Adjust Concurrent Capacity Limit -->
<div id="capacity-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">tune</span>
                <span>تنظیم سقف پذیرش همزمان مهد</span>
            </h3>
            <button type="button" onclick="document.getElementById('capacity-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_capacity_limit">

            <p class="text-xs text-slate-600 leading-relaxed">
                سقف همزمان، حداکثر تعداد سگ‌ها و حیواناتی است که در ساعات اوج فعالیت اجازه دارند همزمان در تالار بازی حضور داشته باشند.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">سقف ظرفیت همزمان جدید (تعداد پت):</label>
                <input type="number" name="concurrent_capacity" min="5" max="50" value="<?= $totalConcurrentCapacity ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono font-bold">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('capacity-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                    ذخیره سقف جدید
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCareModeInputs(mode) {
    const hBox = document.getElementById('hourly-inputs');
    const oBox = document.getElementById('overnight-inputs');
    if (mode === 'hourly') {
        hBox.classList.remove('hidden');
        oBox.classList.add('hidden');
    } else {
        hBox.classList.add('hidden');
        oBox.classList.remove('hidden');
    }
}

function openExtendModal(bookingId, petName, currentHours, currentExpTime) {
    document.getElementById('extend-booking-id').value = bookingId;
    document.getElementById('extend-pet-name').textContent = petName;
    document.getElementById('extend-modal').classList.remove('hidden');
}

function openActivityModal(bookingId, existingNote) {
    document.getElementById('activity-booking-id').value = bookingId;
    document.getElementById('activity-text').value = existingNote;
    document.getElementById('activity-modal').classList.remove('hidden');
}
</script>

<?php require_once 'includes/pansion_footer.php'; ?>
