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
$mode = trim($_POST['mode'] ?? 'file_upload');
$doctorId = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$clinicName = trim($_POST['clinic_name'] ?? '');
$vetName = trim($_POST['vet_name'] ?? '');
$vetPhone = trim($_POST['vet_phone'] ?? '');
$vetLicense = trim($_POST['vet_license_number'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$petId = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : null;

// IDOR Prevention: Verify pet belongs to current user
if ($petId) {
    $petChk = $pdo->prepare("SELECT id FROM user_pets WHERE id = ? AND user_id = ?");
    $petChk->execute([$petId, $userId]);
    if (!$petChk->fetchColumn()) {
        $petId = null;
    }
}

$fileUrl = null;
$trackingCode = 'RX-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

// Branch 1: Chewy-Style Direct Doctor Verification (No Paper File Required)
if ($mode === 'direct_doctor') {
    if (!$doctorId) {
        echo json_encode(['success' => false, 'message' => 'لطفاً پزشک معالج یا کلینیک را از فهرست پزشکان همکار آسنا انتخاب فرمایید.']);
        exit;
    }

    try {
        $docStmt = $pdo->prepare("
            SELECT d.id, d.name, d.specialty, d.phone, d.license_number, o.name as org_name 
            FROM doctors d
            LEFT JOIN organizations o ON d.organization_id = o.id
            WHERE d.id = ?
        ");
        $docStmt->execute([$doctorId]);
        $docInfo = $docStmt->fetch(PDO::FETCH_ASSOC);

        if (!$docInfo) {
            echo json_encode(['success' => false, 'message' => 'پزشک انتخابی در سیستم یافت نشد.']);
            exit;
        }

        $vetName = $docInfo['name'];
        $vetPhone = $docInfo['phone'] ?? '';
        $vetLicense = $docInfo['license_number'] ?? '';
        $clinicName = $docInfo['org_name'] ?? ($docInfo['specialty'] ?? 'کلینیک همکار آسنا');
        $fileUrl = 'digital_authorization';

        $stmt = $pdo->prepare("
            INSERT INTO prescriptions 
                (tracking_code, user_id, doctor_id, pet_id, rx_file_url, clinic_name, vet_name, vet_phone, vet_license_number, status, dispensing_status, bpms_state, doctor_examination_report)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_review', 'broadcasted', ?)
        ");
        $stmt->execute([
            $trackingCode, $userId, $doctorId, $petId, $fileUrl, $clinicName, $vetName, $vetPhone, $vetLicense,
            $notes ?: 'درخواست تایید دیجیتال نسخه توسط سرپرست بیمار (Chewy-style Digital Rx)'
        ]);
        $rxId = (int)$pdo->lastInsertId();

        $_SESSION['active_prescription_id'] = $rxId;

        echo json_encode([
            'success'         => true,
            'message'         => 'درخواست تایید الکترونیک نسخه برای ' . htmlspecialchars($vetName) . ' ثبت شد و کد رهگیری به شما اختصاص یافت.',
            'prescription_id' => $rxId,
            'tracking_code'   => $trackingCode,
            'file_url'        => null,
            'mode'            => 'direct_doctor'
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Direct Rx error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت درخواست نسخه: ' . $e->getMessage()]);
        exit;
    }
}

// Branch 2: Paper Prescription File Upload
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
            (tracking_code, user_id, doctor_id, pet_id, rx_file_url, clinic_name, vet_name, vet_phone, vet_license_number, status, dispensing_status, bpms_state, doctor_examination_report)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_review', 'broadcasted', ?)
    ");
    $stmt->execute([
        $trackingCode, $userId, $doctorId, $petId, $fileUrl, $clinicName, $vetName, $vetPhone, $vetLicense,
        $notes ?: null
    ]);
    $rxId = (int)$pdo->lastInsertId();

    // Store in session for active checkout flow
    $_SESSION['active_prescription_id'] = $rxId;

    echo json_encode([
        'success'         => true,
        'message'         => 'نسخه شما با موفقیت بارگذاری شد و کد رهگیری اختصاص یافت.',
        'prescription_id' => $rxId,
        'tracking_code'   => $trackingCode,
        'file_url'        => $fileUrl,
        'mode'            => 'file_upload'
    ]);
} catch (Exception $e) {
    error_log("Rx upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت اطلاعات نسخه: ' . $e->getMessage()]);
}
