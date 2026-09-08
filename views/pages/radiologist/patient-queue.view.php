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

$base = defined('PROJECT_DIR') && PROJECT_DIR ? '/' . PROJECT_DIR : '';
$redirectUrl = $base . '/index.php?role=radiologist&page=worklist';
$params = [];

if (!empty($branchName)) {
    $params[] = 'branch=' . urlencode($branchName);
}
if (!empty($highlight)) {
    $params[] = 'highlight_case=' . urlencode($highlight);
}
if (!empty($params)) {
    $redirectUrl .= '&' . implode('&', $params);
}

header("Location: " . $redirectUrl);
exit;
