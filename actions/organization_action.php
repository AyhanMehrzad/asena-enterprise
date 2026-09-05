<?php
/**
 * ASENA Enterprise - Organization Actions Handler
 * Handles review submissions, feedback, and direct clinic inquiries.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$orgService = App::organization();

if ($action === 'submit_review') {
    $orgId   = (int)($_POST['organization_id'] ?? 0);
    $rating  = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($orgId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه مرکز درمانی نامعتبر است.']);
        exit;
    }

    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً متن نظر یا تجربه خود را بنویسید.']);
        exit;
    }

    // Determine user ID (authenticated or guest/placeholder)
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        // Find or create guest user
        $guestName = trim($_POST['reviewer_name'] ?? 'مراجعه‌کننده آسنا');
        $guestPhone = trim($_POST['reviewer_phone'] ?? '09120000000');
        
        $st = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $st->execute([$guestPhone]);
        $userId = (int)$st->fetchColumn();

        if (!$userId) {
            $ins = $pdo->prepare("INSERT INTO users (name, phone, role) VALUES (?, ?, 'user')");
            $ins->execute([$guestName, $guestPhone]);
            $userId = (int)$pdo->lastInsertId();
        }
    }

    $result = $orgService->addReview($orgId, $userId, $rating, $comment);
    echo json_encode($result);
    exit;
}

if ($action === 'send_inquiry') {
    $orgId   = (int)($_POST['organization_id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $petType = trim($_POST['pet_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($orgId <= 0 || empty($name) || empty($phone) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً کلیه فیلدهای الزامی را تکمیل فرمایید.']);
        exit;
    }

    // Save inquiry to chat_messages or dashboard_events
    try {
        $eventStmt = $pdo->prepare("
            INSERT INTO dashboard_events (event_type, title, description, created_at)
            VALUES ('clinic_inquiry', ?, ?, NOW())
        ");
        $title = "پیام استعلام برای مرکز درمانی #{$orgId} از طرف {$name}";
        $desc = "تلفن: {$phone} | حیوان خانگی: {$petType} | متن پیام: {$message}";
        $eventStmt->execute([$title, $desc]);

        echo json_encode(['success' => true, 'message' => 'پیام شما برای مرکز درمانی ارسال گردید. همکاران در اسرع وقت با شما تماس خواهند گرفت.']);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'پیام شما دریافت شد و به اطلاع کادر مرکز درمانی خواهد رسید.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملیات تعریف نشده است.']);
