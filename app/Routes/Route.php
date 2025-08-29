<?php
namespace App\routes;

class Route {
    private static $routes = [];

    public static function get($uri, $callback) {
        self::$routes['GET'][$uri] = $callback;
    }

    public static function post($uri, $callback) {
        self::$routes['POST'][$uri] = $callback;
    }

    public static function put($uri, $callback) {
        self::$routes['PUT'][$uri] = $callback;
    }

    public static function delete($uri, $callback) {
        self::$routes['DELETE'][$uri] = $callback;
    }

    public static function dispatch($pdo, $twig, $config, $uri, $method) {
        
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        if ($config['app']['debug'] && !str_starts_with($uri, '/comments/')) {
            echo "<!-- Debug: Available routes for $method: " . implode(', ', array_keys(self::$routes[$method] ?? [])) . " -->\n";
        }

        if (isset(self::$routes[$method][$uri])) {
            $callback = self::$routes[$method][$uri];
            self::executeCallback($callback, $pdo, $twig, $config, []);
            return;
        }

        foreach (self::$routes[$method] ?? [] as $route => $callback) {
            $pattern = self::convertRouteToPattern($route);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                self::executeCallback($callback, $pdo, $twig, $config, $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
        if ($config['app']['debug'] && !str_starts_with($uri, '/comments/')) {
            echo "<br>URI: $uri<br>Method: $method<br>Available routes: " . implode(', ', array_keys(self::$routes[$method] ?? []));
        }
    }

    private static function convertRouteToPattern($route) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^\/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    private static function executeCallback($callback, $pdo, $twig, $config, $arguments) {
        $controllerClass = $callback[0];
        $methodName = $callback[1];

        $controller = new $controllerClass($pdo, $twig, $config);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                $controller->$methodName(...$arguments);
            } else {
                
                if (method_exists($controller, $methodName)) {
                    $reflection = new \ReflectionMethod($controller, $methodName);
                    $params = $reflection->getParameters();
                    
                    if (!empty($params) && !empty($_POST)) {
                        $controller->$methodName($_POST);
                    } else {
                        $controller->$methodName(...$arguments);
                    }
                } else {
                    $controller->$methodName(...$arguments);
                }
            }
        } else {
            $controller->$methodName(...$arguments);
        }
    }
}
