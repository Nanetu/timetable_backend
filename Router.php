<?php
class Router {
    private $routes = [];
    private $basePath = '';
    
    public function setBasePath($basePath) {
        $this->basePath = rtrim($basePath, '/');
    }
    
    public function addRoute($method, $path, $controller, $action) {
        // Normalize the path
        if (empty($path)) {
            $normalizedPath = '/';
        } else {
            $normalizedPath = '/' . ltrim($path, '/');
        }
        
        $this->routes[] = [
            'method' => $method,
            'path' => $normalizedPath,
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $fullPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove the base path from the request path to get the route path
        $routePath = $fullPath;
        if (!empty($this->basePath) && strpos($fullPath, $this->basePath) === 0) {
            $routePath = substr($fullPath, strlen($this->basePath));
        }
        
        // Normalize the route path
        if (empty($routePath) || $routePath === '/') {
            $routePath = '/';
        } else {
            $routePath = '/' . trim($routePath, '/');
        }
        
        // Debug: Let's see what we're working with
        error_log("Request Method: " . $method);
        error_log("Full Path: " . $fullPath);
        error_log("Base Path: " . $this->basePath);
        error_log("Route Path: " . $routePath);
        error_log("Available routes: " . print_r($this->routes, true));
        
        foreach ($this->routes as $route) {
            $expectedPath = $route['path'];
            
            // Normalize expected path
            if (empty($expectedPath) || $expectedPath === '/') {
                $expectedPath = '/';
            } else {
                $expectedPath = '/' . trim($expectedPath, '/');
            }
            
            error_log("Comparing route path: " . $expectedPath . " with request path: " . $routePath);
            
            if ($route['method'] === $method && $expectedPath === $routePath) {
                if (class_exists($route['controller'])) {
                    $controller = new $route['controller']();
                    $action = $route['action'];
                    if (method_exists($controller, $action)) {
                        $controller->$action();
                        return;
                    } else {
                        error_log("Method {$action} not found in {$route['controller']}");
                    }
                } else {
                    error_log("Controller {$route['controller']} not found");
                }
                return;
            }
        }
        
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found', 
            'requested_path' => $fullPath, 
            'route_path' => $routePath,
            'method' => $method,
            'base_path' => $this->basePath,
            'available_routes' => array_map(function($route) {
                return $route['method'] . ' ' . $route['path'];
            }, $this->routes)
        ]);
    }
}
?>