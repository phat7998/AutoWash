<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

final class TierConfigurationValidator
{
    /** @return array<string, mixed> */
    public function tier(array $input): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        if (preg_match('/^[A-Z][A-Z0-9_]{2,29}$/', $code) !== 1) {
            $errors['code'] = 'Mã hạng gồm 3–30 ký tự A–Z, 0–9 hoặc dấu gạch dưới.';
        }
        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Tên hạng bắt buộc và không quá 100 ký tự.';
        }
        $rank = $this->positiveInt((string) ($input['rank_order'] ?? ''));
        $window = $this->nonNegativeInt((string) ($input['booking_window_days'] ?? ''));
        $visits = $this->nonNegativeInt((string) ($input['min_monthly_visits'] ?? ''));
        $spend = $this->decimal((string) ($input['min_monthly_spend'] ?? ''));
        $rate = $this->decimal((string) ($input['point_rate'] ?? ''));
        if ($rank === null) {
            $errors['rank_order'] = 'Thứ tự hạng phải là số nguyên dương.';
        }
        if ($window === null) {
            $errors['booking_window_days'] = 'Số ngày đặt trước không được âm.';
        }
        if ($visits === null) {
            $errors['min_monthly_visits'] = 'Số lượt tối thiểu không được âm.';
        }
        if ($spend === null) {
            $errors['min_monthly_spend'] = 'Chi tiêu tối thiểu không hợp lệ.';
        }
        if ($rate === null) {
            $errors['point_rate'] = 'Tỷ lệ điểm không hợp lệ.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'code' => $code, 'name' => $name, 'rank_order' => $rank,
            'booking_window_days' => $window, 'min_monthly_spend' => $spend,
            'min_monthly_visits' => $visits, 'point_rate' => $rate,
        ];
    }

    /** @param list<int> $tierIds @param list<int> $serviceIds @return array<string, mixed> */
    public function perk(array $input, array $tierIds, array $serviceIds): array
    {
        $errors = [];
        $tierId = $this->positiveInt((string) ($input['tier_id'] ?? ''));
        $serviceId = ($input['service_id'] ?? '') === ''
            ? null : $this->positiveInt((string) $input['service_id']);
        $type = (string) ($input['perk_type'] ?? '');
        $value = $this->decimal((string) ($input['value'] ?? ''));
        if ($tierId === null || !in_array($tierId, $tierIds, true)) {
            $errors['tier_id'] = 'Hạng thành viên không hợp lệ.';
        }
        if (!in_array($type, ['percentage_discount', 'fixed_discount', 'free_add_on'], true)) {
            $errors['perk_type'] = 'Loại quyền lợi không hợp lệ.';
        }
        if ($value === null || $value === '0.00') {
            $errors['value'] = 'Giá trị quyền lợi phải lớn hơn 0.';
        } elseif ($type === 'percentage_discount' && $this->decimalCents($value) > 10_000) {
            $errors['value'] = 'Tỷ lệ giảm không được vượt 100%.';
        }
        if ($serviceId !== null && !in_array($serviceId, $serviceIds, true)) {
            $errors['service_id'] = 'Dịch vụ không hợp lệ.';
        } elseif ($type === 'free_add_on' && $serviceId === null) {
            $errors['service_id'] = 'Quyền lợi dịch vụ miễn phí phải chọn dịch vụ.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['tier_id' => $tierId, 'perk_type' => $type, 'value' => $value, 'service_id' => $serviceId];
    }

    private function positiveInt(string $value): ?int
    {
        return preg_match('/^[1-9][0-9]*$/', $value) === 1 ? (int) $value : null;
    }

    private function nonNegativeInt(string $value): ?int
    {
        return preg_match('/^(0|[1-9][0-9]*)$/', $value) === 1 ? (int) $value : null;
    }

    private function decimal(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,2}))?$/', $value, $matches) !== 1) {
            return null;
        }

        return $matches[1] . '.' . str_pad($matches[2] ?? '', 2, '0');
    }

    private function decimalCents(string $value): int
    {
        [$whole, $fraction] = explode('.', $value);

        return ((int) $whole * 100) + (int) $fraction;
    }
}
