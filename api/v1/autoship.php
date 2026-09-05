<?php
/**
 * ASENA Enterprise - Autoship REST API (Chewy.com Benchmark)
 * Endpoint: GET /api/v1/autoship.php
 * Endpoint: POST /api/v1/autoship.php (action=skip | action=ship_now)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/App.php';

App::boot();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'ابتدا باید وارد حساب کاربری خود شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$autoship = App::autoship();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $subs = $autoship->getUserSubscriptions($userId);
    echo json_encode(['success' => true, 'data' => $subs], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';
    $subId = (int)($input['subscription_id'] ?? 0);

    if (!$subId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'شناسه اشتراک الزامی است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'skip') {
        $ok = $autoship->skipNextShipment($subId, $userId);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'نوبت ارسال با موفقیت به دوره بعد موکول شد.' : 'خطا در به تعویق انداختن سفارش.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'ship_now') {
        $orderId = $autoship->shipNow($subId, $userId);
        echo json_encode([
            'success' => (bool)$orderId,
            'order_id' => $orderId,
            'message' => $orderId ? "سفارش (#{$orderId}) با موفقیت ثبت و جهت آماده‌سازی ارسال شد." : 'خطا در صدور فوری سفارش.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'عملیات نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}
