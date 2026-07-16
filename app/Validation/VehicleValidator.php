<?php

declare(strict_types=1);

namespace App\Validation;

use App\Services\LicensePlateService;

final readonly class VehicleValidator
{
    public function __construct(private LicensePlateService $plates)
    {
    }

    /** @return array<string, string> */
    public function validate(string $vehicleTypeId, string $plate, string $brand, string $model, string $notes): array
    {
        $errors = [];

        if (filter_var($vehicleTypeId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors['vehicle_type_id'] = 'Vui lòng chọn loại phương tiện hợp lệ.';
        }

        $normalizedPlate = $this->plates->normalize($plate);

        if (!$this->plates->isCommonCivilianPlate($normalizedPlate)) {
            $errors['display_plate'] = (
                'Biển số chưa thuộc định dạng dân sự Việt Nam thông dụng được hỗ trợ.'
            );
        }

        if (mb_strlen($this->plates->display($plate)) > 30) {
            $errors['display_plate'] = 'Biển số hiển thị không được vượt quá 30 ký tự.';
        }

        if (mb_strlen($brand) > 100) {
            $errors['brand'] = 'Hãng xe không được vượt quá 100 ký tự.';
        }

        if (mb_strlen($model) > 100) {
            $errors['model'] = 'Dòng xe không được vượt quá 100 ký tự.';
        }

        if (mb_strlen($notes) > 1000) {
            $errors['notes'] = 'Ghi chú không được vượt quá 1.000 ký tự.';
        }

        return $errors;
    }
}
