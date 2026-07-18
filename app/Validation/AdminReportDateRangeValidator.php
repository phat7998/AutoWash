<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class AdminReportDateRangeValidator
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    /** @return array{from_date: string, to_date: string, from_at: string, to_exclusive: string} */
    public function validate(mixed $fromDate, mixed $toDate): array
    {
        $today = new DateTimeImmutable('today', $this->timezone);

        if (($fromDate === null || $fromDate === '') && ($toDate === null || $toDate === '')) {
            $fromDate = $today->modify('-29 days')->format('Y-m-d');
            $toDate = $today->format('Y-m-d');
        }

        $errors = [];
        $from = $this->date($fromDate);
        $to = $this->date($toDate);

        if ($from === null) {
            $errors['from_date'] = 'Từ ngày phải đúng định dạng YYYY-MM-DD.';
        }

        if ($to === null) {
            $errors['to_date'] = 'Đến ngày phải đúng định dạng YYYY-MM-DD.';
        }

        if ($from !== null && $to !== null && $from > $to) {
            $errors['date_range'] = 'Từ ngày không được lớn hơn đến ngày.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'from_date' => $from->format('Y-m-d'),
            'to_date' => $to->format('Y-m-d'),
            'from_at' => $from->format('Y-m-d 00:00:00'),
            'to_exclusive' => $to->modify('+1 day')->format('Y-m-d 00:00:00'),
        ];
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->timezone);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
