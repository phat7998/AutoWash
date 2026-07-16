<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

final readonly class RewardValidator
{
    private const TYPES = ['fixed_discount', 'percentage_discount', 'free_service', 'add_on'];

    /**
     * @param list<int> $validServiceIds
     * @param list<int> $validTierIds
     * @param list<int> $validVehicleTypeIds
     * @return array<string, mixed>
     */
    public function validate(
        string $code,
        string $name,
        string $rewardType,
        string $pointsCost,
        string $value,
        string $serviceId,
        string $minimumTierId,
        string $validDays,
        array $vehicleTypeIds,
        array $validServiceIds,
        array $validTierIds,
        array $validVehicleTypeIds
    ): array {
        $code = strtoupper(trim($code));
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $errors = [];

        if (preg_match('/^[A-Z][A-Z0-9_]{2,49}$/', $code) !== 1) {
            $errors['code'] = 'Mã reward gồm 3–50 ký tự A–Z, 0–9 hoặc dấu gạch dưới.';
        }

        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = 'Tên reward bắt buộc và không quá 150 ký tự.';
        }

        if (!in_array($rewardType, self::TYPES, true)) {
            $errors['reward_type'] = 'Loại reward không hợp lệ.';
        }

        $points = $this->positiveInteger($pointsCost);
        $days = $this->positiveInteger($validDays);

        if ($points === null) {
            $errors['points_cost'] = 'Điểm đổi phải là số nguyên dương.';
        }

        if ($days === null) {
            $errors['valid_days_after_redeem'] = 'Số ngày hiệu lực phải là số nguyên dương.';
        }

        $normalizedValue = $this->money($value);
        $requiresPositiveValue = in_array($rewardType, ['fixed_discount', 'percentage_discount'], true);

        if ($normalizedValue === null || ($requiresPositiveValue && $normalizedValue === '0.00')) {
            $errors['value'] = $requiresPositiveValue
                ? 'Giá trị giảm phải lớn hơn 0.'
                : 'Giá trị reward không hợp lệ.';
        }

        if ($rewardType === 'percentage_discount' && $normalizedValue !== null) {
            [$whole, $fraction] = explode('.', $normalizedValue);

            if ((int) $whole > 100 || ((int) $whole === 100 && (int) $fraction > 0)) {
                $errors['value'] = 'Tỷ lệ giảm không được vượt 100%.';
            }
        }

        $service = $this->optionalId($serviceId);
        $tier = $this->optionalId($minimumTierId);

        if ($serviceId !== '' && $service === null) {
            $errors['service_id'] = 'Dịch vụ được chọn không hợp lệ.';
        } elseif (in_array($rewardType, ['free_service', 'add_on'], true) && $service === null) {
            $errors['service_id'] = 'Loại reward này bắt buộc chọn dịch vụ.';
        } elseif ($service !== null && !in_array($service, $validServiceIds, true)) {
            $errors['service_id'] = 'Dịch vụ được chọn không hợp lệ.';
        }

        if ($minimumTierId !== '' && ($tier === null || !in_array($tier, $validTierIds, true))) {
            $errors['minimum_tier_id'] = 'Hạng tối thiểu không hợp lệ.';
        }

        $vehicles = [];

        foreach ($vehicleTypeIds as $vehicleTypeId) {
            if (!is_string($vehicleTypeId) || preg_match('/^[1-9][0-9]*$/', $vehicleTypeId) !== 1) {
                $errors['vehicle_type_ids'] = 'Loại phương tiện được chọn không hợp lệ.';
                continue;
            }

            $id = (int) $vehicleTypeId;

            if (!in_array($id, $validVehicleTypeIds, true)) {
                $errors['vehicle_type_ids'] = 'Loại phương tiện được chọn không hợp lệ.';
                continue;
            }

            $vehicles[$id] = $id;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'code' => $code,
            'name' => $name,
            'reward_type' => $rewardType,
            'points_cost' => $points,
            'value' => $normalizedValue,
            'service_id' => $service,
            'minimum_tier_id' => $tier,
            'valid_days_after_redeem' => $days,
            'vehicle_type_ids' => array_values($vehicles),
        ];
    }

    private function positiveInteger(string $value): ?int
    {
        return preg_match('/^[1-9][0-9]*$/', $value) === 1 ? (int) $value : null;
    }

    private function optionalId(string $value): ?int
    {
        return $value !== '' && preg_match('/^[1-9][0-9]*$/', $value) === 1 ? (int) $value : null;
    }

    private function money(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,2}))?$/', $value, $matches) !== 1) {
            return null;
        }

        return $matches[1] . '.' . str_pad($matches[2] ?? '', 2, '0');
    }
}
