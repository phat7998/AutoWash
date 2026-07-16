<?php

declare(strict_types=1);

namespace Tests\Integration\Reward;

use App\Controllers\AdminRewardController;
use App\Controllers\RewardController;
use App\Core\Application;
use App\Core\CsrfTokenManager;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\RewardNotEligibleException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\LoyaltyTransactionRepository;
use App\Repositories\RewardRepository;
use App\Repositories\ResearchEventRepository;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyService;
use App\Services\RewardService;
use App\Services\ResearchEventService;
use App\Validation\LoyaltyAdjustmentValidator;
use App\Validation\RewardValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class RewardFlowTest extends TestCase
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
        self::$database->exec('DELETE FROM loyalty_allocations');
        self::$database->exec('DELETE FROM reward_redemptions');
        self::$database->exec('UPDATE loyalty_transactions SET source_transaction_id = NULL');
        self::$database->exec('DELETE FROM loyalty_transactions');
        self::$database->exec(
            "UPDATE users SET point_balance = 0, monthly_spend = 0, monthly_visits = 0 "
            . "WHERE role = 'customer'"
        );
    }

    public function testAdjustmentCreditIsNonExpiringAndCanFundRedemption(): void
    {
        $userId = $this->userId('0900000002');
        $service = $this->loyalty();
        $service->adjust($this->userId('0900000001'), (string) $userId, '150', 'Credit kiểm thử.');
        $credit = $this->latestTransaction('adjust_credit');
        self::assertSame(150, (int) $credit['remaining_points']);
        self::assertNull($credit['expires_at']);

        $redemptionId = $this->rewards()->redeem($userId, $this->rewardId('DISCOUNT_10K'));
        self::assertGreaterThan(0, $redemptionId);
        self::assertSame(50, $this->balance($userId));
        self::assertSame(50, (int) $this->transaction((int) $credit['id'])['remaining_points']);
        $debit = $this->latestTransaction('redeem');
        self::assertSame(100, $this->allocationTotal((int) $debit['id']));
        $this->assertConsistent($userId);
    }

    public function testRedeemUsesExpiringEarnBeforeAdjustmentCreditsAndFifoForNonExpiringLots(): void
    {
        $userId = $this->userId('0900000002');
        $earnId = $this->insertCredit($userId, 'earn', 60, '2030-01-01 08:00:00', '2031-01-01 08:00:00');
        $firstAdjust = $this->insertCredit($userId, 'adjust_credit', 60, '2030-02-01 08:00:00');
        $secondAdjust = $this->insertCredit($userId, 'adjust_credit', 80, '2030-03-01 08:00:00');
        $this->setBalanceFromLedger($userId);

        $this->rewards()->redeem(
            $userId,
            $this->rewardId('DISCOUNT_10K'),
            new DateTimeImmutable('2030-04-01 08:00:00', $this->timezone())
        );
        self::assertSame(0, (int) $this->transaction($earnId)['remaining_points']);
        self::assertSame(20, (int) $this->transaction($firstAdjust)['remaining_points']);
        self::assertSame(80, (int) $this->transaction($secondAdjust)['remaining_points']);

        $this->rewards()->redeem(
            $userId,
            $this->rewardId('DISCOUNT_10K'),
            new DateTimeImmutable('2030-04-02 08:00:00', $this->timezone())
        );
        self::assertSame(0, (int) $this->transaction($firstAdjust)['remaining_points']);
        self::assertSame(0, (int) $this->transaction($secondAdjust)['remaining_points']);
        self::assertSame(0, $this->balance($userId));
        $this->assertConsistent($userId);
    }

    public function testNegativeAdjustmentAllocatesLotsAndExpiryOnlyConsumesRemainderOnce(): void
    {
        $userId = $this->userId('0900000002');
        $lotId = $this->insertCredit(
            $userId,
            'earn',
            100,
            '2025-01-01 08:00:00',
            '2030-01-01 08:00:00'
        );
        $this->setBalanceFromLedger($userId);
        $this->loyalty()->adjust(
            $this->userId('0900000001'),
            (string) $userId,
            '-30',
            'Giảm điểm đúng FEFO.'
        );
        $adjustment = $this->latestTransaction('adjust_debit');
        self::assertSame(30, $this->allocationTotal((int) $adjustment['id']));
        self::assertSame(70, (int) $this->transaction($lotId)['remaining_points']);

        $result = $this->loyalty()->expirePoints(
            new DateTimeImmutable('2030-01-01 08:00:00', $this->timezone())
        );
        self::assertSame(['expired_lots' => 1, 'expired_points' => 70], $result);
        self::assertSame(['expired_lots' => 0, 'expired_points' => 0], $this->loyalty()->expirePoints(
            new DateTimeImmutable('2030-01-02 08:00:00', $this->timezone())
        ));
        self::assertSame(0, $this->balance($userId));
        self::assertSame(0, (int) $this->transaction($lotId)['remaining_points']);
        $this->assertConsistent($userId);
    }

    public function testTierRestrictionAndInsufficientPointsRollbackRedemption(): void
    {
        $memberId = $this->userId('0900000002');
        self::$database->exec(
            "UPDATE rewards SET minimum_tier_id = (SELECT id FROM tiers WHERE code = 'GOLD') "
            . "WHERE code = 'DISCOUNT_10K'"
        );
        $this->insertCredit($memberId, 'adjust_credit', 200, '2030-01-01 08:00:00');
        $this->setBalanceFromLedger($memberId);

        try {
            $this->rewards()->redeem($memberId, $this->rewardId('DISCOUNT_10K'));
            self::fail('Member không được đổi reward yêu cầu Gold.');
        } catch (RewardNotEligibleException) {
            self::assertSame(0, $this->redemptionCount($memberId));
        }

        self::$database->exec("UPDATE rewards SET minimum_tier_id = NULL WHERE code = 'DISCOUNT_10K'");
        self::$database->exec('DELETE FROM loyalty_transactions');
        self::$database->exec('UPDATE users SET point_balance = 0 WHERE id = ' . $memberId);

        try {
            $this->rewards()->redeem($memberId, $this->rewardId('DISCOUNT_10K'));
            self::fail('Thiếu điểm phải rollback redemption.');
        } catch (InsufficientPointsException) {
            self::assertSame(0, $this->redemptionCount($memberId));
        }
    }

    public function testConcurrentRedeemCannotOverspend(): void
    {
        $userId = $this->userId('0900000002');
        $this->insertCredit($userId, 'adjust_credit', 150, '2025-01-01 08:00:00');
        $this->setBalanceFromLedger($userId);
        $token = bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir() . '/autowash-reward-barrier-' . $token;
        $first = sys_get_temp_dir() . '/autowash-reward-first-' . $token;
        $second = sys_get_temp_dir() . '/autowash-reward-second-' . $token;
        $worker = dirname(__DIR__, 2) . '/Support/RewardRedemptionConcurrencyWorker.php';
        $processes = [
            $this->startWorker($worker, $barrier, $first, $userId, $this->rewardId('DISCOUNT_10K')),
            $this->startWorker($worker, $barrier, $second, $userId, $this->rewardId('DISCOUNT_10K')),
        ];
        touch($barrier);

        foreach ($processes as $process) {
            self::assertSame(0, proc_close($process));
        }

        $results = [trim((string) file_get_contents($first)), trim((string) file_get_contents($second))];
        sort($results);
        self::assertSame(['insufficient', 'success'], $results);
        self::assertSame(50, $this->balance($userId));
        self::assertSame(1, $this->redemptionCount($userId));
        $this->assertConsistent($userId);

        foreach ([$barrier, $first, $second] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testAdminCrudKeepsVehicleRestrictionsAndHistorySafe(): void
    {
        $service = $this->rewards();
        $options = $service->formOptions();
        $serviceId = (string) $options['services'][0]['id'];
        $tierId = (string) $options['tiers'][1]['id'];
        $vehicleId = (string) $options['vehicle_types'][0]['id'];
        $rewardId = $service->create(
            'TEST_REWARD',
            'Reward <b>kiểm thử</b>',
            'free_service',
            '120',
            '0',
            $serviceId,
            $tierId,
            '45',
            [$vehicleId]
        );
        $reward = $service->reward($rewardId);
        self::assertSame('TEST_REWARD', $reward['code']);
        self::assertSame([(int) $vehicleId], $reward['vehicle_type_ids']);

        $service->update(
            $rewardId,
            'TEST_REWARD',
            'Reward đã sửa',
            'fixed_discount',
            '130',
            '15000',
            '',
            '',
            '60',
            []
        );
        self::assertSame(130, (int) $service->reward($rewardId)['points_cost']);
        $service->setActive($rewardId, false);
        self::assertFalse((bool) $service->reward($rewardId)['is_active']);
        $service->setActive($rewardId, true);
        self::assertTrue((bool) $service->reward($rewardId)['is_active']);
    }

    public function testHttpRoutesEnforceRoleCsrfOwnershipAndEscaping(): void
    {
        $customerId = $this->userId('0900000002');
        self::$database->exec(
            "UPDATE rewards SET name = '<script>alert(10)</script>' WHERE code = 'DISCOUNT_10K'"
        );
        [$customerApp] = $this->application(['auth_user' => [
            'id' => $customerId,
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]]);
        $response = $customerApp->handle(new Request('GET', '/doi-thuong'));
        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('&lt;script&gt;alert(10)&lt;/script&gt;', $response->body());
        self::assertStringNotContainsString('<script>alert(10)</script>', $response->body());
        self::assertSame(403, $customerApp->handle(new Request('GET', '/admin/reward'))->statusCode());
        self::assertSame(
            419,
            $customerApp->handle(new Request('POST', '/doi-thuong/1'))->statusCode()
        );

        [$adminApp] = $this->application(['auth_user' => [
            'id' => $this->userId('0900000001'),
            'full_name' => 'Quản trị viên AutoWash',
            'role' => 'admin',
        ]]);
        self::assertSame(200, $adminApp->handle(new Request('GET', '/admin/reward'))->statusCode());
        self::assertSame(403, $adminApp->handle(new Request('GET', '/doi-thuong'))->statusCode());
    }

    private function loyalty(): LoyaltyService
    {
        return new LoyaltyService(
            new LoyaltyTransactionRepository(self::$database),
            new LoyaltyPointCalculator(10_000),
            new LoyaltyAdjustmentValidator(),
            new LoyaltyDebitAllocator(),
            new LoyaltyExpirationPolicy($this->timezone()),
            $this->timezone(),
            new ResearchEventService(new ResearchEventRepository(self::$database))
        );
    }

    private function rewards(): RewardService
    {
        return new RewardService(
            new RewardRepository(self::$database),
            $this->loyalty(),
            new RewardValidator(),
            $this->timezone(),
            new ResearchEventService(new ResearchEventRepository(self::$database))
        );
    }

    /** @param array<string, mixed> $sessionData @return array{Application, CsrfTokenManager} */
    private function application(array $sessionData): array
    {
        $session = new Session($sessionData);
        $tokens = new CsrfTokenManager($session);
        $view = new View(dirname(__DIR__, 3) . '/resources/views');
        $router = new Router();
        $router->middleware(new CsrfMiddleware($tokens));
        $register = require dirname(__DIR__, 3) . '/routes/web.php';
        $rewardFactory = fn (): RewardController => new RewardController(
            $this->rewards(),
            $view,
            $session,
            $tokens
        );
        $adminRewardFactory = fn (): AdminRewardController => new AdminRewardController(
            $this->rewards(),
            $view,
            $session,
            $tokens
        );
        $register(
            $router,
            $view,
            $session,
            $tokens,
            static fn (): never => throw new \RuntimeException('Không cần AuthController.'),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $rewardFactory,
            $adminRewardFactory
        );
        $log = sys_get_temp_dir() . '/autowash-reward-http-' . bin2hex(random_bytes(6)) . '.log';

        return [new Application(
            $router,
            new ErrorHandler($view, new Logger($log, $this->timezone()), false)
        ), $tokens];
    }

    private function insertCredit(
        int $userId,
        string $type,
        int $points,
        string $createdAt,
        ?string $expiresAt = null
    ): int {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                description, earned_at, expires_at, created_at, updated_at
            ) VALUES (
                :user_id, :type, :points, :remaining, 'test_credit', :source_id,
                'Credit lot kiểm thử.', :earned_at, :expires_at, :created_at, :updated_at
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'points' => $points,
            'remaining' => $points,
            'source_id' => random_int(1, PHP_INT_MAX),
            'earned_at' => $type === 'earn' ? $createdAt : null,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return (int) self::$database->lastInsertId();
    }

    private function setBalanceFromLedger(int $userId): void
    {
        self::$database->exec(
            'UPDATE users SET point_balance = (SELECT COALESCE(SUM(points_delta), 0) '
            . 'FROM loyalty_transactions WHERE user_id = ' . $userId . ') WHERE id = ' . $userId
        );
    }

    private function assertConsistent(int $userId): void
    {
        $report = array_values(array_filter(
            $this->loyalty()->reconciliationReport(),
            static fn (array $row): bool => (int) $row['user_id'] === $userId
        ));
        self::assertCount(1, $report);
        self::assertTrue($report[0]['matches']);

        foreach ($this->loyalty()->debitAllocationReport() as $debit) {
            self::assertTrue($debit['matches']);
        }
    }

    /** @return array<string, mixed> */
    private function latestTransaction(string $type): array
    {
        $statement = self::$database->prepare(
            'SELECT * FROM loyalty_transactions WHERE type = :type ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['type' => $type]);
        $row = $statement->fetch();
        self::assertIsArray($row);

        return $row;
    }

    /** @return array<string, mixed> */
    private function transaction(int $id): array
    {
        $statement = self::$database->prepare('SELECT * FROM loyalty_transactions WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        self::assertIsArray($row);

        return $row;
    }

    private function allocationTotal(int $debitId): int
    {
        $statement = self::$database->prepare(
            'SELECT COALESCE(SUM(allocated_points), 0) FROM loyalty_allocations WHERE debit_transaction_id = :id'
        );
        $statement->execute(['id' => $debitId]);

        return (int) $statement->fetchColumn();
    }

    private function balance(int $userId): int
    {
        return (int) self::$database->query('SELECT point_balance FROM users WHERE id = ' . $userId)
            ->fetchColumn();
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function rewardId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM rewards WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function redemptionCount(int $userId): int
    {
        return (int) self::$database->query(
            'SELECT COUNT(*) FROM reward_redemptions WHERE user_id = ' . $userId
        )->fetchColumn();
    }

    /** @return resource */
    private function startWorker(
        string $worker,
        string $barrier,
        string $result,
        int $userId,
        int $rewardId
    ) {
        $process = proc_open([
            PHP_BINARY, $worker, $barrier, $result, (string) $userId, (string) $rewardId,
        ], [], $pipes, dirname(__DIR__, 3));
        self::assertIsResource($process);

        return $process;
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Ho_Chi_Minh');
    }
}
