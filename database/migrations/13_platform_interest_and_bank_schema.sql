-- ASENA Enterprise - Migration 13: Platform Interest (15%), Organization Bank Schema & Missing Tables
-- 1. Add missing tables and columns from Migration 11
-- 2. Add bank account columns to organizations table
-- 3. Update platform interest / commission rate from 5% to 15%

-- Add bank details to organizations for admin management and payout routing
ALTER TABLE `organizations`
  ADD COLUMN IF NOT EXISTS `bank_name` VARCHAR(100) NULL AFTER `website`,
  ADD COLUMN IF NOT EXISTS `bank_sheba` VARCHAR(50) NULL AFTER `bank_name`,
  ADD COLUMN IF NOT EXISTS `bank_account_holder` VARCHAR(150) NULL AFTER `bank_sheba`,
  ADD COLUMN IF NOT EXISTS `bank_card_number` VARCHAR(30) NULL AFTER `bank_account_holder`;

-- Set default appointment commission rate on organizations to 15%
ALTER TABLE `organizations`
  MODIFY COLUMN `appointment_commission_rate` DECIMAL(5,2) DEFAULT 15.00;

UPDATE `organizations`
  SET `appointment_commission_rate` = 15.00
  WHERE `appointment_commission_rate` = 5.00 OR `appointment_commission_rate` IS NULL;

-- Add sms_credits to seller_wallets
ALTER TABLE `seller_wallets`
  ADD COLUMN IF NOT EXISTS `sms_credits` INT NOT NULL DEFAULT 0 AFTER `balance_settled_lifetime`;

-- Add settlement_batch_id to appointments
ALTER TABLE `appointments`
  ADD COLUMN IF NOT EXISTS `settlement_batch_id` INT NULL AFTER `settlement_status`,
  ADD INDEX IF NOT EXISTS `idx_settlement_batch` (`settlement_batch_id`);

-- Create sms_usage_logs if not exists
CREATE TABLE IF NOT EXISTS `sms_usage_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `recipient` VARCHAR(30) NOT NULL,
  `message` TEXT NOT NULL,
  `credits_deducted` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sms_package_purchases if not exists
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

-- Create payout_cron_runs if not exists
CREATE TABLE IF NOT EXISTS `payout_cron_runs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cycle_key` VARCHAR(50) NOT NULL UNIQUE,
  `batch_id` INT DEFAULT NULL,
  `total_amount` BIGINT NOT NULL DEFAULT 0,
  `seller_count` INT NOT NULL DEFAULT 0,
  `run_type` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure site_settings table exists
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert / Update platform commission to 15%
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('platform_commission_percent', '15'),
  ('admin_bank_card', '6037991199223344'),
  ('admin_bank_sheba', 'IR120560000000100000000001'),
  ('admin_bank_name', 'بانک سامان'),
  ('admin_bank_holder', 'شرکت توسعه تجارت الکترونیک آسنا'),
  ('tax_rate_percent', '9'),
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
  ('sms_pack_1000_price', '680000')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

UPDATE `site_settings` SET `setting_value` = '15' WHERE `setting_key` = 'platform_commission_percent';
