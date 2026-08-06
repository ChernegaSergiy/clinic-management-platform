<?php

namespace App\Core\Http;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    private array $routes = [];
    private ?ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function add(string $method, string $path, callable|array $handler) : void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request) : Response
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
                    try {
                        if ($this->container && $this->container->has($handler[0])) {
                            $controller = $this->container->get($handler[0]);
                        } else {
                            $controller = new $handler[0]();
                        }
                        $callback = [$controller, $handler[1]];
                    } catch (\Throwable $e) {
                        $detail = $e->getMessage() . "\n\n" . $e->getTraceAsString();
                        if ($this->container && $this->container->has(View::class)) {
                            $view = $this->container->get(View::class);
                            $content = $view->renderToString('errors/error.html.twig', ['message' => 'Internal Server Error', 'detail' => $detail]);
                        } else {
                            $content = "Internal Server Error: " . htmlspecialchars($e->getMessage());
                        }
                        $response = new Response($content, 500);
                        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
                        return $response;
                    }
                } else {
                    $callback = $handler;
                }

                ob_start();
                $result = call_user_func_array($callback, $params);
                $output = ob_get_clean();

                if (is_array($result)) {
                    $response = new Response(json_encode($result));
                    $response->headers->set('Content-Type', 'application/json');
                } else {
                    $content = $output ?: $result;
                    $response = new Response($content);
                    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
                }
                return $response;
            }
        }

        if ($this->container && $this->container->has(View::class)) {
            $view = $this->container->get(View::class);
            $content = $view->renderToString('errors/error.html.twig', ['message' => '404 Not Found']);
        } else {
            $content = "404 Not Found";
        }
        $response = new Response($content, 404);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }
}
