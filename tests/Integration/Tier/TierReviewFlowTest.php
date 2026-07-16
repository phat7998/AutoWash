<?php

declare(strict_types=1);

namespace Tests\Integration\Tier;

use App\Controllers\AdminTierReviewController;
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
use App\Exceptions\MonthlyReviewAlreadyCompletedException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\TierRepository;
use App\Services\TierReviewPolicy;
use App\Services\TierReviewService;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class TierReviewFlowTest extends TestCase
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
        self::$database->exec('DELETE FROM tier_histories');
        self::$database->exec('DELETE FROM monthly_review_runs');
    }

    public function testReviewUpgradesDowngradesHoldsSnapshotsAndKeepsPointBalance(): void
    {
        $this->setScenario('0900000002', 'MEMBER', '800000.00', 5, 321);
        $this->setScenario('0900000003', 'SILVER', '300000.00', 2, 222);
        $this->setScenario('0900000004', 'GOLD', '1500000.00', 1, 123);
        $this->setScenario('0900000005', 'PLATINUM', '1500000.00', 8, 999);

        $result = $this->service()->run(
            '2026-06',
            new DateTimeImmutable('2026-07-01 00:05:00', $this->timezone())
        );

        self::assertSame([
            'review_period' => '2026-06',
            'processed_users' => 4,
            'status' => 'completed',
        ], $result);
        $this->assertCustomer('0900000002', 'GOLD', 321);
        $this->assertCustomer('0900000003', 'SILVER', 222);
        $this->assertCustomer('0900000004', 'MEMBER', 123);
        $this->assertCustomer('0900000005', 'PLATINUM', 999);
        self::assertSame(4, $this->historyCount('2026-06'));
        self::assertSame('800000.00', $this->history('0900000002', '2026-06')['monthly_spend_snapshot']);
        self::assertSame(5, (int) $this->history('0900000002', '2026-06')['monthly_visits_snapshot']);
        self::assertSame('completed', $this->reviewRun('2026-06')['status']);

        self::$seeder->seed();
        self::assertSame('GOLD', $this->customer('0900000002')['tier_code']);
        self::assertSame('0.00', $this->customer('0900000002')['monthly_spend']);
        self::assertSame(0, (int) $this->customer('0900000002')['monthly_visits']);

        $this->expectException(MonthlyReviewAlreadyCompletedException::class);
        $this->service()->run('2026-06');
    }

    public function testFailedRunResumesOnlyUnprocessedCustomers(): void
    {
        foreach (['0900000002', '0900000003', '0900000004', '0900000005'] as $phone) {
            $this->setScenario($phone, 'MEMBER', '300000.00', 2, 50);
        }

        self::$database->exec('UPDATE tiers SET is_active = FALSE');

        try {
            $this->service()->run('2026-05');
            self::fail('Thiếu cấu hình tier phải làm kỳ xét hạng thất bại.');
        } catch (\InvalidArgumentException) {
            self::assertSame('failed', $this->reviewRun('2026-05')['status']);
            self::assertSame(0, $this->historyCount('2026-05'));
        }

        self::$database->exec('UPDATE tiers SET is_active = TRUE');
        $this->simulateCommittedCustomerBeforeFailure('0900000002', '2026-05', 'SILVER');
        $result = $this->service()->run('2026-05');
        self::assertSame(4, $result['processed_users']);
        self::assertSame(4, $this->historyCount('2026-05'));
        self::assertSame('completed', $this->reviewRun('2026-05')['status']);
        self::assertSame('SILVER', $this->customer('0900000002')['tier_code']);
        self::assertSame('0.00', $this->customer('0900000002')['monthly_spend']);
    }

    public function testAdminResultRouteRequiresRoleAndEscapesHistory(): void
    {
        $this->setScenario('0900000002', 'MEMBER', '300000.00', 2, 50);
        $this->service()->run('2026-04');
        self::$database->exec(
            "UPDATE users SET full_name = '<script>alert(11)</script>' WHERE phone = '0900000002'"
        );

        $adminApp = $this->application([
            'id' => $this->userId('0900000001'),
            'full_name' => 'Quản trị viên AutoWash',
            'role' => 'admin',
        ]);
        $response = $adminApp->handle(new Request('GET', '/admin/xet-hang'));
        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('&lt;script&gt;alert(11)&lt;/script&gt;', $response->body());
        self::assertStringNotContainsString('<script>alert(11)</script>', $response->body());

        $customerApp = $this->application([
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng',
            'role' => 'customer',
        ]);
        self::assertSame(
            403,
            $customerApp->handle(new Request('GET', '/admin/xet-hang'))->statusCode()
        );
    }

    private function service(): TierReviewService
    {
        return new TierReviewService(
            new TierRepository(self::$database),
            new TierReviewPolicy($this->timezone()),
            $this->timezone()
        );
    }

    /** @param array<string, mixed> $authUser */
    private function application(array $authUser): Application
    {
        $sessionData = ['auth_user' => $authUser];
        $session = new Session($sessionData);
        $tokens = new CsrfTokenManager($session);
        $view = new View(dirname(__DIR__, 3) . '/resources/views');
        $router = new Router();
        $router->middleware(new CsrfMiddleware($tokens));
        $register = require dirname(__DIR__, 3) . '/routes/web.php';
        $factory = fn (): AdminTierReviewController => new AdminTierReviewController(
            $this->service(),
            $view,
            $session,
            $tokens
        );
        $register(
            router: $router,
            view: $view,
            session: $session,
            tokens: $tokens,
            authControllerFactory: static fn (): never => throw new \RuntimeException('Không cần AuthController.'),
            adminTierReviewControllerFactory: $factory
        );
        $log = sys_get_temp_dir() . '/autowash-tier-http-' . bin2hex(random_bytes(6)) . '.log';

        return new Application(
            $router,
            new ErrorHandler($view, new Logger($log, $this->timezone()), false)
        );
    }

    private function setScenario(
        string $phone,
        string $tierCode,
        string $monthlySpend,
        int $monthlyVisits,
        int $pointBalance
    ): void {
        $statement = self::$database->prepare(
            <<<'SQL'
            UPDATE users
            SET current_tier_id = (SELECT id FROM tiers WHERE code = :tier_code),
                monthly_spend = :monthly_spend,
                monthly_visits = :monthly_visits,
                point_balance = :point_balance
            WHERE phone = :phone
            SQL
        );
        $statement->execute([
            'tier_code' => $tierCode,
            'monthly_spend' => $monthlySpend,
            'monthly_visits' => $monthlyVisits,
            'point_balance' => $pointBalance,
            'phone' => $phone,
        ]);
    }

    private function assertCustomer(string $phone, string $tierCode, int $pointBalance): void
    {
        $customer = $this->customer($phone);
        self::assertSame($tierCode, $customer['tier_code']);
        self::assertSame('0.00', $customer['monthly_spend']);
        self::assertSame(0, (int) $customer['monthly_visits']);
        self::assertSame($pointBalance, (int) $customer['point_balance']);
    }

    /** @return array<string, mixed> */
    private function customer(string $phone): array
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            SELECT users.monthly_spend, users.monthly_visits, users.point_balance,
                tiers.code AS tier_code
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.phone = :phone
            SQL
        );
        $statement->execute(['phone' => $phone]);
        $row = $statement->fetch();
        self::assertIsArray($row);

        return $row;
    }

    /** @return array<string, mixed> */
    private function history(string $phone, string $period): array
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            SELECT tier_histories.*
            FROM tier_histories
            INNER JOIN users ON users.id = tier_histories.user_id
            WHERE users.phone = :phone AND tier_histories.review_period = :period
            SQL
        );
        $statement->execute(['phone' => $phone, 'period' => $period]);
        $row = $statement->fetch();
        self::assertIsArray($row);

        return $row;
    }

    /** @return array<string, mixed> */
    private function reviewRun(string $period): array
    {
        $statement = self::$database->prepare(
            'SELECT * FROM monthly_review_runs WHERE review_period = :period'
        );
        $statement->execute(['period' => $period]);
        $row = $statement->fetch();
        self::assertIsArray($row);

        return $row;
    }

    private function historyCount(string $period): int
    {
        $statement = self::$database->prepare(
            'SELECT COUNT(*) FROM tier_histories WHERE review_period = :period'
        );
        $statement->execute(['period' => $period]);

        return (int) $statement->fetchColumn();
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function simulateCommittedCustomerBeforeFailure(
        string $phone,
        string $period,
        string $newTierCode
    ): void {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO tier_histories (
                user_id, old_tier_id, new_tier_id, review_period,
                monthly_spend_snapshot, monthly_visits_snapshot, reason
            )
            SELECT users.id, users.current_tier_id, new_tier.id, :period,
                users.monthly_spend, users.monthly_visits, 'Snapshot recovery kiểm thử.'
            FROM users
            INNER JOIN tiers AS new_tier ON new_tier.code = :new_tier_code
            WHERE users.phone = :phone
            SQL
        );
        $statement->execute([
            'period' => $period,
            'new_tier_code' => $newTierCode,
            'phone' => $phone,
        ]);
        $update = self::$database->prepare(
            <<<'SQL'
            UPDATE users
            SET current_tier_id = (SELECT id FROM tiers WHERE code = :new_tier_code),
                monthly_spend = 0, monthly_visits = 0
            WHERE phone = :phone
            SQL
        );
        $update->execute(['new_tier_code' => $newTierCode, 'phone' => $phone]);
        self::$database->prepare(
            'UPDATE monthly_review_runs SET processed_users = 1 WHERE review_period = :period'
        )->execute(['period' => $period]);
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Ho_Chi_Minh');
    }
}
