-- Migration: Add missing columns to cases table for full compatibility (Standard MySQL 8.0+)
ALTER TABLE `cases` 
ADD COLUMN `report_status` ENUM('Draft', 'Final', 'Released') DEFAULT 'Draft' AFTER `status`,
ADD COLUMN `pdf_path` VARCHAR(255) DEFAULT NULL AFTER `report_status`,
ADD COLUMN `radtech_submitted_at` DATETIME DEFAULT NULL AFTER `request_id`,
ADD COLUMN `is_amended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `radtech_submitted_at`,
ADD COLUMN `amendment_notes` TEXT DEFAULT NULL AFTER `is_amended`,
ADD COLUMN `re_edit_reason` TEXT DEFAULT NULL AFTER `amendment_notes`,
ADD COLUMN `philhealth_relation` ENUM('Principal Member', 'Qualified Dependent') DEFAULT NULL AFTER `re_edit_reason`,
ADD COLUMN `status_timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `philhealth_relation`;
