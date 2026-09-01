<?php
// Use the global $pdo already created by index.php bootstrap
global $pdo;
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
}
require_once __DIR__ . '/../app/Models/PatientModel.php';
require_once __DIR__ . '/../app/Models/CaseModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Determine base path for redirects (empty on Railway/production, /ProjectDir on localhost)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
$redirectBase = $isLocal ? ('/' . (defined('PROJECT_DIR') ? PROJECT_DIR : 'Citilife-System')) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientModel = new PatientModel($pdo);
    $caseModel = new CaseModel($pdo);

    $caseId       = isset($_POST['id'])           ? (int)$_POST['id']              : 0;
    $name         = isset($_POST['name'])          ? trim($_POST['name'])           : '';
    $birthdate    = isset($_POST['birthdate'])     ? trim($_POST['birthdate'])      : '';
    $sex          = isset($_POST['sex'])           ? $_POST['sex']                  : '';
    $contact      = isset($_POST['contact'])       ? trim($_POST['contact'])        : '';
    $homeAddress  = isset($_POST['home_address'])  ? trim($_POST['home_address'])   : '';
    $philhealth   = isset($_POST['philhealth'])    ? $_POST['philhealth']           : '';
    $philhealthId = isset($_POST['philhealth_id']) ? trim($_POST['philhealth_id']) : '';
    $philhealthRelation = isset($_POST['philhealth_relation']) ? trim($_POST['philhealth_relation']) : null;

    // Extract first and last name
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName  = $nameParts[1] ?? '';

    if ($caseId && $firstName && $birthdate && $sex && $contact) {
        try {
            $branchId = $_SESSION['branch_id'] ?? 1;
            $stmtReq = $pdo->prepare("SELECT branch_id, patient_id FROM requests WHERE id = ?");
            $stmtReq->execute([$caseId]);
            $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if ($request && $request['branch_id'] == $branchId) {
                // 1. Update patient info
                $patientData = [
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'birthdate'      => $birthdate,
                    'sex'            => $sex,
                    'contact_number' => $contact,
                    'home_address'   => $homeAddress
                ];
                $patientModel->updatePatient($request['patient_id'], $patientData);

                // 2. Update PhilHealth info & recalculate price if exam already assigned
                $hasPhilHealth     = ($philhealth === 'With PhilHealth Card');
                $philhealthIdToSave = $hasPhilHealth ? $philhealthId : null;
                $philhealthRelationToSave = $hasPhilHealth ? $philhealthRelation : null;

                // Backend Validation for PhilHealth ID
                if ($hasPhilHealth && $philhealthIdToSave && $philhealthRelationToSave) {
                    $sqlOwnerReq = "SELECT 1 FROM requests WHERE philhealth_id = :id AND philhealth_relation = 'Principal Member' AND status != 'Cancelled' AND status != 'Rejected' AND id != :req_id";
                    $sqlOwnerCase = "SELECT 1 FROM cases WHERE philhealth_id = :id AND philhealth_relation = 'Principal Member' AND status != 'Rejected' AND (request_id IS NULL OR request_id != :req_id)";
                    $stmtOwner = $pdo->prepare("$sqlOwnerReq UNION $sqlOwnerCase");
                    $stmtOwner->execute([':id' => $philhealthIdToSave, ':req_id' => $caseId]);
                    $ownerUsed = (bool) $stmtOwner->fetchColumn();

                    $sqlFamilyReq = "SELECT 1 FROM requests WHERE philhealth_id = :id AND philhealth_relation = 'Qualified Dependent' AND status != 'Cancelled' AND status != 'Rejected' AND id != :req_id";
                    $sqlFamilyCase = "SELECT 1 FROM cases WHERE philhealth_id = :id AND philhealth_relation = 'Qualified Dependent' AND status != 'Rejected' AND (request_id IS NULL OR request_id != :req_id)";
                    $stmtFamily = $pdo->prepare("$sqlFamilyReq UNION $sqlFamilyCase");
                    $stmtFamily->execute([':id' => $philhealthIdToSave, ':req_id' => $caseId]);
                    $familyUsed = (bool) $stmtFamily->fetchColumn();

                    if ($philhealthRelationToSave === 'Principal Member' && $ownerUsed) {
                        throw new Exception("This PhilHealth ID is already used for the Principal Member.");
                    }
                    if ($philhealthRelationToSave === 'Qualified Dependent' && $familyUsed) {
                        throw new Exception("This PhilHealth ID is already used for a Qualified Dependent.");
                    }
                }

                $stmtExam = $pdo->prepare("SELECT exam_type, status FROM requests WHERE id = ?");
                $stmtExam->execute([$caseId]);
                $reqData = $stmtExam->fetch(PDO::FETCH_ASSOC);

                if ($reqData && !empty($reqData['exam_type']) && in_array($reqData['status'], ['Pending Payment', 'Pending Approval', 'Pending'])) {
                    $examArray         = array_filter(array_map('trim', explode(',', $reqData['exam_type'])));
                    $originalPrice     = 0.00;
                    $philhealthDiscount = 0.00;

                    if (!empty($examArray)) {
                        $placeholders  = implode(',', array_fill(0, count($examArray), '?'));
                        $stmtServices  = $pdo->prepare(
                            "SELECT exam_type, price, is_philhealth_covered, philhealth_discount
                             FROM xray_services
                             WHERE exam_type IN ($placeholders) AND status = 'active'"
                        );
                        $stmtServices->execute($examArray);
                        $services = $stmtServices->fetchAll();

                        foreach ($services as $srv) {
                            $price          = (float)$srv['price'];
                            $originalPrice += $price;

                            if ($hasPhilHealth && (int)$srv['is_philhealth_covered'] === 1) {
                                $discount           = (float)$srv['philhealth_discount'];
                                $philhealthDiscount += min($discount, $price);
                            }
                        }
                    }

                    $amountDue = max(0.00, $originalPrice - $philhealthDiscount);
                    $stmtUp    = $pdo->prepare(
                        "UPDATE requests
                         SET philhealth_status = ?, philhealth_id = ?, philhealth_relation = ?,
                             original_price = ?, philhealth_discount = ?, amount_due = ?, is_verified = 1
                         WHERE id = ?"
                    );
                    $stmtUp->execute([$philhealth, $philhealthIdToSave, $philhealthRelationToSave, $originalPrice, $philhealthDiscount, $amountDue, $caseId]);

                } else {
                    $stmtUp = $pdo->prepare("UPDATE requests SET philhealth_status = ?, philhealth_id = ?, philhealth_relation = ?, is_verified = 1 WHERE id = ?");
                    $stmtUp->execute([$philhealth, $philhealthIdToSave, $philhealthRelationToSave, $caseId]);
                }

                header('Location: ' . $redirectBase . '/patient-approval?success=1');
                exit;
            }
        } catch (Exception $e) {
            error_log('update_patient error: ' . $e->getMessage());
            header('Location: ' . $redirectBase . '/patient-approval?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: ' . $redirectBase . '/patient-approval');
exit;
