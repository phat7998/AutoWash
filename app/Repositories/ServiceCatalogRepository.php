<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class ServiceCatalogRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array{id: int, code: string, display_name: string}> */
    public function findActiveVehicleTypes(): array
    {
        $rows = $this->database->query(
            'SELECT id, code, display_name FROM vehicle_types WHERE is_active = TRUE ORDER BY id'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'display_name' => (string) $row['display_name'],
        ], $rows);
    }

    /** @return array{id: int, code: string, display_name: string, is_active: bool}|null */
    public function findVehicleType(int $vehicleTypeId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, code, display_name, is_active FROM vehicle_types WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $vehicleTypeId]);
        $row = $statement->fetch();

        return is_array($row) ? [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'display_name' => (string) $row['display_name'],
            'is_active' => (bool) $row['is_active'],
        ] : null;
    }

    /** @return list<array<string, mixed>> */
    public function findActiveServiceGroups(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT id, code, name, selection_mode, min_selection, max_selection
            FROM service_groups
            WHERE is_active = TRUE
            ORDER BY id
            SQL
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findActiveCatalogForType(int $vehicleTypeId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                services.id,
                services.code,
                services.name,
                services.description,
                service_groups.id AS service_group_id,
                service_groups.code AS service_group_code,
                service_groups.name AS service_group_name,
                service_groups.selection_mode,
                service_groups.min_selection,
                service_groups.max_selection,
                service_vehicle_prices.price,
                service_vehicle_prices.duration_minutes,
                COALESCE(
                    service_vehicle_prices.capacity_units_override,
                    vehicle_types.default_capacity_units
                ) AS capacity_units
            FROM services
            INNER JOIN service_vehicle_prices ON service_vehicle_prices.service_id = services.id
            INNER JOIN service_groups ON service_groups.id = services.service_group_id
            INNER JOIN vehicle_types ON vehicle_types.id = service_vehicle_prices.vehicle_type_id
            WHERE services.is_active = TRUE
              AND service_vehicle_prices.vehicle_type_id = :vehicle_type_id
              AND service_vehicle_prices.is_supported = TRUE
              AND service_vehicle_prices.is_active = TRUE
              AND vehicle_types.is_active = TRUE
              AND service_groups.is_active = TRUE
            ORDER BY service_groups.id, services.id
            SQL
        );
        $statement->execute(['vehicle_type_id' => $vehicleTypeId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findAllServices(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT
                services.id,
                services.code,
                services.name,
                services.description,
                services.is_active,
                service_groups.code AS service_group_code,
                service_groups.name AS service_group_name,
                service_groups.selection_mode,
                service_groups.min_selection,
                service_groups.max_selection,
                SUM(service_vehicle_prices.is_supported = TRUE AND service_vehicle_prices.is_active = TRUE)
                    AS supported_type_count
            FROM services
            INNER JOIN service_groups ON service_groups.id = services.service_group_id
            LEFT JOIN service_vehicle_prices ON service_vehicle_prices.service_id = services.id
            GROUP BY services.id
            ORDER BY services.is_active DESC, services.name, services.id
            SQL
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findService(int $serviceId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT services.id, services.code, services.name, services.description,
                services.service_group_id, services.is_active,
                service_groups.code AS service_group_code,
                service_groups.name AS service_group_name,
                service_groups.selection_mode,
                service_groups.min_selection,
                service_groups.max_selection
            FROM services
            INNER JOIN service_groups ON service_groups.id = services.service_group_id
            WHERE services.id = :id
            LIMIT 1
            SQL
        );
        $statement->execute(['id' => $serviceId]);
        $service = $statement->fetch();

        if (!is_array($service)) {
            return null;
        }

        $priceStatement = $this->database->prepare(
            <<<'SQL'
            SELECT
                vehicle_type_id,
                price,
                duration_minutes,
                capacity_units_override,
                is_supported,
                is_active
            FROM service_vehicle_prices
            WHERE service_id = :service_id
            SQL
        );
        $priceStatement->execute(['service_id' => $serviceId]);
        $service['prices'] = array_column($priceStatement->fetchAll(), null, 'vehicle_type_id');

        return $service;
    }

    /**
     * @param list<array<string, mixed>> $prices
     */
    public function createWithPrices(
        string $code,
        string $name,
        ?string $description,
        int $serviceGroupId,
        array $prices,
        int $adminId
    ): int {
        return $this->transactional(function () use (
            $code,
            $name,
            $description,
            $serviceGroupId,
            $prices,
            $adminId
        ): int {
            $statement = $this->database->prepare(
                <<<'SQL'
                INSERT INTO services (code, name, description, service_group_id, is_active)
                VALUES (:code, :name, :description, :service_group_id, TRUE)
                SQL
            );
            $statement->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'service_group_id' => $serviceGroupId,
            ]);
            $serviceId = (int) $this->database->lastInsertId();
            $this->upsertPrices($serviceId, $prices);
            $this->audit($adminId, 'service_created', $serviceId, null, $this->findService($serviceId));

            return $serviceId;
        });
    }

    /** @param list<array<string, mixed>> $prices */
    public function updateWithPrices(
        int $serviceId,
        string $code,
        string $name,
        ?string $description,
        int $serviceGroupId,
        array $prices,
        int $adminId
    ): bool {
        return $this->transactional(function () use (
            $serviceId,
            $code,
            $name,
            $description,
            $serviceGroupId,
            $prices,
            $adminId
        ): bool {
            $before = $this->findService($serviceId);
            $statement = $this->database->prepare(
                <<<'SQL'
                UPDATE services
                SET code = :code, name = :name, description = :description,
                    service_group_id = :service_group_id, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                SQL
            );
            $statement->execute([
                'id' => $serviceId,
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'service_group_id' => $serviceGroupId,
            ]);
            $exists = $statement->rowCount() === 1 || $this->findService($serviceId) !== null;

            if ($exists) {
                $this->upsertPrices($serviceId, $prices);
                $this->audit(
                    $adminId,
                    'service_updated',
                    $serviceId,
                    $before,
                    $this->findService($serviceId)
                );
            }

            return $exists;
        });
    }

    public function deactivate(int $serviceId, int $adminId): bool
    {
        return $this->setActive($serviceId, false, $adminId);
    }

    public function activate(int $serviceId, int $adminId): bool
    {
        return $this->setActive($serviceId, true, $adminId);
    }

    private function setActive(int $serviceId, bool $active, int $adminId): bool
    {
        return $this->transactional(function () use ($serviceId, $active, $adminId): bool {
            $before = $this->findService($serviceId);
            $statement = $this->database->prepare(
                <<<'SQL'
                UPDATE services
                SET is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND is_active <> :current_state
                SQL
            );
            $statement->execute([
                'id' => $serviceId,
                'is_active' => $active ? 1 : 0,
                'current_state' => $active ? 1 : 0,
            ]);
            $changed = $statement->rowCount() === 1;

            if ($changed) {
                $this->audit(
                    $adminId,
                    $active ? 'service_activated' : 'service_deactivated',
                    $serviceId,
                    $before,
                    $this->findService($serviceId)
                );
            }

            return $changed;
        });
    }

    /** @param list<array<string, mixed>> $prices */
    private function upsertPrices(int $serviceId, array $prices): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO service_vehicle_prices (
                service_id,
                vehicle_type_id,
                price,
                duration_minutes,
                capacity_units_override,
                is_supported,
                is_active
            ) VALUES (
                :service_id,
                :vehicle_type_id,
                :price,
                :duration_minutes,
                :capacity_units_override,
                :is_supported,
                :is_active
            )
            ON DUPLICATE KEY UPDATE
                price = VALUES(price),
                duration_minutes = VALUES(duration_minutes),
                capacity_units_override = VALUES(capacity_units_override),
                is_supported = VALUES(is_supported),
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($prices as $price) {
            $statement->execute(['service_id' => $serviceId] + $price);
        }
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    private function audit(
        int $adminId,
        string $action,
        int $serviceId,
        ?array $before,
        ?array $after
    ): void {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO audit_logs (
                actor_user_id, action, target_type, target_id, before_json, after_json, reason
            ) VALUES (
                :actor_id, :action, 'service', :target_id, :before_json, :after_json,
                'Cập nhật dịch vụ và cấu hình giá trong trang quản trị.'
            )
            SQL
        );
        $statement->execute([
            'actor_id' => $adminId,
            'action' => $action,
            'target_id' => $serviceId,
            'before_json' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_json' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
        ]);
    }

    private function transactional(callable $callback): mixed
    {
        $this->database->beginTransaction();

        try {
            $result = $callback();
            $this->database->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $throwable;
        }
    }
}
