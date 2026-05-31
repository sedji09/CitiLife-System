<?php
/**
 * RecordRequestModel.php
 * Handles all database interactions related to record requests between branches.
 */

class RecordRequestModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new record request.
     */
    public function createRequest($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO record_requests (patient_name, patient_no, exam_type, reason, request_branch, branch_id, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        return $stmt->execute([
            $data['patient_name'],
            $data['patient_no'],
            $data['exam_type'],
            $data['reason'],
            $data['request_branch'],
            $data['branch_id']
        ]);
    }

    public function getPendingRequestsForBranch($branchName) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, b.name as requester_branch_name 
            FROM record_requests r
            LEFT JOIN branches b ON r.branch_id = b.id
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
    public function updateRequestStatus($requestId, $status, $branchName = null) {
        $sql = "UPDATE record_requests SET status = ? WHERE id = ?";
        $params = [$status, $requestId];
        
        if ($branchName) {
            $sql .= " AND request_branch = ?";
            $params[] = $branchName;
        }
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get request details by ID.
     */
    public function getRequestById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM record_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get requests made by a specific branch.
     */
    public function getRequestsByBranch($branchId) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, b.id as branch_id
            FROM record_requests r
            JOIN branches b ON r.request_branch = b.name
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
    public function processRequestAction($requestId, $action, $myBranchName, $notificationModel) {
        if (!$requestId || !in_array($action, ['Approve', 'Deny'])) {
            throw new Exception("Invalid action or request ID.");
        }

        $newStatus = ($action === 'Approve') ? 'Approved' : 'Denied';
        $requestData = $this->getRequestById($requestId);

        if ($requestData && $this->updateRequestStatus($requestId, $newStatus, $myBranchName)) {
            // Notifications Logic
            if (!empty($requestData['branch_id'])) {
                $notifTitle = "Record Request " . $newStatus;
                $notifMsg   = "Your request for patient " . ($requestData['patient_name'] ?? 'N/A') . " has been " . strtolower($newStatus) . " by " . $myBranchName . ".";
                $notificationModel->add($notifTitle, $notifMsg, "/" . PROJECT_DIR . "/index.php?role=radtech&page=record-request", null, 'radtech', $requestData['branch_id']);
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
                    "/" . PROJECT_DIR . "/index.php?role=branch_admin&page=record-requests", 
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
