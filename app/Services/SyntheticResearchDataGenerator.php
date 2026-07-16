<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

final class SyntheticResearchDataGenerator
{
    /** @return Generator<int, array<string, mixed>> */
    public function generate(int $count, int $seed): Generator
    {
        $vehicleTypes = ['motorbike', 'car', 'truck', 'bus'];
        $services = ['STANDARD_WASH', 'PREMIUM_WASH', 'INTERIOR_CARE', 'FULL_DETAILING'];
        $tiers = ['MEMBER', 'SILVER', 'GOLD', 'PLATINUM'];

        for ($index = 0; $index < $count; $index++) {
            $numbers = $this->numbers($seed, $index);
            $vehicle = $vehicleTypes[$index % count($vehicleTypes)];
            $visits = 1 + ($numbers[0] % 12);
            $tierIndex = min(3, intdiv($visits, 3));
            $orderValue = (50000 + ($numbers[1] % 46) * 10000);
            $leadDays = $numbers[2] % 15;
            $cancelled = $numbers[3] % 10 === 0;
            $noShow = !$cancelled && $numbers[3] % 19 === 0;
            $eventTime = (new \DateTimeImmutable('2025-01-01 08:00:00'))
                ->modify('+' . ($numbers[4] % 540) . ' days')
                ->modify('+' . ($numbers[5] % 720) . ' minutes');
            $previousTier = $tiers[max(0, $tierIndex - (($numbers[6] % 5 === 0) ? 1 : 0))];
            $pointsEarned = $cancelled || $noShow ? 0 : intdiv($orderValue, 10000);

            yield [
                'schema_version' => '1.0',
                'event_key' => sprintf('synthetic:%d:%d', $seed, $index + 1),
                'anonymous_user_key' => hash('sha256', sprintf('synthetic-user:%d:%d', $seed, $index % 320)),
                'event_type' => $cancelled || $noShow ? 'booking_created' : 'booking_completed',
                'event_time' => $eventTime->format('Y-m-d H:i:s'),
                'tier_code' => $tiers[$tierIndex],
                'tier_before_code' => $previousTier,
                'tier_after_code' => $tiers[$tierIndex],
                'vehicle_type_code' => $vehicle,
                'service_code' => $services[$numbers[7] % count($services)],
                'service_codes' => $services[$numbers[7] % count($services)],
                'booking_lead_days' => $leadDays,
                'order_value' => sprintf('%d.00', $orderValue),
                'monthly_spend_snapshot' => sprintf('%d.00', $orderValue * $visits),
                'monthly_visits_snapshot' => $visits,
                'points_earned' => $pointsEarned,
                'points_redeemed' => $numbers[0] % 7 === 0 ? 100 : 0,
                'used_reward' => $numbers[1] % 7 === 0,
                'used_promotion' => $numbers[2] % 5 === 0,
                'cancellation_status' => $cancelled ? 'cancelled' : ($noShow ? 'no_show' : ''),
                'return_frequency_days' => 3 + ($numbers[3] % 58),
                'data_source' => 'synthetic',
            ];
        }
    }

    /** @return list<int> */
    private function numbers(int $seed, int $index): array
    {
        $bytes = hash('sha256', $seed . ':' . $index, true);
        $values = unpack('N8', $bytes);

        return array_values(array_map('intval', $values === false ? [] : $values));
    }
}
