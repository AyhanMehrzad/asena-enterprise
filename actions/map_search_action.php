<?php
/**
 * ASENA Enterprise - Map.ir Live Place & Street Search Endpoint
 * 
 * Provides instantaneous autocomplete suggestions for streets, alleys, and landmarks
 * across Iran with priority geographic biasing.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../includes/MapService.php';

$query = trim($_REQUEST['q'] ?? $_REQUEST['text'] ?? '');
$lat = isset($_REQUEST['lat']) && is_numeric($_REQUEST['lat']) ? (float)$_REQUEST['lat'] : null;
$lng = isset($_REQUEST['lng']) && is_numeric($_REQUEST['lng']) ? (float)$_REQUEST['lng'] : null;
$city = trim($_REQUEST['city'] ?? '');

if (empty($query) || mb_strlen($query) < 2) {
    echo json_encode([
        'status' => 'success',
        'count'  => 0,
        'results'=> []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Prepend city context if query doesn't already contain it
if (!empty($city) && !str_contains($query, $city)) {
    $searchQuery = $city . ' ' . $query;
} else {
    $searchQuery = $query;
}

$results = MapService::searchPlaces($searchQuery, $lat, $lng);

// Fallback to query without city prefix if no results
if (empty($results) && $searchQuery !== $query) {
    $results = MapService::searchPlaces($query, $lat, $lng);
}

echo json_encode([
    'status'  => 'success',
    'count'   => count($results),
    'results' => $results
], JSON_UNESCAPED_UNICODE);
