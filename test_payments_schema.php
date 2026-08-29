<?php
require 'config/database.php';
try {
    $stmt = $pdo->query('DESCRIBE payments');
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in payments table:\n";
    foreach ($cols as $col) {
        echo "  - $col\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
