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

if (!function_exists('generateReportToken')) {
    /**
     * Generate an HMAC signed base64url token for a case ID to prevent IDOR and URL tampering
     *
     * @param int|string $caseId
     * @return string
     */
    function generateReportToken($caseId)
    {
        $caseId = (int) $caseId;
        $secretKey = function_exists('getAppSecret') ? getAppSecret() : (getenv('APP_SECRET') ?: 'CitiLife_Secure_HMAC_Secret_Token_2026');
        $sig = substr(hash_hmac('sha256', (string) $caseId, $secretKey), 0, 12);
        return rtrim(strtr(base64_encode($caseId . ':' . $sig), '+/', '-_'), '=');
    }
}

if (!function_exists('verifyReportToken')) {
    /**
     * Verify an HMAC signed token and extract the case ID
     *
     * @param string $token
     * @return int 0 if invalid, case ID if valid
     */
    function verifyReportToken($token)
    {
        if (empty($token) || !is_string($token)) return 0;
        $decoded = base64_decode(strtr($token, '-_', '+/'));
        if (!$decoded || strpos($decoded, ':') === false) return 0;
        list($caseId, $sig) = explode(':', $decoded, 2);
        $secretKey = function_exists('getAppSecret') ? getAppSecret() : (getenv('APP_SECRET') ?: 'CitiLife_Secure_HMAC_Secret_Token_2026');
        $expectedSig = substr(hash_hmac('sha256', (string) $caseId, $secretKey), 0, 12);
        if (hash_equals($expectedSig, $sig)) {
            return (int) $caseId;
        }
        return 0;
    }
}

if (!function_exists('getAppSecret')) {
    /**
     * Get the application encryption & HMAC secret key from environment or default fallback
     * 
     * @return string
     */
    function getAppSecret()
    {
        $secret = getenv('APP_SECRET') ?: ($_ENV['APP_SECRET'] ?? ($_SERVER['APP_SECRET'] ?? ''));
        if (!empty($secret)) {
            return $secret;
        }
        return 'CitiLife_Secure_HMAC_Secret_Token_2026';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get or initialize the CSRF token for the active session
     * 
     * @return string
     */
    function csrf_token()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            try {
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            } catch (\Throwable $e) {
                $_SESSION['_csrf_token'] = md5(uniqid((string)mt_rand(), true) . microtime(true));
            }
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate HTML hidden input tag for CSRF protection
     * 
     * @return string
     */
    function csrf_field()
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Verify CSRF token from POST, JSON payload, or Request Header against session token
     * 
     * @param string|null $token
     * @return bool
     */
    function verify_csrf_token($token = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if ($token === null) {
            // Check POST parameters
            $token = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? null;

            // Check Request Headers
            if (empty($token)) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? null);
            }

            if (empty($token) && function_exists('getallheaders')) {
                $headers = getallheaders();
                $token = $headers['X-CSRF-TOKEN'] ?? ($headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? null));
            }

            // Check JSON body if applicable
            if (empty($token)) {
                $rawInput = @file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $json = @json_decode($rawInput, true);
                    if (is_array($json) && !empty($json['_csrf_token'])) {
                        $token = $json['_csrf_token'];
                    } elseif (is_array($json) && !empty($json['csrf_token'])) {
                        $token = $json['csrf_token'];
                    }
                }
            }
        }

        if (empty($token) || !is_string($token)) {
            return false;
        }

        $sessionToken = csrf_token();
        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('getClientIp')) {
    /**
     * Get real client IP address with proxy support
     * 
     * @return string
     */
    function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($list[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('ensureRateLimitsTable')) {
    /**
     * Ensure rate_limits table exists in database
     * 
     * @param PDO $pdo
     * @return void
     */
    function ensureRateLimitsTable($pdo)
    {
        static $ensured = false;
        if ($ensured || !($pdo instanceof \PDO)) return;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    rate_key VARCHAR(191) NOT NULL UNIQUE,
                    attempts INT NOT NULL DEFAULT 1,
                    locked_until DATETIME DEFAULT NULL,
                    last_attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_rate_key (rate_key),
                    INDEX idx_locked_until (locked_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $ensured = true;
        } catch (\Throwable $e) {
            // Silently fail if permissions are restricted
        }
    }
}

if (!function_exists('checkRateLimit')) {
    /**
     * Check if an action by an identifier (IP / Email) is currently rate limited
     * 
     * @param string $action
     * @param string $identifier
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return array ['allowed' => bool, 'remaining_seconds' => int, 'attempts' => int]
     */
    function checkRateLimit($action, $identifier, $maxAttempts = 5, $decaySeconds = 900)
    {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof \PDO)) {
            return ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 0];
        }

        ensureRateLimitsTable($pdo);
        $rateKey = hash('sha256', strtolower(trim($action)) . '|' . strtolower(trim($identifier)));

        try {
            // Clean up records older than decay period
            $cleanupStmt = $pdo->prepare("
                DELETE FROM rate_limits 
                WHERE rate_key = ? 
                  AND (locked_until IS NULL OR locked_until <= NOW()) 
                  AND last_attempt_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $cleanupStmt->execute([$rateKey, $decaySeconds]);

            $stmt = $pdo->prepare("SELECT attempts, locked_until, UNIX_TIMESTAMP(locked_until) as locked_ts FROM rate_limits WHERE rate_key = ? LIMIT 1");
            $stmt->execute([$rateKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $now = time();
                $lockedTs = $row['locked_ts'] ? intval($row['locked_ts']) : 0;
                if ($lockedTs > $now) {
                    return [
                        'allowed' => false,
                        'remaining_seconds' => $lockedTs - $now,
                        'attempts' => intval($row['attempts'])
                    ];
                }
                return [
                    'allowed' => true,
                    'remaining_seconds' => 0,
                    'attempts' => intval($row['attempts'])
                ];
            }
        } catch (\Throwable $e) {}

        return ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 0];
    }
}

if (!function_exists('recordFailedAttempt')) {
    /**
     * Record a failed attempt for an action & identifier and trigger lockout if limit reached
     * 
     * @param string $action
     * @param string $identifier
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return array
     */
    function recordFailedAttempt($action, $identifier, $maxAttempts = 5, $decaySeconds = 900)
    {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof \PDO)) {
            return ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 1];
        }

        ensureRateLimitsTable($pdo);
        $rateKey = hash('sha256', strtolower(trim($action)) . '|' . strtolower(trim($identifier)));

        try {
            $stmt = $pdo->prepare("SELECT attempts, locked_until, UNIX_TIMESTAMP(locked_until) as locked_ts FROM rate_limits WHERE rate_key = ? LIMIT 1");
            $stmt->execute([$rateKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $now = time();
            if ($row) {
                $attempts = intval($row['attempts']) + 1;
                $lockedUntil = null;
                $remainingSeconds = 0;

                if ($attempts >= $maxAttempts) {
                    $lockedTs = $now + $decaySeconds;
                    $lockedUntil = date('Y-m-d H:i:s', $lockedTs);
                    $remainingSeconds = $decaySeconds;
                }

                $updateStmt = $pdo->prepare("
                    UPDATE rate_limits 
                    SET attempts = ?, 
                        locked_until = CASE WHEN ? IS NOT NULL THEN ? ELSE locked_until END, 
                        last_attempt_at = NOW() 
                    WHERE rate_key = ?
                ");
                $updateStmt->execute([$attempts, $lockedUntil, $lockedUntil, $rateKey]);

                return [
                    'allowed' => $attempts < $maxAttempts,
                    'remaining_seconds' => $remainingSeconds,
                    'attempts' => $attempts
                ];
            } else {
                $attempts = 1;
                $lockedUntil = ($attempts >= $maxAttempts) ? date('Y-m-d H:i:s', $now + $decaySeconds) : null;
                $insertStmt = $pdo->prepare("
                    INSERT INTO rate_limits (rate_key, attempts, locked_until, last_attempt_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $insertStmt->execute([$rateKey, $attempts, $lockedUntil]);

                return [
                    'allowed' => $attempts < $maxAttempts,
                    'remaining_seconds' => ($attempts >= $maxAttempts) ? $decaySeconds : 0,
                    'attempts' => $attempts
                ];
            }
        } catch (\Throwable $e) {}

        return ['allowed' => true, 'remaining_seconds' => 0, 'attempts' => 1];
    }
}

if (!function_exists('clearRateLimit')) {
    /**
     * Clear rate limit records upon successful action
     * 
     * @param string $action
     * @param string $identifier
     * @return void
     */
    function clearRateLimit($action, $identifier)
    {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof \PDO)) return;

        try {
            $rateKey = hash('sha256', strtolower(trim($action)) . '|' . strtolower(trim($identifier)));
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE rate_key = ?");
            $stmt->execute([$rateKey]);
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('validatePasswordPolicy')) {
    /**
     * Validate password against system security policy
     * 
     * @param string $password
     * @param int|null $minLen
     * @return array ['valid' => bool, 'error' => string, 'min_length' => int]
     */
    function validatePasswordPolicy($password, $minLen = null)
    {
        if ($minLen === null || $minLen <= 0) {
            $minLen = intval(getSystemSetting('min_password_length', 8));
            if ($minLen <= 0) $minLen = 8;
        }

        if (strlen($password) < $minLen) {
            return [
                'valid' => false,
                'error' => "Password must be at least {$minLen} characters long.",
                'min_length' => $minLen
            ];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'valid' => false,
                'error' => "Password must contain at least one uppercase letter (A-Z).",
                'min_length' => $minLen
            ];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'error' => "Password must contain at least one number (0-9).",
                'min_length' => $minLen
            ];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return [
                'valid' => false,
                'error' => "Password must contain at least one special character (e.g. !@#$%^&*).",
                'min_length' => $minLen
            ];
        }

        return [
            'valid' => true,
            'error' => '',
            'min_length' => $minLen
        ];
    }
}




