<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $roles = []): void
    {
        $this->add('GET', $path, $handler, $roles);
    }

    public function post(string $path, array $handler, array $roles = []): void
    {
        $this->add('POST', $path, $handler, $roles);
    }

    public function add(string $method, string $path, array $handler, array $roles = []): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'roles');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
        $basePath = trim((string) parse_url((string) env('APP_URL', ''), PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with(trim($path, '/'), $basePath)) {
            $path = '/' . trim(substr(trim($path, '/'), strlen($basePath)), '/');
        }
        if ($path !== '/' && str_contains($path, '/index.php')) {
            $path = preg_replace('#^.*?/index\.php#', '', $path) ?: '/';
        }

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $path);
            if ($route['method'] === $method && $params !== null) {
                if ($route['roles']) {
                    Auth::requireRole($route['roles']);
                }
                [$class, $action] = $route['handler'];
                (new $class())->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        (new \App\Controllers\ErrorController())->notFound();
    }

    private function match(string $routePath, string $actualPath): ?array
    {
        $keys = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $m) use (&$keys): string {
            $keys[] = $m[1];
            return '([^/]+)';
        }, rtrim($routePath, '/') ?: '/');
        $pattern = '#^' . $pattern . '$#';
        $actualPath = rtrim($actualPath, '/') ?: '/';
        if (!preg_match($pattern, $actualPath, $matches)) {
            return null;
        }
        array_shift($matches);
        return array_combine($keys, $matches) ?: [];
    }
}
