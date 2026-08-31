<?php
require 'config/database.php';

// Find all records with philhealth_id set
$stmt = $pdo->query("SELECT id, philhealth_id, philhealth_relation, status, created_at FROM requests WHERE philhealth_id IS NOT NULL AND philhealth_id != '' ORDER BY created_at DESC LIMIT 10");
echo "=== REQUESTS ===\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query("SELECT id, philhealth_id, philhealth_relation, status, created_at FROM cases WHERE philhealth_id IS NOT NULL AND philhealth_id != '' ORDER BY created_at DESC LIMIT 10");
echo "=== CASES ===\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
