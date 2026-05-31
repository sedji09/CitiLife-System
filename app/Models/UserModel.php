<?php
/**
 * UserModel.php
 * Handles all database interactions related to users.
 */

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get user by ID.
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Update user status (Active/Pending/Rejected).
     */
    public function updateUserStatus($userId, $status) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }

    /**
     * Get user by email.
     */
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Get display info (initials, formatted name, avatar) for sidebar/layout.
     */
    public function getDisplayInfo($userId, $sessionName = '', $sessionEmail = '') {
        // 1. Fetch Avatar
        $stmt = $this->pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $avatar = $stmt->fetchColumn();

        // 2. Generate Display Name and Initials
        $displayName = '';
        $initials = '';

        if ($sessionName) {
            $nameParts = explode(' ', $sessionName);
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
            $displayName = $sessionName;
        } else {
            $emailParts = explode('@', $sessionEmail);
            $nameParts = explode('.', $emailParts[0]);
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : substr($nameParts[0], 1, 1)));
            $displayName = implode(' ', array_map('ucfirst', $nameParts));
        }

        return [
            'avatar'      => $avatar,
            'displayName' => $displayName,
            'initials'    => $initials
        ];
    }

    /**
     * Get RadTech name specifically for reports.
     */
    public function getRadTechName($userId) {
        if (!$userId) return null;
        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Centralized logic for approving or rejecting a patient registration.
     * Handles DB update and notification dispatch.
     */
    public function processAccountStatus($userId, $action, $notificationModel) {
        if (!$userId || !in_array($action, ['Approve', 'Reject', 'Restore'])) {
            throw new Exception("Invalid action or user ID.");
        }

        if ($action === 'Approve') {
            $newStatus = 'Active';
        } elseif ($action === 'Reject') {
            $newStatus = 'Rejected';
        } else {
            $newStatus = 'Pending';
        }
        
        if ($this->updateUserStatus($userId, $newStatus)) {
            // Automatically reject pending cases if account is rejected
            if ($action === 'Reject') {
                $stmtRejectCases = $this->pdo->prepare("
                    UPDATE cases SET approval_status = 'Rejected' 
                    WHERE patient_id = (SELECT patient_id FROM users WHERE id = ?) 
                    AND approval_status = 'Pending'
                ");
                $stmtRejectCases->execute([$userId]);
            }

            // Notifications Logic
            if ($action !== 'Restore') {
                $notifTitle = ($action === 'Approve') ? "Account Approved" : "Registration Rejected";
                $notifMsg   = ($action === 'Approve') 
                                ? "Your patient account has been approved! You can now log in to the Patient Portal." 
                                : "Your patient account registration was rejected by the branch admin.";
                $notifLink  = ($action === 'Approve')
                                ? "/" . PROJECT_DIR . "/index.php?role=patient&page=xray-status"
                                : "/" . PROJECT_DIR . "/patient-login.php";
                
                $notificationModel->add($notifTitle, $notifMsg, $notifLink, $userId);
            }
            
            $actionLabel = ($action === 'Restore') ? "restored to pending" : strtolower($action) . "d";
            return [
                'success' => true,
                'message' => "Patient account $actionLabel successfully."
            ];
        }

        return [
            'success' => false,
            'message' => "Failed to update account."
        ];
    }
    /**
     * Get all staff users (exclude patients).
     */
    public function getAllStaffUsers() {
        $stmt = $this->pdo->prepare("
            SELECT u.*, b.name as branch_name 
            FROM users u 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.role != 'patient' 
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new staff account.
     */
    public function createStaffUser($email, $password, $role, $branchId = null) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password, role, branch_id, status) 
            VALUES (?, ?, ?, ?, 'Active')
        ");
        return $stmt->execute([$email, $hashedPassword, $role, $branchId]);
    }

    /**
     * Update a staff account.
     */
    public function updateStaffUser($id, $email, $role, $branchId = null, $password = null) {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users SET email = ?, password = ?, role = ?, branch_id = ? 
                WHERE id = ? AND role != 'admin_central'
            ");
            return $stmt->execute([$email, $hashedPassword, $role, $branchId, $id]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE users SET email = ?, role = ?, branch_id = ? 
                WHERE id = ? AND role != 'admin_central'
            ");
            return $stmt->execute([$email, $role, $branchId, $id]);
        }
    }

    /**
     * Delete a staff account.
     */
    public function deleteStaffUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin_central'");
        return $stmt->execute([$id]);
    }

    /**
     * Permanently delete a patient account registration.
     * Removes both the user record and the linked patient record.
     */
    public function deletePatientAccount($userId) {
        if (!$userId) return ['success' => false, 'message' => "Invalid User ID."];

        // 1. Fetch patient_id before deleting the user
        $stmt = $this->pdo->prepare("SELECT patient_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $patientId = $stmt->fetchColumn();

        try {
            $this->pdo->beginTransaction();

            // 2. Delete the user record
            $stmtUser = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);

            // 3. Delete the patient record if linked
            if ($patientId) {
                // Check if this patient is linked to any other users (unlikely but safe)
                $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE patient_id = ?");
                $stmtCheck->execute([$patientId]);
                if ($stmtCheck->fetchColumn() == 0) {
                    // Also check for cases
                    $stmtCase = $this->pdo->prepare("SELECT COUNT(*) FROM cases WHERE patient_id = ?");
                    $stmtCase->execute([$patientId]);
                    if ($stmtCase->fetchColumn() == 0) {
                        $stmtPatient = $this->pdo->prepare("DELETE FROM patients WHERE id = ?");
                        $stmtPatient->execute([$patientId]);
                    }
                }
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => "Patient registration deleted successfully. Email is now reusable."
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => "Error deleting account: " . $e->getMessage()
            ];
        }
    }
}
