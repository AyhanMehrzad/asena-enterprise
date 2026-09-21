-- ASENA Enterprise - Migration 20: Unified Cart Item Sources & Payment Reconciliation Ledger
-- 1. Extend order_items with explicit item_source column to segregate retail products from veterinary medicines
ALTER TABLE `order_items`
    ADD COLUMN IF NOT EXISTS `item_source` VARCHAR(20) NOT NULL DEFAULT 'product' AFTER `product_id`;

-- 2. Create payment_discrepancy_logs table for auto-reconciling post-gateway payment exceptions
CREATE TABLE IF NOT EXISTS `payment_discrepancy_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `gateway_ref_id` VARCHAR(100) NOT NULL,
    `authority` VARCHAR(100) NOT NULL,
    `amount` BIGINT NOT NULL,
    `pending_order_json` LONGTEXT NULL,
    `error_message` TEXT NOT NULL,
    `status` ENUM('pending_investigation', 'refunded', 'resolved') DEFAULT 'pending_investigation',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pdl_user` (`user_id`),
    INDEX `idx_pdl_ref` (`gateway_ref_id`),
    INDEX `idx_pdl_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Self-healing indexing on order_items
CREATE INDEX IF NOT EXISTS `idx_order_items_source` ON `order_items` (`order_id`, `item_source`);
