<?php
/**
 * Card-to-Card Automatic Bank SMS / Notification Webhook
 * Accepts incoming SMS alerts forwarded from banking apps (Blu, Resalat, Mellat, Melli, Saman)
 * Matches transaction by amount or tracking code, and auto-approves.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PaymentService.php';

$secretKey = get_setting($pdo, 'card_webhook_secret', 'ASENA-WEBHOOK-SECRET-12345');
$providedKey = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? $_GET['secret'] ?? '';

if (!hash_equals((string)$secretKey, (string)$providedKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'کلید امنیتی وب‌سرویس نامعتبر است.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$amount = (int)($data['amount'] ?? 0);
$trackingCode = preg_replace('/[^\d]/', '', (string)($data['tracking_code'] ?? ''));
$smsBody = (string)($data['sms_body'] ?? $data['message'] ?? '');

// If sms_body was passed, attempt regex parse
if (empty($amount) && !empty($smsBody)) {
    // Look for amounts (e.g. واریز: 1,000,000 ریال or تومان)
    if (preg_match('/(?:مبلغ|واریز|مانده)[:\s]*([\d,\.]+)/u', $smsBody, $m)) {
        $parsedNum = (int)str_replace([',', '.'], '', $m[1]);
        if (str_contains($smsBody, 'ریال')) {
            $amount = (int)round($parsedNum / 10);
        } else {
            $amount = $parsedNum;
        }
    }
    // Look for tracking code / ارجاع / پیگیری
    if (preg_match('/(?:پیگیری|ارجاع|کد)[:\s]*(\d{4,12})/u', $smsBody, $mCode)) {
        $trackingCode = $mCode[1];
    }
}

if ($amount <= 0 && empty($trackingCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'مبلغ یا کد پیگیری یافت نشد.']);
    exit;
}

// 1. Search for matching submission by tracking code first
$paymentService = new PaymentService($pdo);

if (!empty($trackingCode)) {
    $stmt = $pdo->prepare("SELECT id FROM card_receipt_submissions WHERE bank_tracking_code = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$trackingCode]);
    $subId = $stmt->fetchColumn();
    if ($subId) {
        $res = $paymentService->approveCardReceipt((int)$subId, 0, 'تأیید خودکار از طریق وب‌سرویس پیامک بانک');
        echo json_encode($res);
        exit;
    }
}

// 2. Search for matching pending transaction by exact amount
if ($amount > 0) {
    $txStmt = $pdo->prepare("
        SELECT s.id 
        FROM card_receipt_submissions s
        JOIN payment_transactions t ON s.payment_transaction_id = t.id
        WHERE s.status = 'pending' AND t.amount = ?
        ORDER BY s.id ASC LIMIT 1
    ");
    $txStmt->execute([$amount]);
    $subId = $txStmt->fetchColumn();
    if ($subId) {
        $res = $paymentService->approveCardReceipt((int)$subId, 0, 'تأیید خودکار بر اساس تطبیق دقیق مبلغ واریزی');
        echo json_encode($res);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'پیامک دریافت شد ولی تراکنش باز همخوانی‌دار در لحظه یافت نشد.',
    'parsed' => ['amount' => $amount, 'tracking_code' => $trackingCode]
]);
