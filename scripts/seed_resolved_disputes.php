<?php
/**
 * CitiLife Diagnostic System - 50 Resolved Correction Requests Seeder per Branch
 * Seeds 50 realistic resolved dispute/correction history records per branch across 7 branches (Total: 350)
 * Usage: php scripts/seed_resolved_disputes.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

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
    echo "====================================================\n";
    echo " Connected to database: {$dbConfig['dbname']} ({$dbConfig['host']})\n";
    echo "====================================================\n\n";
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage() . "\n");
}

// 1. Fetch RadTech user for resolver ID
$stmtTech = $pdo->query("SELECT id FROM users WHERE role = 'radtech' ORDER BY id ASC LIMIT 1");
$techId = (int)$stmtTech->fetchColumn() ?: 7;

// 2. Correction Categories & Realistic Descriptions
$disputeTemplates = [
    [
        'category' => 'demographic_error',
        'desc' => "Please correct the spelling of patient's middle name and update the current barangay address.",
        'rad_note' => "Patient provided valid ID. Demographics verified and updated in master records.",
        'res_note' => "Patient information successfully updated and verified."
    ],
    [
        'category' => 'findings_error',
        'desc' => "Typographical error noticed in the findings description text.",
        'rad_note' => "Reviewed report text with Radiologist. Typographical wording refined.",
        'res_note' => "Findings description updated and amended report re-released."
    ],
    [
        'category' => 'template_error',
        'desc' => "Requesting clarification and standard naming format on the examination view title.",
        'rad_note' => "Exam template naming aligned with official clinic protocol.",
        'res_note' => "Template header corrected and finalized."
    ],
    [
        'category' => 'both_error',
        'desc' => "Minor typo in findings and request to check middle initial on receipt.",
        'rad_note' => "Checked patient file and adjusted findings wording and middle name.",
        'res_note' => "Both patient info and findings verified and amended."
    ],
    [
        'category' => 'exam_details_error',
        'desc' => "Updated clinical information note from attending physician requested.",
        'rad_note' => "Attending physician referral slip attached and clinical info updated.",
        'res_note' => "Clinical history updated and report re-verified."
    ],
];

$branches = [
    1 => 'Gapan',
    2 => 'Bongabon',
    3 => 'Peñaranda',
    4 => 'General Tinio',
    5 => 'Sto Domingo',
    6 => 'San Antonio',
    7 => 'Pantabangan',
];

echo "====================================================\n";
echo " Seeding 50 Resolved Correction Requests per Branch (350 Total)\n";
echo "====================================================\n\n";

$totalInserted = 0;
$pdo->beginTransaction();

try {
    // Clean any previous test disputes that might have orphaned null branch_id
    $pdo->exec("DELETE FROM result_disputes WHERE branch_id IS NULL OR case_id NOT IN (SELECT id FROM cases)");

    foreach ($branches as $branchId => $branchName) {
        echo "Processing Branch [{$branchId}] - {$branchName}...\n";

        // Fetch 50 cases from this branch
        $stmtCases = $pdo->prepare("
            SELECT id, patient_id, branch_id, case_number, findings, impression, created_at 
            FROM cases 
            WHERE branch_id = ? 
            ORDER BY id ASC 
            LIMIT 50
        ");
        $stmtCases->execute([$branchId]);
        $cases = $stmtCases->fetchAll();

        if (empty($cases)) {
            echo "  [!] No cases found for Branch {$branchName}. Skipping.\n";
            continue;
        }

        $branchCount = 0;
        foreach ($cases as $c) {
            $caseId = (int)$c['id'];
            $patientId = (int)$c['patient_id'];
            $tpl = $disputeTemplates[array_rand($disputeTemplates)];

            // Distributed creation time
            $caseTime = strtotime($c['created_at']);
            $dispCreated = date('Y-m-d H:i:s', $caseTime + mt_rand(3600, 86400));
            $dispResolved = date('Y-m-d H:i:s', strtotime($dispCreated) + mt_rand(1800, 7200));

            // Check if dispute already exists for this case
            $chk = $pdo->prepare("SELECT id FROM result_disputes WHERE case_id = ? AND dispute_category = ?");
            $chk->execute([$caseId, $tpl['category']]);
            if ($chk->fetch()) {
                continue;
            }

            $ins = $pdo->prepare("
                INSERT INTO result_disputes (
                    case_id, patient_id, branch_id, dispute_category, description,
                    old_findings, old_impression, status, assigned_role, radtech_notes,
                    resolution_notes, demographics_fixed, radiologist_amended,
                    resolved_by, resolved_at, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, 'Resolved', 'radtech', ?,
                    ?, 1, 1,
                    ?, ?, ?
                )
            ");

            $ins->execute([
                $caseId,
                $patientId,
                $branchId,
                $tpl['category'],
                $tpl['desc'],
                $c['findings'],
                $c['impression'],
                $tpl['rad_note'],
                $tpl['res_note'],
                $techId,
                $dispResolved,
                $dispCreated
            ]);

            $branchCount++;
            $totalInserted++;
        }

        echo "  --> Branch {$branchName}: Seeded {$branchCount} resolved correction requests.\n";
    }

    $pdo->commit();

    echo "\n====================================================\n";
    echo " SUCCESS! Seeded {$totalInserted} Resolved Correction Requests!\n";
    echo " All records are marked 'Resolved' ('No action needed') and strictly isolated per branch.\n";
    echo "====================================================\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("\n[ERROR] Failed to seed resolved disputes: " . $e->getMessage() . "\n");
}
