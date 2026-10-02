-- ASENA Enterprise - Migration 23: Website Orders & Inquiries
-- Stores purchase inquiries and setup requests for custom tenant showcase websites.

CREATE TABLE IF NOT EXISTS `website_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `archetype` VARCHAR(32) NOT NULL DEFAULT 'doctor',
    `tier` VARCHAR(32) NOT NULL DEFAULT 'standard',
    `desired_slug` VARCHAR(64) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) NULL,
    `notes` TEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_wo_status` (`status`),
    INDEX `idx_wo_phone` (`phone`),
    INDEX `idx_wo_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
