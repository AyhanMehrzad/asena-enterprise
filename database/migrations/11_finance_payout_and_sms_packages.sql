-- ASENA Enterprise - Migration 11: Finance, Payout Scheduler, and SMS Packages
-- Implements:
-- 1. sms_credits in seller_wallets
-- 2. sms_usage_logs for role-based SMS tracking
-- 3. sms_package_purchases for paid SMS packages
-- 4. payout_cron_runs for idempotent Thursday 9 AM Central Bank Paya execution
-- 5. Seed default settings in site_settings

ALTER TABLE `seller_wallets` 
  ADD COLUMN IF NOT EXISTS `sms_credits` INT NOT NULL DEFAULT 0 AFTER `balance_settled_lifetime`;

ALTER TABLE `appointments` 
  ADD COLUMN IF NOT EXISTS `settlement_batch_id` INT NULL AFTER `settlement_status`,
  ADD INDEX IF NOT EXISTS `idx_settlement_batch` (`settlement_batch_id`);

CREATE TABLE IF NOT EXISTS `sms_usage_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `recipient` VARCHAR(30) NOT NULL,
  `message` TEXT NOT NULL,
  `credits_deducted` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_package_purchases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `package_name` VARCHAR(100) NOT NULL,
  `credits` INT NOT NULL,
  `price` INT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'wallet',
  `payment_ref` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payout_cron_runs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cycle_key` VARCHAR(50) NOT NULL UNIQUE,
  `batch_id` INT DEFAULT NULL,
  `total_amount` BIGINT NOT NULL DEFAULT 0,
  `seller_count` INT NOT NULL DEFAULT 0,
  `run_type` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default finance and Melipayamak settings if not present
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('admin_bank_card', '6037991199223344'),
('admin_bank_sheba', 'IR120560000000100000000001'),
('admin_bank_name', 'بانک سامان'),
('admin_bank_holder', 'شرکت توسعه تجارت الکترونیک آسنا'),
('tax_rate_percent', '9'),
('platform_commission_percent', '5'),
('auto_payout_enabled', '1'),
('auto_payout_day', '4'),
('auto_payout_time', '09:00'),
('tax_on_appointments_enabled', '1'),
('melipayamak_api_key', 'MELI_SANDBOX_ASENA_PROD_KEY_2026'),
('melipayamak_username', 'asena_enterprise'),
('melipayamak_from', '50004001'),
('melipayamak_sandbox', '0'),
('sms_price_per_unit', '850'),
('sms_pack_100_price', '85000'),
('sms_pack_500_price', '375000'),
('sms_pack_1000_price', '680000');
