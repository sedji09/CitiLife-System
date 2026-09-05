<?php
/**
 * disputes.php
 * API endpoint for Patient & Clinic Result Dispute Operations (Strict 5-Step Workflow)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/session.php';

if (!defined('PROJECT_DIR')) {
    $scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $parts = explode('/', str_replace('\\', '/', $scriptDir));
    define('PROJECT_DIR', (isset($parts[0]) && $parts[0] !== 'app' && $parts[0] !== 'index.php') ? $parts[0] : 'CitiLife-System');
}

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/ResultDisputeModel.php';
require_once __DIR__ . '/../Models/CaseModel.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';
require_once __DIR__ . '/../Models/CaseAmendmentModel.php';

$disputeModel = new ResultDisputeModel($pdo);
$caseModel = new CaseModel($pdo);
$auditLog = new AuditLogModel($pdo);
$amendmentModel = new CaseAmendmentModel($pdo);

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'submit_dispute') {
        // Step 1: Patient Submission -> Status: "Issue Reported", Assigned: "radtech"
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

        // Update case status forward to 'Issue Reported'
        $caseModel->transitionStatus($caseId, 'Issue Reported', $userId);

        // Notify RadTech ONLY
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (role, branch_id, title, message, link, created_at)
            VALUES ('radtech', ?, ?, ?, ?, NOW())
        ");
        $notifTitle = "New Error Report (" . $case['case_number'] . ")";
        $notifMsg = "A new patient error report requires RadTech review.";
        $notifLink = "index.php?role=radtech&page=patient-lists&tab=disputes&dispute_id=" . $disputeId . "&highlight_case=" . urlencode($case['case_number']);
        $notifStmt->execute([$case['branch_id'], $notifTitle, $notifMsg, $notifLink]);

        $auditLog->addLog($userId, 'Dispute Submitted', 'Patient Portal', 'Case', $caseId, "Submitted result dispute (ID: {$disputeId}, Category: {$category})", $case['branch_id']);

        echo json_encode(['success' => true, 'message' => 'Your error report has been submitted to the clinic. It will be reviewed shortly.']);
        exit;

    } elseif ($action === 'escalate_to_radiologist') {
        echo json_encode([
            'success' => false, 
            'message' => 'Escalation to radiologist is discontinued. Error reports are resolved directly by RadTech.'
        ]);
        exit;

    } elseif ($action === 'update_patient_demographics') {
        // Step: RadTech applies correction to patient demographics (Name)
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $disputeId = intval($_POST['dispute_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
        $sex = trim($_POST['sex'] ?? '');

        if (!$disputeId) {
            echo json_encode(['success' => false, 'message' => 'Dispute ID is required.']);
            exit;
        }

        $stmtDisp = $pdo->prepare("
            SELECT rd.*, c.patient_id, c.case_number, c.branch_id 
            FROM result_disputes rd 
            JOIN cases c ON rd.case_id = c.id 
            WHERE rd.id = ?
        ");
        $stmtDisp->execute([$disputeId]);
        $disputeInfo = $stmtDisp->fetch(PDO::FETCH_ASSOC);

        if (!$disputeInfo) {
            echo json_encode(['success' => false, 'message' => 'Dispute not found.']);
            exit;
        }

        if ($role !== 'admin_central') {
            $sessionBranch = $_SESSION['branch_id'] ?? null;
            if ($sessionBranch && (int) $disputeInfo['branch_id'] !== (int) $sessionBranch) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized branch.']);
                exit;
            }
        }

        $patientId = $disputeInfo['patient_id'];

        // Update patient demographics dynamically
        $updateFields = [];
        $updateParams = [];

        if ($firstName !== '') {
            $updateFields[] = "first_name = ?";
            $updateParams[] = $firstName;
        }
        if ($lastName !== '') {
            $updateFields[] = "last_name = ?";
            $updateParams[] = $lastName;
        }
        if ($middleName !== '') {
            $updateFields[] = "middle_name = ?";
            $updateParams[] = $middleName;
        }
        if ($sex !== '') {
            $updateFields[] = "sex = ?";
            $updateParams[] = $sex;
        }
        if ($age > 0) {
            $birthYear = date('Y') - $age;
            $birthdate = "{$birthYear}-01-01";
            $updateFields[] = "birthdate = ?";
            $updateParams[] = $birthdate;
        }

        if (!empty($updateFields)) {
            $updateParams[] = $patientId;
            $stmtP = $pdo->prepare("UPDATE patients SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $stmtP->execute($updateParams);
        }

        // Also update linked patient user account if name was changed
        $fullName = trim($firstName . ' ' . $lastName);
        if ($fullName !== '') {
            $stmtU = $pdo->prepare("UPDATE users SET name = ? WHERE patient_id = ? AND role = 'patient'");
            $stmtU->execute([$fullName, $patientId]);
        }

        $cat = $disputeInfo['dispute_category'] ?? '';
        $isBoth = ($cat === 'both_error' || $cat === 'both_template_error');
        $radAmended = (int)($disputeInfo['radiologist_amended'] ?? 0);

        // Check if case was already amended in cases table
        $caseAmended = 0;
        if (!empty($disputeInfo['case_id'])) {
            $stmtC = $pdo->prepare("SELECT is_amended FROM cases WHERE id = ?");
            $stmtC->execute([$disputeInfo['case_id']]);
            $caseAmended = (int)$stmtC->fetchColumn();
        }

        if ($isBoth && !$radAmended && !$caseAmended) {
            $newStatus = 'Correction in Progress';
            $msg = 'Patient demographics updated. Please proceed with template / report amendment.';
        } else {
            $newStatus = 'Resolved';
            $msg = 'Patient demographics updated and dispute successfully resolved.';
        }

        $stmtD = $pdo->prepare("
            UPDATE result_disputes 
            SET status = ?, 
                assigned_role = 'radtech',
                demographics_fixed = 1,
                resolution_notes = CONCAT(COALESCE(resolution_notes, ''), ' Patient demographics updated.')
            WHERE id = ?
        ");
        $stmtD->execute([$newStatus, $disputeId]);

        // Advance through workflow steps for tracking
        if ($newStatus === 'Resolved') {
            $disputeModel->advanceStatus($disputeId, 'Correction Completed');
            $disputeModel->advanceStatus($disputeId, 'Resolved');

            if (!empty($disputeInfo['case_id'])) {
                $pdo->prepare("UPDATE cases SET status = 'Released', released = 1, is_amended = 1, amendment_notes = COALESCE(amendment_notes, 'Demographics corrected by RadTech') WHERE id = ?")
                    ->execute([$disputeInfo['case_id']]);
            }
        }

        $auditLog->addLog($userId, 'Demographics Corrected', 'Clinic Management', 'Patient', $patientId, "Corrected patient name to '{$fullName}' under dispute #{$disputeId}");

        echo json_encode([
            'success' => true, 
            'message' => $msg, 
            'both_pending_escalate' => ($isBoth && !$radAmended)
        ]);
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
            $reportUrl = !empty($disputeInfo['case_id']) 
                ? (appBaseUrl() . "/" . PROJECT_DIR . "/case-status?case_id=" . $disputeInfo['case_id'])
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
    } elseif ($action === 'get_case_for_amend') {
        // RadTech opens Amend Modal -> Fetch case details, patient info, and dispute
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $caseId = intval($_GET['case_id'] ?? ($_POST['case_id'] ?? 0));
        $disputeId = intval($_GET['dispute_id'] ?? ($_POST['dispute_id'] ?? 0));

        if (!$caseId && $disputeId) {
            $stmtD = $pdo->prepare("SELECT case_id FROM result_disputes WHERE id = ?");
            $stmtD->execute([$disputeId]);
            $caseId = (int) $stmtD->fetchColumn();
        }

        if (!$caseId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Case ID.']);
            exit;
        }

        // Fetch case with patient information
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   p.first_name, p.last_name, p.middle_name, p.patient_number, p.contact_number, p.email as patient_email, p.sex, p.birthdate,
                   (YEAR(CURDATE()) - YEAR(p.birthdate)) AS age,
                   b.name as branch_name
            FROM cases c
            JOIN patients p ON c.patient_id = p.id
            LEFT JOIN branches b ON c.branch_id = b.id
            WHERE c.id = ?
        ");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$case) {
            echo json_encode(['success' => false, 'message' => 'Case not found.']);
            exit;
        }

        // Branch check for security
        if ($role !== 'admin_central') {
            $sessionBranch = $_SESSION['branch_id'] ?? null;
            if ($sessionBranch && (int) $case['branch_id'] !== (int) $sessionBranch) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized branch access.']);
                exit;
            }
        }

        // Fetch active dispute
        $activeDispute = null;
        if ($disputeId) {
            $stmtD = $pdo->prepare("SELECT * FROM result_disputes WHERE id = ?");
            $stmtD->execute([$disputeId]);
            $activeDispute = $stmtD->fetch(PDO::FETCH_ASSOC);
        } else {
            $activeDispute = $disputeModel->getActiveDisputeByCase($caseId);
        }

        // Automatic forward transition to 'Correction in Progress' if currently in early review
        if ($activeDispute && in_array($activeDispute['status'], ['Issue Reported', 'For RadTech Review', 'Pending RadTech Review'])) {
            $disputeModel->updateDisputeStatus($activeDispute['id'], 'Correction in Progress', 'radtech');
            $caseModel->transitionStatus($caseId, 'Correction in Progress', $userId);
            $activeDispute['status'] = 'Correction in Progress';
            $case['status'] = 'Correction in Progress';
        }

        // Fetch amendment history
        $amendments = $amendmentModel->getAmendmentsByCaseId($caseId);

        echo json_encode([
            'success' => true,
            'case' => [
                'id' => $case['id'],
                'case_number' => $case['case_number'],
                'exam_type' => $case['exam_type'],
                'clinical_information' => $case['clinical_information'] ?? '',
                'findings' => $case['findings'] ?? '',
                'impression' => $case['impression'] ?? '',
                'report_template' => $case['report_template'] ?? '',
                'status' => $case['status'],
                'is_amended' => (int) ($case['is_amended'] ?? 0),
                'amendment_notes' => $case['amendment_notes'] ?? '',
                'branch_name' => $case['branch_name'] ?? '',
                'created_at' => date('M d, Y h:i A', strtotime($case['created_at'])),
            ],
            'patient' => [
                'id' => $case['patient_id'],
                'first_name' => $case['first_name'],
                'last_name' => $case['last_name'],
                'middle_name' => $case['middle_name'] ?? '',
                'patient_number' => $case['patient_number'] ?? '',
                'age' => $case['age'] ?? '',
                'sex' => $case['sex'] ?? '',
            ],
            'dispute' => $activeDispute ? [
                'id' => $activeDispute['id'],
                'category' => $activeDispute['dispute_category'],
                'description' => $activeDispute['description'],
                'status' => $activeDispute['status'],
                'created_at' => date('M d, Y h:i A', strtotime($activeDispute['created_at'])),
            ] : null,
            'amendments' => $amendments
        ]);
        exit;

    } elseif ($action === 'save_radtech_amendment') {
        // RadTech edits findings, impression, patient name/DICOM name, or template
        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['radtech', 'branch_admin', 'admin_central'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
            exit;
        }

        $caseId = intval($_POST['case_id'] ?? 0);
        $disputeId = intval($_POST['dispute_id'] ?? 0);
        $findings = trim($_POST['findings'] ?? '');
        $impression = trim($_POST['impression'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $examType = trim($_POST['exam_type'] ?? '');
        $reportTemplate = trim($_POST['report_template'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $actionType = trim($_POST['action_type'] ?? 'save_and_release'); // 'save_only' or 'save_and_release'

        if (!$caseId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Case ID.']);
            exit;
        }

        // Fetch current case & patient
        $stmtC = $pdo->prepare("
            SELECT c.*, p.first_name as p_first_name, p.last_name as p_last_name, p.middle_name as p_middle_name,
                   p.email as p_email, u.id as patient_user_id, u.email as user_email
            FROM cases c
            JOIN patients p ON c.patient_id = p.id
            LEFT JOIN users u ON p.id = u.patient_id AND u.role = 'patient'
            WHERE c.id = ?
        ");
        $stmtC->execute([$caseId]);
        $currentCase = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$currentCase) {
            echo json_encode(['success' => false, 'message' => 'Case not found.']);
            exit;
        }

        // Branch check
        if ($role !== 'admin_central') {
            $sessionBranch = $_SESSION['branch_id'] ?? null;
            if ($sessionBranch && !empty($currentCase['branch_id']) && (int) $currentCase['branch_id'] !== (int) $sessionBranch) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized branch.']);
                exit;
            }
        }

        // Default values if blank
        if (empty($firstName)) $firstName = $currentCase['p_first_name'];
        if (empty($lastName)) $lastName = $currentCase['p_last_name'];
        if (empty($examType)) $examType = $currentCase['exam_type'] ?? 'General Exam';
        if (empty($reportTemplate)) $reportTemplate = $currentCase['report_template'] ?? 'General Standard';

        $oldName = trim($currentCase['p_first_name'] . ' ' . $currentCase['p_last_name']);
        $newName = trim($firstName . ' ' . $lastName);

        // 1. Record amendment in case_amendments table
        $amendmentModel->createAmendment([
            'case_id'              => $caseId,
            'dispute_id'           => $disputeId ?: null,
            'amended_by'           => $userId,
            'findings_before'      => $currentCase['findings'],
            'findings_after'       => $findings,
            'impression_before'    => $currentCase['impression'],
            'impression_after'     => $impression,
            'dicom_name_before'    => $oldName,
            'dicom_name_after'     => $newName,
            'template_before'      => $currentCase['report_template'],
            'template_after'       => $reportTemplate,
            'exam_type_before'     => $currentCase['exam_type'],
            'exam_type_after'      => $examType,
            'patient_name_before'  => $oldName,
            'patient_name_after'   => $newName,
            'notes'                => $notes ?: 'RadTech report, exam type & demographic amendment',
        ]);

        // 2. Update case record
        $stmtUpCase = $pdo->prepare("
            UPDATE cases
            SET findings = ?,
                impression = ?,
                exam_type = ?,
                report_template = ?,
                is_amended = 1,
                amendment_notes = ?
            WHERE id = ?
        ");
        $stmtUpCase->execute([$findings, $impression, $examType, $reportTemplate, $notes, $caseId]);

        // 3. Update patient demographics & linked user
        $stmtUpPatient = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, middle_name = ? WHERE id = ?");
        $stmtUpPatient->execute([$firstName, $lastName, $middleName, $currentCase['patient_id']]);

        $stmtUpUser = $pdo->prepare("UPDATE users SET name = ? WHERE patient_id = ? AND role = 'patient'");
        $stmtUpUser->execute([$newName, $currentCase['patient_id']]);

        // 4. Handle Status Transition
        if ($actionType === 'save_and_release') {
            // Forward to 'Resolved'
            $caseModel->transitionStatus($caseId, 'Resolved', $userId);
            
            // Mark dispute resolved
            if ($disputeId) {
                $disputeModel->updateDisputeStatus($disputeId, 'Resolved', 'radtech', 'Amended report released by RadTech. ' . $notes, $userId);
            } else {
                $activeDispute = $disputeModel->getActiveDisputeByCase($caseId);
                if ($activeDispute) {
                    $disputeModel->updateDisputeStatus($activeDispute['id'], 'Resolved', 'radtech', 'Amended report released by RadTech. ' . $notes, $userId);
                }
            }

            // In-App Notification to Patient
            if (!empty($currentCase['patient_user_id'])) {
                $notifTitle = "X-ray Report Amended & Released";
                $notifMsg = "Your error report for Case {$currentCase['case_number']} has been resolved and the updated report is now released.";
                $notifLink = "case-status?case_id={$caseId}";
                $pdo->prepare("INSERT INTO notifications (user_id, role, title, message, link, created_at) VALUES (?, 'patient', ?, ?, ?, NOW())")
                    ->execute([$currentCase['patient_user_id'], $notifTitle, $notifMsg, $notifLink]);
            }

            // Email to Patient
            $patientEmail = !empty($currentCase['user_email']) ? $currentCase['user_email'] : $currentCase['p_email'];
            if (!empty($patientEmail)) {
                require_once __DIR__ . '/../../app/Helpers/mailer_helper.php';
                $patientName = htmlspecialchars($newName);
                $caseNum = htmlspecialchars($currentCase['case_number']);
                $reportUrl = appBaseUrl() . "/" . PROJECT_DIR . "/case-status?case_id=" . $caseId;

                $emailSubject = "Amended X-ray Report Released - Citilife Diagnostic Center";
                $emailBody = renderNotificationEmail(
                    $patientName,
                    "Updated Report Released - Case #{$caseNum}",
                    "Your error report for Case <strong>{$caseNum}</strong> has been corrected and verified by our Radiologic Technologist. Your official updated X-ray report is now released and ready for viewing.",
                    [
                        'Case Number' => htmlspecialchars($caseNum),
                        'Patient' => htmlspecialchars($patientName),
                        'Status' => '<span style="color: #1a7f37; font-weight: 600;">Resolved &amp; Released</span>'
                    ],
                    "View Updated Report",
                    $reportUrl,
                    "You're receiving this notification because an error report for your case was resolved.",
                    "#1f883d"
                );
                sendEmail($patientEmail, $newName, $emailSubject, $emailBody);
            }

            $auditLog->addLog($userId, 'Amendment Released', 'RadTech Workflow', 'Case', $caseId, "Amended & Released case {$caseId}. Notes: {$notes}", $currentCase['branch_id']);

            echo json_encode([
                'success' => true,
                'status' => 'Resolved',
                'message' => 'Matagumpay na naiwasto at na-release ang Amended Report! Nai-notify na rin ang pasyente.'
            ]);
            exit;

        } else {
            // 'save_only' -> Forward to 'Correction Completed'
            $caseModel->transitionStatus($caseId, 'Correction Completed', $userId);

            if ($disputeId) {
                $disputeModel->updateDisputeStatus($disputeId, 'Correction Completed', 'radtech', 'Corrections applied by RadTech: ' . $notes);
            } else {
                $activeDispute = $disputeModel->getActiveDisputeByCase($caseId);
                if ($activeDispute) {
                    $disputeModel->updateDisputeStatus($activeDispute['id'], 'Correction Completed', 'radtech', 'Corrections applied by RadTech: ' . $notes);
                }
            }

            $auditLog->addLog($userId, 'Amendment Saved', 'RadTech Workflow', 'Case', $caseId, "Saved corrections for case {$caseId}. Notes: {$notes}", $currentCase['branch_id']);

            echo json_encode([
                'success' => true,
                'status' => 'Correction Completed',
                'message' => 'Matagumpay na nai-save ang corrections bilang "Correction Completed"! Handa na ito para sa final release.'
            ]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
