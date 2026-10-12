<?php

declare(strict_types=1);

namespace RewardGate\Http;

final class Router
{
    private array $routes = [];

    private readonly string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(
        string $method,
        string $path,
        callable $handler
    ): void {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $path): bool
    {
        if ($this->basePath !== '') {
            if ($path === $this->basePath) {
                $path = '/';
            } elseif (str_starts_with($path, $this->basePath . '/')) {
                $path = substr($path, strlen($this->basePath));
            } else {
                return false;
            }
        }

        $path = rtrim($path, '/');

        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                '(?P<$1>[^/]+)',
                $route
            );

            if ($pattern === null) {
                continue;
            }

            if (preg_match('#^' . $pattern . '$#', $path, $matches) !== 1) {
                continue;
            }

            $params = [];

            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $handler($params);

            return true;
        }

        return false;
    }
}
