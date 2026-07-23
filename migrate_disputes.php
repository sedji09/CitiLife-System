<?php
require 'config/database.php';

try {
    // Update result_disputes schema to support explicit ticket status workflow
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS result_disputes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_id INT NOT NULL,
            patient_id INT NOT NULL,
            branch_id INT DEFAULT NULL,
            dispute_category VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending RadTech Review',
            assigned_role VARCHAR(50) NOT NULL DEFAULT 'radtech',
            radtech_notes TEXT DEFAULT NULL,
            resolution_notes TEXT DEFAULT NULL,
            resolved_by INT DEFAULT NULL,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_case (case_id),
            KEY idx_patient (patient_id),
            KEY idx_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Modify column type if table already existed
    $pdo->exec("ALTER TABLE result_disputes MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending RadTech Review'");
    $pdo->exec("ALTER TABLE result_disputes MODIFY COLUMN assigned_role VARCHAR(50) NOT NULL DEFAULT 'radtech'");
    
    // Add radtech_notes if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM result_disputes LIKE 'radtech_notes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE result_disputes ADD radtech_notes TEXT DEFAULT NULL AFTER assigned_role;");
    }

    // Ensure cases table has amendment columns
    $stmt = $pdo->query("SHOW COLUMNS FROM cases LIKE 'is_amended'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cases ADD is_amended TINYINT(1) NOT NULL DEFAULT 0, ADD amendment_notes TEXT DEFAULT NULL;");
    }

    echo "Strict Workflow Disputes Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
