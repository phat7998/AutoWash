<?php

declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BookingRepository;
use App\Repositories\PromotionRepository;
use App\Services\BookingResourceCalculator;
use App\Services\BookingService;
use App\Services\BookingWindowPolicy;
use App\Services\PriceCalculator;
use App\Services\PromotionService;
use App\Validation\BookingValidator;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/bootstrap/environment.php';

[$script, $barrier, $result, $ownerId, $vehicleId, $slotId, $serviceId] = $argv;
$deadline = microtime(true) + 10;
while (!is_file($barrier) && microtime(true) < $deadline) {
    usleep(10_000);
}
if (!is_file($barrier)) {
    file_put_contents($result, 'error:Không nhận được tín hiệu bắt đầu.');
    exit(2);
}
$timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
$promotions = new PromotionService(new PromotionRepository(Database::connection()), $timezone);
$booking = new BookingService(
    new BookingRepository(Database::connection()),
    new BookingValidator(),
    new BookingWindowPolicy($timezone),
    new PriceCalculator(),
    new BookingResourceCalculator(),
    $timezone,
    promotionService: $promotions
);
try {
    $code = $booking->create((int) $ownerId, $vehicleId, $slotId, [$serviceId]);
    file_put_contents($result, 'success:' . $code);
} catch (Throwable $throwable) {
    file_put_contents($result, 'error:' . $throwable::class . ':' . $throwable->getMessage());
    exit(3);
}
