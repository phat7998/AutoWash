<?php

declare(strict_types=1);

namespace App\Api;

final class BookingLeadApi
{
    /**
     * @param array<string, mixed> $user
     * @param list<array<string, mixed>> $tiers
     * @return array<string, mixed>
     */
    public function resolve(array $user, array $tiers): array
    {
        $defaultTierApi = new DefaultTierApi();
        $defaultResult = $defaultTierApi->resolve($user, $tiers);
        $defaultTier = $defaultResult['data']['default_tier'] ?? [];

        $leadDays = (int) ($defaultTier['booking_window_days'] ?? 7);

        return [
            'success' => true,
            'message' => 'Booking lead days have been calculated from current tier.',
            'data' => [
                'user_id' => (int) ($user['id'] ?? 0),
                'default_tier' => $defaultTier,
                'booking_lead_days' => $leadDays,
            ],
        ];
    }
}