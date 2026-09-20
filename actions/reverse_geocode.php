<?php
/**
 * ASENA Enterprise - High-Reliability Persian Reverse Geocoding Endpoint
 * Resolves GPS Coordinates (lat, lng) to Clean Iranian Postal Address via Map.ir
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../includes/MapService.php';

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : (isset($_POST['lat']) ? (float)$_POST['lat'] : null);
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : (isset($_POST['lng']) ? (float)$_POST['lng'] : null);

if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'مختصات جغرافیایی (عرض و طول جغرافیایی) نامعتبر است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$geo = MapService::reverseGeocode($lat, $lng);

if (!$geo || empty($geo['formatted_address'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'اطلاعات نشانی برای این نقطه مکانی یافت نشد. لطفاً نشانگر را روی خیابان یا کوچه مجاور قرار دهید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$city = $geo['city'] ?? '';
$state = $geo['province'] ?? '';
$neighbourhood = $geo['neighbourhood'] ?? '';
$road = $geo['primary_road'] ?? '';
$formattedAddress = $geo['postal_address'] ?? $geo['formatted_address'] ?? '';
$postcode = $geo['postal_code'] ?? '';

// Build full readable address
$fullAddress = !empty($geo['postal_address']) ? $geo['postal_address'] : $formattedAddress;
if (!empty($city) && !str_contains($fullAddress, $city)) {
    $fullAddress = $city . '، ' . $fullAddress;
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'source'            => $geo['source'] ?? 'map_ir',
        'latitude'          => $lat,
        'longitude'         => $lng,
        'city'              => $city,
        'state'             => $state,
        'neighbourhood'     => $neighbourhood,
        'road'              => $road,
        'house_number'      => '',
        'postal_code'       => $postcode,
        'formatted_address' => $formattedAddress,
        'full_address'      => $fullAddress,
        'raw_display_name'  => $geo['formatted_address'] ?? ''
    ]
], JSON_UNESCAPED_UNICODE);
