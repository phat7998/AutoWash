<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookingResources;
use InvalidArgumentException;

final class BookingResourceCalculator
{
    /** @param list<array<string, mixed>> $items */
    public function calculate(int $defaultCapacityUnits, array $items): BookingResources
    {
        if ($defaultCapacityUnits <= 0 || $items === []) {
            throw new InvalidArgumentException('Cấu hình tài nguyên booking không hợp lệ.');
        }

        $durationMinutes = 0;
        $capacityUnits = $defaultCapacityUnits;

        foreach ($items as $item) {
            $duration = (int) ($item['duration_minutes'] ?? 0);
            $capacity = (int) ($item['capacity_units'] ?? 0);

            if ($duration <= 0 || $capacity <= 0) {
                throw new InvalidArgumentException('Cấu hình dịch vụ cho booking không hợp lệ.');
            }

            $durationMinutes += $duration;
            $capacityUnits = max($capacityUnits, $capacity);
        }

        return new BookingResources($durationMinutes, $capacityUnits);
    }
}
