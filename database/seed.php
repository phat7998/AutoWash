<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\DatabaseSeeder;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set($app['timezone']);

$arguments = array_slice($argv, 1);
$unknownArguments = array_diff($arguments, ['--demo']);

if ($unknownArguments !== []) {
    fwrite(STDERR, "Seed chỉ hỗ trợ cờ --demo.\n");
    exit(1);
}

try {
    $seeder = new DatabaseSeeder(Database::connection(), __DIR__ . '/seeds/base.php');
    $seeder->seed();
    echo "Đã seed cấu hình và dữ liệu demo nền tảng.\n";
} catch (Throwable $throwable) {
    fwrite(STDERR, sprintf("Seed thất bại: %s\n", $throwable->getMessage()));
    exit(1);
}
