<?php

namespace App\Controllers\radtech;

class PatientApprovalController
{
    public function handle()
    {
        global $pdo;

        require_once __DIR__ . '/../../Models/CaseModel.php';
        require_once __DIR__ . '/../../Models/NotificationModel.php';
        require_once __DIR__ . '/../../Models/AuditLogModel.php';

        $caseModel = new \CaseModel($pdo);
        $notificationModel = new \NotificationModel($pdo);
        $auditLogModel = new \AuditLogModel($pdo);

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

        // Handle GET Actions (like approve)
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'approve' && isset($_GET['id'])) {
                $requestId = (int)$_GET['id'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Verify request belongs to this branch and is Payment Verified
                    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ? AND branch_id = ? AND status = 'Payment Verified'");
                    $stmt->execute([$requestId, $branchId]);
                    $req = $stmt->fetch();
                    
                    if (!$req) {
                        throw new \Exception("Request not found or not ready for approval.");
                    }
                    
                    // Update request status to Approved
                    $stmtUpdate = $pdo->prepare("UPDATE requests SET status = 'Approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmtUpdate->execute([$requestId]);
                    
                    // Generate a case number and insert into cases table
                    $caseNumber = $caseModel->generateCaseNumber($branchId);
                    $stmtCase = $pdo->prepare("INSERT INTO cases (case_number, patient_id, branch_id, exam_type, priority, philhealth_status, philhealth_id, status, request_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
                    $stmtCase->execute([
                        $caseNumber, 
                        $req['patient_id'], 
                        $branchId, 
                        $req['exam_type'],
                        $req['priority'], 
                        $req['philhealth_status'], 
                        $req['philhealth_id'],
                        $requestId
                    ]);
                    $newCaseId = $pdo->lastInsertId();
                    
                    $auditLogModel->addLog($currentUserId, "Approved Patient Request", 'Patient Approval', 'Request', $requestId, "Approved request #{$req['request_number']} and created Case #{$caseNumber}", $branchId);
                    
                    $pdo->commit();
                    $_SESSION['flash_success'] = "Patient request has been finally approved. They can now proceed to X-ray.";
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Approval failed: " . $e->getMessage();
                }
                
                header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=patient-details&id=" . urlencode($newCaseId));
                exit;
            } elseif ($_GET['action'] === 'reject' && isset($_GET['id'])) {
                $requestId = (int)$_GET['id'];
                $reason = $_POST['rejection_reason'] ?? '';

                try {
                    $pdo->beginTransaction();
                    
                    $stmtUpdate = $pdo->prepare("UPDATE requests SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
                    $stmtUpdate->execute([$reason, $requestId]);
                    
                    $auditLogModel->addLog($currentUserId, "Rejected Patient Request", 'Patient Approval', 'Request', $requestId, "Rejected request with reason: " . ($reason ?: "No reason provided"), $branchId);
                    
                    $pdo->commit();
                    $_SESSION['flash_success'] = "Request rejected.";
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Rejection failed: " . $e->getMessage();
                }
                
                $redirectBase = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? '/' . PROJECT_DIR : '';
                header("Location: " . $redirectBase . "/patient-approval");
                exit;
            }
        }

        // Handle POST Actions (like assign_exam or reject)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
            if ($_GET['action'] === 'assign_exam' && isset($_GET['id'])) {
                $requestId = (int)$_GET['id'];
                $examType = $_POST['exam_type'] ?? '';

                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ? AND branch_id = ? AND (status = 'Pending Approval' OR status = 'Pending' OR status = 'Pending Payment')");
                    $stmt->execute([$requestId, $branchId]);
                    $req = $stmt->fetch();
                    
                    if (!$req) {
                        throw new \Exception("Request not found or not in pending state.");
                    }

                    // Calculate total original price and PhilHealth discount based on selected exams
                    $examArray = array_filter(array_map('trim', explode(',', $examType)));
                    $originalPrice = 0.00;
                    $philhealthDiscount = 0.00;
                    $hasPhilHealth = ($req['philhealth_status'] === 'With PhilHealth Card');

                    if (!empty($examArray)) {
                        $placeholders = implode(',', array_fill(0, count($examArray), '?'));
                        $stmtServices = $pdo->prepare("SELECT exam_type, price, is_philhealth_covered, philhealth_discount FROM xray_services WHERE exam_type IN ($placeholders) AND status = 'active'");
                        $stmtServices->execute($examArray);
                        $services = $stmtServices->fetchAll();

                        foreach ($services as $srv) {
                            $price = (float)$srv['price'];
                            $originalPrice += $price;

                            if ($hasPhilHealth && (int)$srv['is_philhealth_covered'] === 1) {
                                $discount = (float)$srv['philhealth_discount'];
                                // Discount cannot exceed individual procedure price
                                $philhealthDiscount += min($discount, $price);
                            }
                        }
                    }

                    $amountDue = max(0.00, $originalPrice - $philhealthDiscount);
                    
                    // Update request with exam type, original price, PhilHealth discount, and amount due, set to Pending Payment
                    $stmtUpdate = $pdo->prepare("UPDATE requests SET exam_type = ?, original_price = ?, philhealth_discount = ?, amount_due = ?, status = 'Pending Payment' WHERE id = ?");
                    $stmtUpdate->execute([$examType, $originalPrice, $philhealthDiscount, $amountDue, $requestId]);
                    
                    $auditLogModel->addLog($currentUserId, "Assigned Exam", 'Patient Approval', 'Request', $requestId, "Assigned $examType (Original: PHP $originalPrice, PhilHealth Discount: PHP $philhealthDiscount, Due: PHP $amountDue) to request #{$req['request_number']}", $branchId);
                    
                    $pdo->commit();
                    $_SESSION['flash_success'] = "Exam assigned successfully. Awaiting patient payment.";
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Assignment failed: " . $e->getMessage();
                }
                
                $redirectBase = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? '/' . PROJECT_DIR : '';
                header("Location: " . $redirectBase . "/patient-approval");
                exit;
        }

        // Fetch patients pending approval, pending payment, or payment verified
        $stmt = $pdo->prepare("
            SELECT r.*, p.first_name, p.last_name, p.patient_number, p.birthdate, p.sex, p.contact_number, p.home_address
            FROM requests r
            JOIN patients p ON r.patient_id = p.id
            WHERE r.branch_id = ? AND r.status IN ('Pending Approval', 'Pending', 'Pending Payment', 'Payment Verifying', 'Payment Verified')
            ORDER BY CASE WHEN r.status = 'Payment Verified' THEN 1 WHEN r.status = 'Pending Approval' THEN 2 ELSE 3 END, r.created_at ASC
        ");
        $stmt->execute([$branchId]);
        $patientsToApprove = $stmt->fetchAll();

        // Pass exam services to view
        $stmtServices = $pdo->prepare("SELECT id, exam_type AS name, price, category FROM xray_services WHERE status = 'active'");
        $stmtServices->execute();
        $examServices = $stmtServices->fetchAll();

        return get_defined_vars();
    }
}
