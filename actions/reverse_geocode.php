<?php
/**
 * ASENA Enterprise - High-Reliability Persian Reverse Geocoding Endpoint
 * Resolves GPS Coordinates (lat, lng) to Clean Iranian Postal Address
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

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

// Function to fetch with User-Agent and timeout
function fetchGeocodeJson(string $url): ?array {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: ASENA-Enterprise-System/1.0 (info@asena.company)',
                'Accept-Language: fa,en-US;q=0.9',
                'Accept: application/json'
            ],
            'timeout' => 4.0,
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return null;
}

// 1. Primary Query: OpenStreetMap Nominatim with Persian Language
$nominatimUrl = sprintf(
    'https://nominatim.openstreetmap.org/reverse?format=json&lat=%.6f&lon=%.6f&zoom=18&addressdetails=1&accept-language=fa',
    $lat,
    $lng
);

$res = fetchGeocodeJson($nominatimUrl);

// 2. Fallback Query: Photon / Komoot (if Nominatim fails or times out)
if (!$res || empty($res['address'])) {
    $photonUrl = sprintf(
        'https://photon.komoot.io/reverse?lat=%.6f&lon=%.6f&lang=default',
        $lat,
        $lng
    );
    $photonRes = fetchGeocodeJson($photonUrl);
    if (!empty($photonRes['features'][0]['properties'])) {
        $props = $photonRes['features'][0]['properties'];
        $res = [
            'display_name' => $props['name'] ?? '',
            'address' => [
                'city' => $props['city'] ?? $props['county'] ?? '',
                'state' => $props['state'] ?? '',
                'postcode' => $props['postcode'] ?? '',
                'road' => $props['street'] ?? $props['name'] ?? '',
                'district' => $props['district'] ?? '',
                'country' => $props['country'] ?? 'ایران'
            ]
        ];
    }
}

if (!$res || empty($res['address'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'اطلاعات نشانی برای این نقطه مکانی یافت نشد. لطفاً نشانگر را روی خیابان یا کوچه مجاور قرار دهید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$addr = $res['address'] ?? [];

// Resolve City
$city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['municipality'] ?? $addr['county'] ?? '';
$city = preg_replace('/^(شهرستان|شهر|بخش)\s+/u', '', trim($city));
if (empty($city) && !empty($addr['state'])) {
    $city = str_replace('استان ', '', $addr['state']);
}

// Resolve State/Province
$state = $addr['state'] ?? $addr['province'] ?? '';

// Resolve Postal Code (10 digits in Iran)
$postcode = preg_replace('/[^0-9]/', '', $addr['postcode'] ?? '');
if (strlen($postcode) > 10) {
    $postcode = substr($postcode, 0, 10);
}

// Resolve Street / Alley / Highway
$road = $addr['road'] ?? $addr['pedestrian'] ?? $addr['residential'] ?? $addr['path'] ?? $addr['footway'] ?? '';
$houseNumber = $addr['house_number'] ?? $addr['house_name'] ?? '';

// Resolve Suburb / Neighbourhood / Quarter
$neighbourhoodParts = [];
foreach (['neighbourhood', 'suburb', 'quarter', 'city_district'] as $key) {
    if (!empty($addr[$key])) {
        $val = trim($addr[$key]);
        // Avoid duplicate or overly broad terms
        if (!in_array($val, $neighbourhoodParts) && !str_contains($val, 'شهرستان') && !str_contains($val, 'بخش مرکزی')) {
            $neighbourhoodParts[] = $val;
        }
    }
}
$neighbourhood = implode('، ', $neighbourhoodParts);

// Build Clean Persian Address Line
$addressComponents = [];

if (!empty($neighbourhood)) {
    $addressComponents[] = $neighbourhood;
}

if (!empty($road)) {
    $roadFormatted = $road;
    if (!preg_match('/^(خیابان|بلوار|کوچه|میدان|بزرگراه|بن‌بست|بن بست|چهارراه)/u', $roadFormatted)) {
        $roadFormatted = 'خیابان ' . $roadFormatted;
    }
    if (!empty($houseNumber)) {
        $roadFormatted .= '، پلاک ' . $houseNumber;
    }
    $addressComponents[] = $roadFormatted;
}

if (empty($addressComponents)) {
    // If specific street not tagged, use cleaned display_name
    $cleanedDisplay = $res['display_name'] ?? '';
    // Remove "ایران" and country code from end
    $cleanedDisplay = preg_replace('/(،\s*ایران|\s*ایران)$/u', '', $cleanedDisplay);
    $cleanedDisplay = preg_replace('/،\s*[0-9\-]+،/u', '،', $cleanedDisplay);
    $addressComponents[] = trim($cleanedDisplay, " ،\t\n\r\0\x0B");
}

$formattedAddress = implode('، ', $addressComponents);

// Full Address with City
$fullAddressParts = [];
if (!empty($city)) {
    $fullAddressParts[] = $city;
}
$fullAddressParts[] = $formattedAddress;
$fullAddress = implode('، ', array_filter($fullAddressParts));

echo json_encode([
    'status' => 'success',
    'data' => [
        'latitude'          => $lat,
        'longitude'         => $lng,
        'city'              => $city,
        'state'             => $state,
        'neighbourhood'     => $neighbourhood,
        'road'              => $road,
        'house_number'      => $houseNumber,
        'postal_code'       => $postcode,
        'formatted_address' => $formattedAddress,
        'full_address'      => $fullAddress,
        'raw_display_name'  => $res['display_name'] ?? ''
    ]
], JSON_UNESCAPED_UNICODE);
