<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final readonly class DatabaseResetter
{
    public function __construct(private PDO $database)
    {
    }

    public function reset(string $environment, bool $forced): void
    {
        if (!in_array($environment, ['local', 'testing'], true)) {
            throw new RuntimeException('Chỉ được reset database trong môi trường local hoặc testing.');
        }

        if (!$forced) {
            throw new RuntimeException('Reset database cần cờ --force để xác nhận.');
        }

        $tables = $this->database->query(
            <<<'SQL'
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
            SQL
        )->fetchAll(PDO::FETCH_COLUMN);

        $this->database->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                $safeTable = str_replace('`', '``', (string) $table);
                $this->database->exec(sprintf('DROP TABLE `%s`', $safeTable));
            }
        } finally {
            $this->database->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
