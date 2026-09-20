<?php
/**
 * ASENA Enterprise - Save Pet Drug Interaction Analysis to Health Dossier
 * Action endpoint for archiving pharmacological interaction reports to user's pet dossier
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check authentication
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'require_login' => true,
        'message' => 'جهت ذخیره کارنامه تداخل دارویی در پرونده سلامت پت، لطفاً ابتدا وارد حساب کاربری خود شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse input
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput) && ($decoded = json_decode($rawInput, true))) {
    $inputData = $decoded;
} else {
    $inputData = $_POST;
}

// CSRF check
$csrf = $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrf)) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی (CSRF) منقضی شده است. لطفاً صفحه را تازه‌سازی فرمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$petName = trim((string)($inputData['pet_name'] ?? 'حیوان خانگی من'));
$species = in_array($inputData['species'] ?? '', ['dog', 'cat', 'horse', 'bird', 'exotic']) ? $inputData['species'] : 'dog';
$race = trim((string)($inputData['race'] ?? ''));
$weightKg = (float)($inputData['weight_kg'] ?? 0);
$overallSafety = trim((string)($inputData['overall_safety'] ?? 'safe'));
$overallSummary = trim((string)($inputData['overall_summary'] ?? ''));
$reportSerial = trim((string)($inputData['report_serial'] ?? 'ASENA-INT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));

$drugs = (array)($inputData['drugs'] ?? []);
$interactions = (array)($inputData['interactions'] ?? []);
$contraindications = (array)($inputData['contraindications'] ?? []);

$drugNames = [];
foreach ($drugs as $d) {
    $drugNames[] = is_array($d) ? ($d['name'] ?? '') : (string)$d;
}
$drugListText = implode(' + ', array_filter($drugNames));

$safetyFa = match($overallSafety) {
    'critical' => 'خطر بحرانی و منع مصرف قطعی 🔴',
    'warning' => 'احتیاط بالینی و تنظیم دوز 🟠',
    'moderate' => 'تداخل متوسط / فاصله زمانی 🟡',
    default => 'ایمن و سازگار 🟢'
};

$speciesFa = match($species) {
    'cat' => 'گربه',
    'horse' => 'اسب',
    'bird' => 'پرنده',
    'exotic' => 'اگزوتیک/جونده',
    default => 'سگ'
};

$notesContent = sprintf(
    "گزارش رسمی پایش تداخلات دارویی آسنا (%s)\nگونه: %s | نژاد: %s | وزن: %.1f کیلوگرم\nوضعیت ایمنی فارماکولوژی: %s\nداروهای بررسی‌شده: %s\n\nخلاصه ارزیابی بالینی:\n%s",
    $reportSerial,
    $speciesFa,
    $race ?: 'عمومی',
    $weightKg,
    $safetyFa,
    $drugListText,
    $overallSummary
);

if (!empty($interactions)) {
    $notesContent .= "\n\nتداخلات ثبت‌شده:\n";
    foreach ($interactions as $idx => $it) {
        $num = $idx + 1;
        $d1 = $it['drug1'] ?? '';
        $d2 = $it['drug2'] ?? '';
        $t = $it['title'] ?? 'تداخل دارویی';
        $rec = $it['recommendation'] ?? '';
        $notesContent .= "{$num}. {$d1} با {$d2}: {$t}\n   دستورالعمل: {$rec}\n";
    }
}

if (!empty($contraindications)) {
    $notesContent .= "\nموارد منع مصرف بالینی:\n";
    foreach ($contraindications as $idx => $ct) {
        $num = $idx + 1;
        $d = $ct['drug'] ?? '';
        $t = $ct['title'] ?? '';
        $act = $ct['action'] ?? '';
        $notesContent .= "{$num}. {$d}: {$t}\n   اقدام فوری: {$act}\n";
    }
}

try {
    // 1. Find or insert into user_pets
    $petId = 0;
    $chkStmt = $pdo->prepare("SELECT id FROM user_pets WHERE user_id = ? AND (name = ? OR name LIKE ?) LIMIT 1");
    $chkStmt->execute([$userId, $petName, "%$petName%"]);
    $petId = (int)$chkStmt->fetchColumn();

    if ($petId <= 0) {
        try {
            $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, race, weight_kg) VALUES (?, ?, ?, ?, ?)");
            $insPet->execute([$userId, $petName, $species, $race, $weightKg]);
            $petId = (int)$pdo->lastInsertId();
        } catch (Exception $ePet) {
            $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, weight_kg) VALUES (?, ?, ?, ?)");
            $insPet->execute([$userId, $petName, $species, $weightKg]);
            $petId = (int)$pdo->lastInsertId();
        }
    }

    // 2. Insert into pet_documents
    $docTitle = 'کارنامه بالینی پایش تداخلات دارویی (' . $reportSerial . ')';
    try {
        $insDoc = $pdo->prepare("
            INSERT INTO pet_documents (user_id, pet_id, title, document_type, notes, uploaded_at)
            VALUES (?, ?, ?, 'drug_interaction', ?, NOW())
        ");
        $insDoc->execute([$userId, $petId, $docTitle, $notesContent]);
    } catch (Exception $e) {
        // Fallback without notes column if needed
        $insDoc = $pdo->prepare("
            INSERT INTO pet_documents (user_id, pet_id, title, document_type, uploaded_at)
            VALUES (?, ?, ?, 'drug_interaction', NOW())
        ");
        $insDoc->execute([$userId, $petId, $docTitle]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'کارنامه ارزیابی تداخلات دارویی با موفقیت در پرونده سلامت پت ذخیره گردید.',
        'serial' => $reportSerial,
        'pet_id' => $petId,
        'profile_url' => 'profile.php?tab=pets'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ثبت سند در پرونده سلامت: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
