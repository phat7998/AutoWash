<?php

declare(strict_types=1);

namespace App\Validation;

use App\DTO\BookingSelection;
use App\Exceptions\ValidationException;

final class BookingValidator
{
    /**
     * @param mixed $serviceIds
     */
    public function selection(
        string $vehicleId,
        string $startSlotId,
        mixed $serviceIds,
        string $rewardRedemptionId = ''
    ): BookingSelection {
        $errors = [];
        $normalizedVehicleId = $this->positiveId($vehicleId);
        $normalizedSlotId = $this->positiveId($startSlotId);

        if ($normalizedVehicleId === null) {
            $errors['vehicle_id'] = 'Vui lòng chọn phương tiện hợp lệ.';
        }

        if ($normalizedSlotId === null) {
            $errors['start_slot_id'] = 'Vui lòng chọn khung giờ hợp lệ.';
        }

        if (!is_array($serviceIds) || $serviceIds === []) {
            $errors['service_ids'] = 'Vui lòng chọn một gói rửa chính.';
            $normalizedServiceIds = [];
        } else {
            $normalizedServiceIds = [];

            foreach ($serviceIds as $serviceId) {
                if (!is_string($serviceId) || $this->positiveId($serviceId) === null) {
                    $errors['service_ids'] = 'Danh sách dịch vụ không hợp lệ.';
                    break;
                }

                $normalizedServiceIds[] = (int) $serviceId;
            }

            if (count($normalizedServiceIds) !== count(array_unique($normalizedServiceIds))) {
                $errors['service_ids'] = 'Mỗi dịch vụ chỉ được chọn một lần.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $normalizedRewardId = $rewardRedemptionId === ''
            ? null
            : $this->positiveId($rewardRedemptionId);

        if ($rewardRedemptionId !== '' && $normalizedRewardId === null) {
            throw new ValidationException([
                'reward_redemption_id' => 'Reward được chọn không hợp lệ.',
            ]);
        }

        sort($normalizedServiceIds);

        return new BookingSelection(
            $normalizedVehicleId,
            $normalizedSlotId,
            $normalizedServiceIds,
            $normalizedRewardId
        );
    }

    private function positiveId(string $value): ?int
    {
        return preg_match('/^[1-9][0-9]*$/', $value) === 1 ? (int) $value : null;
    }
}
