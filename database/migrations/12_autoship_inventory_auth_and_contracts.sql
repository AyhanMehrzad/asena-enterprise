-- =========================================================================
-- ASENA Enterprise - Migration 12: Autoship Inventory Authentication & Electronic Contracts
-- =========================================================================

SET NAMES utf8mb4;

-- 1. Contract Acceptance Audit Table (Electronic Commerce Law Arts 6 & 12 Compliant)
CREATE TABLE IF NOT EXISTS `contract_acceptances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `contract_version` VARCHAR(20) NOT NULL,
    `contract_title` VARCHAR(255) NOT NULL,
    `signature_hash` VARCHAR(64) NOT NULL,
    `ip_address` VARCHAR(50) NOT NULL,
    `user_agent` TEXT NOT NULL,
    `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_contract` (`user_id`, `contract_version`),
    INDEX `idx_role_accepted` (`role`, `accepted_at`),
    CONSTRAINT `fk_contract_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Extend users table for fast contract version checking
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `contract_accepted_version` VARCHAR(20) NULL AFTER `verification_status`,
    ADD COLUMN IF NOT EXISTS `contract_accepted_at` DATETIME NULL AFTER `contract_accepted_version`;

-- 3. Extend products table for organization link and autoship inventory threshold
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `organization_id` INT NULL AFTER `seller_id`,
    ADD COLUMN IF NOT EXISTS `autoship_min_months_stock` INT NOT NULL DEFAULT 5 AFTER `stock`,
    ADD INDEX IF NOT EXISTS `idx_prod_org` (`organization_id`);

-- 4. Extend pharmacy_medicines table for seller link and autoship inventory threshold
ALTER TABLE `pharmacy_medicines`
    ADD COLUMN IF NOT EXISTS `seller_id` INT NULL AFTER `organization_id`,
    ADD COLUMN IF NOT EXISTS `autoship_min_months_stock` INT NOT NULL DEFAULT 5 AFTER `stock`,
    ADD INDEX IF NOT EXISTS `idx_med_seller` (`seller_id`);
