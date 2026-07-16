<?php

declare(strict_types=1);

namespace App\Contracts;

interface BookingCompletionProcessorInterface
{
    /** @param array<string, mixed> $lockedBooking */
    public function process(array $lockedBooking): void;
}
