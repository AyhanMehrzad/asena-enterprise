-- ASENA Enterprise - Migration 22: Tenant Site Builder & Showcase Microsites
-- Multi-tenant website builder for Organizations, Doctors, Pharmacists, and Sellers.

CREATE TABLE IF NOT EXISTS `tenant_sites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_type` VARCHAR(32) NOT NULL,
    `tenant_id` INT NOT NULL,
    `slug` VARCHAR(64) NOT NULL UNIQUE,
    `site_title` VARCHAR(150) NOT NULL,
    `site_tagline` VARCHAR(255) NULL,
    `logo_url` VARCHAR(255) NULL,
    `banner_url` VARCHAR(255) NULL,
    `theme_palette` VARCHAR(50) NOT NULL DEFAULT 'emerald',
    `primary_color` VARCHAR(20) NOT NULL DEFAULT '#001a48',
    `secondary_color` VARCHAR(20) NOT NULL DEFAULT '#fd8100',
    `font_family` VARCHAR(30) NOT NULL DEFAULT 'Vazirmatn',
    `layout_json` LONGTEXT NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `views_count` INT NOT NULL DEFAULT 0,
    `meta_description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_type_id` (`tenant_type`, `tenant_id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_is_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
