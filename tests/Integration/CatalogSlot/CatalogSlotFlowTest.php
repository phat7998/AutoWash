<?php

declare(strict_types=1);

namespace Tests\Integration\CatalogSlot;

use App\Controllers\AdminServiceController;
use App\Controllers\AdminSlotController;
use App\Controllers\CatalogController;
use App\Controllers\WashSlotController;
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
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\WashSlotRepository;
use App\Services\ServiceCatalogService;
use App\Services\WashSlotService;
use App\Validation\ServiceCatalogValidator;
use App\Validation\WashSlotValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class CatalogSlotFlowTest extends TestCase
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
        self::$database->exec(
            "DELETE FROM service_vehicle_prices WHERE service_id IN "
            . "(SELECT id FROM services WHERE code LIKE 'TEST_%')"
        );
        self::$database->exec("DELETE FROM services WHERE code LIKE 'TEST_%'");
        self::$database->exec("DELETE FROM wash_slots WHERE slot_date >= '2098-01-01'");
        $this->logFile = sys_get_temp_dir() . '/autowash-catalog-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testCustomerCatalogUsesActiveSupportedDatabaseConfiguration(): void
    {
        $service = $this->catalogService();
        $busId = $this->vehicleTypeId('bus');
        $catalog = $service->customerCatalog((string) $busId);

        self::assertSame(['TIRE_CARE', 'PREMIUM_WASH', 'STANDARD_WASH'], array_column(
            $catalog['services'],
            'code'
        ));
        self::assertNotContains('ENGINE_CLEAN', array_column($catalog['services'], 'code'));
        $premium = $this->rowByCode($catalog['services'], 'PREMIUM_WASH');
        self::assertSame('550000.00', $premium['price']);
        self::assertSame(150, (int) $premium['duration_minutes']);
        self::assertSame(6, (int) $premium['capacity_units']);

        self::$database->prepare(
            <<<'SQL'
            UPDATE service_vehicle_prices
            SET is_active = FALSE
            WHERE vehicle_type_id = :type_id
              AND service_id = (SELECT id FROM services WHERE code = 'TIRE_CARE')
            SQL
        )->execute(['type_id' => $busId]);

        self::assertNotContains(
            'TIRE_CARE',
            array_column($service->customerCatalog((string) $busId)['services'], 'code')
        );
    }

    public function testAdminCreatesUpdatesAndDeactivatesServiceAtomically(): void
    {
        $service = $this->catalogService();
        $prices = $this->validPrices('75000', '30');
        $serviceId = $service->create('TEST_DETAIL', 'Rửa kiểm thử', '<b>Mô tả</b>', $prices);
        $created = $service->service($serviceId);

        self::assertSame('TEST_DETAIL', $created['code']);
        self::assertCount(4, $created['prices']);
        self::assertSame('75000.00', $created['prices'][$this->vehicleTypeId('motorbike')]['price']);

        $prices[(string) $this->vehicleTypeId('motorbike')]['price'] = '90000.50';
        $service->update($serviceId, 'TEST_DETAIL', 'Rửa kiểm thử mới', 'Nội dung mới', $prices);
        self::assertSame('90000.50', $service->service($serviceId)['prices'][
            $this->vehicleTypeId('motorbike')
        ]['price']);

        $service->deactivate($serviceId);
        self::assertFalse((bool) $service->service($serviceId)['is_active']);
        $service->activate($serviceId);
        self::assertTrue((bool) $service->service($serviceId)['is_active']);
        self::assertSame(0, (int) self::$database->query(
            "SELECT COUNT(*) FROM booking_items WHERE service_id = {$serviceId}"
        )->fetchColumn());
    }

    public function testRejectsInvalidAndDuplicateServiceConfiguration(): void
    {
        $service = $this->catalogService();
        $prices = $this->validPrices('0', '0');

        try {
            $service->create('TEST_INVALID', '', '', $prices);
            self::fail('Cấu hình giá/thời lượng sai phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('name', $exception->errors());
            self::assertNotEmpty(array_filter(
                array_keys($exception->errors()),
                static fn (string $key): bool => str_ends_with($key, '.price')
            ));
        }

        $service->create('TEST_DUPLICATE', 'Dịch vụ một', '', $this->validPrices('50000', '20'));
        $this->expectException(DuplicateCatalogException::class);
        $service->create('TEST_DUPLICATE', 'Dịch vụ hai', '', $this->validPrices('60000', '25'));
    }

    public function testPriceUpdateDoesNotChangeExistingBookingItemSnapshot(): void
    {
        $service = $this->catalogService();
        $serviceId = (int) self::$database->query(
            "SELECT id FROM services WHERE code = 'STANDARD_WASH'"
        )->fetchColumn();
        $configured = $service->service($serviceId);
        $prices = [];

        foreach ($configured['prices'] as $typeId => $row) {
            $prices[(string) $typeId] = [
                'is_supported' => (bool) $row['is_supported'] ? '1' : '',
                'is_active' => (bool) $row['is_active'] ? '1' : '',
                'price' => (string) ($row['price'] ?? ''),
                'duration_minutes' => (string) ($row['duration_minutes'] ?? ''),
                'capacity_units_override' => (string) ($row['capacity_units_override'] ?? ''),
            ];
        }

        $carId = $this->vehicleTypeId('car');
        $snapshotBefore = self::$database->query(
            <<<'SQL'
            SELECT booking_items.unit_price_snapshot
            FROM booking_items
            INNER JOIN bookings ON bookings.id = booking_items.booking_id
            WHERE bookings.booking_code = 'DEMO_FULL'
            SQL
        )->fetchColumn();
        $prices[(string) $carId]['price'] = '125000';
        $service->update(
            $serviceId,
            (string) $configured['code'],
            (string) $configured['name'],
            (string) ($configured['description'] ?? ''),
            $prices
        );

        self::assertSame('125000.00', $service->service($serviceId)['prices'][$carId]['price']);
        self::assertSame($snapshotBefore, self::$database->query(
            <<<'SQL'
            SELECT booking_items.unit_price_snapshot
            FROM booking_items
            INNER JOIN bookings ON bookings.id = booking_items.booking_id
            WHERE bookings.booking_code = 'DEMO_FULL'
            SQL
        )->fetchColumn());
    }

    public function testCapacityComesFromActiveReservationsAndCancelledDoesNotOccupy(): void
    {
        $slots = new WashSlotRepository(self::$database);
        $rows = $slots->findAvailable('2030-01-15');
        $nearFull = $this->rowByStart($rows, '09:00:00');
        $full = $this->rowByStart($rows, '10:00:00');

        self::assertSame(4, (int) $nearFull['used_capacity_units']);
        self::assertSame(1, (int) $nearFull['remaining_capacity_units']);
        self::assertSame(2, (int) $full['used_capacity_units']);
        self::assertSame(0, (int) $full['remaining_capacity_units']);
        self::assertNotContains('11:00:00', array_column($rows, 'start_time'));

        self::$database->exec("UPDATE bookings SET status = 'cancelled' WHERE booking_code = 'DEMO_FULL'");
        $afterCancellation = $this->rowByStart($slots->findAvailable('2030-01-15'), '10:00:00');
        self::assertSame(0, (int) $afterCancellation['used_capacity_units']);
        self::assertSame(2, (int) $afterCancellation['remaining_capacity_units']);
    }

    public function testAdminSlotValidationDuplicateAndClose(): void
    {
        $service = $this->slotService();
        $date = (new DateTimeImmutable('2099-01-01'))->format('Y-m-d');

        $invalidFixtures = [
            ['2020-01-01', '08:00', '09:00', '1'],
            [$date, '10:00', '09:00', '1'],
            [$date, '08:00', '09:00', '0'],
        ];

        foreach ($invalidFixtures as $invalid) {
            try {
                $service->create(...$invalid);
                self::fail('Khung giờ sai phải bị từ chối.');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }

        $slotId = $service->create($date, '08:00', '09:00', '7');
        self::assertSame(7, (int) self::$database->query(
            "SELECT capacity_units FROM wash_slots WHERE id = {$slotId}"
        )->fetchColumn());

        try {
            $service->create($date, '08:00', '09:00', '7');
            self::fail('Khung giờ trùng phải bị từ chối.');
        } catch (DuplicateCatalogException) {
            self::assertTrue(true);
        }

        $service->close($slotId);
        self::assertSame('closed', self::$database->query(
            "SELECT status FROM wash_slots WHERE id = {$slotId}"
        )->fetchColumn());
    }

    public function testHttpRoutesEnforceRoleCsrfValidationAndEscaping(): void
    {
        $guestData = [];
        [$guestApp] = $this->application($guestData);
        $catalog = $guestApp->handle(new Request('GET', '/dich-vu', [
            'vehicle_type_id' => (string) $this->vehicleTypeId('car'),
        ]));
        self::assertSame(200, $catalog->statusCode());
        self::assertStringContainsString('200.000', $catalog->body());

        $customerData = ['auth_user' => [
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng',
            'role' => 'customer',
        ]];
        [$customerApp] = $this->application($customerData);
        self::assertSame(200, $customerApp->handle(new Request('GET', '/khung-gio'))->statusCode());
        self::assertSame(403, $customerApp->handle(new Request('GET', '/admin/dich-vu'))->statusCode());

        $adminData = ['auth_user' => [
            'id' => $this->userId('0900000001'),
            'full_name' => 'Quản trị viên',
            'role' => 'admin',
        ]];
        [$adminApp, $tokens] = $this->application($adminData);
        self::assertSame(419, $adminApp->handle(new Request('POST', '/admin/khung-gio/them'))->statusCode());

        [$secondAdminApp, $secondTokens] = $this->application($adminData);
        $response = $secondAdminApp->handle(new Request('POST', '/admin/dich-vu/them', [], [
            '_csrf_token' => $secondTokens->token(),
            'code' => 'TEST_HTTP',
            'name' => 'Dịch vụ HTTP',
            'description' => '<script>alert(1)</script>',
            'prices' => $this->validPrices('65000', '25'),
        ]));
        self::assertSame(303, $response->statusCode());
        self::assertSame('/admin/dich-vu', $response->headers()['Location']);

        $public = $secondAdminApp->handle(new Request(
            'GET',
            '/dich-vu',
            ['vehicle_type_id' => (string) $this->vehicleTypeId('motorbike')]
        ));
        self::assertStringNotContainsString('<script>alert(1)</script>', $public->body());
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $public->body());
        self::assertNotSame('', $tokens->token());
    }

    private function catalogService(): ServiceCatalogService
    {
        return new ServiceCatalogService(
            new ServiceCatalogRepository(self::$database),
            new ServiceCatalogValidator()
        );
    }

    private function slotService(): WashSlotService
    {
        return new WashSlotService(
            new WashSlotRepository(self::$database),
            new WashSlotValidator(new DateTimeZone('Asia/Ho_Chi_Minh'))
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
            fn (): CatalogController => new CatalogController($this->catalogService(), $view, $session),
            fn (): AdminServiceController => new AdminServiceController(
                $this->catalogService(),
                $view,
                $session,
                $tokens
            ),
            fn (): WashSlotController => new WashSlotController($this->slotService(), $view, $session),
            fn (): AdminSlotController => new AdminSlotController(
                $this->slotService(),
                $view,
                $session,
                $tokens
            )
        );

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        )), $tokens];
    }

    /** @return array<string, array<string, string>> */
    private function validPrices(string $price, string $duration): array
    {
        $prices = [];

        foreach (self::$database->query('SELECT id FROM vehicle_types WHERE is_active = TRUE')->fetchAll() as $type) {
            $prices[(string) $type['id']] = [
                'is_supported' => '1',
                'is_active' => '1',
                'price' => $price,
                'duration_minutes' => $duration,
                'capacity_units_override' => '',
            ];
        }

        return $prices;
    }

    private function vehicleTypeId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM vehicle_types WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function rowByCode(array $rows, string $code): array
    {
        foreach ($rows as $row) {
            if ($row['code'] === $code) {
                return $row;
            }
        }

        self::fail('Không tìm thấy service fixture ' . $code . '.');
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function rowByStart(array $rows, string $startTime): array
    {
        foreach ($rows as $row) {
            if ($row['start_time'] === $startTime) {
                return $row;
            }
        }

        self::fail('Không tìm thấy slot fixture ' . $startTime . '.');
    }
}
