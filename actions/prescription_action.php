<?php
/**
 * ASENA Enterprise - Prescription (Rx) Upload Handler
 * Chewy-Style Digital Prescription Verification
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit;
}

csrf_verify();

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

// IDOR Prevention: Verify pet belongs to current user
if ($petId) {
    $petChk = $pdo->prepare("SELECT id FROM user_pets WHERE id = ? AND user_id = ?");
    $petChk->execute([$petId, $userId]);
    if (!$petChk->fetchColumn()) {
        $petId = null;
    }
}

// Validate upload using secure MIME inspection
if (empty($_FILES['rx_file'])) {
    echo json_encode(['success' => false, 'message' => 'لطفاً تصویر یا فایل معتبر نسخه را انتخاب نمایید.']);
    exit;
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
$validation = validate_upload($_FILES['rx_file'], $allowedMimes, 10 * 1024 * 1024);

if (!$validation['ok']) {
    echo json_encode(['success' => false, 'message' => $validation['error']]);
    exit;
}

$ext = match($validation['mime']) {
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
    default           => 'bin'
};

// Ensure destination directory
$uploadDir = __DIR__ . '/../uploads/prescriptions';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique cryptographically secure filename
$fileName = 'rx_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($_FILES['rx_file']['tmp_name'], $destPath)) {
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
