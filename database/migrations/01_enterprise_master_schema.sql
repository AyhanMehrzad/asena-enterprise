-- ==============================================================================
-- ASENA Enterprise - Master Schema Migration 01
-- Benchmarked against: Chewy.com, Amazon.com, Alibaba.com, Digikala.com
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. CHEWY.COM BENCHMARK: Multi-Pet Health Records & Dossier
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pet_health_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `pet_name` VARCHAR(150) NOT NULL,
  `species` ENUM('dog', 'cat', 'bird', 'horse', 'cow', 'rabbit', 'other') DEFAULT 'dog',
  `breed` VARCHAR(150) DEFAULT NULL,
  `gender` ENUM('male', 'female', 'neutered_male', 'spayed_female') DEFAULT 'male',
  `birth_date` DATE DEFAULT NULL,
  `weight_kg` DECIMAL(5,2) DEFAULT NULL,
  `microchip_id` VARCHAR(100) DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `chronic_conditions` TEXT DEFAULT NULL,
  `rabies_tag_num` VARCHAR(100) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_pet_user` (`user_id`),
  CONSTRAINT `fk_pet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. CHEWY.COM BENCHMARK: Pet Vaccinations & Health Reminders
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pet_vaccinations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pet_id` INT NOT NULL,
  `vaccine_name` VARCHAR(200) NOT NULL,
  `administered_date` DATE NOT NULL,
  `next_due_date` DATE DEFAULT NULL,
  `vet_name` VARCHAR(150) DEFAULT NULL,
  `clinic_name` VARCHAR(200) DEFAULT NULL,
  `batch_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `reminder_sent_sms` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vac_pet` (`pet_id`),
  CONSTRAINT `fk_vac_pet` FOREIGN KEY (`pet_id`) REFERENCES `pet_health_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. CHEWY.COM BENCHMARK: Prescription (Rx) Verification & Pharmacist Queue
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `pet_id` INT DEFAULT NULL,
  `order_id` INT DEFAULT NULL,
  `rx_file_url` VARCHAR(500) NOT NULL,
  `clinic_name` VARCHAR(255) DEFAULT NULL,
  `vet_name` VARCHAR(150) DEFAULT NULL,
  `vet_phone` VARCHAR(50) DEFAULT NULL,
  `vet_license_number` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
  `pharmacist_notes` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rx_user` (`user_id`),
  KEY `idx_rx_status` (`status`),
  CONSTRAINT `fk_rx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. ALIBABA.COM BENCHMARK: Quantity-Tiered Volume Pricing
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_price_tiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `is_pharmacy` TINYINT(1) DEFAULT 0,
  `min_qty` INT NOT NULL DEFAULT 1,
  `max_qty` INT DEFAULT NULL,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `unit_price` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tier_product` (`product_id`, `is_pharmacy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. ALIBABA.COM BENCHMARK: Clinic Wholesale RFQ (Request for Quotation)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `b2b_rfqs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `clinic_name` VARCHAR(255) NOT NULL,
  `vet_license_num` VARCHAR(100) DEFAULT NULL,
  `contact_person` VARCHAR(150) NOT NULL,
  `contact_phone` VARCHAR(50) NOT NULL,
  `status` ENUM('submitted', 'under_review', 'quoted', 'accepted', 'rejected', 'expired') DEFAULT 'submitted',
  `target_delivery_date` DATE DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `proforma_invoice_num` VARCHAR(100) DEFAULT NULL,
  `quoted_total_amount` INT DEFAULT NULL,
  `quote_valid_until` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_rfq_user` (`user_id`),
  KEY `idx_rfq_status` (`status`),
  CONSTRAINT `fk_rfq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `b2b_rfq_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rfq_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `is_pharmacy` TINYINT(1) DEFAULT 0,
  `product_title` VARCHAR(255) NOT NULL,
  `requested_quantity` INT NOT NULL,
  `target_unit_price` INT DEFAULT NULL,
  `quoted_unit_price` INT DEFAULT NULL,
  `quoted_notes` VARCHAR(500) DEFAULT NULL,
  KEY `idx_rfq_item` (`rfq_id`),
  CONSTRAINT `fk_rfq_item` FOREIGN KEY (`rfq_id`) REFERENCES `b2b_rfqs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. AMAZON.COM BENCHMARK: 8-Stage Order Lifecycle Audit Trail
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_status_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `from_status` VARCHAR(50) DEFAULT NULL,
  `to_status` VARCHAR(50) NOT NULL,
  `actor_type` ENUM('system', 'admin', 'doctor', 'user', 'carrier') DEFAULT 'admin',
  `actor_id` INT DEFAULT NULL,
  `carrier_name` VARCHAR(100) DEFAULT NULL,
  `tracking_code` VARCHAR(150) DEFAULT NULL,
  `tracking_url` VARCHAR(500) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `sms_sent` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_log_order` (`order_id`),
  CONSTRAINT `fk_log_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. DIGIKALA.COM BENCHMARK: Iranian Multi-Carrier Shipping Rates
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shipping_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `carrier_code` VARCHAR(50) NOT NULL,
  `carrier_name_fa` VARCHAR(100) NOT NULL,
  `base_weight_grams` INT DEFAULT 1000,
  `base_cost` INT NOT NULL DEFAULT 45000,
  `extra_kg_cost` INT NOT NULL DEFAULT 15000,
  `inter_provincial_surcharge` INT NOT NULL DEFAULT 20000,
  `cold_chain_surcharge` INT NOT NULL DEFAULT 50000,
  `estimated_days_min` INT DEFAULT 1,
  `estimated_days_max` INT DEFAULT 3,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default Iranian carriers if empty
INSERT INTO `shipping_rates` (`carrier_code`, `carrier_name_fa`, `base_weight_grams`, `base_cost`, `extra_kg_cost`, `inter_provincial_surcharge`, `cold_chain_surcharge`, `estimated_days_min`, `estimated_days_max`, `is_active`)
SELECT 'pishtaz', 'پست پیشتاز جمهوری اسلامی ایران', 1000, 48000, 15000, 22000, 0, 2, 4, 1
WHERE NOT EXISTS (SELECT 1 FROM `shipping_rates` WHERE `carrier_code` = 'pishtaz');

INSERT INTO `shipping_rates` (`carrier_code`, `carrier_name_fa`, `base_weight_grams`, `base_cost`, `extra_kg_cost`, `inter_provincial_surcharge`, `cold_chain_surcharge`, `estimated_days_min`, `estimated_days_max`, `is_active`)
SELECT 'tipax', 'تیپاکس اکسپرس (تحویل درب منزل / کلینیک)', 1000, 65000, 20000, 25000, 0, 1, 2, 1
WHERE NOT EXISTS (SELECT 1 FROM `shipping_rates` WHERE `carrier_code` = 'tipax');

INSERT INTO `shipping_rates` (`carrier_code`, `carrier_name_fa`, `base_weight_grams`, `base_cost`, `extra_kg_cost`, `inter_provincial_surcharge`, `cold_chain_surcharge`, `estimated_days_min`, `estimated_days_max`, `is_active`)
SELECT 'express_courier', 'پیک موتوری فوری اختصاصی (الوپیک / اسنپ‌باکس)', 5000, 55000, 10000, 0, 0, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `shipping_rates` WHERE `carrier_code` = 'express_courier');

INSERT INTO `shipping_rates` (`carrier_code`, `carrier_name_fa`, `base_weight_grams`, `base_cost`, `extra_kg_cost`, `inter_provincial_surcharge`, `cold_chain_surcharge`, `estimated_days_min`, `estimated_days_max`, `is_active`)
SELECT 'cold_chain_express', 'پیک ویژه زنجیره سرد دارویی (ایزوترمال + یخ خشک)', 2000, 120000, 30000, 45000, 60000, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `shipping_rates` WHERE `carrier_code` = 'cold_chain_express');

-- ------------------------------------------------------------------------------
-- 8. DIGIKALA.COM BENCHMARK: Flash Sales (پیشنهاد شگفت‌انگیز)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flash_sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `is_pharmacy` TINYINT(1) DEFAULT 0,
  `special_price` INT NOT NULL,
  `stock_quota` INT NOT NULL DEFAULT 10,
  `claimed_count` INT NOT NULL DEFAULT 0,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `badge_text` VARCHAR(100) DEFAULT 'پیشنهاد شگفت‌انگیز',
  `is_active` TINYINT(1) DEFAULT 1,
  KEY `idx_flash_dates` (`starts_at`, `ends_at`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. DIGIKALA & AMAZON BENCHMARK: User Wallet & Transactions
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_wallets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `balance` INT NOT NULL DEFAULT 0,
  `currency` VARCHAR(10) DEFAULT 'IRT',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `wallet_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `amount` INT NOT NULL,
  `type` ENUM('deposit', 'withdrawal', 'refund', 'cashback', 'purchase') NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `reference_id` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_wallet_tx` (`wallet_id`),
  CONSTRAINT `fk_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `user_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. Core Table Enhancements (Non-breaking column additions)
-- ------------------------------------------------------------------------------
-- Ensure columns exist in orders
SET @dbname = DATABASE();

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'carrier_name') > 0,
  'SELECT 1',
  'ALTER TABLE orders ADD COLUMN carrier_name VARCHAR(100) DEFAULT NULL'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'tracking_code') > 0,
  'SELECT 1',
  'ALTER TABLE orders ADD COLUMN tracking_code VARCHAR(150) DEFAULT NULL'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'shipping_cost') > 0,
  'SELECT 1',
  'ALTER TABLE orders ADD COLUMN shipping_cost INT DEFAULT 0'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'tax_amount') > 0,
  'SELECT 1',
  'ALTER TABLE orders ADD COLUMN tax_amount INT DEFAULT 0'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure B2B MOQ in products and pharmacy_medicines
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'moq') > 0,
  'SELECT 1',
  'ALTER TABLE products ADD COLUMN moq INT DEFAULT 1'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_b2b_only') > 0,
  'SELECT 1',
  'ALTER TABLE products ADD COLUMN is_b2b_only TINYINT(1) DEFAULT 0'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'pharmacy_medicines' AND COLUMN_NAME = 'moq') > 0,
  'SELECT 1',
  'ALTER TABLE pharmacy_medicines ADD COLUMN moq INT DEFAULT 1'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'pharmacy_medicines' AND COLUMN_NAME = 'is_b2b_only') > 0,
  'SELECT 1',
  'ALTER TABLE pharmacy_medicines ADD COLUMN is_b2b_only TINYINT(1) DEFAULT 0'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure Iranian fiscal and B2B fields in users
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'national_id') > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN national_id VARCHAR(20) DEFAULT NULL'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'vet_council_number') > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN vet_council_number VARCHAR(50) DEFAULT NULL'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_verified_vet') > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN is_verified_vet TINYINT(1) DEFAULT 0'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sheba_number') > 0,
  'SELECT 1',
  'ALTER TABLE users ADD COLUMN sheba_number VARCHAR(50) DEFAULT NULL'
));
PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
