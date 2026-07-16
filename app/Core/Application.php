<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly ErrorHandler $errorHandler
    ) {
    }

    public function handle(Request $request): Response
    {
        $requestId = $this->requestId($request);

        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $exception) {
            $response = $this->errorHandler->handle($exception, $requestId);
        }

        return $response->withHeader('X-Request-ID', $requestId);
    }

    private function requestId(Request $request): string
    {
        $provided = $request->header('x-request-id', '');

        if (is_string($provided) && preg_match('/^[A-Za-z0-9._-]{8,64}$/', $provided) === 1) {
            return $provided;
        }

        return bin2hex(random_bytes(16));
    }
}
