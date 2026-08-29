<?php

namespace App\Controllers\admin_central;

class ServicePricingController
{
    public function handle()
    {
        global $pdo;

        require_once basePath('app/Models/ServiceModel.php');
        require_once basePath('app/Models/AuditLogModel.php');

        $serviceModel = new \ServiceModel($pdo);
        $auditLogModel = new \AuditLogModel($pdo);
        $currentUserId = $_SESSION['user_id'] ?? 0;

        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $category = trim($_POST['category'] ?? '');
                $customCategory = trim($_POST['custom_category'] ?? '');
                if ($category === '__new__' && !empty($customCategory)) {
                    $category = $customCategory;
                }

                $examType = trim($_POST['exam_type'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $isPhilhealthCovered = !empty($_POST['is_philhealth_covered']) ? 1 : 0;
                $philhealthDiscount = $isPhilhealthCovered ? filter_var($_POST['philhealth_discount'] ?? 0, FILTER_VALIDATE_FLOAT) : 0.00;
                if ($philhealthDiscount === false || $philhealthDiscount < 0) {
                    $philhealthDiscount = 0.00;
                }

                $status = $_POST['status'] ?? 'active';
                if (!in_array($status, ['active', 'inactive'])) {
                    $status = 'active';
                }

                if (empty($category) || empty($examType) || $price === false || $price < 0) {
                    $error = "Please fill in all required fields with valid values.";
                } elseif ($isPhilhealthCovered && $philhealthDiscount > $price) {
                    $error = "PhilHealth discount cannot exceed the procedure price.";
                } else {
                    if ($serviceModel->createService($category, $examType, $price, $isPhilhealthCovered, $philhealthDiscount, $status)) {
                        $newId = $pdo->lastInsertId();
                        $success = "X-Ray service '{$examType}' added successfully!";
                        $auditLogModel->addLog(
                            $currentUserId,
                            "Added X-Ray Service: $examType",
                            'Service Pricing',
                            'Service',
                            $newId,
                            "Category: $category, Price: PHP $price, PhilHealth: " . ($isPhilhealthCovered ? "Yes (Discount: PHP $philhealthDiscount)" : "No") . ", Status: $status",
                            null
                        );
                    } else {
                        $error = "Failed to add new X-Ray service.";
                    }
                }
            }

            if ($action === 'update') {
                $id = $_POST['service_id'] ?? null;
                $category = trim($_POST['category'] ?? '');
                $customCategory = trim($_POST['custom_category'] ?? '');
                if ($category === '__new__' && !empty($customCategory)) {
                    $category = $customCategory;
                }

                $examType = trim($_POST['exam_type'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $isPhilhealthCovered = !empty($_POST['is_philhealth_covered']) ? 1 : 0;
                $philhealthDiscount = $isPhilhealthCovered ? filter_var($_POST['philhealth_discount'] ?? 0, FILTER_VALIDATE_FLOAT) : 0.00;
                if ($philhealthDiscount === false || $philhealthDiscount < 0) {
                    $philhealthDiscount = 0.00;
                }

                $status = $_POST['status'] ?? 'active';
                if (!in_array($status, ['active', 'inactive'])) {
                    $status = 'active';
                }

                if (!$id || empty($category) || empty($examType) || $price === false || $price < 0) {
                    $error = "Please provide valid information to update the service.";
                } elseif ($isPhilhealthCovered && $philhealthDiscount > $price) {
                    $error = "PhilHealth discount cannot exceed the procedure price.";
                } else {
                    if ($serviceModel->updateService($id, $category, $examType, $price, $isPhilhealthCovered, $philhealthDiscount, $status)) {
                        $success = "Service '{$examType}' updated successfully!";
                        $auditLogModel->addLog(
                            $currentUserId,
                            "Updated X-Ray Service: $examType",
                            'Service Pricing',
                            'Service',
                            $id,
                            "Category: $category, Price: PHP $price, PhilHealth: " . ($isPhilhealthCovered ? "Yes (Discount: PHP $philhealthDiscount)" : "No") . ", Status: $status",
                            null
                        );
                    } else {
                        $error = "Failed to update X-Ray service.";
                    }
                }
            }

            if ($action === 'toggle-status') {
                $id = $_POST['service_id'] ?? null;
                $newStatus = $_POST['new_status'] ?? 'active';
                if (!in_array($newStatus, ['active', 'inactive'])) {
                    $newStatus = 'active';
                }

                if ($id && $serviceModel->updateServiceStatus($id, $newStatus)) {
                    $actionText = ($newStatus === 'active') ? 'activated' : 'deactivated';
                    $success = "Service status successfully set to " . ucfirst($newStatus) . "!";
                    $auditLogModel->addLog(
                        $currentUserId,
                        "Status changed to $newStatus",
                        'Service Pricing',
                        'Service',
                        $id,
                        "Service ID: $id status $actionText",
                        null
                    );
                } else {
                    $error = "Failed to update service status.";
                }
            }

            if ($action === 'delete') {
                $id = $_POST['service_id'] ?? null;
                if ($id && $serviceModel->deleteService($id)) {
                    $success = "X-Ray service removed successfully.";
                    $auditLogModel->addLog(
                        $currentUserId,
                        "Deleted X-Ray Service",
                        'Service Pricing',
                        'Service',
                        $id,
                        "Deleted service ID: $id",
                        null
                    );
                } else {
                    $error = "Failed to delete service.";
                }
            }
        }

        // Fetch all services and categories
        $services = $serviceModel->getAllServices();
        $categories = $serviceModel->getCategories();

        return get_defined_vars();
    }
}
