<?php
require_once __DIR__ . '/../config/database.php';
global $pdo;

echo "<h2>Database Reset Script (Patient Records & Accounts Only)</h2>";

try {
    echo "Connected sa database!<br>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // 1. Burahin ang Patient Accounts sa "users" table nang hindi ginagalaw ang Admin at Staff
    $pdo->exec("DELETE FROM users WHERE role = 'patient' OR patient_id IS NOT NULL;");
    echo "✅ Na-delete na ang mga <b>Patient Accounts</b> sa users table (Admin at Staff naiwan).<br>";

    // 2. I-truncate ang lahat ng table na puro records ng patients at transactions
    $tables = [
        'account_verifications', 
        'branch_case_sequences',
        'cases', 
        'feedbacks', 
        'patients',
        'record_requests', 
        'request_logs', 
        'request_sequences', 
        'requests',
        'result_disputes'
        // 'notifications', // Inalis muna kung sakaling kailangan ng admin ang old notifs
        // 'messages',
        // 'audit_logs'
    ];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table;");
        echo "✅ Na-truncate na ang table: <b>$table</b> <br>";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<h3 style='color:green;'>✅ Tapos na! Na-reset na ang mga Patient Accounts at Records.</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>May Error: " . $e->getMessage() . "</h3>";
}
