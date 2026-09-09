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

// Action: Veterinary Body Condition Score & Energy Calculation
if (in_array($_GET['action'] ?? '', ['calculate_health', 'calculate_dosage', 'calculate_bmi'])) {
    $species = $_GET['species'] ?? 'dog';
    $weightKg = (float)($_GET['weight_kg'] ?? 10.0);
    $activity = $_GET['activity_level'] ?? 'normal_neutered';
    $age = isset($_GET['age_years']) ? (int)$_GET['age_years'] : null;

    $healthData = PetPassportService::calculateHealthAndBcs($weightKg, $species, $age, $activity);

    echo json_encode(['success' => true, 'data' => $healthData], JSON_UNESCAPED_UNICODE);
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
