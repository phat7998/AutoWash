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

final class AuthAuthorizationTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/autowash-authz-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testGuestIsRedirectedFromProtectedRoutes(): void
    {
        $sessionData = [];
        $application = $this->application($sessionData);

        $customer = $application->handle(new Request('GET', '/tai-khoan'));
        $admin = $application->handle(new Request('GET', '/admin'));
        $adminReport = $application->handle(new Request('GET', '/admin/bao-cao'));

        self::assertSame(303, $customer->statusCode());
        self::assertSame('/dang-nhap', $customer->headers()['Location']);
        self::assertSame(303, $admin->statusCode());
        self::assertSame('/dang-nhap', $admin->headers()['Location']);
        self::assertSame(303, $adminReport->statusCode());
        self::assertSame('/dang-nhap', $adminReport->headers()['Location']);
    }

    public function testCustomerCannotAccessAdminEvenByDirectUrl(): void
    {
        $sessionData = ['auth_user' => ['id' => 10, 'full_name' => 'Khách hàng', 'role' => 'customer']];
        $application = $this->application($sessionData);

        $ownDashboard = $application->handle(new Request('GET', '/tai-khoan'));
        $admin = $application->handle(new Request('GET', '/admin'));
        $adminReport = $application->handle(new Request('GET', '/admin/bao-cao'));
        $root = $application->handle(new Request('GET', '/'));

        self::assertSame(200, $ownDashboard->statusCode());
        self::assertSame(303, $root->statusCode());
        self::assertSame('/tai-khoan', $root->headers()['Location']);
        self::assertSame(403, $admin->statusCode());
        self::assertSame(403, $adminReport->statusCode());
        self::assertStringContainsString('không có quyền', $admin->body());
        self::assertStringNotContainsString('/admin/lich-dat', $ownDashboard->body());
        self::assertStringContainsString('/doi-thuong', $ownDashboard->body());
    }

    public function testAdminCanAccessAdminAndCannotEnterCustomerArea(): void
    {
        $sessionData = ['auth_user' => ['id' => 1, 'full_name' => 'Quản trị viên', 'role' => 'admin']];
        $application = $this->application($sessionData);

        $adminDashboard = $application->handle(new Request('GET', '/admin'));
        $root = $application->handle(new Request('GET', '/'));

        self::assertSame(200, $adminDashboard->statusCode());
        self::assertSame(303, $root->statusCode());
        self::assertSame('/admin', $root->headers()['Location']);
        foreach (
            [
                '/admin/lich-dat',
                '/admin/dich-vu',
                '/admin/khung-gio',
                '/admin/diem-thuong',
                '/admin/xet-hang',
                '/admin/hang-thanh-vien',
                '/admin/promotion',
                '/admin/reward',
                '/admin/bao-cao',
            ] as $path
        ) {
            self::assertStringContainsString('href="' . $path . '"', $adminDashboard->body());
        }
        self::assertSame(403, $application->handle(new Request('GET', '/tai-khoan'))->statusCode());
    }

    /** @param array<string, mixed> $sessionData */
    private function application(array &$sessionData): Application
    {
        $session = new Session($sessionData);
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
            static fn (): never => throw new \RuntimeException('Không cần AuthController cho test phân quyền.'),
            adminReportControllerFactory: static fn (): never =>
                throw new \RuntimeException('Middleware phải chặn trước khi tạo controller báo cáo.')
        );

        return new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        ));
    }
}
