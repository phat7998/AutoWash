<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookingPrice;
use InvalidArgumentException;

final class PriceCalculator
{
    /** @param list<array<string, mixed>> $items */
    public function calculate(array $items): BookingPrice
    {
        $subtotalCents = 0;

        foreach ($items as $item) {
            $subtotalCents += $this->toCents((string) ($item['price'] ?? ''));
        }

        $subtotal = $this->fromCents($subtotalCents);

        return new BookingPrice($subtotal, '0.00', '0.00', '0.00', $subtotal);
    }

    private function toCents(string $amount): int
    {
        if (preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Giá dịch vụ từ cơ sở dữ liệu không hợp lệ.');
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }

    private function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
