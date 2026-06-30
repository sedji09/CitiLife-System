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
        $ipAddress = '0.0.0.0';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Sometimes it's a comma separated list, get the first one
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ipList[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
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
    public function getDistinctModules($currentRole = 'admin_central', $currentBranchId = null) {
        // For IT Admin, always return the fixed list of technical/system modules
        // regardless of whether there are existing logs for them yet.
        if ($currentRole === 'it_admin') {
            $modules = ['Authentication', 'Branch Management', 'Security Settings', 'System', 'User Management'];
            sort($modules);
            return $modules;
        }

        $params = [];
        // For branch admin, only fetch modules that actually have logs in their branch
        if ($currentRole === 'branch_admin' && $currentBranchId) {
            $query = "SELECT DISTINCT al.module FROM audit_logs al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      WHERE al.module IS NOT NULL AND al.branch_id = ? 
                      AND (u.role IS NULL OR u.role NOT IN ('admin_central', 'it_admin'))";
            $params[] = $currentBranchId;
        } else {
            $query = "SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $dbModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        sort($dbModules);
        return $dbModules;
    }

    public function getDistinctRoles($currentRole = 'admin_central') {
        $query = "SELECT DISTINCT role FROM users WHERE role != 'patient'";
        if ($currentRole === 'branch_admin') {
            $query .= " AND role NOT IN ('admin_central', 'it_admin')";
        }
        $query .= " ORDER BY role ASC";
        
        $stmt = $this->pdo->prepare($query);
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
            LEFT JOIN patients p ON u.patient_id = p.id
            WHERE 1=1
        ";
        $params = [];

        // Role-based filtering: Non-central admins see only their own branch logs
        if (!in_array($currentRole, ['admin_central', 'it_admin']) && $currentBranchId) {
            $query .= " AND al.branch_id = ?";
            $params[] = $currentBranchId;
            // Exclude logs from central admins in branch view
            $query .= " AND (u.role IS NULL OR u.role NOT IN ('admin_central', 'it_admin'))";
        }

        // IT Admin sees only system-related logs
        if ($currentRole === 'it_admin') {
            $query .= " AND al.module IN ('User Management', 'Branch Management', 'Authentication', 'System', 'Security Settings')";
        }

        if (!empty($filters['search'])) {
            $query .= " AND (al.action LIKE ? OR COALESCE(u.name, CONCAT(p.first_name, ' ', p.last_name)) LIKE ? OR al.details LIKE ? OR al.module LIKE ?)";
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
            SELECT al.*, u.email as user_email, u.role as user_role, 
                   COALESCE(u.name, CONCAT(p.first_name, ' ', p.last_name)) as user_name, 
                   b.name as branch_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN patients p ON u.patient_id = p.id
            LEFT JOIN branches b ON al.branch_id = b.id
            WHERE 1=1
        ";
        $params = [];

        // Role-based filtering: Non-central admins see only their own branch logs
        if (!in_array($currentRole, ['admin_central', 'it_admin']) && $currentBranchId) {
            $query .= " AND al.branch_id = ?";
            $params[] = $currentBranchId;
            // Exclude logs from central admins in branch view
            $query .= " AND (u.role IS NULL OR u.role NOT IN ('admin_central', 'it_admin'))";
        }

        // IT Admin sees only system-related logs
        if ($currentRole === 'it_admin') {
            $query .= " AND al.module IN ('User Management', 'Branch Management', 'Authentication', 'System', 'Security Settings')";
        }

        if (!empty($filters['search'])) {
            $query .= " AND (al.action LIKE ? OR COALESCE(u.name, CONCAT(p.first_name, ' ', p.last_name)) LIKE ? OR al.details LIKE ? OR al.module LIKE ?)";
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
