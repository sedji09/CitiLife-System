<?php
/**
 * Link User seigipascual09@gmail.com to Patient PAT2026-GAP-00101 and Case GAP2026-00103
 * And clean up any dispute or incorrect link on GAP2026-00001
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dbConfig = require __DIR__ . '/../config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "Connected to {$dbConfig['dbname']} ({$dbConfig['host']})\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

$email = 'seigipascual09@gmail.com';
$targetPatientNo = 'PAT2026-GAP-00101';
$targetCaseNo = 'GAP2026-00103';

// 1. Find the target patient PAT2026-GAP-00101
$stmt = $pdo->prepare("SELECT id, patient_number, first_name, last_name, email FROM patients WHERE patient_number = ?");
$stmt->execute([$targetPatientNo]);
$targetPatient = $stmt->fetch();

if (!$targetPatient) {
    // If not found, create PAT2026-GAP-00101
    echo "Creating patient $targetPatientNo...\n";
    $ins = $pdo->prepare("INSERT INTO patients (patient_number, first_name, middle_name, last_name, sex, birthdate, contact_number, email, home_address, branch_id, created_at)
                          VALUES (?, 'Sedji', 'Reyes', 'Pascual', 'Male', '2005-01-01', '0985-767-3975', ?, '0089 Purok 3, Mangino, Gapan City, Nueva Ecija', 1, NOW())");
    $ins->execute([$targetPatientNo, $email]);
    $targetPatientId = (int)$pdo->lastInsertId();
} else {
    $targetPatientId = (int)$targetPatient['id'];
    echo "Found target patient ID: $targetPatientId ($targetPatientNo)\n";
    // Ensure email and name are set
    $pdo->prepare("UPDATE patients SET email = ?, first_name = 'Sedji', last_name = 'Pascual' WHERE id = ?")
        ->execute([$email, $targetPatientId]);
}

// 2. Clear email from any other patient so this one is unique
$pdo->prepare("UPDATE patients SET email = NULL WHERE email = ? AND id != ?")->execute([$email, $targetPatientId]);

// 3. Find or link the target case GAP2026-00103 to this patient
$stmtCase = $pdo->prepare("SELECT id, case_number, patient_id FROM cases WHERE case_number = ?");
$stmtCase->execute([$targetCaseNo]);
$targetCase = $stmtCase->fetch();

if ($targetCase) {
    echo "Linking case $targetCaseNo (ID: {$targetCase['id']}) to Patient ID: $targetPatientId\n";
    $pdo->prepare("UPDATE cases SET patient_id = ? WHERE id = ?")->execute([$targetPatientId, $targetCase['id']]);
    $targetCaseId = $targetCase['id'];
} else {
    echo "Creating case $targetCaseNo for patient ID: $targetPatientId\n";
    $stmtTpl = $pdo->query("SELECT * FROM cases WHERE exam_type = 'Chest AP/LAT' LIMIT 1");
    $tpl = $stmtTpl->fetch();
    
    $insCase = $pdo->prepare("INSERT INTO cases (
        case_number, patient_id, branch_id, service_type, service_id, exam_type, priority,
        philhealth_status, philhealth_id, status, report_status, approval_status,
        image_status, image_path, released, created_at, clinical_information,
        findings, impression, recommendation, radiologist_id, radtech_id,
        date_completed, report_template
    ) VALUES (
        ?, ?, 1, 'X-Ray', 3, 'Chest AP/LAT', 'Routine',
        'Without PhilHealth Card', NULL, 'Completed', 'Final', 'Approved',
        'Uploaded', '[\"public/assets/uploads/cases/samples/sample_chest_pa.png\"]', 1, NOW(), 'Routine medical check-up / diagnostic evaluation',
        'Biapical lung zones are clear. Costophrenic sulci are sharp.\nHeart size and pulmonary vascularity are normal.',
        'No acute cardiopulmonary disease.', '', 5, 7,
        NOW(), 'Chest AP/LAT'
    )");
    $insCase->execute([$targetCaseNo, $targetPatientId]);
    $targetCaseId = (int)$pdo->lastInsertId();
}

// 4. Update the user account to link directly to this patient ID
$pdo->prepare("UPDATE users SET patient_id = ?, status = 'Active' WHERE email = ?")->execute([$targetPatientId, $email]);
echo "Updated users table: email $email -> patient_id $targetPatientId\n";

// 5. Delete any result disputes on GAP2026-00001 or on this user's cases so no unwanted 'Correction Status' is shown
$stmtOldCase = $pdo->prepare("SELECT id FROM cases WHERE case_number = 'GAP2026-00001'");
$stmtOldCase->execute();
$oldCaseId = $stmtOldCase->fetchColumn();

if ($oldCaseId) {
    $pdo->prepare("DELETE FROM result_disputes WHERE case_id = ?")->execute([$oldCaseId]);
    echo "Cleaned disputes for old case GAP2026-00001\n";
}

// Also remove any disputes on targetCaseId so it starts clean
$pdo->prepare("DELETE FROM result_disputes WHERE case_id = ? OR patient_id = ?")->execute([$targetCaseId, $targetPatientId]);
echo "Cleaned disputes on patient $targetPatientId / case $targetCaseId\n";

echo "===========================================\n";
echo " SUCCESS! Patient account $email is now correctly linked to $targetPatientNo and Case $targetCaseNo.\n";
echo "===========================================\n";
