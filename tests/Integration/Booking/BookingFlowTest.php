<?php

declare(strict_types=1);

namespace Tests\Integration\Booking;

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
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\SlotFullException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\BookingRepository;
use App\Repositories\LoyaltyTransactionRepository;
use App\Repositories\PromotionRepository;
use App\Services\BookingService;
use App\Services\BookingCompletionService;
use App\Services\BookingResourceCalculator;
use App\Services\BookingWindowPolicy;
use App\Services\PriceCalculator;
use App\Services\PromotionService;
use App\Services\LoyaltyService;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Validation\BookingValidator;
use App\Validation\LoyaltyAdjustmentValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class BookingFlowTest extends TestCase
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
        $this->deleteGeneratedBookings();
        self::$database->exec("DELETE FROM service_vehicle_prices WHERE service_id IN "
            . "(SELECT id FROM services WHERE code LIKE 'TEST_BKG_%')");
        self::$database->exec("DELETE FROM services WHERE code LIKE 'TEST_BKG_%'");
        self::$database->exec("DELETE FROM vehicles WHERE normalized_plate LIKE '77%'");
        self::$database->prepare(
            'DELETE FROM wash_slots WHERE slot_date BETWEEN :start_date AND :end_date AND start_time >= :start_time'
        )->execute([
            'start_date' => $this->dateAfter(0),
            'end_date' => $this->dateAfter(20),
            'start_time' => '17:00:00',
        ]);
        $this->logFile = sys_get_temp_dir() . '/autowash-booking-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testCreatesMultiServiceSnapshotAndHoldsEveryOverlappingSlot(): void
    {
        $slotOne = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $slotTwo = $this->createSlot(1, '18:00:00', '19:00:00', 10);
        $ownerId = $this->userId('0900000003');
        $code = $this->service()->create(
            $ownerId,
            (string) $this->vehicleId('51AB12345'),
            (string) $slotOne,
            [(string) $this->serviceId('STANDARD_WASH'), (string) $this->serviceId('PREMIUM_WASH')]
        );
        $booking = $this->bookingByCode($code);

        self::assertSame(100, (int) $booking['booking_duration_minutes']);
        self::assertSame(3, (int) $booking['booking_capacity_units']);
        self::assertSame('300000.00', $booking['subtotal']);
        self::assertSame('300000.00', $booking['final_price']);
        self::assertSame('0.00', $booking['perk_discount']);
        self::assertSame(2, $this->countRows('booking_items', (int) $booking['id']));

        $reservations = self::$database->query(
            'SELECT wash_slot_id, capacity_units_reserved FROM booking_slot_reservations '
            . 'WHERE booking_id = ' . (int) $booking['id'] . ' ORDER BY wash_slot_id'
        )->fetchAll();
        self::assertSame([$slotOne, $slotTwo], array_map('intval', array_column(
            $reservations,
            'wash_slot_id'
        )));
        self::assertSame([3, 3], array_map('intval', array_column($reservations, 'capacity_units_reserved')));
        self::assertSame(1, (int) self::$database->query(
            "SELECT COUNT(*) FROM research_event_logs WHERE event_key = 'booking_created:"
            . (int) $booking['id'] . "' AND data_source = 'system'"
        )->fetchColumn());
    }

    public function testCheckoutSnapshotsPerkPromotionRewardAndCompletesUsageOnce(): void
    {
        $slot = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $ownerId = $this->userId('0900000003');
        $redemptionId = $this->insertAvailableRedemption(
            $ownerId,
            $this->rewardId('DISCOUNT_10K')
        );
        $code = $this->benefitService()->create(
            $ownerId,
            (string) $this->vehicleId('51AB12345'),
            (string) $slot,
            [(string) $this->serviceId('STANDARD_WASH')],
            (string) $redemptionId
        );
        $booking = $this->bookingByCode($code);

        self::assertSame('100000.00', $booking['subtotal']);
        self::assertSame('5000.00', $booking['perk_discount']);
        self::assertSame('10000.00', $booking['promotion_discount']);
        self::assertSame('10000.00', $booking['reward_discount']);
        self::assertSame('75000.00', $booking['final_price']);
        self::assertNotNull($booking['promotion_id']);
        self::assertSame(
            (int) $booking['id'],
            (int) $this->scalar(
                'SELECT booking_id FROM reward_redemptions WHERE id = ' . $redemptionId
            )
        );

        $service = $this->benefitService(true);
        $service->confirmByAdmin($this->userId('0900000001'), (int) $booking['id']);
        $service->completeByAdmin($this->userId('0900000001'), (int) $booking['id']);
        self::assertSame('used', $this->scalar(
            'SELECT status FROM reward_redemptions WHERE id = ' . $redemptionId
        ));
        self::assertSame(1, (int) $this->scalar(
            'SELECT COUNT(*) FROM promotion_usages WHERE booking_id = ' . (int) $booking['id']
        ));
        $event = self::$database->query(
            "SELECT used_reward, used_promotion FROM research_event_logs "
            . "WHERE event_key = 'booking_completed:" . (int) $booking['id'] . "'"
        )->fetch();
        self::assertSame(1, (int) $event['used_reward']);
        self::assertSame(1, (int) $event['used_promotion']);
    }

    public function testCancellationRestoresReservedRewardAndDoesNotRecordPromotionUsage(): void
    {
        $slot = $this->createSlot(1, '19:00:00', '20:00:00', 10);
        $noShowSlot = $this->createSlot(1, '20:00:00', '21:00:00', 10);
        $ownerId = $this->userId('0900000003');
        $redemptionId = $this->insertAvailableRedemption(
            $ownerId,
            $this->rewardId('DISCOUNT_10K')
        );
        $service = $this->benefitService();
        $code = $service->create(
            $ownerId,
            (string) $this->vehicleId('51AB12345'),
            (string) $slot,
            [(string) $this->serviceId('STANDARD_WASH')],
            (string) $redemptionId
        );
        $booking = $this->bookingByCode($code);
        $service->cancelByCustomer($ownerId, (int) $booking['id']);
        $redemption = self::$database->query(
            'SELECT status, booking_id FROM reward_redemptions WHERE id = ' . $redemptionId
        )->fetch();

        self::assertSame('available', $redemption['status']);
        self::assertNull($redemption['booking_id']);
        self::assertSame(0, (int) $this->scalar(
            'SELECT COUNT(*) FROM promotion_usages WHERE booking_id = ' . (int) $booking['id']
        ));

        $noShowRedemptionId = $this->insertAvailableRedemption(
            $ownerId,
            $this->rewardId('DISCOUNT_10K')
        );
        $noShowCode = $service->create(
            $ownerId,
            (string) $this->vehicleId('51AB12345'),
            (string) $noShowSlot,
            [(string) $this->serviceId('STANDARD_WASH')],
            (string) $noShowRedemptionId
        );
        $noShowBooking = $this->bookingByCode($noShowCode);
        $adminId = $this->userId('0900000001');
        $service->confirmByAdmin($adminId, (int) $noShowBooking['id']);
        $service->markNoShowByAdmin($adminId, (int) $noShowBooking['id']);
        $noShowRedemption = self::$database->query(
            'SELECT status, booking_id FROM reward_redemptions WHERE id = ' . $noShowRedemptionId
        )->fetch();

        self::assertSame('available', $noShowRedemption['status']);
        self::assertNull($noShowRedemption['booking_id']);
        self::assertSame(0, (int) $this->scalar(
            'SELECT COUNT(*) FROM promotion_usages WHERE booking_id = ' . (int) $noShowBooking['id']
        ));
    }

    public function testConcurrentCheckoutDoesNotReservePromotionBeyondTotalLimit(): void
    {
        $slot = $this->createSlot(1, '20:00:00', '21:00:00', 10);
        $this->createSlot(1, '21:00:00', '22:00:00', 10);
        $promotionId = (int) $this->scalar(
            "SELECT id FROM promotions WHERE code = 'SILVER_PLUS_10'"
        );
        self::$database->exec(
            "UPDATE promotions SET minimum_order_value = 0, usage_limit = 1, per_user_limit = 1 "
            . "WHERE id = {$promotionId}"
        );
        $barrier = sys_get_temp_dir() . '/autowash-promotion-barrier-' . bin2hex(random_bytes(6));
        $resultOne = $barrier . '-one';
        $resultTwo = $barrier . '-two';
        $worker = dirname(__DIR__, 2) . '/Support/PromotionCheckoutConcurrencyWorker.php';
        $processes = [
            $this->startWorker(
                $worker,
                $barrier,
                $resultOne,
                $this->userId('0900000003'),
                $this->vehicleId('51AB12345'),
                $slot,
                $this->serviceId('STANDARD_WASH')
            ),
            $this->startWorker(
                $worker,
                $barrier,
                $resultTwo,
                $this->userId('0900000004'),
                $this->vehicleId('50C1234'),
                $slot,
                $this->serviceId('STANDARD_WASH')
            ),
        ];
        touch($barrier);
        foreach ($processes as $process) {
            self::assertSame(0, proc_close($process));
        }
        self::assertStringStartsWith('success:', (string) file_get_contents($resultOne));
        self::assertStringStartsWith('success:', (string) file_get_contents($resultTwo));
        self::assertSame(1, (int) $this->scalar(
            "SELECT COUNT(*) FROM bookings WHERE promotion_id = {$promotionId} "
            . "AND booking_code LIKE 'AW%' AND status IN ('pending', 'confirmed')"
        ));
        foreach ([$barrier, $resultOne, $resultTwo] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testRejectsForeignVehicleUnsupportedServiceAndWindowBeyondTier(): void
    {
        $slotTomorrow = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $slotBeyondMember = $this->createSlot(8, '17:00:00', '18:00:00', 10);
        $memberId = $this->userId('0900000002');

        try {
            $this->service()->create(
                $memberId,
                (string) $this->vehicleId('51AB12345'),
                (string) $slotTomorrow,
                [(string) $this->serviceId('STANDARD_WASH')]
            );
            self::fail('Customer không được đặt lịch bằng phương tiện của người khác.');
        } catch (VehicleOwnershipException) {
            self::assertTrue(true);
        }

        try {
            $this->service()->create(
                $memberId,
                (string) $this->vehicleId('59A12345'),
                (string) $slotTomorrow,
                [(string) $this->serviceId('ENGINE_CLEAN')]
            );
            self::fail('Dịch vụ không hỗ trợ loại xe phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('service_ids', $exception->errors());
        }

        $this->expectException(BookingWindowExceededException::class);
        $this->service()->create(
            $memberId,
            (string) $this->vehicleId('59A12345'),
            (string) $slotBeyondMember,
            [(string) $this->serviceId('STANDARD_WASH')]
        );
    }

    public function testExactTierBoundaryIsAllowedAndClientCannotSupplyPriceOrCapacity(): void
    {
        $slot = $this->createSlot(10, '17:00:00', '18:00:00', 10);
        $ownerId = $this->userId('0900000003');
        $sessionData = ['auth_user' => [
            'id' => $ownerId,
            'full_name' => 'Khách hàng Bạc',
            'role' => 'customer',
        ]];
        [$application, $tokens] = $this->application($sessionData);
        $response = $application->handle(new Request('POST', '/dat-lich', [], [
            '_csrf_token' => $tokens->token(),
            'vehicle_id' => (string) $this->vehicleId('51AB12345'),
            'start_slot_id' => (string) $slot,
            'service_ids' => [(string) $this->serviceId('STANDARD_WASH')],
            'final_price' => '1.00',
            'booking_duration_minutes' => '1',
            'booking_capacity_units' => '1',
        ]));

        self::assertSame(303, $response->statusCode());
        self::assertSame('/dat-lich', $response->headers()['Location']);
        $booking = self::$database->query(
            'SELECT * FROM bookings WHERE user_id = ' . $ownerId . " AND booking_code LIKE 'AW%' "
            . 'ORDER BY id DESC LIMIT 1'
        )->fetch();
        self::assertIsArray($booking);
        self::assertSame('100000.00', $booking['final_price']);
        self::assertSame(40, (int) $booking['booking_duration_minutes']);
        self::assertSame(2, (int) $booking['booking_capacity_units']);
    }

    public function testFullMiddleSlotRollsBackBookingItemsAndReservations(): void
    {
        $first = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $middle = $this->createSlot(1, '18:00:00', '19:00:00', 2);
        $this->createSlot(1, '19:00:00', '20:00:00', 10);
        $ownerId = $this->userId('0900000003');
        $before = $this->generatedBookingCount();

        try {
            $this->service()->create(
                $ownerId,
                (string) $this->vehicleId('51AB12345'),
                (string) $first,
                [
                    (string) $this->serviceId('STANDARD_WASH'),
                    (string) $this->serviceId('PREMIUM_WASH'),
                    (string) $this->serviceId('ENGINE_CLEAN'),
                    (string) $this->serviceId('TIRE_CARE'),
                ]
            );
            self::fail('Slot giữa thiếu capacity phải làm toàn bộ booking thất bại.');
        } catch (SlotFullException) {
            self::assertTrue(true);
        }

        self::assertSame($before, $this->generatedBookingCount());
        self::assertSame(0, (int) self::$database->query(
            'SELECT COUNT(*) FROM booking_slot_reservations WHERE wash_slot_id IN ('
            . $first . ', ' . $middle . ') AND booking_id IN '
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'AW%')"
        )->fetchColumn());
    }

    public function testMissingFollowingSlotRejectsBookingWithoutPartialState(): void
    {
        $first = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $ownerId = $this->userId('0900000003');

        try {
            $this->service()->create(
                $ownerId,
                (string) $this->vehicleId('51AB12345'),
                (string) $first,
                [
                    (string) $this->serviceId('STANDARD_WASH'),
                    (string) $this->serviceId('PREMIUM_WASH'),
                ]
            );
            self::fail('Thiếu slot liên tục phải làm booking thất bại.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('start_slot_id', $exception->errors());
        }

        self::assertSame(0, $this->generatedBookingCount());
        self::assertSame(0, (int) self::$database->query(
            'SELECT COUNT(*) FROM booking_slot_reservations WHERE wash_slot_id = ' . $first
            . " AND booking_id IN (SELECT id FROM bookings WHERE booking_code LIKE 'AW%')"
        )->fetchColumn());
    }

    public function testDuplicateActiveVehicleIntervalIsRejected(): void
    {
        $first = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $overlap = $this->createSlot(1, '17:30:00', '18:30:00', 10);
        $ownerId = $this->userId('0900000002');
        $vehicleId = $this->vehicleId('59A12345');
        $standardId = $this->serviceId('STANDARD_WASH');
        $tireCareId = $this->serviceId('TIRE_CARE');
        $this->service()->create(
            $ownerId,
            (string) $vehicleId,
            (string) $first,
            [(string) $standardId, (string) $tireCareId]
        );

        $this->expectException(BookingConflictException::class);
        $this->service()->create(
            $ownerId,
            (string) $vehicleId,
            (string) $overlap,
            [(string) $standardId]
        );
    }

    public function testDatabaseFailureAfterBookingInsertRollsBackEverything(): void
    {
        $slot = $this->createSlot(1, '17:00:00', '18:00:00', 10);
        $ownerId = $this->userId('0900000002');
        $nextBookingId = (int) self::$database->query(
            <<<'SQL'
            SELECT AUTO_INCREMENT
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'
            SQL
        )->fetchColumn();
        $event = self::$database->prepare(
            <<<'SQL'
            INSERT INTO research_event_logs (
                event_key, anonymous_user_key, event_type, event_time, tier_code, data_source
            ) VALUES (
                :event_key, 'failure-injection', 'booking_created', CURRENT_TIMESTAMP, 'MEMBER', 'system'
            )
            SQL
        );
        $event->execute(['event_key' => 'booking_created:' . $nextBookingId]);

        try {
            $this->service()->create(
                $ownerId,
                (string) $this->vehicleId('59A12345'),
                (string) $slot,
                [(string) $this->serviceId('STANDARD_WASH')]
            );
            self::fail('Failure injection phải làm create booking thất bại.');
        } catch (PDOException) {
            self::assertTrue(true);
        }

        self::assertSame(0, $this->generatedBookingCount());
        self::assertSame(0, (int) self::$database->query(
            'SELECT COUNT(*) FROM booking_slot_reservations WHERE booking_id = ' . $nextBookingId
        )->fetchColumn());
        self::assertSame(0, (int) self::$database->query(
            'SELECT COUNT(*) FROM booking_items WHERE booking_id = ' . $nextBookingId
        )->fetchColumn());
    }

    public function testTwoProcessesCompetingForLastUnitProduceOneWinner(): void
    {
        $slot = $this->createSlot(1, '17:00:00', '18:00:00', 1);
        $typeId = $this->vehicleTypeId('motorbike');
        $firstOwner = $this->userId('0900000002');
        $secondOwner = $this->userId('0900000003');
        $firstVehicle = $this->createVehicle($firstOwner, $typeId, '77A11111');
        $secondVehicle = $this->createVehicle($secondOwner, $typeId, '77B11112');
        $serviceId = $this->serviceId('STANDARD_WASH');
        $token = bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir() . '/autowash-booking-barrier-' . $token;
        $resultOne = sys_get_temp_dir() . '/autowash-booking-result-one-' . $token;
        $resultTwo = sys_get_temp_dir() . '/autowash-booking-result-two-' . $token;
        $worker = dirname(__DIR__, 2) . '/Support/BookingConcurrencyWorker.php';
        $processes = [
            $this->startWorker($worker, $barrier, $resultOne, $firstOwner, $firstVehicle, $slot, $serviceId),
            $this->startWorker($worker, $barrier, $resultTwo, $secondOwner, $secondVehicle, $slot, $serviceId),
        ];
        touch($barrier);

        foreach ($processes as $process) {
            self::assertSame(0, proc_close($process));
        }

        $results = [trim((string) file_get_contents($resultOne)), trim((string) file_get_contents($resultTwo))];
        sort($results);
        self::assertSame(['full', 'success'], $results);
        self::assertSame(1, (int) self::$database->query(
            'SELECT COALESCE(SUM(capacity_units_reserved), 0) FROM booking_slot_reservations '
            . 'INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id '
            . 'WHERE wash_slot_id = ' . $slot . " AND bookings.status IN ('pending', 'confirmed')"
        )->fetchColumn());

        foreach ([$barrier, $resultOne, $resultTwo] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testHttpRouteRequiresCustomerCsrfAndEscapesDatabaseContent(): void
    {
        $this->insertScriptService();
        $ownerId = $this->userId('0900000002');
        $customerData = ['auth_user' => [
            'id' => $ownerId,
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$customerApp] = $this->application($customerData);
        $page = $customerApp->handle(new Request('GET', '/dat-lich'));
        self::assertSame(200, $page->statusCode());
        self::assertStringNotContainsString('<script>alert(7)</script>', $page->body());
        self::assertStringContainsString('&lt;script&gt;alert(7)&lt;/script&gt;', $page->body());
        self::assertSame(419, $customerApp->handle(new Request('POST', '/dat-lich'))->statusCode());

        $adminData = ['auth_user' => [
            'id' => $this->userId('0900000001'),
            'full_name' => 'Quản trị viên',
            'role' => 'admin',
        ]];
        [$adminApp] = $this->application($adminData);
        self::assertSame(403, $adminApp->handle(new Request('GET', '/dat-lich'))->statusCode());

        $guestData = [];
        [$guestApp] = $this->application($guestData);
        $guest = $guestApp->handle(new Request('GET', '/dat-lich'));
        self::assertSame(303, $guest->statusCode());
        self::assertSame('/dang-nhap', $guest->headers()['Location']);
    }

    private function service(?PDO $database = null): BookingService
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');

        return new BookingService(
            new BookingRepository($database ?? self::$database),
            new BookingValidator(),
            new BookingWindowPolicy($timezone),
            new PriceCalculator(),
            new BookingResourceCalculator(),
            $timezone
        );
    }

    private function benefitService(bool $withCompletion = false): BookingService
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $promotions = new PromotionService(new PromotionRepository(self::$database), $timezone);
        $completion = null;
        if ($withCompletion) {
            $loyalty = new LoyaltyService(
                new LoyaltyTransactionRepository(self::$database),
                new LoyaltyPointCalculator(10_000),
                new LoyaltyAdjustmentValidator(),
                new LoyaltyDebitAllocator(),
                new LoyaltyExpirationPolicy($timezone),
                $timezone
            );
            $completion = new BookingCompletionService($promotions, $loyalty);
        }

        return new BookingService(
            new BookingRepository(self::$database),
            new BookingValidator(),
            new BookingWindowPolicy($timezone),
            new PriceCalculator(),
            new BookingResourceCalculator(),
            $timezone,
            completionProcessor: $completion,
            promotionService: $promotions
        );
    }

    private function insertAvailableRedemption(int $userId, int $rewardId): int
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

    private function rewardId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM rewards WHERE code = :code');
        $statement->execute(['code' => $code]);
        return (int) $statement->fetchColumn();
    }

    private function scalar(string $sql): mixed
    {
        return self::$database->query($sql)->fetchColumn();
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
            static fn (): never => throw new \RuntimeException('Không cần AuthController cho test booking.'),
            null,
            null,
            null,
            null,
            null,
            fn (): BookingController => new BookingController($this->service(), $view, $session, $tokens)
        );

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        )), $tokens];
    }

    private function createSlot(int $daysAfter, string $start, string $end, int $capacity): int
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, :capacity, 'open')
            SQL
        );
        $statement->execute([
            'slot_date' => $this->dateAfter($daysAfter),
            'start_time' => $start,
            'end_time' => $end,
            'capacity' => $capacity,
        ]);

        return (int) self::$database->lastInsertId();
    }

    private function dateAfter(int $days): string
    {
        return (new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh')))
            ->modify('+' . $days . ' days')
            ->format('Y-m-d');
    }

    private function deleteGeneratedBookings(): void
    {
        self::$database->exec("DELETE FROM research_event_logs WHERE event_key LIKE 'booking_created:%' "
            . "OR event_key LIKE 'booking_completed:%'");
        self::$database->exec("DELETE FROM promotion_usages WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'AW%')");
        self::$database->exec("DELETE FROM reward_redemptions WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'AW%')");
        self::$database->exec("DELETE FROM loyalty_transactions WHERE source_type = 'booking' "
            . "AND source_id IN (SELECT id FROM bookings WHERE booking_code LIKE 'AW%')");
        self::$database->exec("DELETE FROM booking_slot_reservations WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'AW%')");
        self::$database->exec("DELETE FROM booking_items WHERE booking_id IN "
            . "(SELECT id FROM bookings WHERE booking_code LIKE 'AW%')");
        self::$database->exec("DELETE FROM bookings WHERE booking_code LIKE 'AW%'");
        self::$database->exec(
            "UPDATE users SET point_balance = (SELECT COALESCE(SUM(points_delta), 0) "
            . "FROM loyalty_transactions WHERE loyalty_transactions.user_id = users.id)"
        );
    }

    private function generatedBookingCount(): int
    {
        return (int) self::$database->query(
            "SELECT COUNT(*) FROM bookings WHERE booking_code LIKE 'AW%'"
        )->fetchColumn();
    }

    /** @return array<string, mixed> */
    private function bookingByCode(string $code): array
    {
        $statement = self::$database->prepare('SELECT * FROM bookings WHERE booking_code = :code');
        $statement->execute(['code' => $code]);
        $booking = $statement->fetch();
        self::assertIsArray($booking);

        return $booking;
    }

    private function countRows(string $table, int $bookingId): int
    {
        return (int) self::$database->query(
            sprintf('SELECT COUNT(*) FROM %s WHERE booking_id = %d', $table, $bookingId)
        )->fetchColumn();
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function vehicleId(string $plate): int
    {
        $statement = self::$database->prepare('SELECT id FROM vehicles WHERE normalized_plate = :plate');
        $statement->execute(['plate' => $plate]);

        return (int) $statement->fetchColumn();
    }

    private function vehicleTypeId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM vehicle_types WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function serviceId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM services WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function createVehicle(int $ownerId, int $typeId, string $plate): int
    {
        $statement = self::$database->prepare(
            <<<'SQL'
            INSERT INTO vehicles (
                user_id, vehicle_type_id, normalized_plate, display_plate, brand, model, notes, is_active
            ) VALUES (
                :owner_id, :type_id, :normalized_plate, :display_plate, 'Xe kiểm thử', NULL, NULL, TRUE
            )
            SQL
        );
        $statement->execute([
            'owner_id' => $ownerId,
            'type_id' => $typeId,
            'normalized_plate' => $plate,
            'display_plate' => $plate,
        ]);

        return (int) self::$database->lastInsertId();
    }

    /** @return resource */
    private function startWorker(
        string $worker,
        string $barrier,
        string $result,
        int $ownerId,
        int $vehicleId,
        int $slotId,
        int $serviceId
    ) {
        $process = proc_open([
            PHP_BINARY,
            $worker,
            $barrier,
            $result,
            (string) $ownerId,
            (string) $vehicleId,
            (string) $slotId,
            (string) $serviceId,
        ], [], $pipes, dirname(__DIR__, 3));
        self::assertIsResource($process);

        return $process;
    }

    private function insertScriptService(): void
    {
        $statement = self::$database->prepare(
            "INSERT INTO services (code, name, description, is_active) "
            . "VALUES ('TEST_BKG_XSS', '<script>alert(7)</script>', 'Nội dung kiểm thử', TRUE)"
        );
        $statement->execute();
        $serviceId = (int) self::$database->lastInsertId();
        $price = self::$database->prepare(
            <<<'SQL'
            INSERT INTO service_vehicle_prices (
                service_id, vehicle_type_id, price, duration_minutes,
                capacity_units_override, is_supported, is_active
            ) VALUES (
                :service_id, :vehicle_type_id, '50000.00', 20, NULL, TRUE, TRUE
            )
            SQL
        );
        $price->execute([
            'service_id' => $serviceId,
            'vehicle_type_id' => $this->vehicleTypeId('motorbike'),
        ]);
    }
}
