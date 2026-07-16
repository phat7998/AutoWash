<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;
use DateTimeZone;

final readonly class WashSlotValidator
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    /** @return array<string, string> */
    public function validate(string $date, string $startTime, string $endTime, string $capacity): array
    {
        $errors = [];
        $slotDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->timezone);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if (
            $slotDate === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $slotDate->format('Y-m-d') !== $date
        ) {
            $errors['slot_date'] = 'Ngày khung giờ không hợp lệ.';
        } elseif ($slotDate < new DateTimeImmutable('today', $this->timezone)) {
            $errors['slot_date'] = 'Không thể tạo khung giờ cho ngày đã qua.';
        }

        $start = $this->normalizeTime($startTime);
        $end = $this->normalizeTime($endTime);

        if ($start === null) {
            $errors['start_time'] = 'Giờ bắt đầu không hợp lệ.';
        }

        if ($end === null) {
            $errors['end_time'] = 'Giờ kết thúc không hợp lệ.';
        } elseif ($start !== null && $end <= $start) {
            $errors['end_time'] = 'Giờ kết thúc phải sau giờ bắt đầu.';
        }

        if (preg_match('/^[1-9][0-9]*$/', $capacity) !== 1 || (int) $capacity > 10000) {
            $errors['capacity_units'] = 'Sức chứa phải là số nguyên từ 1 đến 10.000 units.';
        }

        return $errors;
    }

    public function normalizeTime(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) !== 1) {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }
}
