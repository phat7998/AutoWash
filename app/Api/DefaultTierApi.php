<?php

declare(strict_types=1);

namespace App\Api;

final class DefaultTierApi
{
    /**
     * @param array<string, mixed> $user
     * @param list<array<string, mixed>> $tiers
     * @return array<string, mixed>
     */
    public function resolve(array $user, array $tiers): array
    {
        $selectedTier = null;

        foreach ($tiers as $tier) {
            $tierId = (int) ($tier['id'] ?? 0);
            $tierCode = (string) ($tier['code'] ?? '');
            $tierName = (string) ($tier['name'] ?? '');
            $bookingWindow = (int) ($tier['booking_window_days'] ?? 0);

            if ($tierId === 0 || $tierCode === '') {
                continue;
            }

            if ($selectedTier === null) {
                $selectedTier = $tier;
            }

            if ((int) ($user['current_tier_id'] ?? 0) === $tierId) {
                $selectedTier = $tier;
                break;
            }
        }

        if ($selectedTier === null) {
            $selectedTier = [
                'id' => 0,
                'code' => 'bronze',
                'name' => 'Bronze',
                'booking_window_days' => 7,
            ];
        }

        $defaultTier = [
            'id' => (int) ($selectedTier['id'] ?? 0),
            'code' => (string) ($selectedTier['code'] ?? 'bronze'),
            'name' => (string) ($selectedTier['name'] ?? 'Bronze'),
            'booking_window_days' => (int) ($selectedTier['booking_window_days'] ?? 7),
        ];

        return [
            'success' => true,
            'message' => 'Default tier has been resolved.',
            'data' => [
                'user_id' => (int) ($user['id'] ?? 0),
                'default_tier' => $defaultTier,
            ],
        ];
    }
}