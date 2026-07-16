<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BookingCompletionProcessorInterface;

final readonly class BookingCompletionService implements BookingCompletionProcessorInterface
{
    public function __construct(
        private PromotionService $promotions,
        private LoyaltyService $loyalty
    ) {
    }

    /** @param array<string, mixed> $lockedBooking */
    public function process(array $lockedBooking): void
    {
        $this->promotions->completeBookingBenefits($lockedBooking);
        $this->loyalty->process($lockedBooking);
    }
}
