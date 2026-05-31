<?php
/**
 * PatientModel.php
 * Handles all database interactions related to patients.
 */

class PatientModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Search patients by name or patient number.
     */
    public function searchPatients($query, $limit = 10) {
        if (strlen($query) < 2) return [];

        $searchTerm = '%' . $query . '%';
        $stmt = $this->pdo->prepare("SELECT id, patient_number, first_name, last_name, age, sex, contact_number 
                               FROM patients 
                               WHERE patient_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? 
                               ORDER BY first_name ASC 
                               LIMIT " . (int)$limit);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    /**
     * Get patient by user ID.
     */
    public function getPatientByUserId($userId) {
        $hasPatientId = $this->hasColumn('users', 'patient_id');
        if ($hasPatientId) {
            $stmt = $this->pdo->prepare("SELECT p.* 
                                   FROM patients p 
                                   JOIN users u ON u.patient_id = p.id 
                                   WHERE u.id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        }
        return null;
    }

    /**
     * Get patient by ID.
     */
    public function getPatientById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Register a new patient.
     */
    public function registerPatient($data) {
        $patientNumber = $this->generatePatientNumber($data['branch_id'] ?? null);
        
        // Check if branch_id column exists in patients table
        $hasBranchId = $this->hasColumn('patients', 'branch_id');
        
        if ($hasBranchId) {
            $stmt = $this->pdo->prepare("INSERT INTO patients (patient_number, first_name, last_name, age, sex, contact_number, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $patientNumber, 
                $data['first_name'], 
                $data['last_name'], 
                $data['age'], 
                $data['sex'], 
                $data['contact_number'], 
                $data['branch_id']
            ]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO patients (patient_number, first_name, last_name, age, sex, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $patientNumber, 
                $data['first_name'], 
                $data['last_name'], 
                $data['age'], 
                $data['sex'], 
                $data['contact_number']
            ]);
        }
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Update patient information.
     */
    public function updatePatient($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, age = ?, sex = ?, contact_number = ? WHERE id = ?");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['age'],
            $data['sex'],
            $data['contact_number'],
            $id
        ]);
    }

    /**
     * Generate a unique patient number based on branch and year.
     * Ported from patient_helper.php
     */
    public function generatePatientNumber($branchId) {
        $branchName = 'General';
        if ($branchId) {
            $stmtB = $this->pdo->prepare("SELECT name FROM branches WHERE id = ?");
            $stmtB->execute([$branchId]);
            $branchName = $stmtB->fetchColumn() ?: 'General';
        }

        $code = 'GEN';
        $padLength = 3;
        
        if (stripos($branchName, 'Gapan') !== false) { $code = 'GAP'; }
        elseif (stripos($branchName, 'Bongabon') !== false) { $code = 'BON'; }
        elseif (stripos($branchName, 'Peñaranda') !== false) { $code = 'PEN'; }
        elseif (stripos($branchName, 'General Tinio') !== false || stripos($branchName, 'General Tion') !== false) { $code = 'GTI'; }
        elseif (stripos($branchName, 'San Antonio') !== false) { $code = 'SAN'; }
        elseif (stripos($branchName, 'Sto Domingo') !== false) { $code = 'STD'; }
        elseif (stripos($branchName, 'Pantabangan') !== false) { $code = 'PAN'; $padLength = 4; }

        $year = date('Y');
        $prefix = "PAT-{$code}-{$year}-";

        $stmtLast = $this->pdo->prepare("SELECT patient_number FROM patients WHERE patient_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute([$prefix . '%']);
        $lastPatient = $stmtLast->fetchColumn();

        $seqIndex = 1;
        if ($lastPatient && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastPatient, $m)) {
            $seqIndex = (int)$m[1] + 1;
        }

        return $prefix . str_pad($seqIndex, $padLength, '0', STR_PAD_LEFT);
    }

    public function getPendingPatients() {
        $stmt = $this->pdo->prepare("
            SELECT u.id AS user_id, u.email, u.created_at, p.id AS patient_id, 
                   p.first_name, p.last_name, p.age, p.sex, p.contact_number, b.name AS branch_name
            FROM users u
            INNER JOIN patients p ON u.patient_id = p.id
            LEFT JOIN branches b ON p.branch_id = b.id
            WHERE u.status = 'Pending' AND u.role = 'patient'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRejectedPatients() {
        $stmt = $this->pdo->prepare("
            SELECT u.id AS user_id, u.email, u.created_at, p.id AS patient_id, 
                   p.first_name, p.last_name, p.age, p.sex, p.contact_number, b.name AS branch_name
            FROM users u
            INNER JOIN patients p ON u.patient_id = p.id
            LEFT JOIN branches b ON p.branch_id = b.id
            WHERE u.status = 'Rejected' AND u.role = 'patient'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count pending patient registrations for a specific branch.
     */
    public function countPendingPatientsByBranch($branchId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            INNER JOIN patients p ON u.patient_id = p.id
            WHERE u.status = 'Pending' AND u.role = 'patient' AND p.branch_id = ?
        ");
        $stmt->execute([$branchId]);
        return $stmt->fetchColumn();
    }

    /**
     * Centralized logic for patient registration (both from RadTech and Portal).
     * Handles transactions, case creation, and notifications.
     */
    public function processRegistration($data, $caseModel, $notificationModel) {
        $pdo = $this->pdo;
        $isExisting = ($data['form_mode'] ?? '') === 'existing-patient';
        $userId = $data['user_id'] ?? null;
        $branchId = $data['branch_id'] ?? 1;

        try {
            $pdo->beginTransaction();
            $patientId = null;

            if ($isExisting) {
                $patientId = (int)($data['patient_id'] ?? 0);
                if (!$patientId) throw new Exception("Existing patient ID is required.");
            } else {
                // Register New Patient
                $patientId = $this->registerPatient([
                    'first_name'     => $data['first_name'],
                    'last_name'      => $data['last_name'],
                    'age'            => $data['age'],
                    'sex'            => $data['sex'],
                    'contact_number' => $data['contact_number'],
                    'branch_id'      => $branchId
                ]);
            }

            // Link to User if provided (for Portal registration)
            if ($userId && $patientId) {
                $this->linkToUser($userId, $patientId);
            }

            // Register Initial Case
            $caseData = [
                'patient_id'        => $patientId,
                'branch_id'         => $branchId,
                'exam_type'         => $data['exam_type'] ?? 'To be determined',
                'priority'          => $data['priority'] ?? 'Routine',
                'philhealth_status' => $data['philhealth_status'] ?? 'Without PhilHealth Card',
                'philhealth_id'     => $data['philhealth_id'] ?? null,
                'approval_status'   => $data['approval_status'] ?? 'Pending'
            ];
            
            $caseResult = $caseModel->registerCase($caseData);
            $caseId = $caseResult['id'];
            $caseNumber = $caseResult['case_number'];
            
            // Handle Notifications
            $isPortal = ($data['source'] ?? '') === 'portal';
            if ($isPortal) {
                // For portal-side registration, notify RadTech of the new request
                $notificationModel->add(
                    "New Patient Registration", 
                    "Case {$caseNumber} awaits approval.", 
                    "/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-approval&highlight=" . urlencode($caseNumber), 
                    null, 
                    'radtech', 
                    $branchId
                );
            }

            $pdo->commit();

            return [
                'success' => true,
                'patient_id' => $patientId,
                'case_id' => $caseId,
                'case_number' => $caseNumber,
                'message' => $isExisting ? "Case created successfully." : "Patient registered successfully."
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Link a patient ID to a user account.
     */
    public function linkToUser($userId, $patientId) {
        $stmt = $this->pdo->prepare("UPDATE users SET patient_id = ? WHERE id = ?");
        return $stmt->execute([$patientId, $userId]);
    }

    /**
     * Get all patients with their branch name and latest case date.
     * Used by Central Admin Patient Records.
     */
    public function getAllPatientsWithBranches() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name as branch_name, 
                   (SELECT MAX(created_at) FROM cases WHERE patient_id = p.id) as latest_case_date
            FROM patients p
            LEFT JOIN branches b ON p.branch_id = b.id
            ORDER BY latest_case_date DESC, p.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Helper to check if a column exists in a table.
     */
    private function hasColumn($table, $column) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }
}
