-- ASENA Enterprise - Migration 16: Telehealth Doctor Chat & Prescription Tracking
-- Alignment for Doctor consultations, 7-day appointment gatekeeper & SMS notifications

-- 1. Align tickets table for direct Doctor telehealth consultations
ALTER TABLE `tickets`
  ADD COLUMN `doctor_id` INT(11) NULL AFTER `organization_id`,
  ADD COLUMN `closed_by` INT(11) NULL AFTER `status`,
  ADD COLUMN `resolution_notes` TEXT NULL AFTER `closed_by`,
  ADD COLUMN `last_notified_at` DATETIME NULL AFTER `resolution_notes`,
  ADD INDEX `idx_tickets_doc` (`doctor_id`),
  ADD INDEX `idx_tickets_status` (`status`);

-- 2. Align ticket_messages sender_type enum to include 'doctor' and 'organization'
ALTER TABLE `ticket_messages`
  MODIFY COLUMN `sender_type` ENUM('user', 'ai', 'admin', 'doctor', 'organization') NOT NULL;

-- 3. Align prescriptions table for tracking code and quick customer lookup
ALTER TABLE `prescriptions`
  ADD COLUMN `tracking_code` VARCHAR(50) NULL AFTER `id`,
  ADD INDEX `idx_rx_tracking` (`tracking_code`);

