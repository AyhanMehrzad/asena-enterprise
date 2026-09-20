<?php
/**
 * ASENA Enterprise - Initiate Meal Plan Payment
 * Creates payment order & redirects user to active payment driver (ZarinPal, Mock, Card-to-Card)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PaymentService.php';

// Verify POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد ارسالی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check authentication
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'require_login' => true,
        'message' => 'جهت پرداخت و صدور جدول برنامه غذایی در پرونده، لطفاً ابتدا وارد حساب کاربری خود شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse input
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput) && ($decoded = json_decode($rawInput, true))) {
    $inputData = $decoded;
} else {
    $inputData = $_POST;
}

// CSRF check
$csrf = $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrf)) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی (CSRF) نامعتبر است. لطفاً صفحه را تازه‌سازی فرمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$calcPrice = (int)get_setting($pdo, 'calculator_price_toman', 98000);
if ($calcPrice <= 0) {
    // If price is 0 or negative, route directly to free saver
    require_once __DIR__ . '/save_nutrition_report.php';
    exit;
}

$petName = trim((string)($inputData['pet_name'] ?? 'حیوان خانگی'));
$species = in_array($inputData['species'] ?? '', ['dog', 'cat']) ? $inputData['species'] : 'dog';
$race = trim((string)($inputData['race'] ?? 'مشخص نشده'));
$weightKg = (float)($inputData['weight_kg'] ?? 0);

if ($weightKg <= 0) {
    echo json_encode(['success' => false, 'message' => 'وزن پت نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fetch current user details for gateway
$uStmt = $pdo->prepare("SELECT email, phone FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);

$orderDesc = "پرداخت هزینه صدور رسمی و بایگانی جدول برنامه غذایی بالینی برای پت ({$petName} - نژاد {$race})";

// Initiate payment via unified PaymentService
try {
    $paymentService = new PaymentService($pdo);

    $metadata = [
        'email' => $user['email'] ?? '',
        'mobile' => (string)($user['phone'] ?? ''),
        'meal_plan_data' => $inputData
    ];

    $res = $paymentService->requestPayment(
        $userId,
        $calcPrice,
        $orderDesc,
        'meal_plan',
        null,
        $metadata
    );

    if (!$res['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'خطا در اتصال به درگاه پرداخت: ' . ($res['error'] ?? $res['message'] ?? 'خطای نامشخص')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Store in session for callback verification
    $_SESSION['pending_order'] = [
        'type' => 'meal_plan',
        'user_id' => $userId,
        'total_amount' => $calcPrice,
        'authority' => $res['authority'],
        'meal_plan_data' => $inputData,
        'created_at' => time()
    ];

    echo json_encode([
        'success' => true,
        'authority' => $res['authority'],
        'payment_url' => $res['payment_url'],
        'amount' => $calcPrice
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ایجاد تراکنش: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
