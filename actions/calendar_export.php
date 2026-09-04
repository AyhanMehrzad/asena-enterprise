<?php
/**
 * ASENA Enterprise — iCalendar (.ics) and Google Calendar Exporter
 * Generates standards-compliant RFC 5545 calendar invitations for veterinary appointments.
 */

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$appointment_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$format = $_GET['format'] ?? 'ics'; // 'ics' or 'google'

if ($appointment_id <= 0) {
    die('شناسه نوبت نامعتبر است.');
}

// Fetch appointment ensuring ownership
$stmt = $pdo->prepare("
    SELECT a.*, d.name as doctor_name, d.specialty as doctor_specialty, d.address as clinic_address, u.name as pet_owner_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$apt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apt) {
    die('نوبت مورد نظر یافت نشد یا دسترسی مجاز نیست.');
}

// Parse datetime
$startDateTime = new DateTime($apt['appointment_date'] . ' ' . $apt['appointment_time'], new DateTimeZone('Asia/Tehran'));
$endDateTime = clone $startDateTime;
$endDateTime->modify('+45 minutes'); // Standard appointment slot duration

$title = "نوبت دامپزشکی: دکتر " . $apt['doctor_name'] . " (" . ($apt['doctor_specialty'] ?: 'کلینیک') . ")";
$description = "نوبت ویزیت بیمار: " . ($apt['pet_name'] ?: ($apt['pet_type'] ?: 'حیوان خانگی')) . "\\nعلت مراجعه: " . ($apt['visit_purpose'] ?: 'معاینه دوره‌ای') . "\\nسامانه مدیریت کلینیک آسنا";
$location = !empty($apt['clinic_address']) ? $apt['clinic_address'] : "کلینیک تخصصی حیوانات آسنا";

if ($format === 'google') {
    // Format UTC for Google Calendar URL
    $startUtc = clone $startDateTime;
    $startUtc->setTimezone(new DateTimeZone('UTC'));
    $endUtc = clone $endDateTime;
    $endUtc->setTimezone(new DateTimeZone('UTC'));

    $dates = $startUtc->format('Ymd\THis\Z') . '/' . $endUtc->format('Ymd\THis\Z');
    $gcalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE"
        . "&text=" . urlencode($title)
        . "&dates=" . urlencode($dates)
        . "&details=" . urlencode(str_replace('\\n', "\n", $description))
        . "&location=" . urlencode($location);

    header("Location: $gcalUrl");
    exit;
}

// Default: RFC 5545 iCalendar stream
$startUtc = clone $startDateTime;
$startUtc->setTimezone(new DateTimeZone('UTC'));
$endUtc = clone $endDateTime;
$endUtc->setTimezone(new DateTimeZone('UTC'));
$nowUtc = new DateTime('now', new DateTimeZone('UTC'));

$uid = "asena-appt-{$apt['id']}-" . md5($apt['created_at'] ?? time()) . "@asena.company";

$ics = "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//ASENA Enterprise//Veterinary Booking//FA\r\n";
$ics .= "CALSCALE:GREGORIAN\r\n";
$ics .= "METHOD:PUBLISH\r\n";
$ics .= "X-WR-CALNAME:نوبت‌های کلینیک آسنا\r\n";
$ics .= "X-WR-TIMEZONE:Asia/Tehran\r\n";
$ics .= "BEGIN:VEVENT\r\n";
$ics .= "UID:{$uid}\r\n";
$ics .= "DTSTAMP:" . $nowUtc->format('Ymd\THis\Z') . "\r\n";
$ics .= "DTSTART:" . $startUtc->format('Ymd\THis\Z') . "\r\n";
$ics .= "DTEND:" . $endUtc->format('Ymd\THis\Z') . "\r\n";
$ics .= "SUMMARY:" . $title . "\r\n";
$ics .= "DESCRIPTION:" . $description . "\r\n";
$ics .= "LOCATION:" . $location . "\r\n";
$ics .= "STATUS:CONFIRMED\r\n";
// Reminder 1 hour before
$ics .= "BEGIN:VALARM\r\n";
$ics .= "TRIGGER:-PT60M\r\n";
$ics .= "ACTION:DISPLAY\r\n";
$ics .= "DESCRIPTION:یادآوری نوبت کلینیک آسنا\r\n";
$ics .= "END:VALARM\r\n";
$ics .= "END:VEVENT\r\n";
$ics .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="asena-appointment-' . $apt['id'] . '.ics"');
header('Content-Length: ' . strlen($ics));
header('Connection: close');

echo $ics;
exit;
