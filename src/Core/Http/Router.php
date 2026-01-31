<?php

namespace App\Core\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        foreach ($this->routes as $route) {
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

                ob_start();
                call_user_func_array($callback, $params);
                $content = ob_get_clean();

                return new Response($content);
            }
        }

        $content = View::render('errors/error.html.twig', ['message' => '404 Not Found']);
        return new Response($content, 404);
    }
}
