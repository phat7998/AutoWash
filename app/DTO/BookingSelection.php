<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BookingSelection
{
    /** @param list<int> $serviceIds */
    public function __construct(
        public int $vehicleId,
        public int $startSlotId,
        public array $serviceIds
    ) {
    }
}
