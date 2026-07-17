<?php

declare(strict_types=1);

namespace Tests\Integration\Promotion;

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Repositories\PromotionRepository;
use App\Repositories\TierConfigurationRepository;
use App\Services\PromotionService;
use App\Services\TierConfigurationService;
use App\Validation\PromotionValidator;
use App\Validation\TierConfigurationValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class PromotionConfigurationFlowTest extends TestCase
{
    private static PDO $database;
    private static DatabaseSeeder $seeder;

    public static function setUpBeforeClass(): void
    {
        if (getenv('AUTOWASH_DB_TESTS') !== '1') {
            self::markTestSkipped('Đặt AUTOWASH_DB_TESTS=1 để chạy integration test MySQL.');
        }
        $root = dirname(__DIR__, 3);
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
        (new MigrationRunner(self::$database, $root . '/database/migrations'))->migrate();
        self::$seeder = new DatabaseSeeder(self::$database, $root . '/database/seeds/base.php');
        self::$seeder->seed();
    }

    public static function tearDownAfterClass(): void
    {
        Database::disconnect();
    }

    protected function setUp(): void
    {
        self::$seeder->seed();
        self::$database->exec("DELETE FROM promotion_tiers WHERE promotion_id IN "
            . "(SELECT id FROM promotions WHERE code LIKE 'TEST_PRO_%')");
        self::$database->exec("DELETE FROM promotion_services WHERE promotion_id IN "
            . "(SELECT id FROM promotions WHERE code LIKE 'TEST_PRO_%')");
        self::$database->exec("DELETE FROM promotion_vehicle_types WHERE promotion_id IN "
            . "(SELECT id FROM promotions WHERE code LIKE 'TEST_PRO_%')");
        self::$database->exec("DELETE FROM promotions WHERE code LIKE 'TEST_PRO_%'");
        self::$database->exec("DELETE FROM tier_perks WHERE tier_id IN "
            . "(SELECT id FROM tiers WHERE code LIKE 'TEST_TIER_%')");
        self::$database->exec("DELETE FROM tiers WHERE code LIKE 'TEST_TIER_%'");
    }

    public function testAdminCanConfigureTierAndPerkWithAuditAndUniqueRankGuard(): void
    {
        $service = new TierConfigurationService(
            new TierConfigurationRepository(self::$database),
            new TierConfigurationValidator()
        );
        $adminId = $this->id('users', 'phone', '0900000001');
        $tierId = $service->saveTier(null, $adminId, [
            'code' => 'TEST_TIER_VIP', 'name' => 'Hạng kiểm thử', 'rank_order' => '20',
            'booking_window_days' => '20', 'min_monthly_spend' => '2000000',
            'min_monthly_visits' => '10', 'point_rate' => '2.00',
        ]);
        $service->savePerk(null, $adminId, [
            'tier_id' => (string) $tierId, 'perk_type' => 'percentage_discount',
            'value' => '12.50', 'service_id' => '',
        ]);
        $service->setTierActive($tierId, false, $adminId);

        self::assertSame(0, (int) $this->scalar('SELECT is_active FROM tiers WHERE id = ' . $tierId));
        self::assertSame(1, (int) $this->scalar(
            'SELECT COUNT(*) FROM tier_perks WHERE tier_id = ' . $tierId
        ));
        self::assertGreaterThanOrEqual(3, (int) $this->scalar(
            "SELECT COUNT(*) FROM audit_logs WHERE actor_user_id = {$adminId} "
            . "AND target_type IN ('tier', 'tier_perk')"
        ));

        $this->expectException(DuplicateCatalogException::class);
        $service->saveTier(null, $adminId, [
            'code' => 'TEST_TIER_DUP', 'name' => 'Trùng rank', 'rank_order' => '20',
            'booking_window_days' => '1', 'min_monthly_spend' => '0',
            'min_monthly_visits' => '0', 'point_rate' => '1.00',
        ]);
    }

    public function testPromotionSilverPlusPersistsTargetsAndRejectsInvalidPeriod(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $service = new PromotionService(
            new PromotionRepository(self::$database),
            $timezone,
            new PromotionValidator($timezone)
        );
        $adminId = $this->id('users', 'phone', '0900000001');
        $silver = $this->id('tiers', 'code', 'SILVER');
        $gold = $this->id('tiers', 'code', 'GOLD');
        $platinum = $this->id('tiers', 'code', 'PLATINUM');
        $start = new DateTimeImmutable('+1 day', $timezone);
        $end = $start->modify('+10 days');
        $promotionId = $service->savePromotion(null, $adminId, [
            'code' => 'TEST_PRO_SILVER_PLUS', 'name' => 'Silver+ kiểm thử', 'description' => '',
            'discount_type' => 'percentage', 'discount_value' => '15', 'max_discount' => '50000',
            'minimum_order_value' => '100000', 'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $end->format('Y-m-d\TH:i'), 'usage_limit' => '10',
            'per_user_limit' => '1',
            'tier_ids' => array_map('strval', [$silver, $gold, $platinum]),
            'service_ids' => [], 'vehicle_type_ids' => [],
        ]);

        self::assertSame(3, (int) $this->scalar(
            'SELECT COUNT(*) FROM promotion_tiers WHERE promotion_id = ' . $promotionId
        ));
        self::assertSame(1, (int) $this->scalar(
            "SELECT COUNT(*) FROM audit_logs WHERE action = 'promotion_saved' "
            . 'AND target_id = ' . $promotionId
        ));

        $this->expectException(ValidationException::class);
        $service->savePromotion(null, $adminId, [
            'code' => 'TEST_PRO_BAD_TIME', 'name' => 'Sai thời gian', 'description' => '',
            'discount_type' => 'fixed', 'discount_value' => '10000', 'max_discount' => '',
            'minimum_order_value' => '0', 'start_at' => $end->format('Y-m-d\TH:i'),
            'end_at' => $start->format('Y-m-d\TH:i'), 'usage_limit' => '',
            'per_user_limit' => '', 'tier_ids' => [], 'service_ids' => [], 'vehicle_type_ids' => [],
        ]);
    }

    public function testCheckoutEnforcesPromotionBoundariesScopesAndRewardOwnership(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $at = new DateTimeImmutable('now', $timezone);
        $promotionId = $this->id('promotions', 'code', 'SILVER_PLUS_10');
        $statement = self::$database->prepare(
            <<<'SQL'
            UPDATE promotions
            SET is_active = TRUE, start_at = :start_at, end_at = :end_at,
                minimum_order_value = 100000, usage_limit = 1000, per_user_limit = 5
            WHERE id = :id
            SQL
        );
        $statement->execute([
            'start_at' => $at->format('Y-m-d H:i:s'),
            'end_at' => $at->modify('+1 hour')->format('Y-m-d H:i:s'),
            'id' => $promotionId,
        ]);
        $standardServiceId = $this->id('services', 'code', 'STANDARD_WASH');
        $premiumServiceId = $this->id('services', 'code', 'PREMIUM_WASH');
        $engineServiceId = $this->id('services', 'code', 'ENGINE_CLEAN');
        $carTypeId = $this->id('vehicle_types', 'code', 'car');
        $busTypeId = $this->id('vehicle_types', 'code', 'bus');
        $tierUsers = [
            'SILVER' => '0900000003',
            'GOLD' => '0900000004',
            'PLATINUM' => '0900000005',
        ];

        foreach ($tierUsers as $tierCode => $phone) {
            $benefits = $this->checkoutBenefits(
                $this->id('users', 'phone', $phone),
                $this->id('tiers', 'code', $tierCode),
                $carTypeId,
                [$standardServiceId],
                '100000.00',
                null,
                $at
            );
            self::assertCount(1, $benefits['promotions']);
        }

        $memberBenefits = $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000002'),
            $this->id('tiers', 'code', 'MEMBER'),
            $carTypeId,
            [$standardServiceId],
            '100000.00',
            null,
            $at
        );
        self::assertSame([], $memberBenefits['promotions']);
        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$standardServiceId],
            '99999.99',
            null,
            $at
        )['promotions']);
        self::$database->exec(
            "INSERT INTO promotion_services (promotion_id, service_id) VALUES ({$promotionId}, {$standardServiceId})"
        );
        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$premiumServiceId],
            '200000.00',
            null,
            $at
        )['promotions']);
        self::$database->exec(
            "INSERT INTO promotion_services (promotion_id, service_id) VALUES ({$promotionId}, {$premiumServiceId})"
        );
        self::assertCount(1, $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$premiumServiceId],
            '200000.00',
            null,
            $at
        )['promotions']);
        self::assertCount(1, $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$standardServiceId],
            '100000.00',
            null,
            $at->modify('+1 hour')
        )['promotions']);
        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$standardServiceId],
            '100000.00',
            null,
            $at->modify('-1 second')
        )['promotions']);
        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$standardServiceId],
            '100000.00',
            null,
            $at->modify('+1 hour 1 second')
        )['promotions']);

        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $carTypeId,
            [$engineServiceId],
            '100000.00',
            null,
            $at
        )['promotions']);
        self::$database->exec(
            "INSERT INTO promotion_vehicle_types (promotion_id, vehicle_type_id) "
            . "VALUES ({$promotionId}, {$carTypeId})"
        );
        self::assertSame([], $this->checkoutBenefits(
            $this->id('users', 'phone', '0900000003'),
            $this->id('tiers', 'code', 'SILVER'),
            $busTypeId,
            [$standardServiceId],
            '100000.00',
            null,
            $at
        )['promotions']);

        $silverId = $this->id('users', 'phone', '0900000003');
        $goldId = $this->id('users', 'phone', '0900000004');
        $rewardId = $this->id('rewards', 'code', 'DISCOUNT_10K');
        $foreignRedemptionId = $this->insertRedemption($silverId, $rewardId);
        try {
            $this->checkoutBenefits(
                $goldId,
                $this->id('tiers', 'code', 'GOLD'),
                $carTypeId,
                [$standardServiceId],
                '100000.00',
                $foreignRedemptionId,
                $at
            );
            self::fail('Không được dùng reward redemption của khách hàng khác.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('reward_redemption_id', $exception->errors());
        }

        self::$database->exec(
            "INSERT INTO reward_vehicle_types (reward_id, vehicle_type_id) VALUES ({$rewardId}, {$carTypeId})"
        );
        $goldRedemptionId = $this->insertRedemption($goldId, $rewardId);
        try {
            $this->checkoutBenefits(
                $goldId,
                $this->id('tiers', 'code', 'GOLD'),
                $busTypeId,
                [$standardServiceId],
                '100000.00',
                $goldRedemptionId,
                $at
            );
            self::fail('Reward giới hạn loại xe phải bị từ chối ở backend.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('reward_redemption_id', $exception->errors());
        }
    }

    /** @return array<string, mixed> */
    private function checkoutBenefits(
        int $userId,
        int $tierId,
        int $vehicleTypeId,
        array $serviceIds,
        string $subtotal,
        ?int $redemptionId,
        DateTimeImmutable $at
    ): array {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $service = new PromotionService(new PromotionRepository(self::$database), $timezone);
        self::$database->beginTransaction();
        try {
            return $service->checkoutBenefits(
                $userId,
                $tierId,
                (int) $this->scalar('SELECT rank_order FROM tiers WHERE id = ' . $tierId),
                $vehicleTypeId,
                $serviceIds,
                $subtotal,
                $redemptionId,
                $at
            );
        } finally {
            self::$database->rollBack();
        }
    }

    private function insertRedemption(int $userId, int $rewardId): int
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO reward_redemptions (
                user_id, reward_id, points_spent, status, redeemed_at, expires_at
            ) VALUES (
                :user_id, :reward_id, 100, 'available', CURRENT_TIMESTAMP,
                DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
            )
            SQL
        );
        $statement->execute(['user_id' => $userId, 'reward_id' => $rewardId]);

        return (int) self::$database->lastInsertId();
    }

    private function id(string $table, string $column, string $value): int
    {
        $allowed = [
            'users' => ['phone'],
            'tiers' => ['code'],
            'promotions' => ['code'],
            'services' => ['code'],
            'vehicle_types' => ['code'],
            'rewards' => ['code'],
        ];
        self::assertContains($column, $allowed[$table]);
        $statement = self::$database->prepare("SELECT id FROM {$table} WHERE {$column} = :value");
        $statement->execute(['value' => $value]);
        return (int) $statement->fetchColumn();
    }

    private function scalar(string $sql): mixed
    {
        return self::$database->query($sql)->fetchColumn();
    }
}
