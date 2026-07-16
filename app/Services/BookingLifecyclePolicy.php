<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CancellationCutoffException;
use App\Exceptions\InvalidBookingTransitionException;
use DateTimeImmutable;

final class BookingLifecyclePolicy
{
    private const CUSTOMER_CANCELLATION_SECONDS = 2 * 60 * 60;

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled', 'no_show'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    public function assertTransition(string $currentStatus, string $targetStatus): void
    {
        if (!in_array($targetStatus, self::TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new InvalidBookingTransitionException($currentStatus, $targetStatus);
        }
    }

    public function assertCustomerCancellation(
        DateTimeImmutable $bookingStart,
        DateTimeImmutable $now
    ): void {
        if (!$this->customerCanCancel($bookingStart, $now)) {
            throw new CancellationCutoffException();
        }
    }

    public function customerCanCancel(DateTimeImmutable $bookingStart, DateTimeImmutable $now): bool
    {
        return $bookingStart->getTimestamp() - $now->getTimestamp()
            >= self::CUSTOMER_CANCELLATION_SECONDS;
    }

    /** @return list<string> */
    public function allowedTargets(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }
}
