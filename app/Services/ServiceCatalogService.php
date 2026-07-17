<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Repositories\ServiceCatalogRepository;
use App\Validation\ServiceCatalogValidator;
use PDOException;

final readonly class ServiceCatalogService
{
    public function __construct(
        private ServiceCatalogRepository $catalog,
        private ServiceCatalogValidator $validator
    ) {
    }

    /**
     * @return array{
     *     vehicle_types: list<array{id: int, code: string, display_name: string}>,
     *     selected_type: array{id: int, code: string, display_name: string, is_active: bool},
     *     services: list<array<string, mixed>>
     * }
     */
    public function customerCatalog(string $vehicleTypeId): array
    {
        $types = $this->catalog->findActiveVehicleTypes();

        if ($types === []) {
            throw new ValidationException([
                'vehicle_type_id' => 'Hiện chưa có loại phương tiện hoạt động.',
            ]);
        }

        $selectedId = $vehicleTypeId === ''
            ? $types[0]['id']
            : $this->positiveId($vehicleTypeId, 'vehicle_type_id');
        $selectedType = $this->catalog->findVehicleType($selectedId);

        if ($selectedType === null || !$selectedType['is_active']) {
            throw new ValidationException([
                'vehicle_type_id' => 'Loại phương tiện không tồn tại hoặc đã ngừng hoạt động.',
            ]);
        }

        return [
            'vehicle_types' => $types,
            'selected_type' => $selectedType,
            'services' => $this->catalog->findActiveCatalogForType($selectedId),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function adminServices(): array
    {
        return $this->catalog->findAllServices();
    }

    /** @return list<array{id: int, code: string, display_name: string}> */
    public function vehicleTypes(): array
    {
        return $this->catalog->findActiveVehicleTypes();
    }

    /** @return array<string, mixed> */
    public function service(int $serviceId): array
    {
        $service = $this->catalog->findService($serviceId);

        if ($service === null) {
            throw new ValidationException(['service' => 'Không tìm thấy dịch vụ được yêu cầu.']);
        }

        return $service;
    }

    /** @param array<string, array<string, string>> $prices */
    public function create(
        string $code,
        string $name,
        string $description,
        array $prices,
        int $adminId
    ): int {
        [$code, $name, $description, $normalizedPrices] = $this->validatedData(
            $code,
            $name,
            $description,
            $prices
        );

        try {
            return $this->catalog->createWithPrices(
                $code,
                $name,
                $description,
                $normalizedPrices,
                $adminId
            );
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }
    }

    /** @param array<string, array<string, string>> $prices */
    public function update(
        int $serviceId,
        string $code,
        string $name,
        string $description,
        array $prices,
        int $adminId
    ): void {
        $this->service($serviceId);
        [$code, $name, $description, $normalizedPrices] = $this->validatedData(
            $code,
            $name,
            $description,
            $prices
        );

        try {
            $updated = $this->catalog->updateWithPrices(
                $serviceId,
                $code,
                $name,
                $description,
                $normalizedPrices,
                $adminId
            );
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }

        if (!$updated) {
            throw new ValidationException(['service' => 'Không tìm thấy dịch vụ được yêu cầu.']);
        }
    }

    public function deactivate(int $serviceId, int $adminId): void
    {
        $service = $this->service($serviceId);

        if (!(bool) $service['is_active']) {
            return;
        }

        if (!$this->catalog->deactivate($serviceId, $adminId)) {
            throw new ValidationException(['service' => 'Không thể ngừng dịch vụ được yêu cầu.']);
        }
    }

    public function activate(int $serviceId, int $adminId): void
    {
        $service = $this->service($serviceId);

        if ((bool) $service['is_active']) {
            return;
        }

        if (!$this->catalog->activate($serviceId, $adminId)) {
            throw new ValidationException(['service' => 'Không thể kích hoạt dịch vụ được yêu cầu.']);
        }
    }

    /**
     * @param array<string, array<string, string>> $prices
     * @return array{string, string, ?string, list<array<string, mixed>>}
     */
    private function validatedData(string $code, string $name, string $description, array $prices): array
    {
        $code = strtoupper(trim($code));
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $description = trim($description);
        $types = $this->catalog->findActiveVehicleTypes();
        $errors = $this->validator->validate($code, $name, $description, $prices, $types);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $normalizedPrices = [];

        foreach ($types as $type) {
            $key = (string) $type['id'];
            $row = $prices[$key] ?? [];
            $supported = ($row['is_supported'] ?? '') === '1';
            $active = ($row['is_active'] ?? '') === '1';
            $capacity = trim($row['capacity_units_override'] ?? '');
            $normalizedPrices[] = [
                'vehicle_type_id' => $type['id'],
                'price' => $supported ? $this->validator->normalizeMoney($row['price']) : null,
                'duration_minutes' => $supported ? (int) $row['duration_minutes'] : null,
                'capacity_units_override' => $supported && $capacity !== '' ? (int) $capacity : null,
                'is_supported' => $supported ? 1 : 0,
                'is_active' => $active ? 1 : 0,
            ];
        }

        return [$code, $name, $description !== '' ? $description : null, $normalizedPrices];
    }

    private function positiveId(string $value, string $field): int
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new ValidationException([$field => 'Giá trị lựa chọn không hợp lệ.']);
        }

        return (int) $value;
    }

    private function throwDuplicateOrOriginal(PDOException $exception): never
    {
        if ($exception->getCode() === '23000') {
            throw new DuplicateCatalogException('Mã dịch vụ đã tồn tại.');
        }

        throw $exception;
    }
}
