<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BookingResources
{
    public function __construct(
        public int $durationMinutes,
        public int $capacityUnits
    ) {
    }
}
