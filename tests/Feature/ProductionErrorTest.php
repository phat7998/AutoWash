<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Application;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProductionErrorTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/autowash-error-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testProductionErrorHidesTechnicalDetailAndLogsRequestId(): void
    {
        $router = new Router();
        $router->get('/loi', static function (): Response {
            throw new RuntimeException('Chi tiết kỹ thuật nội bộ DB_PASSWORD=mat-khau-that');
        });
        $view = new View(dirname(__DIR__, 2) . '/resources/views');
        $application = new Application(
            $router,
            new ErrorHandler(
                $view,
                new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
                false
            )
        );

        $response = $application->handle(new Request(
            'GET',
            '/loi',
            [],
            [],
            [],
            ['x-request-id' => 'request-test-1234']
        ));
        $log = (string) file_get_contents($this->logFile);

        self::assertSame(500, $response->statusCode());
        self::assertStringNotContainsString('Chi tiết kỹ thuật nội bộ', $response->body());
        self::assertStringNotContainsString('mat-khau-that', $response->body());
        self::assertStringNotContainsString('RuntimeException', $response->body());
        self::assertStringContainsString('request-test-1234', $response->body());
        self::assertStringContainsString('request-test-1234', $log);
        self::assertStringContainsString('RuntimeException', $log);
        self::assertStringNotContainsString('mat-khau-that', $log);
        self::assertStringContainsString('DB_PASSWORD=[ĐÃ ẨN]', $log);
    }
}
