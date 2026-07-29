<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->add('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->add('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->add('DELETE', $uri, $action, $middleware);
    }

    public function group(array $options, callable $callback): void
    {
        $this->groupStack[] = $options;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $uri, array|callable $action, array $middleware = []): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }

        $uri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/') ?: '/';
        }

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri === '' ? '/' : $uri,
            'action' => $action,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];

        return $this;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        if ($method === 'POST' && ($request->input('_method'))) {
            $method = strtoupper((string) $request->input('_method'));
        }

        $path = $request->path();
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        foreach ($this->routes as $route) {
            $params = $this->match($route['uri'], $path);
            if ($params !== false && $route['method'] === $method) {
                $this->runMiddleware($route['middleware'], $request);
                $this->runAction($route['action'], $request, $params);
                return;
            }
        }

        http_response_code(404);
        if ($request->isAjax()) {
            Response::error('Page not found.', null, 404);
        }
        echo '404 Not Found';
    }

    private function match(string $routeUri, string $path): array|false
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $routeUri);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $path, $matches)) {
            return false;
        }

        array_shift($matches);
        preg_match_all('#\{([a-zA-Z_]+)\}#', $routeUri, $keys);
        $params = [];
        foreach ($keys[1] as $i => $key) {
            $params[$key] = $matches[$i] ?? null;
        }
        return $params;
    }

    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $name) {
            if (str_starts_with($name, 'permission:')) {
                \App\Middleware\PermissionMiddleware::setRequired(substr($name, 11));
                (new \App\Middleware\PermissionMiddleware())->handle($request);
                \App\Middleware\PermissionMiddleware::setRequired(null);
                continue;
            }

            $class = $this->resolveMiddleware($name);
            (new $class())->handle($request);
        }
    }

    private function resolveMiddleware(string $name): string
    {
        $map = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'csrf' => \App\Middleware\CsrfMiddleware::class,
            'permission' => \App\Middleware\PermissionMiddleware::class,
        ];

        if (!isset($map[$name])) {
            throw new \RuntimeException("Middleware [{$name}] not found.");
        }

        return $map[$name];
    }

    private function runAction(array|callable $action, Request $request, array $params): void
    {
        if (is_callable($action)) {
            $action($request, ...array_values($params));
            return;
        }

        [$controller, $method] = $action;
        $instance = new $controller();

        // Pass permission middleware param if present in route middleware stack handled separately
        $instance->{$method}($request, ...array_values($params));
    }
}
