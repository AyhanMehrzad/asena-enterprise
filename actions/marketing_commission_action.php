<?php
/**
 * actions/marketing_commission_action.php
 * Administrative AJAX Endpoint for Platform Commission & Marketing Zero-Fee Mode
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SecurityMiddleware.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Authentication check: Admin only
if (empty($_SESSION['user_id']) || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز. فقط مدیر کل سامانه مجاز به مدیریت کارمزد است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
try {
    SecurityMiddleware::validateCsrfToken($csrfToken);
} catch (Throwable $e) {
    if (!verify_csrf_token($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'توکن امنیتی منقضی شده است. لطفاً صفحه را تازه‌سازی فرمایید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$action = trim($_POST['action'] ?? '');

switch ($action) {
    case 'toggle_marketing_commission':
        $rawEnabled = $_POST['enabled'] ?? '1';
        $isEnabled = ($rawEnabled === '1' || $rawEnabled === 1 || $rawEnabled === 'true' || $rawEnabled === true);
        $settingVal = $isEnabled ? '1' : '0';
        
        set_setting($pdo, 'platform_commission_enabled', $settingVal);
        $currentPercent = (float)get_setting($pdo, 'platform_commission_percent', 5.0);
        $effectiveRate = $isEnabled ? $currentPercent : 0.0;
        $providerShare = 100.0 - $effectiveRate;

        $msg = $isEnabled 
            ? "کارمزد پلتفرم فعال گردید (نرخ جاری: {$currentPercent}٪ | سهم ارائه‌دهنده: {$providerShare}٪)." 
            : "کمپین مارکتینگ فعال شد! کارمزد پلتفرم موقتاً ۰٪ گردید و ۱۰۰٪ درآمد به فروشندگان و پزشکان تعلق می‌گیرد.";

        echo json_encode([
            'success' => true,
            'enabled' => $isEnabled,
            'percent' => $currentPercent,
            'effective_rate' => $effectiveRate,
            'provider_share' => $providerShare,
            'message' => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'update_commission_percentage':
        if (!isset($_POST['percentage']) || !is_numeric($_POST['percentage'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'لطفاً یک درصد عددی معتبر وارد نمایید.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $newPercent = round(max(0.0, min(100.0, (float)$_POST['percentage'])), 2);
        set_setting($pdo, 'platform_commission_percent', $newPercent);

        $isEnabled = (get_setting($pdo, 'platform_commission_enabled', '1') !== '0');
        $effectiveRate = $isEnabled ? $newPercent : 0.0;
        $providerShare = 100.0 - $effectiveRate;

        $msg = "نرخ کارمزد پلتفرم با موفقیت روی {$newPercent}٪ تنظیم و ذخیره شد (سهم خالص ارائه‌دهنده: {$providerShare}٪).";
        if (!$isEnabled) {
            $msg .= " (توجه: در حال حاضر کلید کارمزد جهت کمپین مارکتینگ غیرفعال است و نرخ موثر ۰٪ اعمال می‌شود).";
        }

        echo json_encode([
            'success' => true,
            'enabled' => $isEnabled,
            'percent' => $newPercent,
            'effective_rate' => $effectiveRate,
            'provider_share' => $providerShare,
            'message' => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'get_status':
        $isEnabled = (get_setting($pdo, 'platform_commission_enabled', '1') !== '0');
        $percent = (float)get_setting($pdo, 'platform_commission_percent', 5.0);
        $effectiveRate = $isEnabled ? $percent : 0.0;
        $providerShare = 100.0 - $effectiveRate;

        echo json_encode([
            'success' => true,
            'enabled' => $isEnabled,
            'percent' => $percent,
            'effective_rate' => $effectiveRate,
            'provider_share' => $providerShare
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'عملیات درخواستی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
        exit;
}
