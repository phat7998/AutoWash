<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ResearchReportRepository;

final readonly class DashboardService
{
    public function __construct(
        private ResearchReportRepository $reports,
        private LoyaltyService $loyalty
    ) {
    }

    /** @return array<string, mixed> */
    public function customer(int $userId): array
    {
        return $this->loyalty->dashboard($userId) + $this->reports->customerMetrics($userId);
    }

    /** @return array<string, mixed> */
    public function admin(): array
    {
        $metrics = $this->reports->adminMetrics();
        $metrics['booking_status'] = $this->withPercentages($metrics['booking_status'], 'total');
        $metrics['tiers'] = $this->withPercentages($metrics['tiers'], 'total');
        $totalCapacity = (int) ($metrics['slots']['total_capacity'] ?? 0);
        $reservedCapacity = (int) ($metrics['slots']['reserved_capacity'] ?? 0);
        $metrics['slots']['utilization_percent'] = $totalCapacity === 0
            ? 0
            : min(100, (int) round($reservedCapacity * 100 / $totalCapacity));

        return $metrics;
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
    private function withPercentages(array $rows, string $field): array
    {
        $total = array_sum(array_map(static fn (array $row): int => (int) $row[$field], $rows));

        return array_map(static function (array $row) use ($field, $total): array {
            $row['percent'] = $total === 0 ? 0 : (int) round((int) $row[$field] * 100 / $total);

            return $row;
        }, $rows);
    }
}
