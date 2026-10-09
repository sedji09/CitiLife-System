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
        $padLength = 5;
    }

    $year = date('Y');
    $prefix = "PAT{$year}-{$code}-";

    $stmt = $pdo->prepare("SELECT patient_number FROM patients WHERE patient_number LIKE ?");
    $stmt->execute([$prefix . '%']);
    $allNums = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    $maxSeq = 0;
    foreach ($allNums as $pNum) {
        if (preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $pNum, $m)) {
            $val = (int)$m[1];
            if ($val > $maxSeq && $val < 50000) {
                $maxSeq = $val;
            }
        }
    }

    $nextSeq = $maxSeq + 1;
    return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
}
