<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ResearchEventRepository;
use DateTimeImmutable;

final readonly class ResearchEventService
{
    public function __construct(private ResearchEventRepository $events)
    {
    }

    public function rewardRedeemed(
        int $userId,
        int $redemptionId,
        int $points,
        DateTimeImmutable $at
    ): void {
        $context = $this->events->userContext($userId);
        $this->events->insert($this->baseEvent(
            'reward_redeemed:' . $redemptionId,
            'reward_redeemed',
            $context,
            $at
        ) + [
            'points_redeemed' => $points,
            'used_reward' => true,
            'monthly_spend_snapshot' => $context['monthly_spend'],
            'monthly_visits_snapshot' => $context['monthly_visits'],
        ]);
    }

    public function pointsExpired(
        int $userId,
        int $debitId,
        int $creditLotId,
        int $points,
        DateTimeImmutable $at
    ): void {
        $context = $this->events->userContext($userId);
        $this->events->insert($this->baseEvent(
            'points_expired:' . $debitId,
            'points_expired',
            $context,
            $at
        ) + [
            'points_redeemed' => $points,
            'monthly_spend_snapshot' => $context['monthly_spend'],
            'monthly_visits_snapshot' => $context['monthly_visits'],
            'metadata' => ['credit_lot_reference' => $creditLotId],
        ]);
    }

    public function tierChanged(
        int $userId,
        int $historyId,
        string $beforeCode,
        string $afterCode,
        string $monthlySpend,
        int $monthlyVisits,
        string $reviewPeriod,
        DateTimeImmutable $at
    ): void {
        $context = $this->events->userContext($userId);
        $direction = match (true) {
            $beforeCode === $afterCode => 'hold',
            default => 'changed',
        };
        $this->events->insert($this->baseEvent(
            'tier_changed:' . $historyId,
            'tier_changed',
            $context,
            $at
        ) + [
            'tier_code' => $afterCode,
            'tier_before_code' => $beforeCode,
            'tier_after_code' => $afterCode,
            'monthly_spend_snapshot' => $monthlySpend,
            'monthly_visits_snapshot' => $monthlyVisits,
            'metadata' => ['review_period' => $reviewPeriod, 'transition' => $direction],
        ]);
    }

    /** @param array<string, mixed> $booking */
    public function promotionUsed(array $booking, DateTimeImmutable $at): void
    {
        if (($booking['promotion_id'] ?? null) === null) {
            return;
        }

        $bookingId = (int) $booking['id'];
        $bookingContext = $this->events->bookingContext($bookingId);
        $userContext = $this->events->userContext((int) $bookingContext['user_id']);
        $services = array_values(array_filter(explode(',', (string) $bookingContext['service_codes'])));
        $this->events->insert($this->baseEvent(
            'promotion_used:' . $bookingId,
            'promotion_used',
            $userContext,
            $at
        ) + [
            'vehicle_type_code' => $bookingContext['vehicle_type_code'],
            'service_code' => count($services) === 1 ? $services[0] : null,
            'booking_lead_days' => $bookingContext['booking_lead_days'],
            'order_value' => $bookingContext['order_value'],
            'used_promotion' => true,
            'metadata' => ['service_codes' => $services],
        ]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function baseEvent(
        string $eventKey,
        string $eventType,
        array $context,
        DateTimeImmutable $at
    ): array {
        return [
            'event_key' => $eventKey,
            'anonymous_user_key' => $context['anonymous_user_key'],
            'event_type' => $eventType,
            'event_time' => $at->format('Y-m-d H:i:s'),
            'tier_code' => $context['tier_code'],
        ];
    }
}
