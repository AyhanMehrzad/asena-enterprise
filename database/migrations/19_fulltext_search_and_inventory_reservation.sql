-- =========================================================================
-- ASENA Enterprise - Migration 19: Full-Text Catalog Search & Inventory Reservation
-- =========================================================================

SET NAMES utf8mb4;

-- 1. Segregated Inventory Reservation Pool for Autoship Commitments
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `reserved_stock` INT NOT NULL DEFAULT 0 AFTER `stock`;

ALTER TABLE `pharmacy_medicines`
    ADD COLUMN IF NOT EXISTS `reserved_stock` INT NOT NULL DEFAULT 0 AFTER `stock`;

-- 2. Full-Text Search Optimization on Catalog Tables
-- Enables high-speed MATCH() AGAINST() searches with Persian support
ALTER TABLE `products`
    ADD FULLTEXT INDEX IF NOT EXISTS `ft_products_catalog_search` (`name`, `description`, `brand`);

ALTER TABLE `pharmacy_medicines`
    ADD FULLTEXT INDEX IF NOT EXISTS `ft_pharmacy_catalog_search` (`name`, `description`, `brand`);
