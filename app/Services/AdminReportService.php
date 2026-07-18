<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ResearchReportRepository;
use App\Validation\AdminReportDateRangeValidator;

final readonly class AdminReportService
{
    public function __construct(
        private ResearchReportRepository $reports,
        private AdminReportDateRangeValidator $dateRanges
    ) {
    }

    /** @return array<string, mixed> */
    public function report(mixed $fromDate, mixed $toDate): array
    {
        $range = $this->dateRanges->validate($fromDate, $toDate);
        $metrics = $this->reports->adminReportMetrics($range['from_at'], $range['to_exclusive']);

        foreach (['booking_status', 'vehicle_types', 'services', 'tiers'] as $key) {
            $metrics[$key] = $this->withPercentages($metrics[$key] ?? []);
        }

        return $metrics + [
            'from_date' => $range['from_date'],
            'to_date' => $range['to_date'],
        ];
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
    private function withPercentages(array $rows): array
    {
        $total = array_sum(array_map(static fn (array $row): int => (int) $row['total'], $rows));

        return array_map(static function (array $row) use ($total): array {
            $row['percent'] = $total === 0 ? 0 : (int) round((int) $row['total'] * 100 / $total);

            return $row;
        }, $rows);
    }
}
