<?php
require_once __DIR__ . '/../config/database.php';
global $pdo;

echo "<h2>Database Reset Script (Production / Railway)</h2>";

try {
    echo "Connected sa database!<br>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $tables = [
        'account_verifications', 'audit_logs', 'branch_case_sequences',
        'cases', 'feedbacks', 'messages', 'notifications', 'patients',
        'record_requests', 'request_logs', 'request_sequences', 'requests',
        'result_disputes', 'user_devices'
    ];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table;");
        echo "✅ Na-truncate na ang table: <b>$table</b> <br>";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<h3 style='color:green;'>✅ Tapos na! Na-reset na ang database.</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>May Error: " . $e->getMessage() . "</h3>";
}
