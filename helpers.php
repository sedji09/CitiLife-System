<?php

date_default_timezone_set('Asia/Manila');

if (!function_exists('basePath')) {
    /**
     * Get base path of the project
     * 
     * @param string $path
     * @return string
     */
    function basePath($path = '')
    {
        return __DIR__ . '/' . $path;
    }
}

if (!function_exists('loadView')) {
    /**
     * Load a view directly (e.g., login, errors)
     * 
     * @param string $name
     * @param array $data
     * @return void
     */
    function loadView($name, $data = [])
    {
        $viewPath = basePath("views/{$name}.view.php");

        if (file_exists($viewPath)) {
            if (isset($data['viewPath'])) {
                unset($data['viewPath']);
            }
            extract($data);
            require $viewPath;
        } else {
            echo "View '{$name}' not found at: {$viewPath}";
        }
    }
}

if (!function_exists('loadLayoutView')) {
    /**
     * Load a view embedded in the central dashboard layout
     * 
     * @param string $name
     * @param array $data
     * @return void
     */
    function loadLayoutView($name, $data = [])
    {
        $_originalName = $name;
        $contentView = basePath("views/{$name}.view.php");

        if (isset($data['contentView'])) {
            unset($data['contentView']);
        }
        extract($data);

        if (!file_exists($contentView)) {
            echo "View '{$_originalName}' not found at: {$contentView}";
            return;
        }

        // Set globally shared layout variables
        global $pdo, $role, $userId, $userEmail, $branchId, $branchNameDisplay;
        global $userDisplayName, $initials, $userAvatar, $currentUser;
        global $userSignature, $userProfessionalTitle, $userFullNameReport;

        // Standard variable bootstrap from session
        $role = $_SESSION['role'] ?? 'radtech';
        $userEmail = $_SESSION['email'] ?? 'user@example.com';
        $userId = $_SESSION['user_id'] ?? 0;
        $branchId = $_SESSION['branch_id'] ?? null;

        // Load helpers needed by the dashboard
        require_once basePath('app/Helpers/AuthHelper.php');

        // Require the central dashboard layout which pulls in $contentView dynamically
        require basePath('views/layouts/dashboard.php');
    }
}

if (!function_exists('loadPartial')) {
    /**
     * Load a view partial (e.g. navbar, sidebar)
     * 
     * @param string $name
     * @param array $data
     * @return void
     */
    function loadPartial($name, $data = [])
    {
        $partialPath = basePath("views/partials/{$name}.php");

        if (file_exists($partialPath)) {
            if (isset($data['partialPath'])) {
                unset($data['partialPath']);
            }
            extract($data);
            require $partialPath;
        } else {
            echo "Partial '{$name}' not found at: {$partialPath}";
        }
    }
}

if (!function_exists('inspect')) {
    /**
     * Inspect a value for debugging
     * 
     * @param mixed $value
     * @return void
     */
    function inspect($value)
    {
        echo '<pre class="bg-gray-100 p-4 border rounded font-mono text-xs">';
        var_dump($value);
        echo '</pre>';
    }
}

if (!function_exists('inspectAndDie')) {
    /**
     * Inspect a value and terminate execution
     * 
     * @param mixed $value
     * @return void
     */
    function inspectAndDie($value)
    {
        echo '<pre class="bg-gray-100 p-4 border rounded font-mono text-xs">';
        var_dump($value);
        echo '</pre>';
        die();
    }
}

if (!function_exists('redirect')) {
    /**
     * Clean HTTP redirect helper
     * 
     * @param string $url
     * @return void
     */
    function redirect($url)
    {
        header("Location: " . $url);
        exit();
    }
}

if (!function_exists('appBaseUrl')) {
    /**
     * Get base URL of application with protocol and host
     * 
     * @return string
     */
    function appBaseUrl()
    {
        if (!empty($_ENV['APP_URL'])) return rtrim($_ENV['APP_URL'], '/');
        if (!empty(getenv('APP_URL'))) return rtrim(getenv('APP_URL'), '/');
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($protocol . $host, '/');
    }
}

if (!function_exists('getSystemSetting')) {
    /**
     * Get a setting value from system_settings table with memory caching
     *
     * @param string $key
     * @param mixed $default
     * @param bool $refresh
     * @return mixed
     */
    function getSystemSetting($key, $default = '', $refresh = false)
    {
        static $settingsCache = null;
        global $pdo;

        if ($refresh || $settingsCache === null) {
            $settingsCache = [];
            if (isset($pdo) && $pdo instanceof \PDO) {
                try {
                    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                    if ($stmt) {
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $settingsCache[$row['setting_key']] = $row['setting_value'];
                        }
                    }
                } catch (\Throwable $e) {
                    // Fallback to individual query if mass fetch fails
                }
            }
        }

        if (array_key_exists($key, $settingsCache) && $settingsCache[$key] !== null && $settingsCache[$key] !== '') {
            return $settingsCache[$key];
        }

        // If not found in cache and pdo is available, try a direct query
        if (isset($pdo) && $pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
                $stmt->execute([$key]);
                $val = $stmt->fetchColumn();
                if ($val !== false && $val !== null && $val !== '') {
                    $settingsCache[$key] = $val;
                    return $val;
                }
            } catch (\Throwable $e) {}
        }

        return $default;
    }
}

if (!function_exists('getSystemName')) {
    /**
     * Get the configured brand/system display name
     *
     * @param string $default
     * @return string
     */
    function getSystemName($default = 'CitiLife Diagnostic Center')
    {
        return getSystemSetting('system_name', $default);
    }
}

if (!function_exists('getSystemLogo')) {
    /**
     * Get relative path from project root to active clinic logo
     *
     * @param string $default
     * @return string
     */
    function getSystemLogo($default = 'public/assets/img/logo/citilife-logo.png')
    {
        $logo = getSystemSetting('clinic_logo', $default);
        if (!empty($logo) && file_exists(basePath($logo))) {
            return $logo;
        }
        return $default;
    }
}

if (!function_exists('getSystemLogoUrl')) {
    /**
     * Get web URL (relative or absolute) to the active clinic logo with cache busting
     *
     * @param bool $absolute
     * @return string
     */
    function getSystemLogoUrl($absolute = false)
    {
        $relPath = getSystemLogo();
        $fullPath = basePath($relPath);
        $v = file_exists($fullPath) ? filemtime($fullPath) : time();
        $query = '?v=' . $v;

        if ($absolute) {
            $appUrl = getenv('APP_URL') ?: ($_SERVER['APP_URL'] ?? '');
            if (empty($appUrl)) {
                $appUrl = appBaseUrl();
            }
            $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            if (defined('PROJECT_DIR') && PROJECT_DIR && $isLocal && strpos($appUrl, PROJECT_DIR) === false) {
                return rtrim($appUrl, '/') . '/' . PROJECT_DIR . '/' . ltrim($relPath, '/') . $query;
            }
            return rtrim($appUrl, '/') . '/' . ltrim($relPath, '/') . $query;
        }

        if (defined('PROJECT_DIR') && PROJECT_DIR) {
            return '/' . PROJECT_DIR . '/' . ltrim($relPath, '/') . $query;
        }
        return '/' . ltrim($relPath, '/') . $query;
    }
}


