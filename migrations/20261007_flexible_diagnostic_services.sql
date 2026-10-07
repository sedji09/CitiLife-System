-- Migration: Make services and modalities 100% flexible for any Diagnostic Center Service (X-Ray, Ultrasound, CT Scan, ECG, 2D-Echo, Laboratory, etc.)
-- Date: 2026-10-07

-- 1. Add service_type (Modality) to xray_services table
ALTER TABLE `xray_services` 
ADD COLUMN `service_type` VARCHAR(50) NOT NULL DEFAULT 'X-Ray' AFTER `id`;

-- 2. Modify cases table to allow ANY service_type and add optional service_id Foreign Key
ALTER TABLE `cases` 
MODIFY COLUMN `service_type` VARCHAR(50) NOT NULL DEFAULT 'X-Ray',
ADD COLUMN `service_id` INT(11) NULL AFTER `branch_id`,
ADD CONSTRAINT `fk_cases_service` FOREIGN KEY (`service_id`) REFERENCES `xray_services` (`id`) ON DELETE SET NULL;

-- 3. Add service_type and service_id to requests table
ALTER TABLE `requests` 
ADD COLUMN `service_type` VARCHAR(50) NOT NULL DEFAULT 'X-Ray' AFTER `branch_id`,
ADD COLUMN `service_id` INT(11) NULL AFTER `service_type`,
ADD CONSTRAINT `fk_requests_service` FOREIGN KEY (`service_id`) REFERENCES `xray_services` (`id`) ON DELETE SET NULL;
