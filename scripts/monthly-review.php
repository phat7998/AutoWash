<?php

declare(strict_types=1);

use App\Core\Database;
use App\Repositories\TierRepository;
use App\Repositories\ResearchEventRepository;
use App\Services\ResearchEventService;
use App\Services\TierReviewPolicy;
use App\Services\TierReviewService;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$appConfig = require $projectRoot . '/config/app.php';
$timezone = new DateTimeZone((string) $appConfig['timezone']);
$service = new TierReviewService(
    new TierRepository(Database::connection()),
    new TierReviewPolicy($timezone),
    $timezone,
    new ResearchEventService(new ResearchEventRepository(Database::connection()))
);

try {
    $result = $service->run();
    printf(
        "Đã hoàn tất kỳ xét hạng %s cho %d khách hàng.\n",
        $result['review_period'],
        $result['processed_users']
    );
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Xét hạng hàng tháng thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
