<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\DatabaseResetter;
use App\Database\DatabaseSeeder;
use App\Database\MigrationRunner;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set($app['timezone']);

$forced = in_array('--force', $argv, true);
$seed = in_array('--seed', $argv, true);
$arguments = array_slice($argv, 1);
$unknownArguments = array_diff($arguments, ['--force', '--seed']);

if ($unknownArguments !== []) {
    fwrite(STDERR, "Reset chỉ hỗ trợ các cờ --force và --seed.\n");
    exit(1);
}

try {
    $database = Database::connection();
    (new DatabaseResetter($database))->reset($app['environment'], $forced);
    (new MigrationRunner($database, __DIR__ . '/migrations'))->migrate();

    if ($seed) {
        (new DatabaseSeeder($database, __DIR__ . '/seeds/base.php'))->seed();
    }

    echo $seed
        ? "Đã reset, migrate và seed database.\n"
        : "Đã reset và migrate database.\n";
} catch (Throwable $throwable) {
    fwrite(STDERR, sprintf("Reset thất bại: %s\n", $throwable->getMessage()));
    exit(1);
}
