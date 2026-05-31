<?php

namespace Framework;

use Exception;

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
        // Parse the URL to get the path
        $path = parse_url($uri, PHP_URL_PATH);

        // Strip project root prefix if it is present
        $projectPrefix = '/' . PROJECT_DIR;
        if (strpos($path, $projectPrefix) === 0) {
            $path = substr($path, strlen($projectPrefix));
        }

        // Standardize leading and trailing slash
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            // Normalize route uri
            $routeUri = '/' . trim($route['uri'], '/');

            if ($routeUri === $path && $route['method'] === $method) {
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($name === 'auth') {
            if (!isset($_SESSION['role'])) {
                header("Location: /" . PROJECT_DIR . "/login");
                exit;
            }
        }

        if ($name === 'guest') {
            if (isset($_SESSION['role'])) {
                header("Location: /" . PROJECT_DIR . "/dashboard");
                exit;
            }
        }
    }

    /**
     * Load HTTP error page
     */
    public function error($httpCode = 404)
    {
        http_response_code($httpCode);
        loadView("errors/{$httpCode}");
        exit;
    }
}
