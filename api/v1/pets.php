<?php
/**
 * ASENA Enterprise - Pet Passport & Dosage Calculator REST API (Chewy.com Benchmark)
 * Endpoint: GET /api/v1/pets.php
 * Endpoint: POST /api/v1/pets.php?action=calculate_dosage
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/App.php';

App::boot();

$petPassport = App::petPassport();

// Action: Public / Customer Dosage & BMI Calculation
if (($_GET['action'] ?? '') === 'calculate_dosage') {
    $species = $_GET['species'] ?? 'dog';
    $weightKg = (float)($_GET['weight_kg'] ?? 10.0);
    $medType = $_GET['medication_type'] ?? 'dewormer';

    // In PetPassportService: calculateDosage(drugCategory, weightKg, species)
    $dosage = $petPassport->calculateDosage($medType, $weightKg, $species);
    $dosage['dosage_display'] = ($dosage['dosage'] ?? '') . (!empty($dosage['frequency']) ? ' • ' . $dosage['frequency'] : '');

    // Veterinary Body Condition & BMI Assessment
    if ($species === 'cat') {
        if ($weightKg < 3.0) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): لاغر / زیر وزن نرمال (زیر ۳ کیلو)';
        } elseif ($weightKg <= 5.5) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): وزن استاندارد و ایده‌آل گربه بالغ (۳ تا ۵.۵ کیلو)';
        } else {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): مستعد اضافه وزن / نیازمند غذای رژیمی یا لایت (بالای ۵.۵ کیلو)';
        }
    } else {
        if ($weightKg < 4.0) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): جثه مینیاتوری / Toy (زیر ۴ کیلو)';
        } elseif ($weightKg <= 10.0) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): رده نژادی کوچک / Small Breed (۴ تا ۱۰ کیلو)';
        } elseif ($weightKg <= 25.0) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): رده نژادی متوسط / Medium Breed (۱۰ تا ۲۵ کیلو)';
        } elseif ($weightKg <= 45.0) {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): رده نژادی بزرگ / Large Breed (۲۵ تا ۴۵ کیلو)';
        } else {
            $dosage['bmi_status'] = 'شاخص وزن (BMI): رده نژادی غول‌پیکر / Giant Breed (بالای ۴۵ کیلو)';
        }
    }

    echo json_encode(['success' => true, 'data' => $dosage], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'ابتدا باید وارد حساب کاربری خود شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pets = $petPassport->getPetsByUser($userId);
echo json_encode(['success' => true, 'data' => $pets], JSON_UNESCAPED_UNICODE);
