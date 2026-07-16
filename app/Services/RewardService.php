<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\RewardNotEligibleException;
use App\Exceptions\RewardNotFoundException;
use App\Repositories\RewardRepository;
use App\Validation\RewardValidator;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;

final readonly class RewardService
{
    public function __construct(
        private RewardRepository $rewards,
        private LoyaltyService $loyalty,
        private RewardValidator $validator,
        private DateTimeZone $timezone
    ) {
    }

    /** @return array<string, mixed> */
    public function customerRewards(int $userId): array
    {
        $customer = $this->rewards->customerContext($userId);

        if ($customer === null || $customer['role'] !== 'customer' || $customer['status'] !== 'active') {
            throw new RewardNotEligibleException('Tài khoản khách hàng không hợp lệ.');
        }

        $items = [];

        foreach ($this->rewards->rewards() as $reward) {
            if (!(bool) $reward['is_active']) {
                continue;
            }

            $minimumRank = $reward['minimum_tier_rank'] === null
                ? 0
                : (int) $reward['minimum_tier_rank'];
            $reward['tier_eligible'] = (int) $customer['tier_rank'] >= $minimumRank;
            $reward['affordable'] = (int) $customer['point_balance'] >= (int) $reward['points_cost'];
            $items[] = $reward;
        }

        $at = new DateTimeImmutable('now', $this->timezone);

        return [
            'customer' => $customer,
            'rewards' => $items,
            'redemptions' => $this->rewards->customerRedemptions(
                $userId,
                $at->format('Y-m-d H:i:s')
            ),
        ];
    }

    public function redeem(int $userId, int $rewardId, ?DateTimeImmutable $at = null): int
    {
        $at = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);

        return $this->rewards->transactional(function () use ($userId, $rewardId, $at): int {
            $customer = $this->loyalty->customerForMutationInCurrentTransaction($userId);
            $reward = $this->rewards->reward($rewardId, true);

            if ($reward === null || !(bool) $reward['is_active']) {
                throw new RewardNotEligibleException('Reward không tồn tại hoặc đã ngừng hoạt động.');
            }

            $minimumRank = $reward['minimum_tier_rank'] === null ? 0 : (int) $reward['minimum_tier_rank'];

            if ((int) $customer['tier_rank'] < $minimumRank) {
                throw new RewardNotEligibleException(
                    'Hạng thành viên hiện tại chưa đủ điều kiện đổi reward.'
                );
            }

            $points = (int) $reward['points_cost'];

            if ((int) $customer['point_balance'] < $points) {
                throw new InsufficientPointsException();
            }

            $redeemedAt = $at->format('Y-m-d H:i:s');
            $expiresAt = $at->modify('+' . (int) $reward['valid_days_after_redeem'] . ' days')
                ->format('Y-m-d H:i:s');
            $redemptionId = $this->rewards->insertRedemption(
                $userId,
                $rewardId,
                $points,
                $redeemedAt,
                $expiresAt
            );
            $this->loyalty->debitInCurrentTransaction(
                $userId,
                'redeem',
                $points,
                'reward_redemption',
                $redemptionId,
                'Đổi reward “' . $reward['name'] . '”.',
                null,
                null,
                $at
            );

            return $redemptionId;
        });
    }

    /** @return list<array<string, mixed>> */
    public function adminRewards(): array
    {
        return $this->rewards->rewards();
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function formOptions(): array
    {
        return [
            'services' => $this->rewards->services(),
            'tiers' => $this->rewards->tiers(),
            'vehicle_types' => $this->rewards->vehicleTypes(),
        ];
    }

    /** @return array<string, mixed> */
    public function reward(int $rewardId): array
    {
        return $this->requiredReward($rewardId);
    }

    /** @param list<string> $vehicleTypeIds */
    public function create(
        string $code,
        string $name,
        string $rewardType,
        string $pointsCost,
        string $value,
        string $serviceId,
        string $minimumTierId,
        string $validDays,
        array $vehicleTypeIds,
        string $maxDiscount = ''
    ): int {
        $data = $this->validatedData(
            $code,
            $name,
            $rewardType,
            $pointsCost,
            $value,
            $serviceId,
            $minimumTierId,
            $validDays,
            $vehicleTypeIds,
            $maxDiscount
        );

        try {
            return $this->rewards->create($data);
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }
    }

    /** @param list<string> $vehicleTypeIds */
    public function update(
        int $rewardId,
        string $code,
        string $name,
        string $rewardType,
        string $pointsCost,
        string $value,
        string $serviceId,
        string $minimumTierId,
        string $validDays,
        array $vehicleTypeIds,
        string $maxDiscount = ''
    ): void {
        $this->requiredReward($rewardId);
        $data = $this->validatedData(
            $code,
            $name,
            $rewardType,
            $pointsCost,
            $value,
            $serviceId,
            $minimumTierId,
            $validDays,
            $vehicleTypeIds,
            $maxDiscount
        );

        try {
            $updated = $this->rewards->update($rewardId, $data);
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }

        if (!$updated) {
            throw new RewardNotFoundException();
        }
    }

    public function setActive(int $rewardId, bool $active): void
    {
        $reward = $this->requiredReward($rewardId);

        if ((bool) $reward['is_active'] === $active) {
            return;
        }

        if (!$this->rewards->setActive($rewardId, $active)) {
            throw new RewardNotFoundException();
        }
    }

    /** @param list<string> $vehicleTypeIds @return array<string, mixed> */
    private function validatedData(
        string $code,
        string $name,
        string $rewardType,
        string $pointsCost,
        string $value,
        string $serviceId,
        string $minimumTierId,
        string $validDays,
        array $vehicleTypeIds,
        string $maxDiscount = ''
    ): array {
        $options = $this->formOptions();

        return $this->validator->validate(
            $code,
            $name,
            $rewardType,
            $pointsCost,
            $value,
            $serviceId,
            $minimumTierId,
            $validDays,
            $vehicleTypeIds,
            array_map('intval', array_column($options['services'], 'id')),
            array_map('intval', array_column($options['tiers'], 'id')),
            array_map('intval', array_column($options['vehicle_types'], 'id')),
            $maxDiscount
        );
    }

    /** @return array<string, mixed> */
    private function requiredReward(int $rewardId): array
    {
        $reward = $this->rewards->reward($rewardId);

        if ($reward === null) {
            throw new RewardNotFoundException();
        }

        return $reward;
    }

    private function throwDuplicateOrOriginal(PDOException $exception): never
    {
        if ($exception->getCode() === '23000') {
            throw new DuplicateCatalogException('Mã reward đã tồn tại.');
        }

        throw $exception;
    }
}
