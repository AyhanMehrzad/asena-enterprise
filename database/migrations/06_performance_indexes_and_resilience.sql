-- =====================================================================
-- ASENA Enterprise - Migration 06: Database Performance Optimization & SMS Delivery Logs
-- Version: 1.0.0
-- =====================================================================

-- 1. Create sms_delivery_logs table for outbound SMS tracking (Live & Sandbox)
CREATE TABLE IF NOT EXISTS sms_delivery_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    action_tag VARCHAR(50) NOT NULL,
    body_id VARCHAR(30) NULL,
    message TEXT NULL,
    is_mock TINYINT(1) DEFAULT 0,
    status ENUM('sent', 'failed', 'mock_delivered') DEFAULT 'sent',
    gateway_response VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_phone (phone),
    INDEX idx_sms_tag (action_tag),
    INDEX idx_sms_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add high-performance composite indexes across core enterprise tables
ALTER TABLE orders
    ADD INDEX IF NOT EXISTS idx_user_status (user_id, status),
    ADD INDEX IF NOT EXISTS idx_escrow_status (escrow_status, post_delivery_verified);

ALTER TABLE order_items
    ADD INDEX IF NOT EXISTS idx_order_prod (order_id, product_id),
    ADD INDEX IF NOT EXISTS idx_seller_order (seller_id, order_id);

ALTER TABLE appointments
    ADD INDEX IF NOT EXISTS idx_doc_date (doctor_id, appointment_date),
    ADD INDEX IF NOT EXISTS idx_user_status (user_id, status);

ALTER TABLE reviews
    ADD INDEX IF NOT EXISTS idx_target_rating (target_type, target_id, rating);

ALTER TABLE role_applications
    ADD INDEX IF NOT EXISTS idx_status_role (status, applied_role);

ALTER TABLE seller_escrow_ledger
    ADD INDEX IF NOT EXISTS idx_seller_eligible (seller_id, status, payout_eligible_at);
