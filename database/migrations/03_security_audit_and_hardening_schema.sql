-- =====================================================================
-- ASENA Enterprise - Migration 03: Security Audit, Threat Intelligence & IP Ban
-- Version: 1.0.0
-- =====================================================================

-- 1. Security Audit Logs Table
CREATE TABLE IF NOT EXISTS security_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(64) NOT NULL COMMENT 'auth_fail, auth_success, waf_blocked, privilege_escalation, file_upload, csrf_mismatch, rate_limit_exceeded, role_change',
    severity ENUM('info', 'warning', 'critical', 'emergency') DEFAULT 'info',
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    request_uri VARCHAR(255) NULL,
    request_method VARCHAR(10) NULL,
    payload_summary TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Banned IPs Table
CREATE TABLE IF NOT EXISTS security_banned_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    banned_until DATETIME NOT NULL,
    banned_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_banned_until (banned_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
