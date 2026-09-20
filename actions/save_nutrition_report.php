<?php
/**
 * ASENA Enterprise - Save Pet Clinical Nutrition & Assessment Report
 * Action endpoint for saving veterinary dietary assessment to user pet health dossier
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/MealPlanGenerator.php';

// Verify POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد ارسالی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse JSON or standard POST
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
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی (CSRF) نامعتبر یا منقضی شده است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check authentication
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'require_login' => true,
        'message' => 'جهت ذخیره کارنامه در پرونده سلامت، لطفاً ابتدا وارد حساب کاربری خود شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Extract inputs
$petName = trim((string)($inputData['pet_name'] ?? 'حیوان خانگی من'));
$species = in_array($inputData['species'] ?? '', ['dog', 'cat']) ? $inputData['species'] : 'dog';
$race = trim((string)($inputData['race'] ?? $inputData['breed'] ?? 'مشخص نشده'));
$weightKg = (float)($inputData['weight_kg'] ?? 0);
$idealWeightKg = (float)($inputData['ideal_weight_kg'] ?? $weightKg);
$bcsScore = (int)($inputData['bcs_score'] ?? 5);
$dailyCalories = (int)($inputData['daily_calories'] ?? 0);
$kibbleGrams = (int)($inputData['kibble_grams'] ?? 0);
$waterMl = (int)($inputData['water_ml'] ?? 0);
$activity = trim((string)($inputData['activity'] ?? 'neutered'));
$stage = trim((string)($inputData['stage'] ?? 'adult'));
$aiAnalysis = trim((string)($inputData['ai_analysis'] ?? ''));
$aiAnalysisObj = $inputData['ai_analysis_obj'] ?? null;
if (empty($aiAnalysisObj) && !empty($aiAnalysis)) {
    $decodedAi = json_decode($aiAnalysis, true);
    if (is_array($decodedAi)) {
        $aiAnalysisObj = $decodedAi;
    }
}
$treatCalories = (int)($inputData['treat_calories'] ?? (int)round($dailyCalories * 0.10));

if ($weightKg <= 0 || $dailyCalories <= 0) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات وزنی و کالری نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$reportSerial = 'ASENA-NUT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

// Generate Standalone Meal Plan HTML Document File
$mealPlansDir = __DIR__ . '/../uploads/meal_plans';
if (!is_dir($mealPlansDir)) {
    @mkdir($mealPlansDir, 0755, true);
}

$cleanSerialPart = strtolower(str_replace(['ASENA-', 'NUT-', 'DIET-'], '', $reportSerial));
$fileName = 'meal_plan_' . $cleanSerialPart . '_' . time() . '.html';
$relativeFilePath = 'uploads/meal_plans/' . $fileName;
$fullFilePath = $mealPlansDir . '/' . $fileName;

$htmlContent = MealPlanGenerator::generate([
    'serial' => $reportSerial,
    'pet_name' => $petName,
    'species' => $species,
    'race' => $race,
    'weight_kg' => $weightKg,
    'ideal_weight_kg' => $idealWeightKg,
    'bcs_score' => $bcsScore,
    'stage' => $stage,
    'activity' => $activity,
    'daily_calories' => $dailyCalories,
    'kibble_grams' => $kibbleGrams,
    'water_ml' => $waterMl,
    'treat_calories' => $treatCalories,
    'ai_analysis' => $aiAnalysisObj,
    'created_at' => date('Y/m/d - H:i')
]);

@file_put_contents($fullFilePath, $htmlContent);

try {
    $reportSummary = sprintf(
        "کارنامه تغذیه بالینی آسنا (%s)\nگونه: %s | نژاد: %s | مرحله زندگی: %s\nوزن جاری: %.1f کیلوگرم | وزن هدف: %.1f کیلوگرم\nشاخص وضعیت بدنی (BCS): %d/9\nکالری روزانه: %d کیلوکالری | غذای خشک: %d گرم | آب مورد نیاز: %d میلی‌لیتر%s",
        $reportSerial,
        $species === 'dog' ? 'سگ' : 'گربه',
        $race,
        $stage,
        $weightKg,
        $idealWeightKg,
        $bcsScore,
        $dailyCalories,
        $kibbleGrams,
        $waterMl,
        !empty($aiAnalysis) ? "\n\nتحلیل هوش مصنوعی بالینی:\n" . $aiAnalysis : ''
    );

    // 1. Try to find or insert into user_pets
    $petId = 0;
    $chkStmt = $pdo->prepare("SELECT id FROM user_pets WHERE user_id = ? AND (name = ? OR name LIKE ?) LIMIT 1");
    $chkStmt->execute([$userId, $petName, "%$petName%"]);
    $petId = (int)$chkStmt->fetchColumn();

    if ($petId <= 0) {
        try {
            $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, race, weight_kg) VALUES (?, ?, ?, ?, ?)");
            $insPet->execute([$userId, $petName, $species, $race, $weightKg]);
        } catch (Exception $ePet) {
            $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, weight_kg) VALUES (?, ?, ?, ?)");
            $insPet->execute([$userId, $petName, $species, $weightKg]);
        }
        $petId = (int)$pdo->lastInsertId();
    } else {
        // Update weight & race
        try {
            $updPet = $pdo->prepare("UPDATE user_pets SET weight_kg = ?, race = COALESCE(NULLIF(?, ''), race) WHERE id = ? AND user_id = ?");
            $updPet->execute([$weightKg, $race, $petId, $userId]);
        } catch (Exception $ePetUp) {
            $updPet = $pdo->prepare("UPDATE user_pets SET weight_kg = ? WHERE id = ? AND user_id = ?");
            $updPet->execute([$weightKg, $petId, $userId]);
        }
    }

    // 2. Try inserting document / record with file_path pointing to generated meal plan HTML
    $docTitle = 'جدول و برنامه غذایی بالینی (' . $reportSerial . ')';
    try {
        $insDoc = $pdo->prepare("
            INSERT INTO pet_documents (user_id, pet_id, title, document_type, notes, file_path, uploaded_at)
            VALUES (?, ?, ?, 'nutrition_assessment', ?, ?, NOW())
        ");
        $insDoc->execute([$userId, $petId, $docTitle, $reportSummary, $relativeFilePath]);
    } catch (Exception $e) {
        // Fallback if notes column doesn't exist
        try {
            $insDoc = $pdo->prepare("
                INSERT INTO pet_documents (user_id, pet_id, title, document_type, file_path, uploaded_at)
                VALUES (?, ?, ?, 'nutrition_assessment', ?, NOW())
            ");
            $insDoc->execute([$userId, $petId, $docTitle, $relativeFilePath]);
        } catch (Exception $e2) {}
    }

    echo json_encode([
        'success' => true,
        'message' => 'جدول برنامه غذایی بالینی با موفقیت صادر و به پرونده سلامت شما در پروفایل ارسال گردید.',
        'serial' => $reportSerial,
        'pet_id' => $petId,
        'file_path' => $relativeFilePath,
        'file_url' => $relativeFilePath,
        'view_url' => 'view_meal_plan.php?file=' . urlencode($fileName),
        'created_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ثبت کارنامه در سیستم: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
