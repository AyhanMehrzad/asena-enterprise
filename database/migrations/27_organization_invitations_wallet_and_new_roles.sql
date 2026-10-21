-- Migration 27: Organization Personnel Invitations, In-App Wallet & Refunds, and Groomer & Pansion Roles
-- 1. Updates users table with wallet_balance and new roles ('groomer', 'pansion')
-- 2. Creates organization_invitations table for two-way verified personnel handshake
-- 3. Creates wallet_transactions table for closed-loop in-app refund and Zibal top-up
-- 4. Creates grooming_services, grooming_portfolio, pansion_rooms, and pansion_bookings tables

-- 1. Update users table
ALTER TABLE `users` 
  ADD COLUMN `wallet_balance` BIGINT NOT NULL DEFAULT 0 AFTER `loyalty_points`;

ALTER TABLE `users` 
  MODIFY COLUMN `role` ENUM('user','admin','doctor','organization','pharmacist','pharmacologist','seller','pharmacy','groomer','pansion') DEFAULT 'user';

-- 2. Organization Invitations (Handshake engine between clinics and specialists)
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

-- 3. Wallet Transactions Ledger (Immutable financial audit trail)
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `transaction_type` ENUM('deposit', 'purchase', 'refund', 'bonus', 'admin_adjustment') NOT NULL,
  `amount` BIGINT NOT NULL, -- Tomans
  `balance_before` BIGINT NOT NULL,
  `balance_after` BIGINT NOT NULL,
  `reference_module` VARCHAR(50) NOT NULL, -- order, booking, subscription, diet_calculator, zibal_topup
  `reference_id` VARCHAR(100) NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_wallet_user_date` (`user_id`, `created_at`),
  INDEX `idx_wallet_ref` (`reference_module`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Grooming Services & Portfolio
CREATE TABLE IF NOT EXISTS `grooming_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `groomer_user_id` INT NOT NULL,
  `organization_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `category` ENUM('full_grooming', 'bath_blowdry', 'deshedding', 'nail_trim', 'hygiene_ears_teeth', 'styling') NOT NULL DEFAULT 'full_grooming',
  `duration_minutes` INT NOT NULL DEFAULT 60,
  `price` BIGINT NOT NULL, -- Tomans
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

-- 5. Pansion Rooms & Bookings
CREATE TABLE IF NOT EXISTS `pansion_rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `organization_id` INT NULL,
  `pansion_user_id` INT NOT NULL,
  `room_number` VARCHAR(50) NOT NULL,
  `room_title` VARCHAR(150) NOT NULL,
  `room_type` ENUM('vip_suite', 'standard_kennel', 'cat_room', 'hospital_box', 'daycare_playpen') NOT NULL DEFAULT 'standard_kennel',
  `capacity` INT NOT NULL DEFAULT 1,
  `price_per_night` BIGINT NOT NULL, -- Tomans
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
