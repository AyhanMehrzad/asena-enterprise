<?php
/**
 * ASENA Enterprise - Prescription (Rx) Upload Handler
 * Chewy-Style Digital Prescription Verification
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$clinicName = trim($_POST['clinic_name'] ?? '');
$vetName = trim($_POST['vet_name'] ?? '');
$vetPhone = trim($_POST['vet_phone'] ?? '');
$vetLicense = trim($_POST['vet_license_number'] ?? '');
$petId = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : null;

// Validate upload
if (empty($_FILES['rx_file']) || $_FILES['rx_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'لطفاً تصویر یا فایل معتبر نسخه را انتخاب نمایید.']);
    exit;
}

$file = $_FILES['rx_file'];
$maxSize = 10 * 1024 * 1024; // 10 MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'حجم فایل نسخه نباید بیشتر از ۱۰ مگابایت باشد.']);
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'فرمت فایل مجاز نمی‌باشد (تنها JPG, PNG, WEBP, PDF).']);
    exit;
}

// Ensure destination directory
$uploadDir = __DIR__ . '/../uploads/prescriptions';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique hashed filename
$fileName = 'rx_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی فایل نسخه بر روی سرور.']);
    exit;
}

$fileUrl = 'uploads/prescriptions/' . $fileName;

try {
    $stmt = $pdo->prepare("
        INSERT INTO prescriptions 
            (user_id, pet_id, rx_file_url, clinic_name, vet_name, vet_phone, vet_license_number, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $userId, $petId, $fileUrl, $clinicName, $vetName, $vetPhone, $vetLicense
    ]);
    $rxId = (int)$pdo->lastInsertId();

    // Store in session for active checkout flow
    $_SESSION['active_prescription_id'] = $rxId;

    echo json_encode([
        'success'         => true,
        'message'         => 'نسخه دیجیتال با موفقیت بارگذاری شد و در صف بررسی داروساز قرار گرفت.',
        'prescription_id' => $rxId,
        'file_url'        => $fileUrl
    ]);
} catch (Exception $e) {
    error_log("Rx upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت اطلاعات نسخه: ' . $e->getMessage()]);
}
