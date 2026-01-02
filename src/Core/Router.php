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
            // Convert route path to a regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];

                if (is_array($handler) && is_string($handler[0])) {
                    $controller = new $handler[0]();
                    $callback = [$controller, $handler[1]];
                } else {
                    $callback = $handler;
                }

                call_user_func_array($callback, $params);
                return;
            }
        }

        // Handle 404
        http_response_code(404);
        View::render('errors/error.html.twig', ['message' => '404 Not Found']);
    }
}
