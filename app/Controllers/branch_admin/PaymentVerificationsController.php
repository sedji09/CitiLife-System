<?php

namespace App\Controllers\branch_admin;

class PaymentVerificationsController
{
    public function handle()
    {
        global $pdo;

        /**
         * PaymentVerificationsController.php
         * Handles backend logic for Branch Admin to verify GCash payments.
         */

        $currentUserId = $_SESSION['user_id'] ?? 0;
        $branchId = $_SESSION['branch_id'] ?? 1;

        $successMsg = '';
        $errorMsg = '';

        if (!empty($_SESSION['flash_success'])) {
            $successMsg = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        if (!empty($_SESSION['flash_error'])) {
            $errorMsg = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        // Handle POST Actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            
            if ($paymentId > 0) {
                require_once __DIR__ . '/../../Models/AuditLogModel.php';
                $auditLogModel = new \AuditLogModel($pdo);

                if ($_POST['action'] === 'verify') {
                    $maxAttempts = 3;
                    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                        try {
                            $pdo->beginTransaction();
                            
                            // 1. Lock payment and request rows atomically
                            $stmtPayReq = $pdo->prepare("
                                SELECT p.id as payment_id, p.status as payment_status, r.* 
                                FROM payments p 
                                JOIN requests r ON p.request_id = r.id 
                                WHERE p.id = ? 
                                FOR UPDATE
                            ");
                            $stmtPayReq->execute([$paymentId]);
                            $reqData = $stmtPayReq->fetch();
                            
                            if (!$reqData) {
                                throw new \Exception("Payment or associated request not found.");
                            }

                            // Update payment status
                            $stmt = $pdo->prepare("UPDATE payments SET status = 'Verified', verified_by = ? WHERE id = ?");
                            $stmt->execute([$currentUserId, $paymentId]);
                            
                            $reqId = $reqData['id'];
                            $reqBranchId = $reqData['branch_id'] ?: $branchId;
                            
                            // 2. Update request status to Approved
                            $stmtCase = $pdo->prepare("UPDATE requests SET status = 'Approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
                            $stmtCase->execute([$reqId]);
                            
                            // 3. Generate case number and insert into cases table (moves directly to RadTech Patient Queue)
                            require_once __DIR__ . '/../../Models/CaseModel.php';
                            $caseModel = new \CaseModel($pdo);
                            $caseNumber = $caseModel->generateCaseNumber($reqBranchId);
                            
                            $stmtInsertCase = $pdo->prepare("
                                INSERT INTO cases (case_number, patient_id, branch_id, exam_type, priority, philhealth_status, philhealth_id, status, request_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
                            ");
                            $stmtInsertCase->execute([
                                $caseNumber,
                                $reqData['patient_id'],
                                $reqBranchId,
                                $reqData['exam_type'],
                                $reqData['priority'],
                                $reqData['philhealth_status'],
                                $reqData['philhealth_id'],
                                $reqId
                            ]);
                            $newCaseId = $pdo->lastInsertId();
                            
                            // 4. Record audit log
                            $details = "Verified payment ID: {$paymentId} for Request #{$reqData['request_number']}. Created Case #{$caseNumber} (ID: {$newCaseId}) and moved directly to RadTech Patient Queue.";
                            $auditLogModel->addLog($currentUserId, 'Approved & Created Case', 'Payment Verifications', 'Case', $newCaseId, $details, $reqBranchId);

                            // 5. Send notifications
                            require_once __DIR__ . '/../../Models/NotificationModel.php';
                            $notifModel = new \NotificationModel($pdo);
                            
                            // Send to Patient
                            $stmtPat = $pdo->prepare("
                                SELECT u.id as user_id, u.email, u.name 
                                FROM users u 
                                WHERE u.patient_id = ? AND u.role = 'patient'
                                LIMIT 1
                            ");
                            $stmtPat->execute([$reqData['patient_id']]);
                            $patUser = $stmtPat->fetch();
                            
                            if ($patUser) {
                                $notifModel->add(
                                    "Request Approved",
                                    "Your payment for request {$reqData['request_number']} has been verified and approved (Case #{$caseNumber}). Please proceed to the X-ray room for examination.",
                                    "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&case_id=" . urlencode($newCaseId) . "&highlight=" . urlencode($caseNumber),
                                    $patUser['user_id'],
                                    'patient'
                                );
                            }
                            
                            // Send Notification to RadTech team at this branch
                            $notifModel->add(
                                "New Patient in Queue",
                                "Case #{$caseNumber} ({$reqData['exam_type']}) payment verified and approved. Ready for X-ray examination.",
                                "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-lists&highlight=" . urlencode($caseNumber),
                                null,
                                'radtech',
                                $reqBranchId
                            );
                            
                            $pdo->commit();

                            // Send Email to Patient if available (outside transaction)
                            if ($patUser && !empty($patUser['email'])) {
                                require_once __DIR__ . '/../../Helpers/mailer_helper.php';
                                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                                $portalUrl = $baseUrl . (defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '') . '/index.php?role=patient&page=dashboard';
                                $patientName = $patUser['name'] ?: 'Patient';
                                $subject = "Your X-ray Request ({$caseNumber}) is Approved - Citilife System";
                                $emailBody = renderNotificationEmail(
                                    $patientName,
                                    "Payment Verified & Request Approved",
                                    "Good news! Your payment has been verified by the branch admin and your request is now approved. You may proceed to the X-ray room for your examination.",
                                    [
                                        'Case Number' => htmlspecialchars($caseNumber),
                                        'Request Number' => htmlspecialchars($reqData['request_number']),
                                        'Examination' => htmlspecialchars($reqData['exam_type'] ?: 'N/A'),
                                        'Status' => '<span style="color: #1a7f37; font-weight: 600;">Approved &amp; Queued</span>'
                                    ],
                                    "View Case Status",
                                    $portalUrl,
                                    "You're receiving this notification regarding your X-ray examination at Citilife.",
                                    "#16a34a"
                                );
                                sendEmailAsync($patUser['email'], $patientName, $subject, $emailBody);
                            }

                            $_SESSION['flash_success'] = "Payment verified successfully. Request has been approved and moved to the patient queue.";
                            break; // Exit retry loop on success
                        } catch (\PDOException $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            $isDeadlock = ($e->getCode() == 40001 || strpos($e->getMessage(), '1213') !== false || stripos($e->getMessage(), 'deadlock') !== false);
                            if ($isDeadlock && $attempt < $maxAttempts) {
                                usleep(150000); // wait 150ms before retry
                                continue;
                            }
                            $_SESSION['flash_error'] = "Error verifying payment: " . $e->getMessage();
                            break;
                        } catch (\Exception $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            $_SESSION['flash_error'] = "Error verifying payment: " . $e->getMessage();
                            break;
                        }
                    }
                    redirect(url('payment-verifications'));
                } elseif ($_POST['action'] === 'reject') {
                    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
                    if (empty($rejectionReason)) {
                        $_SESSION['flash_error'] = "A reason is required to reject a payment confirmation.";
                        redirect(url('payment-verifications'));
                    }

                    try {
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("UPDATE payments SET status = 'Rejected', rejection_reason = ?, verified_by = ? WHERE id = ?");
                        $stmt->execute([$rejectionReason, $currentUserId, $paymentId]);
                        
                        // Get request_id associated with this payment
                        $stmtReq = $pdo->prepare("SELECT request_id FROM payments WHERE id = ?");
                        $stmtReq->execute([$paymentId]);
                        $reqId = $stmtReq->fetchColumn();
                        
                        if ($reqId) {
                            // Revert request status to 'Pending Payment' so the patient can re-upload / resubmit
                            $stmtCase = $pdo->prepare("UPDATE requests SET status = 'Pending Payment', rejection_reason = ? WHERE id = ?");
                            $stmtCase->execute([$rejectionReason, $reqId]);

                            // Send notification to patient
                            require_once __DIR__ . '/../../Models/NotificationModel.php';
                            $notifModel = new \NotificationModel($pdo);
                            
                            $stmtPat = $pdo->prepare("
                                SELECT u.id as user_id, r.request_number 
                                FROM requests r 
                                JOIN users u ON r.patient_id = u.patient_id 
                                WHERE r.id = ? AND u.role = 'patient'
                            ");
                            $stmtPat->execute([$reqId]);
                            $patData = $stmtPat->fetch();
                            
                            if ($patData) {
                                $notifModel->add(
                                    "Payment Rejected",
                                    "Your payment for request {$patData['request_number']} was returned: \"{$rejectionReason}\". Please resubmit your payment details with the correct reference number and receipt screenshot.",
                                    url('dashboard?highlight=' . urlencode($patData['request_number'])),
                                    $patData['user_id'],
                                    'patient'
                                );
                            }
                        }
                        
                        $reqNumStr = isset($patData['request_number']) ? " (Request: {$patData['request_number']})" : "";
                        $details = "Rejected payment ID: {$paymentId}{$reqNumStr}. Reason: {$rejectionReason}";
                        $auditLogModel->addLog($currentUserId, 'Rejected Payment', 'Payment Verifications', 'Payment', $paymentId, $details, $branchId);
                        
                        $pdo->commit();
                        $_SESSION['flash_success'] = "Payment rejected. Request has returned to the patient with your reason so they can resubmit the correct reference number and receipt screenshot.";
                    } catch (\Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $_SESSION['flash_error'] = "Error rejecting payment: " . $e->getMessage();
                    }
                    redirect(url('payment-verifications'));
                }
            }
        }

        // Fetch pending payments
        // We join `payments` -> `requests` -> `patients`
        $stmtPending = $pdo->prepare("
            SELECT p.*, r.request_number, r.exam_type, r.priority, r.submitted_at,
                   r.philhealth_status, r.philhealth_id,
                   pat.first_name, pat.last_name, pat.contact_number
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN patients pat ON r.patient_id = pat.id
            WHERE r.branch_id = ? AND p.status = 'Pending Verification'
            ORDER BY p.created_at DESC
        ");
        $stmtPending->execute([$branchId]);
        $pendingPayments = $stmtPending->fetchAll();
        
        // Fetch verified/rejected payments (History) - Fetch all for JS processing
        $stmtHistory = $pdo->prepare("
            SELECT p.*, r.request_number, r.exam_type, r.philhealth_status, r.philhealth_id,
                   pat.first_name, pat.last_name
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN patients pat ON r.patient_id = pat.id
            WHERE r.branch_id = ? AND p.status != 'Pending Verification'
            ORDER BY p.updated_at DESC
        ");
        $stmtHistory->execute([$branchId]);
        $paymentHistory = $stmtHistory->fetchAll();

        // AJAX response for live polling
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'pending' => array_map(function($p) {
                    return [
                        'id' => (int)$p['id'],
                        'request_number' => $p['request_number'],
                        'first_name' => $p['first_name'],
                        'last_name' => $p['last_name'],
                        'exam_type' => $p['exam_type'],
                        'philhealth_status' => $p['philhealth_status'],
                        'philhealth_id' => $p['philhealth_id'],
                        'amount' => (float)$p['amount'],
                        'original_amount' => (float)($p['original_amount'] ?? $p['amount']),
                        'discount_amount' => (float)($p['discount_amount'] ?? 0),
                        'payment_method' => $p['payment_method'],
                        'reference_number' => $p['reference_number'],
                        'proof_of_payment_path' => $p['proof_of_payment_path'],
                        'created_at' => $p['created_at'],
                        'created_at_formatted' => date('M d, Y h:i A', strtotime($p['created_at'])),
                        'timestamp' => strtotime($p['created_at'])
                    ];
                }, $pendingPayments),
                'history' => array_map(function($p) {
                    return [
                        'id' => (int)$p['id'],
                        'request_number' => $p['request_number'],
                        'first_name' => $p['first_name'],
                        'last_name' => $p['last_name'],
                        'exam_type' => $p['exam_type'],
                        'philhealth_status' => $p['philhealth_status'],
                        'philhealth_id' => $p['philhealth_id'],
                        'amount' => (float)$p['amount'],
                        'payment_method' => $p['payment_method'],
                        'reference_number' => $p['reference_number'],
                        'rejection_reason' => $p['rejection_reason'] ?? null,
                        'status' => $p['status'],
                        'updated_at' => $p['updated_at'],
                        'updated_at_formatted' => date('M d, Y h:i A', strtotime($p['updated_at'])),
                        'timestamp' => strtotime($p['updated_at'])
                    ];
                }, $paymentHistory),
                'pendingCount' => count($pendingPayments)
            ]);
            exit;
        }

        // Check if we are currently on the history tab (if tab is set to history)
        $activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'history') ? 'history' : 'pending';

        return get_defined_vars();
    }
}
