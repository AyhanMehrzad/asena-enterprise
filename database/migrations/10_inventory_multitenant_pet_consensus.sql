-- ==============================================================================
-- ASENA Enterprise - Migration 10: Inventory, Multi-Tenancy & Pet Clinical Governance
-- ==============================================================================

SET NAMES utf8mb4;

-- 1. Enhance user_pets table for clinical dossier & doctor consensus
ALTER TABLE `user_pets` 
    ADD COLUMN IF NOT EXISTS `weight_kg` DECIMAL(5,2) NULL AFTER `age`,
    ADD COLUMN IF NOT EXISTS `birth_date` DATE NULL AFTER `weight_kg`,
    ADD COLUMN IF NOT EXISTS `microchip_number` VARCHAR(100) NULL AFTER `birth_date`,
    ADD COLUMN IF NOT EXISTS `allergies` TEXT NULL AFTER `microchip_number`,
    ADD COLUMN IF NOT EXISTS `medical_history` TEXT NULL AFTER `allergies`,
    ADD COLUMN IF NOT EXISTS `last_doctor_id` INT NULL AFTER `medical_history`,
    ADD COLUMN IF NOT EXISTS `clinical_verified_at` DATETIME NULL AFTER `last_doctor_id`,
    ADD COLUMN IF NOT EXISTS `pending_doctor_proposal` LONGTEXT NULL AFTER `clinical_verified_at`;

-- 2. Modify appointments table for direct organization booking & pet weight snapshot
ALTER TABLE `appointments`
    ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `doctor_id`,
    ADD COLUMN IF NOT EXISTS `pet_weight` DECIMAL(5,2) NULL AFTER `pet_age`,
    MODIFY COLUMN `doctor_id` INT(11) NULL;

-- 3. Extend organizations table for direct booking & doctor roster visibility
ALTER TABLE `organizations`
    ADD COLUMN IF NOT EXISTS `hide_doctors_roster` TINYINT(1) DEFAULT 0 AFTER `is_24_7`,
    ADD COLUMN IF NOT EXISTS `direct_booking_enabled` TINYINT(1) DEFAULT 1 AFTER `hide_doctors_roster`,
    ADD COLUMN IF NOT EXISTS `consultation_fee` INT DEFAULT 250000 AFTER `direct_booking_enabled`;

-- 4. Extend products table for advanced seller inventory management
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `sku` VARCHAR(100) NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `low_stock_threshold` INT DEFAULT 5 AFTER `stock`;
