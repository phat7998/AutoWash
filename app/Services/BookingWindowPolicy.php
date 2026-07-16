<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\ValidationException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class BookingWindowPolicy
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    public function assertAllowed(
        string $slotDate,
        int $bookingWindowDays,
        ?DateTimeImmutable $currentTime = null
    ): int {
        $today = ($currentTime ?? new DateTimeImmutable('now', $this->timezone))
            ->setTimezone($this->timezone)
            ->setTime(0, 0);
        $bookingDate = DateTimeImmutable::createFromFormat('!Y-m-d', $slotDate, $this->timezone);

        if (!$bookingDate instanceof DateTimeImmutable || $bookingDate->format('Y-m-d') !== $slotDate) {
            throw new ValidationException(['start_slot_id' => 'Ngày của khung giờ không hợp lệ.']);
        }

        if ($bookingDate < $today) {
            throw new ValidationException(['start_slot_id' => 'Không thể đặt lịch cho ngày đã qua.']);
        }

        $leadDays = (int) $today->diff($bookingDate)->format('%a');

        if ($leadDays > $bookingWindowDays) {
            throw new BookingWindowExceededException($bookingWindowDays);
        }

        return $leadDays;
    }
}
