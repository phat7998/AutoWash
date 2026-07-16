<?php

declare(strict_types=1);

namespace Tests\Integration\Research;

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use App\Repositories\ResearchEventRepository;
use App\Repositories\ResearchReportRepository;
use App\Services\ResearchCsvExporter;
use App\Services\ResearchEventService;
use App\Services\ResearchExportService;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

final class ResearchFlowTest extends TestCase
{
    private static PDO $database;

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
        (new DatabaseResetter(self::$database))->reset('testing', true);
        (new MigrationRunner(self::$database, $projectRoot . '/database/migrations'))->migrate();
        (new DatabaseSeeder(self::$database, $projectRoot . '/database/seeds/base.php'))->seed();
    }

    public static function tearDownAfterClass(): void
    {
        Database::disconnect();
    }

    protected function setUp(): void
    {
        self::$database->exec("DELETE FROM research_event_logs WHERE event_key LIKE 'test:%'");
    }

    public function testResearchServiceWritesPrivacySafeOperationalEvents(): void
    {
        $customerId = $this->customerId('0900000002');
        $events = new ResearchEventService(new ResearchEventRepository(self::$database));
        $at = new DateTimeImmutable('2026-07-17 09:00:00');

        $events->rewardRedeemed($customerId, 900001, 100, $at);
        $events->pointsExpired($customerId, 900002, 800001, 25, $at);
        $events->tierChanged(
            $customerId,
            900003,
            'MEMBER',
            'SILVER',
            '350000.00',
            3,
            '2026-06',
            $at
        );

        $rows = self::$database->query(
            "SELECT event_type, anonymous_user_key, points_redeemed, data_source "
            . "FROM research_event_logs WHERE event_key LIKE '%:90000%' ORDER BY event_type"
        )->fetchAll();
        self::assertCount(3, $rows);
        self::assertSame(['points_expired', 'reward_redeemed', 'tier_changed'], array_column($rows, 'event_type'));
        self::assertSame(['system'], array_values(array_unique(array_column($rows, 'data_source'))));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $rows[0]['anonymous_user_key']);
        self::assertNotSame((string) $customerId, (string) $rows[0]['anonymous_user_key']);
    }

    public function testCsvExportHasExactSafeColumnsAndFiltersSource(): void
    {
        $repository = new ResearchEventRepository(self::$database);
        $context = $repository->userContext($this->customerId('0900000002'));
        $repository->insert([
            'event_key' => 'test:export:1',
            'anonymous_user_key' => $context['anonymous_user_key'],
            'event_type' => 'booking_completed',
            'event_time' => '2026-07-17 10:00:00',
            'tier_code' => 'MEMBER',
            'order_value' => '120000.00',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'autowash-export-');
        self::assertIsString($path);

        try {
            $count = (new ResearchExportService($repository, new ResearchCsvExporter()))
                ->export($path, '2026-07-17', '2026-07-17', 'system');
            self::assertGreaterThanOrEqual(1, $count);
            $handle = fopen($path, 'rb');
            self::assertIsResource($handle);
            $header = fgetcsv($handle, 0, ',', '"', '');
            fclose($handle);
            self::assertSame(ResearchCsvExporter::COLUMNS, $header);
            self::assertSame([], array_intersect(
                ['full_name', 'phone', 'email', 'password_hash', 'normalized_plate', 'ip'],
                $header
            ));
            $content = (string) file_get_contents($path);
            self::assertStringNotContainsString('0900000002', $content);
            self::assertStringNotContainsString('AutoWash@123', $content);
        } finally {
            @unlink($path);
        }
    }

    public function testDashboardRevenueUsesCompletedBookingsOnlyAndCustomerDataIsOwnerScoped(): void
    {
        $customerId = $this->customerId('0900000002');
        $otherCustomerId = $this->customerId('0900000003');
        $bookingIds = self::$database->query('SELECT id FROM bookings ORDER BY id LIMIT 2')
            ->fetchAll(PDO::FETCH_COLUMN);
        self::assertCount(2, $bookingIds);
        $complete = self::$database->prepare(
            "UPDATE bookings SET user_id = :user_id, status = 'completed', subtotal = '111111.00', "
            . "perk_discount = 0, promotion_discount = 0, reward_discount = 0, final_price = '111111.00', "
            . "completed_at = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $complete->execute(['user_id' => $customerId, 'id' => $bookingIds[0]]);
        $pending = self::$database->prepare(
            "UPDATE bookings SET user_id = :user_id, status = 'confirmed', subtotal = '999999.00', "
            . "perk_discount = 0, promotion_discount = 0, reward_discount = 0, final_price = '999999.00', "
            . 'completed_at = NULL WHERE id = :id'
        );
        $pending->execute(['user_id' => $otherCustomerId, 'id' => $bookingIds[1]]);

        $reports = new ResearchReportRepository(self::$database);
        $admin = $reports->adminMetrics();
        self::assertSame('111111.00', $admin['revenue']['completed_revenue']);

        $customer = $reports->customerMetrics($customerId);
        self::assertSame(1, count($customer['wash_history']));
        self::assertSame('111111.00', $customer['wash_history'][0]['final_price']);
        $other = $reports->customerMetrics($otherCustomerId);
        self::assertSame([], $other['wash_history']);
    }

    private function customerId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }
}
