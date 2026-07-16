<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    /**
     * @param array<string, mixed>|null $configuration
     */
    public static function connection(?array $configuration = null): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configuration ??= require dirname(__DIR__, 2) . '/config/database.php';
        self::validateConfiguration($configuration);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $configuration['host'],
            $configuration['port'],
            $configuration['database'],
            $configuration['charset']
        );

        try {
            $pdo = new PDO(
                $dsn,
                (string) $configuration['username'],
                (string) $configuration['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $statement = $pdo->prepare('SET time_zone = :timezone');
            $statement->execute(['timezone' => $configuration['timezone']]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Không thể kết nối cơ sở dữ liệu. Hãy kiểm tra cấu hình môi trường.',
                0,
                $exception
            );
        }

        self::$connection = $pdo;

        return self::$connection;
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function validateConfiguration(array $configuration): void
    {
        $requiredKeys = [
            'host',
            'port',
            'database',
            'username',
            'password',
            'charset',
            'timezone',
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $configuration) || $configuration[$key] === '') {
                throw new RuntimeException(sprintf('Thiếu cấu hình cơ sở dữ liệu: %s.', $key));
            }
        }

        if ($configuration['charset'] !== 'utf8mb4') {
            throw new RuntimeException('AutoWash Pro bắt buộc dùng charset utf8mb4.');
        }
    }
}
