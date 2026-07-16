<?php

declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LoyaltyTransactionRepository;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyService;
use App\Validation\LoyaltyAdjustmentValidator;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$appConfig = require $projectRoot . '/config/app.php';
$loyaltyConfig = require $projectRoot . '/config/loyalty.php';
$timezone = new DateTimeZone((string) $appConfig['timezone']);
$service = new LoyaltyService(
    new LoyaltyTransactionRepository(Database::connection()),
    new LoyaltyPointCalculator((int) $loyaltyConfig['point_unit_amount']),
    new LoyaltyAdjustmentValidator(),
    new LoyaltyDebitAllocator(),
    new LoyaltyExpirationPolicy($timezone),
    $timezone
);

try {
    $result = $service->expirePoints();
    printf(
        "Đã hết hạn %d credit lot, tổng cộng %d điểm.\n",
        $result['expired_lots'],
        $result['expired_points']
    );
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Expire points thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
