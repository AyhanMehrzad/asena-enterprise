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

// Action: Public / Customer Dosage Calculation
if (($_GET['action'] ?? '') === 'calculate_dosage') {
    $species = $_GET['species'] ?? 'dog';
    $weightKg = (float)($_GET['weight_kg'] ?? 10.0);
    $medType = $_GET['medication_type'] ?? 'dewormer';

    $dosage = $petPassport->calculateDosage($species, $weightKg, $medType);
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
