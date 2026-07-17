<?php

declare(strict_types=1);

namespace App\Validation;

final class ServiceCatalogValidator
{
    /**
     * @param array<string, array<string, string>> $prices
     * @param list<array{id: int, code: string, display_name: string}> $vehicleTypes
     * @param list<array{id: int, code: string, name: string}> $serviceGroups
     * @return array<string, string>
     */
    public function validate(
        string $code,
        string $name,
        string $description,
        string $serviceGroupId,
        array $prices,
        array $vehicleTypes,
        array $serviceGroups
    ): array {
        $errors = [];

        if (preg_match('/^[A-Z][A-Z0-9_]{1,49}$/', $code) !== 1) {
            $errors['code'] = 'Mã dịch vụ gồm 2–50 ký tự in hoa, chữ số hoặc dấu gạch dưới.';
        }

        $nameLength = mb_strlen($name);

        if ($nameLength < 2 || $nameLength > 150) {
            $errors['name'] = 'Tên dịch vụ phải có từ 2 đến 150 ký tự.';
        }

        if (mb_strlen($description) > 2000) {
            $errors['description'] = 'Mô tả không được vượt quá 2.000 ký tự.';
        }

        $validGroupIds = array_map(
            static fn (array $group): string => (string) $group['id'],
            $serviceGroups
        );

        if (!in_array($serviceGroupId, $validGroupIds, true)) {
            $errors['service_group_id'] = 'Vui lòng chọn nhóm dịch vụ đang hoạt động.';
        }

        foreach ($vehicleTypes as $type) {
            $key = (string) $type['id'];
            $row = $prices[$key] ?? [];
            $supported = ($row['is_supported'] ?? '') === '1';

            if (!$supported) {
                continue;
            }

            if (!$this->isPositiveMoney($row['price'] ?? '')) {
                $errors['prices.' . $key . '.price'] =
                    'Giá phải lớn hơn 0 và có tối đa 2 chữ số thập phân.';
            }

            if (!$this->isPositiveInteger($row['duration_minutes'] ?? '', 1440)) {
                $errors['prices.' . $key . '.duration_minutes'] = 'Thời lượng phải từ 1 đến 1.440 phút.';
            }

            $capacity = trim($row['capacity_units_override'] ?? '');

            if ($capacity !== '' && !$this->isPositiveInteger($capacity, 10000)) {
                $errors['prices.' . $key . '.capacity_units_override'] =
                    'Capacity override phải là số nguyên dương hoặc để trống.';
            }
        }

        return $errors;
    }

    public function normalizeMoney(string $value): string
    {
        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');

        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole) . '.' . $fraction;
    }

    private function isPositiveMoney(string $value): bool
    {
        $value = trim($value);

        if (preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value) !== 1) {
            return false;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return trim($whole, '0') !== '' || trim($fraction, '0') !== '';
    }

    private function isPositiveInteger(string $value, int $maximum): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $value) === 1 && (int) $value <= $maximum;
    }
}
