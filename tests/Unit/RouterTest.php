<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\RouteNotFoundException;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesStaticAndParameterizedRoute(): void
    {
        $router = new Router();
        $router->get('/xe/{id}', static fn (Request $request): Response => Response::html(
            'Xe ' . $request->route('id')
        ));

        $response = $router->dispatch(new Request('GET', '/xe/15'));

        self::assertSame(200, $response->statusCode());
        self::assertSame('Xe 15', $response->body());
    }

    public function testThrowsRouteNotFound(): void
    {
        $router = new Router();

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch(new Request('GET', '/khong-ton-tai'));
    }

    public function testMethodMismatchContainsAllowedMethod(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): Response => Response::html('ok'));

        try {
            $router->dispatch(new Request('POST', '/health'));
            self::fail('Router phải từ chối phương thức không được khai báo.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(405, $exception->statusCode());
            self::assertSame('GET', $exception->headers()['Allow']);
        }
    }
}
