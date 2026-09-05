-- =====================================================================
-- ASENA Enterprise - Migration 04: Marketplace Escrow, Iran Post API & Weekly Payout
-- Version: 1.0.0
-- =====================================================================

-- 1. Modify orders table to add Post Barcode Tracking and Escrow Status fields
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS post_tracking_code VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS post_delivery_verified TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS escrow_status ENUM('pending_delivery', 'delivered_in_inspection', 'cleared_for_payout', 'settled', 'disputed') DEFAULT 'pending_delivery',
    ADD COLUMN IF NOT EXISTS escrow_cleared_at DATETIME NULL;

-- 2. Modify order_items table to link seller attribution and platform commission
ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS seller_id INT NULL,
    ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) DEFAULT 10.00,
    ADD COLUMN IF NOT EXISTS commission_amount BIGINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS seller_net_amount BIGINT DEFAULT 0;

-- 3. Create seller_wallets table for holding vendor balances and Sheba bank details
CREATE TABLE IF NOT EXISTS seller_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL UNIQUE,
    bank_name VARCHAR(100) NULL,
    bank_account_holder VARCHAR(150) NULL,
    bank_sheba VARCHAR(30) NULL COMMENT 'Format: IR followed by 24 digits',
    bank_card_number VARCHAR(20) NULL,
    balance_pending_escrow BIGINT DEFAULT 0 COMMENT 'Held in 7-day post-delivery guarantee escrow',
    balance_available_for_payout BIGINT DEFAULT 0 COMMENT 'Released from escrow, eligible for weekly payout',
    balance_settled_lifetime BIGINT DEFAULT 0 COMMENT 'Cumulative total settled through Paya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_seller_wallet (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create seller_escrow_ledger table for granular line-item tracking
CREATE TABLE IF NOT EXISTS seller_escrow_ledger (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_item_id INT NULL,
    seller_id INT NOT NULL,
    gross_amount BIGINT NOT NULL,
    commission_amount BIGINT NOT NULL,
    net_seller_amount BIGINT NOT NULL,
    status ENUM('held_in_escrow', 'released_to_available', 'settled_in_batch', 'refunded', 'disputed') DEFAULT 'held_in_escrow',
    delivered_at DATETIME NULL,
    payout_eligible_at DATETIME NULL COMMENT 'delivered_at + 7 days',
    settlement_batch_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_escrow_seller_status (seller_id, status),
    INDEX idx_escrow_order (order_id),
    INDEX idx_escrow_payout_time (payout_eligible_at),
    INDEX idx_escrow_batch (settlement_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create seller_payout_batches table for weekly Central Bank Paya settlements
CREATE TABLE IF NOT EXISTS seller_payout_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) UNIQUE NOT NULL,
    total_payout_amount BIGINT NOT NULL,
    seller_count INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',
    paya_export_file_url VARCHAR(255) NULL,
    paya_export_content TEXT NULL,
    processed_by INT NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch_code (batch_code),
    INDEX idx_batch_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
