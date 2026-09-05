-- =====================================================================
-- ASENA Enterprise - Migration 05: Universal Interaction Logging & DDoS Defense
-- Version: 1.0.0
-- =====================================================================

-- 1. Create system_request_logs table for universal interaction tracking
CREATE TABLE IF NOT EXISTS system_request_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT NULL,
    user_name VARCHAR(150) NULL,
    session_id VARCHAR(128) NULL,
    action_type VARCHAR(50) DEFAULT 'page_view' COMMENT 'browse, auth, checkout, admin, api, suspicious_probe, ddos_flood',
    request_method VARCHAR(10) NOT NULL,
    request_uri VARCHAR(500) NOT NULL,
    payload_summary TEXT NULL COMMENT 'Sanitized parameters with passwords/tokens redacted',
    cf_ray VARCHAR(64) NULL COMMENT 'Cloudflare Ray ID',
    cf_country VARCHAR(10) NULL COMMENT 'Cloudflare Two-Letter Country Code',
    user_agent VARCHAR(255) NULL,
    response_code INT DEFAULT 200,
    is_suspicious TINYINT(1) DEFAULT 0,
    suspicion_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_req_ip_time (ip_address, created_at),
    INDEX idx_req_user_time (user_id, created_at),
    INDEX idx_req_session (session_id),
    INDEX idx_req_suspicious (is_suspicious, created_at),
    INDEX idx_req_action (action_type),
    INDEX idx_req_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Extend security_banned_ips table with threat categorization and Cloudflare Ray ID
ALTER TABLE security_banned_ips
    ADD COLUMN IF NOT EXISTS threat_type VARCHAR(50) DEFAULT 'manual' AFTER reason,
    ADD COLUMN IF NOT EXISTS cf_ray VARCHAR(64) NULL AFTER threat_type;
