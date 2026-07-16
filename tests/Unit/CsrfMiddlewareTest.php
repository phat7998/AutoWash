<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\CsrfTokenMismatchException;
use App\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    public function testAllowsSafeMethodWithoutToken(): void
    {
        $middleware = $this->middleware();

        $response = $middleware->process(
            new Request('GET', '/'),
            static fn (): Response => Response::html('ok')
        );

        self::assertSame('ok', $response->body());
    }

    public function testAcceptsValidTokenAndRejectsReplay(): void
    {
        $data = [];
        $session = new Session($data);
        $tokens = new CsrfTokenManager($session);
        $middleware = new CsrfMiddleware($tokens);
        $token = $tokens->token();
        $request = new Request('POST', '/', [], ['_csrf_token' => $token]);

        $response = $middleware->process($request, static fn (): Response => Response::html('ok'));

        self::assertSame('ok', $response->body());

        $this->expectException(CsrfTokenMismatchException::class);
        $middleware->process($request, static fn (): Response => Response::html('không được gọi'));
    }

    public function testRejectsMissingOrInvalidToken(): void
    {
        $middleware = $this->middleware();

        $this->expectException(CsrfTokenMismatchException::class);
        $middleware->process(
            new Request('DELETE', '/xe/1', [], ['_csrf_token' => 'sai-token']),
            static fn (): Response => Response::html('không được gọi')
        );
    }

    private function middleware(): CsrfMiddleware
    {
        $data = [];
        $session = new Session($data);

        return new CsrfMiddleware(new CsrfTokenManager($session));
    }
}
