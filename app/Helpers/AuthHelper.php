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
        
        static $permCache = [];
        $cacheKey = $role . '_' . $permKey;
        
        if (isset($permCache[$cacheKey])) {
            return $permCache[$cacheKey];
        }

        try {
            $stmt = $pdo->prepare("SELECT access_level FROM role_permissions WHERE role = ? AND perm_key = ? LIMIT 1");
            $stmt->execute([$role, $permKey]);
            $level = $stmt->fetchColumn();
            
            $permCache[$cacheKey] = ($level !== false) ? intval($level) : 0;
            return $permCache[$cacheKey];
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('guardPermission')) {
    /**
     * Terminate execution if user doesn't have permission.
     */
    function guardPermission($role, $permKey, $redirect = true) {
        if (hasPermission($role, $permKey) === 0) {
            if ($redirect) {
                header("Location: /" . PROJECT_DIR . "/?page=dashboard&error=unauthorized");
                exit();
            }
            return false;
        }
        return true;
    }
}
