-- ASENA Enterprise - Migration 24: Multi-Tenant Tickets Schema Alignment
-- Ensures tickets and ticket_messages tables support organization routing, custom subjects, and specialist communication

ALTER TABLE `tickets`
  ADD COLUMN IF NOT EXISTS `organization_id` INT(11) NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `subject` VARCHAR(255) NULL AFTER `organization_id`,
  ADD COLUMN IF NOT EXISTS `doctor_id` INT(11) NULL AFTER `subject`,
  ADD COLUMN IF NOT EXISTS `target_role` VARCHAR(50) NULL AFTER `doctor_id`,
  ADD COLUMN IF NOT EXISTS `target_id` INT(11) NULL AFTER `target_role`,
  ADD COLUMN IF NOT EXISTS `closed_by` INT(11) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `resolution_notes` TEXT NULL AFTER `closed_by`,
  ADD COLUMN IF NOT EXISTS `last_notified_at` DATETIME NULL AFTER `resolution_notes`,
  ADD COLUMN IF NOT EXISTS `last_user_notified_at` DATETIME NULL AFTER `last_notified_at`;

ALTER TABLE `tickets`
  MODIFY COLUMN `mode` VARCHAR(50) NOT NULL DEFAULT 'admin';

ALTER TABLE `ticket_messages`
  MODIFY COLUMN `sender_type` VARCHAR(50) NOT NULL DEFAULT 'user';

ALTER TABLE `tickets`
  ADD INDEX IF NOT EXISTS `idx_tickets_org` (`organization_id`),
  ADD INDEX IF NOT EXISTS `idx_tickets_doc` (`doctor_id`);
