<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BookingPrice
{
    public function __construct(
        public string $subtotal,
        public string $perkDiscount,
        public string $promotionDiscount,
        public string $rewardDiscount,
        public string $finalPrice
    ) {
    }
}
