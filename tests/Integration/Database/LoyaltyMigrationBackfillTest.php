<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class LoyaltyMigrationBackfillTest extends TestCase
{
    private static PDO $database;
    private string $legacyMigrationPath;
    private string $migrationPath;

    public static function setUpBeforeClass(): void
    {
        if (getenv('AUTOWASH_DB_TESTS') !== '1') {
            self::markTestSkipped('Đặt AUTOWASH_DB_TESTS=1 để chạy integration test MySQL.');
        }

        Database::disconnect();
        self::$database = Database::connection([
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_NAME') ?: 'autowash',
            'username' => getenv('DB_USER') ?: 'autowash',
            'password' => getenv('DB_PASSWORD') ?: 'autowash_local',
            'charset' => 'utf8mb4',
            'timezone' => '+07:00',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        Database::disconnect();
    }

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        $this->migrationPath = $root . '/database/migrations';
        $this->legacyMigrationPath = sys_get_temp_dir() . '/autowash-legacy-migrations-' . bin2hex(random_bytes(6));
        mkdir($this->legacyMigrationPath, 0700, true);

        foreach (glob($this->migrationPath . '/00[1-6]_*.php') ?: [] as $file) {
            copy($file, $this->legacyMigrationPath . '/' . basename($file));
        }

        (new DatabaseResetter(self::$database))->reset('testing', true);
        (new MigrationRunner(self::$database, $this->legacyMigrationPath))->migrate();
        (new DatabaseSeeder(self::$database, $root . '/database/seeds/base.php'))->seed();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->legacyMigrationPath . '/*.php') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->legacyMigrationPath);
        self::$database->exec('DROP PROCEDURE IF EXISTS autowash_preflight_loyalty_adjustments');
    }

    public function testMigrationBackfillsCreditsDebitsAndPreservesExistingAllocation(): void
    {
        $userId = $this->userId();
        $earnId = $this->legacyCredit($userId, 'earn', 100, 80, 1, '2025-01-01', '2027-01-01');
        $redeemId = $this->legacyDebit($userId, 'redeem', -20, 2, '2025-02-01');
        $this->legacyAllocation($redeemId, $earnId, 20, '2025-02-01');
        $positiveId = $this->legacyAdjustment($userId, 50, 3, '2025-03-01');
        $negativeId = $this->legacyAdjustment($userId, -60, 4, '2025-04-01');
        $this->setBalance($userId, 70);

        (new MigrationRunner(self::$database, $this->migrationPath))->migrate();

        self::assertSame('adjust_credit', $this->transactionType($positiveId));
        self::assertSame('adjust_debit', $this->transactionType($negativeId));
        self::assertSame(20, $this->remainingPoints($earnId));
        self::assertSame(50, $this->remainingPoints($positiveId));
        self::assertSame(20, $this->allocationTotal($redeemId));
        self::assertSame(60, $this->allocationTotal($negativeId));
        self::assertSame(70, $this->remainingCreditTotal($userId));
        self::assertSame(70, $this->ledgerTotal($userId));
        self::assertSame(70, $this->cachedBalance($userId));
    }

    public function testMigrationFailsWithTransactionIdWhenHistoricalDebitCannotBeAllocated(): void
    {
        $userId = $this->userId();
        $negativeId = $this->legacyAdjustment($userId, -100, 20, '2025-01-01');
        $this->legacyAdjustment($userId, 150, 21, '2025-02-01');
        $this->setBalance($userId, 50);

        try {
            (new MigrationRunner(self::$database, $this->migrationPath))->migrate();
            self::fail('Migration phải từ chối debit không có credit lot lịch sử.');
        } catch (PDOException $exception) {
            self::assertStringContainsString(
                'LOYALTY_BACKFILL_UNALLOCATABLE_ADJUST_' . $negativeId,
                $exception->getMessage()
            );
        }

        self::assertSame('adjust', $this->transactionType($negativeId));
    }

    public function testMigrationFailsWithTransactionIdForLegacyZeroPointCredit(): void
    {
        $userId = $this->userId();
        $creditId = $this->legacyCredit(
            $userId,
            'earn',
            0,
            0,
            30,
            '2025-01-01',
            '2027-01-01'
        );
        $this->setBalance($userId, 0);

        try {
            (new MigrationRunner(self::$database, $this->migrationPath))->migrate();
            self::fail('Migration phải từ chối credit transaction không có điểm dương.');
        } catch (PDOException $exception) {
            self::assertStringContainsString(
                'LOYALTY_BACKFILL_ZERO_CREDIT_' . $creditId,
                $exception->getMessage()
            );
        }

        self::assertSame('earn', $this->transactionType($creditId));
    }

    private function legacyCredit(
        int $userId,
        string $type,
        int $points,
        int $remaining,
        int $sourceId,
        string $createdAt,
        string $expiresAt
    ): int {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                description, earned_at, expires_at, created_at, updated_at
            ) VALUES (
                :user_id, :type, :points, :remaining, 'legacy_test', :source_id,
                'Legacy credit.', :created_at, :expires_at, :created_at_copy, :updated_at
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'points' => $points,
            'remaining' => $remaining,
            'source_id' => $sourceId,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
            'created_at_copy' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return (int) self::$database->lastInsertId();
    }

    private function legacyDebit(int $userId, string $type, int $points, int $sourceId, string $at): int
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, source_type, source_id, description, created_at, updated_at
            ) VALUES (:user_id, :type, :points, 'legacy_test', :source_id, 'Legacy debit.', :at, :updated_at)
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'points' => $points,
            'source_id' => $sourceId,
            'at' => $at,
            'updated_at' => $at,
        ]);

        return (int) self::$database->lastInsertId();
    }

    private function legacyAdjustment(int $userId, int $points, int $sourceId, string $at): int
    {
        return $this->legacyDebit($userId, 'adjust', $points, $sourceId, $at);
    }

    private function legacyAllocation(int $debitId, int $earnId, int $points, string $at): void
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_allocations (
                debit_transaction_id, earn_transaction_id, points_allocated, allocated_at
            ) VALUES (:debit_id, :earn_id, :points, :at)
            SQL
        );
        $statement->execute([
            'debit_id' => $debitId,
            'earn_id' => $earnId,
            'points' => $points,
            'at' => $at,
        ]);
    }

    private function userId(): int
    {
        return (int) self::$database->query("SELECT id FROM users WHERE phone = '0900000002'")
            ->fetchColumn();
    }

    private function setBalance(int $userId, int $points): void
    {
        self::$database->exec('UPDATE users SET point_balance = ' . $points . ' WHERE id = ' . $userId);
    }

    private function transactionType(int $id): string
    {
        return (string) self::$database->query('SELECT type FROM loyalty_transactions WHERE id = ' . $id)
            ->fetchColumn();
    }

    private function remainingPoints(int $id): int
    {
        return (int) self::$database->query(
            'SELECT remaining_points FROM loyalty_transactions WHERE id = ' . $id
        )->fetchColumn();
    }

    private function allocationTotal(int $debitId): int
    {
        return (int) self::$database->query(
            'SELECT COALESCE(SUM(allocated_points), 0) FROM loyalty_allocations '
            . 'WHERE debit_transaction_id = ' . $debitId
        )->fetchColumn();
    }

    private function remainingCreditTotal(int $userId): int
    {
        return (int) self::$database->query(
            "SELECT COALESCE(SUM(remaining_points), 0) FROM loyalty_transactions "
            . "WHERE user_id = $userId AND type IN ('earn', 'adjust_credit')"
        )->fetchColumn();
    }

    private function ledgerTotal(int $userId): int
    {
        return (int) self::$database->query(
            'SELECT COALESCE(SUM(points_delta), 0) FROM loyalty_transactions WHERE user_id = ' . $userId
        )->fetchColumn();
    }

    private function cachedBalance(int $userId): int
    {
        return (int) self::$database->query('SELECT point_balance FROM users WHERE id = ' . $userId)
            ->fetchColumn();
    }
}
