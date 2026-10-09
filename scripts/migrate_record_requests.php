<?php
require_once __DIR__ . '/../config/database.php';

echo "Connected to DB. Checking table columns for record_requests...\n";
try {
    $pdo->exec("ALTER TABLE record_requests ADD COLUMN birthdate DATE NULL AFTER patient_name");
    echo "Added birthdate column.\n";
} catch (Exception $e) {
    echo "birthdate column note: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE record_requests ADD COLUMN rejection_reason TEXT NULL AFTER status");
    echo "Added rejection_reason column.\n";
} catch (Exception $e) {
    echo "rejection_reason column note: " . $e->getMessage() . "\n";
}

$stmt = $pdo->query("DESCRIBE record_requests");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Current columns in record_requests:\n";
foreach ($cols as $col) {
    echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
