<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class RewardRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function rewards(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT rewards.*, services.name AS service_name, tiers.name AS minimum_tier_name,
                tiers.rank_order AS minimum_tier_rank,
                GROUP_CONCAT(vehicle_types.display_name ORDER BY vehicle_types.id SEPARATOR ', ') AS vehicle_types
            FROM rewards
            LEFT JOIN services ON services.id = rewards.service_id
            LEFT JOIN tiers ON tiers.id = rewards.minimum_tier_id
            LEFT JOIN reward_vehicle_types ON reward_vehicle_types.reward_id = rewards.id
            LEFT JOIN vehicle_types ON vehicle_types.id = reward_vehicle_types.vehicle_type_id
            GROUP BY rewards.id, services.id, tiers.id
            ORDER BY rewards.is_active DESC, rewards.points_cost, rewards.id
            SQL
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function reward(int $rewardId, bool $forUpdate = false): ?array
    {
        $statement = $this->database->prepare(
            'SELECT rewards.*, tiers.rank_order AS minimum_tier_rank '
            . 'FROM rewards LEFT JOIN tiers ON tiers.id = rewards.minimum_tier_id '
            . 'WHERE rewards.id = :id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $rewardId]);
        $reward = $statement->fetch();

        if (!is_array($reward)) {
            return null;
        }

        $vehicles = $this->database->prepare(
            'SELECT vehicle_type_id FROM reward_vehicle_types WHERE reward_id = :reward_id ORDER BY vehicle_type_id'
        );
        $vehicles->execute(['reward_id' => $rewardId]);
        $reward['vehicle_type_ids'] = array_map('intval', $vehicles->fetchAll(PDO::FETCH_COLUMN));

        return $reward;
    }

    /** @return array<string, mixed>|null */
    public function customerContext(int $userId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT users.id, users.role, users.status, users.point_balance,
                tiers.id AS tier_id, tiers.name AS tier_name, tiers.rank_order AS tier_rank
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.id = :id
            LIMIT 1
            SQL
        );
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function customerRedemptions(int $userId, string $at): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT reward_redemptions.*, rewards.name AS reward_name,
                CASE
                    WHEN reward_redemptions.status = 'available' AND reward_redemptions.expires_at <= :at
                    THEN 'expired'
                    WHEN reward_redemptions.status = 'available' AND reward_redemptions.booking_id IS NOT NULL
                    THEN 'reserved'
                    ELSE reward_redemptions.status
                END AS effective_status
            FROM reward_redemptions
            INNER JOIN rewards ON rewards.id = reward_redemptions.reward_id
            WHERE reward_redemptions.user_id = :user_id
            ORDER BY reward_redemptions.redeemed_at DESC, reward_redemptions.id DESC
            SQL
        );
        $statement->execute(['user_id' => $userId, 'at' => $at]);

        return $statement->fetchAll();
    }

    public function insertRedemption(
        int $userId,
        int $rewardId,
        int $points,
        string $redeemedAt,
        string $expiresAt
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO reward_redemptions (
                user_id, reward_id, points_spent, status, redeemed_at, expires_at
            ) VALUES (:user_id, :reward_id, :points, 'available', :redeemed_at, :expires_at)
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'points' => $points,
            'redeemed_at' => $redeemedAt,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->database->lastInsertId();
    }

    /** @return list<array{id: int, name: string}> */
    public function services(): array
    {
        return $this->database->query(
            'SELECT id, name FROM services WHERE is_active = TRUE ORDER BY name, id'
        )->fetchAll();
    }

    /** @return list<array{id: int, name: string}> */
    public function tiers(): array
    {
        return $this->database->query(
            'SELECT id, name FROM tiers WHERE is_active = TRUE ORDER BY rank_order, id'
        )->fetchAll();
    }

    /** @return list<array{id: int, display_name: string}> */
    public function vehicleTypes(): array
    {
        return $this->database->query(
            'SELECT id, display_name FROM vehicle_types WHERE is_active = TRUE ORDER BY id'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->transactional(function () use ($data): int {
            $statement = $this->database->prepare(
                <<<'SQL'
                INSERT INTO rewards (
                    code, name, reward_type, points_cost, value, max_discount, service_id,
                    minimum_tier_id, valid_days_after_redeem, is_active
                ) VALUES (
                    :code, :name, :reward_type, :points_cost, :value, :max_discount, :service_id,
                    :minimum_tier_id, :valid_days_after_redeem, TRUE
                )
                SQL
            );
            $statement->execute($this->rewardParameters($data));
            $rewardId = (int) $this->database->lastInsertId();
            $this->replaceVehicleTypes($rewardId, $data['vehicle_type_ids']);

            return $rewardId;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $rewardId, array $data): bool
    {
        return $this->transactional(function () use ($rewardId, $data): bool {
            $statement = $this->database->prepare(
                <<<'SQL'
                UPDATE rewards SET code = :code, name = :name, reward_type = :reward_type,
                    points_cost = :points_cost, value = :value, max_discount = :max_discount,
                    service_id = :service_id,
                    minimum_tier_id = :minimum_tier_id,
                    valid_days_after_redeem = :valid_days_after_redeem,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                SQL
            );
            $statement->execute($this->rewardParameters($data) + ['id' => $rewardId]);
            $exists = $statement->rowCount() === 1 || $this->reward($rewardId) !== null;

            if ($exists) {
                $this->replaceVehicleTypes($rewardId, $data['vehicle_type_ids']);
            }

            return $exists;
        });
    }

    public function setActive(int $rewardId, bool $active): bool
    {
        $statement = $this->database->prepare(
            'UPDATE rewards SET is_active = :active, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE id = :id AND is_active <> :active_compare'
        );
        $statement->execute([
            'id' => $rewardId,
            'active' => $active ? 1 : 0,
            'active_compare' => $active ? 1 : 0,
        ]);

        return $statement->rowCount() === 1;
    }

    public function transactional(callable $callback): mixed
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

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function rewardParameters(array $data): array
    {
        $parameters = $data;
        unset($parameters['vehicle_type_ids']);

        return $parameters;
    }

    /** @param list<int> $vehicleTypeIds */
    private function replaceVehicleTypes(int $rewardId, array $vehicleTypeIds): void
    {
        $delete = $this->database->prepare('DELETE FROM reward_vehicle_types WHERE reward_id = :reward_id');
        $delete->execute(['reward_id' => $rewardId]);
        $insert = $this->database->prepare(
            'INSERT INTO reward_vehicle_types (reward_id, vehicle_type_id) VALUES (:reward_id, :vehicle_type_id)'
        );

        foreach ($vehicleTypeIds as $vehicleTypeId) {
            $insert->execute(['reward_id' => $rewardId, 'vehicle_type_id' => $vehicleTypeId]);
        }
    }
}
