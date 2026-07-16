<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final readonly class LoyaltyPointCalculator
{
    public function __construct(private int $pointUnitAmount)
    {
        if ($pointUnitAmount <= 0) {
            throw new InvalidArgumentException('Đơn vị quy đổi điểm phải lớn hơn 0.');
        }
    }

    public function earnedPoints(string $finalPrice, string $pointRate): int
    {
        if (preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $finalPrice) !== 1) {
            throw new InvalidArgumentException('Giá cuối cùng không hợp lệ.');
        }

        if (preg_match('/^[0-9]+(?:\.[0-9]+)?$/', $pointRate) !== 1) {
            throw new InvalidArgumentException('Hệ số tích điểm không hợp lệ.');
        }

        $basePoints = intdiv($this->minorUnits($finalPrice), $this->pointUnitAmount * 100);
        [$rateNumerator, $rateDenominator] = $this->decimalFraction($pointRate);

        return intdiv($basePoints * $rateNumerator, $rateDenominator);
    }

    private function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    /** @return array{int, int} */
    private function decimalFraction(string $value): array
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        if ($fraction === '') {
            return [(int) $whole, 1];
        }

        $denominator = 10 ** strlen($fraction);

        return [((int) $whole * $denominator) + (int) $fraction, $denominator];
    }
}
