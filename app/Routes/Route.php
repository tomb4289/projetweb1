<?php
namespace App\Routes;

class Route {
    private static $routes = [];

    public static function get($uri, $callback) {
        self::$routes['GET'][$uri] = $callback;
    }

    public static function post($uri, $callback) {
        self::$routes['POST'][$uri] = $callback;
    }

    public static function dispatch($pdo, $twig, $config, $uri, $method) {
        if (isset(self::$routes[$method][$uri])) {
            $callback = self::$routes[$method][$uri];

            $controllerClass = $callback[0];
            $methodName = $callback[1];

            $controller = new $controllerClass($pdo, $twig, $config);

            $arguments = [];
            if ($method === 'POST') {
                $arguments = $_POST;
            }

            $controller->$methodName($arguments);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }
}
