<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TierReviewPolicy;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class TierRulesTest extends TestCase
{
    public function testPreviousPeriodUsesPreviousCalendarMonthAcrossYearBoundary(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $policy = new TierReviewPolicy($timezone);

        self::assertSame(
            '2025-12',
            $policy->previousPeriod(new DateTimeImmutable('2026-01-01 00:00:00', $timezone))
        );
        self::assertSame(
            '2026-06',
            $policy->previousPeriod(new DateTimeImmutable('2026-07-31 23:59:59', $timezone))
        );
    }

    public function testQualificationRequiresSpendAndVisitsAtBoundary(): void
    {
        $policy = new TierReviewPolicy(new DateTimeZone('Asia/Ho_Chi_Minh'));
        $tiers = $this->tiers();

        self::assertSame('SILVER', $policy->qualifiedTier('300000.00', 2, $tiers)['code']);
        self::assertSame('MEMBER', $policy->qualifiedTier('299999.99', 2, $tiers)['code']);
        self::assertSame('MEMBER', $policy->qualifiedTier('300000.00', 1, $tiers)['code']);
    }

    public function testQualificationSelectsHighestTierMeetingBothThresholds(): void
    {
        $policy = new TierReviewPolicy(new DateTimeZone('Asia/Ho_Chi_Minh'));

        self::assertSame('PLATINUM', $policy->qualifiedTier('2000000.00', 10, $this->tiers())['code']);
        self::assertSame('GOLD', $policy->qualifiedTier('1499999.99', 8, $this->tiers())['code']);
    }

    /** @return list<array<string, int|string>> */
    private function tiers(): array
    {
        return [
            ['id' => 1, 'code' => 'MEMBER', 'name' => 'Thành viên', 'rank_order' => 1,
                'min_monthly_spend' => '0.00', 'min_monthly_visits' => 0],
            ['id' => 2, 'code' => 'SILVER', 'name' => 'Bạc', 'rank_order' => 2,
                'min_monthly_spend' => '300000.00', 'min_monthly_visits' => 2],
            ['id' => 3, 'code' => 'GOLD', 'name' => 'Vàng', 'rank_order' => 3,
                'min_monthly_spend' => '800000.00', 'min_monthly_visits' => 5],
            ['id' => 4, 'code' => 'PLATINUM', 'name' => 'Bạch kim', 'rank_order' => 4,
                'min_monthly_spend' => '1500000.00', 'min_monthly_visits' => 8],
        ];
    }
}
