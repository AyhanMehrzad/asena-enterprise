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

    // 2. Check-in Pet into Room
    elseif ($action === 'check_in_pet') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $petName = trim($_POST['pet_name'] ?? '');
        $petSpecies = trim($_POST['pet_species'] ?? 'سگ');
        $petBreed = trim($_POST['pet_breed'] ?? '');
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = preg_replace('/[^0-9]/', '', trim($_POST['customer_phone'] ?? ''));
        $checkInDate = trim($_POST['check_in_date'] ?? date('Y-m-d'));
        $checkOutDate = trim($_POST['check_out_date'] ?? date('Y-m-d', strtotime('+3 days')));
        $dietNotes = trim($_POST['special_diet_notes'] ?? '');
        $medNotes = trim($_POST['medication_notes'] ?? '');
        $belongings = trim($_POST['belongings_list'] ?? '');
        $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');

        if ($roomId <= 0 || empty($petName) || empty($custPhone) || strlen($custPhone) !== 11) {
            $error = 'انتخاب اتاق، نام پت و شماره همراه ۱۱ رقمی سرپرست الزامی است.';
        } else {
            // Fetch room
            $rStmt = $pdo->prepare("SELECT * FROM pansion_rooms WHERE id = ?");
            $rStmt->execute([$roomId]);
            $room = $rStmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                $error = 'اتاق انتخابی یافت نشد.';
            } else {
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

                $nights = max(1, (int)round((strtotime($checkOutDate) - strtotime($checkInDate)) / 86400));
                $totalPrice = $nights * (int)$room['price_per_night'];
                $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;

                $insB = $pdo->prepare("
                    INSERT INTO pansion_bookings 
                    (room_id, organization_id, pansion_user_id, customer_user_id, pet_name, pet_species, pet_breed, check_in_date, check_out_date, nights_count, total_price, status, special_diet_notes, medication_notes, belongings_list, emergency_contact_phone, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'checked_in', ?, ?, ?, ?, {$nowSql})
                ");
                if ($insB->execute([$roomId, $orgId, $pansionUserId, $custId, $petName, $petSpecies, $petBreed, $checkInDate, $checkOutDate, $nights, $totalPrice, $dietNotes, $medNotes, $belongings, $emergencyPhone])) {
                    // Update room status to occupied
                    $pdo->prepare("UPDATE pansion_rooms SET status = 'occupied' WHERE id = ?")->execute([$roomId]);
                    $success = "پذیرش «{$petName}» با موفقیت در اتاق «{$room['room_number']}» ثبت گردید و اتاق به وضعیت اشغال تغییر یافت.";
                } else {
                    $error = 'خطا در ثبت پذیرش در دیتابیس.';
                }
            }
        }
    }

    // 3. Check-out Pet
    elseif ($action === 'check_out_pet') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);

        if ($bookingId > 0) {
            $updB = $pdo->prepare("UPDATE pansion_bookings SET status = 'checked_out' WHERE id = ? AND pansion_user_id = ?");
            $updB->execute([$bookingId, $pansionUserId]);

            if ($roomId > 0) {
                // Room transitions to cleaning status
                $pdo->prepare("UPDATE pansion_rooms SET status = 'cleaning' WHERE id = ?")->execute([$roomId]);
            }
            $success = "پت با موفقیت ترخیص گردید. اتاق مربوطه جهت آماده‌سازی به وضعیت «در حال ضدعفونی» منتقل شد.";
        }
    }

    // 4. Add Room / Suite
    elseif ($action === 'add_room') {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomTitle = trim($_POST['room_title'] ?? '');
        $roomType = trim($_POST['room_type'] ?? 'standard_kennel');
        $capacity = (int)($_POST['capacity'] ?? 1);
        $priceNight = (int)preg_replace('/[^0-9]/', '', $_POST['price_per_night'] ?? '0');
        $desc = trim($_POST['description'] ?? '');

        if (empty($roomNumber) || empty($roomTitle) || $priceNight <= 0) {
            $error = 'شماره اتاق، عنوان سوئیت و تعرفه هر شب الزامی است.';
        } else {
            $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;
            $insR = $pdo->prepare("
                INSERT INTO pansion_rooms 
                (organization_id, pansion_user_id, room_number, room_title, room_type, capacity, price_per_night, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', {$nowSql})
            ");
            if ($insR->execute([$orgId, $pansionUserId, $roomNumber, $roomTitle, $roomType, $capacity, $priceNight, $desc])) {
                $success = "سوئیت جدید «{$roomTitle} ({$roomNumber})» با موفقیت افزوده شد.";
            } else {
                $error = 'خطا در افزودن اتاق.';
            }
        }
    }

    // 5. Update Room Status (e.g. Finish cleaning -> Available)
    elseif ($action === 'update_room_status') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'available');
        if ($roomId > 0 && in_array($newStatus, ['available', 'occupied', 'cleaning', 'maintenance'])) {
            $pdo->prepare("UPDATE pansion_rooms SET status = ? WHERE id = ? AND pansion_user_id = ?")->execute([$newStatus, $roomId, $pansionUserId]);
            $success = "وضعیت اتاق با موفقیت به‌روزرسانی شد.";
        }
    }
}

// Fetch Pending Organization Invitations
$pendingOrgInvitations = [];
try {
    $pendingOrgInvitations = App::organization()->getUserInvitations($pansionUserId, 'pending');
} catch (Throwable $e) {}

// Fetch Pansion Rooms
$rooms = [];
try {
    $rQuery = $pdo->prepare("SELECT * FROM pansion_rooms WHERE pansion_user_id = ? ORDER BY id ASC");
    $rQuery->execute([$pansionUserId]);
    $rooms = $rQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Seed default sample suites if empty
if (empty($rooms)) {
    $defaultRooms = [
        ['room_number' => 'VIP-101', 'room_title' => 'سوئیت سلطنتی VIP گلدن', 'room_type' => 'vip_suite', 'capacity' => 2, 'price_per_night' => 850000, 'description' => 'مجهز به دوربین مداربسته اختصاصی ۲۴ ساعته، مبل پت، تهویه مطبوع مستقل و دسترسی به حیاط بازی'],
        ['room_number' => 'STD-102', 'room_title' => 'اتاق استاندارد نگهداری سگ', 'room_type' => 'standard_kennel', 'capacity' => 1, 'price_per_night' => 450000, 'description' => 'محیط آرام، کف‌پوش ضدآب و آنتی‌باکتریال، گرمایش از کف و ۳ وعده پیاده‌روی روزانه'],
        ['room_number' => 'CAT-201', 'room_title' => 'کاندوی اختصاصی گربه پرشین', 'room_type' => 'cat_room', 'capacity' => 1, 'price_per_night' => 380000, 'description' => 'درخت گربه ۳ طبقه، اسکرچر چوبی، ظرف خاک اتوماتیک و موسیقی آرامش‌بخش گربه'],
        ['room_number' => 'ISO-301', 'room_title' => 'باکس ایزوله و بستری درمانی', 'room_type' => 'hospital_box', 'capacity' => 1, 'price_per_night' => 550000, 'description' => 'تحت نظارت مستقیم دامپزشک پشتیبان، اکسیژن‌تراپی و مراقبت‌های ویژه بعد از جراحی']
    ];
    $orgId = !empty($currentUser['organization_id']) ? (int)$currentUser['organization_id'] : null;
    foreach ($defaultRooms as $dr) {
        try {
            $insDR = $pdo->prepare("INSERT INTO pansion_rooms (organization_id, pansion_user_id, room_number, room_title, room_type, capacity, price_per_night, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', {$nowSql})");
            $insDR->execute([$orgId, $pansionUserId, $dr['room_number'], $dr['room_title'], $dr['room_type'], $dr['capacity'], $dr['price_per_night'], $dr['description']]);
        } catch (Throwable $e) {}
    }
    try {
        $rQuery = $pdo->prepare("SELECT * FROM pansion_rooms WHERE pansion_user_id = ? ORDER BY id ASC");
        $rQuery->execute([$pansionUserId]);
        $rooms = $rQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

// Fetch Active Boarders (Current Guests)
$boarders = [];
try {
    $bQuery = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_title, r.room_type,
               u.name as customer_name, u.phone as customer_phone
        FROM pansion_bookings b
        JOIN pansion_rooms r ON b.room_id = r.id
        LEFT JOIN users u ON b.customer_user_id = u.id
        WHERE b.pansion_user_id = ? AND b.status = 'checked_in'
        ORDER BY b.id DESC
    ");
    $bQuery->execute([$pansionUserId]);
    $boarders = $bQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Compute Stats
$totalRooms = count($rooms);
$occupiedRooms = count(array_filter($rooms, fn($r) => $r['status'] === 'occupied'));
$cleaningRooms = count(array_filter($rooms, fn($r) => $r['status'] === 'cleaning'));
$availableRooms = count(array_filter($rooms, fn($r) => $r['status'] === 'available'));
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
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
                <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950 via-slate-900 to-indigo-950 text-white border border-emerald-500/30 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
                    <div class="flex items-start gap-4 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center shrink-0 shadow-inner text-emerald-300">
                            <span class="material-symbols-outlined text-3xl">local_hospital</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-400 text-slate-950 animate-pulse">دعوت‌نامه رسمی همکاری کلینیک</span>
                                <h3 class="font-black text-sm text-white">مرکز درمانی «<?= htmlspecialchars($pInv['org_name']) ?>»</h3>
                                <span class="text-xs text-emerald-200">(<?= htmlspecialchars($pInv['org_city'] ?? 'تهران') ?>)</span>
                            </div>
                            <p class="text-xs text-slate-300">
                                نقش پیشنهادی: <strong class="text-emerald-300"><?= htmlspecialchars($pInv['job_title'] ?: 'واحد پانسیون و بستری همکار') ?></strong>
                                <?php if (!empty($pInv['proposed_shifts'])): ?>
                                    | هماهنگی: <span class="text-emerald-200"><?= htmlspecialchars($pInv['proposed_shifts']) ?></span>
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
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 flex items-center gap-1.5 transition-all">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                <span>تأیید و اتصال پانسیون</span>
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

    <!-- PANSION LIVE COCKPIT HERO (Rule 11) -->
    <div class="cockpit-pansion text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-emerald-500/30 relative overflow-hidden space-y-5" style="background-color: #06241a !important; background-image: linear-gradient(135deg, #06241a 0%, #093526 50%, #041a12 100%) !important; color: #ffffff !important;">
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-400 flex items-center justify-center font-black shadow-inner">
                    <span class="material-symbols-outlined text-2xl">holiday_village</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>پذیرش و مراقبت شبانه‌روزی ۲۴ ساعته</span>
                        </span>
                        <span class="text-xs text-emerald-200 font-bold">| نرخ اشغال هتل: <?= $occupancyRate ?>٪</span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-white mt-1"><?= htmlspecialchars($pansionName) ?></h2>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs shadow-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">person_add</span>
                    <span>پذیرش مهمان جدید (Check-in)</span>
                </button>
                <button type="button" onclick="document.getElementById('add-room-modal').classList.remove('hidden')" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">add_home</span>
                    <span>تعریف اتاق جدید</span>
                </button>
            </div>
        </div>

        <!-- Occupancy Bar & Health Guard Hotline -->
        <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-emerald-100 font-bold">ظرفیت تکمیل شده:</span>
                    <strong class="text-emerald-300 font-mono"><?= $occupiedRooms ?> از <?= $totalRooms ?> سوئیت</strong>
                </div>
                <div class="w-full bg-white/10 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-400 to-teal-400 h-full rounded-full transition-all duration-500" style="width: <?= $occupancyRate ?>%"></div>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-between text-xs">
                <div>
                    <p class="text-emerald-100 font-bold">اتاق‌های آماده رزرو فوری:</p>
                    <h4 class="text-lg font-black text-white mt-0.5"><?= $availableRooms ?> سوئیت خالی</h4>
                </div>
                <span class="material-symbols-outlined text-3xl text-emerald-400">meeting_room</span>
            </div>

            <div class="p-4 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-between text-xs">
                <div>
                    <p class="text-emerald-100 font-bold">پزشک و کلینیک پشتیبان:</p>
                    <h4 class="text-xs font-black text-teal-300 mt-0.5"><?= $affiliatedOrg ? htmlspecialchars($affiliatedOrg['name']) : 'پشتیبانی اورژانس سراسری آسنا' ?></h4>
                </div>
                <span class="material-symbols-outlined text-3xl text-teal-400">medical_services</span>
            </div>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">hotel</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">کل سوئیت‌ها و اتاق‌ها</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= $totalRooms ?> اتاق</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">pets</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">پت‌های مقیم (پذیرش فعال)</p>
                <h3 class="text-xl font-black text-amber-700 mt-0.5"><?= count($boarders) ?> مهمان</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">sanitizer</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">در حال ضدعفونی و نظافت</p>
                <h3 class="text-xl font-black text-purple-700 mt-0.5"><?= $cleaningRooms ?> اتاق</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">کیف‌پول پانسیون</p>
                <h3 class="text-lg font-black text-sky-700 mt-0.5"><?= number_format($currentUser['wallet_balance'] ?? 0) ?> ت</h3>
            </div>
        </div>
    </div>

    <!-- SECTION 1: SUITES & ROOMS INTERACTIVE MATRIX -->
    <div id="rooms-section" class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">meeting_room</span>
                    <span>نقشه زنده سوئیت‌ها و وضعیت اشغال اتاق‌ها</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">مشاهده اتاق‌های خالی، تحت اقامت یا نیازمند نظافت و تغییر وضعیت سریع</p>
            </div>
            <button type="button" onclick="document.getElementById('add-room-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>افزودن اتاق</span>
            </button>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($rooms as $rm): 
                    $st = $rm['status'];
                    $statusMeta = match($st) {
                        'available'   => ['label' => 'آماده پذیرش', 'card_border' => 'border-emerald-200 bg-emerald-50/30', 'badge' => 'bg-emerald-100 text-emerald-800', 'icon' => 'check_circle'],
                        'occupied'    => ['label' => 'مهمان مقیم (اشغال)', 'card_border' => 'border-rose-200 bg-rose-50/30', 'badge' => 'bg-rose-100 text-rose-800', 'icon' => 'pets'],
                        'cleaning'    => ['label' => 'در حال ضدعفونی', 'card_border' => 'border-amber-200 bg-amber-50/30', 'badge' => 'bg-amber-100 text-amber-800', 'icon' => 'sanitizer'],
                        'maintenance' => ['label' => 'تعمیرات و ارتقا', 'card_border' => 'border-slate-200 bg-slate-50', 'badge' => 'bg-slate-100 text-slate-700', 'icon' => 'build'],
                        default       => ['label' => $st, 'card_border' => 'border-slate-200 bg-white', 'badge' => 'bg-slate-100 text-slate-700', 'icon' => 'info']
                    };
                ?>
                <div class="p-4 rounded-2xl border <?= $statusMeta['card_border'] ?> transition-all flex flex-col justify-between space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-mono text-[11px] font-black text-slate-500"><?= htmlspecialchars($rm['room_number']) ?></span>
                            <h4 class="font-black text-xs text-slate-900 mt-0.5"><?= htmlspecialchars($rm['room_title']) ?></h4>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold <?= $statusMeta['badge'] ?>">
                            <span class="material-symbols-outlined text-xs"><?= $statusMeta['icon'] ?></span>
                            <span><?= $statusMeta['label'] ?></span>
                        </span>
                    </div>

                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>تعرفه هر شب:</span>
                            <strong class="font-mono font-bold text-slate-900"><?= number_format($rm['price_per_night']) ?> تومان</strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>ظرفیت:</span>
                            <span class="font-bold text-slate-700"><?= (int)$rm['capacity'] ?> پت</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                        <?php if ($st === 'cleaning'): ?>
                            <form method="POST" action="index.php" class="w-full m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_room_status">
                                <input type="hidden" name="room_id" value="<?= (int)$rm['id'] ?>">
                                <input type="hidden" name="status" value="available">
                                <button type="submit" class="w-full py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center justify-center gap-1 transition-all">
                                    <span class="material-symbols-outlined text-sm">cleaning_services</span>
                                    <span>اتمام ضدعفونی و آزادسازی</span>
                                </button>
                            </form>
                        <?php elseif ($st === 'available'): ?>
                            <span class="text-emerald-700 font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">door_front</span>
                                <span>آماده واگذاری</span>
                            </span>
                        <?php elseif ($st === 'occupied'): ?>
                            <span class="text-rose-700 font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">lock</span>
                                <span>دارای مسافر مقیم</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ACTIVE RESIDENT PETS (BOARDERS) -->
    <div id="boarders-section" class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600">pets</span>
                    <span>مهمانان مقیم هتل (پذیرش فعال هم‌اکنون)</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">ثبت تاریخ ورود و خروج، اطلاعات سرپرست، داروهای تجویزی و وسایل همراه پت</p>
            </div>
            <button type="button" onclick="document.getElementById('checkin-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-sm">person_add</span>
                <span>پذیرش مهمان جدید</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">پت مهمان</th>
                        <th class="p-4">سوئیت مقیم</th>
                        <th class="p-4">بازه اقامت (شب‌ها)</th>
                        <th class="p-4">اطلاعات سرپرست و تماس اضطراری</th>
                        <th class="p-4">دستورات غذایی و دارویی</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($boarders)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">hotel_class</span>
                                هم‌اکنون هیچ مهمان مقیمی در هتل ثبت نشده است. از دکمه «پذیرش مهمان جدید» برای ثبت چک‌این استفاده فرمایید.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($boarders as $b): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4">
                                <div class="font-black text-slate-900 text-sm"><?= htmlspecialchars($b['pet_name']) ?></div>
                                <div class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($b['pet_species']) ?> - <?= htmlspecialchars($b['pet_breed'] ?: 'نژاد معمولی') ?></div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold font-mono">
                                    <?= htmlspecialchars($b['room_number']) ?>
                                </span>
                                <div class="text-[11px] text-slate-500 mt-1"><?= htmlspecialchars($b['room_title']) ?></div>
                            </td>
                            <td class="p-4 font-mono">
                                <div class="font-bold text-slate-800">از <?= htmlspecialchars($b['check_in_date']) ?> تا <?= htmlspecialchars($b['check_out_date']) ?></div>
                                <div class="text-[11px] text-indigo-700 font-bold mt-0.5"><?= (int)$b['nights_count'] ?> شب اقامت</div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($b['customer_name'] ?: 'سرپرست محترم') ?></div>
                                <div class="font-mono text-slate-500 text-[11px]" dir="ltr"><?= htmlspecialchars($b['customer_phone'] ?? '—') ?></div>
                                <?php if (!empty($b['emergency_contact_phone'])): ?>
                                    <div class="text-[10px] text-rose-600 font-mono mt-0.5">اضطراری: <?= htmlspecialchars($b['emergency_contact_phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 max-w-xs">
                                <?php if (!empty($b['special_diet_notes'])): ?>
                                    <div class="text-[11px] text-slate-700">🥗 <?= htmlspecialchars($b['special_diet_notes']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($b['medication_notes'])): ?>
                                    <div class="text-[11px] text-rose-700 font-bold mt-0.5">💊 <?= htmlspecialchars($b['medication_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <form method="POST" action="index.php" class="inline m-0" onsubmit="return confirm('آیا از ترخیص و تسویه حساب این مهمان اطمینان دارید؟ اتاق به وضعیت ضدعفونی منتقل خواهد شد.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="check_out_pet">
                                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                    <input type="hidden" name="room_id" value="<?= (int)$b['room_id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-all shadow-xs">
                                        ترخیص مهمان (Check-out)
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 3: BELONGINGS & DIET INVENTORY TRACKER -->
    <div id="inventory-section" class="bg-white rounded-3xl border border-slate-200 shadow-xs p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">inventory</span>
                    <span>چک‌لیست وسایل امانی و رژیم غذایی مسافران</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">حفظ اموال سرپرستان، ظروف غذا، اسباب‌بازی‌ها و داروهای تحویلی جهت جلوگیری از مفقودی</p>
            </div>
            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-200">
                سیستم امانت‌داری هوشمند
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($boarders as $b): ?>
                <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-xs text-slate-900"><?= htmlspecialchars($b['pet_name']) ?> (اتاق <?= htmlspecialchars($b['room_number']) ?>)</span>
                        <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($b['customer_name']) ?></span>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] font-bold text-slate-400 block mb-0.5">وسایل و ملزومات تحویلی:</span>
                            <p class="text-slate-700"><?= htmlspecialchars($b['belongings_list'] ?: 'تشک اختصاصی، ظرف آب، قلاده کمری') ?></p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                            <span class="text-[10px] font-bold text-slate-400 block mb-0.5">برنامه تغذیه:</span>
                            <p class="text-slate-700"><?= htmlspecialchars($b['special_diet_notes'] ?: 'غذای خشک استاندارد هتل (۲ وعده صبح و شب)') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($boarders)): ?>
                <div class="col-span-3 p-8 text-center text-slate-400 bg-slate-50 rounded-2xl">
                    مهمان مقیمی جهت نمایش وسایل امانی وجود ندارد.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modal: Check-In Pet -->
<div id="checkin-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-xl rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">person_add</span>
                <span>پذیرش مهمان جدید در هتل (Check-in Form)</span>
            </h3>
            <button type="button" onclick="document.getElementById('checkin-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check_in_pet">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">انتخاب اتاق / سوئیت *</label>
                    <select name="room_id" required class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-800">
                        <?php foreach ($rooms as $rm): ?>
                            <?php if ($rm['status'] === 'available'): ?>
                                <option value="<?= (int)$rm['id'] ?>"><?= htmlspecialchars($rm['room_number']) ?> - <?= htmlspecialchars($rm['room_title']) ?> (<?= number_format($rm['price_per_night']) ?> ت/شب)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام پت دلبند *</label>
                    <input type="text" name="pet_name" required placeholder="مثال: لوسی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
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
                    <input type="text" name="pet_breed" placeholder="مثال: گلدن رتریور، هاسکی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام سرپرست پت</label>
                    <input type="text" name="customer_name" placeholder="نام و نام خانوادگی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">شماره همراه سرپرست (ورود به سامانه) *</label>
                    <input type="tel" name="customer_phone" required placeholder="09120000000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ ورود (Check-in)</label>
                    <input type="date" name="check_in_date" value="<?= date('Y-m-d') ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ خروج تخمینی (Check-out)</label>
                    <input type="date" name="check_out_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">وسایل و ملزومات امانی همراه پت</label>
                <input type="text" name="belongings_list" placeholder="مثال: قلاده چرمی قرمز، تشک خواب خاکستری، ظرف غذای استیل، اسباب‌بازی اردک" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دستورات غذایی و حساسیت‌ها</label>
                    <input type="text" name="special_diet_notes" placeholder="برند غذا، تعداد وعده و حساسیت..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">دستورات دارویی (در صورت وجود)</label>
                    <input type="text" name="medication_notes" placeholder="نام دارو، دوز و ساعت مصرف..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">شماره تماس اضطراری جایگزین</label>
                <input type="tel" name="emergency_contact_phone" placeholder="شماره تماس دوم در صورت عدم پاسخگویی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                    تأیید پذیرش و واگذاری اتاق
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Room -->
<div id="add-room-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">add_home</span>
                <span>تعریف اتاق یا سوئیت جدید در هتل</span>
            </h3>
            <button type="button" onclick="document.getElementById('add-room-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="index.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_room">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">شماره / کد اتاق *</label>
                    <input type="text" name="room_number" required placeholder="مثال: VIP-105" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">عنوان سوئیت *</label>
                    <input type="text" name="room_title" required placeholder="مثال: سوئیت گاردن با تراس اختصاصی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نوع اتاق</label>
                    <select name="room_type" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white font-bold text-slate-700">
                        <option value="vip_suite">سوئیت رویال VIP</option>
                        <option value="standard_kennel">اتاق استاندارد نگهداری سگ</option>
                        <option value="cat_room">اتاق و کاندوی اختصاصی گربه</option>
                        <option value="hospital_box">باکس بستری و مراقبت درمانی</option>
                        <option value="daycare_playpen">پلی‌پن دی‌کر روزانه</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ظرفیت پذیرش پت</label>
                    <input type="number" min="1" max="5" name="capacity" value="1" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">تعرفه اقامت هر شب (تومان) *</label>
                <input type="text" name="price_per_night" required placeholder="مثال: 550,000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs font-mono" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">توضیحات و امکانات سوئیت</label>
                <textarea name="description" rows="2" placeholder="امکاناتی چون دوربین اختصاصی، سیستم تهویه، اسباب‌بازی و..." class="w-full p-3 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('add-room-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                    ایجاد و ثبت اتاق
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/pansion_footer.php'; ?>
