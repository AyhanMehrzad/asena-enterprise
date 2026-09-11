<?php
// Ensure secure session configuration globally before any potential output
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Load Environment Configuration
require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/IntlDateFormatterFallback.php';

// Master configuration file in root (if exists)
$rootConfig = __DIR__ . '/../../config.php';
if (file_exists($rootConfig)) {
    require_once $rootConfig;
}

$host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$dbname = defined('DB_NAME') ? DB_NAME : 'asena_premium';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Native prepared statements prevent SQLi emulation bypasses
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    // Connect directly to the database with utf8mb4 charset (standard for cPanel & local)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $pdoOptions);
} catch(PDOException $e) {
    // Try local default credentials if connection failed on localhost
    $connected = false;
    if ($host === '127.0.0.1' || $host === 'localhost') {
        foreach (array_unique([$dbname, 'asena_premium', 'petshop_db']) as $tryDb) {
            try {
                $pdo = new PDO("mysql:host=127.0.0.1;dbname=$tryDb;charset=utf8mb4", 'root', '', $pdoOptions);
                $connected = true;
                break;
            } catch (PDOException $eLocal) {}
        }
    }
    if (!$connected) {
        // If database doesn't exist on local development, try to create it
        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");
            $sqlFile = __DIR__ . '/../petshop_db.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if (!empty(trim($sql))) {
                    $pdo->exec($sql);
                }
            }
        } catch(PDOException $e2) {
            error_log('[Database Connection Error] ' . $e->getMessage() . ' | ' . $e2->getMessage());
            die("خطا در برقراری ارتباط با پایگاه‌داده. لطفاً تنظیمات پیکربندی سیستم را بررسی فرمایید.");
        }
    }
}
$GLOBALS['pdo'] = $pdo;
require_once __DIR__ . '/Feature.php';
require_once __DIR__ . '/functions.php';
?>
