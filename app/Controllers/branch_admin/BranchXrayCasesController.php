<?php

namespace App\Controllers\branch_admin;

class BranchXrayCasesController
{
    public function handle()
    {
        global $pdo;


/**
 * BranchXrayCasesController.php
 * Handles backend logic for the Branch Admin's X-ray Cases (Tabbed: Today's Queue & Patient Records).
 */


$caseModel = new \CaseModel($pdo);

// 1. Ensure Schema
$caseModel->ensureSchema();

$branchId = $_SESSION['branch_id'] ?? 1;

// 2. Fetch Data for both tabs
// Tab 1: Active Queue (Includes Today and Backlogs)
$allQueue = $caseModel->getWorklist($branchId, null, null);
$todayQueue = array_filter($allQueue, function($p) {
    return $p['released'] == 0 
           && $p['approval_status'] === 'Approved';
});

// Tab 2: Patient Records (Released)
$releasedRecords = $caseModel->getReleasedRecords($branchId);

// 3. Tab State
$currentTab = $_GET['tab'] ?? 'queue';
if (!in_array($currentTab, ['queue', 'records'])) {
    $currentTab = 'queue';
}

        return get_defined_vars();
    }
}
