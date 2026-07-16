<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\CsrfTokenMismatchException;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private readonly CsrfTokenManager $tokens)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $submittedToken = $request->input('_csrf_token');
        $headerToken = $request->header('x-csrf-token', '');
        $token = is_string($submittedToken) ? $submittedToken : (string) $headerToken;

        if (!$this->tokens->validate($token)) {
            throw new CsrfTokenMismatchException();
        }

        return $next($request);
    }
}
