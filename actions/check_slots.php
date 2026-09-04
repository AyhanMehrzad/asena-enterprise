<?php
/**
 * Slot availability check — lightweight JSON endpoint.
 * Called via AJAX from booking.js every 10 seconds.
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date      = trim($_GET['date'] ?? '');

// Validate inputs
if ($doctor_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_params']);
    exit;
}

// Validate date is real
[$y, $m, $d] = explode('-', $date);
if (!checkdate((int)$m, (int)$d, (int)$y)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_date']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT appointment_time
         FROM appointments
         WHERE doctor_id = ? AND appointment_date = ?
         AND status NOT IN ('cancelled')"
    );
    $stmt->execute([$doctor_id, $date]);
    $booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Normalise to HH:MM
    $booked = array_map(fn($t) => substr($t, 0, 5), $booked_times);

    // Include doctor blocked slots
    $bStmt = $pdo->prepare(
        "SELECT start_time, end_time
         FROM doctor_blocked_slots
         WHERE doctor_id = ? AND block_date = ?"
    );
    $bStmt->execute([$doctor_id, $date]);
    $blocks = $bStmt->fetchAll(PDO::FETCH_ASSOC);
    $allSlots = ['08:00', '08:45', '09:30', '10:15', '11:00', '11:45', '12:30', '16:00', '16:45', '17:30', '18:15', '19:00', '19:45', '20:30'];
    foreach ($blocks as $blk) {
        $sTime = substr($blk['start_time'] ?? '00:00', 0, 5);
        $eTime = substr($blk['end_time'] ?? '23:59', 0, 5);
        foreach ($allSlots as $slot) {
            if ($sTime === '00:00' && $eTime === '23:59') {
                $booked[] = $slot;
            } elseif ($slot >= $sTime && $slot <= $eTime) {
                $booked[] = $slot;
            } elseif ($slot === $sTime) {
                $booked[] = $slot;
            }
        }
    }
    $booked = array_values(array_unique($booked));

    echo json_encode([
        'doctor_id'   => $doctor_id,
        'date'        => $date,
        'booked_slots' => $booked,
    ]);

} catch (PDOException $e) {
    error_log("check_slots error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
