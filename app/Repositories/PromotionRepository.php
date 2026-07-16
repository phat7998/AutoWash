<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class PromotionRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function lockTierPerks(int $tierId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT tier_perks.*
            FROM tier_perks
            WHERE tier_perks.tier_id = :tier_id
              AND tier_perks.is_active = TRUE
              AND (tier_perks.service_id IS NULL OR EXISTS (
                  SELECT 1 FROM services
                  WHERE services.id = tier_perks.service_id AND services.is_active = TRUE
              ))
            ORDER BY tier_perks.id
            FOR UPDATE
            SQL
        );
        $statement->execute(['tier_id' => $tierId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function lockPromotionCandidates(string $at): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT *
            FROM promotions
            WHERE is_active = TRUE AND start_at <= :at AND end_at >= :at_end
            ORDER BY id
            FOR UPDATE
            SQL
        );
        $statement->execute(['at' => $at, 'at_end' => $at]);
        $promotions = $statement->fetchAll();

        foreach ($promotions as &$promotion) {
            $promotionId = (int) $promotion['id'];
            $promotion['tier_ids'] = $this->relationIds('promotion_tiers', 'tier_id', $promotionId);
            $promotion['service_ids'] = $this->relationIds(
                'promotion_services',
                'service_id',
                $promotionId
            );
            $promotion['vehicle_type_ids'] = $this->relationIds(
                'promotion_vehicle_types',
                'vehicle_type_id',
                $promotionId
            );
        }

        return $promotions;
    }

    /** @return array{total: int, user: int} */
    public function promotionReservationCounts(int $promotionId, int $userId): array
    {
        $usageStatement = $this->database->prepare(
            <<<'SQL'
            SELECT id, user_id
            FROM promotion_usages
            WHERE promotion_id = :promotion_id
            ORDER BY id
            FOR UPDATE
            SQL
        );
        $usageStatement->execute(['promotion_id' => $promotionId]);
        $bookingStatement = $this->database->prepare(
            <<<'SQL'
            SELECT id, user_id
            FROM bookings
            WHERE promotion_id = :promotion_id AND status IN ('pending', 'confirmed')
            ORDER BY id
            FOR UPDATE
            SQL
        );
        $bookingStatement->execute(['promotion_id' => $promotionId]);
        $rows = [...$usageStatement->fetchAll(), ...$bookingStatement->fetchAll()];
        $userCount = count(array_filter(
            $rows,
            static fn (array $row): bool => (int) $row['user_id'] === $userId
        ));

        return ['total' => count($rows), 'user' => $userCount];
    }

    /** @return list<array<string, mixed>> */
    public function availableRedemptions(int $userId, string $at): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT reward_redemptions.id AS redemption_id, reward_redemptions.expires_at,
                rewards.name, rewards.reward_type, rewards.value, rewards.max_discount,
                rewards.service_id, services.name AS service_name,
                GROUP_CONCAT(vehicle_types.display_name ORDER BY vehicle_types.id SEPARATOR ', ')
                    AS vehicle_type_names
            FROM reward_redemptions
            INNER JOIN rewards ON rewards.id = reward_redemptions.reward_id
            LEFT JOIN services ON services.id = rewards.service_id
            LEFT JOIN reward_vehicle_types ON reward_vehicle_types.reward_id = rewards.id
            LEFT JOIN vehicle_types ON vehicle_types.id = reward_vehicle_types.vehicle_type_id
            WHERE reward_redemptions.user_id = :user_id
              AND reward_redemptions.status = 'available'
              AND reward_redemptions.booking_id IS NULL
              AND reward_redemptions.expires_at > :at
            GROUP BY reward_redemptions.id, rewards.id, services.id
            ORDER BY reward_redemptions.expires_at, reward_redemptions.id
            SQL
        );
        $statement->execute(['user_id' => $userId, 'at' => $at]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function lockRedemption(int $redemptionId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT reward_redemptions.id AS redemption_id, reward_redemptions.user_id,
                reward_redemptions.booking_id, reward_redemptions.status,
                reward_redemptions.expires_at, rewards.id AS reward_id, rewards.name,
                rewards.reward_type, rewards.value, rewards.max_discount, rewards.service_id,
                rewards.is_active, tiers.rank_order AS minimum_tier_rank
            FROM reward_redemptions
            INNER JOIN rewards ON rewards.id = reward_redemptions.reward_id
            LEFT JOIN tiers ON tiers.id = rewards.minimum_tier_id
            WHERE reward_redemptions.id = :id
            LIMIT 1
            FOR UPDATE
            SQL
        );
        $statement->execute(['id' => $redemptionId]);
        $reward = $statement->fetch();

        if (!is_array($reward)) {
            return null;
        }

        $vehicles = $this->database->prepare(
            'SELECT vehicle_type_id FROM reward_vehicle_types WHERE reward_id = :reward_id ORDER BY vehicle_type_id'
        );
        $vehicles->execute(['reward_id' => $reward['reward_id']]);
        $reward['vehicle_type_ids'] = array_map('intval', $vehicles->fetchAll(PDO::FETCH_COLUMN));

        return $reward;
    }

    public function attachReward(int $redemptionId, int $bookingId, string $at): bool
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE reward_redemptions
            SET booking_id = :booking_id, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND status = 'available' AND booking_id IS NULL AND expires_at > :at
            SQL
        );
        $statement->execute(['booking_id' => $bookingId, 'id' => $redemptionId, 'at' => $at]);

        return $statement->rowCount() === 1;
    }

    public function completePromotionUsage(array $booking): void
    {
        if ($booking['promotion_id'] === null || (string) $booking['promotion_discount'] === '0.00') {
            return;
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO promotion_usages (
                promotion_id, user_id, booking_id, discount_amount, used_at
            ) VALUES (
                :promotion_id, :user_id, :booking_id, :discount_amount, CURRENT_TIMESTAMP
            )
            SQL
        );
        $statement->execute([
            'promotion_id' => $booking['promotion_id'],
            'user_id' => $booking['user_id'],
            'booking_id' => $booking['id'],
            'discount_amount' => $booking['promotion_discount'],
        ]);
    }

    public function completeRewardUsage(int $bookingId): bool
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE reward_redemptions
            SET status = 'used', used_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE booking_id = :booking_id AND status = 'available'
            SQL
        );
        $statement->execute(['booking_id' => $bookingId]);

        return $statement->rowCount() === 1;
    }

    public function releaseReward(int $bookingId, string $at): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE reward_redemptions
            SET status = IF(expires_at > :at, 'available', 'expired'),
                booking_id = NULL, used_at = NULL, updated_at = CURRENT_TIMESTAMP
            WHERE booking_id = :booking_id AND status = 'available'
            SQL
        );
        $statement->execute(['at' => $at, 'booking_id' => $bookingId]);
    }

    public function inTransaction(): bool
    {
        return $this->database->inTransaction();
    }

    /** @return list<array<string, mixed>> */
    public function promotions(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT promotions.*,
                GROUP_CONCAT(DISTINCT tiers.name ORDER BY tiers.rank_order SEPARATOR ', ') AS tier_names,
                GROUP_CONCAT(DISTINCT services.name ORDER BY services.id SEPARATOR ', ') AS service_names,
                GROUP_CONCAT(DISTINCT vehicle_types.display_name ORDER BY vehicle_types.id SEPARATOR ', ')
                    AS vehicle_type_names
            FROM promotions
            LEFT JOIN promotion_tiers ON promotion_tiers.promotion_id = promotions.id
            LEFT JOIN tiers ON tiers.id = promotion_tiers.tier_id
            LEFT JOIN promotion_services ON promotion_services.promotion_id = promotions.id
            LEFT JOIN services ON services.id = promotion_services.service_id
            LEFT JOIN promotion_vehicle_types
                ON promotion_vehicle_types.promotion_id = promotions.id
            LEFT JOIN vehicle_types ON vehicle_types.id = promotion_vehicle_types.vehicle_type_id
            GROUP BY promotions.id
            ORDER BY promotions.is_active DESC, promotions.start_at DESC, promotions.id DESC
            SQL
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function promotion(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM promotions WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }
        $row['tier_ids'] = $this->relationIds('promotion_tiers', 'tier_id', $id);
        $row['service_ids'] = $this->relationIds('promotion_services', 'service_id', $id);
        $row['vehicle_type_ids'] = $this->relationIds(
            'promotion_vehicle_types',
            'vehicle_type_id',
            $id
        );

        return $row;
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function formOptions(): array
    {
        return [
            'tiers' => $this->database->query(
                'SELECT id, code, name FROM tiers WHERE is_active = TRUE ORDER BY rank_order, id'
            )->fetchAll(),
            'services' => $this->database->query(
                'SELECT id, name FROM services WHERE is_active = TRUE ORDER BY name, id'
            )->fetchAll(),
            'vehicle_types' => $this->database->query(
                'SELECT id, display_name FROM vehicle_types WHERE is_active = TRUE ORDER BY id'
            )->fetchAll(),
        ];
    }

    public function savePromotion(?int $id, array $data, int $adminId): int
    {
        return $this->transactional(function () use ($id, $data, $adminId): int {
            $before = $id === null ? null : $this->promotion($id);
            $relations = [
                'promotion_tiers' => $data['tier_ids'],
                'promotion_services' => $data['service_ids'],
                'promotion_vehicle_types' => $data['vehicle_type_ids'],
            ];
            unset($data['tier_ids'], $data['service_ids'], $data['vehicle_type_ids']);
            if ($id === null) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO promotions (
                        code, name, description, discount_type, discount_value, max_discount,
                        minimum_order_value, start_at, end_at, usage_limit, per_user_limit, is_active
                    ) VALUES (
                        :code, :name, :description, :discount_type, :discount_value, :max_discount,
                        :minimum_order_value, :start_at, :end_at, :usage_limit, :per_user_limit, TRUE
                    )
                    SQL
                );
                $statement->execute($data);
                $id = (int) $this->database->lastInsertId();
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE promotions SET code = :code, name = :name, description = :description,
                        discount_type = :discount_type, discount_value = :discount_value,
                        max_discount = :max_discount, minimum_order_value = :minimum_order_value,
                        start_at = :start_at, end_at = :end_at, usage_limit = :usage_limit,
                        per_user_limit = :per_user_limit, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    SQL
                );
                $statement->execute($data + ['id' => $id]);
            }
            $this->replaceRelations($id, $relations);
            $this->audit($adminId, 'promotion_saved', $id, $before, $this->promotion($id));

            return $id;
        });
    }

    public function setPromotionActive(int $id, bool $active, int $adminId): bool
    {
        return $this->transactional(function () use ($id, $active, $adminId): bool {
            $before = $this->promotion($id);
            if ($before === null) {
                return false;
            }
            $statement = $this->database->prepare(
                'UPDATE promotions SET is_active = :active, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $statement->execute(['active' => $active ? 1 : 0, 'id' => $id]);
            $this->audit(
                $adminId,
                $active ? 'promotion_activated' : 'promotion_deactivated',
                $id,
                $before,
                $this->promotion($id)
            );

            return true;
        });
    }

    /** @return list<int> */
    private function relationIds(string $table, string $column, int $promotionId): array
    {
        $statement = $this->database->prepare(
            "SELECT {$column} FROM {$table} WHERE promotion_id = :promotion_id ORDER BY {$column}"
        );
        $statement->execute(['promotion_id' => $promotionId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<string, list<int>> $relations */
    private function replaceRelations(int $promotionId, array $relations): void
    {
        $columns = [
            'promotion_tiers' => 'tier_id',
            'promotion_services' => 'service_id',
            'promotion_vehicle_types' => 'vehicle_type_id',
        ];
        foreach ($relations as $table => $ids) {
            $column = $columns[$table];
            $delete = $this->database->prepare("DELETE FROM {$table} WHERE promotion_id = :id");
            $delete->execute(['id' => $promotionId]);
            $insert = $this->database->prepare(
                "INSERT INTO {$table} (promotion_id, {$column}) VALUES (:promotion_id, :relation_id)"
            );
            foreach ($ids as $relationId) {
                $insert->execute(['promotion_id' => $promotionId, 'relation_id' => $relationId]);
            }
        }
    }

    private function audit(int $adminId, string $action, int $id, ?array $before, ?array $after): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO audit_logs (
                actor_user_id, action, target_type, target_id, before_json, after_json, reason
            ) VALUES (
                :actor_id, :action, 'promotion', :target_id, :before_json, :after_json,
                'Cập nhật promotion trong trang quản trị.'
            )
            SQL
        );
        $statement->execute([
            'actor_id' => $adminId,
            'action' => $action,
            'target_id' => $id,
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
