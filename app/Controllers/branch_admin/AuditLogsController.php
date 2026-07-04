<?php

namespace App\Controllers\branch_admin;

class AuditLogsController
{
    public function handle()
    {
        global $pdo;


/**
 * AuditLogsController.php - Branch Admin
 * Controller for viewing and filtering audit logs for a specific branch.
 */

$auditLogModel = new \AuditLogModel($pdo);
$userModel = new \UserModel($pdo);

// Initialize variables
$filters = [
    'search'      => $_GET['search'] ?? '',
    'role'        => $_GET['rl'] ?? '', // Role filter renamed to 'rl' to avoid conflict with routing 'role'
    'module'      => $_GET['module'] ?? '',
    'sort'        => $_GET['sort'] ?? '',
    'start_date'  => $_GET['start_date'] ?? '',
    'end_date'    => $_GET['end_date'] ?? ''
];

$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 7; // Fixed to 7 rows per page for better visibility
$offset = ($page_num - 1) * $limit;

$currentRole = $_SESSION['role'] ?? 'branch_admin';
$currentBranchId = $_SESSION['branch_id'] ?? null;

// Fetch logs and filter options
try {
    // Branch Admin only sees their own branch logs
    $logs = $auditLogModel->getFilteredLogs($filters, $limit, $offset, $currentRole, $currentBranchId);
    $total_count = $auditLogModel->getTotalFilteredLogsCount($filters, $currentRole, $currentBranchId);
    $distinctModules = $auditLogModel->getDistinctModules($currentRole, $currentBranchId);
    // For roles, we might only want to show staff roles, but keeping it general for now
    $distinctRoles = $auditLogModel->getDistinctRoles($currentRole);
} catch (\Exception $e) {
    $error = "Failed to retrieve logs: " . $e->getMessage();
    $logs = [];
    $total_count = 0;
    $distinctModules = [];
    $distinctRoles = [];
}

        return get_defined_vars();
    }
}
