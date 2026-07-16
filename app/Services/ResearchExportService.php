<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\ResearchEventRepository;
use DateTimeImmutable;

final readonly class ResearchExportService
{
    public function __construct(
        private ResearchEventRepository $events,
        private ResearchCsvExporter $exporter
    ) {
    }

    public function export(
        string $path,
        ?string $from = null,
        ?string $to = null,
        ?string $source = null
    ): int {
        $from = $this->date($from, 'from');
        $to = $this->date($to, 'to');

        if ($from !== null && $to !== null && $from > $to) {
            throw new ValidationException([
                'date_range' => 'Ngày bắt đầu không được sau ngày kết thúc.',
            ]);
        }
        if ($source !== null && !in_array($source, ['system', 'synthetic', 'survey'], true)) {
            throw new ValidationException(['source' => 'Nguồn dữ liệu không hợp lệ.']);
        }

        return $this->exporter->write($path, $this->events->exportRows($from, $to, $source));
    }

    private function date(?string $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new ValidationException([$field => 'Ngày phải có định dạng YYYY-MM-DD.']);
        }

        return $date->format('Y-m-d');
    }
}
