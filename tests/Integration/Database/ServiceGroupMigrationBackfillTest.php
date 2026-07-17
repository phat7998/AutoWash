<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\MigrationRunner;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class ServiceGroupMigrationBackfillTest extends TestCase
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
        $this->legacyMigrationPath = sys_get_temp_dir()
            . '/autowash-service-group-migrations-' . bin2hex(random_bytes(6));
        mkdir($this->legacyMigrationPath, 0700, true);

        foreach (glob($this->migrationPath . '/00[1-9]_*.php') ?: [] as $file) {
            copy($file, $this->legacyMigrationPath . '/' . basename($file));
        }

        (new DatabaseResetter(self::$database))->reset('testing', true);
        (new MigrationRunner(self::$database, $this->legacyMigrationPath))->migrate();
        $this->insertLegacyBooking();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->legacyMigrationPath . '/*.php') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->legacyMigrationPath);
    }

    public function testMigrationBackfillsCatalogAndPreservesHistoricalSnapshots(): void
    {
        $serviceIdsBefore = $this->serviceIds();
        $snapshotsBefore = $this->bookingSnapshots();
        $researchBefore = $this->researchMetadata();

        self::assertSame(
            ['010_add_service_group_selection_policy'],
            (new MigrationRunner(self::$database, $this->migrationPath))->migrate()
        );

        self::assertSame($serviceIdsBefore, $this->serviceIds());
        self::assertSame([
            ['code' => 'STANDARD_WASH', 'group_code' => 'WASH_PACKAGE'],
            ['code' => 'PREMIUM_WASH', 'group_code' => 'WASH_PACKAGE'],
            ['code' => 'TIRE_CARE', 'group_code' => 'ADD_ON'],
            ['code' => 'ENGINE_CLEAN', 'group_code' => 'ADD_ON'],
        ], self::$database->query(
            <<<'SQL'
            SELECT services.code, service_groups.code AS group_code
            FROM services
            INNER JOIN service_groups ON service_groups.id = services.service_group_id
            ORDER BY services.id
            SQL
        )->fetchAll());
        self::assertSame(0, (int) self::$database->query(
            'SELECT COUNT(*) FROM service_vehicle_prices WHERE capacity_units_override IS NOT NULL'
        )->fetchColumn());
        self::assertSame($snapshotsBefore, $this->bookingSnapshots());
        self::assertSame($researchBefore, $this->researchMetadata());
    }

    public function testMigrationFailsBeforeSchemaChangeWhenLegacyServiceCannotBeClassified(): void
    {
        self::$database->exec(
            "INSERT INTO services (code, name, description, is_active) "
            . "VALUES ('LEGACY_UNKNOWN', 'Dịch vụ chưa phân loại', NULL, TRUE)"
        );

        try {
            (new MigrationRunner(self::$database, $this->migrationPath))->migrate();
            self::fail('Migration không được tự đoán group cho service ngoài catalog đã audit.');
        } catch (PDOException $exception) {
            self::assertStringContainsString(
                'SERVICE_GROUP_BACKFILL_UNCLASSIFIED_CATALOG',
                $exception->getMessage()
            );
        }

        self::assertSame(0, (int) self::$database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'service_groups'
            SQL
        )->fetchColumn());
    }

    private function insertLegacyBooking(): void
    {
        self::$database->exec(
            <<<'SQL'
            INSERT INTO tiers (
                code, name, rank_order, booking_window_days,
                min_monthly_spend, min_monthly_visits, point_rate
            ) VALUES ('MEMBER', 'Thành viên', 1, 7, 0, 0, 1.00)
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO users (current_tier_id, phone, full_name, password_hash, role)
            SELECT id, '0900999999', 'Khách lịch sử', 'legacy-hash', 'customer'
            FROM tiers WHERE code = 'MEMBER'
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO vehicle_types (
                code, display_name, default_duration_minutes, default_capacity_units
            ) VALUES ('car', 'Ô tô con', 40, 2)
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO vehicles (
                user_id, vehicle_type_id, normalized_plate, display_plate, is_active
            )
            SELECT users.id, vehicle_types.id, '30A99999', '30A-99999', TRUE
            FROM users CROSS JOIN vehicle_types
            WHERE users.phone = '0900999999' AND vehicle_types.code = 'car'
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO services (code, name, description, is_active) VALUES
                ('STANDARD_WASH', 'Rửa tiêu chuẩn', 'Legacy', TRUE),
                ('PREMIUM_WASH', 'Rửa cao cấp', 'Legacy', TRUE),
                ('TIRE_CARE', 'Dưỡng lốp', 'Legacy', TRUE),
                ('ENGINE_CLEAN', 'Vệ sinh khoang máy', 'Legacy', TRUE)
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO service_vehicle_prices (
                service_id, vehicle_type_id, price, duration_minutes,
                capacity_units_override, is_supported, is_active
            )
            SELECT services.id, vehicle_types.id,
                CASE services.code
                    WHEN 'STANDARD_WASH' THEN 100000
                    WHEN 'PREMIUM_WASH' THEN 200000
                    WHEN 'TIRE_CARE' THEN 60000
                    ELSE 180000
                END,
                CASE services.code
                    WHEN 'STANDARD_WASH' THEN 40
                    WHEN 'PREMIUM_WASH' THEN 60
                    WHEN 'TIRE_CARE' THEN 20
                    ELSE 45
                END,
                CASE services.code
                    WHEN 'STANDARD_WASH' THEN 2
                    WHEN 'PREMIUM_WASH' THEN 7
                    WHEN 'TIRE_CARE' THEN 3
                    ELSE 4
                END,
                TRUE, TRUE
            FROM services CROSS JOIN vehicle_types
            WHERE vehicle_types.code = 'car'
            SQL
        );
        self::$database->exec(
            "INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status) "
            . "VALUES ('2030-01-15', '08:00:00', '09:00:00', 10, 'open')"
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO bookings (
                booking_code, user_id, vehicle_id, start_slot_id, status,
                booking_duration_minutes, booking_capacity_units, subtotal, final_price
            )
            SELECT 'LEGACY_BOTH_PACKAGES', users.id, vehicles.id, wash_slots.id, 'completed',
                100, 9, 300000, 300000
            FROM users
            INNER JOIN vehicles ON vehicles.user_id = users.id
            CROSS JOIN wash_slots
            WHERE users.phone = '0900999999'
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO booking_items (
                booking_id, service_id, service_vehicle_price_id,
                service_name_snapshot, vehicle_type_code_snapshot,
                unit_price_snapshot, duration_minutes_snapshot, capacity_units_snapshot,
                quantity, line_total
            )
            SELECT bookings.id, services.id, service_vehicle_prices.id,
                services.name, 'car', service_vehicle_prices.price,
                service_vehicle_prices.duration_minutes, 9, 1, service_vehicle_prices.price
            FROM bookings
            CROSS JOIN services
            INNER JOIN service_vehicle_prices ON service_vehicle_prices.service_id = services.id
            WHERE bookings.booking_code = 'LEGACY_BOTH_PACKAGES'
              AND services.code IN ('STANDARD_WASH', 'PREMIUM_WASH')
            SQL
        );
        self::$database->exec(
            <<<'SQL'
            INSERT INTO research_event_logs (
                event_key, anonymous_user_key, event_type, event_time, tier_code,
                vehicle_type_code, service_code, order_value, data_source, metadata_json
            ) VALUES (
                'booking_created:legacy', 'legacy-user', 'booking_created', CURRENT_TIMESTAMP,
                'MEMBER', 'car', NULL, 300000, 'system',
                JSON_OBJECT('service_codes', JSON_ARRAY('STANDARD_WASH', 'PREMIUM_WASH'))
            )
            SQL
        );
    }

    /** @return array<string, int> */
    private function serviceIds(): array
    {
        return array_map('intval', self::$database->query(
            'SELECT code, id FROM services ORDER BY id'
        )->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** @return list<array<string, mixed>> */
    private function bookingSnapshots(): array
    {
        return self::$database->query(
            <<<'SQL'
            SELECT services.code, booking_items.service_name_snapshot,
                booking_items.unit_price_snapshot,
                booking_items.duration_minutes_snapshot,
                booking_items.capacity_units_snapshot
            FROM booking_items
            INNER JOIN services ON services.id = booking_items.service_id
            ORDER BY booking_items.id
            SQL
        )->fetchAll();
    }

    private function researchMetadata(): string
    {
        return (string) self::$database->query(
            "SELECT metadata_json FROM research_event_logs WHERE event_key = 'booking_created:legacy'"
        )->fetchColumn();
    }
}
