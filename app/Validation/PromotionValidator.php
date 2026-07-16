<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;
use DateTimeImmutable;
use DateTimeZone;

final readonly class PromotionValidator
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    /** @param array<string, list<int>> $validIds @return array<string, mixed> */
    public function validate(array $input, array $validIds): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $type = (string) ($input['discount_type'] ?? '');
        if (preg_match('/^[A-Z][A-Z0-9_]{2,49}$/', $code) !== 1) {
            $errors['code'] = 'Mã promotion gồm 3–50 ký tự A–Z, 0–9 hoặc dấu gạch dưới.';
        }
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = 'Tên promotion bắt buộc và không quá 150 ký tự.';
        }
        if (mb_strlen($description) > 2000) {
            $errors['description'] = 'Mô tả không được vượt 2.000 ký tự.';
        }
        if (!in_array($type, ['fixed', 'percentage'], true)) {
            $errors['discount_type'] = 'Loại giảm giá không hợp lệ.';
        }
        $value = $this->money((string) ($input['discount_value'] ?? ''));
        $minimum = $this->money((string) ($input['minimum_order_value'] ?? ''));
        $max = ($input['max_discount'] ?? '') === ''
            ? null : $this->money((string) $input['max_discount']);
        if ($value === null || $value === '0.00') {
            $errors['discount_value'] = 'Giá trị giảm phải lớn hơn 0.';
        } elseif ($type === 'percentage' && $this->moneyCents($value) > 10_000) {
            $errors['discount_value'] = 'Tỷ lệ giảm không được vượt 100%.';
        }
        if ($minimum === null) {
            $errors['minimum_order_value'] = 'Giá trị đơn tối thiểu không hợp lệ.';
        }
        if (($input['max_discount'] ?? '') !== '' && ($max === null || $max === '0.00')) {
            $errors['max_discount'] = 'Mức giảm tối đa phải lớn hơn 0.';
        } elseif ($type !== 'percentage' && $max !== null) {
            $errors['max_discount'] = 'Chỉ promotion phần trăm mới có mức giảm tối đa.';
        }
        $start = $this->dateTime((string) ($input['start_at'] ?? ''));
        $end = $this->dateTime((string) ($input['end_at'] ?? ''));
        if ($start === null) {
            $errors['start_at'] = 'Thời điểm bắt đầu không hợp lệ.';
        }
        if ($end === null) {
            $errors['end_at'] = 'Thời điểm kết thúc không hợp lệ.';
        }
        if ($start !== null && $end !== null && $end <= $start) {
            $errors['end_at'] = 'Thời điểm kết thúc phải sau thời điểm bắt đầu.';
        }
        $usageLimit = $this->optionalPositiveInt((string) ($input['usage_limit'] ?? ''));
        $userLimit = $this->optionalPositiveInt((string) ($input['per_user_limit'] ?? ''));
        if (($input['usage_limit'] ?? '') !== '' && $usageLimit === null) {
            $errors['usage_limit'] = 'Giới hạn tổng phải là số nguyên dương.';
        }
        if (($input['per_user_limit'] ?? '') !== '' && $userLimit === null) {
            $errors['per_user_limit'] = 'Giới hạn mỗi khách phải là số nguyên dương.';
        }
        $tierIds = $this->ids($input['tier_ids'] ?? [], $validIds['tiers'], 'tier_ids', $errors);
        $serviceIds = $this->ids(
            $input['service_ids'] ?? [],
            $validIds['services'],
            'service_ids',
            $errors
        );
        $vehicleTypeIds = $this->ids(
            $input['vehicle_type_ids'] ?? [],
            $validIds['vehicle_types'],
            'vehicle_type_ids',
            $errors
        );
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'code' => $code, 'name' => $name, 'description' => $description === '' ? null : $description,
            'discount_type' => $type, 'discount_value' => $value, 'max_discount' => $max,
            'minimum_order_value' => $minimum,
            'start_at' => $start->format('Y-m-d H:i:s'), 'end_at' => $end->format('Y-m-d H:i:s'),
            'usage_limit' => $usageLimit, 'per_user_limit' => $userLimit,
            'tier_ids' => $tierIds, 'service_ids' => $serviceIds,
            'vehicle_type_ids' => $vehicleTypeIds,
        ];
    }

    /** @param mixed $values @param list<int> $valid @param array<string, string> $errors @return list<int> */
    private function ids(mixed $values, array $valid, string $key, array &$errors): array
    {
        if (!is_array($values)) {
            $errors[$key] = 'Danh sách mục tiêu không hợp lệ.';
            return [];
        }
        $ids = [];
        foreach ($values as $value) {
            if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
                $errors[$key] = 'Danh sách mục tiêu không hợp lệ.';
                continue;
            }
            $id = (int) $value;
            if (!in_array($id, $valid, true)) {
                $errors[$key] = 'Danh sách mục tiêu không hợp lệ.';
                continue;
            }
            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    private function optionalPositiveInt(string $value): ?int
    {
        return $value === '' ? null : (preg_match('/^[1-9][0-9]*$/', $value) === 1 ? (int) $value : null);
    }

    private function money(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,2}))?$/', $value, $matches) !== 1) {
            return null;
        }

        return $matches[1] . '.' . str_pad($matches[2] ?? '', 2, '0');
    }

    private function moneyCents(string $value): int
    {
        [$whole, $fraction] = explode('.', $value);
        return ((int) $whole * 100) + (int) $fraction;
    }

    private function dateTime(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $this->timezone);
        return $date !== false && $date->format('Y-m-d\TH:i') === $value ? $date : null;
    }
}
