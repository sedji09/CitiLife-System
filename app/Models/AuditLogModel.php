<?php
/**
 * AuditLogModel.php
 * Handles all database interactions related to audit logs.
 */

class AuditLogModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Add a new audit log entry.
     */
    public function addLog($userId, $action, $module, $entityType, $entityId, $details = null, $branchId = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, branch_id, module, action, entity_type, entity_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId, 
            $branchId,
            $module,
            $action, 
            $entityType, 
            $entityId, 
            $details, 
            $ipAddress
        ]);
    }

    /**
     * Get distinct modules for filtering.
     */
    public function getDistinctModules() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct roles for filtering.
     */
    public function getDistinctRoles() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT role FROM users WHERE role != 'patient' ORDER BY role ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get total count of filtered audit logs.
     */
    public function getTotalFilteredLogsCount($filters = [], $currentRole = 'admin_central', $currentBranchId = null) {
        $query = "
            SELECT COUNT(*)
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        // Role-based filtering: Non-central admins see only their own branch logs
        if (!in_array($currentRole, ['admin_central', 'it_admin']) && $currentBranchId) {
            $query .= " AND al.branch_id = ?";
            $params[] = $currentBranchId;
        }

        if (!empty($filters['search'])) {
            $query .= " AND (al.action LIKE ? OR u.name LIKE ? OR al.details LIKE ? OR al.module LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['role'])) {
            $query .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['module'])) {
            $query .= " AND al.module = ?";
            $params[] = $filters['module'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Filter audit logs.
     */
    public function getFilteredLogs($filters = [], $limit = 50, $offset = 0, $currentRole = 'admin_central', $currentBranchId = null) {
        $query = "
            SELECT al.*, u.email as user_email, u.role as user_role, u.name as user_name, b.name as branch_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN branches b ON al.branch_id = b.id
            WHERE 1=1
        ";
        $params = [];

        // Role-based filtering: Non-central admins see only their own branch logs
        if (!in_array($currentRole, ['admin_central', 'it_admin']) && $currentBranchId) {
            $query .= " AND al.branch_id = ?";
            $params[] = $currentBranchId;
        }

        if (!empty($filters['search'])) {
            $query .= " AND (al.action LIKE ? OR u.name LIKE ? OR al.details LIKE ? OR al.module LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['role'])) {
            $query .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['module'])) {
            $query .= " AND al.module = ?";
            $params[] = $filters['module'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        $sortOrder = (isset($filters['sort']) && strtolower($filters['sort']) === 'asc') ? 'ASC' : 'DESC';
        $query .= " ORDER BY al.created_at $sortOrder LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($idx++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
