<?php
/**
 * UserRoleDefaultsController.php
 * IT Admin module for managing system security policies and role permissions.
 */

require_once __DIR__ . '/../../Models/AuditLogModel.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';

$auditLogModel = new AuditLogModel($pdo);
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// 1. Define UI categories (Hardcoded because these represent functional blocks in the code)
$roles = [
    'it_admin'      => 'IT System Admin',
    'admin_central' => 'Admin (Central)',
    'branch_admin'  => 'Branch Admin',
    'radtech'       => 'RadTech (Staff)',
    'radiologist'   => 'Radiologist',
    'patient'       => 'Patient'
];

$permissions = [
    'Management' => [
        'system_security'   => ['label' => 'System Security Settings', 'desc' => 'Manage password policies and logout rules.'],
        'backup_mgmt'       => ['label' => 'Backup & Maintenance', 'desc' => 'Download DB backups and server health.'],
        'user_mgmt'         => ['label' => 'Staff User Management', 'desc' => 'Create/Edit/Delete staff accounts.'],
        'branch_mgmt'       => ['label' => 'Branch Management', 'desc' => 'Manage clinical branch locations.'],
    ],
    'Clinical' => [
        'worklist'          => ['label' => 'Clinical Worklist', 'desc' => 'View list of patients waiting for diagnosis.'],
        'case_review'       => ['label' => 'Medical Case Review', 'desc' => 'Full access to X-ray images and patient file.'],
        'write_report'      => ['label' => 'Submit Medical Reports', 'desc' => 'Draft and sign diagnostic radiology reports.'],
        'patient_history'   => ['label' => 'Global Patient History', 'desc' => 'Search and view all previous case records.'],
    ],
    'Operational' => [
        'patient_reg'       => ['label' => 'Patient Registration', 'desc' => 'Register new patients into the system.'],
        'approvals'         => ['label' => 'Registration Approvals', 'desc' => 'Approve or Reject new patient accounts.'],
        'record_requests'   => ['label' => 'Handle Record Requests', 'desc' => 'Manage requests for old hard-copy records.'],
    ],
    'Utility' => [
        'audit_logs'        => ['label' => 'View System Audit Logs', 'desc' => 'Track all system activities and timestamped logs.'],
        'global_reports'    => ['label' => 'View Statistical Reports', 'desc' => 'Access analytics and branch performance.'],
        'dashboard'         => ['label' => 'Access UI Dashboard', 'desc' => 'View the main summary board for their role.'],
    ]
];

// 2. Handle AJAX Toggle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_perm') {
    header('Content-Type: application/json');
    $roleKey = $_POST['role'] ?? '';
    $permKey = $_POST['perm'] ?? '';
    $level   = intval($_POST['level'] ?? 0);

    if (isset($roles[$roleKey])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role, perm_key, access_level) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE access_level = VALUES(access_level)");
            if ($stmt->execute([$roleKey, $permKey, $level])) {
                // Log the change
                $adminId = $_SESSION['user_id'] ?? 0;
                $details = "Updated Permission: Role [$roleKey], Perm [$permKey] set to level [$level].";
                $auditLogModel->addLog($adminId, 'Update Role Permission', 'IT Admin', 'RBAC', 0, $details);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB execution failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    exit();
}

// 3. Load active matrix from DB
$activeMatrix = [];
$stmt = $pdo->query("SELECT role, perm_key, access_level FROM role_permissions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $activeMatrix[$row['role']][$row['perm_key']] = intval($row['access_level']);
}
