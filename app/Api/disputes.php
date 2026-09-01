<?php
/**
 * disputes.php
 * API endpoint for Patient & Clinic Result Dispute Operations (Strict 5-Step Workflow)
 */

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/ResultDisputeModel.php';
require_once __DIR__ . '/../Models/CaseModel.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';

$disputeModel = new ResultDisputeModel($pdo);
$auditLog = new AuditLogModel($pdo);

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'submit_dispute') {
        // Step 2: Patient Submission -> Status: "Pending RadTech Review", Assigned: "radtech"
        $userId = $_SESSION['user_id'] ?? null;
        $patientId = $_SESSION['patient_id'] ?? null;

        if (!$userId || !$patientId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized patient session.']);
            exit;
        }

        $caseId = intval($_POST['case_id'] ?? 0);
        $category = trim($_POST['dispute_category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$caseId || !$category || !$description) {
            echo json_encode(['success' => false, 'message' => 'Kumpletuhin ang rason at detalye ng report.']);
            exit;
        }

        // Verify case belongs to patient
        $stmt = $pdo->prepare("SELECT id, branch_id, case_number FROM cases WHERE id = ? AND patient_id = ?");
        $stmt->execute([$caseId, $patientId]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$case) {
            echo json_encode(['success' => false, 'message' => 'Hindi nahanap ang resulta ng rekurso.']);
            exit;
        }

        // Check if existing pending dispute
        $activeDispute = $disputeModel->getActiveDisputeByCase($caseId);
        if ($activeDispute) {
            echo json_encode(['success' => false, 'message' => 'Mayroon nang umiiral na report sa resultang ito na kasalukuyang tinitingnan.']);
            exit;
        }

        $disputeId = $disputeModel->createDispute($caseId, $patientId, $case['branch_id'], $category, $description);

        // Notify RadTech ONLY
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (role, branch_id, title, message, link, created_at)
            VALUES ('radtech', ?, ?, ?, ?, NOW())
        ");
        $notifTitle = "New Error Report (" . $case['case_number'] . ")";
        $notifMsg = "A new patient error report requires RadTech review.";
        $notifLink = "index.php?role=radtech&page=patient-lists&tab=disputes&dispute_id=" . $disputeId;
        $notifStmt->execute([$case['branch_id'], $notifTitle, $notifMsg, $notifLink]);

        $auditLog->addLog($userId, 'Dispute Submitted', 'Patient Portal', 'Case', $caseId, "Submitted result dispute (ID: {$disputeId}, Category: {$category})", $case['branch_id']);

        echo json_encode(['success' => true, 'message' => 'Your error report has been submitted to the clinic. It will be reviewed shortly.']);
        exit;

    } elseif ($action === 'escalate_to_radiologist') {
        // Step 3: RadTech Escalates Medical Findings Error to Radiologist
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $disputeId = intval($_POST['dispute_id'] ?? 0);
        $radtechNotes = trim($_POST['radtech_notes'] ?? '');

        if (!$disputeId || !$radtechNotes) {
            echo json_encode(['success' => false, 'message' => 'Please provide internal notes before escalating to the Radiologist.']);
            exit;
        }

        // Fetch dispute & case info first for branch ownership check
        $stmtBranch = $pdo->prepare("SELECT rd.case_id, c.case_number, c.branch_id FROM result_disputes rd JOIN cases c ON rd.case_id = c.id WHERE rd.id = ?");
        $stmtBranch->execute([$disputeId]);
        $disData = $stmtBranch->fetch(PDO::FETCH_ASSOC);

        // Security: non-admin_central staff may only act on their own branch's disputes
        if ($disData && $role !== 'admin_central') {
            $sessionBranch = $_SESSION['branch_id'] ?? null;
            if ($sessionBranch && (int) $disData['branch_id'] !== (int) $sessionBranch) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: dispute does not belong to your branch.']);
                exit;
            }
        }

        $disputeModel->escalateToRadiologist($disputeId, $radtechNotes);

        // Fetch case info to send notification to Radiologist
        if (!isset($disData)) {
            $stmt = $pdo->prepare("SELECT rd.case_id, c.case_number, c.branch_id FROM result_disputes rd JOIN cases c ON rd.case_id = c.id WHERE rd.id = ?");
            $stmt->execute([$disputeId]);
            $disData = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($disData) {
            $pdo->prepare("
                INSERT INTO notifications (role, branch_id, title, message, link, created_at)
                VALUES ('radiologist', ?, ?, ?, ?, NOW())
            ")->execute([
                $disData['branch_id'],
                "Dispute Escalated: " . $disData['case_number'],
                "RadTech escalated an error report for Radiologist review. Notes: " . $radtechNotes,
                "index.php?role=radiologist&page=worklist&tab=disputes&highlight_dispute_case=" . $disData['case_number']
            ]);
        }

        $auditLog->addLog($userId, 'Dispute Escalated', 'Clinic Management', 'Dispute', $disputeId, "Escalated dispute to Radiologist. Notes: {$radtechNotes}");

        echo json_encode(['success' => true, 'message' => 'Dispute ticket successfully escalated to the Radiologist.']);
        exit;

    } elseif ($action === 'resolve_dispute') {
        // RadTech resolves clerical/technical error directly (Step 3 / Step 5)
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $disputeId = intval($_POST['dispute_id'] ?? 0);

        if (!$disputeId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Dispute ID.']);
            exit;
        }

        // Fetch dispute & case patient data (also used for branch check)
        $stmtDisp = $pdo->prepare("
            SELECT rd.*, c.patient_id, c.case_number, c.branch_id, p.email, p.first_name, u.id AS patient_user_id, u.email AS user_email 
            FROM result_disputes rd 
            JOIN cases c ON rd.case_id = c.id 
            JOIN patients p ON c.patient_id = p.id
            LEFT JOIN users u ON p.id = u.patient_id AND u.role = 'patient'
            WHERE rd.id = ?
        ");
        $stmtDisp->execute([$disputeId]);
        $disputeInfo = $stmtDisp->fetch(PDO::FETCH_ASSOC);

        // Security: non-admin_central staff may only resolve disputes in their own branch
        if ($disputeInfo && $role !== 'admin_central') {
            $sessionBranch = $_SESSION['branch_id'] ?? null;
            if ($sessionBranch && (int) $disputeInfo['branch_id'] !== (int) $sessionBranch) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: dispute does not belong to your branch.']);
                exit;
            }
        }

        $notes = 'Clerical/demographic error corrected by RadTech.';

        if ($disputeInfo && $disputeInfo['dispute_category'] === 'demographic_error' && !empty($disputeInfo['description'])) {
            // Automatically parse and update patient details in database
            $desc = $disputeInfo['description'];
            $patientId = $disputeInfo['patient_id'];

            $updateFields = [];
            $updateParams = [];

            // Match First Name
            if (preg_match('/First Name:\s*([^,\n]+)/i', $desc, $m)) {
                $updateFields[] = "first_name = ?";
                $updateParams[] = trim($m[1]);
            }
            // Match Last Name
            if (preg_match('/Last Name:\s*([^,\n]+)/i', $desc, $m)) {
                $updateFields[] = "last_name = ?";
                $updateParams[] = trim($m[1]);
            }
            // Match Sex
            if (preg_match('/Sex:\s*([^,\n]+)/i', $desc, $m)) {
                $updateFields[] = "sex = ?";
                $updateParams[] = trim($m[1]);
            }
            // Match Age -> Update birthdate based on age
            if (preg_match('/Age:\s*(\d+)/i', $desc, $m)) {
                $age = intval($m[1]);
                if ($age > 0) {
                    $birthYear = date('Y') - $age;
                    $birthdate = "{$birthYear}-01-01";
                    $updateFields[] = "birthdate = ?";
                    $updateParams[] = $birthdate;
                }
            }

            if (!empty($updateFields)) {
                $updateParams[] = $patientId;
                $sqlP = "UPDATE patients SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmtP = $pdo->prepare($sqlP);
                $stmtP->execute($updateParams);
                $notes = "Patient details updated: " . $desc;
            }
        }

        $disputeModel->updateDisputeStatus($disputeId, 'Resolved', 'radtech', $notes, $userId);

        // Mark case as Released again
        if ($disputeInfo && !empty($disputeInfo['case_id'])) {
            $pdo->prepare("UPDATE cases SET status = 'Released', released = 1, is_amended = 1, amendment_notes = COALESCE(amendment_notes, ?) WHERE id = ?")
                ->execute([$notes, $disputeInfo['case_id']]);
        }
        
        // Notify the Patient (In-App)
        if ($disputeInfo && !empty($disputeInfo['patient_user_id'])) {
            $notifTitle = "Error Report Resolved";
            $notifMsg = "Your error report for Case " . $disputeInfo['case_number'] . " has been successfully resolved.";
            $notifLink = "index.php?role=patient&page=my-records&tab=disputes&highlight_dispute_id=" . $disputeId;
            $pdo->prepare("INSERT INTO notifications (user_id, role, title, message, link, created_at) VALUES (?, 'patient', ?, ?, ?, NOW())")
                ->execute([$disputeInfo['patient_user_id'], $notifTitle, $notifMsg, $notifLink]);
        }

        // Email the Patient
        $patientEmail = !empty($disputeInfo['user_email']) ? $disputeInfo['user_email'] : $disputeInfo['email'];
        if ($disputeInfo && !empty($patientEmail)) {
            require_once __DIR__ . '/../../app/Helpers/mailer_helper.php';
            $patientName = htmlspecialchars($disputeInfo['first_name']);
            $caseNum = htmlspecialchars($disputeInfo['case_number']);
            $refToken = !empty($disputeInfo['case_id']) ? base64_encode('Citilife_Case_' . $disputeInfo['case_id']) : '';
            $reportUrl = !empty($refToken) 
                ? (appBaseUrl() . "/" . PROJECT_DIR . "/view-report?ref=" . $refToken)
                : (appBaseUrl() . "/" . PROJECT_DIR . "/dashboard");

            $emailSubject = "Error Report Resolved - Citilife Diagnostic Center";
            $emailBody = renderNotificationEmail(
                $patientName,
                "Error Report Resolved - Case #{$caseNum}",
                "We have successfully reviewed and resolved your error report for Case <strong>{$caseNum}</strong>. Your updated patient records and X-ray report are now available in your Citilife patient portal.",
                [
                    'Case Number' => htmlspecialchars($caseNum),
                    'Patient' => htmlspecialchars($patientName),
                    'Status' => '<span style="color: #1a7f37; font-weight: 600;">Resolved &amp; Updated</span>'
                ],
                "View Updated Report",
                $reportUrl,
                "You're receiving this notification because an error report for your case was resolved.",
                "#1f883d"
            );
            sendEmail($patientEmail, $disputeInfo['first_name'], $emailSubject, $emailBody);
        }

        $auditLog->addLog($userId, 'Dispute Resolved', 'Clinic Management', 'Dispute', $disputeId, "Resolved dispute. Notes: {$notes}");

        echo json_encode(['success' => true, 'message' => 'Dispute ticket resolved and patient info updated successfully.']);
        exit;

    } elseif ($action === 'amend_report') {
        // Step 4: Radiologist Issues Amended Report -> Ticket becomes "Pending RadTech Final Verification"
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radiologist', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $caseId = intval($_POST['case_id'] ?? 0);
        $disputeId = intval($_POST['dispute_id'] ?? 0);
        $findings = trim($_POST['findings'] ?? '');
        $impression = trim($_POST['impression'] ?? '');
        $amendmentNotes = trim($_POST['amendment_notes'] ?? '');

        if (!$caseId || !$findings || !$impression) {
            echo json_encode(['success' => false, 'message' => 'Kumpletuhin ang findings, impression, at dahilan ng amendment.']);
            exit;
        }

        // Update Case to Report Ready and set as amended
        $stmt = $pdo->prepare("
            UPDATE cases 
            SET findings = ?, impression = ?, is_amended = 1, amendment_notes = ?, status = 'Report Ready', released = 0
            WHERE id = ?
        ");
        $stmt->execute([$findings, $impression, $amendmentNotes, $caseId]);

        // Route ticket back to RadTech for final verification (Step 5)
        if ($disputeId) {
            $disputeModel->updateDisputeStatus($disputeId, 'Pending RadTech Verification', 'radtech', "Amended report issued by radiologist: " . $amendmentNotes, $userId);

            // Fetch case info to send notification to RadTech
            $stmtDisData = $pdo->prepare("SELECT c.case_number, c.branch_id FROM cases c WHERE c.id = ?");
            $stmtDisData->execute([$caseId]);
            $cData = $stmtDisData->fetch(PDO::FETCH_ASSOC);

            if ($cData) {
                $pdo->prepare("
                    INSERT INTO notifications (role, branch_id, title, message, link, created_at)
                    VALUES ('radtech', ?, ?, ?, ?, NOW())
                ")->execute([
                    $cData['branch_id'],
                    "Amended Report Issued (" . $cData['case_number'] . ")",
                    "Radiologist issued an amended report. Verification required.",
                    "index.php?role=radtech&page=patient-lists&tab=disputes&dispute_id=" . $disputeId
                ]);
            }
        }

        $auditLog->addLog($userId, 'Report Amended', 'Radiology Reporting', 'Case', $caseId, "Amended findings & impression for case {$caseId}. Notes: {$amendmentNotes}");

        echo json_encode(['success' => true, 'message' => 'Matagumpay na nai-save ang Amended Report! Ang ticket ay naipasa na kay RadTech para sa final approval at release.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
