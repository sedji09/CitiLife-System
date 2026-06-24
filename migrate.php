<?php
require 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create new tables (Causes implicit commit)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `request_number` VARCHAR(50) NOT NULL UNIQUE,
            `patient_id` INT NOT NULL,
            `branch_id` INT NOT NULL,
            `exam_type` VARCHAR(100) NOT NULL,
            `priority` ENUM('Normal','Priority','STAT','Urgent','Routine') NOT NULL DEFAULT 'Normal',
            `philhealth_status` ENUM('With PhilHealth Card','Without PhilHealth Card') NOT NULL,
            `philhealth_id` VARCHAR(50) DEFAULT NULL,
            `status` ENUM('Pending Approval', 'Approved', 'Rejected', 'Needs Revision') NOT NULL DEFAULT 'Pending Approval',
            `rejection_reason` TEXT DEFAULT NULL,
            `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `approved_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `request_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `request_id` INT NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `remarks` TEXT DEFAULT NULL,
            `performed_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `branch_case_sequences` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `branch_id` INT NOT NULL,
            `year` INT NOT NULL,
            `current_number` INT NOT NULL DEFAULT 0,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `branch_year` (`branch_id`, `year`),
            FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `request_sequences` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `year` INT NOT NULL,
            `current_number` INT NOT NULL DEFAULT 0,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `year` (`year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // 2. Alter cases table (Causes implicit commit)
    $stmt = $pdo->query("SHOW COLUMNS FROM `cases` LIKE 'request_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `cases` ADD `request_id` INT NULL");
        $pdo->exec("ALTER TABLE `cases` ADD UNIQUE KEY `req_id_unique` (`request_id`)");
        $pdo->exec("ALTER TABLE `cases` ADD CONSTRAINT `fk_cases_requests` FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE SET NULL");
    }

    // Now start transaction for data migration
    $pdo->beginTransaction();

    // 3. Migrate records
    $stmt = $pdo->query("SELECT * FROM branches");
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $branchCodes = [];
    foreach ($branches as $b) {
        $name = $b['name'];
        $code = 'GEN';
        if (stripos($name, 'Gapan') !== false) $code = 'GAP';
        elseif (stripos($name, 'Bongabon') !== false) $code = 'BON';
        elseif (stripos($name, 'Peñaranda') !== false) $code = 'PEN';
        elseif (stripos($name, 'General Tinio') !== false || stripos($name, 'General Tion') !== false) $code = 'GTI';
        elseif (stripos($name, 'San Antonio') !== false) $code = 'SAN';
        elseif (stripos($name, 'Sto Domingo') !== false) $code = 'STD';
        elseif (stripos($name, 'Pantabangan') !== false) $code = 'PAN';
        $branchCodes[$b['id']] = $code;
    }

    $stmt = $pdo->query("SELECT * FROM cases ORDER BY id ASC");
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reportsDir = __DIR__ . '/public/uploads/reports';

    foreach ($cases as $c) {
        $isPending = (isset($c['approval_status']) && $c['approval_status'] === 'Pending');
        $year = date('Y', strtotime($c['created_at']));

        if ($isPending) {
            // Pending cases moved to requests
            $pdo->prepare("INSERT IGNORE INTO request_sequences (year, current_number) VALUES (?, 0)")->execute([$year]);
            $pdo->prepare("UPDATE request_sequences SET current_number = current_number + 1 WHERE year = ?")->execute([$year]);
            
            $seqStmt = $pdo->prepare("SELECT current_number FROM request_sequences WHERE year = ?");
            $seqStmt->execute([$year]);
            $seqNum = $seqStmt->fetchColumn();
            
            $reqNum = "REQ-{$year}-" . str_pad($seqNum, 5, '0', STR_PAD_LEFT);

            $stmtIns = $pdo->prepare("
                INSERT INTO requests (request_number, patient_id, branch_id, exam_type, priority, philhealth_status, philhealth_id, status, submitted_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Approval', ?, ?)
            ");
            $bId = $c['branch_id'] ?: 1; // Fallback to 1 if null
            $stmtIns->execute([
                $reqNum, $c['patient_id'], $bId, $c['exam_type'], $c['priority'], 
                $c['philhealth_status'], $c['philhealth_id'], $c['created_at'], $c['created_at']
            ]);
            $reqId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO request_logs (request_id, action, remarks, created_at) VALUES (?, 'Submitted', 'Migrated from legacy pending case', ?)")
                ->execute([$reqId, $c['created_at']]);

            // Delete from cases
            $pdo->prepare("DELETE FROM cases WHERE id = ?")->execute([$c['id']]);

        } else {
            // Already approved cases, reformat Case Number
            $bId = $c['branch_id'] ?: 1; // Fallback
            $bCode = $branchCodes[$bId] ?? 'GEN';
            
            $pdo->prepare("INSERT IGNORE INTO branch_case_sequences (branch_id, year, current_number) VALUES (?, ?, 0)")->execute([$bId, $year]);
            $pdo->prepare("UPDATE branch_case_sequences SET current_number = current_number + 1 WHERE branch_id = ? AND year = ?")->execute([$bId, $year]);
            
            $seqStmt = $pdo->prepare("SELECT current_number FROM branch_case_sequences WHERE branch_id = ? AND year = ?");
            $seqStmt->execute([$bId, $year]);
            $seqNum = $seqStmt->fetchColumn();
            
            $newCaseNum = "{$bCode}{$year}-" . str_pad($seqNum, 5, '0', STR_PAD_LEFT);
            $oldCaseNum = $c['case_number'];
            
            // Only update if different
            if ($oldCaseNum !== $newCaseNum) {
                $pdo->prepare("UPDATE cases SET case_number = ? WHERE id = ?")->execute([$newCaseNum, $c['id']]);

                // Rename files
                if (is_dir($reportsDir)) {
                    $files = glob($reportsDir . '/' . $oldCaseNum . '_page_*.jpg');
                    foreach ($files as $file) {
                        $newName = str_replace($oldCaseNum, $newCaseNum, basename($file));
                        rename($file, $reportsDir . '/' . $newName);
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $e2) {
            echo "Rollback failed: " . $e2->getMessage() . "\n";
        }
    }
}
