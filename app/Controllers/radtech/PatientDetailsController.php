<?php

namespace App\Controllers\radtech;

use Exception;
use CaseModel;
use NotificationModel;

class PatientDetailsController
{
    /**
     * Handles backend logic for patient details, image uploads, and submission to radiologist.
     *
     * @return array
     */
    public function handle()
    {
        global $pdo;

        $caseModel = new CaseModel($pdo);
        $notificationModel = new NotificationModel($pdo);

        // 1. Ensure Schema
        $caseModel->ensureSchema();

        $caseId    = (int)($_GET['id'] ?? 0);
        $fromParam = $_GET['from'] ?? '';
        $disputeId = (int)($_GET['dispute_id'] ?? 0);
        $errorMsg  = '';
        $successMsg = '';
        $branchId  = $_SESSION['branch_id'] ?? 1;

        if (!empty($_SESSION['flash_success'])) {
            $successMsg = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        // 2. Handle Amendment Save (from=disputes inline edit POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_amendment'])) {
            try {
                require_once __DIR__ . '/../../Models/ResultDisputeModel.php';

                $disputeMdl = new \ResultDisputeModel($pdo);
                $dId        = (int)($_POST['dispute_id'] ?? 0);
                $action     = $_POST['amendment_action'] ?? 'save_only';

                $auditNote = trim($_POST['amend_notes'] ?? 'Typo correction by RadTech');

                // Update findings/impression on the case ONLY if fields were present in the form
                if (isset($_POST['amend_findings']) || isset($_POST['amend_impression'])) {
                    $newFindings   = trim($_POST['amend_findings']   ?? '');
                    $newImpression = trim($_POST['amend_impression']  ?? '');
                    $stmtUpd = $pdo->prepare(
                        "UPDATE cases SET findings = ?, impression = ? WHERE id = ?"
                    );
                    $stmtUpd->execute([$newFindings ?: null, $newImpression ?: null, $caseId]);
                }

                // Update patient info if demographic fields were present in the form
                if (isset($_POST['amend_first_name']) || isset($_POST['amend_last_name']) || isset($_POST['amend_age']) || isset($_POST['amend_sex'])) {
                    $newFirstName  = trim($_POST['amend_first_name']  ?? '');
                    $newMiddleName = trim($_POST['amend_middle_name'] ?? '');
                    $newLastName   = trim($_POST['amend_last_name']   ?? '');
                    $newAge        = trim($_POST['amend_age']         ?? '');
                    $newSex        = trim($_POST['amend_sex']         ?? '');

                    // Sync to patients table
                    $patUpd = [];
                    $patParams = [];
                    if ($newFirstName)  { $patUpd[] = "first_name = ?";  $patParams[] = $newFirstName; }
                    if ($newMiddleName) { $patUpd[] = "middle_name = ?"; $patParams[] = $newMiddleName; }
                    if ($newLastName)   { $patUpd[] = "last_name = ?";   $patParams[] = $newLastName; }
                    if ($newSex)        { $patUpd[] = "sex = ?";         $patParams[] = $newSex; }
                    if ($newAge !== '' && (int)$newAge > 0) {
                        $birthYear = date('Y') - (int)$newAge;
                        $patUpd[] = "birthdate = ?";
                        $patParams[] = "{$birthYear}-01-01";
                    }
                    if (!empty($patUpd)) {
                        $stmtCasePat = $pdo->prepare("SELECT patient_id FROM cases WHERE id = ?");
                        $stmtCasePat->execute([$caseId]);
                        $pId = $stmtCasePat->fetchColumn();
                        if ($pId) {
                            $patParams[] = $pId;
                            $pdo->prepare("UPDATE patients SET " . implode(', ', $patUpd) . " WHERE id = ?")->execute($patParams);

                            // Also update linked patient user account if name was changed
                            if ($newFirstName || $newLastName) {
                                $stmtCurName = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE id = ?");
                                $stmtCurName->execute([$pId]);
                                $curPat = $stmtCurName->fetch(\PDO::FETCH_ASSOC);
                                $fullName = trim(($curPat['first_name'] ?? '') . ' ' . ($curPat['last_name'] ?? ''));
                                if ($fullName !== '') {
                                    $stmtU = $pdo->prepare("UPDATE users SET name = ? WHERE patient_id = ? AND role = 'patient'");
                                    $stmtU->execute([$fullName, $pId]);
                                }
                            }
                        }
                    }
                }

                // Update exam type if provided
                if (isset($_POST['amend_exam_type'])) {
                    $newExamType = trim($_POST['amend_exam_type'] ?? '');
                    if ($newExamType) {
                        $stmtExam = $pdo->prepare("UPDATE cases SET exam_type = ? WHERE id = ?");
                        $stmtExam->execute([$newExamType, $caseId]);
                    }
                }

                // Log amendment if model exists
                if (file_exists(__DIR__ . '/../../Models/CaseAmendmentModel.php')) {
                    require_once __DIR__ . '/../../Models/CaseAmendmentModel.php';
                    if (class_exists('CaseAmendmentModel')) {
                        try {
                            $amendmentMdl = new \CaseAmendmentModel($pdo);
                            $amendmentMdl->createAmendment([
                                'case_id'          => $caseId,
                                'dispute_id'       => $dId ?: null,
                                'amended_by'       => $_SESSION['user_id'] ?? 1,
                                'findings_after'   => $newFindings ?? null,
                                'impression_after' => $newImpression ?? null,
                                'notes'            => $auditNote,
                            ]);
                        } catch (\Throwable $logErr) {
                            error_log("Notice: Failed to log amendment: " . $logErr->getMessage());
                        }
                    }
                }

                // Advance dispute status
                if ($dId) {
                    if ($action === 'save_and_release' || $action === 'submit') {
                        $disputeMdl->advanceStatus($dId, 'Correction Completed');
                        $disputeMdl->advanceStatus($dId, 'Resolved');

                        // Mark case as Released and amended, and reset activity status
                        $pdo->prepare("UPDATE cases SET status = 'Released', released = 1, is_amended = 1, status_timestamp = NOW(), rad_activity_status = 'inactive', rad_last_active = '1970-01-01 00:00:00' WHERE id = ?")
                            ->execute([$caseId]);
                    } else {
                        $disputeMdl->advanceStatus($dId, 'Correction Completed');
                    }
                }

                $_SESSION['flash_success'] = ($action === 'save_and_release' || $action === 'submit')
                    ? 'Report amendments successfully saved and marked as Resolved.'
                    : 'Amendment saved. Dispute marked as Correction Completed.';

                $fromParam = $_GET['from'] ?? ($dId ? 'disputes' : 'queue');
                $redirectUrl = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-details&id=" . $caseId . "&from=" . urlencode($fromParam);
                if ($dId) {
                    $redirectUrl .= "&dispute_id=" . $dId;
                }
                $redirectUrl .= "&saved=1";

                header("Location: " . $redirectUrl);
                exit;
            } catch (\Throwable $e) {
                $errorMsg = "Error saving amendment: " . $e->getMessage();
            }
        }

        // 3. Handle Submit to Radiologist
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_radiologist'])) {
            try {
                $correctionType      = $_POST['correction_type'] ?? 'reupload';
                $radtechDisputeNotes = trim($_POST['radtech_dispute_notes'] ?? '');
                $keepExistingImage   = in_array($correctionType, ['typo', 'reread']);

                $submitData = [
                    'exam_type'            => $_POST['exam_type'] ?? '',
                    'priority'             => $_POST['priority'] ?? '',
                    'clinical_information' => $_POST['clinical_information'] ?? '',
                    'report_template'      => $_POST['exam_type'] ?? '',
                    'files'                => $_FILES['xray_image'] ?? null,
                    'keep_existing_image'  => $keepExistingImage,
                    'radtech_id'           => $_SESSION['user_id'] ?? null,
                    'radiologist_id'       => $_POST['radiologist_id'] ?? null
                ];

                $result = $caseModel->processRadTechSubmission($caseId, $submitData, $notificationModel);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    $fromParam = $_GET['from'] ?? '';
                    $redirectUrl = "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-details&id=" . $caseId;
                    if ($fromParam) {
                        $redirectUrl .= "&from=" . urlencode($fromParam);
                    }
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    $errorMsg = $result['message'];
                }
            } catch (Exception $e) {
                $errorMsg = "Error: " . $e->getMessage();
            }
        }

        // 4. Fetch Case & Patient Details
        $caseDetails = $caseModel->getCaseById($caseId);

        if (!$caseDetails || $caseDetails['branch_id'] != $branchId) {
            $caseNotFound     = true;
            $radiologistsList = [];
            $isAmendMode      = false;
            $activeDispute    = null;
        } else {
            $caseNotFound = false;

            // Fetch Radiologists with active case count
            $stmtRad = $pdo->prepare("
                SELECT 
                    u.id, 
                    COALESCE(NULLIF(u.full_name_report, ''), NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) AS radiologist_name,
                    COUNT(c.id) AS active_case_count,
                    u.is_available
                FROM users u
                LEFT JOIN cases c ON u.id = c.radiologist_id AND c.status IN ('Pending', 'Under Reading')
                WHERE u.role = 'radiologist' AND u.status = 'Active'
                GROUP BY u.id
            ");
            $stmtRad->execute();
            $radiologistsList = $stmtRad->fetchAll();

            // Fetch active dispute
            require_once __DIR__ . '/../../Models/ResultDisputeModel.php';
            $disputeMdl    = new \ResultDisputeModel($pdo);
            $activeDispute = $disputeMdl->getActiveDisputeByCase($caseId);

            // If active dispute not found, fallback if coming from disputes or saved
            if (!$activeDispute && ($disputeId || $fromParam === 'disputes' || (isset($_GET['saved']) && $_GET['saved'] == '1'))) {
                if ($disputeId) {
                    $stmtD = $pdo->prepare("SELECT * FROM result_disputes WHERE id = ? LIMIT 1");
                    $stmtD->execute([$disputeId]);
                    $activeDispute = $stmtD->fetch() ?: null;
                }
                if (!$activeDispute) {
                    $stmtD = $pdo->prepare("SELECT * FROM result_disputes WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
                    $stmtD->execute([$caseId]);
                    $activeDispute = $stmtD->fetch() ?: null;
                }
            }

            // 5. Amend Mode: Error report resolution is strictly between Patient & RadTech
            $isAmendMode = !empty($activeDispute);

            // When RadTech opens the case in amend mode and dispute is still 'Issue Reported', advance to 'For RadTech Review'
            $currentRole = $_SESSION['role'] ?? 'radtech';
            if ($activeDispute && in_array($activeDispute['status'], ['Issue Reported']) && in_array($currentRole, ['radtech', 'branch_admin', 'admin_central'])) {
                $disputeMdl->advanceStatus($activeDispute['id'], 'For RadTech Review');
                $activeDispute['status'] = 'For RadTech Review';
                $pdo->prepare("UPDATE cases SET status = 'For RadTech Review', status_timestamp = NOW() WHERE id = ?")
                    ->execute([$caseId]);
            }

            // 6. Page Logic (Read-only check)
            $isReadOnly  = ($currentRole !== 'radtech') || (
                in_array($caseDetails['status'], ['Pending', 'Under Reading', 'Report Ready', 'Completed', 'Released'])
                && $caseDetails['image_status'] === 'Uploaded'
                && (!$activeDispute || $activeDispute['status'] !== 'Pending RadTech Review')
            );

            // In amend mode, keep left panel read-only; findings card becomes inline-editable
            if ($isAmendMode || !empty($activeDispute)) {
                $isReadOnly = true;
            }

            $savedTemplate = $caseDetails['report_template'] ?? '';
        }

        return get_defined_vars();
    }
}
