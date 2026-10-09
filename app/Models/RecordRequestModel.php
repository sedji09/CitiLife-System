<?php
/**
 * RecordRequestModel.php
 * Handles all database interactions related to record requests between branches.
 */

class RecordRequestModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    /**
     * Ensure schema has birthdate and rejection_reason columns.
     */
    public function ensureSchema() {
        try {
            $this->pdo->exec("ALTER TABLE record_requests ADD COLUMN birthdate DATE NULL AFTER patient_name");
        } catch (\Throwable $e) {}
        try {
            $this->pdo->exec("ALTER TABLE record_requests ADD COLUMN rejection_reason TEXT NULL AFTER status");
        } catch (\Throwable $e) {}
    }

    /**
     * Create a new record request.
     */
    public function createRequest($data) {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare("
            INSERT INTO record_requests (patient_name, birthdate, patient_no, exam_type, reason, request_branch, branch_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        return $stmt->execute([
            $data['patient_name'],
            !empty($data['birthdate']) ? $data['birthdate'] : null,
            $data['patient_no'],
            $data['exam_type'],
            $data['reason'],
            $data['request_branch'],
            $data['branch_id']
        ]);
    }

    public function getPendingRequestsForBranch($branchName) {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   COALESCE(r.birthdate, p.birthdate) as birthdate,
                   TIMESTAMPDIFF(YEAR, COALESCE(r.birthdate, p.birthdate), CURDATE()) AS age,
                   p.patient_number, 
                   c.created_at as exam_date,
                   b.name as requester_branch_name 
            FROM record_requests r
            LEFT JOIN branches b ON r.branch_id = b.id
            LEFT JOIN cases c ON (r.patient_no = c.case_number)
            LEFT JOIN patients p ON (c.patient_id = p.id OR r.patient_no = p.patient_number)
            WHERE r.status = 'Pending' AND LOWER(r.request_branch) = LOWER(?)
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$branchName]);
        return $stmt->fetchAll();
    }

    /**
     * Count pending record requests for a specific branch.
     */
    public function countPendingRequestsForBranch($branchName) {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM record_requests 
            WHERE status = 'Pending' AND LOWER(request_branch) = LOWER(?)
        ");
        $stmt->execute([$branchName]);
        return $stmt->fetchColumn();
    }

    /**
     * Update request status.
     */
    public function updateRequestStatus($requestId, $status, $branchName = null, $rejectionReason = null) {
        $this->ensureSchema();
        try {
            $sql = "UPDATE record_requests SET status = ?, rejection_reason = ? WHERE id = ?";
            $params = [$status, $rejectionReason, $requestId];
            
            if ($branchName) {
                $sql .= " AND LOWER(request_branch) = LOWER(?)";
                $params[] = $branchName;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            // Fallback if rejection_reason fails
            $sql = "UPDATE record_requests SET status = ? WHERE id = ?";
            $params = [$status, $requestId];
            if ($branchName) {
                $sql .= " AND LOWER(request_branch) = LOWER(?)";
                $params[] = $branchName;
            }
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }
    }

    /**
     * Get request details by ID.
     */
    public function getRequestById($id) {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   COALESCE(r.birthdate, p.birthdate) as birthdate,
                   TIMESTAMPDIFF(YEAR, COALESCE(r.birthdate, p.birthdate), CURDATE()) AS age,
                   p.patient_number,
                   c.created_at as exam_date 
            FROM record_requests r 
            LEFT JOIN cases c ON (r.patient_no = c.case_number) 
            LEFT JOIN patients p ON (c.patient_id = p.id OR r.patient_no = p.patient_number)
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get requests made by a specific branch.
     */
    public function getRequestsByBranch($branchId) {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   COALESCE(r.birthdate, p.birthdate) as birthdate,
                   TIMESTAMPDIFF(YEAR, COALESCE(r.birthdate, p.birthdate), CURDATE()) AS age,
                   p.patient_number,
                   b.id as branch_id, 
                   COALESCE(c.created_at, r.created_at) as exam_date
            FROM record_requests r
            JOIN branches b ON r.request_branch = b.name
            LEFT JOIN cases c ON (r.patient_no = c.case_number)
            LEFT JOIN patients p ON (c.patient_id = p.id OR r.patient_no = p.patient_number)
            WHERE r.branch_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    /**
     * Centralized logic for approving or denying a record request.
     * Handles DB update and notification dispatch.
     */
    public function processRequestAction($requestId, $action, $myBranchName, $notificationModel, $rejectionReason = null) {
        if (!$requestId || !in_array($action, ['Approve', 'Deny'])) {
            throw new Exception("Invalid action or request ID.");
        }

        if ($action === 'Deny' && empty(trim($rejectionReason ?? ''))) {
            throw new Exception("Please provide a reason for denying this record request.");
        }

        $newStatus = ($action === 'Approve') ? 'Approved' : 'Denied';
        $requestData = $this->getRequestById($requestId);

        if ($requestData && $this->updateRequestStatus($requestId, $newStatus, $myBranchName, ($action === 'Deny' ? trim($rejectionReason) : null))) {
            // Notifications Logic
            if (!empty($requestData['branch_id'])) {
                $notifTitle = "Record Request " . $newStatus;
                $patientName = $requestData['patient_name'] ?? 'N/A';
                if ($action === 'Deny') {
                    $cleanReason = trim($rejectionReason);
                    $notifMsg   = "Your record request for patient {$patientName} was denied by {$myBranchName}. Reason: \"{$cleanReason}\".";
                } else {
                    $notifMsg   = "Your request for patient {$patientName} has been approved by {$myBranchName}.";
                }
                $projectBase = defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '';
                $notificationModel->add(
                    $notifTitle, 
                    $notifMsg, 
                    url("view-record-request?id=" . urlencode($requestId)), 
                    null, 
                    'radtech', 
                    $requestData['branch_id']
                );
            }

            return [
                'success' => true,
                'message' => "Record request " . strtolower($newStatus) . " successfully."
            ];
        }

        return [
            'success' => false,
            'message' => "Failed to update record request."
        ];
    }

    /**
     * Unified logic for a RadTech submitting a record request.
     * Handles creation and notifies the target branch admin.
     */
    public function processRequestSubmission($data, $branchModel, $notificationModel) {
        if ($this->createRequest($data)) {
            $targetBranch = $branchModel->getBranchByName($data['request_branch']);
            if ($targetBranch) {
                $myBranch = $branchModel->getBranchById($data['branch_id']);
                $myBranchName = $myBranch['name'] ?? "Another branch";
                $notifMsg = "A RadTech from " . $myBranchName . " has requested records for patient " . $data['patient_name'] . ".";
                
                $notificationModel->add(
                    "New Record Request", 
                    $notifMsg, 
                    url("record-requests?highlight=" . urlencode($data['patient_no'] ?? $data['patient_name'])), 
                    null, 
                    'branch_admin', 
                    $targetBranch['id']
                );
            }
            return true;
        }
        return false;
    }
}
