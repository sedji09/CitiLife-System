<?php

namespace App\Controllers\branch_admin;

class DashboardController
{
    public function handle()
    {
        global $pdo;


/**
 * DashboardController.php - Branch Admin
 * Fetches statistics and recent activity for the branch admin dashboard.
 */

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);
$recordRequestModel = new \RecordRequestModel($pdo);
$branchModel = new \BranchModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);

$branchId = $_SESSION['branch_id'] ?? 1;
$branchInfo = $branchModel->getBranchById($branchId);
$branchName = $branchInfo['name'] ?? '';
$branchDisplayName = $branchModel->getBranchDisplayName($branchId);

// Inputs for filtering stats
$filter = $_GET['filter'] ?? 'today';
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');

// Fetch Date Condition
$dateInfo = $caseModel->buildDateCondition($filter, $selectedMonth, $selectedYear);
$dateCondition = $dateInfo['condition'];
$periodLabel = $dateInfo['label'];

// 1. Fetch Case Stats (Today/Weekly/Monthly/Yearly)
$caseStats = $caseModel->getDashboardStats($branchId, $dateCondition);

// 2. Fetch Admin Stats (Pending counts)
$pendingApprovalsCount = $patientModel->countPendingPatientsByBranch($branchId);
$pendingRequestsCount = $recordRequestModel->countPendingRequestsForBranch($branchName);

// 3. New Dashboard Metrics (for Branch Admin)
$casesFilteredCount = $caseModel->getDashboardStats($branchId, $dateCondition)['total'];

// Total Patients of Branch
$branchTotalPatients = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE branch_id = ?");
$branchTotalPatients->execute([$branchId]);
$branchTotalPatients = $branchTotalPatients->fetchColumn();

// 4. Fetch Recent Activity (Sync with date filters)
$recentCases = $caseModel->getRecentCases($branchId, $dateCondition, 8);

// Prepare filters for audit logs to match the dashboard view
$auditFilters = [];
if ($filter === 'today') {
    $auditFilters['start_date'] = date('Y-m-d');
    $auditFilters['end_date'] = date('Y-m-d');
} elseif ($filter === 'weekly') {
    // Audit log start_date/end_date filter handles the range
    $auditFilters['start_date'] = date('Y-m-d', strtotime('monday this week'));
    $auditFilters['end_date'] = date('Y-m-d');
} elseif ($filter === 'monthly') {
    $auditFilters['start_date'] = $selectedMonth . '-01';
    $auditFilters['end_date'] = date('Y-m-t', strtotime($selectedMonth . '-01'));
} elseif ($filter === 'yearly') {
    $auditFilters['start_date'] = $selectedYear . '-01-01';
    $auditFilters['end_date'] = $selectedYear . '-12-31';
}

$recentActivity = $auditLogModel->getFilteredLogs($auditFilters, 8, 0, 'branch_admin', $branchId);

        return get_defined_vars();
    }
}
