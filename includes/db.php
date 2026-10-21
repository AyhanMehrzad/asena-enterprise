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

if (!$connected && !empty($is_local)) {
    try {
        $sqliteDir = __DIR__ . '/../database';
        if (!is_dir($sqliteDir)) {
            @mkdir($sqliteDir, 0777, true);
        }
        $sqliteFile = $sqliteDir . '/local_dev.sqlite';
        $pdo = new PDO("sqlite:$sqliteFile", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (key TEXT PRIMARY KEY, value TEXT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, role TEXT, loyalty_points INTEGER, password TEXT, last_monthly_points_date TEXT, verification_status TEXT, contract_accepted_version TEXT, contract_accepted_at TEXT)");
        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
        }
        $connected = true;
    } catch (Throwable $eSqlite) {
        $lastError .= ' | SQLite fallback failed: ' . $eSqlite->getMessage();
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

// Schema alignment v8: Self-heals Migration 26 (organization_members, pharmacologist role, multi-tenant inventory, unblocked shipping)
if (!file_exists(__DIR__ . '/.schema_aligned_v8')) {
    try {
        $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        if ($isSqlite) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS organization_members (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    organization_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    member_role TEXT NOT NULL DEFAULT 'staff',
                    job_title TEXT NULL,
                    notes TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_org_user_member ON organization_members(organization_id, user_id)");
            } catch (Throwable $e) {}
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS pharmacy_medicines (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    brand TEXT,
                    price INTEGER NOT NULL DEFAULT 0,
                    image_url TEXT,
                    description TEXT,
                    requires_prescription INTEGER NOT NULL DEFAULT 0,
                    stock INTEGER NOT NULL DEFAULT 0,
                    organization_id INTEGER,
                    seller_id INTEGER,
                    category TEXT,
                    user_id INTEGER,
                    batch_number TEXT,
                    expiry_date TEXT,
                    cold_chain INTEGER NOT NULL DEFAULT 0,
                    dosage_form TEXT,
                    strength TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (Throwable $e) {}

            $sqliteCols = [
                "ALTER TABLE users ADD COLUMN name TEXT NULL",
                "ALTER TABLE users ADD COLUMN phone TEXT NULL",
                "ALTER TABLE users ADD COLUMN email TEXT NULL",
                "ALTER TABLE users ADD COLUMN organization_id INTEGER NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN organization_id INTEGER NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN user_id INTEGER NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN batch_number TEXT NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN expiry_date TEXT NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN cold_chain INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE pharmacy_medicines ADD COLUMN dosage_form TEXT NULL",
                "ALTER TABLE pharmacy_medicines ADD COLUMN strength TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN organization_id INTEGER NULL",
                "ALTER TABLE prescriptions ADD COLUMN pharmacy_id INTEGER NULL",
                "ALTER TABLE prescriptions ADD COLUMN diagnosis TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN doctor_examination_report TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN items_json TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN rx_file_url TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN vet_name TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN vet_phone TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN vet_license_number TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN clinic_name TEXT NULL",
                "ALTER TABLE prescriptions ADD COLUMN dispensing_status TEXT DEFAULT 'ready_for_pickup'",
                "ALTER TABLE prescriptions ADD COLUMN bpms_state TEXT DEFAULT 'broadcasted'",
                "ALTER TABLE prescriptions ADD COLUMN pharmacist_decision TEXT DEFAULT 'none'",
                "ALTER TABLE prescriptions ADD COLUMN shipping_unlocked INTEGER NOT NULL DEFAULT 1"
            ];
            foreach ($sqliteCols as $sql) {
                try { $pdo->exec($sql); } catch (Throwable $ignore) {}
            }
        } else {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `organization_members` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `organization_id` INT NOT NULL,
                      `user_id` INT NOT NULL,
                      `member_role` ENUM('pharmacologist', 'pharmacist', 'doctor', 'receptionist', 'manager', 'assistant_manager', 'groomer', 'staff') NOT NULL DEFAULT 'staff',
                      `job_title` VARCHAR(150) NULL,
                      `notes` TEXT NULL,
                      `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      UNIQUE KEY `uq_org_user_member` (`organization_id`, `user_id`),
                      INDEX `idx_org_members_org` (`organization_id`),
                      INDEX `idx_org_members_user` (`user_id`),
                      INDEX `idx_org_members_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (Throwable $e) {}

            $colsToAddV8 = [
                "ALTER TABLE `users` ADD COLUMN `organization_id` INT NULL AFTER `role`",
                "ALTER TABLE `users` MODIFY COLUMN `role` enum('user','admin','doctor','organization','pharmacist','pharmacologist','seller','pharmacy') DEFAULT 'user'",
                "ALTER TABLE `pharmacy_medicines` ADD COLUMN `organization_id` INT NULL AFTER `id`",
                "ALTER TABLE `pharmacy_medicines` ADD COLUMN `user_id` INT NULL AFTER `organization_id`",
                "ALTER TABLE `pharmacy_medicines` ADD INDEX `idx_pm_org` (`organization_id`)",
                "ALTER TABLE `pharmacy_medicines` ADD INDEX `idx_pm_user` (`user_id`)",
                "ALTER TABLE `prescriptions` MODIFY COLUMN `shipping_unlocked` TINYINT(1) NOT NULL DEFAULT 1"
            ];
            foreach ($colsToAddV8 as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (Throwable $ignore) {}
            }
            try {
                $pdo->exec("UPDATE `prescriptions` SET `shipping_unlocked` = 1 WHERE `shipping_unlocked` = 0");
            } catch (Throwable $ignore) {}
        }
        @touch(__DIR__ . '/.schema_aligned_v8');
    } catch (Throwable $e) {}
}

// Automated schema alignment v9: Organization Invitations, In-App Wallet Ledger, Groomer & Pansion Roles
if (!file_exists(__DIR__ . '/.schema_aligned_v9')) {
    try {
        $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        if ($isSqlite) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS organization_invitations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    organization_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    suggested_role TEXT NOT NULL DEFAULT 'staff',
                    job_title TEXT NULL,
                    proposed_shifts TEXT NULL,
                    commission_rate REAL DEFAULT 0.00,
                    notes TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'pending',
                    declined_reason TEXT NULL,
                    created_at TEXT DEFAULT (datetime('now')),
                    updated_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS wallet_transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    transaction_type TEXT NOT NULL,
                    amount INTEGER NOT NULL,
                    balance_before INTEGER NOT NULL,
                    balance_after INTEGER NOT NULL,
                    reference_module TEXT NOT NULL,
                    reference_id TEXT NULL,
                    description TEXT NOT NULL,
                    created_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS grooming_services (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    groomer_user_id INTEGER NOT NULL,
                    organization_id INTEGER NULL,
                    title TEXT NOT NULL,
                    category TEXT NOT NULL DEFAULT 'full_grooming',
                    duration_minutes INTEGER NOT NULL DEFAULT 60,
                    price INTEGER NOT NULL,
                    description TEXT NULL,
                    is_active INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS grooming_portfolio (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    groomer_user_id INTEGER NOT NULL,
                    pet_type TEXT NOT NULL DEFAULT 'dog',
                    pet_breed TEXT NULL,
                    before_image TEXT NULL,
                    after_image TEXT NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT NULL,
                    created_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS pansion_rooms (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    organization_id INTEGER NULL,
                    pansion_user_id INTEGER NOT NULL,
                    room_number TEXT NOT NULL,
                    room_title TEXT NOT NULL,
                    room_type TEXT NOT NULL DEFAULT 'standard_kennel',
                    capacity INTEGER NOT NULL DEFAULT 1,
                    price_per_night INTEGER NOT NULL,
                    description TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'available',
                    created_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS pansion_bookings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id INTEGER NOT NULL,
                    organization_id INTEGER NULL,
                    pansion_user_id INTEGER NOT NULL,
                    customer_user_id INTEGER NOT NULL,
                    pet_name TEXT NOT NULL,
                    pet_species TEXT NOT NULL DEFAULT 'dog',
                    pet_breed TEXT NULL,
                    check_in_date TEXT NOT NULL,
                    check_out_date TEXT NOT NULL,
                    nights_count INTEGER NOT NULL DEFAULT 1,
                    total_price INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'pending',
                    special_diet_notes TEXT NULL,
                    medication_notes TEXT NULL,
                    belongings_list TEXT NULL,
                    emergency_contact_phone TEXT NULL,
                    created_at TEXT DEFAULT (datetime('now'))
                );

                CREATE TABLE IF NOT EXISTS appointments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tracking_code TEXT NULL,
                    user_id INTEGER NOT NULL,
                    doctor_id INTEGER NULL,
                    organization_id INTEGER NULL,
                    appointment_date TEXT NOT NULL,
                    appointment_time TEXT NOT NULL,
                    status TEXT DEFAULT 'pending',
                    created_at TEXT DEFAULT (datetime('now')),
                    pet_type TEXT NULL,
                    pet_race TEXT NULL,
                    pet_id INTEGER NULL,
                    pet_name TEXT NULL,
                    pet_gender TEXT NULL,
                    pet_age TEXT NULL,
                    pet_weight REAL NULL,
                    visit_purpose TEXT NULL,
                    pet_notes TEXT NULL,
                    fee INTEGER NOT NULL DEFAULT 350000,
                    commission_amount INTEGER NOT NULL DEFAULT 0,
                    net_amount INTEGER NOT NULL DEFAULT 0,
                    service_type TEXT DEFAULT 'consultation',
                    settlement_status TEXT DEFAULT 'held_in_escrow',
                    settlement_batch_id INTEGER NULL,
                    doctor_diagnosis TEXT NULL,
                    doctor_prescription TEXT NULL,
                    reschedule_reason TEXT NULL,
                    rescheduled_at TEXT NULL
                );

                CREATE TABLE IF NOT EXISTS user_subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    plan_name TEXT NOT NULL,
                    amount INTEGER NOT NULL,
                    status TEXT DEFAULT 'active',
                    next_delivery_date TEXT NULL,
                    created_at TEXT DEFAULT (datetime('now')),
                    duration_months INTEGER DEFAULT 3,
                    payment_model TEXT DEFAULT 'monthly',
                    delivery_frequency TEXT DEFAULT '1_month'
                );

                CREATE TABLE IF NOT EXISTS subscription_deliveries (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    subscription_id INTEGER NOT NULL,
                    delivery_month INTEGER NOT NULL,
                    scheduled_date TEXT NULL,
                    status TEXT DEFAULT 'pending'
                );
            ");

            $sqliteAlter = [
                "ALTER TABLE users ADD COLUMN wallet_balance INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE doctors ADD COLUMN provider_type TEXT NOT NULL DEFAULT 'doctor'"
            ];
            foreach ($sqliteAlter as $sql) {
                try { $pdo->exec($sql); } catch (Throwable $e) {}
            }
        } else {
            // MySQL
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `organization_invitations` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `organization_id` INT NOT NULL,
                      `user_id` INT NOT NULL,
                      `suggested_role` ENUM('doctor', 'groomer', 'pharmacist', 'pharmacologist', 'receptionist', 'manager', 'assistant_manager', 'staff') NOT NULL DEFAULT 'staff',
                      `job_title` VARCHAR(150) NULL,
                      `proposed_shifts` TEXT NULL,
                      `commission_rate` DECIMAL(5,2) DEFAULT 0.00,
                      `notes` TEXT NULL,
                      `status` ENUM('pending', 'accepted', 'declined', 'cancelled') NOT NULL DEFAULT 'pending',
                      `declined_reason` TEXT NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      INDEX `idx_org_inv_org` (`organization_id`),
                      INDEX `idx_org_inv_user` (`user_id`),
                      INDEX `idx_org_inv_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `wallet_transactions` (
                      `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                      `user_id` INT NOT NULL,
                      `transaction_type` ENUM('deposit', 'purchase', 'refund', 'bonus', 'admin_adjustment') NOT NULL,
                      `amount` BIGINT NOT NULL,
                      `balance_before` BIGINT NOT NULL,
                      `balance_after` BIGINT NOT NULL,
                      `reference_module` VARCHAR(50) NOT NULL,
                      `reference_id` VARCHAR(100) NULL,
                      `description` VARCHAR(255) NOT NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      INDEX `idx_wallet_user_date` (`user_id`, `created_at`),
                      INDEX `idx_wallet_ref` (`reference_module`, `reference_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `grooming_services` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `groomer_user_id` INT NOT NULL,
                      `organization_id` INT NULL,
                      `title` VARCHAR(150) NOT NULL,
                      `category` ENUM('full_grooming', 'bath_blowdry', 'deshedding', 'nail_trim', 'hygiene_ears_teeth', 'styling') NOT NULL DEFAULT 'full_grooming',
                      `duration_minutes` INT NOT NULL DEFAULT 60,
                      `price` BIGINT NOT NULL,
                      `description` TEXT NULL,
                      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      INDEX `idx_grooming_user` (`groomer_user_id`),
                      INDEX `idx_grooming_org` (`organization_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `grooming_portfolio` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `groomer_user_id` INT NOT NULL,
                      `pet_type` VARCHAR(50) NOT NULL DEFAULT 'dog',
                      `pet_breed` VARCHAR(100) NULL,
                      `before_image` VARCHAR(255) NULL,
                      `after_image` VARCHAR(255) NOT NULL,
                      `title` VARCHAR(150) NOT NULL,
                      `description` TEXT NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      INDEX `idx_grooming_port_user` (`groomer_user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `pansion_rooms` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `organization_id` INT NULL,
                      `pansion_user_id` INT NOT NULL,
                      `room_number` VARCHAR(50) NOT NULL,
                      `room_title` VARCHAR(150) NOT NULL,
                      `room_type` ENUM('vip_suite', 'standard_kennel', 'cat_room', 'hospital_box', 'daycare_playpen') NOT NULL DEFAULT 'standard_kennel',
                      `capacity` INT NOT NULL DEFAULT 1,
                      `price_per_night` BIGINT NOT NULL,
                      `description` TEXT NULL,
                      `status` ENUM('available', 'occupied', 'cleaning', 'maintenance') NOT NULL DEFAULT 'available',
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      INDEX `idx_pansion_rooms_org` (`organization_id`),
                      INDEX `idx_pansion_rooms_user` (`pansion_user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                    CREATE TABLE IF NOT EXISTS `pansion_bookings` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `room_id` INT NOT NULL,
                      `organization_id` INT NULL,
                      `pansion_user_id` INT NOT NULL,
                      `customer_user_id` INT NOT NULL,
                      `pet_name` VARCHAR(100) NOT NULL,
                      `pet_species` VARCHAR(50) NOT NULL DEFAULT 'dog',
                      `pet_breed` VARCHAR(100) NULL,
                      `check_in_date` DATE NOT NULL,
                      `check_out_date` DATE NOT NULL,
                      `nights_count` INT NOT NULL DEFAULT 1,
                      `total_price` BIGINT NOT NULL,
                      `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'pending',
                      `special_diet_notes` TEXT NULL,
                      `medication_notes` TEXT NULL,
                      `belongings_list` TEXT NULL,
                      `emergency_contact_phone` VARCHAR(30) NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      INDEX `idx_pansion_book_room` (`room_id`),
                      INDEX `idx_pansion_book_dates` (`check_in_date`, `check_out_date`),
                      INDEX `idx_pansion_book_customer` (`customer_user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (Throwable $e) {}

            $mysqlAlter = [
                "ALTER TABLE `users` ADD COLUMN `wallet_balance` BIGINT NOT NULL DEFAULT 0 AFTER `loyalty_points`",
                "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user','admin','doctor','organization','pharmacist','pharmacologist','seller','pharmacy','groomer','pansion') DEFAULT 'user'",
                "ALTER TABLE `doctors` ADD COLUMN `provider_type` VARCHAR(50) NOT NULL DEFAULT 'doctor' AFTER `specialty`"
            ];
            foreach ($mysqlAlter as $sql) {
                try { $pdo->exec($sql); } catch (Throwable $e) {}
            }
        }
        @touch(__DIR__ . '/.schema_aligned_v9');
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
