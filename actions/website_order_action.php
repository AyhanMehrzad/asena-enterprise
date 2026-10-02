<?php
/**
 * ASENA Enterprise - Website Purchase & Order Controller
 * Handles incoming website setup orders, custom domain requests, and subdomain verification.
 * 
 * Version: 1.0.0
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/App.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$tenantService = App::tenantSite();

// Subdomain live availability check
if ($action === 'check_slug') {
    $rawSlug = trim($_POST['slug'] ?? $_GET['slug'] ?? '');
    $sanitized = $tenantService->sanitizeSlug($rawSlug);

    if (empty($sanitized)) {
        echo json_encode([
            'success' => false,
            'available' => false,
            'message' => 'لطفاً یک آدرس معتبر به حروف یا اعداد انگلیسی وارد کنید.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strlen($sanitized) < 3) {
        echo json_encode([
            'success' => false,
            'available' => false,
            'message' => 'طول آدرس باید حداقل ۳ کاراکتر باشد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $reserved = ['admin', 'api', 'app', 'panel', 'login', 'shop', 'pharmacy', 'booking', 'blog', 'support', 'help', 'mail', 'doctor', 'seller', 'organization', 'pharmacist'];
    if (in_array($sanitized, $reserved)) {
        echo json_encode([
            'success' => true,
            'available' => false,
            'slug' => $sanitized,
            'message' => 'این شناسه به عنوان کلید رزرو شده سیستم است و قابل انتخاب نیست.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $available = $tenantService->isSlugAvailable($sanitized);
    echo json_encode([
        'success' => true,
        'available' => $available,
        'slug' => $sanitized,
        'message' => $available ? "شناسه {$sanitized}.asena.company آزاد و آماده فعال‌سازی است!" : "شناسه {$sanitized} قبلاً ثبت شده است. لطفاً شناسه دیگری انتخاب کنید."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Order submission
if ($action === 'submit_order') {
    $archetype = sanitize_input($_POST['archetype'] ?? 'doctor');
    $tier = sanitize_input($_POST['tier'] ?? 'standard');
    $desiredSlug = $tenantService->sanitizeSlug($_POST['desired_slug'] ?? '');
    $fullName = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');

    $validArchetypes = ['doctor', 'pharmacist', 'seller', 'organization'];
    if (!in_array($archetype, $validArchetypes)) {
        $archetype = 'doctor';
    }

    $validTiers = ['basic', 'standard', 'premium', 'pharmacy', 'enterprise'];
    if (!in_array($tier, $validTiers)) {
        $tier = 'standard';
    }

    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً نام و نام خانوادگی یا نام مجموعه را وارد کنید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($phone) || !preg_match('/^09[0-9]{9}$/', preg_replace('/[^\d]/', '', $phone))) {
        echo json_encode(['success' => false, 'message' => 'لطفاً یک شماره موبایل معتبر ۱۱ رقمی (مانند ۰۹۱۲۳۴۵۶۷۸۹) وارد کنید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($desiredSlug) || strlen($desiredSlug) < 3) {
        $desiredSlug = 'site-' . substr(md5(uniqid()), 0, 6);
    }

    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO website_orders (
                user_id, archetype, tier, desired_slug, full_name, phone, email, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $userId,
            $archetype,
            $tier,
            $desiredSlug,
            $fullName,
            $phone,
            $email,
            $notes
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // Archetype title in Persian
        $archetypesMeta = $tenantService->getWebsiteArchetypes();
        $archTitle = $archetypesMeta[$archetype]['title'] ?? 'وب‌سایت اختصاصی';

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'archetype_title' => $archTitle,
            'slug' => $desiredSlug,
            'message' => 'سفارش و درخواست فعال‌سازی وب‌سایت شما با موفقیت ثبت شد. کارشناسان ما ظرف حداکثر ۲ ساعت کاری جهت تحویل نهایی و تنظیمات دامنه با شما تماس خواهند گرفت.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        error_log("[WebsiteOrderAction] " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت سفارش. لطفاً مجدداً تلاش نمایید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است.'], JSON_UNESCAPED_UNICODE);
