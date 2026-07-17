<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseFoundationTest extends TestCase
{
    private static PDO $database;
    private static MigrationRunner $runner;
    private static DatabaseSeeder $seeder;

    public static function setUpBeforeClass(): void
    {
        if (getenv('AUTOWASH_DB_TESTS') !== '1') {
            self::markTestSkipped('Đặt AUTOWASH_DB_TESTS=1 để chạy integration test MySQL.');
        }

        $projectRoot = dirname(__DIR__, 3);
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
        self::$runner = new MigrationRunner(self::$database, $projectRoot . '/database/migrations');
        self::$seeder = new DatabaseSeeder(self::$database, $projectRoot . '/database/seeds/base.php');

        (new DatabaseResetter(self::$database))->reset('testing', true);
        self::$runner->migrate();
        self::$seeder->seed();
    }

    public static function tearDownAfterClass(): void
    {
        Database::disconnect();
    }

    public function testPdoUsesRequiredOptionsCharsetAndTimezone(): void
    {
        self::assertSame(PDO::ERRMODE_EXCEPTION, self::$database->getAttribute(PDO::ATTR_ERRMODE));
        self::assertSame(PDO::FETCH_ASSOC, self::$database->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        self::assertFalse((bool) self::$database->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        $connectionCharset = self::$database->query('SELECT @@character_set_connection')->fetchColumn();
        self::assertStringContainsString('utf8mb4', (string) $connectionCharset);
        self::assertSame('+07:00', self::$database->query('SELECT @@session.time_zone')->fetchColumn());
        self::assertSame(self::$database, Database::connection());
    }

    public function testMigrateCreatesCompleteSchemaAndIsRepeatable(): void
    {
        $expectedTables = [
            'app_settings',
            'audit_logs',
            'booking_items',
            'booking_slot_reservations',
            'bookings',
            'loyalty_allocations',
            'loyalty_transactions',
            'lpr_attempts',
            'migrations',
            'monthly_review_runs',
            'promotion_services',
            'promotion_tiers',
            'promotion_usages',
            'promotion_vehicle_types',
            'promotions',
            'research_event_logs',
            'reward_redemptions',
            'reward_vehicle_types',
            'rewards',
            'service_vehicle_prices',
            'services',
            'tier_histories',
            'tier_perks',
            'tiers',
            'users',
            'vehicle_types',
            'vehicles',
            'wash_slots',
        ];

        $actualTables = self::$database->query(
            <<<'SQL'
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
            ORDER BY table_name
            SQL
        )->fetchAll(PDO::FETCH_COLUMN);

        self::assertSame($expectedTables, $actualTables);
        self::assertSame(9, (int) self::$database->query('SELECT COUNT(*) FROM migrations')->fetchColumn());
        self::assertSame([], self::$runner->migrate());
    }

    #[Depends('testMigrateCreatesCompleteSchemaAndIsRepeatable')]
    public function testSeedContainsLockedConfigurationAndIsIdempotent(): void
    {
        $countsBefore = $this->seedCounts();
        self::$seeder->seed();

        self::assertSame($countsBefore, $this->seedCounts());
        self::assertSame([
            ['code' => 'MEMBER', 'booking_window_days' => 7, 'point_rate' => '1.00'],
            ['code' => 'SILVER', 'booking_window_days' => 10, 'point_rate' => '1.10'],
            ['code' => 'GOLD', 'booking_window_days' => 12, 'point_rate' => '1.25'],
            ['code' => 'PLATINUM', 'booking_window_days' => 14, 'point_rate' => '1.50'],
        ], self::$database->query(
            'SELECT code, booking_window_days, point_rate FROM tiers ORDER BY rank_order'
        )->fetchAll());
        self::assertSame(
            ['motorbike', 'car', 'truck', 'bus'],
            self::$database->query('SELECT code FROM vehicle_types ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)
        );
        self::assertSame(
            '10000',
            self::$database->query(
                "SELECT setting_value FROM app_settings WHERE setting_key = 'loyalty.point_unit_amount'"
            )->fetchColumn()
        );
        self::assertSame(2, (int) self::$database->query(
            'SELECT COUNT(*) FROM service_vehicle_prices WHERE is_supported = FALSE'
        )->fetchColumn());
        $demoUsers = self::$database->query(
            'SELECT phone, password_hash, role FROM users ORDER BY phone'
        )->fetchAll();
        self::assertCount(5, $demoUsers);
        self::assertSame(1, count(array_filter(
            $demoUsers,
            static fn (array $user): bool => $user['role'] === 'admin'
        )));
        self::assertSame(4, count(array_filter(
            $demoUsers,
            static fn (array $user): bool => $user['role'] === 'customer'
        )));
        self::assertTrue(password_verify('AutoWash@123', $demoUsers[0]['password_hash']));
        self::assertNotSame('AutoWash@123', $demoUsers[0]['password_hash']);
        self::assertSame(
            ['motorbike', 'car', 'truck', 'bus'],
            self::$database->query(
                <<<'SQL'
                SELECT vehicle_types.code
                FROM vehicles
                INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
                ORDER BY vehicles.id
                SQL
            )->fetchAll(PDO::FETCH_COLUMN)
        );
        self::assertSame(1, (int) self::$database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM loyalty_transactions
            WHERE source_type = 'demo_seed'
              AND type = 'earn'
              AND remaining_points > 0
              AND expires_at <= CURRENT_TIMESTAMP
            SQL
        )->fetchColumn());
        self::assertSame(1, (int) self::$database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM loyalty_transactions
            WHERE source_type = 'demo_seed'
              AND type = 'earn'
              AND remaining_points > 0
              AND expires_at > CURRENT_TIMESTAMP
              AND expires_at <= CURRENT_TIMESTAMP + INTERVAL 30 DAY
            SQL
        )->fetchColumn());
    }

    public function testImportantUniqueAndCheckConstraintsAreEnforced(): void
    {
        $this->assertStatementFails(
            <<<'SQL'
            INSERT INTO tiers (
                code, name, rank_order, booking_window_days,
                min_monthly_spend, min_monthly_visits, point_rate
            ) VALUES ('MEMBER', 'Trùng', 99, 1, 0, 0, 1.00)
            SQL
        );
        $this->assertStatementFails(
            <<<'SQL'
            INSERT INTO service_vehicle_prices (
                service_id, vehicle_type_id, price, duration_minutes, is_supported, is_active
            ) SELECT service_id, vehicle_type_id, price, duration_minutes, is_supported, is_active
              FROM service_vehicle_prices LIMIT 1
            SQL
        );
        $this->assertStatementFails(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES ('2031-01-01', '10:00:00', '09:00:00', 1, 'open')
            SQL
        );
    }

    public function testMoneyColumnsUseDecimalAndCriticalHistoryHasNoCascadeDelete(): void
    {
        $invalidMoneyTypes = self::$database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND column_name IN (
                'price', 'subtotal', 'final_price', 'discount_amount', 'order_value',
                'min_monthly_spend', 'monthly_spend', 'value'
              )
              AND data_type <> 'decimal'
            SQL
        )->fetchColumn();
        self::assertSame(0, (int) $invalidMoneyTypes);

        $cascades = self::$database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.referential_constraints
            WHERE constraint_schema = DATABASE()
              AND table_name IN (
                'bookings', 'booking_items', 'loyalty_transactions', 'loyalty_allocations',
                'reward_redemptions', 'promotion_usages', 'tier_histories'
              )
              AND delete_rule = 'CASCADE'
            SQL
        )->fetchColumn();
        self::assertSame(0, (int) $cascades);
    }

    public function testReferencedVehicleTypeCannotBeHardDeleted(): void
    {
        $this->assertStatementFails("DELETE FROM vehicle_types WHERE code = 'motorbike'");
        self::assertSame(4, (int) self::$database->query('SELECT COUNT(*) FROM vehicle_types')->fetchColumn());
    }

    public function testResetRequiresSafeEnvironmentAndExplicitConfirmation(): void
    {
        $resetter = new DatabaseResetter(self::$database);

        try {
            $resetter->reset('production', true);
            self::fail('Reset production phải bị từ chối.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('local hoặc testing', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cần cờ --force');
        $resetter->reset('testing', false);
    }

    /** @return array<string, int> */
    private function seedCounts(): array
    {
        $tables = [
            'app_settings',
            'tiers',
            'users',
            'vehicle_types',
            'vehicles',
            'services',
            'service_vehicle_prices',
            'wash_slots',
            'bookings',
            'booking_items',
            'booking_slot_reservations',
            'rewards',
            'reward_vehicle_types',
        ];
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = (int) self::$database->query(sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();
        }

        return $counts;
    }

    private function assertStatementFails(string $sql): void
    {
        try {
            self::$database->exec($sql);
            self::fail('Câu lệnh vi phạm constraint phải thất bại.');
        } catch (PDOException) {
            self::assertTrue(true);
        }
    }
}
