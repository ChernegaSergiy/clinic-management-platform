<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                $handler = $route['handler'];

                // Якщо передано [ClassName, method], створюємо екземпляр контролера
                if (is_array($handler) && is_string($handler[0])) {
                    $controller = new $handler[0]();
                    $callback = [$controller, $handler[1]];
                } else {
                    $callback = $handler;
                }

                call_user_func($callback);
                return;
            }
        }

        // Handle 404
        http_response_code(404);
        echo "404 Not Found"; // Поки що просто текст
    }
}
