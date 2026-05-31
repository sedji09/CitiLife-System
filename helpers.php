<?php

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
        // Extract dynamic page variables first so they don't overwrite $contentView
        extract($data);

        $contentView = basePath("views/{$name}.view.php");

        if (!file_exists($contentView)) {
            echo "View '{$name}' not found at: {$contentView}";
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
