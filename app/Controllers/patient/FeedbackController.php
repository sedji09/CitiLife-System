<?php

namespace App\Controllers\patient;

class FeedbackController
{
    public function handle()
    {
        global $pdo;

        require_once __DIR__ . '/../../Models/FeedbackModel.php';
        require_once __DIR__ . '/../../Models/PatientModel.php';
        require_once __DIR__ . '/../../Models/AuditLogModel.php';
        require_once __DIR__ . '/../../Helpers/mailer_helper.php';

        $feedbackModel = new \FeedbackModel($pdo);
        $patientModel = new \PatientModel($pdo);
        $notificationModel = new \NotificationModel($pdo);

        // Ensure table exists
        $feedbackModel->ensureSchema();

        $userId = $_SESSION['user_id'] ?? 0;

        // Get patient data
        $patientData = $patientModel->getPatientByUserId($userId);
        $patientId = $patientData['id'] ?? null;
        $branchId = $patientData['branch_id'] ?? null;

        $caseId = $_GET['case_id'] ?? null;
        $caseData = null;
        if ($caseId) {
            require_once __DIR__ . '/../../Models/CaseModel.php';
            $caseModel = new \CaseModel($pdo);
            $caseData = $caseModel->getCaseById($caseId);
        }

        $successMsg = '';
        $errorMsg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
            $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int) $_POST['rating'] : 0;
            $comments = trim($_POST['comments'] ?? '');
            $postCaseId = $_POST['case_id'] ?? null;
            if ($postCaseId === '')
                $postCaseId = null;

            if (!$patientId) {
                $errorMsg = "You must have a completed patient profile to submit feedback.";
            } elseif ($rating < 1 || $rating > 5) {
                $errorMsg = "Please provide a star rating to let us know how we did!";
            } else {
                // Check if case already has feedback
                $feedbackCaseIds = $feedbackModel->getPatientFeedbackCaseIds($patientId);
                if ($postCaseId && in_array($postCaseId, $feedbackCaseIds)) {
                    $errorMsg = "You have already submitted feedback for this case.";
                } else {
                    try {
                        $feedbackModel->submitFeedback($postCaseId, $patientId, $userId, $branchId, $rating, $comments);
                        $successMsg = "Thank you! Your feedback has been submitted successfully.";

                        // Notify Branch Admin
                        if ($branchId) {
                            $patientName = $patientData['first_name'] . ' ' . $patientData['last_name'];

                            // Add audit log for patient feedback
                            $auditLogModel = new \AuditLogModel($pdo);
                            $actionMsg = "Submitted feedback" . ($postCaseId ? " for case #{$postCaseId}" : "");
                            $auditLogModel->addLog(
                                $userId,
                                $actionMsg,
                                'Patient Feedback',
                                'feedback',
                                $postCaseId, // entity_id
                                "Comments: " . ($comments ?: 'No comments provided.'),
                                $branchId
                            );

                            $notificationModel->add(
                                "New Patient Feedback",
                                "Patient $patientName submitted a {$rating}-star rating.",
                                "/" . PROJECT_DIR . "/index.php?role=branch_admin&page=feedback",
                                null,
                                'branch_admin',
                                $branchId
                            );
                        }

                        // Notify Central Admin
                        $notificationModel->add(
                            "New Patient Feedback",
                            "A new {$rating}-star rating was submitted by a patient.",
                            "/" . PROJECT_DIR . "/index.php?role=admin_central&page=feedback",
                            null,
                            'admin_central'
                        );

                        // EMAIL ALERTS FOR CRITICAL FEEDBACK
                        if ($rating <= 2) {
                            $patientNameForEmail = $patientData['first_name'] . ' ' . $patientData['last_name'];
                            $branchName = 'Unknown Branch';
                            if ($branchId) {
                                $stmtBranch = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
                                $stmtBranch->execute([$branchId]);
                                $branchName = $stmtBranch->fetchColumn() ?: 'Unknown Branch';
                            }
                            
                            $emailSubject = "(URGENT) Critical Patient Feedback - {$branchName}";
                            $emailBody = "
                                <h2>Critical Patient Feedback Received</h2>
                                <p>A patient has submitted a <strong>{$rating}-Star</strong> feedback rating.</p>
                                <ul>
                                    <li><strong>Patient Name:</strong> {$patientNameForEmail}</li>
                                    <li><strong>Branch:</strong> {$branchName}</li>
                                    <li><strong>Case Number:</strong> " . ($postCaseId ?: 'N/A') . "</li>
                                    <li><strong>Rating:</strong> {$rating} / 5</li>
                                    <li><strong>Comments:</strong><br><em>" . nl2br(htmlspecialchars($comments)) . "</em></li>
                                </ul>
                                <p>Please log in to the dashboard to review and address this feedback immediately.</p>
                            ";

                            $emailsToNotify = [];
                            
                            // Get branch admins
                            if ($branchId) {
                                $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'branch_admin' AND branch_id = ? AND status = 'Active'");
                                $stmt->execute([$branchId]);
                                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                                    if (!empty($row['email'])) $emailsToNotify[] = $row['email'];
                                }
                            }
                            
                            // Get central admins
                            $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin_central' AND status = 'Active'");
                            $stmt->execute();
                            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                                if (!empty($row['email'])) $emailsToNotify[] = $row['email'];
                            }
                            
                            $emailsToNotify = array_unique($emailsToNotify);
                            
                            foreach ($emailsToNotify as $adminEmail) {
                                sendEmail($adminEmail, "Administrator", $emailSubject, $emailBody);
                            }
                        }

                    } catch (\Exception $e) {
                        $errorMsg = "An error occurred while submitting your feedback. Please try again later.";
                    }
                } // Close inner else
            } // Close outer else
        } // Close if POST

        // Render view (feedbacks array is no longer passed as past feedback is hidden)

        return get_defined_vars();
    }
}
