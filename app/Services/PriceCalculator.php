<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookingPrice;
use InvalidArgumentException;

final class PriceCalculator
{
    /**
     * @param list<array<string, mixed>> $items
     * @param list<array<string, mixed>> $perks
     * @param list<array<string, mixed>> $promotions
     * @param array<string, mixed>|null $reward
     */
    public function calculate(
        array $items,
        array $perks = [],
        array $promotions = [],
        ?array $reward = null
    ): BookingPrice {
        $subtotalCents = 0;

        foreach ($items as $item) {
            $subtotalCents += $this->toCents((string) ($item['price'] ?? ''));
        }

        [$perk, $perkCents] = $this->bestPerk($perks, $items, $subtotalCents);
        $perkCents = min($perkCents, $subtotalCents);
        [$promotion, $promotionCents] = $this->bestPromotion(
            $promotions,
            $items,
            $subtotalCents
        );
        $promotionCents = min($promotionCents, $subtotalCents - $perkCents);
        $rewardCents = $reward === null
            ? 0
            : $this->benefitDiscount($reward, $items, $subtotalCents, 'reward_type');
        $rewardCents = min($rewardCents, $subtotalCents - $perkCents - $promotionCents);
        $finalCents = $subtotalCents - $perkCents - $promotionCents - $rewardCents;

        return new BookingPrice(
            $this->fromCents($subtotalCents),
            $this->fromCents($perkCents),
            $this->fromCents($promotionCents),
            $this->fromCents($rewardCents),
            $this->fromCents($finalCents),
            $perkCents > 0 && isset($perk['id']) ? (int) $perk['id'] : null,
            $promotionCents > 0 && isset($promotion['id']) ? (int) $promotion['id'] : null,
            $rewardCents > 0 && isset($reward['redemption_id'])
                ? (int) $reward['redemption_id']
                : null
        );
    }

    /** @param list<array<string, mixed>> $perks @param list<array<string, mixed>> $items */
    private function bestPerk(array $perks, array $items, int $subtotalCents): array
    {
        $best = null;
        $bestDiscount = 0;

        foreach ($perks as $perk) {
            $discount = $this->benefitDiscount($perk, $items, $subtotalCents, 'perk_type');

            if (
                $discount > $bestDiscount
                || ($discount === $bestDiscount && $discount > 0 && (int) $perk['id'] < (int) $best['id'])
            ) {
                $best = $perk;
                $bestDiscount = $discount;
            }
        }

        return [$best, $bestDiscount];
    }

    /** @param list<array<string, mixed>> $promotions @param list<array<string, mixed>> $items */
    private function bestPromotion(array $promotions, array $items, int $subtotalCents): array
    {
        $best = null;
        $bestDiscount = 0;

        foreach ($promotions as $promotion) {
            $discount = $this->benefitDiscount(
                $promotion,
                $items,
                $subtotalCents,
                'discount_type'
            );
            $winsTie = $discount === $bestDiscount
                && $discount > 0
                && ($best === null
                    || (string) $promotion['end_at'] < (string) $best['end_at']
                    || ((string) $promotion['end_at'] === (string) $best['end_at']
                        && (int) $promotion['id'] < (int) $best['id']));

            if ($discount > $bestDiscount || $winsTie) {
                $best = $promotion;
                $bestDiscount = $discount;
            }
        }

        return [$best, $bestDiscount];
    }

    /** @param array<string, mixed> $benefit @param list<array<string, mixed>> $items */
    private function benefitDiscount(
        array $benefit,
        array $items,
        int $subtotalCents,
        string $typeKey
    ): int {
        $serviceId = isset($benefit['service_id']) && $benefit['service_id'] !== null
            ? (int) $benefit['service_id']
            : null;
        $baseCents = $serviceId === null
            ? $subtotalCents
            : $this->serviceSubtotal($items, $serviceId);

        if ($baseCents === 0) {
            return 0;
        }

        $type = (string) ($benefit[$typeKey] ?? '');
        $value = (string) ($benefit['discount_value'] ?? $benefit['value'] ?? '0');
        $discount = match ($type) {
            'fixed', 'fixed_discount' => min($this->toCents($value), $baseCents),
            'percentage', 'percentage_discount' => intdiv(
                $baseCents * $this->percentageBasisPoints($value),
                10_000
            ),
            'free_service', 'free_add_on', 'add_on' => $serviceId === null ? 0 : $baseCents,
            default => 0,
        };
        $maxDiscount = $benefit['max_discount'] ?? null;

        if ($maxDiscount !== null && (string) $maxDiscount !== '') {
            $discount = min($discount, $this->toCents((string) $maxDiscount));
        }

        return min($discount, $baseCents);
    }

    /** @param list<array<string, mixed>> $items */
    private function serviceSubtotal(array $items, int $serviceId): int
    {
        $subtotal = 0;

        foreach ($items as $item) {
            if ((int) ($item['service_id'] ?? 0) === $serviceId) {
                $subtotal += $this->toCents((string) ($item['price'] ?? ''));
            }
        }

        return $subtotal;
    }

    private function percentageBasisPoints(string $value): int
    {
        $cents = $this->toCents($value);

        if ($cents > 10_000) {
            throw new InvalidArgumentException('Tỷ lệ giảm từ cơ sở dữ liệu không hợp lệ.');
        }

        return $cents;
    }

    private function toCents(string $amount): int
    {
        if (preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Giá dịch vụ từ cơ sở dữ liệu không hợp lệ.');
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }

    private function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
