<?php
/**
 * patient-queue.view.php
 * DEPRECATED: This module has been retired.
 * Radiologists now manage pending queues directly through the unified Worklist
 * with branch-specific filtering and automatic case highlighting.
 */
require_once __DIR__ . '/../../../config/database.php';

$branchId = $_GET['branch_id'] ?? 0;
$branchName = $_GET['branch'] ?? '';

if (empty($branchName) && !empty($branchId)) {
    $branchModel = new \BranchModel($pdo);
    $b = $branchModel->getBranchById((int)$branchId);
    if (!empty($b['name'])) {
        $branchName = $b['name'];
    }
}

$highlight = $_GET['highlight'] ?? $_GET['highlight_case'] ?? $_GET['case_id'] ?? '';

$params = [];
if (!empty($branchName)) {
    $params[] = 'branch=' . urlencode($branchName);
}
if (!empty($highlight)) {
    $params[] = 'highlight_case=' . urlencode($highlight);
}
$query = !empty($params) ? '?' . implode('&', $params) : '';

redirect(url('worklist' . $query));
