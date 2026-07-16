<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\LoyaltyPointCalculator;
use App\Validation\LoyaltyAdjustmentValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoyaltyRulesTest extends TestCase
{
    /** @return iterable<string, array{string, string, int}> */
    public static function earnedPointCases(): iterable
    {
        yield 'Member' => ['250000.00', '1.00', 25];
        yield 'Silver' => ['250000.00', '1.10', 27];
        yield 'Gold floor hai bước' => ['250000.00', '1.25', 31];
        yield 'Platinum' => ['250000.00', '1.50', 37];
        yield 'Giá bằng 0' => ['0.00', '1.50', 0];
        yield 'Chưa đủ một đơn vị' => ['9999.99', '1.50', 0];
    }

    #[DataProvider('earnedPointCases')]
    public function testEarnedPointsUsesIntegerDecimalMath(
        string $finalPrice,
        string $pointRate,
        int $expected
    ): void {
        $calculator = new LoyaltyPointCalculator(10_000);

        self::assertSame($expected, $calculator->earnedPoints($finalPrice, $pointRate));
    }

    public function testEarnedPointsRejectsInvalidMoneyInsteadOfUsingFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LoyaltyPointCalculator(10_000))->earnedPoints('250.000', '1.25');
    }

    public function testAdjustmentValidatorNormalizesValidInput(): void
    {
        $input = (new LoyaltyAdjustmentValidator())->validate(
            '12',
            '-50',
            '  Điều chỉnh sai lệch tại quầy.  ',
            '7'
        );

        self::assertSame([
            'user_id' => 12,
            'points' => -50,
            'reason' => 'Điều chỉnh sai lệch tại quầy.',
            'source_transaction_id' => 7,
        ], $input);
    }

    public function testAdjustmentValidatorRejectsZeroMissingReasonAndInvalidIds(): void
    {
        try {
            (new LoyaltyAdjustmentValidator())->validate('0', '0', '   ', '-1');
            self::fail('Input điều chỉnh sai phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('user_id', $exception->errors());
            self::assertArrayHasKey('points', $exception->errors());
            self::assertArrayHasKey('reason', $exception->errors());
            self::assertArrayHasKey('source_transaction_id', $exception->errors());
        }
    }
}
