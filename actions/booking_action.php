<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'booking.php';
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php');
    exit;
}

csrf_verify();

$doctor_id     = (int)($_POST['doctor_id'] ?? 0);
$date          = trim($_POST['appointment_date'] ?? '');
$time          = trim($_POST['appointment_time'] ?? '');
$pet_type      = trim($_POST['pet_type'] ?? '');
$pet_race      = trim($_POST['pet_race'] ?? '');
$visit_purpose = trim($_POST['visit_purpose'] ?? 'معاینه و چکاپ سلامت');
$pet_notes     = trim($_POST['pet_notes'] ?? '');

// ── Validate inputs ────────────────────────────────────────────────────────────
if ($doctor_id <= 0 || empty($date) || empty($time) || empty($pet_type)) {
    $_SESSION['booking_error'] = 'لطفا پزشک، تاریخ و زمان را به درستی انتخاب کنید.';
    header('Location: ../booking.php');
    exit;
}

// Reject past dates
if (strtotime($date) < strtotime('today')) {
    $_SESSION['booking_error'] = 'تاریخ انتخابی نمی‌تواند در گذشته باشد.';
    header('Location: ../booking.php');
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate(
    (int)substr($date, 5, 2),
    (int)substr($date, 8, 2),
    (int)substr($date, 0, 4)
)) {
    $_SESSION['booking_error'] = 'فرمت تاریخ نامعتبر است.';
    header('Location: ../booking.php');
    exit;
}

// Validate time format (HH:MM)
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    $_SESSION['booking_error'] = 'فرمت زمان نامعتبر است.';
    header('Location: ../booking.php');
    exit;
}

try {
    // ── Doctor validation & fee calculation ──────────────────────────────────
    $docCheck = $pdo->prepare("SELECT id, price, provider_type, organization_id FROM doctors WHERE id = ?");
    $docCheck->execute([$doctor_id]);
    $doctorRow = $docCheck->fetch();
    if (!$doctorRow) {
        $_SESSION['booking_error'] = 'متخصص انتخابی معتبر نیست.';
        header('Location: ../booking.php');
        exit;
    }
    $doctor_price = (int)$doctorRow['price'];
    $provider_type = $doctorRow['provider_type'] ?? 'doctor';
    $organization_id = !empty($doctorRow['organization_id']) ? (int)$doctorRow['organization_id'] : 1;
    
    // Calculate 5% platform interest / commission and 95% clinic net share
    $commission_rate = 0.05;
    $commission_amount = (int)round($doctor_price * $commission_rate);
    $net_amount = $doctor_price - $commission_amount;
    $service_type = ($provider_type === 'groomer') ? 'grooming' : 'consultation';

    $pdo->beginTransaction();

    // ── Double-booking guard ───────────────────────────────────────────────────
    // Check if this exact slot is already taken (prevents race conditions via DB constraint)
    $dupStmt = $pdo->prepare(
        "SELECT id FROM appointments
         WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
         AND status NOT IN ('cancelled')
         LIMIT 1
         FOR UPDATE"
    );
    $dupStmt->execute([$doctor_id, $date, $time]);
    if ($dupStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = 'این زمان قبلاً رزرو شده است. لطفاً زمان دیگری انتخاب کنید.';
        header('Location: ../booking.php');
        exit;
    }

    // ── Check if doctor blocked this slot (for phone bookings/leaves) ───────────
    $blkStmt = $pdo->prepare(
        "SELECT id FROM doctor_blocked_slots
         WHERE doctor_id = ? AND block_date = ? 
         AND ((start_time = '00:00' AND end_time = '23:59') OR (? BETWEEN start_time AND end_time) OR start_time = ?)
         LIMIT 1"
    );
    $blkStmt->execute([$doctor_id, $date, $time, $time]);
    if ($blkStmt->fetch()) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = 'این زمان توسط مرکز جهت نوبت‌های تلفنی یا استراحت مسدود شده است. لطفاً زمان دیگری را انتخاب نمایید.';
        header('Location: ../booking.php');
        exit;
    }

    // ── Insert appointment with financial breakdown and purpose ───────────────
    $stmt = $pdo->prepare(
        "INSERT INTO appointments (
            user_id, doctor_id, organization_id, appointment_date, appointment_time, 
            pet_type, pet_race, visit_purpose, pet_notes, fee, commission_amount, 
            net_amount, service_type, settlement_status, status
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_service', 'pending')"
    );
    $stmt->execute([
        $_SESSION['user_id'], $doctor_id, $organization_id, $date, $time,
        $pet_type, $pet_race, $visit_purpose, $pet_notes,
        $doctor_price, $commission_amount, $net_amount, $service_type
    ]);
    $appointment_id = $pdo->lastInsertId();

    // ── Store booking pending order in session for payment flow ───────────────
    $_SESSION['pending_order'] = [
        'type'           => 'booking',
        'booking_id'     => $appointment_id,
        'items'          => [],
        'total_amount'   => $doctor_price,
        'commission'     => $commission_amount,
        'net_amount'     => $net_amount,
        'created_at'     => time(),
    ];
    $_SESSION['pay_nonce'] = bin2hex(random_bytes(24));
    
    $pdo->commit();

    header('Location: ../payment.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Booking error: " . $e->getMessage());
    $_SESSION['booking_error'] = 'خطای سیستمی در ثبت نوبت. لطفاً دوباره تلاش کنید.';
    header('Location: ../booking.php');
    exit;
}
