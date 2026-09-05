<?php
/**
 * ASENA Enterprise - Multi-Carrier Shipping Rates API (Digikala.com Benchmark)
 * Endpoint: POST /api/v1/shipping.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/App.php';

App::boot();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$destProvince = trim($input['destination_province'] ?? 'تهران');
$weightGrams = (int)($input['weight_grams'] ?? 1000);
$originProvince = trim($input['origin_province'] ?? 'آذربایجان شرقی');
$requiresColdChain = !empty($input['requires_cold_chain']);

$shipping = App::shipping();
$rates = $shipping->getAllOptions($destProvince, $weightGrams, $requiresColdChain);

echo json_encode([
    'success' => true,
    'destination' => $destProvince,
    'origin' => $originProvince,
    'weight_grams' => $weightGrams,
    'requires_cold_chain' => $requiresColdChain,
    'carriers' => $rates
], JSON_UNESCAPED_UNICODE);
