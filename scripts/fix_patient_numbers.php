<?php
/**
 * Quick Fix Script: Normalize all patient numbers to standard format: PAT2026-GAP-00001
 * Usage: php scripts/fix_patient_numbers.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dbConfig = require __DIR__ . '/../config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "Connected to database: {$dbConfig['dbname']}\n";
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage() . "\n");
}

// Fetch all patients
$stmt = $pdo->query("SELECT id, patient_number, branch_id FROM patients ORDER BY id ASC");
$patients = $stmt->fetchAll();

$branches = [
    1 => 'GAP',
    2 => 'BON',
    3 => 'PEN',
    4 => 'GTI',
    5 => 'STD',
    6 => 'SAN',
    7 => 'PAN',
];

$updatedCount = 0;
$updateStmt = $pdo->prepare("UPDATE patients SET patient_number = ? WHERE id = ?");
$checkStmt = $pdo->prepare("SELECT id FROM patients WHERE patient_number = ? AND id != ?");

foreach ($patients as $p) {
    $pNum = trim($p['patient_number'] ?? '');
    $id = (int)$p['id'];
    $bId = (int)($p['branch_id'] ?? 1);
    $bCode = $branches[$bId] ?? 'GAP';

    $year = '2026';
    $seq = 0;
    $foundMatch = false;

    // Matches PAT-GAP-2026-0058 or PAT-GAP-2026-58
    if (preg_match('/^PAT-?([A-Z]{3})-?(\d{4})-?(\d+)$/i', $pNum, $m)) {
        $year = $m[2];
        $code = strtoupper($m[1]);
        $seq  = (int)$m[3];
        $foundMatch = true;
    } elseif (preg_match('/^PAT(\d{4})-([A-Z]{3})-(\d+)$/i', $pNum, $m)) {
        $year = $m[1];
        $code = strtoupper($m[2]);
        $seq  = (int)$m[3];
        $foundMatch = true;
    }

    if ($foundMatch) {
        $candidate = "PAT{$year}-{$code}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
        
        // Check collision
        $checkStmt->execute([$candidate, $id]);
        while ($checkStmt->fetch()) {
            $seq++;
            $candidate = "PAT{$year}-{$code}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
            $checkStmt->execute([$candidate, $id]);
        }

        if ($candidate !== $pNum) {
            $updateStmt->execute([$candidate, $id]);
            $updatedCount++;
        }
    }
}

echo "Successfully normalized {$updatedCount} patient numbers to format: PAT2026-GAP-00001\n";
