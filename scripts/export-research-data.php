<?php

declare(strict_types=1);

use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Repositories\ResearchEventRepository;
use App\Services\ResearchCsvExporter;
use App\Services\ResearchExportService;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$options = getopt('', ['output:', 'from::', 'to::', 'source::']);
$output = is_string($options['output'] ?? null) ? trim($options['output']) : '';

if ($output === '') {
    fwrite(STDERR, "Thiếu --output=/duong-dan/file.csv.\n");
    exit(1);
}

$path = str_starts_with($output, '/') ? $output : $projectRoot . '/' . ltrim($output, '/');
$directory = dirname($path);

if (!is_dir($directory) || !is_writable($directory)) {
    fwrite(STDERR, "Thư mục đầu ra không tồn tại hoặc không thể ghi.\n");
    exit(1);
}

$service = new ResearchExportService(
    new ResearchEventRepository(Database::connection()),
    new ResearchCsvExporter()
);

try {
    $count = $service->export(
        $path,
        is_string($options['from'] ?? null) ? $options['from'] : null,
        is_string($options['to'] ?? null) ? $options['to'] : null,
        is_string($options['source'] ?? null) ? $options['source'] : null
    );
    printf("Đã xuất %d record nghiên cứu ẩn danh vào %s.\n", $count, $path);
} catch (ValidationException $exception) {
    fwrite(STDERR, implode(' ', $exception->errors()) . PHP_EOL);
    exit(1);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Export nghiên cứu thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
