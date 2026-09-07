-- ==============================================================================
-- ASENA Enterprise - Migration 08: Organization Types & Sub-Admins Schema
-- ==============================================================================

USE asena_premium;

-- 1. Ensure organizations.type ENUM includes all standard categories
ALTER TABLE organizations 
MODIFY COLUMN type ENUM(
    'hospital',
    'clinic',
    'pharmacy',
    'shelter_charity',
    'emergency_center',
    'diagnostic_lab'
) DEFAULT 'clinic';

-- 2. Create organization_admins table for multiple admins per organization
CREATE TABLE IF NOT EXISTS organization_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    admin_role VARCHAR(50) NOT NULL DEFAULT 'manager',
    title VARCHAR(100) NULL,
    permissions_json TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_org_user (organization_id, user_id),
    INDEX idx_org_admins_org (organization_id),
    INDEX idx_org_admins_user (user_id),
    INDEX idx_org_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
