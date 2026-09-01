<?php
require_once __DIR__ . '/config/database.php';
try {
    $pdo->exec("ALTER TABLE requests ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    echo "Column added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
