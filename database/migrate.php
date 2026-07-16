<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\MigrationRunner;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set($app['timezone']);

try {
    $runner = new MigrationRunner(Database::connection(), __DIR__ . '/migrations');
    $migrated = $runner->migrate();

    if ($migrated === []) {
        echo "Database đã ở phiên bản mới nhất.\n";
        exit(0);
    }

    foreach ($migrated as $migration) {
        echo sprintf("Đã chạy migration: %s\n", $migration);
    }
} catch (Throwable $throwable) {
    fwrite(STDERR, sprintf("Migration thất bại: %s\n", $throwable->getMessage()));
    exit(1);
}
