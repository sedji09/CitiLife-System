<?php
/**
 * CaseModel.php
 * Handles all database interactions related to cases.
 * Separation of concerns: This is the 'Backend' logic.
 */

class CaseModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Build date condition string for SQL based on filter type.
     */
    public function buildDateCondition($filter, $selectedMonth = null, $selectedYear = null)
    {
        $dateCondition = "DATE(created_at) = CURDATE()";
        $periodLabel = "Today";

        if ($filter === 'weekly') {
            $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $periodLabel = "This Week";
        } elseif ($filter === 'monthly') {
            $parts = explode('-', $selectedMonth);
            $y = (int) ($parts[0] ?? date('Y'));
            $m = (int) ($parts[1] ?? date('m'));
            $dateCondition = "YEAR(created_at) = $y AND MONTH(created_at) = $m";
            $dateObj = DateTime::createFromFormat('!m', $m);
            $monthName = $dateObj ? $dateObj->format('F') : '';
            $periodLabel = trim("$monthName $y");
        } elseif ($filter === 'yearly') {
            $y = (int) $selectedYear;
            $dateCondition = "YEAR(created_at) = $y";
            $periodLabel = "Year $y";
        }

        return ['condition' => $dateCondition, 'label' => $periodLabel];
    }

    /**
     * Get dashboard stats (total, pending, priority, emergency, completed)
     */
    public function getDashboardStats($branchId, $dateCondition)
    {
        // Total
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE $dateCondition AND branch_id = ?");
        $stmt->execute([$branchId]);
        $total = $stmt->fetchColumn();

        // Pending
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE status = 'Pending' AND $dateCondition AND branch_id = ?");
        $stmt->execute([$branchId]);
        $pending = $stmt->fetchColumn();

        // Priority
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE priority IN ('Priority', 'Urgent') AND $dateCondition AND branch_id = ?");
        $stmt->execute([$branchId]);
        $priority = $stmt->fetchColumn();

        // Emergency
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE priority = 'Emergency' AND $dateCondition AND branch_id = ?");
        $stmt->execute([$branchId]);
        $emergency = $stmt->fetchColumn();

        // Completed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE status = 'Completed' AND $dateCondition AND branch_id = ?");
        $stmt->execute([$branchId]);
        $completed = $stmt->fetchColumn();

        return [
            'total' => $total,
            'pending' => $pending,
            'priority' => $priority,
            'emergency' => $emergency,
            'completed' => $completed
        ];
    }

    /**
     * Get the name of the technologist who handled the case.
     */
    public function getRadTechName($radtechId)
    {
        if (!$radtechId)
            return null;
        $stmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$radtechId]);
        $rt = $stmt->fetch();
        if ($rt) {
            return $rt['name'] ?: ucwords(str_replace('.', ' ', explode('@', $rt['email'])[0]));
        }
        return null;
    }

    /**
     * Get statistics for the Radiologist Dashboard.
     */
    public function getRadiologistStats($dateCondition)
    {
        // Total Pending (All Branches)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE status IN ('Pending', 'Under Reading') AND image_status = 'Uploaded' AND $dateCondition");
        $stmt->execute();
        $totalPending = $stmt->fetchColumn() ?: 0;

        // Emergency Cases (All Branches)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE status IN ('Pending', 'Under Reading') AND image_status = 'Uploaded' AND priority = 'Emergency' AND $dateCondition");
        $stmt->execute();
        $emergencyCases = $stmt->fetchColumn() ?: 0;

        return [
            'totalPending' => $totalPending,
            'emergencyCases' => $emergencyCases
        ];
    }

    /**
     * Get branch priority breakdown for charts.
     */
    public function getBranchPriorityStats($dateCondition)
    {
        $stmt = $this->pdo->prepare("SELECT branch_id, priority, COUNT(*) as count 
                               FROM cases 
                               WHERE status IN ('Pending', 'Under Reading') AND image_status = 'Uploaded' AND $dateCondition 
                               GROUP BY branch_id, priority");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Save radiologist findings and update status.
     */
    public function saveFinding($caseId, $radiologistId, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cases
            SET clinical_information = ?,
                findings             = ?,
                impression           = ?,
                recommendation       = '',
                radiologist_id       = ?,
                date_completed       = NOW(),
                status               = 'Report Ready'
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['clinical_information'],
            $data['findings'],
            $data['impression'],
            $radiologistId,
            $caseId
        ]);
    }

    /**
     * Unified logic for a radiologist submitting a report.
     * Handles data processing, DB updates, and notifies the RadTech and Patient.
     */
    public function submitRadiologistReport($caseId, $radiologistId, $data, $notificationModel)
    {
        $examReportsArr = $data['exam_reports_arr'] ?? [];
        $clinicalInfo = $data['clinical_information'] ?? '';

        // 1. Process findings/impression for storage (Logic moved from View)
        $allFindings = [];
        $allImpressions = [];
        foreach ($examReportsArr as $examKey => $eData) {
            if (!empty($eData['findings']))
                $allFindings[] = $eData['findings'];
            if (!empty($eData['impression']))
                $allImpressions[] = $eData['impression'];
        }

        $flatFindings = implode("\n\n", $allFindings);
        $flatImpression = implode("\n\n", $allImpressions);

        // If multiple exams, we store raw JSON in findings for the report generator
        $findingsStore = count($examReportsArr) > 1 ? json_encode($examReportsArr) : ($flatFindings ?: '');
        $impressionStore = count($examReportsArr) > 1 ? '' : ($flatImpression ?: '');

        $saveData = [
            'clinical_information' => $clinicalInfo,
            'findings' => $findingsStore,
            'impression' => $impressionStore
        ];

        if ($this->saveFinding($caseId, $radiologistId, $saveData)) {
            $cData = $this->getCaseById($caseId);
            if ($cData && !empty($cData['branch_id'])) {
                // Determine branch name/code for the message
                $branchLabel = str_replace(' Branch', '', $cData['branch_name']);

                // Notify RadTech
                $notificationModel->add(
                    "Report Ready",
                    "Radiology report ready for Case {$cData['case_number']} ({$branchLabel}). Awaiting release.",
                    "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-details&id={$caseId}",
                    null,
                    'radtech',
                    $cData['branch_id']
                );

                // Notify Patient (if linked)
                $patientUserId = $this->getPatientUserId($caseId);
                if ($patientUserId) {
                    $notificationModel->add(
                        "Reading Completed",
                        "Your X-ray for Case {$cData['case_number']} has been read. It will be released shortly.",
                        "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&case_id={$caseId}",
                        $patientUserId,
                        'patient'
                    );
                }
            }
            return [
                'success' => true,
                'message' => "Report submitted successfully."
            ];
        }
        return [
            'success' => false,
            'message' => "Failed to submit report."
        ];
    }

    /**
     * Update case status.
     */
    public function updateStatus($caseId, $status)
    {
        $stmt = $this->pdo->prepare("UPDATE cases SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $caseId]);
    }

    /**
     * Get latest 5 cases for the dashboard table
     */
    public function getRecentCases($branchId, $dateCondition, $limit = 5)
    {
        // We need to adjust 'created_at' in the condition if we're using joins with aliases
        $recentDateCondition = str_replace('created_at', 'c.created_at', $dateCondition);

        $sql = "SELECT c.*, p.first_name, p.last_name, p.patient_number 
                FROM cases c 
                JOIN patients p ON c.patient_id = p.id 
                WHERE c.branch_id = ? AND $recentDateCondition 
                ORDER BY c.created_at DESC 
                LIMIT " . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    /**
     * Get case by ID with patient and branch details.
     */
    public function getCaseById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.first_name, p.last_name, p.age, p.sex, p.contact_number, p.patient_number,
                   b.name AS branch_name,
                   COALESCE(NULLIF(u.full_name_report, ''), NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) AS radtech_name, u.professional_title AS radtech_title, u.signature AS radtech_signature,
                   COALESCE(NULLIF(ur.full_name_report, ''), NULLIF(ur.name, ''), SUBSTRING_INDEX(ur.email, '@', 1)) AS radiologist_name, ur.professional_title AS radiologist_title, ur.signature AS radiologist_signature
            FROM cases c
            JOIN patients p ON c.patient_id = p.id
            JOIN branches b ON c.branch_id = b.id
            LEFT JOIN users u ON c.radtech_id = u.id
            LEFT JOIN users ur ON c.radiologist_id = ur.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Register a new case.
     */
    public function registerCase($data)
    {
        $caseNumber = $this->generateCaseNumber($data['branch_id'] ?? null);
        $hasApprovalStatus = $this->hasColumn('cases', 'approval_status');
        $approvalStatus = $data['approval_status'] ?? 'Pending';

        if ($hasApprovalStatus) {
            $stmt = $this->pdo->prepare("INSERT INTO cases (case_number, patient_id, branch_id, exam_type, priority, philhealth_status, philhealth_id, status, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([
                $caseNumber,
                $data['patient_id'],
                $data['branch_id'],
                $data['exam_type'] ?? 'To be determined',
                $data['priority'] ?? 'Routine',
                $data['philhealth_status'] ?? 'Without PhilHealth Card',
                $data['philhealth_id'] ?? null,
                $approvalStatus
            ]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO cases (case_number, patient_id, branch_id, exam_type, priority, philhealth_status, philhealth_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([
                $caseNumber,
                $data['patient_id'],
                $data['branch_id'],
                $data['exam_type'] ?? 'To be determined',
                $data['priority'] ?? 'Routine',
                $data['philhealth_status'] ?? 'Without PhilHealth Card',
                $data['philhealth_id'] ?? null
            ]);
        }

        $newCaseId = $this->pdo->lastInsertId();
        return ['id' => $newCaseId, 'case_number' => $caseNumber];
    }

    /**
     * Get the latest case for a patient.
     */
    public function getLatestCaseByPatient($patientId)
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, b.name AS branch_name
            FROM cases c
            LEFT JOIN branches b ON c.branch_id = b.id
            WHERE c.patient_id = ?
            ORDER BY c.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetch();
    }

    /**
     * Get patient's case history across all branches.
     */
    public function getPatientHistory($patientNumber, $excludeCaseId = null)
    {
        $sql = "SELECT c.*, b.name as branch_name
                FROM cases c
                JOIN branches b ON c.branch_id = b.id
                JOIN patients p ON c.patient_id = p.id
                WHERE p.patient_number = ?";
        $params = [$patientNumber];

        if ($excludeCaseId) {
            $sql .= " AND c.id != ?";
            $params[] = $excludeCaseId;
        }

        $sql .= " ORDER BY c.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get worklist for radiologists.
     */
    public function getWorklist($branchId = null, $priority = null, $status = null, $imageUploadedOnly = false)
    {
        $sql = "SELECT c.*, p.first_name, p.last_name, p.patient_number, b.name as branch_name 
                FROM cases c 
                JOIN patients p ON c.patient_id = p.id 
                JOIN branches b ON c.branch_id = b.id 
                WHERE 1=1";
        $params = [];

        if ($branchId) {
            $sql .= " AND c.branch_id = ?";
            $params[] = $branchId;
        }
        if ($priority) {
            $sql .= " AND c.priority = ?";
            $params[] = $priority;
        }
        if ($status) {
            if (is_array($status)) {
                $placeholders = implode(',', array_fill(0, count($status), '?'));
                $sql .= " AND c.status IN ($placeholders)";
                foreach ($status as $s)
                    $params[] = $s;
            } else {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
        }
        if ($imageUploadedOnly) {
            $sql .= " AND c.image_status = 'Uploaded'";
        }

        $sql .= " ORDER BY CASE WHEN c.priority = 'Emergency' THEN 1 ELSE 2 END, c.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Generate a unique case number.
     */
    public function generateCaseNumber($branchId = null)
    {
        $branchCode = 'GEN';
        if ($branchId) {
            $stmtB = $this->pdo->prepare("SELECT name FROM branches WHERE id = ?");
            $stmtB->execute([$branchId]);
            $branchName = $stmtB->fetchColumn() ?: 'General';

            $cleanName = str_replace(' Branch', '', $branchName);
            $branchCode = strtoupper(substr($cleanName, 0, 3));

            if (strpos($branchName, 'San Antonio') !== false)
                $branchCode = 'SAN';
            if (strpos($branchName, 'Sto Domingo') !== false)
                $branchCode = 'STO';
            if (strpos($branchName, 'General Tinio') !== false)
                $branchCode = 'GEN';
        }

        $year = date('Y');
        $prefix = "CX-{$year}-"; // System standard uses CX-YYYY-XXXX

        $stmtLast = $this->pdo->prepare("SELECT case_number FROM cases WHERE case_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute(["CX-{$year}-%"]);
        $lastCase = $stmtLast->fetchColumn();

        $caseIndex = 1;
        if ($lastCase && preg_match('/CX-' . $year . '-(\d+)/', $lastCase, $m)) {
            $caseIndex = (int) $m[1] + 1;
        }

        return 'CX-' . $year . '-' . str_pad($caseIndex, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Helper to check if a column exists in a table.
     */
    private function hasColumn($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Release result and move to completed.
     */
    public function releaseResult($caseId)
    {
        $stmt = $this->pdo->prepare("UPDATE cases SET status = 'Completed', released = 1 WHERE id = ?");
        return $stmt->execute([$caseId]);
    }

    /**
     * Get patient linked to a user ID.
     */
    public function getLinkedPatient($userId)
    {
        if (!$userId)
            return null;
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name as branch_name 
            FROM patients p 
            INNER JOIN users u ON u.patient_id = p.id 
            LEFT JOIN branches b ON p.branch_id = b.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Get patient by user ID (legacy simple version).
     */
    public function getPatientByUserId($userId)
    {
        $stmt = $this->pdo->prepare("SELECT p.* FROM patients p JOIN users u ON u.patient_id = p.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Get associated User ID for a case (for notifications).
     */
    public function getPatientUserId($caseId)
    {
        $stmt = $this->pdo->prepare("SELECT u.id FROM users u JOIN cases c ON c.patient_id = u.patient_id WHERE c.id = ? LIMIT 1");
        $stmt->execute([$caseId]);
        return $stmt->fetchColumn();
    }

    /**
     * Get released or completed records for a branch.
     */
    public function getReleasedRecords($branchId)
    {
        $hasReleased = $this->hasColumn('cases', 'released');

        if ($hasReleased) {
            $sql = "SELECT c.*, p.first_name, p.last_name, p.patient_number 
                    FROM cases c 
                    JOIN patients p ON c.patient_id = p.id 
                    WHERE c.released = 1 AND c.branch_id = ?
                    ORDER BY c.created_at DESC";
        } else {
            $sql = "SELECT c.*, p.first_name, p.last_name, p.patient_number 
                    FROM cases c 
                    JOIN patients p ON c.patient_id = p.id 
                    WHERE c.status = 'Completed' AND c.branch_id = ?
                    ORDER BY c.created_at DESC";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    /**
     * Get case details by case number (CX-YYYY-XXXX).
     */
    public function getCaseByNumber($caseNumber)
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.first_name, p.last_name, p.age, p.sex, p.contact_number, p.patient_number as p_num
            FROM cases c
            JOIN patients p ON c.patient_id = p.id
            WHERE c.case_number = ?
        ");
        $stmt->execute([$caseNumber]);
        return $stmt->fetch();
    }

    /**
     * Approve a pending case.
     */
    public function approveCase($id)
    {
        $stmt = $this->pdo->prepare("UPDATE cases SET approval_status = 'Approved', status = 'Pending' WHERE id = ? AND approval_status = 'Pending'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Search for cases in a specific branch by case number or name.
     */
    public function searchCasesInBranch($branchId, $caseNumber, $patientName = '', $examType = '')
    {
        // Search by case number first
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.first_name, p.last_name, p.patient_number 
            FROM cases c 
            JOIN patients p ON c.patient_id = p.id 
            WHERE c.branch_id = ? AND c.case_number = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$branchId, $caseNumber]);
        $cases = $stmt->fetchAll();

        // Fallback or additional search by name if name and exam type provided
        if (empty($cases) && !empty($patientName)) {
            $likeName = '%' . $patientName . '%';
            $stmt2 = $this->pdo->prepare("
                SELECT c.*, p.first_name, p.last_name, p.patient_number 
                FROM cases c 
                JOIN patients p ON c.patient_id = p.id 
                WHERE c.branch_id = ? 
                  AND CONCAT(p.first_name, ' ', p.last_name) LIKE ?
                  AND c.exam_type LIKE ?
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            $stmt2->execute([$branchId, $likeName, '%' . $examType . '%']);
            $cases = $stmt2->fetchAll();
        }

        return $cases;
    }

    /**
     * Reject a pending case.
     */
    public function rejectCase($id)
    {
        $stmt = $this->pdo->prepare("UPDATE cases SET approval_status = 'Rejected', status = 'Rejected' WHERE id = ? AND approval_status = 'Pending'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update Case PhilHealth info.
     */
    public function updateCasePhilHealth($id, $status, $philhealthId)
    {
        $philhealthIdToSave = ($status === 'With PhilHealth Card') ? $philhealthId : null;
        $stmt = $this->pdo->prepare("UPDATE cases SET philhealth_status = ?, philhealth_id = ? WHERE id = ?");
        return $stmt->execute([$status, $philhealthIdToSave, $id]);
    }

    /**
     * Get pending cases for approval.
     */
    public function getPendingCases($branchId)
    {
        $hasApprovalStatus = $this->hasColumn('cases', 'approval_status');
        if ($hasApprovalStatus) {
            $sql = "SELECT c.*, p.first_name, p.last_name, p.age, p.sex, p.contact_number 
                    FROM cases c 
                    JOIN patients p ON c.patient_id = p.id 
                    WHERE c.approval_status = 'Pending' AND c.branch_id = ?
                    ORDER BY c.created_at DESC";
        } else {
            $sql = "SELECT c.*, p.first_name, p.last_name, p.age, p.sex, p.contact_number 
                    FROM cases c 
                    JOIN patients p ON c.patient_id = p.id 
                    WHERE c.status = 'Pending' AND c.branch_id = ?
                    ORDER BY c.created_at DESC";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    /**
     * Submit a case to a radiologist with images and template.
     */
    public function submitToRadiologist($caseId, $data)
    {
        $sql = "UPDATE cases SET 
                exam_type = ?, 
                priority = ?, 
                report_template = ?, 
                status = 'Pending', 
                image_status = 'Uploaded',
                radtech_id = ?";
        $params = [
            $data['exam_type'],
            $data['priority'],
            $data['report_template'],
            $data['radtech_id'] ?? null
        ];

        if (isset($data['image_path'])) {
            $sql .= ", image_path = ?";
            $params[] = $data['image_path'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $caseId;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Complete logic for RadTech submitting images to a radiologist.
     * Handles file uploads, DB updates, and notifications.
     */
    public function processRadTechSubmission($caseId, $data, $notificationModel)
    {
        // Validation
        if (empty($data['exam_type']))
            return ['success' => false, 'message' => "Please select at least one Exam Type."];
        if (empty($data['report_template']))
            return ['success' => false, 'message' => "Please select a Report Template before submitting."];

        $files = $data['files'];
        $hasFiles = isset($files) && is_array($files['name']) && !empty(array_filter($files['name']));
        if (!$hasFiles)
            return ['success' => false, 'message' => "Please upload at least one diagnostic image before submitting."];

        // File Processing
        $uploadedPaths = [];
        $uploadDir = __DIR__ . '/../../public/assets/uploads/cases/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK)
                continue;
            $fileName = $files['name'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate allowed extensions
            $allowedExts = ['jpg', 'jpeg', 'png', 'dcm', 'dicom'];
            if (!in_array($fileExt, $allowedExts)) {
                return ['success' => false, 'message' => "Invalid file format for \"$fileName\". Only JPG, PNG, and DICOM formats are allowed."];
            }

            $newFileName = 'case_' . $caseId . '_' . time() . '_' . $i . '.' . $fileExt;

            if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $newFileName)) {
                $uploadedPaths[] = 'public/assets/uploads/cases/' . $newFileName;
            } else {
                return ['success' => false, 'message' => "Error saving \"$fileName\"."];
            }
        }

        // Database Update
        $submitData = [
            'exam_type' => $data['exam_type'],
            'priority' => $data['priority'],
            'report_template' => $data['report_template'],
            'image_path' => json_encode($uploadedPaths),
            'radtech_id' => $data['radtech_id'] ?? null
        ];

        if ($this->submitToRadiologist($caseId, $submitData)) {
            // Notifications
            $cData = $this->getCaseById($caseId);
            if ($cData && !empty($cData['branch_id'])) {
                $notificationModel->add(
                    "New X-ray Uploaded",
                    "X-ray image uploaded for Case {$cData['case_number']} and is ready for reading.",
                    "/" . PROJECT_DIR . "/index.php?role=radiologist&page=patient-queue&branch_id={$cData['branch_id']}&highlight=" . urlencode($cData['case_number']),
                    null,
                    'radiologist'
                );
            }
            return ['success' => true, 'message' => "Image processing and case submission successful!"];
        }

        return ['success' => false, 'message' => "Failed to update case database."];
    }

    /**
     * Unified logic for a technologist approving or rejecting a case.
     * Handles status changes and patient notifications.
     */
    public function processCaseApproval($id, $action, $notificationModel)
    {
        if ($action === 'approve') {
            if ($this->approveCase($id)) {
                $caseData = $this->getCaseById($id);
                $patientUserId = $this->getPatientUserId($id);

                if ($patientUserId) {
                    $notificationModel->add(
                        "Request Approved",
                        "Your X-ray request ({$caseData['case_number']}) has been approved. Please proceed to the X-ray room.",
                        "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status&case_id={$id}",
                        $patientUserId,
                        'patient'
                    );
                }
                return ['success' => true, 'message' => "Patient approved and moved to Today's Queue."];
            }
        } elseif ($action === 'reject') {
            $caseData = $this->getCaseById($id);
            if ($this->rejectCase($id)) {
                $patientUserId = $this->getPatientUserId($id);
                if ($patientUserId && $caseData) {
                    $notificationModel->add(
                        "Request Rejected",
                        "Your X-ray request ({$caseData['case_number']}) has been rejected. Please contact the clinic for more info.",
                        "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status",
                        $patientUserId,
                        'patient'
                    );
                }
                return ['success' => true, 'message' => "Patient registration rejected and notified."];
            }
        }
        return ['success' => false, 'message' => "Action failed."];
    }

    /**
     * Get aggregated statistics for reports based on date range and branch selection.
     */
    public function getReportStats($startDate, $endDate, $branchIds = [])
    {
        $sql = "SELECT b.id as branch_id,
                       b.name as branch_name,
                       COUNT(c.id) as total_patients,
                       SUM(CASE WHEN c.philhealth_status = 'With PhilHealth Card' THEN 1 ELSE 0 END) as with_philhealth,
                       SUM(CASE WHEN c.philhealth_status = 'Without PhilHealth Card' THEN 1 ELSE 0 END) as without_philhealth,
                       SUM(CASE WHEN c.priority = 'Emergency' THEN 1 ELSE 0 END) as emergency_count,
                       SUM(CASE WHEN c.priority IN ('Urgent', 'Priority') THEN 1 ELSE 0 END) as urgent_count,
                       SUM(CASE WHEN c.priority IN ('Routine', 'Normal') THEN 1 ELSE 0 END) as routine_count
                FROM branches b
                LEFT JOIN cases c ON c.branch_id = b.id AND DATE(c.created_at) BETWEEN ? AND ?
                WHERE 1=1";

        $params = [$startDate, $endDate];

        if (!empty($branchIds)) {
            $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
            $sql .= " AND b.id IN ($placeholders)";
            foreach ($branchIds as $id)
                $params[] = $id;
        }

        $sql .= " GROUP BY b.id, b.name ORDER BY b.name ASC, b.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get monthly patient counts for a branch in a given year.
     */
    public function getBranchMonthlyStats($branchId, $year)
    {
        $sql = "SELECT MONTH(created_at) as month_num, COUNT(*) as count 
                FROM cases 
                WHERE branch_id = ? AND YEAR(created_at) = ? 
                GROUP BY MONTH(created_at) 
                ORDER BY month_num ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId, $year]);
        return $stmt->fetchAll();
    }

    /**
     * Get stats breakdown for a specific branch and date range.
     * More targeted than getReportStats which is branch-centric.
     */
    public function getBranchBreakdown($branchId, $startDate, $endDate)
    {
        $sql = "SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN philhealth_status = 'With PhilHealth Card' THEN 1 ELSE 0 END) as with_philhealth,
                    SUM(CASE WHEN philhealth_status = 'Without PhilHealth Card' THEN 1 ELSE 0 END) as without_philhealth,
                    SUM(CASE WHEN priority = 'Emergency' THEN 1 ELSE 0 END) as emergency_count,
                    SUM(CASE WHEN priority IN ('Urgent', 'Priority') THEN 1 ELSE 0 END) as urgent_count,
                    SUM(CASE WHEN priority IN ('Routine', 'Normal') THEN 1 ELSE 0 END) as routine_count
                FROM cases
                WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId, $startDate, $endDate]);
        return $stmt->fetch();
    }

    /**
     * Ensure database schema is up to date (Backend migration logic).
     */
    public function ensureSchema()
    {
        // Ensure approval_status exists
        if (!$this->hasColumn('cases', 'approval_status')) {
            $this->pdo->exec("ALTER TABLE cases ADD COLUMN approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");
        }
        // Ensure released exists
        if (!$this->hasColumn('cases', 'released')) {
            $this->pdo->exec("ALTER TABLE cases ADD COLUMN released TINYINT(1) NOT NULL DEFAULT 0");
        }
        // Ensure report_template exists
        if (!$this->hasColumn('cases', 'report_template')) {
            $this->pdo->exec("ALTER TABLE cases ADD COLUMN report_template VARCHAR(100) DEFAULT NULL");
        }
        // Ensure status enum includes 'Report Ready'
        $stmt = $this->pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cases' AND COLUMN_NAME = 'status'");
        $stmt->execute();
        $enumRow = $stmt->fetchColumn();
        if ($enumRow && strpos($enumRow, 'Report Ready') === false) {
            $this->pdo->exec("ALTER TABLE cases MODIFY COLUMN status ENUM('Pending','Under Reading','Report Ready','Completed') NOT NULL DEFAULT 'Pending'");
        }
        // Ensure image_path is TEXT (not VARCHAR) so many-image JSON is never truncated
        $stmt2 = $this->pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cases' AND COLUMN_NAME = 'image_path'");
        $stmt2->execute();
        $colType = strtolower($stmt2->fetchColumn() ?: '');
        if ($colType && strpos($colType, 'text') === false) {
            $this->pdo->exec("ALTER TABLE cases MODIFY COLUMN image_path TEXT DEFAULT NULL");
        }
    }
}
