<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;
use Throwable;

final readonly class MigrationRunner
{
    public function __construct(
        private PDO $database,
        private string $migrationPath
    ) {
    }

    /**
     * @return list<string> Tên migration vừa chạy
     */
    public function migrate(): array
    {
        $this->createHistoryTable();

        if (!$this->acquireLock()) {
            throw new RuntimeException('Không thể lấy khóa migration trong thời gian cho phép.');
        }

        try {
            $executed = $this->executedMigrationNames();
            $batch = $this->nextBatchNumber();
            $migrated = [];

            foreach ($this->migrationFiles() as $file) {
                $migration = require $file;

                if (!$migration instanceof MigrationDefinition) {
                    throw new RuntimeException(sprintf('Migration không hợp lệ: %s.', basename($file)));
                }

                if (isset($executed[$migration->name])) {
                    continue;
                }

                foreach ($migration->statements as $statement) {
                    $this->database->exec($statement);
                }

                $insert = $this->database->prepare(
                    'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)'
                );
                $insert->execute([
                    'migration' => $migration->name,
                    'batch' => $batch,
                ]);
                $migrated[] = $migration->name;
            }

            return $migrated;
        } finally {
            $this->releaseLock();
        }
    }

    private function createHistoryTable(): void
    {
        $this->database->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    private function acquireLock(): bool
    {
        $statement = $this->database->prepare('SELECT GET_LOCK(:lock_name, 10)');
        $statement->execute(['lock_name' => 'autowash_migrations']);

        return (int) $statement->fetchColumn() === 1;
    }

    private function releaseLock(): void
    {
        try {
            $statement = $this->database->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $statement->execute(['lock_name' => 'autowash_migrations']);
        } catch (Throwable) {
            // Kết nối sẽ tự giải phóng advisory lock nếu database đã ngắt.
        }
    }

    /**
     * @return array<string, true>
     */
    private function executedMigrationNames(): array
    {
        $names = $this->database->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

        return array_fill_keys(array_map('strval', $names), true);
    }

    private function nextBatchNumber(): int
    {
        return (int) $this->database->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob(rtrim($this->migrationPath, '/') . '/*.php');

        if ($files === false) {
            throw new RuntimeException('Không thể đọc thư mục migration.');
        }

        sort($files, SORT_STRING);

        return array_values($files);
    }
}
