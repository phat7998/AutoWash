<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     * @param array<string, string> $routeParameters
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $server = [],
        private readonly array $headers = [],
        private array $routeParameters = []
    ) {
    }

    public static function capture(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_') && is_string($value)) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE']) && is_string($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST,
            $_SERVER,
            $headers
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';
        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function route(string $key, ?string $default = null): ?string
    {
        return $this->routeParameters[$key] ?? $default;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function withRouteParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->routeParameters = $parameters;

        return $clone;
    }

    public function isSecure(): bool
    {
        $https = strtolower((string) ($this->server['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) $this->header('x-forwarded-proto', ''));

        return ($https !== '' && $https !== 'off') || $forwardedProto === 'https';
    }
}
