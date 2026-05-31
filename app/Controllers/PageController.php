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
        if (strpos($path, $projectPrefix) === 0) {
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
            'user-role-defaults',
            'settings',
            'security-settings',
            'backup-maintenance',
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
            'user-role-defaults'  => 'system_security',
            'settings'            => 'system_security',
            'backup-maintenance'  => 'backup_mgmt'
        ];

        if (isset($pagePermMap[$page])) {
            guardPermission($role, $pagePermMap[$page]);
        }

        // 3. Require the procedural controller if exists
        $controllerName = str_replace('-', '', ucwords($page, '-')) . 'Controller.php';
        $controllerFile = basePath("app/Controllers/{$role}/{$controllerName}");

        if (file_exists($controllerFile)) {
            require $controllerFile;
        }

        // 4. Render View
        $contentView = "pages/{$role}/{$page}";

        // Intercept specific AJAX requests before loading the layout
        if (isset($_GET['ajax_polling']) || ($page === 'patient-registration' && isset($_GET['ajax_search']))) {
            loadView($contentView, get_defined_vars());
            exit;
        }

        // Load layout
        if ($page === 'view-report') {
            loadView($contentView, get_defined_vars());
        } else {
            loadLayoutView($contentView, get_defined_vars());
        }
    }
}
