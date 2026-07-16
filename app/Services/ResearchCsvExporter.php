<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ResearchCsvExporter
{
    /** @var list<string> */
    public const COLUMNS = [
        'schema_version',
        'event_key',
        'anonymous_user_key',
        'event_type',
        'event_time',
        'tier_code',
        'tier_before_code',
        'tier_after_code',
        'vehicle_type_code',
        'service_code',
        'service_codes',
        'booking_lead_days',
        'order_value',
        'monthly_spend_snapshot',
        'monthly_visits_snapshot',
        'points_earned',
        'points_redeemed',
        'used_reward',
        'used_promotion',
        'cancellation_status',
        'return_frequency_days',
        'data_source',
    ];

    /** @param iterable<array<string, mixed>> $rows */
    public function write(string $path, iterable $rows): int
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Không thể mở file CSV đầu ra.');
        }

        try {
            if (fputcsv($handle, self::COLUMNS, ',', '"', '') === false) {
                throw new RuntimeException('Không thể ghi header CSV.');
            }

            $count = 0;
            foreach ($rows as $row) {
                $values = array_map(
                    static fn (string $column): string => self::csvValue($row[$column] ?? null),
                    self::COLUMNS
                );
                if (fputcsv($handle, $values, ',', '"', '') === false) {
                    throw new RuntimeException('Không thể ghi record CSV.');
                }
                $count++;
            }

            return $count;
        } finally {
            fclose($handle);
        }
    }

    private static function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
