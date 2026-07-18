<?php

declare(strict_types=1);

use App\Core\Database;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set($app['timezone']);

$password = $_ENV['DEFENSE_DEMO_PASSWORD'] ?? '';

if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "DEFENSE_DEMO_PASSWORD phải có từ 8 đến 72 ký tự trong .env local.\n");
    exit(1);
}

try {
    $seeder = require __DIR__ . '/seeds/defense_demo.php';

    if (!is_object($seeder) || !method_exists($seeder, 'seed')) {
        throw new RuntimeException('Seeder defense không hợp lệ.');
    }

    /** @var array<string, int|string> $result */
    $result = $seeder->seed(Database::connection(), $password);
    echo "Đã seed dữ liệu defense vào database runtime hiện tại.\n";

    foreach ($result as $label => $value) {
        echo sprintf("%s: %s\n", $label, (string) $value);
    }
} catch (Throwable $throwable) {
    fwrite(STDERR, sprintf("Seed defense thất bại: %s\n", $throwable->getMessage()));
    exit(1);
}
