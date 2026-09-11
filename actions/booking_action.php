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

$doctor_id       = (int)($_POST['doctor_id'] ?? 0);
$organization_id = (int)($_POST['organization_id'] ?? 0);
$date            = trim($_POST['appointment_date'] ?? '');
$time            = trim($_POST['appointment_time'] ?? '');
$pet_id          = (int)($_POST['pet_id'] ?? 0);
$pet_name        = trim($_POST['pet_name'] ?? '');
$pet_type        = trim($_POST['pet_type'] ?? '');
$pet_race        = trim($_POST['pet_race'] ?? '');
$pet_gender      = trim($_POST['pet_gender'] ?? '');
$pet_age         = trim($_POST['pet_age'] ?? '');
$pet_weight      = isset($_POST['pet_weight']) && $_POST['pet_weight'] !== '' ? (float)$_POST['pet_weight'] : null;
$visit_purpose   = trim($_POST['visit_purpose'] ?? 'معاینه و چکاپ سلامت');
$pet_notes       = trim($_POST['pet_notes'] ?? '');

// ── Validate inputs ────────────────────────────────────────────────────────────
if (($doctor_id <= 0 && $organization_id <= 0) || empty($date) || empty($time) || empty($pet_type)) {
    $_SESSION['booking_error'] = 'لطفا متخصص معالج یا مرکز درمانی، تاریخ، زمان و مشخصات بیمار را به درستی وارد نمایید.';
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
    // ── Doctor / Organization validation & fee calculation ─────────────────────
    if ($doctor_id > 0) {
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
        $organization_id = !empty($doctorRow['organization_id']) ? (int)$doctorRow['organization_id'] : ($organization_id ?: 1);
        $service_type = ($provider_type === 'groomer') ? 'grooming' : 'consultation';
    } else {
        // Direct organization/hospital booking
        $orgCheck = $pdo->prepare("SELECT id, name, consultation_fee FROM organizations WHERE id = ?");
        $orgCheck->execute([$organization_id]);
        $orgRow = $orgCheck->fetch();
        if (!$orgRow) {
            $_SESSION['booking_error'] = 'مرکز درمانی انتخابی معتبر نیست.';
            header('Location: ../booking.php');
            exit;
        }
        $doctor_price = (int)($orgRow['consultation_fee'] ?? 250000);
        if ($doctor_price <= 0) $doctor_price = 250000;
        $provider_type = 'clinic';
        $service_type = 'consultation';
        $doctor_id = null;
    }
    
    // Calculate 5% platform interest / commission and 95% clinic net share
    $commission_rate_pct = (float)get_setting($pdo, 'platform_commission_percent', 5);
    $commission_amount = (int)round($doctor_price * ($commission_rate_pct / 100.0));
    $net_amount = $doctor_price - $commission_amount;

    // Calculate 9% VAT for user display & payment
    $tax_on_appts = (get_setting($pdo, 'tax_on_appointments_enabled', '1') === '1');
    $tax_rate_pct = $tax_on_appts ? (float)get_setting($pdo, 'tax_rate_percent', 9) : 0;
    $tax_amount = (int)round($doctor_price * ($tax_rate_pct / 100.0));
    $user_total_payable = $doctor_price + $tax_amount;

    $pdo->beginTransaction();

    // ── Double-booking guard ───────────────────────────────────────────────────
    if ($doctor_id !== null) {
        // Check if this doctor slot is already taken
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

        // Check if doctor blocked this slot (for phone bookings/leaves)
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
    } else {
        // Double booking guard for direct facility: user cannot book same slot twice
        $dupStmt = $pdo->prepare(
            "SELECT id FROM appointments
             WHERE user_id = ? AND organization_id = ? AND appointment_date = ? AND appointment_time = ?
             AND status NOT IN ('cancelled')
             LIMIT 1"
        );
        $dupStmt->execute([$_SESSION['user_id'], $organization_id, $date, $time]);
        if ($dupStmt->fetch()) {
            $pdo->rollBack();
            $_SESSION['booking_error'] = 'شما قبلاً در این تاریخ و ساعت یک نوبت در این مرکز ثبت نموده‌اید.';
            header('Location: ../booking.php');
            exit;
        }
    }

    // ── Insert appointment with complete pet vitals and financial breakdown ────
    $stmt = $pdo->prepare(
        "INSERT INTO appointments (
            user_id, doctor_id, organization_id, appointment_date, appointment_time, 
            pet_type, pet_race, pet_id, pet_name, pet_gender, pet_age, pet_weight,
            visit_purpose, pet_notes, fee, commission_amount, 
            net_amount, service_type, settlement_status, status
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_service', 'pending')"
    );
    $stmt->execute([
        $_SESSION['user_id'], $doctor_id, $organization_id, $date, $time,
        $pet_type, $pet_race, ($pet_id > 0 ? $pet_id : null), $pet_name, $pet_gender, $pet_age, $pet_weight,
        $visit_purpose, $pet_notes,
        $doctor_price, $commission_amount, $net_amount, $service_type
    ]);
    $appointment_id = $pdo->lastInsertId();

    // ── Store booking pending order in session for payment flow ───────────────
    $_SESSION['pending_order'] = [
        'type'           => 'booking',
        'booking_id'     => $appointment_id,
        'items'          => [],
        'base_fee'       => $doctor_price,
        'tax_amount'     => $tax_amount,
        'total_amount'   => $user_total_payable,
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
