<?php
namespace App\Core;

use App\Controllers\ErrorController;

class Router {
    protected $routes = [];

    public function __construct($routes = [], AsyncLogger $logger = null) {
        foreach ($routes as $method => $paths) {
            foreach ($paths as $path => $handler) {
                $regex = $this->convertToRegex($path);
                $this->routes[strtoupper($method)][$regex] = $handler;
            }
        }
        $this->logger = $logger;
    }

    private function convertToRegex($path) {
        $patterns = [
            // Các placeholder khác giữ nguyên
            ':num' => '([0-9]+)',
            ':any' => '([^/]+)',
            ':alpha' => '([a-zA-Z]+)',
            ':alnum' => '([a-zA-Z0-9]+)',
            ':slug' => '([a-zA-Z0-9_-]+)',
        ];
        // Thực hiện thay thế
        foreach ($patterns as $key => $value) {
            $path = str_replace($key, $value, $path);
        }
        // Hỗ trợ thêm cho các regex tùy chỉnh trong ngoặc nhọn
        $path = preg_replace_callback('/\{(\w+):([^}]+)\}/', function ($matches) {
            return '(?<' . $matches[1] . '>' . $matches[2] . ')';
        }, $path);
        return '#^' . $path . '$#';
    }
    

    public function dispatch($httpMethod, $uri) {
        $method = strtoupper($httpMethod);
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri = rtrim($uri, '/') ?: '/';

        try {
            foreach ($this->routes[$method] as $pattern => $handler) {
                if (preg_match($pattern, $uri, $matches)) {
                    [$controllerName, $methodName] = $handler;
                    $controller = new $controllerName();
                    $params = array_slice($matches, 1); // Extract parameters
                    if (method_exists($controller, $methodName)) {
                        return call_user_func_array([$controller, $methodName], $params);
                    } else {
                        throw new \Exception("Method {$methodName} not found in controller {$controllerName}.");
                    }
                }
            }
            
            // Nếu không tìm thấy route, thử xử lý động
            foreach ($this->routes[$method] as $pattern => $handler) {
                [$controllerName, $defaultMethod] = $handler;
                $controller = new $controllerName();
                if (strpos($uri, '/') === 0) {
                    $uri = substr($uri, 1);
                }
                $segments = explode('/', $uri);
                $possibleMethod = end($segments);

                if (method_exists($controller, $possibleMethod)) {
                    return call_user_func_array([$controller, $possibleMethod], []);
                }
            }

            throw new \Exception("Route not found for {$uri}.");
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            $errorController = new ErrorController();
            return $errorController->show404();
        }
    }
}
