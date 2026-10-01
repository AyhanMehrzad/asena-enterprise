<?php
/**
 * ASENA Enterprise - Site Builder AJAX Controller
 * Handles real-time saving, slug uniqueness verification, and publishing of tenant showcase websites.
 * Supports: Doctor, Organization, Pharmacist, and Seller.
 * Version: 1.0.0
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/App.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Authentication Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

if ($userId <= 0 || empty($userRole)) {
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedRoles = ['organization', 'doctor', 'pharmacist', 'seller', 'admin'];
if (!in_array($userRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Resolve tenant identity
$tenantType = $userRole === 'admin' ? ($_POST['tenant_type'] ?? 'organization') : $userRole;
$tenantId = 0;

if ($tenantType === 'doctor') {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $tenantId = (int)$stmt->fetchColumn();
    if ($tenantId <= 0) {
        $ins = $pdo->prepare("INSERT INTO doctors (user_id, name, specialty, price) VALUES (?, ?, 'دامپزشک', 150000)");
        $ins->execute([$userId, $_SESSION['user_name'] ?? 'پزشک']);
        $tenantId = (int)$pdo->lastInsertId();
    }
} elseif ($tenantType === 'organization') {
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $tenantId = (int)$stmt->fetchColumn();
    if ($tenantId <= 0) {
        $ins = $pdo->prepare("INSERT INTO organizations (user_id, name, type) VALUES (?, ?, 'clinic')");
        $ins->execute([$userId, $_SESSION['user_name'] ?? 'مرکز درمانی']);
        $tenantId = (int)$pdo->lastInsertId();
    }
} else {
    // Seller or Pharmacist
    $tenantId = $userId;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$tenantService = App::tenantSite();

if ($action === 'check_slug') {
    $slug = trim($_POST['slug'] ?? $_GET['slug'] ?? '');
    $excludeId = (int)($_POST['site_id'] ?? 0);
    $available = $tenantService->isSlugAvailable($slug, $excludeId ?: null);
    echo json_encode([
        'success' => true,
        'available' => $available,
        'slug' => $tenantService->sanitizeSlug($slug)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    // Process JSON layout or form inputs
    $rawLayout = $_POST['layout'] ?? null;
    $layout = [];
    if (is_string($rawLayout)) {
        $layout = json_decode($rawLayout, true) ?: [];
    } elseif (is_array($rawLayout)) {
        $layout = $rawLayout;
    }

    $saveData = [
        'site_title' => sanitize_input($_POST['site_title'] ?? ''),
        'site_tagline' => sanitize_input($_POST['site_tagline'] ?? ''),
        'slug' => sanitize_input($_POST['slug'] ?? ''),
        'site_tier' => sanitize_input($_POST['site_tier'] ?? 'enterprise'),
        'theme_palette' => sanitize_input($_POST['theme_palette'] ?? 'emerald'),
        'primary_color' => sanitize_input($_POST['primary_color'] ?? '#001a48'),
        'secondary_color' => sanitize_input($_POST['secondary_color'] ?? '#fd8100'),
        'font_family' => sanitize_input($_POST['font_family'] ?? 'Vazirmatn'),
        'layout' => $layout,
        'is_published' => isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1,
        'logo_url' => sanitize_input($_POST['logo_url'] ?? ''),
        'banner_url' => sanitize_input($_POST['banner_url'] ?? ''),
        'meta_description' => sanitize_input($_POST['meta_description'] ?? '')
    ];

    $result = $tenantService->saveSite($tenantType, $tenantId, $saveData);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'apply_preset') {
    $tier = sanitize_input($_POST['tier'] ?? 'enterprise');
    $rawLayout = $_POST['layout'] ?? null;
    $layout = [];
    if (is_string($rawLayout)) {
        $layout = json_decode($rawLayout, true) ?: [];
    } elseif (is_array($rawLayout)) {
        $layout = $rawLayout;
    }

    $updatedLayout = $tenantService->applyTierPreset($tenantType, $tier, $layout);
    echo json_encode([
        'success' => true,
        'tier' => $tier,
        'layout' => $updatedLayout
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_section_defaults') {
    $section = sanitize_input($_POST['section'] ?? '');
    $items = $tenantService->getDefaultSectionItems($tenantType, $section);
    echo json_encode([
        'success' => true,
        'section' => $section,
        'items' => $items
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'upload_asset') {
    if (!isset($_FILES['file']) && !isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'فایلی برای بارگذاری ارسال نشده است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES['file'] ?? $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'خطا در بارگذاری فایل از سمت مرورگر (کد: ' . $file['error'] . ')'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'حجم فایل بیش از سقف مجاز (۱۰ مگابایت) است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'فرمت فایل غیرمجاز است. تنها فرمت‌های تصویری JPG, PNG, WebP, SVG مجاز هستند.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/sites/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $fileName = 'asset_' . $tenantType . '_' . $tenantId . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $relativePath = 'uploads/sites/' . $fileName;
        echo json_encode([
            'success' => true,
            'url' => $relativePath,
            'full_url' => '../' . $relativePath,
            'message' => 'تصویر با موفقیت بارگذاری شد.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی فایل در سرور.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'اکشن نامعتبر است.'], JSON_UNESCAPED_UNICODE);
