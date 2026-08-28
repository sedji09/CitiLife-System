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
                if ($_POST['action'] === 'verify') {
                    try {
                        $pdo->beginTransaction();
                        
                        // Update payment status
                        $stmt = $pdo->prepare("UPDATE payments SET status = 'Verified', verified_by = ? WHERE id = ?");
                        $stmt->execute([$currentUserId, $paymentId]);
                        
                        // Get request_id associated with this payment
                        $stmtReq = $pdo->prepare("SELECT request_id FROM payments WHERE id = ?");
                        $stmtReq->execute([$paymentId]);
                        $reqId = $stmtReq->fetchColumn();
                        
                        if ($reqId) {
                            // Update request status so RadTech can perform final approval
                            $stmtCase = $pdo->prepare("UPDATE requests SET status = 'Payment Verified' WHERE id = ?");
                            $stmtCase->execute([$reqId]);
                            
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
                                    "Payment Verified",
                                    "Your payment for request {$patData['request_number']} has been successfully verified. Please wait for RadTech approval.",
                                    "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status",
                                    $patData['user_id'],
                                    'patient'
                                );
                            }
                        }
                        
                        $pdo->commit();
                        $_SESSION['flash_success'] = "Payment verified successfully. RadTech can now approve the request.";
                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['flash_error'] = "Error verifying payment: " . $e->getMessage();
                    }
                    header("Location: /" . PROJECT_DIR . "/index.php?role=branch_admin&page=payment-verifications");
                    exit;
                } elseif ($_POST['action'] === 'reject') {
                    try {
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("UPDATE payments SET status = 'Rejected', verified_by = ? WHERE id = ?");
                        $stmt->execute([$currentUserId, $paymentId]);
                        
                        // Optionally reject the request too, or leave it for patient to re-upload
                        $stmtReq = $pdo->prepare("SELECT request_id FROM payments WHERE id = ?");
                        $stmtReq->execute([$paymentId]);
                        $reqId = $stmtReq->fetchColumn();
                        
                        if ($reqId) {
                            $stmtCase = $pdo->prepare("UPDATE requests SET status = 'Rejected', rejection_reason = 'Payment Rejected' WHERE id = ?");
                            $stmtCase->execute([$reqId]);
                        }
                        
                        $pdo->commit();
                        $_SESSION['flash_success'] = "Payment rejected.";
                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['flash_error'] = "Error rejecting payment: " . $e->getMessage();
                    }
                    header("Location: /" . PROJECT_DIR . "/index.php?role=branch_admin&page=payment-verifications");
                    exit;
                }
            }
        }

        // Fetch pending payments
        // We join `payments` -> `requests` -> `patients`
        $stmtPending = $pdo->prepare("
            SELECT p.*, r.request_number, r.exam_type, r.priority, r.submitted_at,
                   pat.first_name, pat.last_name, pat.contact_number
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN patients pat ON r.patient_id = pat.id
            WHERE r.branch_id = ? AND p.status = 'Pending Verification'
            ORDER BY p.created_at DESC
        ");
        $stmtPending->execute([$branchId]);
        $pendingPayments = $stmtPending->fetchAll();
        
        // Search & Pagination for History Tab
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $historyConditions = "r.branch_id = ? AND p.status != 'Pending Verification'";
        $historyParams = [$branchId];

        if ($search !== '') {
            $historyConditions .= " AND (r.request_number LIKE ? OR pat.first_name LIKE ? OR pat.last_name LIKE ? OR CONCAT(pat.first_name, ' ', pat.last_name) LIKE ?)";
            $searchWildcard = '%' . $search . '%';
            $historyParams[] = $searchWildcard;
            $historyParams[] = $searchWildcard;
            $historyParams[] = $searchWildcard;
            $historyParams[] = $searchWildcard;
        }

        // Fetch total count for pagination
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN patients pat ON r.patient_id = pat.id
            WHERE $historyConditions
        ");
        $stmtCount->execute($historyParams);
        $totalHistory = $stmtCount->fetchColumn();
        $totalPages = ceil($totalHistory / $limit);

        // Fetch verified/rejected payments (History)
        $stmtHistory = $pdo->prepare("
            SELECT p.*, r.request_number, r.exam_type, pat.first_name, pat.last_name
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN patients pat ON r.patient_id = pat.id
            WHERE $historyConditions
            ORDER BY p.updated_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmtHistory->execute($historyParams);
        $paymentHistory = $stmtHistory->fetchAll();

        // Check if we are currently on the history tab (if search or page is active)
        $activeTab = (isset($_GET['search']) || isset($_GET['page_num']) || (isset($_GET['tab']) && $_GET['tab'] === 'history')) ? 'history' : 'pending';

        return get_defined_vars();
    }
}
