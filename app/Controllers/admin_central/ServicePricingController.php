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
                $serviceType = trim($_POST['service_type'] ?? 'X-Ray');
                if (empty($serviceType)) {
                    $serviceType = 'X-Ray';
                }

                $category = trim($_POST['category'] ?? '');
                $customCategory = trim($_POST['custom_category'] ?? '');
                if ($category === '__new__' && !empty($customCategory)) {
                    $category = $customCategory;
                }

                $examType = trim($_POST['exam_type'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $isPhilhealthCovered = !empty($_POST['is_philhealth_covered']) ? 1 : 0;
                $rawDiscount = trim($_POST['philhealth_discount'] ?? '');
                $philhealthDiscount = ($isPhilhealthCovered && $rawDiscount !== '') ? filter_var($rawDiscount, FILTER_VALIDATE_FLOAT) : ($isPhilhealthCovered ? false : 0.00);

                $status = $_POST['status'] ?? 'active';
                if (!in_array($status, ['active', 'inactive'])) {
                    $status = 'active';
                }

                if (empty($category) || empty($examType) || $price === false || $price < 0) {
                    $error = "Please fill in all required fields with valid values.";
                } elseif ($isPhilhealthCovered && ($philhealthDiscount === false || $philhealthDiscount <= 0)) {
                    $error = "Please enter a valid PhilHealth discount amount greater than ₱0.00.";
                } elseif ($isPhilhealthCovered && $philhealthDiscount > $price) {
                    $error = "PhilHealth discount cannot exceed the procedure price.";
                } elseif ($serviceModel->serviceExists($examType)) {
                    $error = "An exam procedure with this name already exists.";
                } else {
                    if ($serviceModel->createService($category, $examType, $price, $isPhilhealthCovered, (float)$philhealthDiscount, $status, $serviceType)) {
                        $newId = $pdo->lastInsertId();
                        $success = "Diagnostic service '{$examType}' ({$serviceType}) added successfully!";
                        $auditLogModel->addLog(
                            $currentUserId,
                            "Added Diagnostic Service: $examType",
                            'Service Pricing',
                            'Service',
                            $newId,
                            "Type: $serviceType, Category: $category, Price: PHP $price, PhilHealth: " . ($isPhilhealthCovered ? "Yes (Discount: PHP $philhealthDiscount)" : "No") . ", Status: $status",
                            null
                        );
                    } else {
                        $error = "Failed to add new diagnostic service.";
                    }
                }
            }

            if ($action === 'update') {
                $id = $_POST['service_id'] ?? null;
                $serviceType = trim($_POST['service_type'] ?? 'X-Ray');
                if (empty($serviceType)) {
                    $serviceType = 'X-Ray';
                }

                $category = trim($_POST['category'] ?? '');
                $customCategory = trim($_POST['custom_category'] ?? '');
                if ($category === '__new__' && !empty($customCategory)) {
                    $category = $customCategory;
                }

                $examType = trim($_POST['exam_type'] ?? '');
                $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                $isPhilhealthCovered = !empty($_POST['is_philhealth_covered']) ? 1 : 0;
                $rawDiscount = trim($_POST['philhealth_discount'] ?? '');
                $philhealthDiscount = ($isPhilhealthCovered && $rawDiscount !== '') ? filter_var($rawDiscount, FILTER_VALIDATE_FLOAT) : ($isPhilhealthCovered ? false : 0.00);

                $status = $_POST['status'] ?? 'active';
                if (!in_array($status, ['active', 'inactive'])) {
                    $status = 'active';
                }

                if (!$id || empty($category) || empty($examType) || $price === false || $price < 0) {
                    $error = "Please provide valid information to update the service.";
                } elseif ($isPhilhealthCovered && ($philhealthDiscount === false || $philhealthDiscount <= 0)) {
                    $error = "Please enter a valid PhilHealth discount amount greater than ₱0.00.";
                } elseif ($isPhilhealthCovered && $philhealthDiscount > $price) {
                    $error = "PhilHealth discount cannot exceed the procedure price.";
                } elseif ($serviceModel->serviceExists($examType, $id)) {
                    $error = "An exam procedure with this name already exists.";
                } else {
                    if ($serviceModel->updateService($id, $category, $examType, $price, $isPhilhealthCovered, (float)$philhealthDiscount, $status, $serviceType)) {
                        $success = "Service '{$examType}' updated successfully!";
                        $auditLogModel->addLog(
                            $currentUserId,
                            "Updated Diagnostic Service: $examType",
                            'Service Pricing',
                            'Service',
                            $id,
                            "Type: $serviceType, Category: $category, Price: PHP $price, PhilHealth: " . ($isPhilhealthCovered ? "Yes (Discount: PHP $philhealthDiscount)" : "No") . ", Status: $status",
                            null
                        );
                    } else {
                        $error = "Failed to update diagnostic service.";
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
                    $actionText = ($newStatus === 'active') ? 'visible' : 'hidden';
                    $success = ($newStatus === 'active') ? "Service is now visible." : "Service is now hidden.";
                    $auditLogModel->addLog(
                        $currentUserId,
                        "Status changed to $newStatus ($actionText)",
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
                    $success = "Diagnostic service removed successfully.";
                    $auditLogModel->addLog(
                        $currentUserId,
                        "Deleted Diagnostic Service",
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

        // Fetch all services, categories, and service types
        $services = $serviceModel->getAllServices();
        $categories = $serviceModel->getCategories();
        $serviceTypes = $serviceModel->getServiceTypes();

        return get_defined_vars();
    }
}
