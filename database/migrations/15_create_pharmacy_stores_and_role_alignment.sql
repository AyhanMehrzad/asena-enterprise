-- Migration 15: Create pharmacy_stores Table & Align User Roles
-- Ensures full database compatibility for Doctor BPMS, Pharmacist Panel, and 1-Click Auto-Login
-- Universal MySQL 5.7+ & MariaDB / MySQL 8.0+ compatible syntax

-- 1. Create pharmacy_stores table if not exists
CREATE TABLE IF NOT EXISTS `pharmacy_stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pharmacy_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed initial demo pharmacy store if none exists
INSERT INTO `pharmacy_stores` (`id`, `user_id`, `name`, `license_number`, `status`, `phone`, `address`, `created_at`)
SELECT 1, u.id, 'داروخانه تخصصی دامپزشکی حکیم', 'PH-1403-8871', 'active', '09120000004', 'تهران، خیابان ولیعصر، جنب مرکز درمانی آسنا', NOW()
FROM users u
WHERE u.phone IN ('09120000004', '09120000008') OR u.role IN ('pharmacist', 'pharmacy')
LIMIT 1
ON DUPLICATE KEY UPDATE `status` = 'active';

-- 3. Align users table role enum to support pharmacy and pharmacist
ALTER TABLE `users` MODIFY COLUMN `role` enum('user','admin','doctor','organization','pharmacist','seller','pharmacy') DEFAULT 'user';

-- 4. Auto-heal any empty roles in users table to 'user'
UPDATE `users` SET `role` = 'user' WHERE `role` = '' OR `role` IS NULL;
