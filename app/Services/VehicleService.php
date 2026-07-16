<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateLicensePlateException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Repositories\VehicleRepository;
use App\Validation\VehicleValidator;
use PDOException;

final readonly class VehicleService
{
    public function __construct(
        private VehicleRepository $vehicles,
        private VehicleValidator $validator,
        private LicensePlateService $plates
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listForOwner(int $ownerId): array
    {
        return $this->vehicles->findAllByOwner($ownerId);
    }

    /** @return list<array{id: int, code: string, display_name: string}> */
    public function activeTypes(): array
    {
        return $this->vehicles->findActiveTypes();
    }

    /** @return array<string, mixed> */
    public function ownedVehicle(int $vehicleId, int $ownerId): array
    {
        $vehicle = $this->vehicles->findOwnedById($vehicleId, $ownerId);

        if ($vehicle === null) {
            throw new VehicleOwnershipException();
        }

        return $vehicle;
    }

    public function create(
        int $ownerId,
        string $vehicleTypeId,
        string $plate,
        string $brand,
        string $model,
        string $notes
    ): int {
        [$typeId, $normalizedPlate, $displayPlate, $brand, $model, $notes] = $this->validatedData(
            $vehicleTypeId,
            $plate,
            $brand,
            $model,
            $notes
        );

        try {
            return $this->vehicles->create(
                $ownerId,
                $typeId,
                $normalizedPlate,
                $displayPlate,
                $brand,
                $model,
                $notes
            );
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }
    }

    public function update(
        int $vehicleId,
        int $ownerId,
        string $vehicleTypeId,
        string $plate,
        string $brand,
        string $model,
        string $notes
    ): void {
        $this->ownedVehicle($vehicleId, $ownerId);
        [$typeId, $normalizedPlate, $displayPlate, $brand, $model, $notes] = $this->validatedData(
            $vehicleTypeId,
            $plate,
            $brand,
            $model,
            $notes
        );

        try {
            $updated = $this->vehicles->updateOwned(
                $vehicleId,
                $ownerId,
                $typeId,
                $normalizedPlate,
                $displayPlate,
                $brand,
                $model,
                $notes
            );
        } catch (PDOException $exception) {
            $this->throwDuplicateOrOriginal($exception);
        }

        if (!$updated && $this->vehicles->findOwnedById($vehicleId, $ownerId) === null) {
            throw new VehicleOwnershipException();
        }
    }

    public function deactivate(int $vehicleId, int $ownerId): void
    {
        $vehicle = $this->ownedVehicle($vehicleId, $ownerId);

        if (!(bool) $vehicle['is_active']) {
            return;
        }

        if (
            !$this->vehicles->deactivateOwned($vehicleId, $ownerId)
            && $this->vehicles->findOwnedById($vehicleId, $ownerId) === null
        ) {
            throw new VehicleOwnershipException();
        }
    }

    /** @return array{int, string, string, ?string, ?string, ?string} */
    private function validatedData(
        string $vehicleTypeId,
        string $plate,
        string $brand,
        string $model,
        string $notes
    ): array {
        $brand = trim(preg_replace('/\s+/u', ' ', $brand) ?? $brand);
        $model = trim(preg_replace('/\s+/u', ' ', $model) ?? $model);
        $notes = trim($notes);
        $errors = $this->validator->validate($vehicleTypeId, $plate, $brand, $model, $notes);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $typeId = (int) $vehicleTypeId;
        $type = $this->vehicles->findTypeById($typeId);

        if ($type === null || !$type['is_active']) {
            throw new ValidationException([
                'vehicle_type_id' => 'Loại phương tiện không tồn tại hoặc đã ngừng hoạt động.',
            ]);
        }

        return [
            $typeId,
            $this->plates->normalize($plate),
            $this->plates->display($plate),
            $brand !== '' ? $brand : null,
            $model !== '' ? $model : null,
            $notes !== '' ? $notes : null,
        ];
    }

    private function throwDuplicateOrOriginal(PDOException $exception): never
    {
        if ($exception->getCode() === '23000') {
            throw new DuplicateLicensePlateException();
        }

        throw $exception;
    }
}
