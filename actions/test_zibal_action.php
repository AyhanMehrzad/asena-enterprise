<?php
/**
 * ASENA Enterprise - Zibal Gateway Live Diagnostic Ping Action
 * AJAX Endpoint for Admin Finance Settings Console
 */

require_once __DIR__ . '/../includes/App.php';
App::boot();

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'دسترسی غیرمجاز. فقط مدیر کل سامانه امکان اجرای آزمون ارتباطی درگاه را دارد.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست باید POST باشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!SecurityMiddleware::validateCsrfToken($csrfToken)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی (CSRF) منقضی شده است. لطفاً صفحه را رفرش فرمایید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../includes/ZibalGateway.php';

$pdo = App::db();
$customMerchant = isset($_POST['merchant_key']) ? trim((string)$_POST['merchant_key']) : null;
$isSandbox = isset($_POST['sandbox']) ? (bool)$_POST['sandbox'] : null;

try {
    $gateway = new ZibalGateway($customMerchant, $isSandbox, $pdo);
    $health = $gateway->checkHealth($customMerchant, $isSandbox);

    echo json_encode([
        'success' => true,
        'health'  => $health
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در برقراری ارتباط با زیبال: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
