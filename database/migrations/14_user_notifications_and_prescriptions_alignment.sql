-- Migration 14: User Notifications, PWA Subscriptions, Social Proof & Prescriptions Alignment
-- Resolves C-2, C-3, C-4 missing database tables and column alignment

-- 1. Create user_notifications table if not exists
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'notifications',
  `image_url` varchar(500) DEFAULT NULL,
  `target_audience` varchar(50) DEFAULT 'all',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_notif_user` (`user_id`),
  KEY `idx_user_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create pwa_subscriptions table if not exists
CREATE TABLE IF NOT EXISTS `pwa_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `endpoint` text NOT NULL,
  `p256dh` text DEFAULT NULL,
  `auth` varchar(255) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT 'unknown',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pwa_user` (`user_id`),
  KEY `idx_pwa_endpoint` (`endpoint`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create live_social_proof_events table if not exists
CREATE TABLE IF NOT EXISTS `live_social_proof_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(150) NOT NULL,
  `city` varchar(100) DEFAULT 'تهران',
  `event_type` varchar(50) NOT NULL DEFAULT 'purchase',
  `item_title` varchar(255) NOT NULL,
  `item_link` varchar(500) DEFAULT 'shop.php',
  `item_image` varchar(500) DEFAULT 'assets/images/cat-hero.jpg',
  `minutes_ago` int(11) DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sp_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Align prescriptions table columns
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL AFTER `doctor_id`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `pharmacy_id` int(11) DEFAULT NULL AFTER `organization_id`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `bpms_state` varchar(50) DEFAULT 'broadcasted' AFTER `status`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `dispensing_status` varchar(50) DEFAULT 'pending_review' AFTER `status`;
ALTER TABLE `prescriptions` ADD INDEX IF NOT EXISTS `idx_rx_org` (`organization_id`);
ALTER TABLE `prescriptions` ADD INDEX IF NOT EXISTS `idx_rx_pharmacy` (`pharmacy_id`);

-- 5. Align doctors table columns
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `license_number` varchar(100) DEFAULT NULL AFTER `clinic_name`;

-- 6. Align users table role enum to support pharmacy
ALTER TABLE `users` MODIFY COLUMN `role` enum('user','admin','doctor','organization','pharmacist','seller','pharmacy') DEFAULT 'user';
