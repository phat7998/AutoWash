<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class VehicleRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findAllByOwner(int $ownerId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                vehicles.id,
                vehicles.vehicle_type_id,
                vehicles.normalized_plate,
                vehicles.display_plate,
                vehicles.brand,
                vehicles.model,
                vehicles.notes,
                vehicles.is_active,
                vehicle_types.code AS vehicle_type_code,
                vehicle_types.display_name AS vehicle_type_name
            FROM vehicles
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            WHERE vehicles.user_id = :owner_id
            ORDER BY vehicles.is_active DESC, vehicles.created_at DESC, vehicles.id DESC
            SQL
        );
        $statement->execute(['owner_id' => $ownerId]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findOwnedById(int $vehicleId, int $ownerId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                vehicles.id,
                vehicles.user_id,
                vehicles.vehicle_type_id,
                vehicles.normalized_plate,
                vehicles.display_plate,
                vehicles.brand,
                vehicles.model,
                vehicles.notes,
                vehicles.is_active,
                vehicle_types.code AS vehicle_type_code,
                vehicle_types.display_name AS vehicle_type_name
            FROM vehicles
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            WHERE vehicles.id = :vehicle_id AND vehicles.user_id = :owner_id
            LIMIT 1
            SQL
        );
        $statement->execute(['vehicle_id' => $vehicleId, 'owner_id' => $ownerId]);
        $vehicle = $statement->fetch();

        return is_array($vehicle) ? $vehicle : null;
    }

    /** @return list<array{id: int, code: string, display_name: string}> */
    public function findActiveTypes(): array
    {
        $rows = $this->database->query(
            <<<'SQL'
            SELECT id, code, display_name
            FROM vehicle_types
            WHERE is_active = TRUE
            ORDER BY id
            SQL
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'display_name' => (string) $row['display_name'],
        ], $rows);
    }

    /** @return array{id: int, is_active: bool}|null */
    public function findTypeById(int $vehicleTypeId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, is_active FROM vehicle_types WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $vehicleTypeId]);
        $type = $statement->fetch();

        if (!is_array($type)) {
            return null;
        }

        return ['id' => (int) $type['id'], 'is_active' => (bool) $type['is_active']];
    }

    public function create(
        int $ownerId,
        int $vehicleTypeId,
        string $normalizedPlate,
        string $displayPlate,
        ?string $brand,
        ?string $model,
        ?string $notes
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO vehicles (
                user_id, vehicle_type_id, normalized_plate, display_plate, brand, model, notes, is_active
            ) VALUES (
                :owner_id, :vehicle_type_id, :normalized_plate, :display_plate, :brand, :model, :notes, TRUE
            )
            SQL
        );
        $statement->execute([
            'owner_id' => $ownerId,
            'vehicle_type_id' => $vehicleTypeId,
            'normalized_plate' => $normalizedPlate,
            'display_plate' => $displayPlate,
            'brand' => $brand,
            'model' => $model,
            'notes' => $notes,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function updateOwned(
        int $vehicleId,
        int $ownerId,
        int $vehicleTypeId,
        string $normalizedPlate,
        string $displayPlate,
        ?string $brand,
        ?string $model,
        ?string $notes
    ): bool {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE vehicles
            SET
                vehicle_type_id = :vehicle_type_id,
                normalized_plate = :normalized_plate,
                display_plate = :display_plate,
                brand = :brand,
                model = :model,
                notes = :notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :vehicle_id AND user_id = :owner_id
            SQL
        );
        $statement->execute([
            'vehicle_id' => $vehicleId,
            'owner_id' => $ownerId,
            'vehicle_type_id' => $vehicleTypeId,
            'normalized_plate' => $normalizedPlate,
            'display_plate' => $displayPlate,
            'brand' => $brand,
            'model' => $model,
            'notes' => $notes,
        ]);

        return $statement->rowCount() === 1;
    }

    public function deactivateOwned(int $vehicleId, int $ownerId): bool
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE vehicles
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP
            WHERE id = :vehicle_id AND user_id = :owner_id AND is_active = TRUE
            SQL
        );
        $statement->execute(['vehicle_id' => $vehicleId, 'owner_id' => $ownerId]);

        return $statement->rowCount() === 1;
    }
}
