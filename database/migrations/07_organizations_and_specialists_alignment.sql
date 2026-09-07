-- =========================================================================
-- ASENA Enterprise - Migration 07: Organizations, Specialists & Appointments Alignment
-- =========================================================================

-- 1. Extend users table role ENUM
ALTER TABLE `users` 
  MODIFY COLUMN `role` ENUM('user', 'admin', 'doctor', 'organization', 'pharmacist', 'seller') DEFAULT 'user';

-- 2. Add role_type to organization_doctors
ALTER TABLE `organization_doctors` 
  ADD COLUMN IF NOT EXISTS `role_type` VARCHAR(50) DEFAULT 'doctor' AFTER `is_head_physician`,
  ADD INDEX IF NOT EXISTS `idx_role_type` (`role_type`);

-- 3. Add specialists metadata to doctors table
ALTER TABLE `doctors` 
  ADD COLUMN IF NOT EXISTS `provider_type` VARCHAR(50) NOT NULL DEFAULT 'doctor' AFTER `specialty`,
  ADD COLUMN IF NOT EXISTS `clinic_name` VARCHAR(255) DEFAULT NULL AFTER `price`,
  ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `clinic_name`,
  ADD COLUMN IF NOT EXISTS `is_emergency` TINYINT(1) NOT NULL DEFAULT 0 AFTER `organization_id`,
  ADD COLUMN IF NOT EXISTS `bio` TEXT DEFAULT NULL AFTER `is_emergency`,
  ADD COLUMN IF NOT EXISTS `tags` VARCHAR(255) DEFAULT NULL AFTER `bio`,
  ADD COLUMN IF NOT EXISTS `services_json` LONGTEXT DEFAULT NULL AFTER `tags`,
  ADD INDEX IF NOT EXISTS `idx_provider_type` (`provider_type`),
  ADD INDEX IF NOT EXISTS `idx_org_id` (`organization_id`);

-- 4. Create doctor blocked slots table (Shifts, vacations, surgery blocks)
CREATE TABLE IF NOT EXISTS `doctor_blocked_slots` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doctor_id` INT NOT NULL,
  `organization_id` INT NULL,
  `block_date` DATE NOT NULL,
  `start_time` VARCHAR(20) DEFAULT NULL,
  `end_time` VARCHAR(20) DEFAULT NULL,
  `reason` VARCHAR(255) DEFAULT 'نوبت تلفنی / خارج از سامانه',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`doctor_id`),
  KEY `idx_org` (`organization_id`),
  CONSTRAINT `fk_block_doc` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Appointments columns alignment for organizations and escrow settlement
ALTER TABLE `appointments`
  ADD COLUMN IF NOT EXISTS `tracking_code` VARCHAR(100) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `doctor_id`,
  ADD COLUMN IF NOT EXISTS `visit_purpose` VARCHAR(255) DEFAULT NULL AFTER `pet_age`,
  ADD COLUMN IF NOT EXISTS `pet_notes` TEXT DEFAULT NULL AFTER `visit_purpose`,
  ADD COLUMN IF NOT EXISTS `fee` INT NOT NULL DEFAULT 350000 AFTER `pet_notes`,
  ADD COLUMN IF NOT EXISTS `commission_amount` INT NOT NULL DEFAULT 0 AFTER `fee`,
  ADD COLUMN IF NOT EXISTS `net_amount` INT NOT NULL DEFAULT 0 AFTER `commission_amount`,
  ADD COLUMN IF NOT EXISTS `service_type` VARCHAR(50) DEFAULT 'consultation' AFTER `net_amount`,
  ADD COLUMN IF NOT EXISTS `settlement_status` VARCHAR(50) DEFAULT 'held_in_escrow' AFTER `service_type`,
  ADD INDEX IF NOT EXISTS `idx_org_id` (`organization_id`);

-- 6. Add seller_id to products table for Marketplace Vendor Isolation
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `seller_id` INT NULL AFTER `id`,
  ADD INDEX IF NOT EXISTS `idx_seller` (`seller_id`);

