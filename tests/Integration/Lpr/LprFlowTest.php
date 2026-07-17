<?php

declare(strict_types=1);

namespace Tests\Integration\Lpr;

use App\Contracts\LprProviderInterface;
use App\Controllers\VehicleController;
use App\Core\Application;
use App\Core\CsrfTokenManager;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\UploadedFile;
use App\Core\View;
use App\DTO\RecognitionResult;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;
use App\Exceptions\LprProviderException;
use App\Exceptions\ValidationException;
use App\Middleware\CsrfMiddleware;
use App\Providers\MockLprProvider;
use App\Repositories\LprAttemptRepository;
use App\Repositories\VehicleRepository;
use App\Services\LicensePlateService;
use App\Services\LprService;
use App\Services\LprUploadService;
use App\Services\VehicleService;
use App\Validation\VehicleValidator;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

final class LprFlowTest extends TestCase
{
    private static PDO $database;
    private static DatabaseSeeder $seeder;
    private string $runtimeRoot;
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
        self::$database->exec('DELETE FROM lpr_attempts');
        self::$database->exec("DELETE FROM vehicles WHERE normalized_plate LIKE '88%'");
        $this->runtimeRoot = sys_get_temp_dir() . '/autowash-lpr-' . bin2hex(random_bytes(8));
        $this->logFile = $this->runtimeRoot . '/app.log';
        mkdir($this->runtimeRoot, 0750, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->runtimeRoot);
    }

    public function testSafeUploadRejectsInvalidMimeAndOversizedImage(): void
    {
        $uploads = new LprUploadService($this->runtimeRoot, 'storage/uploads/lpr', 1024);

        try {
            $uploads->store($this->textUpload());
            self::fail('Nội dung không phải ảnh phải bị từ chối dù client khai MIME ảnh.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('JPEG, PNG hoặc WebP', $exception->errors()['plate_image']);
        }

        $smallLimit = new LprUploadService($this->runtimeRoot, 'storage/uploads/lpr', 10);

        try {
            $smallLimit->store($this->pngUpload());
            self::fail('Ảnh vượt giới hạn phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('không được vượt quá', $exception->errors()['plate_image']);
        }

        self::assertSame(0, (int) self::$database->query('SELECT COUNT(*) FROM lpr_attempts')->fetchColumn());
    }

    public function testUploadStorageCannotBeConfiguredInsidePublicDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ngoài thư mục public');

        new LprUploadService($this->runtimeRoot, 'public/uploads/lpr', 1024);
    }

    public function testMockSuccessNormalizesLowConfidenceAndRecordsManualOverride(): void
    {
        $ownerId = $this->userId('0900000002');
        $service = $this->lprService(new MockLprProvider('88a-123.45', 0.55));
        $outcome = $service->recognize($ownerId, $this->pngUpload('anh-bien-so.php'));

        self::assertSame('88A12345', $outcome->normalizedText);
        self::assertSame('success', $outcome->status);
        self::assertNotNull($outcome->warning);
        $attempt = $service->assertOwnedAttempt($outcome->attemptId, $ownerId);
        self::assertSame('mock', $attempt['provider']);
        self::assertStringStartsWith('storage/uploads/lpr/', (string) $attempt['image_path']);
        self::assertStringNotContainsString('anh-bien-so.php', (string) $attempt['image_path']);
        self::assertFileExists($this->runtimeRoot . '/' . $attempt['image_path']);

        $service->recordConfirmation($outcome->attemptId, $ownerId, '88A-123.46');
        $updated = $service->assertOwnedAttempt($outcome->attemptId, $ownerId);
        self::assertSame('manual_override', $updated['status']);
        self::assertSame('88A12346', $updated['normalized_text']);
    }

    public function testProviderFailureAndTimeoutKeepManualFallbackAndWriteSafeLog(): void
    {
        foreach (['provider lỗi', 'provider timeout'] as $message) {
            $provider = new class ($message) implements LprProviderInterface {
                public function __construct(private readonly string $message)
                {
                }

                public function name(): string
                {
                    return 'test-provider';
                }

                public function recognize(string $imagePath): RecognitionResult
                {
                    throw new LprProviderException($this->message);
                }
            };
            $outcome = $this->lprService($provider)->recognize(
                $this->userId('0900000002'),
                $this->pngUpload()
            );

            self::assertSame('failed', $outcome->status);
            self::assertStringContainsString('nhập biển số thủ công', (string) $outcome->warning);
        }

        $attempts = self::$database->query(
            "SELECT status, recognized_text, confidence FROM lpr_attempts ORDER BY id"
        )->fetchAll();
        self::assertCount(2, $attempts);
        self::assertSame(['failed', 'failed'], array_column($attempts, 'status'));
        self::assertNull($attempts[0]['recognized_text']);
        self::assertNull($attempts[0]['confidence']);
        $log = (string) file_get_contents($this->logFile);
        self::assertStringContainsString('Provider nhận diện biển số thất bại', $log);
        self::assertStringNotContainsString('provider lỗi', $log);
        self::assertStringNotContainsString('provider timeout', $log);
    }

    public function testHttpFlowConfirmsEditedPlateAndProtectsAttemptImageByOwner(): void
    {
        $ownerId = $this->userId('0900000002');
        $sessionData = ['auth_user' => [
            'id' => $ownerId,
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$application, $tokens] = $this->application(
            $sessionData,
            new MockLprProvider('88a-123.45', 0.95)
        );
        $recognition = $application->handle(new Request(
            'POST',
            '/phuong-tien/nhan-dien',
            [],
            ['_csrf_token' => $tokens->token()],
            [],
            [],
            ['plate_image' => $this->pngUpload()]
        ));

        self::assertSame(200, $recognition->statusCode());
        self::assertStringContainsString('Kết quả gợi ý', $recognition->body());
        self::assertStringContainsString('88a-123.45', $recognition->body());
        self::assertStringContainsString('Bạn luôn có thể sửa hoặc nhập thủ công', $recognition->body());
        self::assertStringNotContainsString('provider mock offline', $recognition->body());
        $attemptId = (int) self::$database->query('SELECT MAX(id) FROM lpr_attempts')->fetchColumn();

        $image = $application->handle(new Request(
            'GET',
            '/phuong-tien/nhan-dien/' . $attemptId . '/anh'
        ));
        self::assertSame(200, $image->statusCode());
        self::assertSame('image/png', $image->headers()['Content-Type']);
        self::assertSame('nosniff', $image->headers()['X-Content-Type-Options']);

        $created = $application->handle(new Request('POST', '/phuong-tien/them', [], [
            '_csrf_token' => $tokens->token(),
            'lpr_attempt_id' => (string) $attemptId,
            'vehicle_type_id' => (string) $this->typeId('motorbike'),
            'display_plate' => '88A-123.46',
            'brand' => '',
            'model' => '',
            'notes' => 'Đã xác nhận từ ảnh',
        ]));
        self::assertSame(303, $created->statusCode());
        self::assertSame(1, (int) self::$database->query(
            "SELECT COUNT(*) FROM vehicles WHERE normalized_plate = '88A12346'"
        )->fetchColumn());
        self::assertSame('manual_override', self::$database->query(
            'SELECT status FROM lpr_attempts WHERE id = ' . $attemptId
        )->fetchColumn());

        $otherSession = ['auth_user' => [
            'id' => $this->userId('0900000003'),
            'full_name' => 'Khách hàng khác',
            'role' => 'customer',
        ]];
        [$otherApplication] = $this->application($otherSession, new MockLprProvider('51A12345', 0.95));
        $forbidden = $otherApplication->handle(new Request(
            'GET',
            '/phuong-tien/nhan-dien/' . $attemptId . '/anh'
        ));
        self::assertSame(404, $forbidden->statusCode());
        self::assertStringNotContainsString('storage/uploads', $forbidden->body());

        $invalidId = $application->handle(new Request('GET', '/phuong-tien/nhan-dien/sai/anh'));
        self::assertSame(404, $invalidId->statusCode());
        self::assertStringContainsString('lần nhận diện biển số', $invalidId->body());
    }

    public function testHttpInvalidUploadDuplicateAndCsrfDoNotBreakManualForm(): void
    {
        $sessionData = ['auth_user' => [
            'id' => $this->userId('0900000002'),
            'full_name' => 'Khách hàng Demo',
            'role' => 'customer',
        ]];
        [$application, $tokens] = $this->application($sessionData, new MockLprProvider('59A12345', 0.95));

        $missingCsrf = $application->handle(new Request(
            'POST',
            '/phuong-tien/nhan-dien',
            [],
            [],
            [],
            [],
            ['plate_image' => $this->pngUpload()]
        ));
        self::assertSame(419, $missingCsrf->statusCode());

        $invalid = $application->handle(new Request(
            'POST',
            '/phuong-tien/nhan-dien',
            [],
            ['_csrf_token' => $tokens->token()],
            [],
            [],
            ['plate_image' => $this->textUpload()]
        ));
        self::assertSame(422, $invalid->statusCode());
        self::assertStringContainsString('Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP', $invalid->body());
        self::assertStringContainsString('name="display_plate"', $invalid->body());

        $recognized = $application->handle(new Request(
            'POST',
            '/phuong-tien/nhan-dien',
            [],
            ['_csrf_token' => $tokens->token()],
            [],
            [],
            ['plate_image' => $this->pngUpload()]
        ));
        self::assertSame(200, $recognized->statusCode());
        $attemptId = (int) self::$database->query('SELECT MAX(id) FROM lpr_attempts')->fetchColumn();
        $duplicate = $application->handle(new Request('POST', '/phuong-tien/them', [], [
            '_csrf_token' => $tokens->token(),
            'lpr_attempt_id' => (string) $attemptId,
            'vehicle_type_id' => (string) $this->typeId('motorbike'),
            'display_plate' => '59A-123.45',
            'brand' => '',
            'model' => '',
            'notes' => '',
        ]));
        self::assertSame(422, $duplicate->statusCode());
        self::assertStringContainsString('đã được đăng ký', $duplicate->body());
        self::assertSame(1, (int) self::$database->query(
            "SELECT COUNT(*) FROM vehicles WHERE normalized_plate = '59A12345'"
        )->fetchColumn());
    }

    private function lprService(LprProviderInterface $provider): LprService
    {
        return new LprService(
            new LprAttemptRepository(self::$database),
            new LprUploadService($this->runtimeRoot, 'storage/uploads/lpr', 5 * 1024 * 1024),
            $provider,
            new LicensePlateService(),
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            0.80
        );
    }

    /**
     * @param array<string, mixed> $sessionData
     * @return array{Application, CsrfTokenManager}
     */
    private function application(array &$sessionData, LprProviderInterface $provider): array
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
            static fn (): never => throw new \RuntimeException('Không cần AuthController cho test LPR.'),
            fn (): VehicleController => new VehicleController(
                $this->vehicleService(),
                $view,
                $session,
                $tokens,
                $this->lprService($provider)
            )
        );

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        )), $tokens];
    }

    private function vehicleService(): VehicleService
    {
        $plates = new LicensePlateService();

        return new VehicleService(
            new VehicleRepository(self::$database),
            new VehicleValidator($plates),
            $plates
        );
    }

    private function pngUpload(string $originalName = 'plate.png'): UploadedFile
    {
        $path = tempnam($this->runtimeRoot, 'upload-');
        self::assertIsString($path);
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
        self::assertIsString($content);
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, 'text/plain', strlen($content), UPLOAD_ERR_OK, true);
    }

    private function textUpload(): UploadedFile
    {
        $path = tempnam($this->runtimeRoot, 'upload-');
        self::assertIsString($path);
        file_put_contents($path, '<?php echo "không phải ảnh";');

        return new UploadedFile($path, 'payload.png', 'image/png', 27, UPLOAD_ERR_OK, true);
    }

    private function userId(string $phone): int
    {
        $statement = self::$database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }

    private function typeId(string $code): int
    {
        $statement = self::$database->prepare('SELECT id FROM vehicle_types WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
