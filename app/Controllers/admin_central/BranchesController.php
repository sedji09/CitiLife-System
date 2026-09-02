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

                    // If deactivated, immediately reset active session activity for staff belonging to this branch
                    if ($newStatus === 'Inactive') {
                        $stmtClear = $pdo->prepare("UPDATE users SET last_activity = NULL WHERE branch_id = ? AND role NOT IN ('admin_central', 'it_admin', 'patient')");
                        $stmtClear->execute([$id]);
                    }
                } else {
                    $error = "Failed to update branch status.";
                }
            }

            if ($action === 'upload_qr') {
                $id = $_POST['branch_id'] ?? null;
                if ($id && isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../../public/uploads/qrcodes/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileExt = strtolower(pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($fileExt, $allowedExts)) {
                        $newFilename = 'gcash_qr_branch_' . $id . '_' . time() . '.' . $fileExt;
                        $dest = $uploadDir . $newFilename;
                        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $dest)) {
                            $proofPath = '/public/uploads/qrcodes/' . $newFilename;
                            $stmt = $pdo->prepare("UPDATE branches SET gcash_qr_path = ? WHERE id = ?");
                            if ($stmt->execute([$proofPath, $id])) {
                                $success = "GCash QR Code uploaded successfully.";
                                $auditLogModel->addLog($currentUserId, "Uploaded GCash QR", 'Branch Management', 'Branch', $id, "Uploaded new QR for branch $id", $id);
                            } else {
                                $error = "Failed to update database.";
                            }
                        } else {
                            $error = "Failed to move uploaded file.";
                        }
                    } else {
                        $error = "Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.";
                    }
                } else {
                    $error = "Please select a valid image file.";
                }
            }

            if ($action === 'remove_qr') {
                $id = $_POST['branch_id'] ?? null;
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE branches SET gcash_qr_path = NULL WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success = "GCash QR Code removed successfully.";
                        $auditLogModel->addLog($currentUserId, "Removed GCash QR", 'Branch Management', 'Branch', $id, "Removed QR for branch $id", $id);
                    } else {
                        $error = "Failed to remove QR code from database.";
                    }
                }
            }
        }

        // Fetch all branches
        $branches = $branchModel->getAllBranches();

        return get_defined_vars();
    }
}
