-- Migration 26: Organization Members, Staff Assignment & Pharmacologist Inventory
-- Decouples BPMS prescription locks, enables pharmacologist/pharmacist roles,
-- and allows organizations to assign and manage staff/specialists directly from the organization panel.

-- 1. Ensure users table supports pharmacologist and organization_id
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `role`;

ALTER TABLE `users` 
  MODIFY COLUMN `role` enum('user','admin','doctor','organization','pharmacist','pharmacologist','seller','pharmacy') DEFAULT 'user';

-- 2. Create organization_members table for assigning staff/specialists to organizations
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

-- 3. Ensure pharmacy_medicines has organization_id and user_id for multi-tenant inventory
ALTER TABLE `pharmacy_medicines` 
  ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `organization_id`;

ALTER TABLE `pharmacy_medicines`
  ADD INDEX IF NOT EXISTS `idx_pm_org` (`organization_id`),
  ADD INDEX IF NOT EXISTS `idx_pm_user` (`user_id`);

-- 4. Unlock all existing prescription shipping locks and set default to 1 (BPMS shipping lock decommissioned)
ALTER TABLE `prescriptions` 
  MODIFY COLUMN `shipping_unlocked` TINYINT(1) NOT NULL DEFAULT 1;

UPDATE `prescriptions` SET `shipping_unlocked` = 1 WHERE `shipping_unlocked` = 0;
