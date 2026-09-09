<?php
/**
 * ASENA Enterprise - On-Demand Postex Parcel Reindex & Tracking Sync Action
 * Allows sellers, clinic managers, and admins to force an immediate live check against Postex API.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
App::boot();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';
$allowedRoles = ['admin', 'seller', 'organization_admin', 'clinic_manager', 'doctor', 'pharmacist'];
if (!in_array($userRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

csrf_verify();

try {
    $postex = App::postex();
    $res = $postex->syncInTransitParcels();

    $checked = (int)($res['checked_count'] ?? 0);
    $delivered = (int)($res['delivered_count'] ?? 0);
    $inTransit = (int)($res['in_transit_count'] ?? 0);

    $msg = "استعلام پستکس با موفقیت انجام شد: {$checked} مرسوله در جریان بررسی شد.";
    if ($delivered > 0) {
        $msg .= " {$delivered} مرسوله با تایید تحویل، به وضعیت تحویل نهایی تغییر یافتند.";
    }

    echo json_encode([
        'success' => true,
        'checked_count' => $checked,
        'delivered_count' => $delivered,
        'in_transit_count' => $inTransit,
        'message' => $msg
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در برقراری ارتباط با وب‌سرویس پستکس: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
