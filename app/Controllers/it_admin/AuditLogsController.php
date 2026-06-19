<?php

namespace App\Controllers\it_admin;

class AuditLogsController
{
    public function handle()
    {
        global $pdo;


/**
 * AuditLogsController.php
 * IT Admin controller for global system audit monitoring.
 */


$auditLogModel = new \AuditLogModel($pdo);
$userModel = new \UserModel($pdo);

// 1. Collect Filters
$filters = [
    'search'      => $_GET['search'] ?? '',
    'role'        => $_GET['role'] ?? '',
    'module'      => $_GET['module'] ?? '',
    'start_date'  => $_GET['start_date'] ?? '',
    'end_date'    => $_GET['end_date'] ?? '',
    'sort'        => $_GET['sort'] ?? 'desc'
];

// 2. Pagination Logic
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 15; // Global view shows more records
$offset = ($page_num - 1) * $limit;

$currentRole = $_SESSION['role'] ?? 'it_admin';
$currentBranchId = $_SESSION['branch_id'] ?? null;

// 3. Fetch Data
try {
    $logs = $auditLogModel->getFilteredLogs($filters, $limit, $offset, $currentRole, $currentBranchId);
    $total_count = $auditLogModel->getTotalFilteredLogsCount($filters, $currentRole, $currentBranchId);
    $distinctModules = $auditLogModel->getDistinctModules();
    $distinctRoles = $auditLogModel->getDistinctRoles();
} catch (\Exception $e) {
    $error = "System Error: Failed to retrieve logs. " . $e->getMessage();
    $logs = [];
    $total_count = 0;
    $distinctModules = [];
    $distinctRoles = [];
}

// 4. Calculate pagination
$total_pages = ceil($total_count / $limit);

        return get_defined_vars();
    }
}
