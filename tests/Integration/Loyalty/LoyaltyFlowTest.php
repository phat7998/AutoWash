<?php

declare(strict_types=1);

namespace Tests\Integration\Loyalty;

use App\Controllers\AdminLoyaltyController;
use App\Controllers\DashboardController;
use App\Controllers\LoyaltyController;
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
use App\Exceptions\InvalidBookingTransitionException;
use App\Exceptions\ValidationException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\BookingRepository;
use App\Repositories\LoyaltyTransactionRepository;
use App\Repositories\ResearchEventRepository;
use App\Repositories\ResearchReportRepository;
use App\Services\BookingLifecyclePolicy;
use App\Services\BookingResourceCalculator;
use App\Services\BookingService;
use App\Services\BookingWindowPolicy;
use App\Services\DashboardService;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Services\LoyaltyService;
use App\Services\ResearchEventService;
use App\Services\PriceCalculator;
use App\Validation\BookingLifecycleValidator;
use App\Validation\BookingValidator;
use App\Validation\LoyaltyAdjustmentValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class LoyaltyFlowTest extends TestCase
{
    private static PDO $database;
    private static DatabaseSeeder $seeder;
    private int $slotSequence = 0;

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
        self::$seeder = new DatabaseSeeder(self::$database, $projectRoot . '/database/seeds/base.php');
        self::$seeder->seed();
    }

    public static function tearDownAfterClass(): void
    {
        Database::disconnect();
    }

    protected function setUp(): void
    {
        self::$seeder->seed();
        $this->clearLoyaltyFixtures();
    }

    public function testCompletionAtomicallyWritesMetricsEarnBalanceMarkerAndResearchEvent(): void
    {
        $bookingId = $this->createConfirmedBooking('0900000004', '250000.00');
        $adminId = $this->userId('0900000001');

        $this->bookingService()->completeByAdmin($adminId, $bookingId);

        $booking = $this->booking($bookingId);
        $metrics = $this->metrics('0900000004');
        $earn = $this->earnForBooking($bookingId);
        self::assertSame('completed', $booking['status']);
        self::assertNotNull($booking['completed_at']);
        self::assertNotNull($booking['loyalty_processed_at']);
        self::assertSame('250000.00', $metrics['monthly_spend']);
        self::assertSame(1, $metrics['monthly_visits']);
        self::assertSame(31, $metrics['point_balance']);
        self::assertSame(31, (int) $earn['points_delta']);
        self::assertSame(31, (int) $earn['remaining_points']);
        self::assertNotNull($earn['earned_at']);
        self::assertNotNull($earn['expires_at']);
        self::assertSame(1, $this->eventCount('booking_completed:' . $bookingId));

        $report = $this->loyaltyService()->reconciliationReport();
        $ownerReport = $this->reportForUser($report, $this->userId('0900000004'));
        self::assertTrue($ownerReport['matches']);
        self::assertSame(31, $ownerReport['ledger_balance']);

        try {
            $this->bookingService()->completeByAdmin($adminId, $bookingId);
            self::fail('Complete lặp phải bị từ chối trước khi cộng lại loyalty.');
        } catch (InvalidBookingTransitionException) {
            self::assertTrue(true);
        }

        self::assertSame(1, $this->earnCount($bookingId));
        self::assertSame(31, $this->metrics('0900000004')['point_balance']);
        self::assertSame(1, $this->eventCount('booking_completed:' . $bookingId));
    }

    public function testZeroPriceCreatesIdempotencyMarkerWithoutInvalidZeroCredit(): void
    {
        $bookingId = $this->createConfirmedBooking('0900000002', '0.00');
        $this->bookingService()->completeByAdmin($this->userId('0900000001'), $bookingId);

        self::assertSame(0, $this->earnCount($bookingId));
        self::assertSame(1, $this->metrics('0900000002')['monthly_visits']);
        self::assertSame('0.00', $this->metrics('0900000002')['monthly_spend']);
        self::assertNotNull($this->booking($bookingId)['loyalty_processed_at']);
    }

    public function testLoyaltyInsertFailureRollsBackCompletedStatusAndAllMetrics(): void
    {
        $bookingId = $this->createConfirmedBooking('0900000003', '100000.00');
        $ownerId = $this->userId('0900000003');
        $this->insertConflictingEarn($ownerId, $bookingId);
        $before = $this->metrics('0900000003');

        try {
            $this->bookingService()->completeByAdmin($this->userId('0900000001'), $bookingId);
            self::fail('Unique conflict của earn phải rollback completion.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        $booking = $this->booking($bookingId);
        self::assertSame('confirmed', $booking['status']);
        self::assertNull($booking['completed_at']);
        self::assertNull($booking['loyalty_processed_at']);
        self::assertSame($before, $this->metrics('0900000003'));
        self::assertSame(0, $this->eventCount('booking_completed:' . $bookingId));
    }

    public function testCustomerSummaryHistoryAndExpiringPointsUseOnlyOwnerLedger(): void
    {
        $firstBooking = $this->createConfirmedBooking('0900000002', '100000.00');
        $secondBooking = $this->createConfirmedBooking('0900000003', '200000.00');
        $adminId = $this->userId('0900000001');
        $this->bookingService()->completeByAdmin($adminId, $firstBooking);
        $this->bookingService()->completeByAdmin($adminId, $secondBooking);
        self::$database->exec(
            'UPDATE loyalty_transactions SET expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 DAY) '
            . 'WHERE source_id = ' . $firstBooking . " AND source_type = 'booking'"
        );

        $history = $this->loyaltyService()->history($this->userId('0900000002'));
        self::assertSame(10, (int) $history['summary']['point_balance']);
        self::assertSame(10, (int) $history['summary']['expiring_points_30_days']);
        self::assertCount(1, $history['transactions']);
        self::assertSame($firstBooking, (int) $history['transactions'][0]['source_id']);
        self::assertSame('Cộng điểm', $history['transactions'][0]['type_label']);
    }

    public function testAdjustmentBoundariesAuditSourceOwnershipAndReconcile(): void
    {
        $service = $this->loyaltyService();
        $adminId = $this->userId('0900000001');
        $ownerId = $this->userId('0900000002');
        $otherId = $this->userId('0900000003');
        $positive = $service->adjust($adminId, (string) $ownerId, '100', 'Bù điểm chăm sóc khách hàng.');
        $service->adjust(
            $adminId,
            (string) $ownerId,
            '-40',
            'Sửa giao dịch cộng nhầm.',
            (string) $positive
        );
        $service->adjust($adminId, (string) $ownerId, '-60', 'Đưa số dư về đúng bằng 0.');
        self::assertSame(0, $this->metrics('0900000002')['point_balance']);

        try {
            $service->adjust($adminId, (string) $ownerId, '-1', 'Không được âm số dư.');
            self::fail('Adjustment vượt số dư phải bị từ chối, không clamp.');
        } catch (InsufficientPointsException) {
            self::assertTrue(true);
        }

        try {
            $service->adjust($adminId, (string) $ownerId, '10', '   ');
            self::fail('Adjustment thiếu reason phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('reason', $exception->errors());
        }

        $otherTransaction = $service->adjust(
            $adminId,
            (string) $otherId,
            '5',
            'Tạo giao dịch nguồn của khách khác.'
        );

        try {
            $service->adjust(
                $adminId,
                (string) $ownerId,
                '1',
                'Không được tham chiếu giao dịch chéo owner.',
                (string) $otherTransaction
            );
            self::fail('Giao dịch nguồn của khách khác phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('source_transaction_id', $exception->errors());
        }

        self::assertSame(3, $this->adjustmentCount($ownerId));
        self::assertSame(3, $this->adjustmentAuditCount($ownerId));
        self::assertTrue($this->reportForUser(
            $service->reconciliationReport(),
            $ownerId
        )['matches']);
    }

    public function testConcurrentNegativeAdjustmentsCannotOverspend(): void
    {
        $adminId = $this->userId('0900000001');
        $ownerId = $this->userId('0900000002');
        $this->loyaltyService()->adjust(
            $adminId,
            (string) $ownerId,
            '100',
            'Số dư ban đầu cho kiểm thử đồng thời.'
        );
        $token = bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir() . '/autowash-loyalty-barrier-' . $token;
        $resultOne = sys_get_temp_dir() . '/autowash-loyalty-result-one-' . $token;
        $resultTwo = sys_get_temp_dir() . '/autowash-loyalty-result-two-' . $token;
        $worker = dirname(__DIR__, 2) . '/Support/LoyaltyAdjustmentConcurrencyWorker.php';
        $processes = [
            $this->startWorker($worker, $barrier, $resultOne, $adminId, $ownerId, -80),
            $this->startWorker($worker, $barrier, $resultTwo, $adminId, $ownerId, -80),
        ];
        touch($barrier);

        foreach ($processes as $process) {
            self::assertSame(0, proc_close($process));
        }

        $results = [trim((string) file_get_contents($resultOne)), trim((string) file_get_contents($resultTwo))];
        sort($results);
        self::assertSame(['insufficient', 'success'], $results);
        self::assertSame(20, $this->metrics('0900000002')['point_balance']);
        self::assertTrue($this->reportForUser(
            $this->loyaltyService()->reconciliationReport(),
            $ownerId
        )['matches']);

        foreach ([$barrier, $resultOne, $resultTwo] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testHttpRoutesEnforceRoleCsrfAndEscapeLedgerContent(): void
    {
        $adminId = $this->userId('0900000001');
        $ownerId = $this->userId('0900000002');
        $otherId = $this->userId('0900000003');
        $this->loyaltyService()->adjust(
            $adminId,
            (string) $ownerId,
            '15',
            '<script>alert(9)</script>'
        );
        $this->loyaltyService()->adjust(
            $adminId,
            (string) $otherId,
            '99',
            'Giao dịch chỉ thuộc khách hàng khác.'
        );
        $this->insertLedgerDisplayFixture($ownerId, 'earn', 7, 'Cộng điểm kiểm thử hiển thị.');
        $this->insertLedgerDisplayFixture($ownerId, 'redeem', -3, 'Đổi điểm kiểm thử hiển thị.');
        $this->insertLedgerDisplayFixture($ownerId, 'expire', -2, 'Hết hạn kiểm thử hiển thị.');
        $this->setPointBalance($ownerId, 17);
        $customerData = ['auth_user' => [
            'id' => $ownerId,
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$customerApp, $customerTokens] = $this->application($customerData);
        $history = $customerApp->handle(new Request(
            'GET',
            '/diem-thuong?user_id=' . $otherId,
            [
                'user_id' => (string) $otherId,
                'auth_user' => ['id' => $otherId, 'role' => 'customer'],
            ]
        ));
        self::assertSame(200, $history->statusCode());
        self::assertStringContainsString('17 điểm', $history->body());
        self::assertStringContainsString('Hạng hiện tại', $history->body());
        self::assertStringNotContainsString('<script>alert(9)</script>', $history->body());
        self::assertStringContainsString('&lt;script&gt;alert(9)&lt;/script&gt;', $history->body());
        self::assertStringNotContainsString('Giao dịch chỉ thuộc khách hàng khác.', $history->body());
        self::assertStringContainsString('Cộng điểm', $history->body());
        self::assertStringContainsString('+7 điểm', $history->body());
        self::assertStringContainsString('Đổi thưởng', $history->body());
        self::assertStringContainsString('-3 điểm', $history->body());
        self::assertStringContainsString('Hết hạn', $history->body());
        self::assertStringContainsString('-2 điểm', $history->body());
        self::assertStringContainsString('Điều chỉnh tăng', $history->body());
        self::assertStringContainsString('+15 điểm', $history->body());
        self::assertStringNotContainsString('loyalty-adjustment-form', $history->body());
        self::assertStringNotContainsString('/admin/diem-thuong/dieu-chinh', $history->body());
        self::assertStringContainsString('href="/diem-thuong">Điểm</a>', $history->body());
        self::assertSame(
            404,
            $customerApp->handle(new Request('GET', '/diem-thuong/' . $otherId))->statusCode()
        );
        self::assertSame(405, $customerApp->handle(new Request(
            'POST',
            '/diem-thuong',
            [],
            ['_csrf_token' => $customerTokens->token(), 'user_id' => (string) $otherId]
        ))->statusCode());
        self::assertSame(
            403,
            $customerApp->handle(new Request('GET', '/admin/diem-thuong'))->statusCode()
        );
        self::assertSame(403, $customerApp->handle(new Request(
            'POST',
            '/admin/diem-thuong/dieu-chinh',
            [],
            [
                '_csrf_token' => $customerTokens->token(),
                'user_id' => (string) $otherId,
                'points' => '100',
                'reason' => 'Giả mạo quyền quản trị.',
            ]
        ))->statusCode());
        $dashboard = $customerApp->handle(new Request('GET', '/tai-khoan'));
        self::assertSame(200, $dashboard->statusCode());
        self::assertStringContainsString('<a href="/diem-thuong">Xem sổ giao dịch</a>', $dashboard->body());

        $adminData = ['auth_user' => [
            'id' => $adminId,
            'full_name' => 'Quản trị viên AutoWash',
            'role' => 'admin',
        ]];
        [$adminApp, $tokens] = $this->application($adminData);
        self::assertSame(200, $adminApp->handle(new Request('GET', '/admin/diem-thuong'))->statusCode());
        self::assertSame(403, $adminApp->handle(new Request('GET', '/diem-thuong'))->statusCode());
        self::assertSame(
            419,
            $adminApp->handle(new Request('POST', '/admin/diem-thuong/dieu-chinh'))->statusCode()
        );
        self::assertSame(422, $adminApp->handle(new Request(
            'POST',
            '/admin/diem-thuong/dieu-chinh',
            [],
            ['_csrf_token' => $tokens->token(), 'user_id' => '', 'points' => '0', 'reason' => '']
        ))->statusCode());
    }

    public function testGuestRedirectAndEmptyCustomerLedgerState(): void
    {
        $guestData = [];
        [$guestApp] = $this->application($guestData);
        $guest = $guestApp->handle(new Request('GET', '/diem-thuong'));
        self::assertSame(303, $guest->statusCode());
        self::assertSame('/dang-nhap', $guest->headers()['Location']);

        $customerData = ['auth_user' => [
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng không có giao dịch',
            'role' => 'customer',
        ]];
        [$customerApp] = $this->application($customerData);
        $history = $customerApp->handle(new Request('GET', '/diem-thuong'));
        self::assertSame(200, $history->statusCode());
        self::assertStringContainsString('Chưa có giao dịch điểm', $history->body());
    }

    private function loyaltyService(): LoyaltyService
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

    private function bookingService(): BookingService
    {
        return new BookingService(
            new BookingRepository(self::$database),
            new BookingValidator(),
            new BookingWindowPolicy($this->timezone()),
            new PriceCalculator(),
            new BookingResourceCalculator(),
            $this->timezone(),
            new BookingLifecyclePolicy(),
            new BookingLifecycleValidator(),
            $this->loyaltyService()
        );
    }

    /**
     * @param array<string, mixed> $sessionData
     * @return array{Application, CsrfTokenManager}
     */
    private function application(array &$sessionData): array
    {
        $session = new Session($sessionData);
        $tokens = new CsrfTokenManager($session);
        $view = new View(dirname(__DIR__, 3) . '/resources/views');
        $router = new Router();
        $router->middleware(new CsrfMiddleware($tokens));
        $registerRoutes = require dirname(__DIR__, 3) . '/routes/web.php';
        $registerRoutes(
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
            fn (): LoyaltyController => new LoyaltyController(
                $this->loyaltyService(),
                $view,
                $session,
                $tokens
            ),
            fn (): AdminLoyaltyController => new AdminLoyaltyController(
                $this->loyaltyService(),
                $view,
                $session,
                $tokens
            ),
            null,
            null,
            null,
            null,
            null,
            fn (): DashboardController => new DashboardController(
                new DashboardService(
                    new ResearchReportRepository(self::$database),
                    $this->loyaltyService()
                ),
                $view,
                $session,
                $tokens
            )
        );
        $logFile = sys_get_temp_dir() . '/autowash-loyalty-http-' . bin2hex(random_bytes(6)) . '.log';

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($logFile, $this->timezone()),
            false
        )), $tokens];
    }

    private function createConfirmedBooking(string $phone, string $finalPrice): int
    {
        $source = $this->bookingSource($phone);
        $start = new DateTimeImmutable('2032-07-16 12:00:00', $this->timezone());
        $start = $start->modify('+' . ($this->slotSequence++ * 4) . ' hours');
        $slotStatement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, 20, 'open')
            SQL
        );
        $slotStatement->execute([
            'slot_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $start->modify('+3 hours')->format('H:i:s'),
        ]);
        $slotId = (int) self::$database->lastInsertId();
        $bookingStatement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO bookings (
                booking_code, user_id, vehicle_id, start_slot_id, status,
                booking_duration_minutes, booking_capacity_units, subtotal, final_price
            ) VALUES (
                :booking_code, :user_id, :vehicle_id, :slot_id, 'confirmed',
                :duration, :capacity, :subtotal, :final_price
            )
            SQL
        );
        $bookingStatement->execute([
            'booking_code' => 'TEST_LOYALTY_' . strtoupper(bin2hex(random_bytes(6))),
            'user_id' => $source['user_id'],
            'vehicle_id' => $source['vehicle_id'],
            'slot_id' => $slotId,
            'duration' => $source['duration_minutes'],
            'capacity' => $source['capacity_units'],
            'subtotal' => $finalPrice,
            'final_price' => $finalPrice,
        ]);
        $bookingId = (int) self::$database->lastInsertId();
        $item = self::$database->prepare(
            <<<'SQL'
            INSERT INTO booking_items (
                booking_id, service_id, service_vehicle_price_id, service_name_snapshot,
                vehicle_type_code_snapshot, unit_price_snapshot, duration_minutes_snapshot,
                capacity_units_snapshot, quantity, line_total
            ) VALUES (
                :booking_id, :service_id, :price_id, 'Rửa tiêu chuẩn', :vehicle_type_code,
                :unit_price, :duration, :capacity, 1, :line_total
            )
            SQL
        );
        $item->execute([
            'booking_id' => $bookingId,
            'service_id' => $source['service_id'],
            'price_id' => $source['price_id'],
            'vehicle_type_code' => $source['vehicle_type_code'],
            'unit_price' => $finalPrice,
            'duration' => $source['duration_minutes'],
            'capacity' => $source['capacity_units'],
            'line_total' => $finalPrice,
        ]);

        return $bookingId;
    }

    /** @return array<string, mixed> */
    private function bookingSource(string $phone): array
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            SELECT
                users.id AS user_id, vehicles.id AS vehicle_id, services.id AS service_id,
                service_vehicle_prices.id AS price_id, service_vehicle_prices.duration_minutes,
                vehicle_types.code AS vehicle_type_code,
                vehicle_types.default_capacity_units AS capacity_units
            FROM users
            INNER JOIN vehicles ON vehicles.user_id = users.id AND vehicles.is_active = TRUE
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN services ON services.code = 'STANDARD_WASH'
            INNER JOIN service_vehicle_prices
                ON service_vehicle_prices.service_id = services.id
                AND service_vehicle_prices.vehicle_type_id = vehicle_types.id
            WHERE users.phone = :phone
            LIMIT 1
            SQL
        );
        $statement->execute(['phone' => $phone]);
        $source = $statement->fetch();
        self::assertIsArray($source);

        return $source;
    }

    private function insertConflictingEarn(int $userId, int $bookingId): void
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                description, earned_at, expires_at
            ) VALUES (
                :user_id, 'earn', 1, 1, 'booking', :booking_id,
                'Giao dịch tạo xung đột kiểm thử.', CURRENT_TIMESTAMP,
                DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 12 MONTH)
            )
            SQL
        );
        $statement->execute(['user_id' => $userId, 'booking_id' => $bookingId]);
    }

    /** @return array<string, mixed> */
    private function booking(int $bookingId): array
    {
        $statement = self::$database->prepare('SELECT * FROM bookings WHERE id = :id');
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch();
        self::assertIsArray($booking);

        return $booking;
    }

    /** @return array<string, mixed> */
    private function earnForBooking(int $bookingId): array
    {
        $statement = self::$database->prepare(
            "SELECT * FROM loyalty_transactions WHERE type = 'earn' "
            . "AND source_type = 'booking' AND source_id = :booking_id"
        );
        $statement->execute(['booking_id' => $bookingId]);
        $earn = $statement->fetch();
        self::assertIsArray($earn);

        return $earn;
    }

    /** @return array{monthly_spend: string, monthly_visits: int, point_balance: int} */
    private function metrics(string $phone): array
    {
        $statement = self::$database->prepare(
            'SELECT monthly_spend, monthly_visits, point_balance FROM users WHERE phone = :phone'
        );
        $statement->execute(['phone' => $phone]);
        $metrics = $statement->fetch();
        self::assertIsArray($metrics);

        return [
            'monthly_spend' => (string) $metrics['monthly_spend'],
            'monthly_visits' => (int) $metrics['monthly_visits'],
            'point_balance' => (int) $metrics['point_balance'],
        ];
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function earnCount(int $bookingId): int
    {
        return (int) self::$database->query(
            "SELECT COUNT(*) FROM loyalty_transactions WHERE type = 'earn' "
            . "AND source_type = 'booking' AND source_id = " . $bookingId
        )->fetchColumn();
    }

    private function insertLedgerDisplayFixture(
        int $userId,
        string $type,
        int $points,
        string $description
    ): void {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id, description,
                earned_at, expires_at
            ) VALUES (
                :user_id, :type, :points, :remaining_points, 'manual', :source_id, :description,
                :earned_at, :expires_at
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'points' => $points,
            'remaining_points' => $type === 'earn' ? $points : null,
            'source_id' => random_int(1, PHP_INT_MAX),
            'description' => $description,
            'earned_at' => $type === 'earn' ? '2030-01-01 00:00:00' : null,
            'expires_at' => $type === 'earn' ? '2031-01-01 00:00:00' : null,
        ]);
    }

    private function setPointBalance(int $userId, int $balance): void
    {
        $statement = self::$database->prepare(
            'UPDATE users SET point_balance = :point_balance WHERE id = :user_id'
        );
        $statement->execute(['point_balance' => $balance, 'user_id' => $userId]);
    }

    private function eventCount(string $eventKey): int
    {
        $statement = self::$database->prepare(
            'SELECT COUNT(*) FROM research_event_logs WHERE event_key = :event_key'
        );
        $statement->execute(['event_key' => $eventKey]);

        return (int) $statement->fetchColumn();
    }

    private function adjustmentCount(int $userId): int
    {
        return (int) self::$database->query(
            "SELECT COUNT(*) FROM loyalty_transactions "
            . "WHERE type IN ('adjust_credit', 'adjust_debit') AND user_id = " . $userId
        )->fetchColumn();
    }

    private function adjustmentAuditCount(int $userId): int
    {
        return (int) self::$database->query(
            "SELECT COUNT(*) FROM audit_logs WHERE action = 'loyalty_adjusted' "
            . "AND target_type = 'user' AND target_id = " . $userId
        )->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $report
     * @return array<string, mixed>
     */
    private function reportForUser(array $report, int $userId): array
    {
        foreach ($report as $row) {
            if ((int) $row['user_id'] === $userId) {
                return $row;
            }
        }

        self::fail('Không tìm thấy user trong báo cáo reconcile.');
    }

    /** @return resource */
    private function startWorker(
        string $worker,
        string $barrier,
        string $result,
        int $adminId,
        int $userId,
        int $points
    ) {
        $process = proc_open([
            PHP_BINARY,
            $worker,
            $barrier,
            $result,
            (string) $adminId,
            (string) $userId,
            (string) $points,
        ], [], $pipes, dirname(__DIR__, 3));
        self::assertIsResource($process);

        return $process;
    }

    private function clearLoyaltyFixtures(): void
    {
        self::$database->exec("DELETE FROM audit_logs WHERE action = 'loyalty_adjusted'");
        self::$database->exec("DELETE FROM research_event_logs WHERE event_key LIKE 'booking_completed:%'");
        self::$database->exec('DELETE FROM loyalty_allocations');
        self::$database->exec('UPDATE loyalty_transactions SET source_transaction_id = NULL');
        self::$database->exec('DELETE FROM loyalty_transactions');
        self::$database->exec(
            "DELETE FROM booking_slot_reservations WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'TEST_LOYALTY_%')"
        );
        self::$database->exec(
            "DELETE FROM booking_items WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'TEST_LOYALTY_%')"
        );
        self::$database->exec("DELETE FROM bookings WHERE booking_code LIKE 'TEST_LOYALTY_%'");
        self::$database->exec("DELETE FROM wash_slots WHERE slot_date = '2032-07-16'");
        self::$database->exec(
            "UPDATE users SET monthly_spend = 0, monthly_visits = 0, point_balance = 0 "
            . "WHERE role = 'customer'"
        );
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Ho_Chi_Minh');
    }
}
