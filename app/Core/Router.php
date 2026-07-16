<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\RouteNotFoundException;
use App\Middleware\MiddlewareInterface;

final class Router
{
    /** @var list<array{method: string, path: string, handler: callable}> */
    private array $routes = [];

    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function middleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $parameters = $this->match($route['path'], $request->path());

            if ($parameters === null) {
                continue;
            }

            if ($route['method'] !== $request->method()) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $routedRequest = $request->withRouteParameters($parameters);
            $destination = static fn (Request $current): Response => ($route['handler'])($current);
            $pipeline = array_reduce(
                array_reverse($this->middleware),
                static fn (callable $next, MiddlewareInterface $item): callable =>
                    static fn (Request $current): Response => $item->process($current, $next),
                $destination
            );

            return $pipeline($routedRequest);
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new RouteNotFoundException();
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $routePath, string $requestPath): ?array
    {
        $names = [];
        $quoted = preg_quote($routePath, '#');
        $pattern = preg_replace_callback(
            '/\\\{([A-Za-z_][A-Za-z0-9_]*)\\\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];

                return '([^/]+)';
            },
            $quoted
        );

        if ($pattern === null || preg_match('#^' . $pattern . '$#', $requestPath, $matches) !== 1) {
            return null;
        }

        array_shift($matches);

        return array_combine($names, $matches) ?: [];
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
