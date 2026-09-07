<?php
/**
 * ASENA Enterprise - Landing Page Fast Booking Endpoint
 * Handles asynchronous online appointment reservation directly from the landing page.
 * Includes phone validation, user auto-provisioning / lookup, double-booking prevention,
 * transaction locking, and Persian SMS alert dispatch via Melipayamak.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Feature.php';
require_once __DIR__ . '/../includes/functions.php';

if (!Feature::has('clinic_booking')) {
    echo json_encode(['status' => 'error', 'message' => 'سامانه نوبت‌دهی در حال حاضر غیرفعال است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 1. Extract & Sanitize Inputs ──────────────────────────────────────────────
$doctorId     = (int)($_POST['doctor_id'] ?? 0);
$serviceType  = trim($_POST['service_type'] ?? 'consultation');
$date         = trim($_POST['appointment_date'] ?? '');
$time         = trim($_POST['appointment_time'] ?? '');
$petType      = trim($_POST['pet_type'] ?? 'سگ');
$petName      = trim($_POST['pet_name'] ?? 'پت خانگی');
$phone        = trim($_POST['owner_phone'] ?? '');
$ownerName    = trim($_POST['owner_name'] ?? 'سرپرست گرامی');
$visitPurpose = trim($_POST['visit_purpose'] ?? 'معاینه و چکاپ دوره‌ای');

// Validate phone (Iran mobile format)
$phone = preg_replace('/[^\d]/', '', $phone);
if (strlen($phone) === 10 && substr($phone, 0, 1) === '9') {
    $phone = '0' . $phone;
}
if (!preg_match('/^09\d{9}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'شماره موبایل نامعتبر است. لطفاً شماره‌ای مانند ۰۹۱۲۳۴۵۶۷۸۹ وارد فرمایید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate doctor
if ($doctorId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'لطفاً ابتدا یک پزشک یا متخصص را انتخاب نمایید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate date
if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'لطفاً تاریخ نوبت را از تقویم انتخاب کنید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strtotime($date) < strtotime('today')) {
    echo json_encode(['status' => 'error', 'message' => 'امکان رزرو نوبت در تاریخ‌های گذشته وجود ندارد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate time
if (empty($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    echo json_encode(['status' => 'error', 'message' => 'لطفاً ساعت نوبت را انتخاب فرمایید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ── 2. Validate Doctor Exists & Calculate Fees ─────────────────────────────
    $docStmt = $pdo->prepare("
        SELECT d.id, d.name, d.specialty, d.price, d.provider_type, d.organization_id, d.clinic_name,
               o.name as org_name, o.address as org_address
        FROM doctors d
        LEFT JOIN organizations o ON d.organization_id = o.id
        WHERE d.id = ?
    ");
    $docStmt->execute([$doctorId]);
    $doctor = $docStmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        echo json_encode(['status' => 'error', 'message' => 'پزشک انتخابی در سامانه یافت نشد.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $doctorPrice = (int)$doctor['price'];
    $orgId = (int)($doctor['organization_id'] ?: 1);
    $clinicName = $doctor['clinic_name'] ?: ($doctor['org_name'] ?: 'مرکز درمانی آسنا');

    // Commission calculations (5% platform fee)
    $commissionRate = 0.05;
    $commissionAmount = (int)round($doctorPrice * $commissionRate);
    $netAmount = $doctorPrice - $commissionAmount;

    // ── 3. Resolve or Create User ─────────────────────────────────────────────
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $uStmt = $pdo->prepare("SELECT id, name FROM users WHERE phone = ? LIMIT 1");
        $uStmt->execute([$phone]);
        $existingUser = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingUser) {
            $userId = (int)$existingUser['id'];
        } else {
            // Register guest user
            $tempPass = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
            $regStmt = $pdo->prepare("
                INSERT INTO users (name, phone, password, role, created_at)
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $regStmt->execute([$ownerName, $phone, $tempPass]);
            $userId = (int)$pdo->lastInsertId();
        }
        // Log user into session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $ownerName;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_role'] = 'user';
    }

    // ── 4. Begin DB Transaction & Guard against Double-Booking ────────────────
    $pdo->beginTransaction();

    // Check existing appointments for this slot
    $checkStmt = $pdo->prepare("
        SELECT id FROM appointments
        WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
        AND status NOT IN ('cancelled')
        LIMIT 1
        FOR UPDATE
    ");
    $checkStmt->execute([$doctorId, $date, $time]);
    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'این ساعت نوبت قبلاً توسط شخص دیگری رزرو شده است. لطفاً ساعت دیگری را انتخاب نمایید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check doctor blocked slots
    $blkStmt = $pdo->prepare("
        SELECT id FROM doctor_blocked_slots
        WHERE doctor_id = ? AND block_date = ?
        AND ((start_time = '00:00' AND end_time = '23:59') OR (? BETWEEN start_time AND end_time) OR start_time = ?)
        LIMIT 1
    ");
    $blkStmt->execute([$doctorId, $date, $time, $time]);
    if ($blkStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'این زمان توسط پزشک جهت استراحت یا عمل‌های جراحی رزرو شده است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Generate readable Persian Tracking Code (e.g. ASN-72941)
    $trackingCode = 'ASN-' . rand(10000, 99999);

    // ── 5. Insert Appointment ─────────────────────────────────────────────────
    $insStmt = $pdo->prepare("
        INSERT INTO appointments (
            tracking_code, user_id, doctor_id, organization_id, appointment_date, appointment_time,
            pet_type, pet_name, visit_purpose, fee, commission_amount,
            net_amount, service_type, settlement_status, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, 'held_in_escrow', 'confirmed', NOW()
        )
    ");
    $insStmt->execute([
        $trackingCode, $userId, $doctorId, $orgId, $date, $time,
        $petType, $petName, $visitPurpose, $doctorPrice, $commissionAmount,
        $netAmount, $serviceType
    ]);
    $appointmentId = (int)$pdo->lastInsertId();

    $pdo->commit();

    // ── 6. Dispatch Melipayamak Persian Confirmation SMS ──────────────────────
    $smsSent = false;
    $smsMessage = "نوبت ویزیت شما در سامانه آسنا با موفقیت ثبت شد.\nپزشک: {$doctor['name']}\nتاریخ: {$date} ساعت: {$time}\nکد رهگیری: {$trackingCode}\nمرکز: {$clinicName}";

    try {
        if (class_exists('SmsService')) {
            $smsSent = SmsService::send($phone, $smsMessage);
        } else {
            // Log to local SMS log file
            $logFile = __DIR__ . '/../../logs/sms.log';
            $logEntry = date('Y-m-d H:i:s') . " | APPOINTMENT_RESERVATION | Phone: $phone | Code: $trackingCode | Msg: " . str_replace("\n", " ", $smsMessage) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            $smsSent = true;
        }
    } catch (Exception $smsErr) {
        error_log("SMS dispatch error: " . $smsErr->getMessage());
    }

    // Convert date to Shamsi for Persian UI display
    $shamsiDate = $date;
    if (function_exists('jdate')) {
        $ts = strtotime($date);
        $shamsiDate = jdate('l j F Y', $ts);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'نوبت شما با موفقیت در سامانه ثبت و رزرو گردید.',
        'appointment' => [
            'id' => $appointmentId,
            'tracking_code' => $trackingCode,
            'doctor_name' => $doctor['name'],
            'specialty' => $doctor['specialty'],
            'clinic_name' => $clinicName,
            'date_gregorian' => $date,
            'date_shamsi' => $shamsiDate,
            'time' => $time,
            'pet_type' => $petType,
            'pet_name' => $petName,
            'fee' => $doctorPrice,
            'fee_formatted' => number_format($doctorPrice) . ' تومان',
            'phone' => $phone,
            'sms_sent' => $smsSent
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Landing booking error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'خطای فنی در ثبت نوبت. لطفاً لحظاتی دیگر تلاش فرمایید.'], JSON_UNESCAPED_UNICODE);
}
