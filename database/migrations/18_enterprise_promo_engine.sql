-- 18_enterprise_promo_engine.sql
-- Migration for ASENA Enterprise Promo Code & Coupon Engine
-- Implements platform margin absorption, caps, usage ledgers, and anti-abuse

-- 1. Upgrade / Create promo_codes table
CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `discount_type` ENUM('percentage', 'fixed_amount') NOT NULL DEFAULT 'percentage',
    `discount_value` INT NOT NULL,
    `max_discount_amount` INT DEFAULT NULL, -- Max ceiling cap in Tomans for percentage codes
    `min_order_amount` INT NOT NULL DEFAULT 0, -- Minimum cart subtotal in Tomans
    `usage_limit_total` INT DEFAULT NULL, -- NULL = unlimited global redemptions
    `usage_limit_per_user` INT NOT NULL DEFAULT 1, -- Default 1 use per customer account
    `first_order_only` TINYINT(1) NOT NULL DEFAULT 0, -- 1 = only new users with 0 prior orders
    `starts_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_promo_code` (`code`),
    INDEX `idx_promo_active` (`is_active`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add any missing columns to existing promo_codes table gracefully
SET @dbname = DATABASE();
SET @tablename = "promo_codes";

-- title column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'title') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `title` VARCHAR(150) NOT NULL DEFAULT 'تخفیف آسنا' AFTER `code`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- discount_type column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'discount_type') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `discount_type` ENUM('percentage', 'fixed_amount') NOT NULL DEFAULT 'percentage' AFTER `title`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- discount_value column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'discount_value') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `discount_value` INT NOT NULL DEFAULT 10 AFTER `discount_type`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- max_discount_amount column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'max_discount_amount') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `max_discount_amount` INT DEFAULT NULL AFTER `discount_value`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- min_order_amount column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'min_order_amount') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `min_order_amount` INT NOT NULL DEFAULT 0 AFTER `max_discount_amount`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- usage_limit_total column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'usage_limit_total') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `usage_limit_total` INT DEFAULT NULL AFTER `min_order_amount`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- usage_limit_per_user column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'usage_limit_per_user') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `usage_limit_per_user` INT NOT NULL DEFAULT 1 AFTER `usage_limit_total`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- first_order_only column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'first_order_only') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `first_order_only` TINYINT(1) NOT NULL DEFAULT 0 AFTER `usage_limit_per_user`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- starts_at column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'starts_at') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `starts_at` DATETIME DEFAULT NULL AFTER `first_order_only`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- expires_at column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'expires_at') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `expires_at` DATETIME DEFAULT NULL AFTER `starts_at`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- is_active column
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'is_active') > 0,
  "SELECT 1",
  "ALTER TABLE `promo_codes` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `expires_at`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Create promo_code_usages ledger table
CREATE TABLE IF NOT EXISTS `promo_code_usages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `promo_code_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `discount_amount` INT NOT NULL, -- in Tomans
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usage_code_user` (`promo_code_id`, `user_id`),
    INDEX `idx_usage_user` (`user_id`),
    INDEX `idx_usage_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ensure orders table has promo_code column
SET @tablename = "orders";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'promo_code') > 0,
  "SELECT 1",
  "ALTER TABLE `orders` ADD COLUMN `promo_code` VARCHAR(50) DEFAULT NULL AFTER `discount_amount`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Seed Standard Promotional Campaigns
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
