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

// Automated schema alignment v3: Self-heals Migration 20 (item_source, payment_discrepancy_logs, vet authorization)
if (!file_exists(__DIR__ . '/.schema_aligned_v3')) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payment_discrepancy_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `gateway_ref_id` VARCHAR(100) NOT NULL,
                `authority` VARCHAR(100) NOT NULL,
                `amount` BIGINT NOT NULL,
                `pending_order_json` LONGTEXT NULL,
                `error_message` TEXT NOT NULL,
                `status` ENUM('pending_investigation', 'refunded', 'resolved') DEFAULT 'pending_investigation',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_pdl_user` (`user_id`),
                INDEX `idx_pdl_ref` (`gateway_ref_id`),
                INDEX `idx_pdl_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $colsToAddV3 = [
            "ALTER TABLE `order_items` ADD COLUMN `item_source` VARCHAR(20) NOT NULL DEFAULT 'product' AFTER `product_id`",
            "ALTER TABLE `order_items` ADD INDEX `idx_order_items_source` (`order_id`, `item_source`)",
            "ALTER TABLE `prescriptions` ADD COLUMN `direct_doctor_id` INT NULL AFTER `doctor_id`",
            "ALTER TABLE `prescriptions` ADD COLUMN `authorization_status` VARCHAR(50) DEFAULT 'approved' AFTER `status`",
            "ALTER TABLE `prescriptions` ADD COLUMN `authorization_note` TEXT NULL AFTER `authorization_status`"
        ];

        foreach ($colsToAddV3 as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ignore) {}
        }
        @touch(__DIR__ . '/.schema_aligned_v3');
    } catch (Throwable $e) {}
}

// Automated schema alignment v4: Self-heals Migration 17 (payment_transactions, platform_ledger_entries, card_receipt_submissions, site_settings)
if (!file_exists(__DIR__ . '/.schema_aligned_v4')) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payment_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `order_id` INT DEFAULT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'order',
                `amount` BIGINT NOT NULL,
                `gateway_driver` VARCHAR(50) NOT NULL DEFAULT 'zarinpal',
                `authority_or_ref` VARCHAR(100) NOT NULL,
                `tracking_code` VARCHAR(100) DEFAULT NULL,
                `card_pan` VARCHAR(20) DEFAULT NULL,
                `status` ENUM('initiated', 'pending_verification', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'initiated',
                `metadata` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_user_pay` (`user_id`),
                INDEX `idx_order_pay` (`order_id`),
                INDEX `idx_auth` (`authority_or_ref`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `platform_ledger_entries` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `provider_id` INT DEFAULT NULL,
                `order_id` INT DEFAULT NULL,
                `settlement_batch_id` VARCHAR(64) DEFAULT NULL,
                `type` ENUM(
                    'customer_inflow',
                    'platform_commission',
                    'vat_collected',
                    'escrow_hold',
                    'escrow_release',
                    'payout_settlement',
                    'refund_outflow'
                ) NOT NULL,
                `amount` BIGINT NOT NULL,
                `balance_after` BIGINT NOT NULL DEFAULT 0,
                `description` VARCHAR(255) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_provider_ledger` (`provider_id`),
                INDEX `idx_order_ledger` (`order_id`),
                INDEX `idx_batch_ledger` (`settlement_batch_id`),
                INDEX `idx_created_ledger` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `card_receipt_submissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `payment_transaction_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `order_id` INT DEFAULT NULL,
                `sender_card_last4` VARCHAR(8) DEFAULT NULL,
                `bank_tracking_code` VARCHAR(64) NOT NULL,
                `receipt_image_url` VARCHAR(255) DEFAULT NULL,
                `amount` BIGINT NOT NULL,
                `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                `rejection_reason` VARCHAR(255) DEFAULT NULL,
                `reviewed_by` INT DEFAULT NULL,
                `reviewed_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_card_tx` (`payment_transaction_id`),
                INDEX `idx_card_track` (`bank_tracking_code`),
                INDEX `idx_card_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `site_settings` (
                `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
                `setting_value` TEXT DEFAULT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        @touch(__DIR__ . '/.schema_aligned_v4');
    } catch (Throwable $e) {}
}

// Automated schema alignment v5: Self-heals Migrations 18 & 19 (promo engine, orders columns, reserved_stock)
if (!file_exists(__DIR__ . '/.schema_aligned_v5')) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `promo_codes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(150) NOT NULL DEFAULT 'تخفیف آسنا',
                `discount_type` ENUM('percentage', 'fixed_amount') NOT NULL DEFAULT 'percentage',
                `discount_value` INT NOT NULL DEFAULT 10,
                `max_discount_amount` INT DEFAULT NULL,
                `min_order_amount` INT NOT NULL DEFAULT 0,
                `usage_limit_total` INT DEFAULT NULL,
                `usage_limit_per_user` INT NOT NULL DEFAULT 1,
                `first_order_only` TINYINT(1) NOT NULL DEFAULT 0,
                `starts_at` DATETIME DEFAULT NULL,
                `expires_at` DATETIME DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_promo_code` (`code`),
                INDEX `idx_promo_active` (`is_active`, `expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `promo_code_usages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `promo_code_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `order_id` INT DEFAULT NULL,
                `discount_amount` INT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_usage_code_user` (`promo_code_id`, `user_id`),
                INDEX `idx_usage_user` (`user_id`),
                INDEX `idx_usage_order` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $colsToAddV5 = [
            "ALTER TABLE `orders` ADD COLUMN `promo_code` VARCHAR(50) DEFAULT NULL AFTER `discount_amount`",
            "ALTER TABLE `orders` ADD COLUMN `discount_amount` INT DEFAULT 0 AFTER `total_amount`",
            "ALTER TABLE `orders` ADD COLUMN `tax_amount` INT DEFAULT 0 AFTER `discount_amount`",
            "ALTER TABLE `orders` ADD COLUMN `shipping_cost` INT DEFAULT 0 AFTER `tax_amount`",
            "ALTER TABLE `orders` ADD COLUMN `carrier_name` VARCHAR(100) DEFAULT NULL AFTER `shipping_cost`",
            "ALTER TABLE `orders` ADD COLUMN `gateway_ref_id` VARCHAR(100) DEFAULT NULL AFTER `status`",
            "ALTER TABLE `orders` ADD COLUMN `shipping_address` TEXT DEFAULT NULL AFTER `gateway_ref_id`",
            "ALTER TABLE `orders` ADD COLUMN `tracking_code` VARCHAR(150) DEFAULT NULL AFTER `gateway_ref_id`",
            "ALTER TABLE `products` ADD COLUMN `reserved_stock` INT NOT NULL DEFAULT 0 AFTER `stock`",
            "ALTER TABLE `pharmacy_medicines` ADD COLUMN `reserved_stock` INT NOT NULL DEFAULT 0 AFTER `stock`",
            "ALTER TABLE `payment_transactions` ADD COLUMN `order_id` INT DEFAULT NULL AFTER `user_id`"
        ];

        foreach ($colsToAddV5 as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ignore) {}
        }

        try {
            $pdo->exec("
                INSERT INTO `promo_codes` 
                (`code`, `title`, `discount_type`, `discount_value`, `max_discount_amount`, `min_order_amount`, `usage_limit_total`, `usage_limit_per_user`, `first_order_only`, `is_active`)
                VALUES
                ('WELCOME10', 'تخفیف ۱۰٪ اولین خرید از آسنا', 'percentage', 10, 50000, 100000, NULL, 1, 1, 1),
                ('ASENA15', 'تخفیف ویژه ۱۵٪ مشتریان وفادار آسنا', 'percentage', 15, 100000, 250000, NULL, 1, 0, 1),
                ('PWA-WELCOME', 'کد تخفیف ۱۰٪ نصب وب‌اپلیکیشن آسنا', 'percentage', 10, 75000, 150000, NULL, 1, 0, 1)
                ON DUPLICATE KEY UPDATE 
                    `title` = VALUES(`title`),
                    `discount_value` = VALUES(`discount_value`),
                    `max_discount_amount` = VALUES(`max_discount_amount`),
                    `is_active` = VALUES(`is_active`);
            ");
        } catch (Throwable $seedErr) {}

        @touch(__DIR__ . '/.schema_aligned_v5');
    } catch (Throwable $e) {}
}

// Schema alignment v6: Ensure autoship column alignment & zero-commission policy for Autoship
if (!file_exists(__DIR__ . '/.schema_aligned_v6')) {
    try {
        $colsToAddV6 = [
            "ALTER TABLE `orders` ADD COLUMN `order_type` VARCHAR(32) DEFAULT 'retail'",
            "ALTER TABLE `orders` ADD COLUMN `is_autoship` TINYINT(1) DEFAULT 0",
            "ALTER TABLE `order_items` ADD COLUMN `is_autoship` TINYINT(1) DEFAULT 0"
        ];
        foreach ($colsToAddV6 as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ignore) {}
        }

        // Retroactive alignment for Autoship Order #9:
        // Autoship policy: 15% discount given to buyer is funded by ASENA waiving 100% of its commission (0 Toman).
        // Seller receives 100% of money (no commission deducted).
        try {
            $pdo->exec("UPDATE `orders` SET `order_type` = 'autoship', `is_autoship` = 1 WHERE `id` = 9");
            $pdo->exec("UPDATE `order_items` SET `is_autoship` = 1, `commission_rate` = 0.00, `commission_amount` = 0, `seller_net_amount` = `price_at_purchase` * `quantity` WHERE `order_id` = 9");

            $chkEscrow = $pdo->query("SELECT id, seller_id, gross_amount, commission_amount FROM seller_escrow_ledger WHERE order_id = 9 LIMIT 1");
            $escrowRow = $chkEscrow ? $chkEscrow->fetch(PDO::FETCH_ASSOC) : null;
            if ($escrowRow && (int)$escrowRow['commission_amount'] > 0) {
                $diff = (int)$escrowRow['commission_amount'];
                $sellerId = (int)$escrowRow['seller_id'];
                $pdo->exec("UPDATE seller_escrow_ledger SET commission_amount = 0, net_seller_amount = gross_amount WHERE order_id = 9");
                // Credit seller's pending escrow wallet balance by the restored amount
                $updWallet = $pdo->prepare("UPDATE seller_wallets SET balance_pending_escrow = balance_pending_escrow + ? WHERE seller_id = ?");
                $updWallet->execute([$diff, $sellerId]);
            }
        } catch (Throwable $retroErr) {}

        @touch(__DIR__ . '/.schema_aligned_v6');
    } catch (Throwable $e) {}
}

// Schema alignment v7: Add AI License Verification columns to role_applications
if (!file_exists(__DIR__ . '/.schema_aligned_v7')) {
    try {
        $colsToAddV7 = [
            "ALTER TABLE `role_applications` ADD COLUMN `ai_status` ENUM('pending', 'verified', 'needs_review', 'rejected') DEFAULT 'pending'",
            "ALTER TABLE `role_applications` ADD COLUMN `ai_confidence` INT DEFAULT 0",
            "ALTER TABLE `role_applications` ADD COLUMN `ai_report` TEXT NULL",
            "ALTER TABLE `role_applications` ADD COLUMN `ai_data_json` LONGTEXT NULL",
            "ALTER TABLE `role_applications` ADD COLUMN `ai_verified_at` DATETIME NULL"
        ];
        foreach ($colsToAddV7 as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $ignore) {}
        }
        @touch(__DIR__ . '/.schema_aligned_v7');
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
