<?php
/**
 * ASENA Enterprise - Pet Drug Interaction & Contraindication AI Analysis Endpoint
 *
 * Handles:
 * 1. Action: analyze (Comprehensive drug-drug and drug-disease interaction check)
 * 2. Action: search (Live autocomplete search from pharmacy_medicines catalog)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/DrugInteractionService.php';

// Parse JSON or standard POST
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput) && ($decoded = json_decode($rawInput, true))) {
    $inputData = $decoded;
} else {
    $inputData = array_merge($_GET, $_POST);
}

$action = $inputData['action'] ?? 'analyze';

// ── Search Medicines Autocomplete Action ─────────────────────────────────────
if ($action === 'search') {
    $q = trim((string)($inputData['q'] ?? $inputData['query'] ?? ''));
    if (mb_strlen($q) < 2) {
        echo json_encode(['success' => true, 'results' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $cleanQ = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $species = trim((string)($inputData['species'] ?? ''));

        $sql = "SELECT id, name, category, brand, target_animal, price, image_url 
                FROM pharmacy_medicines 
                WHERE (name LIKE ? OR description LIKE ? OR brand LIKE ?)";
        $params = [$cleanQ, $cleanQ, $cleanQ];

        if (!empty($species) && in_array($species, ['dog', 'cat', 'horse', 'chick', 'cow'], true)) {
            $sql .= " AND (target_animal = ? OR target_animal = 'all' OR target_animal IS NULL)";
            $params[] = $species;
        }

        $sql .= " ORDER BY (name LIKE ?) DESC, id DESC LIMIT 12";
        $params[] = $q . '%';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'results' => $medicines
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        // Return empty results gracefully
        echo json_encode(['success' => true, 'results' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── Drug Interaction Analysis Action ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد ارسالی نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF check
$csrf = $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrf)) {
    echo json_encode([
        'success' => false,
        'message' => 'توکن امنیتی (CSRF) نامعتبر است. لطفاً صفحه را تازه‌سازی فرمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawDrugs = $inputData['drugs'] ?? [];
if (is_string($rawDrugs)) {
    $decoded = json_decode($rawDrugs, true);
    if (is_array($decoded)) $rawDrugs = $decoded;
}

if (!is_array($rawDrugs) || empty($rawDrugs)) {
    echo json_encode([
        'success' => false,
        'message' => 'لطفاً حداقل یک یا دو داروی مصرفی را جهت بررسی وارد فرمایید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$petInfo = [
    'pet_name' => trim((string)($inputData['pet_name'] ?? 'حیوان خانگی')),
    'species' => in_array($inputData['species'] ?? '', ['dog', 'cat', 'horse', 'bird', 'exotic']) ? $inputData['species'] : 'dog',
    'race' => trim((string)($inputData['race'] ?? '')),
    'weight_kg' => (float)($inputData['weight_kg'] ?? 0),
    'age_stage' => trim((string)($inputData['age_stage'] ?? 'adult')),
    'conditions' => is_array($inputData['conditions'] ?? null) ? $inputData['conditions'] : [],
    'user_notes' => trim((string)($inputData['user_notes'] ?? ''))
];

$service = new DrugInteractionService($pdo);
$result = $service->analyzeInteractions($rawDrugs, $petInfo);

// Attach tracking ID
$result['report_serial'] = 'ASENA-INT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$result['analyzed_at'] = date('Y/m/d - H:i');

echo json_encode($result, JSON_UNESCAPED_UNICODE);
