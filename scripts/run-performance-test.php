<?php

declare(strict_types=1);

use App\Core\Database;

// phpcs:disable PSR1.Files.SideEffects -- CLI này vừa điều phối vừa khai báo helper nội bộ.

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set((string) $app['timezone']);
$options = getopt('', ['base-url::', 'vus::', 'output::']);
$baseUrl = rtrim((string) ($options['base-url'] ?? 'http://127.0.0.1:8080'), '/');
$virtualUsers = filter_var($options['vus'] ?? 20, FILTER_VALIDATE_INT);
$output = (string) ($options['output'] ?? $projectRoot . '/storage/performance/result.json');

if (!is_int($virtualUsers) || $virtualUsers < 20) {
    fwrite(STDERR, "Workload yêu cầu tối thiểu 20 virtual user.\n");
    exit(1);
}

if (!function_exists('pcntl_fork') || !function_exists('curl_init')) {
    fwrite(STDERR, "Workload cần extension pcntl và curl trên PHP CLI.\n");
    exit(1);
}

try {
    $database = Database::connection();
    $fixtures = performanceFixtures($database, $virtualUsers);
    $databaseVersion = (string) $database->query('SELECT VERSION()')->fetchColumn();
    $bookingCount = (int) $database->query(
        "SELECT COUNT(*) FROM bookings WHERE booking_code LIKE 'PERF\\_%' ESCAPE '\\\\'"
    )->fetchColumn();
    Database::disconnect();

    if ($bookingCount < 10000) {
        throw new RuntimeException('Chưa có đủ 10.000 booking PERF_. Hãy chạy prepare-performance-data.php.');
    }

    $temporaryDirectory = sys_get_temp_dir() . '/autowash-perf-' . bin2hex(random_bytes(6));

    if (!mkdir($temporaryDirectory, 0700) && !is_dir($temporaryDirectory)) {
        throw new RuntimeException('Không thể tạo thư mục tạm cho workload.');
    }

    $startedAt = microtime(true);
    $children = [];

    foreach ($fixtures as $index => $fixture) {
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Không thể tạo virtual user process.');
        }

        if ($pid === 0) {
            $resultFile = $temporaryDirectory . '/vu-' . $index . '.json';

            try {
                $result = runVirtualUser($baseUrl, $fixture, $index);
            } catch (Throwable $throwable) {
                $result = ['samples' => [], 'errors' => [$throwable->getMessage()]];
            }

            file_put_contents($resultFile, json_encode($result, JSON_THROW_ON_ERROR));
            exit($result['errors'] === [] ? 0 : 1);
        }

        $children[$pid] = $index;
    }

    foreach ($children as $pid => $index) {
        pcntl_waitpid($pid, $status);
    }

    $samples = [];
    $errors = [];

    foreach ($fixtures as $index => $fixture) {
        $file = $temporaryDirectory . '/vu-' . $index . '.json';
        $result = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (!is_array($result)) {
            $errors[] = sprintf('VU %d không trả kết quả.', $index + 1);
            continue;
        }

        foreach ($result['samples'] as $sample) {
            $samples[$sample['metric']][] = (float) $sample['seconds'];
        }

        foreach ($result['errors'] as $error) {
            $errors[] = sprintf('VU %d: %s', $index + 1, $error);
        }
    }

    removeTemporaryDirectory($temporaryDirectory);
    $thresholds = [
        'login' => 1.0,
        'services' => 1.0,
        'slots' => 1.0,
        'history' => 1.0,
        'booking_create' => 2.0,
        'reward_redeem' => 2.0,
        'admin_report' => 2.0,
    ];
    $metrics = [];
    $passed = true;

    foreach ($thresholds as $metric => $threshold) {
        $values = $samples[$metric] ?? [];
        $p95 = percentile($values, 0.95);
        $metricPassed = count($values) === $virtualUsers && $p95 < $threshold;
        $passed = $passed && $metricPassed;
        $metrics[$metric] = [
            'samples' => count($values),
            'p95_ms' => round($p95 * 1000, 2),
            'max_ms' => round(($values === [] ? 0.0 : max($values)) * 1000, 2),
            'threshold_ms' => (int) ($threshold * 1000),
            'passed' => $metricPassed,
        ];
    }

    $requestCount = array_sum(array_map('count', $samples)) + count($errors);
    $errorRate = $requestCount === 0 ? 1.0 : count($errors) / $requestCount;
    $passed = $passed && $errorRate < 0.01;
    $report = [
        'status' => $passed ? 'passed' : 'failed',
        'executed_at' => date(DATE_ATOM),
        'base_url' => $baseUrl,
        'dataset_bookings' => $bookingCount,
        'virtual_users' => $virtualUsers,
        'duration_seconds' => round(microtime(true) - $startedAt, 3),
        'error_count' => count($errors),
        'error_rate_percent' => round($errorRate * 100, 3),
        'environment' => [
            'runner_php' => PHP_VERSION,
            'database' => $databaseVersion,
            'os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
            'cpu' => cpuDescription(),
        ],
        'metrics' => $metrics,
        'errors' => $errors,
        'limitations' => [
            'Workload chạy trên một máy local và không phải SLA thương mại.',
            'Mỗi VU thực hiện một request ghi cho booking và reward; '
                . 'external LPR không nằm trong workload.',
        ],
    ];
    $directory = dirname($output);

    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục output performance.');
    }

    file_put_contents(
        $output,
        json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
    );
    echo json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($passed ? 0 : 1);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Workload thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

/** @return list<array<string, int|string>> */
function performanceFixtures(PDO $database, int $limit): array
{
    $statement = $database->prepare(
        <<<'SQL'
        SELECT users.phone, vehicles.id AS vehicle_id
        FROM users
        INNER JOIN vehicles ON vehicles.user_id = users.id AND vehicles.is_active = TRUE
        WHERE users.phone LIKE '0988%'
          AND users.full_name LIKE 'Khách hiệu năng %'
          AND users.status = 'active'
        ORDER BY users.phone
        LIMIT :fixture_limit
        SQL
    );
    $statement->bindValue('fixture_limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    $users = $statement->fetchAll();
    $slot = $database->query(
        <<<'SQL'
        SELECT id, slot_date
        FROM wash_slots
        WHERE slot_date = CURRENT_DATE + INTERVAL 1 DAY
          AND start_time = '16:00:00'
          AND status = 'open'
        LIMIT 1
        SQL
    )->fetch();
    $serviceId = (int) $database->query(
        "SELECT id FROM services WHERE code = 'STANDARD_WASH' AND is_active = TRUE"
    )->fetchColumn();
    $rewardId = (int) $database->query(
        "SELECT id FROM rewards WHERE code = 'DISCOUNT_10K' AND is_active = TRUE"
    )->fetchColumn();

    if (count($users) !== $limit || !is_array($slot) || $serviceId <= 0 || $rewardId <= 0) {
        throw new RuntimeException(
            'Fixture workload chưa đầy đủ hoặc không đồng bộ ngày hiện tại.'
        );
    }

    return array_map(static fn (array $user): array => [
        'phone' => (string) $user['phone'],
        'password' => 'Performance@123',
        'vehicle_id' => (int) $user['vehicle_id'],
        'slot_id' => (int) $slot['id'],
        'slot_date' => (string) $slot['slot_date'],
        'service_id' => $serviceId,
        'reward_id' => $rewardId,
    ], $users);
}

/**
 * @param array<string, int|string> $fixture
 * @return array{samples: list<array<string, float|string>>, errors: list<string>}
 */
function runVirtualUser(string $baseUrl, array $fixture, int $index): array
{
    $cookieFile = tempnam(sys_get_temp_dir(), 'autowash-cookie-');

    if ($cookieFile === false) {
        throw new RuntimeException('Không thể tạo cookie jar cho virtual user.');
    }

    $samples = [];
    $errors = [];

    try {
        $loginPage = httpRequest($baseUrl . '/dang-nhap', $cookieFile);
        $loginToken = csrfToken($loginPage['body']);
        measureRequest($samples, $errors, 'login', httpRequest(
            $baseUrl . '/dang-nhap',
            $cookieFile,
            ['_csrf_token' => $loginToken, 'phone' => $fixture['phone'], 'password' => $fixture['password']]
        ), [303]);
        measureRequest($samples, $errors, 'services', httpRequest(
            $baseUrl . '/dich-vu',
            $cookieFile
        ), [200]);
        measureRequest($samples, $errors, 'slots', httpRequest(
            $baseUrl . '/khung-gio?ngay=' . rawurlencode((string) $fixture['slot_date']),
            $cookieFile
        ), [200]);
        measureRequest($samples, $errors, 'history', httpRequest(
            $baseUrl . '/lich-dat',
            $cookieFile
        ), [200]);

        $bookingPage = httpRequest(
            $baseUrl . '/dat-lich?vehicle_id=' . rawurlencode((string) $fixture['vehicle_id']),
            $cookieFile
        );
        $bookingToken = csrfToken($bookingPage['body']);
        measureRequest($samples, $errors, 'booking_create', httpRequest(
            $baseUrl . '/dat-lich',
            $cookieFile,
            [
                '_csrf_token' => $bookingToken,
                'vehicle_id' => $fixture['vehicle_id'],
                'start_slot_id' => $fixture['slot_id'],
                'service_ids' => [$fixture['service_id']],
                'reward_redemption_id' => '',
            ]
        ), [303]);

        $rewardPage = httpRequest($baseUrl . '/doi-thuong', $cookieFile);
        $rewardToken = csrfToken($rewardPage['body']);
        measureRequest($samples, $errors, 'reward_redeem', httpRequest(
            $baseUrl . '/doi-thuong/' . $fixture['reward_id'],
            $cookieFile,
            ['_csrf_token' => $rewardToken]
        ), [303]);
    } finally {
        @unlink($cookieFile);
    }

    $adminCookie = tempnam(sys_get_temp_dir(), 'autowash-admin-cookie-');

    if ($adminCookie === false) {
        throw new RuntimeException('Không thể tạo cookie jar admin.');
    }

    try {
        $adminLogin = httpRequest($baseUrl . '/dang-nhap', $adminCookie);
        $adminToken = csrfToken($adminLogin['body']);
        $adminAuth = httpRequest(
            $baseUrl . '/dang-nhap',
            $adminCookie,
            ['_csrf_token' => $adminToken, 'phone' => '0900000001', 'password' => 'AutoWash@123']
        );

        if ($adminAuth['status'] !== 303) {
            throw new RuntimeException('Không thể đăng nhập admin cho workload report.');
        }

        measureRequest($samples, $errors, 'admin_report', httpRequest(
            $baseUrl . '/admin',
            $adminCookie
        ), [200]);
    } finally {
        @unlink($adminCookie);
    }

    return ['samples' => $samples, 'errors' => $errors];
}

/** @return array{status: int, body: string, seconds: float} */
function httpRequest(string $url, string $cookieFile, ?array $form = null): array
{
    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException('Không thể khởi tạo HTTP client.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);

    if ($form !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($form));
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Accept: text/html',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    $body = curl_exec($handle);

    if (!is_string($body)) {
        $message = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException('HTTP request lỗi: ' . $message);
    }

    $result = [
        'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
        'body' => $body,
        'seconds' => (float) curl_getinfo($handle, CURLINFO_TOTAL_TIME),
    ];
    curl_close($handle);

    return $result;
}

function csrfToken(string $html): string
{
    if (preg_match('/name="_csrf_token" value="([^"]+)"/', $html, $matches) !== 1) {
        throw new RuntimeException('Không tìm thấy CSRF token trong response workload.');
    }

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * @param list<array<string, float|string>> $samples
 * @param list<string> $errors
 * @param array{status: int, body: string, seconds: float} $response
 * @param list<int> $expectedStatuses
 */
function measureRequest(array &$samples, array &$errors, string $metric, array $response, array $expectedStatuses): void
{
    if (!in_array($response['status'], $expectedStatuses, true)) {
        $errors[] = sprintf('%s trả HTTP %d.', $metric, $response['status']);
        return;
    }

    $samples[] = ['metric' => $metric, 'seconds' => $response['seconds']];
}

/** @param list<float> $values */
function percentile(array $values, float $percentile): float
{
    if ($values === []) {
        return INF;
    }

    sort($values, SORT_NUMERIC);
    $index = max(0, (int) ceil(count($values) * $percentile) - 1);

    return $values[$index];
}

function cpuDescription(): string
{
    $cpuInfo = is_readable('/proc/cpuinfo') ? (string) file_get_contents('/proc/cpuinfo') : '';

    if (preg_match('/^model name\s*:\s*(.+)$/m', $cpuInfo, $matches) === 1) {
        return trim($matches[1]);
    }

    return 'Không xác định';
}

function removeTemporaryDirectory(string $directory): void
{
    foreach (glob($directory . '/*') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($directory);
}
