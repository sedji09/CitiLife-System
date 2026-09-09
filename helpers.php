<?php

date_default_timezone_set('Asia/Manila');

if (!defined('PROJECT_DIR')) {
    $folderName = basename(__DIR__);
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Ignore container/server roots that happen to be named 'app', 'html', 'public', etc.
    $ignoredFolderNames = ['app', 'html', 'public', 'www', 'var', 'srv'];

    if (!in_array(strtolower($folderName), $ignoredFolderNames) && (
        (!empty($scriptName) && stripos($scriptName, '/' . $folderName) === 0) ||
        (!empty($requestUri) && stripos($requestUri, '/' . $folderName) === 0)
    )) {
        define('PROJECT_DIR', $folderName);
    } else {
        define('PROJECT_DIR', '');
    }
}

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

if (!function_exists('url')) {
    /**
     * Generate application URL properly accounting for local XAMPP subfolder or root domain
     *
     * @param string $path
     * @return string
     */
    function url($path = '')
    {
        $path = ltrim($path, '/');
        $base = (defined('PROJECT_DIR') && PROJECT_DIR !== '') ? '/' . PROJECT_DIR : '';
        return $base . '/' . $path;
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
        // Fix any accidentally produced protocol-relative double slashes like //dashboard
        if (strpos($url, '//') === 0 && strpos($url, '://') === false) {
            $url = '/' . ltrim($url, '/');
        }
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

if (!function_exists('getAvatarUrl')) {
    /**
     * Get web URL for an avatar, normalizing any stored path format and checking file existence
     *
     * @param string|null $avatar
     * @return string|null
     */
    function getAvatarUrl($avatar)
    {
        if (empty($avatar)) {
            return null;
        }

        // Clean up query string if present and extract the clean filename (e.g. avatar_66_1788624970.jpg)
        $cleanPath = explode('?', $avatar)[0];
        $filename = basename($cleanPath);
        if (empty($filename) || !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            return null;
        }

        $relPath = 'public/uploads/avatars/' . $filename;
        $fullPath = basePath($relPath);

        // If file doesn't exist on disk, return null so initials avatar can display cleanly
        if (!file_exists($fullPath)) {
            return null;
        }

        $prefix = (defined('PROJECT_DIR') && PROJECT_DIR !== '') ? '/' . trim(PROJECT_DIR, '/') : '';
        $v = filemtime($fullPath);
        return $prefix . '/' . $relPath . '?v=' . $v;
    }
}

if (!function_exists('getSignatureUrl')) {
    /**
     * Get web URL for a signature, normalizing any stored path format and checking file existence
     *
     * @param string|null $signature
     * @return string|null
     */
    function getSignatureUrl($signature)
    {
        if (empty($signature)) {
            return null;
        }

        $cleanPath = explode('?', $signature)[0];
        $filename = basename($cleanPath);
        if (empty($filename) || !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            return null;
        }

        $relPath = 'public/uploads/signatures/' . $filename;
        $fullPath = basePath($relPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $prefix = (defined('PROJECT_DIR') && PROJECT_DIR !== '') ? '/' . trim(PROJECT_DIR, '/') : '';
        $v = filemtime($fullPath);
        return $prefix . '/' . $relPath . '?v=' . $v;
    }
}

if (!function_exists('formatFullName')) {
    /**
     * Format a full name cleanly with optional middle name or middle initial.
     * Supports either positional arguments: formatFullName($first, $middle, $last)
     * or an associative array/record: formatFullName($row)
     *
     * @param string|array|null $firstNameOrData
     * @param string|null|bool $middleName
     * @param string|null $lastName
     * @param bool $middleInitialOnly
     * @return string
     */
    function formatFullName($firstNameOrData, $middleName = '', $lastName = '', $middleInitialOnly = false)
    {
        if (is_array($firstNameOrData)) {
            $fn = trim((string)($firstNameOrData['first_name'] ?? $firstNameOrData['p_first_name'] ?? ''));
            $mn = trim((string)($firstNameOrData['middle_name'] ?? $firstNameOrData['p_middle_name'] ?? ''));
            $ln = trim((string)($firstNameOrData['last_name'] ?? $firstNameOrData['p_last_name'] ?? ''));
            $initialOnly = is_bool($middleName) ? $middleName : false;
        } else {
            $fn = trim((string)($firstNameOrData ?? ''));
            $mn = trim((string)($middleName ?? ''));
            $ln = trim((string)($lastName ?? ''));
            $initialOnly = (bool)$middleInitialOnly;
        }

        $parts = [];
        if ($fn !== '') {
            $parts[] = $fn;
        }
        if ($mn !== '') {
            if ($initialOnly) {
                $parts[] = strtoupper(mb_substr($mn, 0, 1)) . '.';
            } else {
                $parts[] = $mn;
            }
        }
        if ($ln !== '') {
            $parts[] = $ln;
        }

        return implode(' ', $parts);
    }
}




