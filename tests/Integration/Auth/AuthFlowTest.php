<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Controllers\AuthController;
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
use App\Middleware\CsrfMiddleware;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Validation\AuthValidator;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthFlowTest extends TestCase
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
        self::$database->prepare('DELETE FROM users WHERE phone = :phone')->execute([
            'phone' => '0912345678',
        ]);
        $this->logFile = sys_get_temp_dir() . '/autowash-auth-' . bin2hex(random_bytes(8)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testRegisterIgnoresInjectedRoleAndStoresBcryptHash(): void
    {
        $sessionData = [];
        [$application, $tokens] = $this->application($sessionData);
        $response = $application->handle(new Request('POST', '/dang-ky', [], [
            '_csrf_token' => $tokens->token(),
            'phone' => '0912345678',
            'full_name' => '  Nguyễn   Văn An  ',
            'password' => 'MatKhau@123',
            'role' => 'admin',
        ]));

        self::assertSame(303, $response->statusCode());
        self::assertSame('/dang-nhap', $response->headers()['Location']);
        $user = $this->userByPhone('0912345678');
        self::assertSame('customer', $user['role']);
        self::assertSame('Nguyễn Văn An', $user['full_name']);
        self::assertNotSame('MatKhau@123', $user['password_hash']);
        self::assertTrue(password_verify('MatKhau@123', $user['password_hash']));
        self::assertStringStartsWith('$2y$', $user['password_hash']);
    }

    public function testRegisterRejectsDuplicateAndWeakPasswordWithoutCreatingUser(): void
    {
        $sessionData = [];
        [$application, $tokens] = $this->application($sessionData);
        $weak = $application->handle(new Request('POST', '/dang-ky', [], [
            '_csrf_token' => $tokens->token(),
            'phone' => '0912345678',
            'full_name' => 'Nguyễn Văn An',
            'password' => 'ngan',
        ]));

        self::assertSame(422, $weak->statusCode());
        self::assertStringContainsString('Mật khẩu phải có từ 8 đến 72 ký tự.', $weak->body());
        self::assertSame(0, $this->countPhone('0912345678'));

        $this->registerCustomer('0912345678');
        [$secondApplication, $secondTokens] = $this->application($sessionData);
        $duplicate = $secondApplication->handle(new Request('POST', '/dang-ky', [], [
            '_csrf_token' => $secondTokens->token(),
            'phone' => '0912345678',
            'full_name' => 'Người dùng khác',
            'password' => 'MatKhau@456',
        ]));

        self::assertSame(422, $duplicate->statusCode());
        self::assertStringContainsString('Số điện thoại đã được sử dụng.', $duplicate->body());
        self::assertSame(1, $this->countPhone('0912345678'));
    }

    public function testLoginUsesGenericErrorForWrongUnknownAndDisabledAccounts(): void
    {
        $wrong = $this->loginResponse('0900000002', 'SaiMatKhau');
        $unknown = $this->loginResponse('0999999999', 'SaiMatKhau');
        self::$database->exec("UPDATE users SET status = 'disabled' WHERE phone = '0900000002'");
        $disabled = $this->loginResponse('0900000002', 'AutoWash@123');

        foreach ([$wrong, $unknown, $disabled] as $response) {
            self::assertSame(422, $response->statusCode());
            self::assertStringContainsString(
                'Số điện thoại hoặc mật khẩu không chính xác.',
                $response->body()
            );
            self::assertStringNotContainsString('disabled', $response->body());
        }

        $log = file_get_contents($this->logFile);
        self::assertIsString($log);
        self::assertStringContainsString('Đăng nhập thất bại.', $log);
        self::assertStringNotContainsString('0900000002', $log);
        self::assertStringNotContainsString('AutoWash@123', $log);
    }

    public function testLoginCreatesMinimalIdentityUpdatesTimestampAndLogoutClearsSession(): void
    {
        $sessionData = [];
        [$application, $tokens] = $this->application($sessionData);
        $login = $application->handle(new Request('POST', '/dang-nhap', [], [
            '_csrf_token' => $tokens->token(),
            'phone' => '0900000002',
            'password' => 'AutoWash@123',
        ]));

        self::assertSame(303, $login->statusCode());
        self::assertSame('/tai-khoan', $login->headers()['Location']);
        self::assertSame('customer', $sessionData['auth_user']['role']);
        self::assertArrayNotHasKey('password_hash', $sessionData['auth_user']);
        self::assertNotNull($this->userByPhone('0900000002')['last_login_at']);

        $logout = $application->handle(new Request('POST', '/dang-xuat', [], [
            '_csrf_token' => $tokens->token(),
        ]));
        self::assertSame(303, $logout->statusCode());
        self::assertSame('/dang-nhap', $logout->headers()['Location']);
        self::assertSame([], $sessionData);
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
        $controllerFactory = fn (): AuthController => new AuthController(
            new AuthService(
                new UserRepository(self::$database),
                new AuthValidator(),
                $session,
                new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh'))
            ),
            $view,
            $session,
            $tokens
        );
        $registerRoutes = require dirname(__DIR__, 3) . '/routes/web.php';
        $registerRoutes($router, $view, $session, $tokens, $controllerFactory);

        return [new Application($router, new ErrorHandler(
            $view,
            new Logger($this->logFile, new DateTimeZone('Asia/Ho_Chi_Minh')),
            false
        )), $tokens];
    }

    private function loginResponse(string $phone, string $password): \App\Core\Response
    {
        $sessionData = [];
        [$application, $tokens] = $this->application($sessionData);

        return $application->handle(new Request('POST', '/dang-nhap', [], [
            '_csrf_token' => $tokens->token(),
            'phone' => $phone,
            'password' => $password,
        ]));
    }

    private function registerCustomer(string $phone): void
    {
        $sessionData = [];
        (new AuthService(
            new UserRepository(self::$database),
            new AuthValidator(),
            new Session($sessionData)
        ))->register($phone, 'Khách hàng thử nghiệm', 'MatKhau@123');
    }

    /** @return array<string, mixed> */
    private function userByPhone(string $phone): array
    {
        $statement = self::$database->prepare(
            'SELECT phone, full_name, password_hash, role, last_login_at FROM users WHERE phone = :phone'
        );
        $statement->execute(['phone' => $phone]);
        $user = $statement->fetch();

        self::assertIsArray($user);

        return $user;
    }

    private function countPhone(string $phone): int
    {
        $statement = self::$database->prepare('SELECT COUNT(*) FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);

        return (int) $statement->fetchColumn();
    }
}
