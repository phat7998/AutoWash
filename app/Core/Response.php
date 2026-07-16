<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body = '',
        private readonly int $statusCode = 200,
        private array $headers = []
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function html(string $body, int $statusCode = 200, array $headers = []): self
    {
        return new self($body, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8'] + $headers);
    }

    /**
     * @param array<string, mixed> $data
     * @throws JsonException
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $statusCode,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $location, int $statusCode = 303): self
    {
        return new self('', $statusCode, ['Location' => $location]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        header(
            sprintf('HTTP/1.1 %d %s', $this->statusCode, $this->reasonPhrase()),
            true,
            $this->statusCode
        );

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $this->body;
    }

    private function reasonPhrase(): string
    {
        return match ($this->statusCode) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            302 => 'Found',
            303 => 'See Other',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Content',
            500 => 'Internal Server Error',
            default => 'Response',
        };
    }
}
