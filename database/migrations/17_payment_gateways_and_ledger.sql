-- 17_payment_gateways_and_ledger.sql
-- Migration for ASENA Marketplace Zero-Tax Payment Engine, Ledger, and Card-to-Card Escrow

CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'order', -- 'order', 'booking', 'subscription', 'sms_package'
    `amount` BIGINT NOT NULL, -- in Tomans
    `gateway_driver` VARCHAR(50) NOT NULL DEFAULT 'card_to_card', -- 'card_to_card', 'zarinpal', 'crypto_usdt', 'mock'
    `authority_or_ref` VARCHAR(100) NOT NULL,
    `tracking_code` VARCHAR(100) DEFAULT NULL,
    `card_pan` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('initiated', 'pending_verification', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'initiated',
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_pay` (`user_id`),
    INDEX `idx_order_pay` (`order_id`),
    INDEX `idx_auth` (`authority_or_ref`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `platform_ledger_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider_id` INT DEFAULT NULL, -- NULL if platform-wide/corporate
    `order_id` INT DEFAULT NULL,
    `settlement_batch_id` VARCHAR(64) DEFAULT NULL,
    `type` ENUM(
        'customer_inflow',
        'platform_commission',
        'vat_collected',
        'escrow_hold',
        'escrow_release',
        'payout_settlement',
        'refund_outflow'
    ) NOT NULL,
    `amount` BIGINT NOT NULL, -- in Tomans (positive for credit, negative for debit)
    `balance_after` BIGINT NOT NULL DEFAULT 0,
    `description` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_provider_ledger` (`provider_id`),
    INDEX `idx_order_ledger` (`order_id`),
    INDEX `idx_batch_ledger` (`settlement_batch_id`),
    INDEX `idx_created_ledger` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `card_receipt_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_transaction_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `sender_card_last4` VARCHAR(8) DEFAULT NULL,
    `bank_tracking_code` VARCHAR(64) NOT NULL,
    `receipt_image_url` VARCHAR(255) DEFAULT NULL,
    `amount` BIGINT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason` VARCHAR(255) DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_card_tx` (`payment_transaction_id`),
    INDEX `idx_card_track` (`bank_tracking_code`),
    INDEX `idx_card_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure default site settings exist for Zero-Tax Card-to-Card & 10% VAT
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('tax_rate_percent', '10.0'),
('active_payment_gateway', 'card_to_card'),
('card_gateway_number', '6037997512345678'),
('card_gateway_holder', 'آسنا — حساب متمرکز امانی'),
('card_gateway_bank', 'بانک ملی ایران'),
('card_gateway_shaba', 'IR120170000000123456789012'),
('card_auto_verify_threshold', '0');
