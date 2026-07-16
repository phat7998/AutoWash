<?php

declare(strict_types=1);

use App\Core\Database;
use App\Exceptions\SlotFullException;
use App\Repositories\BookingRepository;
use App\Services\BookingService;
use App\Services\BookingResourceCalculator;
use App\Services\BookingWindowPolicy;
use App\Services\PriceCalculator;
use App\Validation\BookingValidator;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/bootstrap/environment.php';

[$script, $barrierFile, $resultFile, $ownerId, $vehicleId, $slotId, $serviceId] = $argv;
$deadline = microtime(true) + 10;

while (!is_file($barrierFile) && microtime(true) < $deadline) {
    usleep(10_000);
}

if (!is_file($barrierFile)) {
    file_put_contents($resultFile, 'error:Không nhận được tín hiệu bắt đầu.');
    exit(2);
}

$timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
$service = new BookingService(
    new BookingRepository(Database::connection()),
    new BookingValidator(),
    new BookingWindowPolicy($timezone),
    new PriceCalculator(),
    new BookingResourceCalculator(),
    $timezone
);

try {
    $service->create((int) $ownerId, $vehicleId, $slotId, [$serviceId]);
    file_put_contents($resultFile, 'success');
} catch (SlotFullException) {
    file_put_contents($resultFile, 'full');
} catch (Throwable $throwable) {
    file_put_contents($resultFile, 'error:' . $throwable::class . ':' . $throwable->getMessage());
    exit(3);
}
