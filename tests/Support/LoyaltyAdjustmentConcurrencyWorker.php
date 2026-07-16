<?php

declare(strict_types=1);

use App\Core\Database;
use App\Exceptions\InsufficientPointsException;
use App\Repositories\LoyaltyTransactionRepository;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyService;
use App\Validation\LoyaltyAdjustmentValidator;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/bootstrap/environment.php';

[$script, $barrierFile, $resultFile, $adminId, $userId, $points] = $argv;
$deadline = microtime(true) + 10;

while (!is_file($barrierFile) && microtime(true) < $deadline) {
    usleep(10_000);
}

if (!is_file($barrierFile)) {
    file_put_contents($resultFile, 'error:Không nhận được tín hiệu bắt đầu.');
    exit(2);
}

$service = new LoyaltyService(
    new LoyaltyTransactionRepository(Database::connection()),
    new LoyaltyPointCalculator(10_000),
    new LoyaltyAdjustmentValidator(),
    new DateTimeZone('Asia/Ho_Chi_Minh')
);

try {
    $service->adjust(
        (int) $adminId,
        $userId,
        $points,
        'Kiểm thử hai điều chỉnh âm đồng thời.'
    );
    file_put_contents($resultFile, 'success');
} catch (InsufficientPointsException) {
    file_put_contents($resultFile, 'insufficient');
} catch (Throwable $throwable) {
    file_put_contents($resultFile, 'error:' . $throwable::class . ':' . $throwable->getMessage());
    exit(3);
}
