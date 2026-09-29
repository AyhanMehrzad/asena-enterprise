-- ASENA Enterprise - Migration 21: Marketing Commission Toggle & 5% Interest Adjustment
-- 1. Decrease default platform interest / commission rate from 15% to 5%
-- 2. Add platform_commission_enabled setting (1 = enabled, 0 = disabled for marketing)
-- 3. Update organizations appointment_commission_rate default to 5.00%

-- Update organizations default appointment commission rate to 5%
ALTER TABLE `organizations`
  MODIFY COLUMN `appointment_commission_rate` DECIMAL(5,2) DEFAULT 5.00;

UPDATE `organizations`
  SET `appointment_commission_rate` = 5.00
  WHERE `appointment_commission_rate` = 15.00 OR `appointment_commission_rate` IS NULL;

-- Ensure site_settings has platform_commission_percent = 5 and platform_commission_enabled = 1
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('platform_commission_percent', '5'),
  ('platform_commission_enabled', '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

UPDATE `site_settings` SET `setting_value` = '5' WHERE `setting_key` = 'platform_commission_percent';
