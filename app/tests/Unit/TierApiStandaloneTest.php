<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Api\DefaultTierApi;
use App\Api\BookingLeadApi;

final class TierApiStandaloneTest extends TestCase
{
    public function testResolveDefaultTierReturnsBronzeWhenNoMatchingTier(): void
    {
        $user = ['id' => 1, 'current_tier_id' => 999];
        $tiers = [
            ['id' => 1, 'code' => 'bronze', 'name' => 'Bronze', 'booking_window_days' => 7],
            ['id' => 2, 'code' => 'silver', 'name' => 'Silver', 'booking_window_days' => 14],
        ];

        $api = new DefaultTierApi();
        $result = $api->resolve($user, $tiers);

        $this->assertTrue($result['success']);
        $this->assertSame('Default tier has been resolved.', $result['message']);
        $this->assertSame(1, $result['data']['user_id']);
        $this->assertSame('bronze', $result['data']['default_tier']['code']);
        $this->assertSame(7, $result['data']['default_tier']['booking_window_days']);
    }

    public function testResolveDefaultTierReturnsCurrentTierWhenMatchingTierFound(): void
    {
        $user = ['id' => 2, 'current_tier_id' => 2];
        $tiers = [
            ['id' => 1, 'code' => 'bronze', 'name' => 'Bronze', 'booking_window_days' => 7],
            ['id' => 2, 'code' => 'silver', 'name' => 'Silver', 'booking_window_days' => 14],
        ];

        $api = new DefaultTierApi();
        $result = $api->resolve($user, $tiers);

        $this->assertSame('silver', $result['data']['default_tier']['code']);
        $this->assertSame(14, $result['data']['default_tier']['booking_window_days']);
    }

    public function testBookingLeadResolveUsesDefaultTierBookingWindow(): void
    {
        $user = ['id' => 3, 'current_tier_id' => 2];
        $tiers = [
            ['id' => 1, 'code' => 'bronze', 'name' => 'Bronze', 'booking_window_days' => 7],
            ['id' => 2, 'code' => 'silver', 'name' => 'Silver', 'booking_window_days' => 14],
        ];

        $api = new BookingLeadApi();
        $result = $api->resolve($user, $tiers);

        $this->assertSame(true, $result['success']);
        $this->assertSame(14, $result['data']['booking_lead_days']);
        $this->assertSame('silver', $result['data']['default_tier']['code']);
    }
}
