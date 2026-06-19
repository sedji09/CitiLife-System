<?php

namespace App\Controllers\admin_central;

class BranchesController
{
    public function handle()
    {
        global $pdo;



$branchModel = new \BranchModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentBranchId = $_SESSION['branch_id'] ?? null;

$success = '';
$error = '';

// Handle AJAX/POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $additionalAddress = trim($_POST['additional_address'] ?? '');
        $contact1 = trim($_POST['contact_number_1'] ?? '');
        $contact2 = trim($_POST['contact_number_2'] ?? '');
        $contact3 = trim($_POST['contact_number_3'] ?? '');

        if (empty($name)) {
            $error = "Branch name is required.";
        } else {
            // Check for duplicate branch (Same Name AND Same Address)
            if ($branchModel->getBranchByNameAndAddress($name, $address)) {
                $error = "A branch named '" . htmlspecialchars($name) . "' already exists at this address.";
            } else if ($branchModel->createBranch($name, $address, $additionalAddress, $contact1, $contact2, $contact3)) {
                $success = "Branch created successfully!";
                $newBranchId = $pdo->lastInsertId();
                $auditLogModel->addLog($currentUserId, "Added new branch: $name", 'Branch Management', 'Branch', $newBranchId, "Address: $address", $newBranchId);
            } else {
                $error = "Failed to create branch.";
            }
        }
    }

    if ($action === 'delete') {
        $id = $_POST['branch_id'] ?? null;
        if ($id && $branchModel->deleteBranch($id)) {
            $success = "Branch deleted successfully.";
            $auditLogModel->addLog($currentUserId, "Deleted branch", 'Branch Management', 'Branch', $id, "Deleted branch ID: $id", $id);
        } else {
            $error = "Failed to delete branch.";
        }
    }

    if ($action === 'update') {
        $id = $_POST['branch_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $additionalAddress = trim($_POST['additional_address'] ?? '');
        $contact1 = trim($_POST['contact_number_1'] ?? '');
        $contact2 = trim($_POST['contact_number_2'] ?? '');
        $contact3 = trim($_POST['contact_number_3'] ?? '');

        if ($id && !empty($name)) {
            // Check for duplicate branch (Same Name AND Same Address, excluding current ID)
            $existing = $branchModel->getBranchByNameAndAddress($name, $address);
            if ($existing && $existing['id'] != $id) {
                $error = "Another branch with the name '" . htmlspecialchars($name) . "' already exists at this location.";
            } else if ($branchModel->updateBranch($id, $name, $address, $additionalAddress, $contact1, $contact2, $contact3)) {
                $success = "Branch updated successfully!";
                $auditLogModel->addLog($currentUserId, "Updated branch details", 'Branch Management', 'Branch', $id, "New Name: $name, New Address: $address", $id);
            } else {
                $error = "Failed to update branch.";
            }
        }
    }

    if ($action === 'toggle-status') {
        $id = $_POST['branch_id'] ?? null;
        $newStatus = $_POST['new_status'] ?? 'Active';

        if ($id && $branchModel->updateBranchStatus($id, $newStatus)) {
            $success = "Branch status updated to " . htmlspecialchars($newStatus) . "!";
            $auditLogModel->addLog($currentUserId, "Branch status changed to $newStatus", 'Branch Management', 'Branch', $id, "New Status: $newStatus", $id);
        } else {
            $error = "Failed to update branch status.";
        }
    }
}

// Fetch all branches
$branches = $branchModel->getAllBranches();

        return get_defined_vars();
    }
}
