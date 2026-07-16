<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InsufficientPointsException;
use App\Exceptions\ValidationException;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Validation\RewardValidator;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeZone;

final class RewardRulesTest extends TestCase
{
    public function testAllocatorConsumesOrderedCreditLotsWithoutSkipping(): void
    {
        $allocations = (new LoyaltyDebitAllocator())->allocate(120, [
            ['id' => 10, 'remaining_points' => 40],
            ['id' => 11, 'remaining_points' => 100],
            ['id' => 12, 'remaining_points' => 90],
        ]);

        self::assertSame([
            ['credit_transaction_id' => 10, 'allocated_points' => 40],
            ['credit_transaction_id' => 11, 'allocated_points' => 80],
        ], $allocations);
    }

    public function testAllocatorRejectsDebitLargerThanLockedCredits(): void
    {
        $this->expectException(InsufficientPointsException::class);
        (new LoyaltyDebitAllocator())->allocate(51, [['id' => 1, 'remaining_points' => 50]]);
    }

    public function testExpirationUsesTwelveCalendarMonthsWithLeapDayClamp(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $policy = new LoyaltyExpirationPolicy($timezone);

        self::assertSame(
            '2025-02-28 09:15:00',
            $policy->expiresAt(new DateTimeImmutable('2024-02-29 09:15:00', $timezone))
                ->format('Y-m-d H:i:s')
        );
        self::assertSame(
            '2026-01-31 23:59:00',
            $policy->expiresAt(new DateTimeImmutable('2025-01-31 23:59:00', $timezone))
                ->format('Y-m-d H:i:s')
        );
    }

    public function testRewardValidatorNormalizesValidConfiguration(): void
    {
        $data = (new RewardValidator())->validate(
            ' test_reward ',
            '  Reward kiểm thử  ',
            'fixed_discount',
            '100',
            '10000',
            '',
            '2',
            '30',
            ['4', '4'],
            [1, 2],
            [1, 2],
            [3, 4]
        );

        self::assertSame('TEST_REWARD', $data['code']);
        self::assertSame('10000.00', $data['value']);
        self::assertSame([4], $data['vehicle_type_ids']);
        self::assertSame(2, $data['minimum_tier_id']);
    }

    public function testRewardValidatorRejectsInvalidTrustBoundaryInput(): void
    {
        try {
            (new RewardValidator())->validate(
                'x',
                '',
                'percentage_discount',
                '0',
                '100.01',
                '999',
                '-1',
                '0',
                ['999'],
                [1],
                [1],
                [1]
            );
            self::fail('Cấu hình reward sai phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('code', $exception->errors());
            self::assertArrayHasKey('name', $exception->errors());
            self::assertArrayHasKey('points_cost', $exception->errors());
            self::assertArrayHasKey('value', $exception->errors());
            self::assertArrayHasKey('valid_days_after_redeem', $exception->errors());
            self::assertArrayHasKey('minimum_tier_id', $exception->errors());
            self::assertArrayHasKey('vehicle_type_ids', $exception->errors());
        }
    }
}
