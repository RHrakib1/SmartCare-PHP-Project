-- SmartCare Database Migration Script
-- Adds telehealth meeting link support to appointments table

ALTER TABLE `appointments` 
ADD COLUMN `meeting_link` VARCHAR(255) NULL AFTER `status`;
