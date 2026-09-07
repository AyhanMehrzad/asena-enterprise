-- Migration 09: Add doctor_id column and index to prescriptions table
-- Ensures prescriptions can link directly to a doctor ID in addition to vet_phone

ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS doctor_id INT NULL AFTER pet_id;
ALTER TABLE prescriptions ADD INDEX IF NOT EXISTS idx_prescriptions_doctor (doctor_id);
