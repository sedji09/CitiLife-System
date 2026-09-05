-- Migration: Create case_amendments table
-- Date: 2026-09-04

CREATE TABLE IF NOT EXISTS case_amendments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    dispute_id INT DEFAULT NULL,
    amended_by INT NOT NULL,
    amended_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    findings_before TEXT DEFAULT NULL,
    findings_after TEXT DEFAULT NULL,
    impression_before TEXT DEFAULT NULL,
    impression_after TEXT DEFAULT NULL,
    dicom_name_before VARCHAR(255) DEFAULT NULL,
    dicom_name_after VARCHAR(255) DEFAULT NULL,
    template_before VARCHAR(255) DEFAULT NULL,
    template_after VARCHAR(255) DEFAULT NULL,
    patient_name_before VARCHAR(255) DEFAULT NULL,
    patient_name_after VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    KEY idx_case_id (case_id),
    KEY idx_dispute_id (dispute_id),
    KEY idx_amended_by (amended_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
