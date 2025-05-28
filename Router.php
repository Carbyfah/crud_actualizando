<?php

namespace MVC;

use Exception;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $basePath = '';
    private array $namedRoutes = [];

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $originalBasePath = $this->basePath;
        $this->basePath .= '/' . trim($prefix, '/');

        $originalMiddlewares = $this->middlewares;
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        $callback($this);

        $this->basePath = $originalBasePath;
        $this->middlewares = $originalMiddlewares;
    }

    public function get(string $path, $handler, array $middlewares = [], string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares, $name);
    }

    public function post(string $path, $handler, array $middlewares = [], string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares, $name);
    }

    public function put(string $path, $handler, array $middlewares = [], string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares, $name);
    }

    public function delete(string $path, $handler, array $middlewares = [], string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares, $name);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares, ?string $name): void
    {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        $fullPath = rtrim($fullPath, '/') ?: '/';

        $route = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => array_merge($this->middlewares, $middlewares),
            'regex' => $this->pathToRegex($fullPath),
            'params' => []
        ];

        $this->routes[] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
    }

    private function pathToRegex(string $path): string
    {
        $path = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }

    public function resolve(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $matches)) {
                // Extract route parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                try {
                    // Execute middlewares
                    foreach ($route['middlewares'] as $middleware) {
                        if (!$this->executeMiddleware($middleware, $params)) {
                            return;
                        }
                    }

                    // Execute handler
                    $this->executeHandler($route['handler'], $params);
                    return;
                } catch (Exception $e) {
                    $this->handleError($e);
                    return;
                }
            }
        }

        $this->handle404();
    }

    private function executeMiddleware($middleware, array $params): bool
    {
        if (is_callable($middleware)) {
            return $middleware($params) !== false;
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            return $instance->handle($params) !== false;
        }

        return true;
    }

    private function executeHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            $handler($this, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            if (class_exists($controller)) {
                $instance = new $controller();
                $instance->$method($this, $params);
            }
        }
    }

    private function handleError(Exception $e): void
    {
        http_response_code(500);

        if ($_ENV['DEBUG_MODE'] ?? false) {
            echo json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            $this->render('errors/500');
        }
    }

    private function handle404(): void
    {
        http_response_code(404);

        if ($this->isAjax()) {
            echo json_encode(['error' => 'Route not found']);
        } else {
            $this->render('errors/404');
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function render(string $view, array $data = []): void
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        ob_start();
        include_once __DIR__ . "/views/$view.php";
        $contenido = ob_get_clean();
        include_once __DIR__ . '/views/layout.php';
    }

    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
