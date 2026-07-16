<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EnvironmentConfigTest extends TestCase
{
    public function testEnvironmentBootstrapAndDefaultConfiguration(): void
    {
        $projectRoot = require dirname(__DIR__, 2) . '/bootstrap/environment.php';
        $app = require $projectRoot . '/config/app.php';
        $database = require $projectRoot . '/config/database.php';
        $loyalty = require $projectRoot . '/config/loyalty.php';

        self::assertSame(dirname(__DIR__, 2), $projectRoot);
        self::assertSame('Asia/Ho_Chi_Minh', $app['timezone']);
        self::assertSame('utf8mb4', $database['charset']);
        self::assertSame(10_000, $loyalty['point_unit_amount']);
    }
}
