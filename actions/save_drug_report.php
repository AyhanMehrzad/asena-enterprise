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

    // 2. Generate clean HTML report file for viewing & printing
    $fileName = 'drug_report_' . strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $reportSerial)) . '_' . time() . '.html';
    $uploadDir = dirname(__DIR__) . '/uploads/documents';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $reportFilePath = $uploadDir . '/' . $fileName;
    $relativeFilePath = 'uploads/documents/' . $fileName;

    $drugsHtmlList = '';
    foreach ($drugsList as $drg) {
        $drugsHtmlList .= '<li style="padding: 6px 12px; background: #f1f5f9; border-radius: 8px; margin: 4px; font-weight: bold; display: inline-block;">' . htmlspecialchars((string)$drg) . '</li>';
    }
    $reportHtml = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>' . htmlspecialchars($docTitle) . '</title><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet"><style>body{font-family:\'Vazirmatn\',sans-serif;background:#f8fafc;padding:30px;color:#0f172a;direction:rtl;}.card{max-width:700px;margin:0 auto;background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.05);padding:32px;border:1px solid #e2e8f0;}.header{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #f1f5f9;padding-bottom:16px;margin-bottom:20px;}.badge{padding:4px 12px;border-radius:999px;font-size:12px;font-weight:900;background:#eff6ff;color:#1e40af;}.alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:16px;border-radius:16px;margin-bottom:20px;line-height:1.7;font-size:13px;}</style></head><body><div class="card"><div class="header"><div><h2 style="margin:0;color:#001a48;font-weight:900;">کارنامه بالینی پایش تداخلات دارویی پت</h2><p style="margin:4px 0 0;font-size:12px;color:#64748b;">کد رهگیری: ' . htmlspecialchars($reportSerial) . ' | حیوان: ' . htmlspecialchars($petName) . ' (' . htmlspecialchars($species) . ' - ' . htmlspecialchars((string)$weightKg) . ' kg)</p></div><div class="badge">سامانه فارماکولوژی بالینی آسنا</div></div><div class="alert"><strong>خلاصه و تحلیل دارویی:</strong><p style="margin:8px 0 0;white-space:pre-line;">' . htmlspecialchars($analysisText) . '</p></div><div><h4 style="margin-bottom:8px;font-size:13px;color:#334155;">داروهای بررسی شده:</h4><ul style="list-style:none;padding:0;margin:0;">' . $drugsHtmlList . '</ul></div><div style="margin-top:24px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px dashed #e2e8f0;padding-top:16px;">این سند صرفاً جهت آگاهی بالینی و بر اساس مستندات فارماکوکینتیک دامپزشکی صادر گردیده است. دستور مصرف نهایی منحصراً در صلاحیت دکتر دامپزشک است.</div></div></body></html>';
    @file_put_contents($reportFilePath, $reportHtml);

    // 3. Insert into pet_documents
    try {
        $insDoc = $pdo->prepare("
            INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insDoc->execute([$petId, $userId, $docTitle, $fileName, $relativeFilePath]);
    } catch (Exception $e) {
        try {
            $insDoc = $pdo->prepare("
                INSERT INTO pet_documents (user_id, pet_id, title, document_type, notes, file_path, uploaded_at)
                VALUES (?, ?, ?, 'drug_interaction', ?, ?, NOW())
            ");
            $insDoc->execute([$userId, $petId, $docTitle, $notesContent, $relativeFilePath]);
        } catch (Exception $e2) {
            try {
                $insDoc = $pdo->prepare("
                    INSERT INTO pet_documents (pet_id, user_id, title, file_path)
                    VALUES (?, ?, ?, ?)
                ");
                $insDoc->execute([$petId, $userId, $docTitle, $relativeFilePath]);
            } catch (Exception $e3) {}
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'کارنامه ارزیابی تداخلات دارویی با موفقیت در پرونده سلامت پت ذخیره گردید.',
        'serial' => $reportSerial,
        'pet_id' => $petId,
        'file_path' => $relativeFilePath,
        'profile_url' => 'profile.php?tab=pets'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ثبت سند در پرونده سلامت: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
