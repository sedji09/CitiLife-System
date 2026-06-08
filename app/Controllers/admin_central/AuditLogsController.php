<?php
/**
 * AuditLogsController.php
 * Controller for viewing and filtering audit logs.
 */

require_once __DIR__ . '/../../Models/AuditLogModel.php';
require_once __DIR__ . '/../../Models/UserModel.php';

$auditLogModel = new AuditLogModel($pdo);
$userModel = new UserModel($pdo);

// Initialize variables
$filters = [
    'search'      => $_GET['search'] ?? '',
    'role'        => $_GET['role'] ?? '',
    'module'      => $_GET['module'] ?? '',
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
} catch (Exception $e) {
    $error = "Failed to retrieve logs: " . $e->getMessage();
    $logs = [];
    $total_count = 0;
    $distinctModules = [];
    $distinctRoles = [];
}

$total_pages = ceil($total_count / $limit) ?: 1;
