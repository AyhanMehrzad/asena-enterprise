<?php
/**
 * ASENA Enterprise - Health & Diagnostic API
 * Endpoint: GET /api/v1/health.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/App.php';

App::boot();

$startTime = microtime(true);
$response = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'environment' => getenv('APP_ENV') ?: 'production',
    'checks' => []
];

// 1. Database Check
try {
    $db = App::db();
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $response['checks']['database'] = [
        'status' => 'UP',
        'database' => $dbName
    ];
} catch (Exception $e) {
    $response['status'] = 'degraded';
    $response['checks']['database'] = [
        'status' => 'DOWN',
        'error' => $e->getMessage()
    ];
}

// 2. Cache Check
try {
    $cache = App::cache();
    $testKey = '_health_probe_' . time();
    $cache->set($testKey, 'ok', 10);
    $val = $cache->get($testKey);
    $cache->delete($testKey);

    $response['checks']['cache'] = [
        'status' => ($val === 'ok') ? 'UP' : 'DOWN'
    ];
} catch (Exception $e) {
    $response['checks']['cache'] = [
        'status' => 'DOWN',
        'error' => $e->getMessage()
    ];
}

// 3. Storage Checks
$writableDirs = [
    'uploads' => is_writable(__DIR__ . '/../../uploads'),
    'cache' => is_writable(sys_get_temp_dir())
];
$response['checks']['storage'] = $writableDirs;

$response['latency_ms'] = round((microtime(true) - $startTime) * 1000, 2);

http_response_code($response['status'] === 'healthy' ? 200 : 503);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
