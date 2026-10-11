<?php
/**
 * AuthHelper.php
 * Provides centralized role and permission validation.
 */

if (!function_exists('hasPermission')) {
    /**
     * Checks if a given role has access to a specific permission.
     * 
     * @param string $role The user role (e.g., 'radtech')
     * @param string $permKey The permission key (e.g., 'patient_reg')
     * @return int Access Level (0: None, 1: Full, 2: Branch Restricted)
     */
    function hasPermission($role, $permKey) {
        global $pdo;
        
        // Superadmin bypass: admin_central and it_admin have full permission
        if ($role === 'admin_central' || $role === 'it_admin') {
            return 1;
        }

        // Patient role access for feedback
        if ($role === 'patient' && ($permKey === 'patient_feedback' || $permKey === 'feedback')) {
            return 1;
        }

        static $permCache = [];
        $cacheKey = $role . '_' . $permKey;
        
        if (isset($permCache[$cacheKey])) {
            return $permCache[$cacheKey];
        }

        // Native default permissions per role if missing in role_permissions table
        $defaultNativePerms = [
            'branch_admin' => [
                'dashboard' => 1,
                'record_requests' => 2,
                'approvals' => 2,
                'patient_history' => 2,
                'patient_feedback' => 2,
                'feedback' => 2,
                'audit_logs' => 2,
                'global_reports' => 2,
            ],
            'radtech' => [
                'dashboard' => 1,
                'patient_reg' => 2,
                'worklist' => 2,
                'patient_history' => 2,
                'submit_record_request' => 2,
            ],
            'radiologist' => [
                'dashboard' => 1,
                'worklist' => 1,
                'case_review' => 1,
                'write_report' => 1,
                'patient_history' => 2,
            ],
            'patient' => [
                'dashboard' => 1,
                'patient_reg' => 1,
                'my_records' => 1,
                'patient_feedback' => 1,
                'feedback' => 1,
            ]
        ];

        try {
            $stmt = $pdo->prepare("SELECT access_level FROM role_permissions WHERE role = ? AND perm_key = ? LIMIT 1");
            $stmt->execute([$role, $permKey]);
            $level = $stmt->fetchColumn();
            
            if ($level !== false) {
                // If explicitly set in DB:
                // But for branch_admin on patient_feedback, if DB has 0 (due to uninitialized state), ensure at least branch access 2
                if ($role === 'branch_admin' && ($permKey === 'patient_feedback' || $permKey === 'feedback') && intval($level) === 0) {
                    $level = 2;
                }
                $permCache[$cacheKey] = intval($level);
            } else {
                $defaultLevel = $defaultNativePerms[$role][$permKey] ?? 0;
                $permCache[$cacheKey] = $defaultLevel;

                // Auto-seed missing default permission into database if available
                if ($defaultLevel > 0) {
                    try {
                        $insertStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role, perm_key, access_level) VALUES (?, ?, ?)");
                        $insertStmt->execute([$role, $permKey, $defaultLevel]);
                    } catch (\Exception $ignored) {}
                }
            }
            return $permCache[$cacheKey];
        } catch (Exception $e) {
            return $defaultNativePerms[$role][$permKey] ?? 0;
        }
    }
}

if (!function_exists('guardPermission')) {
    /**
     * Terminate execution if user doesn't have permission.
     */
    function guardPermission($role, $permKey, $redirect = true) {
        if (hasPermission($role, $permKey) === 0) {
            $router = new \Framework\Router();
            $router->error(403);
        }
        return true;
    }
}
