<?php

namespace App\Controllers\admin_central;

class AuditLogsController
{
    public function handle()
    {
        global $pdo;


/**
 * AuditLogsController.php
 * Controller for viewing and filtering audit logs.
 */


$auditLogModel = new \AuditLogModel($pdo);
$userModel = new \UserModel($pdo);

// Initialize variables
$filters = [
    'search'      => $_GET['search'] ?? '',
    'role'        => $_GET['role'] ?? '',
    'module'      => $_GET['module'] ?? '',
    'sort'        => $_GET['sort'] ?? '',
    'start_date'  => $_GET['start_date'] ?? '',
    'end_date'    => $_GET['end_date'] ?? ''
];

$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

$currentRole = $_SESSION['role'] ?? 'admin_central';
$currentBranchId = $_SESSION['branch_id'] ?? null;

// Fetch logs and filter options
try {
    $logs = $auditLogModel->getFilteredLogs($filters, $limit, $offset, $currentRole, $currentBranchId);
    $total_count = $auditLogModel->getTotalFilteredLogsCount($filters, $currentRole, $currentBranchId);
    $distinctModules = $auditLogModel->getDistinctModules();
    $distinctRoles = $auditLogModel->getDistinctRoles();
} catch (\Exception $e) {
    $error = "Failed to retrieve logs: " . $e->getMessage();
    $logs = [];
    $total_count = 0;
    $distinctModules = [];
    $distinctRoles = [];
}

$total_pages = ceil($total_count / $limit) ?: 1;

        return get_defined_vars();
    }
}
