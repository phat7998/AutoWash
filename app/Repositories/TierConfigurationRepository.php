<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class TierConfigurationRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function tiers(): array
    {
        return $this->database->query('SELECT * FROM tiers ORDER BY rank_order, id')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function tier(int $id): ?array
    {
        return $this->row('tiers', $id);
    }

    /** @return list<array<string, mixed>> */
    public function perks(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT tier_perks.*, tiers.name AS tier_name, services.name AS service_name
            FROM tier_perks
            INNER JOIN tiers ON tiers.id = tier_perks.tier_id
            LEFT JOIN services ON services.id = tier_perks.service_id
            ORDER BY tiers.rank_order, tier_perks.id
            SQL
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function perk(int $id): ?array
    {
        return $this->row('tier_perks', $id);
    }

    /** @return list<array<string, mixed>> */
    public function services(): array
    {
        return $this->database->query(
            'SELECT id, name FROM services WHERE is_active = TRUE ORDER BY name, id'
        )->fetchAll();
    }

    public function saveTier(?int $id, array $data, int $adminId): int
    {
        return $this->transactional(function () use ($id, $data, $adminId): int {
            $before = $id === null ? null : $this->tier($id);
            if ($id === null) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO tiers (
                        code, name, rank_order, booking_window_days, min_monthly_spend,
                        min_monthly_visits, point_rate, is_active
                    ) VALUES (
                        :code, :name, :rank_order, :booking_window_days, :min_monthly_spend,
                        :min_monthly_visits, :point_rate, TRUE
                    )
                    SQL
                );
                $statement->execute($data);
                $id = (int) $this->database->lastInsertId();
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE tiers SET code = :code, name = :name, rank_order = :rank_order,
                        booking_window_days = :booking_window_days,
                        min_monthly_spend = :min_monthly_spend,
                        min_monthly_visits = :min_monthly_visits,
                        point_rate = :point_rate, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    SQL
                );
                $statement->execute($data + ['id' => $id]);
            }
            $this->audit($adminId, 'tier_config_saved', 'tier', $id, $before, $this->tier($id));

            return $id;
        });
    }

    public function savePerk(?int $id, array $data, int $adminId): int
    {
        return $this->transactional(function () use ($id, $data, $adminId): int {
            $before = $id === null ? null : $this->perk($id);
            if ($id === null) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO tier_perks (tier_id, perk_type, value, service_id, is_active)
                    VALUES (:tier_id, :perk_type, :value, :service_id, TRUE)
                    SQL
                );
                $statement->execute($data);
                $id = (int) $this->database->lastInsertId();
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE tier_perks SET tier_id = :tier_id, perk_type = :perk_type,
                        value = :value, service_id = :service_id, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    SQL
                );
                $statement->execute($data + ['id' => $id]);
            }
            $this->audit($adminId, 'tier_perk_saved', 'tier_perk', $id, $before, $this->perk($id));

            return $id;
        });
    }

    public function setActive(string $table, int $id, bool $active, int $adminId): bool
    {
        $target = $table === 'tiers' ? 'tier' : 'tier_perk';

        return $this->transactional(function () use ($table, $id, $active, $adminId, $target): bool {
            $before = $this->row($table, $id);
            if ($before === null) {
                return false;
            }
            $statement = $this->database->prepare(
                "UPDATE {$table} SET is_active = :active, updated_at = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $statement->execute(['active' => $active ? 1 : 0, 'id' => $id]);
            $this->audit(
                $adminId,
                $active ? $target . '_activated' : $target . '_deactivated',
                $target,
                $id,
                $before,
                $this->row($table, $id)
            );

            return true;
        });
    }

    /** @return array<string, mixed>|null */
    private function row(string $table, int $id): ?array
    {
        if (!in_array($table, ['tiers', 'tier_perks'], true)) {
            throw new \InvalidArgumentException('Bảng cấu hình hạng không hợp lệ.');
        }
        $statement = $this->database->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private function audit(
        int $adminId,
        string $action,
        string $targetType,
        int $targetId,
        ?array $before,
        ?array $after
    ): void {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO audit_logs (
                actor_user_id, action, target_type, target_id, before_json, after_json, reason
            ) VALUES (
                :actor_id, :action, :target_type, :target_id, :before_json, :after_json,
                'Cập nhật cấu hình hạng thành viên trong trang quản trị.'
            )
            SQL
        );
        $statement->execute([
            'actor_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
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
