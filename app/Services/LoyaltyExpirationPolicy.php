<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final readonly class LoyaltyExpirationPolicy
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    public function expiresAt(DateTimeImmutable $earnedAt): DateTimeImmutable
    {
        $earnedAt = $earnedAt->setTimezone($this->timezone);
        $targetYear = (int) $earnedAt->format('Y') + 1;
        $targetMonth = (int) $earnedAt->format('n');
        $lastDay = (int) (new DateTimeImmutable(
            sprintf('%04d-%02d-01', $targetYear, $targetMonth),
            $this->timezone
        ))->format('t');
        $targetDay = min((int) $earnedAt->format('j'), $lastDay);

        return $earnedAt->setDate($targetYear, $targetMonth, $targetDay);
    }
}
