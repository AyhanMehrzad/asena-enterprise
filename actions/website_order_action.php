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

// Self-healing table creation for website_orders
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS website_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                archetype VARCHAR(32) NOT NULL,
                tier VARCHAR(32) NOT NULL DEFAULT 'standard',
                desired_slug VARCHAR(64) NOT NULL,
                full_name VARCHAR(150) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                email VARCHAR(100) NULL,
                notes TEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'pending',
                reviewed_by_user_id INT NULL,
                reviewed_at DATETIME NULL,
                admin_notes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `website_orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NULL,
                `archetype` VARCHAR(32) NOT NULL,
                `tier` VARCHAR(32) NOT NULL DEFAULT 'standard',
                `desired_slug` VARCHAR(64) NOT NULL,
                `full_name` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(50) NOT NULL,
                `email` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                `status` ENUM('pending', 'contacted', 'provisioned', 'rejected') NOT NULL DEFAULT 'pending',
                `reviewed_by_user_id` INT NULL,
                `reviewed_at` DATETIME NULL,
                `admin_notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
} catch (Throwable $eIgnore) {}

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

// Get user's active site and order status
if ($action === 'get_my_website') {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'کاربر وارد نشده است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $role = $_SESSION['user_role'] ?? null;
    $site = $tenantService->getSiteForUser($userId, $role);
    
    // Check pending orders
    $stmt = $pdo->prepare("SELECT * FROM website_orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'has_site' => !empty($site),
        'site' => $site,
        'has_order' => !empty($order),
        'order' => $order
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Provision starter site for logged-in user
if ($action === 'provision_my_site') {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $role = $_SESSION['user_role'] ?? 'doctor';
    $desiredSlug = $tenantService->sanitizeSlug($_POST['desired_slug'] ?? '');
    
    // Find tenant_type and tenant_id
    $tenantType = match($role) {
        'doctor' => 'doctor',
        'pharmacist', 'pharmacy' => 'pharmacist',
        'seller' => 'seller',
        'organization', 'clinic', 'organization_manager' => 'organization',
        default => 'doctor'
    };
    
    $tenantId = $userId;
    $info = ['user_id' => $userId];
    if ($tenantType === 'doctor') {
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            $tenantId = (int)$doc['id'];
            $info['name'] = $doc['name'];
            $info['specialty'] = $doc['specialty'];
            $info['phone'] = $doc['phone'];
            $info['avatar_url'] = $doc['avatar_url'];
        }
    } elseif ($tenantType === 'organization') {
        $stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($org) {
            $tenantId = (int)$org['id'];
            $info['name'] = $org['name'];
            $info['phone'] = $org['phone'];
            $info['address'] = $org['address'];
        }
    } elseif ($tenantType === 'seller') {
        $stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $sel = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($sel) {
            $tenantId = (int)$sel['id'];
            $info['name'] = $sel['shop_name'] ?? $sel['name'] ?? 'پت‌شاپ اختصاصی';
            $info['phone'] = $sel['phone'];
        }
    }

    $existing = $tenantService->getSiteByTenant($tenantType, $tenantId);
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'وب‌سایت اختصاصی شما از قبل فعال است.',
            'slug' => $existing['slug'],
            'site' => $existing
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $site = $tenantService->getOrCreateDefault($tenantType, $tenantId, $info);
    if (!empty($desiredSlug) && $tenantService->isSlugAvailable($desiredSlug, (int)$site['id'])) {
        $site = $tenantService->saveSite($tenantType, $tenantId, ['slug' => $desiredSlug])['site'] ?? $site;
    }

    echo json_encode([
        'success' => true,
        'message' => 'وب‌سایت اختصاصی شما با موفقیت ایجاد و فعال شد!',
        'slug' => $site['slug'],
        'site' => $site
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است.'], JSON_UNESCAPED_UNICODE);
