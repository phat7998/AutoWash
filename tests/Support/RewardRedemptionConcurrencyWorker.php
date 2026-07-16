<?php

declare(strict_types=1);

use App\Core\Database;
use App\Exceptions\InsufficientPointsException;
use App\Repositories\LoyaltyTransactionRepository;
use App\Repositories\RewardRepository;
use App\Repositories\ResearchEventRepository;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyService;
use App\Services\RewardService;
use App\Services\ResearchEventService;
use App\Validation\LoyaltyAdjustmentValidator;
use App\Validation\RewardValidator;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/bootstrap/environment.php';
[$script, $barrierFile, $resultFile, $userId, $rewardId] = $argv;
$deadline = microtime(true) + 10;

while (!is_file($barrierFile) && microtime(true) < $deadline) {
    usleep(10_000);
}

if (!is_file($barrierFile)) {
    file_put_contents($resultFile, 'error:Không nhận được tín hiệu bắt đầu.');
    exit(2);
}

$timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
$research = new ResearchEventService(new ResearchEventRepository(Database::connection()));
$loyalty = new LoyaltyService(
    new LoyaltyTransactionRepository(Database::connection()),
    new LoyaltyPointCalculator(10_000),
    new LoyaltyAdjustmentValidator(),
    new LoyaltyDebitAllocator(),
    new LoyaltyExpirationPolicy($timezone),
    $timezone,
    $research
);
$rewards = new RewardService(
    new RewardRepository(Database::connection()),
    $loyalty,
    new RewardValidator(),
    $timezone,
    $research
);

try {
    $rewards->redeem((int) $userId, (int) $rewardId);
    file_put_contents($resultFile, 'success');
} catch (InsufficientPointsException) {
    file_put_contents($resultFile, 'insufficient');
} catch (Throwable $throwable) {
    file_put_contents($resultFile, 'error:' . $throwable::class . ':' . $throwable->getMessage());
    exit(3);
}
