<?php

declare(strict_types=1);

namespace Tests\Integration\Booking;

use App\Contracts\BookingCompletionProcessorInterface;
use App\Controllers\AdminBookingController;
use App\Controllers\BookingController;
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
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\CancellationCutoffException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Exceptions\ValidationException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\BookingRepository;
use App\Services\BookingLifecyclePolicy;
use App\Services\BookingResourceCalculator;
use App\Services\BookingService;
use App\Services\BookingWindowPolicy;
use App\Services\PriceCalculator;
use App\Validation\BookingLifecycleValidator;
use App\Validation\BookingValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BookingLifecycleFlowTest extends TestCase
{
    private static PDO $database;
    private static DatabaseSeeder $seeder;
    private string $logFile;

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
        $this->deleteFixtures();
        $this->logFile = sys_get_temp_dir() . '/autowash-lifecycle-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testAdminFollowsTransitionMatrixAndDuplicateCompleteDoesNotMutateMetrics(): void
    {
        $fixture = $this->createBooking('0900000003', 'pending', '2031-07-16 12:00:00');
        $adminId = $this->userId('0900000001');
        $ownerBefore = $this->userMetrics('0900000003');
        $this->service()->confirmByAdmin($adminId, $fixture['booking_id']);
        $this->service()->completeByAdmin($adminId, $fixture['booking_id']);
        $booking = $this->booking($fixture['booking_id']);

        self::assertSame('completed', $booking['status']);
        self::assertNotNull($booking['completed_at']);
        self::assertNull($booking['loyalty_processed_at']);
        self::assertSame($ownerBefore, $this->userMetrics('0900000003'));

        try {
            $this->service()->completeByAdmin($adminId, $fixture['booking_id']);
            self::fail('Booking completed không được hoàn thành lần thứ hai.');
        } catch (InvalidBookingTransitionException) {
            self::assertTrue(true);
        }

        self::assertSame($ownerBefore, $this->userMetrics('0900000003'));
    }

    public function testCompletionProcessorFailureRollsBackStatusForSliceNineIntegration(): void
    {
        $fixture = $this->createBooking('0900000003', 'confirmed', '2031-07-16 12:00:00');
        $processor = new class implements BookingCompletionProcessorInterface {
            public function process(array $lockedBooking): void
            {
                throw new RuntimeException('Lỗi giả lập khi xử lý loyalty.');
            }
        };

        try {
            $this->service($processor)->completeByAdmin(
                $this->userId('0900000001'),
                $fixture['booking_id']
            );
            self::fail('Lỗi processor phải rollback trạng thái completed.');
        } catch (RuntimeException $exception) {
            self::assertSame('Lỗi giả lập khi xử lý loyalty.', $exception->getMessage());
        }

        self::assertSame('confirmed', $this->booking($fixture['booking_id'])['status']);
    }

    public function testCustomerCancellationAllowsExactBoundaryAndReleasesCapacity(): void
    {
        $fixture = $this->createBooking('0900000002', 'pending', '2031-07-16 12:00:00');
        $now = new DateTimeImmutable('2031-07-16 10:00:00', $this->timezone());
        $metricsBefore = $this->userMetrics('0900000002');

        $this->service()->cancelByCustomer(
            $this->userId('0900000002'),
            $fixture['booking_id'],
            $now
        );

        $booking = $this->booking($fixture['booking_id']);
        self::assertSame('cancelled', $booking['status']);
        self::assertNotNull($booking['cancelled_at']);
        self::assertSame(0, $this->activeCapacity($fixture['slot_id']));
        self::assertSame(1, $this->reservationCount($fixture['booking_id']));
        self::assertSame($metricsBefore, $this->userMetrics('0900000002'));
        self::assertSame(0, (int) self::$database->query(
            "SELECT COUNT(*) FROM loyalty_transactions WHERE source_type = 'booking' "
            . 'AND source_id = ' . $fixture['booking_id']
        )->fetchColumn());
    }

    public function testCustomerCancellationRejectsInsideCutoffAndForeignBooking(): void
    {
        $fixture = $this->createBooking('0900000002', 'confirmed', '2031-07-16 12:00:00');

        try {
            $this->service()->cancelByCustomer(
                $this->userId('0900000002'),
                $fixture['booking_id'],
                new DateTimeImmutable('2031-07-16 10:00:01', $this->timezone())
            );
            self::fail('Customer không được hủy khi còn dưới 2 giờ.');
        } catch (CancellationCutoffException) {
            self::assertTrue(true);
        }

        try {
            $this->service()->cancelByCustomer(
                $this->userId('0900000003'),
                $fixture['booking_id'],
                new DateTimeImmutable('2031-07-16 09:00:00', $this->timezone())
            );
            self::fail('Customer không được hủy booking của người khác.');
        } catch (BookingNotFoundException) {
            self::assertTrue(true);
        }

        self::assertSame('confirmed', $this->booking($fixture['booking_id'])['status']);
    }

    public function testAdminCanCancelInsideCutoffWithReasonAndAudit(): void
    {
        $fixture = $this->createBooking('0900000002', 'confirmed', '2031-07-16 12:00:00');
        $adminId = $this->userId('0900000001');
        $reason = 'Khách báo sự cố phương tiện sát giờ phục vụ.';

        $this->service()->cancelByAdmin($adminId, $fixture['booking_id'], $reason);

        self::assertSame('cancelled', $this->booking($fixture['booking_id'])['status']);
        $audit = self::$database->query(
            'SELECT * FROM audit_logs WHERE target_type = \'booking\' AND target_id = '
            . $fixture['booking_id']
        )->fetch();
        self::assertIsArray($audit);
        self::assertSame($adminId, (int) $audit['actor_user_id']);
        self::assertSame($reason, $audit['reason']);
        self::assertStringContainsString('confirmed', (string) $audit['before_json']);
        self::assertSame(0, $this->activeCapacity($fixture['slot_id']));
    }

    public function testAdminCancellationRequiresReasonBeforeMutation(): void
    {
        $fixture = $this->createBooking('0900000002', 'confirmed', '2031-07-16 12:00:00');

        try {
            $this->service()->cancelByAdmin(
                $this->userId('0900000001'),
                $fixture['booking_id'],
                '   '
            );
            self::fail('Admin không được hủy booking khi thiếu lý do.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('cancellation_reason', $exception->errors());
        }

        self::assertSame('confirmed', $this->booking($fixture['booking_id'])['status']);
        self::assertSame(0, (int) self::$database->query(
            "SELECT COUNT(*) FROM audit_logs WHERE target_type = 'booking' AND target_id = "
            . $fixture['booking_id']
        )->fetchColumn());
    }

    public function testNoShowReleasesCapacityAndCompletedHistoryUsesItemSnapshot(): void
    {
        $noShow = $this->createBooking('0900000002', 'confirmed', '2031-07-16 12:00:00');
        $completed = $this->createBooking('0900000003', 'confirmed', '2031-07-17 12:00:00');
        $adminId = $this->userId('0900000001');
        $this->service()->markNoShowByAdmin($adminId, $noShow['booking_id']);
        $this->service()->completeByAdmin($adminId, $completed['booking_id']);
        self::$database->exec("UPDATE services SET name = 'Tên cấu hình mới' WHERE code = 'STANDARD_WASH'");
        $overview = $this->service()->customerOverview($this->userId('0900000003'));

        self::assertSame('no_show', $this->booking($noShow['booking_id'])['status']);
        self::assertSame(0, $this->activeCapacity($noShow['slot_id']));
        self::assertCount(1, $overview['history']);
        self::assertSame('Rửa tiêu chuẩn', $overview['history'][0]['service_names']);
        self::assertSame('completed', $overview['history'][0]['status']);
    }

    public function testHttpRoutesEnforceRoleOwnershipCsrfAndEscapeSnapshots(): void
    {
        $fixture = $this->createBooking(
            '0900000002',
            'pending',
            '2031-07-16 12:00:00',
            '<script>alert(8)</script>'
        );
        $customerData = ['auth_user' => [
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng Member',
            'role' => 'customer',
        ]];
        [$customerApp] = $this->application($customerData);
        $list = $customerApp->handle(new Request('GET', '/lich-dat'));
        self::assertSame(200, $list->statusCode());
        self::assertStringNotContainsString('<script>alert(8)</script>', $list->body());
        self::assertStringContainsString('&lt;script&gt;alert(8)&lt;/script&gt;', $list->body());
        self::assertSame(
            419,
            $customerApp->handle(new Request('POST', '/lich-dat/' . $fixture['booking_id'] . '/huy'))
                ->statusCode()
        );
        self::assertSame(403, $customerApp->handle(new Request('GET', '/admin/lich-dat'))->statusCode());

        $otherData = ['auth_user' => [
            'id' => $this->userId('0900000003'),
            'full_name' => 'Khách hàng Silver',
            'role' => 'customer',
        ]];
        [$otherApp] = $this->application($otherData);
        self::assertSame(
            404,
            $otherApp->handle(new Request('GET', '/lich-dat/' . $fixture['booking_id']))->statusCode()
        );

        $adminData = ['auth_user' => [
            'id' => $this->userId('0900000001'),
            'full_name' => 'Quản trị viên',
            'role' => 'admin',
        ]];
        [$adminApp] = $this->application($adminData);
        self::assertSame(200, $adminApp->handle(new Request('GET', '/admin/lich-dat'))->statusCode());
        self::assertSame(403, $adminApp->handle(new Request('GET', '/lich-dat'))->statusCode());
    }

    private function service(
        ?BookingCompletionProcessorInterface $processor = null
    ): BookingService {
        return new BookingService(
            new BookingRepository(self::$database),
            new BookingValidator(),
            new BookingWindowPolicy($this->timezone()),
            new PriceCalculator(),
            new BookingResourceCalculator(),
            $this->timezone(),
            new BookingLifecyclePolicy(),
            new BookingLifecycleValidator(),
            $processor
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
            static fn (): never => throw new RuntimeException('Không cần AuthController cho test.'),
            null,
            null,
            null,
            null,
            null,
            fn (): BookingController => new BookingController($this->service(), $view, $session, $tokens),
            fn (): AdminBookingController => new AdminBookingController(
                $this->service(),
                $view,
                $session,
                $tokens
            )
        );

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, $this->timezone()),
            false
        )), $tokens];
    }

    /** @return array{booking_id: int, slot_id: int} */
    private function createBooking(
        string $phone,
        string $status,
        string $startsAt,
        string $serviceName = 'Rửa tiêu chuẩn'
    ): array {
        $start = new DateTimeImmutable($startsAt, $this->timezone());
        $end = $start->modify('+1 hour');
        $slot = self::$database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, 10, 'open')
            SQL
        );
        $slot->execute([
            'slot_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ]);
        $slotId = (int) self::$database->lastInsertId();
        $source = $this->bookingSource($phone);
        $code = 'TEST_LIFE_' . strtoupper(bin2hex(random_bytes(6)));
        $booking = self::$database->prepare(
            <<<'SQL'
            INSERT INTO bookings (
                booking_code, user_id, vehicle_id, start_slot_id, status,
                booking_duration_minutes, booking_capacity_units, subtotal, final_price
            ) VALUES (
                :code, :user_id, :vehicle_id, :slot_id, :status,
                :duration_minutes, :capacity_units, :subtotal, :final_price
            )
            SQL
        );
        $booking->execute([
            'code' => $code,
            'user_id' => $source['user_id'],
            'vehicle_id' => $source['vehicle_id'],
            'slot_id' => $slotId,
            'status' => $status,
            'duration_minutes' => $source['duration_minutes'],
            'capacity_units' => $source['capacity_units'],
            'subtotal' => $source['price'],
            'final_price' => $source['price'],
        ]);
        $bookingId = (int) self::$database->lastInsertId();
        $item = self::$database->prepare(
            <<<'SQL'
            INSERT INTO booking_items (
                booking_id, service_id, service_vehicle_price_id, service_name_snapshot,
                vehicle_type_code_snapshot, unit_price_snapshot, duration_minutes_snapshot,
                capacity_units_snapshot, quantity, line_total
            ) VALUES (
                :booking_id, :service_id, :price_id, :service_name, :vehicle_type_code,
                :unit_price, :duration_minutes, :capacity_units, 1, :line_total
            )
            SQL
        );
        $item->execute([
            'booking_id' => $bookingId,
            'service_id' => $source['service_id'],
            'price_id' => $source['price_id'],
            'service_name' => $serviceName,
            'vehicle_type_code' => $source['vehicle_type_code'],
            'unit_price' => $source['price'],
            'duration_minutes' => $source['duration_minutes'],
            'capacity_units' => $source['capacity_units'],
            'line_total' => $source['price'],
        ]);
        $reservation = self::$database->prepare(
            <<<'SQL'
            INSERT INTO booking_slot_reservations (booking_id, wash_slot_id, capacity_units_reserved)
            VALUES (:booking_id, :slot_id, :capacity_units)
            SQL
        );
        $reservation->execute([
            'booking_id' => $bookingId,
            'slot_id' => $slotId,
            'capacity_units' => $source['capacity_units'],
        ]);

        return ['booking_id' => $bookingId, 'slot_id' => $slotId];
    }

    /** @return array<string, int|string> */
    private function bookingSource(string $phone): array
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            SELECT
                users.id AS user_id,
                vehicles.id AS vehicle_id,
                services.id AS service_id,
                service_vehicle_prices.id AS price_id,
                service_vehicle_prices.price,
                service_vehicle_prices.duration_minutes,
                vehicle_types.code AS vehicle_type_code,
                GREATEST(
                    vehicle_types.default_capacity_units,
                    COALESCE(service_vehicle_prices.capacity_units_override, 0)
                ) AS capacity_units
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

    /** @return array<string, mixed> */
    private function booking(int $bookingId): array
    {
        $statement = self::$database->prepare('SELECT * FROM bookings WHERE id = :id');
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch();
        self::assertIsArray($booking);

        return $booking;
    }

    /** @return array{monthly_spend: string, monthly_visits: int, point_balance: int} */
    private function userMetrics(string $phone): array
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

    private function activeCapacity(int $slotId): int
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            SELECT COALESCE(SUM(booking_slot_reservations.capacity_units_reserved), 0)
            FROM booking_slot_reservations
            INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
            WHERE booking_slot_reservations.wash_slot_id = :slot_id
              AND bookings.status IN ('pending', 'confirmed')
            SQL
        );
        $statement->execute(['slot_id' => $slotId]);

        return (int) $statement->fetchColumn();
    }

    private function reservationCount(int $bookingId): int
    {
        $statement = self::$database->prepare(
            'SELECT COUNT(*) FROM booking_slot_reservations WHERE booking_id = :booking_id'
        );
        $statement->execute(['booking_id' => $bookingId]);

        return (int) $statement->fetchColumn();
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function deleteFixtures(): void
    {
        self::$database->exec(
            "DELETE FROM audit_logs WHERE target_type = 'booking' AND target_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'TEST_LIFE_%')"
        );
        self::$database->exec(
            "DELETE FROM booking_slot_reservations WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'TEST_LIFE_%')"
        );
        self::$database->exec(
            "DELETE FROM booking_items WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'TEST_LIFE_%')"
        );
        self::$database->exec("DELETE FROM bookings WHERE booking_code LIKE 'TEST_LIFE_%'");
        self::$database->exec(
            "DELETE FROM wash_slots WHERE slot_date BETWEEN '2031-07-16' AND '2031-07-17'"
        );
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Ho_Chi_Minh');
    }
}
