<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Application;
use App\Core\CsrfTokenManager;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class HttpCoreTest extends TestCase
{
    private string $logFile;

    /** @var array<string, mixed> */
    private array $sessionData = [];

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/autowash-http-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testHomeAndHealthRoutesExposeOnlyExpectedInformation(): void
    {
        [$application] = $this->application();

        $home = $application->handle(new Request('GET', '/'));
        $health = $application->handle(new Request('GET', '/health'));

        self::assertSame(200, $home->statusCode());
        self::assertStringContainsString('Chăm sóc phương tiện', $home->body());
        self::assertStringContainsString('href="/dat-lich"', $home->body());
        self::assertStringContainsString('href="/dich-vu"', $home->body());
        self::assertStringContainsString('Xe máy', $home->body());
        self::assertStringContainsString('Ô tô con', $home->body());
        self::assertStringContainsString('Xe tải', $home->body());
        self::assertStringContainsString('Xe khách', $home->body());
        self::assertStringNotContainsString('Front Controller', $home->body());
        self::assertStringNotContainsString('Post/Redirect/Get', $home->body());
        self::assertStringNotContainsString('CSRF', $home->body());
        self::assertStringNotContainsString('Nền tảng AutoWash Pro đã sẵn sàng', $home->body());
        self::assertArrayHasKey('X-Request-ID', $home->headers());
        self::assertSame(200, $health->statusCode());
        self::assertJsonStringEqualsJsonString(
            '{"status":"ok","service":"AutoWash Pro"}',
            $health->body()
        );
        self::assertStringNotContainsString('DB_PASSWORD', $health->body());
    }

    public function testUnknownRouteAndMethodMismatchAreSafe(): void
    {
        [$application] = $this->application();

        $notFound = $application->handle(new Request('GET', '/khong-ton-tai'));
        $methodMismatch = $application->handle(new Request('POST', '/health'));

        self::assertSame(404, $notFound->statusCode());
        self::assertStringContainsString('Không tìm thấy trang', $notFound->body());
        self::assertSame(405, $methodMismatch->statusCode());
        self::assertSame('GET', $methodMismatch->headers()['Allow']);
        self::assertStringNotContainsString('RouteNotFoundException', $notFound->body());
        self::assertStringContainsString('Quay lại khu vực chính', $notFound->body());
    }

    public function testPostRedirectGetRequiresCsrfAndReturnsFlashOnNextGet(): void
    {
        [$application, $tokens] = $this->application();

        $missingToken = $application->handle(new Request('POST', '/thong-bao-mau'));
        self::assertSame(419, $missingToken->statusCode());

        $validToken = $tokens->token();
        $post = $application->handle(new Request(
            'POST',
            '/thong-bao-mau',
            [],
            ['_csrf_token' => $validToken]
        ));
        $get = $application->handle(new Request('GET', '/'));

        self::assertSame(303, $post->statusCode());
        self::assertSame('/', $post->headers()['Location']);
        self::assertStringContainsString('Yêu cầu hợp lệ đã được xử lý an toàn.', $get->body());
    }

    /**
     * @return array{Application, CsrfTokenManager}
     */
    private function application(): array
    {
        $session = new Session($this->sessionData);
        $tokens = new CsrfTokenManager($session);
        $view = new View(dirname(__DIR__, 2) . '/resources/views');
        $router = new Router();
        $router->middleware(new CsrfMiddleware($tokens));

        $registerRoutes = require dirname(__DIR__, 2) . '/routes/web.php';
        $registerRoutes(
            $router,
            $view,
            $session,
            $tokens,
            static fn (): never => throw new \RuntimeException(
                'Route HTTP nền tảng không được tải database.'
            )
        );

        $handler = new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        );

        return [new Application($router, $handler), $tokens];
    }
}
