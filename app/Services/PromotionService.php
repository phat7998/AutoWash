<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\PromotionRepository;
use App\Validation\PromotionValidator;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\CatalogResourceNotFoundException;
use PDOException;

final readonly class PromotionService
{
    public function __construct(
        private PromotionRepository $promotions,
        private DateTimeZone $timezone,
        private ?PromotionValidator $validator = null
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function adminPromotions(): array
    {
        return $this->promotions->promotions();
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function formOptions(): array
    {
        return $this->promotions->formOptions();
    }

    /** @return array<string, mixed> */
    public function promotion(int $id): array
    {
        return $this->promotions->promotion($id) ?? throw new CatalogResourceNotFoundException();
    }

    public function savePromotion(?int $id, int $adminId, array $input): int
    {
        if ($id !== null) {
            $this->promotion($id);
        }
        if ($this->validator === null) {
            throw new RuntimeException('Promotion validator chưa được cấu hình.');
        }
        $options = $this->formOptions();
        $data = $this->validator->validate($input, [
            'tiers' => array_map('intval', array_column($options['tiers'], 'id')),
            'services' => array_map('intval', array_column($options['services'], 'id')),
            'vehicle_types' => array_map('intval', array_column($options['vehicle_types'], 'id')),
        ]);
        try {
            return $this->promotions->savePromotion($id, $data, $adminId);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DuplicateCatalogException('Mã promotion đã tồn tại.');
            }
            throw $exception;
        }
    }

    public function setPromotionActive(int $id, bool $active, int $adminId): void
    {
        if (!$this->promotions->setPromotionActive($id, $active, $adminId)) {
            throw new CatalogResourceNotFoundException();
        }
    }

    /** @return list<array<string, mixed>> */
    public function availableRedemptions(int $userId): array
    {
        return $this->promotions->availableRedemptions(
            $userId,
            (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param list<int> $serviceIds
     * @return array{
     *     perks: list<array<string, mixed>>,
     *     promotions: list<array<string, mixed>>,
     *     reward: array<string, mixed>|null
     * }
     */
    public function checkoutBenefits(
        int $userId,
        int $tierId,
        int $tierRank,
        int $vehicleTypeId,
        array $serviceIds,
        string $subtotal,
        ?int $redemptionId,
        ?DateTimeImmutable $at = null
    ): array {
        if (!$this->promotions->inTransaction()) {
            throw new RuntimeException('Quyền lợi checkout phải được tính trong transaction booking.');
        }

        $at = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);
        $perks = $this->promotions->lockTierPerks($tierId);
        $eligiblePromotions = [];

        foreach ($this->promotions->lockPromotionCandidates($at->format('Y-m-d H:i:s')) as $promotion) {
            if (
                !$this->promotionEligible(
                    $promotion,
                    $userId,
                    $tierId,
                    $vehicleTypeId,
                    $serviceIds,
                    $subtotal
                )
            ) {
                continue;
            }

            $eligiblePromotions[] = $promotion;
        }

        return [
            'perks' => $perks,
            'promotions' => $eligiblePromotions,
            'reward' => $this->eligibleReward(
                $userId,
                $tierRank,
                $vehicleTypeId,
                $serviceIds,
                $redemptionId,
                $at
            ),
        ];
    }

    public function attachReward(int $redemptionId, int $bookingId, DateTimeImmutable $at): void
    {
        if (
            !$this->promotions->attachReward(
                $redemptionId,
                $bookingId,
                $at->setTimezone($this->timezone)->format('Y-m-d H:i:s')
            )
        ) {
            throw new ValidationException([
                'reward_redemption_id' => 'Reward vừa được sử dụng hoặc đã hết hạn.',
            ]);
        }
    }

    /** @param array<string, mixed> $booking */
    public function completeBookingBenefits(array $booking): void
    {
        if (!$this->promotions->inTransaction()) {
            throw new RuntimeException('Hoàn tất quyền lợi phải chạy trong transaction booking.');
        }

        $this->promotions->completePromotionUsage($booking);
        $rewardCompleted = $this->promotions->completeRewardUsage((int) $booking['id']);
        if ((string) $booking['reward_discount'] !== '0.00' && !$rewardCompleted) {
            throw new RuntimeException('Reward đã chọn không thể chuyển sang trạng thái đã dùng.');
        }
    }

    public function releaseReward(int $bookingId, ?DateTimeImmutable $at = null): void
    {
        $at = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);
        $this->promotions->releaseReward($bookingId, $at->format('Y-m-d H:i:s'));
    }

    /** @param array<string, mixed> $promotion @param list<int> $serviceIds */
    private function promotionEligible(
        array $promotion,
        int $userId,
        int $tierId,
        int $vehicleTypeId,
        array $serviceIds,
        string $subtotal
    ): bool {
        if ($this->compareMoney($subtotal, (string) $promotion['minimum_order_value']) < 0) {
            return false;
        }

        if ($promotion['tier_ids'] !== [] && !in_array($tierId, $promotion['tier_ids'], true)) {
            return false;
        }

        if (
            $promotion['service_ids'] !== []
            && array_intersect($serviceIds, $promotion['service_ids']) === []
        ) {
            return false;
        }

        if (
            $promotion['vehicle_type_ids'] !== []
            && !in_array($vehicleTypeId, $promotion['vehicle_type_ids'], true)
        ) {
            return false;
        }

        $counts = $this->promotions->promotionReservationCounts((int) $promotion['id'], $userId);

        return ($promotion['usage_limit'] === null || $counts['total'] < (int) $promotion['usage_limit'])
            && ($promotion['per_user_limit'] === null
                || $counts['user'] < (int) $promotion['per_user_limit']);
    }

    /** @param list<int> $serviceIds @return array<string, mixed>|null */
    private function eligibleReward(
        int $userId,
        int $tierRank,
        int $vehicleTypeId,
        array $serviceIds,
        ?int $redemptionId,
        DateTimeImmutable $at
    ): ?array {
        if ($redemptionId === null) {
            return null;
        }

        $reward = $this->promotions->lockRedemption($redemptionId);

        if (
            $reward === null
            || (int) $reward['user_id'] !== $userId
            || (string) $reward['status'] !== 'available'
            || $reward['booking_id'] !== null
            || !(bool) $reward['is_active']
            || (string) $reward['expires_at'] <= $at->format('Y-m-d H:i:s')
        ) {
            throw new ValidationException([
                'reward_redemption_id' => 'Reward không thuộc tài khoản, đã dùng hoặc đã hết hạn.',
            ]);
        }

        if ($reward['minimum_tier_rank'] !== null && $tierRank < (int) $reward['minimum_tier_rank']) {
            throw new ValidationException([
                'reward_redemption_id' => 'Hạng hiện tại không còn đủ điều kiện dùng reward.',
            ]);
        }

        if (
            $reward['vehicle_type_ids'] !== []
            && !in_array($vehicleTypeId, $reward['vehicle_type_ids'], true)
        ) {
            throw new ValidationException([
                'reward_redemption_id' => 'Reward không áp dụng cho loại phương tiện đã chọn.',
            ]);
        }

        if ($reward['service_id'] !== null && !in_array((int) $reward['service_id'], $serviceIds, true)) {
            throw new ValidationException([
                'reward_redemption_id' => 'Reward không áp dụng cho các dịch vụ đã chọn.',
            ]);
        }

        return $reward;
    }

    private function compareMoney(string $left, string $right): int
    {
        return $this->moneyCents($left) <=> $this->moneyCents($right);
    }

    private function moneyCents(string $amount): int
    {
        if (preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches) !== 1) {
            throw new RuntimeException('Giá trị tiền từ cơ sở dữ liệu không hợp lệ.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}
