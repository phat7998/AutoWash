<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly array $headers = []
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
