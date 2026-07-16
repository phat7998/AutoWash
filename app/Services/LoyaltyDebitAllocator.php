<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InsufficientPointsException;

final readonly class LoyaltyDebitAllocator
{
    /**
     * @param list<array{id: int, remaining_points: int}> $creditLots
     * @return list<array{credit_transaction_id: int, allocated_points: int}>
     */
    public function allocate(int $points, array $creditLots): array
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Số điểm debit phải lớn hơn 0.');
        }

        $remaining = $points;
        $allocations = [];

        foreach ($creditLots as $lot) {
            if ($remaining === 0) {
                break;
            }

            $available = max(0, $lot['remaining_points']);

            if ($available === 0) {
                continue;
            }

            $allocated = min($remaining, $available);
            $allocations[] = [
                'credit_transaction_id' => $lot['id'],
                'allocated_points' => $allocated,
            ];
            $remaining -= $allocated;
        }

        if ($remaining !== 0) {
            throw new InsufficientPointsException();
        }

        return $allocations;
    }
}
