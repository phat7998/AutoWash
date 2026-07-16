<?php

declare(strict_types=1);

use App\Services\ResearchCsvExporter;
use App\Services\SyntheticResearchDataGenerator;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$options = getopt('', ['output:', 'count::', 'seed::']);
$output = is_string($options['output'] ?? null) ? trim($options['output']) : '';
$count = filter_var($options['count'] ?? 2000, FILTER_VALIDATE_INT);
$seed = filter_var($options['seed'] ?? 20260717, FILTER_VALIDATE_INT);

if ($output === '') {
    fwrite(STDERR, "Thiếu --output=/duong-dan/file.csv.\n");
    exit(1);
}
if ($count === false || $count < 2000) {
    fwrite(STDERR, "Synthetic acceptance yêu cầu --count tối thiểu 2000.\n");
    exit(1);
}
if ($seed === false || $seed < 0) {
    fwrite(STDERR, "--seed phải là số nguyên không âm.\n");
    exit(1);
}

$path = str_starts_with($output, '/') ? $output : $projectRoot . '/' . ltrim($output, '/');
$directory = dirname($path);

if (!is_dir($directory) || !is_writable($directory)) {
    fwrite(STDERR, "Thư mục đầu ra không tồn tại hoặc không thể ghi.\n");
    exit(1);
}

try {
    $written = (new ResearchCsvExporter())->write(
        $path,
        (new SyntheticResearchDataGenerator())->generate($count, $seed)
    );
    printf(
        "Đã sinh %d record synthetic (seed %d) vào %s; data_source=synthetic.\n",
        $written,
        $seed,
        $path
    );
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Sinh synthetic dataset thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
