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
        require_once __DIR__ . '/../../Helpers/mailer_helper.php';
        require_once __DIR__ . '/../../Helpers/email_template_helper.php';

        $caseModel = new \CaseModel($pdo);
        $caseModel->ensureSchema();
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
                    
                    // Send notification and email to patient
                    $stmtPat = $pdo->prepare("SELECT u.id, u.name, u.email FROM users u WHERE u.patient_id = ? AND u.role = 'patient' LIMIT 1");
                    $stmtPat->execute([$req['patient_id']]);
                    $patUser = $stmtPat->fetch();
                    if ($patUser) {
                        $notificationModel->add(
                            "Request Approved",
                            "Your X-ray request ({$caseNumber}) has been approved. Please proceed to the X-ray room.",
                            "/" . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') . "/index.php?role=patient&page=dashboard",
                            $patUser['id'],
                            'patient'
                        );

                        if (!empty($patUser['email'])) {
                            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                            $portalUrl = $baseUrl . (defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '') . '/index.php?role=patient&page=dashboard';
                            $patientName = $patUser['name'] ?: 'Patient';
                            $subject = "Your X-ray Request ({$caseNumber}) is Approved - Citilife System";
                            $emailBody = renderNotificationEmail(
                                $patientName,
                                "X-ray Request Approved",
                                "Good news! Your X-ray request has been approved. You may now proceed to the X-ray room for examination.",
                                [
                                    'Case Number' => htmlspecialchars($caseNumber),
                                    'Examination' => htmlspecialchars($req['exam_type'] ?: 'N/A'),
                                    'Status' => '<span style="color: #1a7f37; font-weight: 600;">Approved</span>'
                                ],
                                "View Case Status",
                                $portalUrl,
                                "You're receiving this notification regarding your X-ray examination at Citilife.",
                                "#16a34a"
                            );
                            sendEmailAsync($patUser['email'], $patientName, $subject, $emailBody);
                        }
                    }

                    $pdo->commit();
                    $_SESSION['flash_success'] = "Patient request has been finally approved. They can now proceed to X-ray.";
                    
                    $redirectBase = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? '/' . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') : '';
                    header("Location: " . $redirectBase . "/patient-details?role=radtech&id=" . urlencode($newCaseId) . "&from=approval");
                    exit;
                } catch (\Throwable $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Approval failed: " . $e->getMessage();
                    $redirectBase = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? '/' . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') : '';
                    header("Location: " . $redirectBase . "/patient-approval");
                    exit;
                }
            } elseif ($_GET['action'] === 'reject' && isset($_GET['id'])) {
                $requestId = (int)$_GET['id'];
                $reason = $_POST['rejection_reason'] ?? '';

                try {
                    $pdo->beginTransaction();
                    
                    // Fetch request details before updating
                    $stmtReq = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
                    $stmtReq->execute([$requestId]);
                    $reqData = $stmtReq->fetch();

                    $stmtUpdate = $pdo->prepare("UPDATE requests SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
                    $stmtUpdate->execute([$reason, $requestId]);
                    
                    $auditLogModel->addLog($currentUserId, "Rejected Patient Request", 'Patient Approval', 'Request', $requestId, "Rejected request with reason: " . ($reason ?: "No reason provided"), $branchId);
                    
                    // Send notification and email to patient
                    if ($reqData) {
                        $stmtPat = $pdo->prepare("SELECT u.id, u.name, u.email FROM users u WHERE u.patient_id = ? AND u.role = 'patient' LIMIT 1");
                        $stmtPat->execute([$reqData['patient_id']]);
                        $patUser = $stmtPat->fetch();
                        if ($patUser) {
                            $reqNum = $reqData['request_number'] ?: ('REQ-' . str_pad($requestId, 5, '0', STR_PAD_LEFT));
                            $notificationModel->add(
                                "Request Rejected",
                                "Your X-ray request ({$reqNum}) has been rejected." . ($reason ? " Reason: {$reason}" : " Please contact the clinic for more details or submit a new request."),
                                "/" . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') . "/index.php?role=patient&page=dashboard",
                                $patUser['id'],
                                'patient'
                            );

                            if (!empty($patUser['email'])) {
                                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                                $portalUrl = $baseUrl . (defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '') . '/index.php?role=patient&page=dashboard';
                                $patientName = $patUser['name'] ?: 'Patient';
                                $subject = "Update on Your X-ray Request ({$reqNum}) - Citilife System";
                                $emailBody = renderNotificationEmail(
                                    $patientName,
                                    "X-ray Request Rejected",
                                    "Your X-ray examination request has been reviewed by the clinic staff and was rejected.",
                                    [
                                        'Request Number' => htmlspecialchars($reqNum),
                                        'Examination' => htmlspecialchars($reqData['exam_type'] ?: 'N/A'),
                                        'Status' => '<span style="color: #cf222e; font-weight: 600;">Rejected</span>',
                                        'Reason' => htmlspecialchars($reason ?: 'Please contact the clinic for more details or submit a new request.')
                                    ],
                                    "Go to Patient Portal",
                                    $portalUrl,
                                    "You're receiving this notification regarding your X-ray examination request at Citilife.",
                                    "#dc2626"
                                );
                                sendEmailAsync($patUser['email'], $patientName, $subject, $emailBody);
                            }
                        }
                    }

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
                    if (empty($examArray)) {
                        throw new \Exception("Please select at least one examination procedure.");
                    }

                    // Validate that assigned exams match the patient's requested body part(s)
                    // AND that each body part has at least one assigned exam
                    $requestedExamStr = trim($req['exam_type'] ?? '');
                    if ($requestedExamStr !== '' && strtolower($requestedExamStr) !== 'to be determined' && strtolower($requestedExamStr) !== 'not specified') {
                        $stmtAll = $pdo->query("SELECT exam_type, category FROM xray_services WHERE status = 'active'");
                        $allActiveServices = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);
                        
                        $bodyPartAliases = [
                            'head' => ['Skull', 'Head'],
                            'skull' => ['Skull', 'Head'],
                            'face / nose' => ['Skull', 'Head', 'Facial'],
                            'jaw' => ['Skull', 'Head', 'Mandible'],
                            'chest' => ['Chest', 'Thorax', 'Lungs'],
                            'abdomen' => ['Abdomen', 'Stomach'],
                            'abdomen / stomach' => ['Abdomen', 'Stomach'],
                            'spine' => ['Spine'],
                            'neck' => ['Neck', 'Spine', 'Cervical'],
                            'upper back' => ['Spine', 'Thoracic'],
                            'lower back' => ['Spine', 'Lumbar'],
                            'back' => ['Spine'],
                            'upper extremities' => ['Upper Extremities', 'Arm'],
                            'arm' => ['Upper Extremities', 'Arm'],
                            'upper arm' => ['Upper Extremities', 'Arm'],
                            'elbow' => ['Upper Extremities', 'Elbow'],
                            'forearm' => ['Upper Extremities', 'Forearm'],
                            'hand / wrist' => ['Upper Extremities', 'Hand', 'Wrist'],
                            'hand' => ['Upper Extremities', 'Hand'],
                            'wrist' => ['Upper Extremities', 'Wrist'],
                            'shoulder' => ['Upper Extremities', 'Shoulder'],
                            'lower extremities' => ['Lower Extremities', 'Leg'],
                            'pelvis / hip' => ['Pelvis', 'Lower Extremities'],
                            'pelvis' => ['Pelvis', 'Lower Extremities'],
                            'hip' => ['Pelvis', 'Lower Extremities'],
                            'thigh' => ['Lower Extremities', 'Femur'],
                            'knee' => ['Lower Extremities', 'Knee'],
                            'lower leg' => ['Lower Extremities', 'Leg'],
                            'leg' => ['Lower Extremities', 'Leg'],
                            'ankle' => ['Lower Extremities', 'Ankle'],
                            'foot' => ['Lower Extremities', 'Foot']
                        ];

                        $requestedParts = array_filter(array_map('trim', explode(',', $requestedExamStr)));

                        // Helper: get allowed exams for a single body part
                        $getAllowedForPart = function($part) use ($allActiveServices, $bodyPartAliases) {
                            $partLower = strtolower($part);
                            $aliases = $bodyPartAliases[$partLower] ?? [$part];
                            $allowed = [];
                            foreach ($allActiveServices as $as) {
                                $srvNameLower = strtolower($as['exam_type']);
                                $srvCatLower = strtolower($as['category']);
                                if ($srvCatLower === $partLower || $srvNameLower === $partLower) {
                                    $allowed[] = $as['exam_type'];
                                } else {
                                    foreach ($aliases as $alias) {
                                        $aliasLower = strtolower($alias);
                                        if ($srvCatLower === $aliasLower || strpos($srvCatLower, $aliasLower) !== false || strpos($aliasLower, $srvCatLower) !== false) {
                                            $allowed[] = $as['exam_type'];
                                        }
                                    }
                                }
                            }
                            return array_unique($allowed);
                        };

                        // Check each body part: validate all assigned exams belong to it AND at least one exam covers it
                        $missingCoverage = [];
                        $allAllowedExams = [];
                        foreach ($requestedParts as $part) {
                            $allowedForPart = $getAllowedForPart($part);
                            if (!empty($allowedForPart)) {
                                $allAllowedExams = array_merge($allAllowedExams, $allowedForPart);
                                // Check if at least one assigned exam is in this part's allowed list
                                $hasCoverage = false;
                                foreach ($examArray as $assignedItem) {
                                    if (in_array($assignedItem, $allowedForPart)) {
                                        $hasCoverage = true;
                                        break;
                                    }
                                }
                                if (!$hasCoverage) {
                                    $missingCoverage[] = $part;
                                }
                            }
                        }

                        if (!empty($missingCoverage)) {
                            throw new \Exception("Missing exam assignment for: " . implode(', ', $missingCoverage) . ". Please assign at least one procedure per requested body part.");
                        }

                        // Also validate no assigned exam is outside ALL allowed exams
                        $allAllowedExams = array_unique($allAllowedExams);
                        if (!empty($allAllowedExams)) {
                            foreach ($examArray as $assignedItem) {
                                if (!in_array($assignedItem, $allAllowedExams)) {
                                    throw new \Exception("Cannot assign '$assignedItem': It does not match the patient's requested body part ($requestedExamStr).");
                                }
                            }
                        }
                    }

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
                    
                    // Send notification and email to patient
                    $stmtPat = $pdo->prepare("SELECT u.id, u.name, u.email FROM users u WHERE u.patient_id = ? AND u.role = 'patient' LIMIT 1");
                    $stmtPat->execute([$req['patient_id']]);
                    $patUser = $stmtPat->fetch();
                    if ($patUser) {
                        $reqNum = $req['request_number'] ?: ('REQ-' . str_pad($requestId, 5, '0', STR_PAD_LEFT));
                        $formattedDue = number_format($amountDue, 2);

                        $notificationModel->add(
                            "Payment Required",
                            "Your X-ray request ({$reqNum}) has been reviewed. Amount due: ₱{$formattedDue}. Please proceed to payment.",
                            "/" . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') . "/index.php?role=patient&page=dashboard",
                            $patUser['id'],
                            'patient'
                        );

                        if (!empty($patUser['email'])) {
                            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                            $portalUrl = $baseUrl . (defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '') . '/index.php?role=patient&page=dashboard';
                            $patientName = $patUser['name'] ?: 'Patient';
                            $subject = "Payment Required for Your X-ray Request ({$reqNum}) - Citilife System";
                            $emailBody = renderNotificationEmail(
                                $patientName,
                                "X-ray Request Reviewed - Payment Required",
                                "Your X-ray request has been evaluated by our Radiologic Technologist. Please submit your payment to proceed with your examination.",
                                [
                                    'Request Number' => htmlspecialchars($reqNum),
                                    'Assigned Exam'  => htmlspecialchars($examType),
                                    'Amount Due'     => 'PHP ' . $formattedDue,
                                    'Status'         => '<span style="color: #2563eb; font-weight: 600;">Pending Payment</span>'
                                ],
                                "Proceed to Payment",
                                $portalUrl,
                                "You're receiving this notification regarding your X-ray examination request at Citilife.",
                                "#2563eb"
                            );
                            sendEmailAsync($patUser['email'], $patientName, $subject, $emailBody);
                        }
                    }
                    
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
