<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class TierReviewPolicy
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    public function previousPeriod(?DateTimeImmutable $at = null): string
    {
        $at = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);

        return $at->modify('first day of this month 00:00:00')
            ->modify('-1 month')
            ->format('Y-m');
    }

    /**
     * @param list<array<string, mixed>> $tiers
     * @return array<string, mixed>
     */
    public function qualifiedTier(string $monthlySpend, int $monthlyVisits, array $tiers): array
    {
        if ($monthlyVisits < 0 || $tiers === []) {
            throw new InvalidArgumentException('Dữ liệu xét hạng không hợp lệ.');
        }

        $spend = $this->minorUnits($monthlySpend);
        usort(
            $tiers,
            static fn (array $left, array $right): int =>
                (int) $right['rank_order'] <=> (int) $left['rank_order']
        );

        foreach ($tiers as $tier) {
            $minimumSpend = $this->minorUnits((string) ($tier['min_monthly_spend'] ?? ''));
            $minimumVisits = (int) ($tier['min_monthly_visits'] ?? -1);

            if ($minimumVisits < 0) {
                throw new InvalidArgumentException('Ngưỡng lượt xét hạng không hợp lệ.');
            }

            if ($spend >= $minimumSpend && $monthlyVisits >= $minimumVisits) {
                return $tier;
            }
        }

        throw new InvalidArgumentException('Không có hạng thành viên hoạt động phù hợp.');
    }

    private function minorUnits(string $amount): int
    {
        if (preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Số tiền xét hạng không hợp lệ.');
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }
}
