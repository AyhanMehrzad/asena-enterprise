<?php
/**
 * ASENA Enterprise - Iranian Postal Code Validation & Location Resolver Endpoint
 * 
 * Validates 10-digit Iranian postal codes algorithmically to reject fake/dummy input,
 * and resolves province, city, and approximate map coordinates for auto-centering.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../includes/MapService.php';

$rawCode = $_REQUEST['postal_code'] ?? $_REQUEST['code'] ?? '';

if (empty(trim($rawCode))) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'لطفاً کد پستی ۱۰ رقمی را وارد نمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$validation = MapService::validatePostalCode($rawCode);

if (!$validation['valid']) {
    echo json_encode([
        'status'  => 'error',
        'code'    => $validation['code'],
        'message' => $validation['error']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$resolved = MapService::lookupPostalCode($validation['code']);

if (!$resolved) {
    echo json_encode([
        'status'  => 'error',
        'code'    => $validation['code'],
        'message' => 'کد پستی وارد شده معتبر است اما اطلاعات مکانی آن در بانک داده یافت نشد.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status'  => 'success',
    'message' => 'کد پستی با موفقیت تأیید شد.',
    'data'    => $resolved
], JSON_UNESCAPED_UNICODE);
