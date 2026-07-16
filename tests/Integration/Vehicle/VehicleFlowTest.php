<?php

declare(strict_types=1);

namespace Tests\Integration\Vehicle;

use App\Controllers\VehicleController;
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
use App\Exceptions\DuplicateLicensePlateException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Middleware\CsrfMiddleware;
use App\Repositories\VehicleRepository;
use App\Services\LicensePlateService;
use App\Services\VehicleService;
use App\Validation\VehicleValidator;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class VehicleFlowTest extends TestCase
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
        self::$database->exec("DELETE FROM vehicles WHERE normalized_plate LIKE '88%'");
        $this->logFile = sys_get_temp_dir() . '/autowash-vehicle-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testCreatesAllFourConfiguredVehicleTypesAndNormalizesPlate(): void
    {
        $service = $this->service();
        $ownerId = $this->userId('0900000002');
        $types = $this->typeIds();
        $fixtures = [
            'motorbike' => '88a-100.01',
            'car' => '88b-10002',
            'truck' => '88c 10003',
            'bus' => '88d.10004',
        ];

        foreach ($fixtures as $type => $plate) {
            $service->create($ownerId, (string) $types[$type], $plate, '', '', '');
        }

        $created = self::$database->query(
            "SELECT normalized_plate FROM vehicles WHERE normalized_plate LIKE '88%' ORDER BY normalized_plate"
        )->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame(['88A10001', '88B10002', '88C10003', '88D10004'], $created);
    }

    public function testDuplicateNormalizedPlateBecomesDomainError(): void
    {
        $service = $this->service();
        $ownerId = $this->userId('0900000002');
        $typeId = $this->typeIds()['motorbike'];
        $service->create($ownerId, (string) $typeId, '88A-123.45', '', '', '');

        $this->expectException(DuplicateLicensePlateException::class);
        $this->expectExceptionMessage('đã được đăng ký');
        $service->create($this->userId('0900000003'), (string) $typeId, '88 a 12345', '', '', '');
    }

    public function testRejectsUnknownAndInactiveVehicleType(): void
    {
        $service = $this->service();
        $ownerId = $this->userId('0900000002');

        try {
            $service->create($ownerId, '999999', '88A-22222', '', '', '');
            self::fail('Loại xe không tồn tại phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('vehicle_type_id', $exception->errors());
        }

        $busId = $this->typeIds()['bus'];
        self::$database->prepare(
            'UPDATE vehicle_types SET is_active = FALSE WHERE id = :id'
        )->execute(['id' => $busId]);

        $this->expectException(ValidationException::class);
        $service->create($ownerId, (string) $busId, '88A-33333', '', '', '');
    }

    public function testOwnershipBlocksReadUpdateAndDeactivateThenOwnerCanDeactivate(): void
    {
        $service = $this->service();
        $ownerId = $this->userId('0900000002');
        $otherOwnerId = $this->userId('0900000003');
        $vehicleId = $this->vehicleId('59A12345');

        foreach (['read', 'update', 'deactivate'] as $operation) {
            try {
                match ($operation) {
                    'read' => $service->ownedVehicle($vehicleId, $otherOwnerId),
                    'update' => $service->update(
                        $vehicleId,
                        $otherOwnerId,
                        (string) $this->typeIds()['car'],
                        '88A-44444',
                        '',
                        '',
                        ''
                    ),
                    'deactivate' => $service->deactivate($vehicleId, $otherOwnerId),
                };
                self::fail(sprintf('Ownership phải chặn thao tác %s.', $operation));
            } catch (VehicleOwnershipException) {
                self::assertTrue(true);
            }
        }

        $service->deactivate($vehicleId, $ownerId);
        self::assertSame(0, (int) self::$database->query(
            "SELECT is_active FROM vehicles WHERE normalized_plate = '59A12345'"
        )->fetchColumn());
        self::assertSame(1, (int) self::$database->query(
            "SELECT COUNT(*) FROM vehicles WHERE normalized_plate = '59A12345'"
        )->fetchColumn());
    }

    public function testOwnerCanEditVehicleAndNormalizedPlateIsUpdated(): void
    {
        $ownerId = $this->userId('0900000002');
        $vehicleId = $this->vehicleId('59A12345');
        $this->service()->update(
            $vehicleId,
            $ownerId,
            (string) $this->typeIds()['car'],
            '88 ab-777.77',
            '  Toyota  ',
            ' Vios ',
            'Thông tin đã sửa'
        );

        $vehicle = self::$database->query(
            "SELECT normalized_plate, display_plate, brand, model FROM vehicles WHERE id = {$vehicleId}"
        )->fetch();
        self::assertIsArray($vehicle);
        self::assertSame('88AB77777', $vehicle['normalized_plate']);
        self::assertSame('88 AB-777.77', $vehicle['display_plate']);
        self::assertSame('Toyota', $vehicle['brand']);
        self::assertSame('Vios', $vehicle['model']);
    }

    public function testManualHttpFlowUsesBackendValidationCsrfPrgAndEscaping(): void
    {
        $ownerId = $this->userId('0900000002');
        $sessionData = ['auth_user' => [
            'id' => $ownerId,
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$application, $tokens] = $this->application($sessionData);

        $list = $application->handle(new Request('GET', '/phuong-tien'));
        self::assertSame(200, $list->statusCode());
        self::assertStringContainsString('59A-123.45', $list->body());
        self::assertStringNotContainsString('51AB-12345', $list->body());

        $invalid = $application->handle(new Request('POST', '/phuong-tien/them', [], [
            '_csrf_token' => $tokens->token(),
            'vehicle_type_id' => (string) $this->typeIds()['car'],
            'display_plate' => 'BIEN NGOAI PHAM VI',
            'brand' => '<script>alert(1)</script>',
            'model' => '',
            'notes' => '',
        ]));
        self::assertSame(422, $invalid->statusCode());
        self::assertStringContainsString('định dạng dân sự Việt Nam', $invalid->body());
        self::assertStringNotContainsString('<script>alert(1)</script>', $invalid->body());
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $invalid->body());

        [$secondApplication, $secondTokens] = $this->application($sessionData);
        $created = $secondApplication->handle(new Request('POST', '/phuong-tien/them', [], [
            '_csrf_token' => $secondTokens->token(),
            'vehicle_type_id' => (string) $this->typeIds()['car'],
            'display_plate' => '88AB-55555',
            'brand' => 'Toyota',
            'model' => 'Vios',
            'notes' => 'Nhập biển số thủ công',
        ]));
        self::assertSame(303, $created->statusCode());
        self::assertSame('/phuong-tien', $created->headers()['Location']);
        self::assertSame(1, (int) self::$database->query(
            "SELECT COUNT(*) FROM vehicles WHERE normalized_plate = '88AB55555'"
        )->fetchColumn());

        $missingCsrf = $secondApplication->handle(new Request('POST', '/phuong-tien/them', [], [
            'vehicle_type_id' => (string) $this->typeIds()['car'],
            'display_plate' => '88A-66666',
        ]));
        self::assertSame(419, $missingCsrf->statusCode());
    }

    public function testDirectUrlCannotExposeAnotherCustomersVehicle(): void
    {
        $sessionData = ['auth_user' => [
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$application] = $this->application($sessionData);
        $otherVehicleId = $this->vehicleId('51AB12345');
        $response = $application->handle(new Request('GET', '/phuong-tien/' . $otherVehicleId . '/sua'));

        self::assertSame(404, $response->statusCode());
        self::assertStringContainsString('Không tìm thấy phương tiện', $response->body());
        self::assertStringNotContainsString('51AB-12345', $response->body());
    }

    private function service(): VehicleService
    {
        $plates = new LicensePlateService();

        return new VehicleService(
            new VehicleRepository(self::$database),
            new VehicleValidator($plates),
            $plates
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
            static fn (): never => throw new \RuntimeException('Không cần AuthController cho test vehicle.'),
            fn (): VehicleController => new VehicleController($this->service(), $view, $session, $tokens)
        );

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        )), $tokens];
    }

    /** @return array<string, int> */
    private function typeIds(): array
    {
        $rows = self::$database->query('SELECT code, id FROM vehicle_types')->fetchAll();

        return array_map('intval', array_column($rows, 'id', 'code'));
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function vehicleId(string $normalizedPlate): int
    {
        $statement = self::$database->prepare(
            'SELECT id FROM vehicles WHERE normalized_plate = :normalized_plate'
        );
        $statement->execute(['normalized_plate' => $normalizedPlate]);

        return (int) $statement->fetchColumn();
    }
}
