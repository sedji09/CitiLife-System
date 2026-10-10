<?php
require_once __DIR__ . '/../config/database.php';

try {
    $columns = [
        'is_claimed' => "TINYINT(1) NOT NULL DEFAULT 0",
        'claimed_at' => "DATETIME NULL",
        'claimed_by' => "VARCHAR(255) NULL",
        'claimed_relationship' => "VARCHAR(100) NULL",
        'claimed_id_presented' => "VARCHAR(255) NULL",
        'claimed_notes' => "TEXT NULL",
        'claimed_by_user_id' => "INT NULL"
    ];

    foreach ($columns as $col => $type) {
        $check = $pdo->prepare("SHOW COLUMNS FROM cases LIKE ?");
        $check->execute([$col]);
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE cases ADD COLUMN `$col` $type");
            echo "Added column `$col` to `cases` table.\n";
        } else {
            echo "Column `$col` already exists in `cases` table.\n";
        }
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
