<?php
// Prevent direct execution of include file
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Global production error configuration & security header hardening
if (php_sapi_name() !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (ob_get_level() === 0) {
        ob_start();
    }
    @header_remove('X-Powered-By');
    @header_remove('X-XSS-Protection');
}

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

// Master configuration file in root (if exists)
foreach ([__DIR__ . '/../config.php', __DIR__ . '/../../config.php'] as $cfg) {
    if (file_exists($cfg)) {
        require_once $cfg;
        break;
    }
}

// Load Environment Configuration
require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/IntlDateFormatterFallback.php';

// If PDO instance is already provided (e.g. in unit tests or CLI harness), reuse it
if (isset($pdo) && $pdo instanceof PDO) {
    return;
}

$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$dbname = defined('DB_NAME') ? DB_NAME : 'asencomp_asena_db';
$user = defined('DB_USER') ? DB_USER : 'asencomp_admin';
$pass = defined('DB_PASS') ? DB_PASS : 'X3~YN,HY9M:j%jx';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Native prepared statements prevent SQLi emulation bypasses
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$connected = false;
$lastError = '';

try {
    // Connect directly to the database with utf8mb4 charset (standard for cPanel & local)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, $pdoOptions);
    $connected = true;
} catch(PDOException $e) {
    $lastError = $e->getMessage();
    // Try alternating host (e.g. localhost Unix socket vs 127.0.0.1 TCP loopback)
    $altHost = ($host === 'localhost') ? '127.0.0.1' : (($host === '127.0.0.1') ? 'localhost' : null);
    if ($altHost) {
        try {
            $pdo = new PDO("mysql:host=$altHost;dbname=$dbname;charset=utf8mb4", $user, $pass, $pdoOptions);
            $connected = true;
        } catch (PDOException $eAlt) {
            $lastError .= ' | Alt host failed: ' . $eAlt->getMessage();
        }
    }
}

if (!$connected) {
    // Try local default credentials if connection failed on localhost
    if ($host === '127.0.0.1' || $host === 'localhost') {
        foreach (array_unique([$dbname, 'asena_premium', 'petshop_db']) as $tryDb) {
            try {
                $pdo = new PDO("mysql:host=127.0.0.1;dbname=$tryDb;charset=utf8mb4", 'root', '', $pdoOptions);
                $connected = true;
                break;
            } catch (PDOException $eLocal) {}
        }
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
        $sqlFile = __DIR__ . '/../asena_enterprise_host_ready.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if (!empty(trim($sql))) {
                $pdo->exec($sql);
            }
        }
        $connected = true;
    } catch(PDOException $e2) {
        $lastError .= ' | Creation failed: ' . $e2->getMessage();
    }
}

if (!$connected || !isset($pdo)) {
    error_log('[Database Connection Error] ' . $lastError);
    if (isset($_GET['debug_db'])) {
        header('Content-Type: text/plain; charset=utf-8');
        die("DB Debug: Host=$host, DB=$dbname, User=$user\nError: " . $lastError);
    }
    die("خطا در برقراری ارتباط با پایگاه‌داده. لطفاً تنظیمات پیکربندی سیستم را بررسی فرمایید.");
}
$GLOBALS['pdo'] = $pdo;

// Automated schema alignment: runs once and self-heals any missing columns/tables on production
if (!file_exists(__DIR__ . '/.schema_aligned_v2')) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `contract_acceptances` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `role` VARCHAR(50) NOT NULL,
                `contract_version` VARCHAR(20) NOT NULL,
                `contract_title` VARCHAR(255) NOT NULL,
                `signature_hash` VARCHAR(64) NOT NULL,
                `ip_address` VARCHAR(50) NOT NULL,
                `user_agent` TEXT NOT NULL,
                `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_contract` (`user_id`, `contract_version`),
                INDEX `idx_role_accepted` (`role`, `accepted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $colsToAdd = [
            "ALTER TABLE `users` ADD COLUMN `contract_accepted_version` VARCHAR(20) NULL AFTER `verification_status`",
            "ALTER TABLE `users` ADD COLUMN `contract_accepted_at` DATETIME NULL AFTER `contract_accepted_version`",
            "ALTER TABLE `products` ADD COLUMN `organization_id` INT NULL AFTER `seller_id`",
            "ALTER TABLE `products` ADD COLUMN `autoship_min_months_stock` INT NOT NULL DEFAULT 5 AFTER `stock`",
            "ALTER TABLE `pharmacy_medicines` ADD COLUMN `organization_id` INT NULL AFTER `brand`",
            "ALTER TABLE `pharmacy_medicines` ADD COLUMN `seller_id` INT NULL AFTER `organization_id`",
            "ALTER TABLE `pharmacy_medicines` ADD COLUMN `autoship_min_months_stock` INT NOT NULL DEFAULT 5 AFTER `stock`",
            "ALTER TABLE `prescriptions` ADD COLUMN `organization_id` INT NULL AFTER `doctor_id`",
            "ALTER TABLE `prescriptions` ADD COLUMN `pharmacy_id` INT NULL AFTER `organization_id`",
            "ALTER TABLE `prescriptions` ADD COLUMN `bpms_state` VARCHAR(50) DEFAULT 'broadcasted' AFTER `status`",
            "ALTER TABLE `prescriptions` ADD COLUMN `dispensing_status` VARCHAR(50) DEFAULT 'pending_review' AFTER `status`",
            "ALTER TABLE `doctors` ADD COLUMN `license_number` VARCHAR(100) NULL AFTER `clinic_name`",
            "ALTER TABLE `tickets` ADD COLUMN `organization_id` INT NULL AFTER `user_id`",
            "ALTER TABLE `tickets` MODIFY COLUMN `mode` VARCHAR(50) DEFAULT 'admin'",
            "ALTER TABLE `users` MODIFY COLUMN `role` enum('user','admin','doctor','organization','pharmacist','seller','pharmacy') DEFAULT 'user'"
        ];

        foreach ($colsToAdd as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ignore) {}
        }
        @touch(__DIR__ . '/.schema_aligned_v2');
    } catch (Throwable $e) {}
}

require_once __DIR__ . '/Feature.php';
require_once __DIR__ . '/functions.php';

// Check persistent remember-me login if session not active
if (empty($_SESSION['user_id']) && !empty($_COOKIE['asena_remember'])) {
    require_once __DIR__ . '/AuthGuard.php';
    AuthGuard::attemptRememberLogin($pdo);
}
?>
