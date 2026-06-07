<?php

namespace App\Controllers;

class PageController
{
    public function dispatch()
    {
        global $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? 'radtech';
        $userEmail = $_SESSION['email'] ?? 'user@example.com';
        $userId = $_SESSION['user_id'] ?? 0;
        $branchId = $_SESSION['branch_id'] ?? null;

        // 1. Determine requested page from request URI
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $projectPrefix = '/' . PROJECT_DIR;
        if (stripos($path, $projectPrefix) === 0) {
            $path = substr($path, strlen($projectPrefix));
        }
        $page = trim($path, '/');
        if ($page === '' || $page === 'index.php') {
            $page = $_GET['page'] ?? 'dashboard';
        }

        // Whitelist pages (same as legacy index.php)
        $allowedPages = [
            'dashboard',
            'patient-registration',
            'patient-lists',
            'patient-approval',
            'xray-patient-records',
            'record-request',
            'view-record-request',
            'patient-details',
            'records-history',
            'worklist',
            'patient-queue',
            'case-review',
            'patient-history',
            'patient-records-history',
            'xray-status',
            'case-status',
            'my-records',
            'registration',
            'download-report',
            'view-report',
            'patient-approvals',
            'record-requests',
            'branch-xray-cases',
            'reports',
            'users',
            'branches',
            'patient-records',
            'audit-logs',
            'user-role-settings',
            'settings',
            'security-settings',
            'backup-maintenance',
            'print-report',
        ];

        // Fallback for page parameter
        if (!in_array($page, $allowedPages, true)) {
            $page = 'dashboard';
        }

        // 2. DYNAMIC RBAC GUARD
        require_once basePath('app/Helpers/AuthHelper.php');

        $pagePermMap = [
            'users'               => 'user_mgmt',
            'branches'            => 'branch_mgmt',
            'patient-registration' => 'patient_reg',
            'patient-approvals'   => 'approvals',
            'audit-logs'          => 'audit_logs',
            'reports'             => 'global_reports',
            'security-settings'   => 'system_security',
            'user-role-settings'  => 'system_security',
            'settings'            => 'system_security',
            'backup-maintenance'  => 'backup_mgmt'
        ];

        if (isset($pagePermMap[$page])) {
            guardPermission($role, $pagePermMap[$page]);
        }

        // 3. Resolve and run controller (Class-based OOP if exists, fallback to procedural)
        $controllerName = str_replace('-', '', ucwords($page, '-')) . 'Controller.php';
        $controllerFile = basePath("app/Controllers/{$role}/{$controllerName}");
        $className = "App\\Controllers\\{$role}\\" . str_replace('-', '', ucwords($page, '-')) . 'Controller';

        // Read file to check if it's class-based to avoid triggering class loader on legacy procedural files
        $isClassBased = false;
        if (file_exists($controllerFile)) {
            $content = file_get_contents($controllerFile);
            if (strpos($content, 'class ' . str_replace('-', '', ucwords($page, '-')) . 'Controller') !== false) {
                $isClassBased = true;
            }
        }

        $controllerVars = [];
        if ($isClassBased && class_exists($className)) {
            $controller = new $className();
            if (method_exists($controller, 'handle')) {
                $controllerVars = $controller->handle();
            }
        } else {
            if (file_exists($controllerFile)) {
                require $controllerFile;
            }
        }

        // Merge variables returned from OOP controller handle()
        if (is_array($controllerVars) && !empty($controllerVars)) {
            extract($controllerVars);
        }

        // 4. Render View
        if ($page === 'print-report') {
            $contentView = "pages/radtech/print-report";
        } else {
            $contentView = "pages/{$role}/{$page}";
        }

        // Intercept specific AJAX requests before loading the layout
        if (isset($_GET['ajax_polling']) || ($page === 'patient-registration' && isset($_GET['ajax_search']))) {
            loadView($contentView, get_defined_vars());
            exit;
        }

        // Load layout
        if ($page === 'view-report' || $page === 'print-report') {
            loadView($contentView, get_defined_vars());
        } else {
            loadLayoutView($contentView, get_defined_vars());
        }
    }
}
