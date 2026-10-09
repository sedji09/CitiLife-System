<?php

namespace Framework;

use Exception;
use Framework\middleware\Authorize;

class Router
{
    private $routes = [];

    /**
     * Helper to register a route
     */
    private function registerRoute($method, $uri, $action, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    public function get($uri, $action, $middleware = [])
    {
        $this->registerRoute('GET', $uri, $action, $middleware);
    }

    public function post($uri, $action, $middleware = [])
    {
        $this->registerRoute('POST', $uri, $action, $middleware);
    }

    public function put($uri, $action, $middleware = [])
    {
        $this->registerRoute('PUT', $uri, $action, $middleware);
    }

    public function delete($uri, $action, $middleware = [])
    {
        $this->registerRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Dispatch the route matching the current request
     */
    public function route($uri, $method)
    {
        // Normalize any protocol-relative double slashes e.g. //reset-password?token=... -> /reset-password?token=...
        if (strpos($uri, '//') === 0 && strpos($uri, '://') === false) {
            $uri = '/' . ltrim($uri, '/');
        }

        // Parse the URL to get the path
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === null || $path === false) {
            $path = '/';
        }

        // Strip project root prefix if it is present (case-insensitive for compatibility)
        if (defined('PROJECT_DIR') && PROJECT_DIR !== '') {
            $projectPrefix = '/' . PROJECT_DIR;
            if (stripos($path, $projectPrefix) === 0) {
                $path = substr($path, strlen($projectPrefix));
            }
        }

        // Fallback: strip leading /citilife-system or /citilife_system if present regardless of PROJECT_DIR (crucial for Railway/subfolder compatibility)
        if (preg_match('#^/citilife[-_]system(?:/|$)#i', $path)) {
            $path = preg_replace('#^/citilife[-_]system#i', '', $path);
        }

        // Standardize leading and trailing slash
        $path = '/' . trim((string)$path, '/');

        foreach ($this->routes as $route) {
            // Normalize route uri
            $routeUri = '/' . trim((string)($route['uri'] ?? ''), '/');

            if (strcasecmp($routeUri, $path) === 0 && $route['method'] === $method) {
                // Verify CSRF Token on all state-modifying HTTP methods (POST, PUT, DELETE, PATCH)
                if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                    $exemptPaths = [
                        '/system-health', 
                        '/test-email', 
                        '/test-env',
                        '/api/cron/backup',
                        '/api/cron-backup',
                        '/app/api/cron_backup.php',
                        '/app/Api/cron_backup.php'
                    ];
                    if (!in_array($path, $exemptPaths, true)) {
                        if (function_exists('verify_csrf_token') && !verify_csrf_token()) {
                            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                                || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
                                || (strpos($path, '/app/api/') !== false)
                                || (strpos($path, '/app/Api/') !== false);

                            if ($isAjax) {
                                header('Content-Type: application/json');
                                http_response_code(403);
                                echo json_encode([
                                    'success' => false,
                                    'status' => 'csrf_error',
                                    'message' => 'Security token invalid or expired. Please refresh the page.'
                                ]);
                                exit;
                            }

                            if ($path === '/patient-signup' || $path === '/patient-signup.php') {
                                $iframeParam = isset($_GET['iframe']) ? '&iframe=1' : '';
                                header('Location: ' . url('patient-signup?error=' . urlencode('Security session expired. Please submit again.') . $iframeParam));
                                exit;
                            }

                            if ($path === '/patient-login' || $path === '/login' || $path === '/staff-portal') {
                                header('Location: ' . url('?login=1&error=' . urlencode('Security session expired. Please try again.')));
                                exit;
                            }

                            $this->error(403);
                            return;
                        }
                    }
                }

                // Execute middleware first
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                $action = $route['action'];

                // Handle closure or callable
                if (is_callable($action)) {
                    call_user_func($action);
                    return;
                }

                // Handle class string mapping e.g., 'App\Controllers\HomeController@index'
                if (is_string($action) && strpos($action, '@') !== false) {
                    list($controllerClass, $methodName) = explode('@', $action);
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            $controller->$methodName();
                            return;
                        }
                    }
                    throw new Exception("Method '{$methodName}' on controller class '{$controllerClass}' not found.");
                }

                // Handle controller array syntax e.g., [HomeController::class, 'index']
                if (is_array($action)) {
                    list($controllerClass, $methodName) = $action;
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            $controller->$methodName();
                            return;
                        }
                    }
                    throw new Exception("Method '{$methodName}' on controller class '{$controllerClass}' not found.");
                }

                // Fallback for procedural file path routing
                if (is_string($action)) {
                    $filePath = basePath($action);
                    if (file_exists($filePath)) {
                        require $filePath;
                        return;
                    }
                }
            }
        }

        // No match found
        $this->error(404);
    }

    /**
     * Check if middleware permits request to continue
     */
    private function runMiddleware($name)
    {
        (new Authorize())->handle($name);
    }

    /**
     * Load HTTP error page
     */
    public function error($httpCode = 404)
    {
        http_response_code($httpCode);
        if (file_exists(basePath("views/errors/{$httpCode}.view.php"))) {
            loadView("errors/{$httpCode}");
        } else {
            loadView("errors/404");
        }
        exit;
    }
}

