<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        Database::disconnect();
    }

    public function testRejectsConfigurationWithoutUtf8mb4BeforeConnecting(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bắt buộc dùng charset utf8mb4');

        Database::connection([
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'autowash',
            'username' => 'autowash',
            'password' => 'local',
            'charset' => 'utf8',
            'timezone' => '+07:00',
        ]);
    }

    public function testRejectsMissingConfigurationBeforeConnecting(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Thiếu cấu hình cơ sở dữ liệu: database');

        Database::connection([
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => '',
            'username' => 'autowash',
            'password' => 'local',
            'charset' => 'utf8mb4',
            'timezone' => '+07:00',
        ]);
    }
}
