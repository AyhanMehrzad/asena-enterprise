-- Migration 28: Pansion Kindergarten/Social Daycare Model and Groomer Daily Pipeline
-- 1. Alters pansion_bookings to support hourly care, stay extensions, and safety isolation for aggressive breeds
ALTER TABLE `pansion_bookings`
  ADD COLUMN `care_mode` ENUM('hourly', 'daily', 'overnight') NOT NULL DEFAULT 'hourly' AFTER `room_id`,
  ADD COLUMN `hours_count` INT NOT NULL DEFAULT 1 AFTER `nights_count`,
  ADD COLUMN `hourly_rate` BIGINT NOT NULL DEFAULT 60000 AFTER `hours_count`,
  ADD COLUMN `check_in_time` VARCHAR(10) NOT NULL DEFAULT '09:00' AFTER `check_in_date`,
  ADD COLUMN `expected_check_out_time` VARCHAR(10) NOT NULL DEFAULT '13:00' AFTER `check_out_date`,
  ADD COLUMN `actual_check_out_time` VARCHAR(10) NULL AFTER `expected_check_out_time`,
  ADD COLUMN `is_isolated_zone` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `isolation_reason` VARCHAR(255) NULL AFTER `is_isolated_zone`,
  ADD COLUMN `play_activity_notes` TEXT NULL AFTER `belongings_list`;

-- 2. Alters pansion_rooms to support zone types (social kindergarten vs solitary isolation) and hourly rates
ALTER TABLE `pansion_rooms`
  ADD COLUMN `zone_type` ENUM('social_kindergarten', 'safety_isolation', 'vip_suite', 'standard_kennel') NOT NULL DEFAULT 'social_kindergarten' AFTER `room_type`,
  ADD COLUMN `hourly_rate` BIGINT NOT NULL DEFAULT 60000 AFTER `price_per_night`;

-- 3. Creates grooming_appointments for the Groomer Daily Activity Pipeline & Kanban
CREATE TABLE IF NOT EXISTS `grooming_appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `organization_id` INT NULL,
  `groomer_user_id` INT NOT NULL,
  `customer_user_id` INT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `pet_name` VARCHAR(100) NOT NULL,
  `pet_species` VARCHAR(50) NOT NULL DEFAULT 'dog',
  `pet_breed` VARCHAR(100) NULL,
  `service_id` INT NULL,
  `service_title` VARCHAR(150) NOT NULL,
  `appointment_date` DATE NOT NULL,
  `start_time` VARCHAR(10) NOT NULL DEFAULT '10:00',
  `duration_minutes` INT NOT NULL DEFAULT 60,
  `price` BIGINT NOT NULL DEFAULT 250000,
  `status` ENUM('scheduled', 'in_grooming', 'ready_for_pickup', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  `grooming_started_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_groom_appt_user_date` (`groomer_user_id`, `appointment_date`),
  INDEX `idx_groom_appt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
