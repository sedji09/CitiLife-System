<?php
/**
 * Helper function to generate a unique patient number based on branch and year.
 */
function generatePatientNumber($pdo, $branchId) {
    if (!$branchId) {
        $branchName = 'General';
    } else {
        $stmtB = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmtB->execute([$branchId]);
        $branchName = $stmtB->fetchColumn() ?: 'General';
    }

    $code = 'GEN';
    if (stripos($branchName, 'Gapan') !== false) {
        $code = 'GAP';
        $padLength = 3;
    } elseif (stripos($branchName, 'Bongabon') !== false) {
        $code = 'BON';
        $padLength = 3;
    } elseif (stripos($branchName, 'Peñaranda') !== false) {
        $code = 'PEN';
        $padLength = 3;
    } elseif (stripos($branchName, 'General Tinio') !== false || stripos($branchName, 'General Tion') !== false) {
        $code = 'GTI';
        $padLength = 3;
    } elseif (stripos($branchName, 'San Antonio') !== false) {
        $code = 'SAN';
        $padLength = 3;
    } elseif (stripos($branchName, 'Sto Domingo') !== false) {
        $code = 'STD';
        $padLength = 3;
    } elseif (stripos($branchName, 'Pantabangan') !== false) {
        $code = 'PAN';
        $padLength = 4;
    } else {
        $padLength = 3;
    }

    $year = date('Y');
    $prefix = "PAT-{$code}-{$year}-";

    $stmtLast = $pdo->prepare("SELECT patient_number FROM patients WHERE patient_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmtLast->execute([$prefix . '%']);
    $lastPatient = $stmtLast->fetchColumn();

    $seqIndex = 1;
    if ($lastPatient && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastPatient, $m)) {
        $seqIndex = (int)$m[1] + 1;
    }

    return $prefix . str_pad($seqIndex, $padLength, '0', STR_PAD_LEFT);
}
