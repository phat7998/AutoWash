<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class LoyaltyTransactionRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function lockCustomerContext(int $userId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                users.id,
                users.full_name,
                users.role,
                users.status,
                users.monthly_spend,
                users.monthly_visits,
                users.point_balance,
                tiers.code AS tier_code,
                tiers.name AS tier_name,
                tiers.point_rate
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.id = :user_id
            LIMIT 1
            FOR UPDATE
            SQL
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function isActiveAdmin(int $userId): bool
    {
        $statement = $this->database->prepare(
            "SELECT 1 FROM users WHERE id = :id AND role = 'admin' AND status = 'active'"
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    public function insertEarn(
        int $userId,
        int $bookingId,
        int $points,
        string $earnedAt,
        string $expiresAt
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                description, earned_at, expires_at
            ) VALUES (
                :user_id, 'earn', :points_delta, :remaining_points, 'booking', :booking_id,
                :description, :earned_at, :expires_at
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'points_delta' => $points,
            'remaining_points' => $points,
            'booking_id' => $bookingId,
            'description' => 'Cộng điểm từ lịch đặt đã hoàn thành #' . $bookingId . '.',
            'earned_at' => $earnedAt,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function applyCompletionMetrics(int $userId, string $finalPrice, int $points): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE users
            SET
                monthly_spend = monthly_spend + :final_price,
                monthly_visits = monthly_visits + 1,
                point_balance = point_balance + :points,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
            SQL
        );
        $statement->execute([
            'final_price' => $finalPrice,
            'points' => $points,
            'user_id' => $userId,
        ]);
    }

    public function markBookingLoyaltyProcessed(int $bookingId, string $processedAt): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE bookings
            SET loyalty_processed_at = :processed_at, updated_at = CURRENT_TIMESTAMP
            WHERE id = :booking_id
              AND status = 'completed'
              AND loyalty_processed_at IS NULL
            SQL
        );
        $statement->execute(['processed_at' => $processedAt, 'booking_id' => $bookingId]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Lịch đặt đã được xử lý loyalty hoặc không còn hợp lệ.');
        }
    }

    /** @return array<string, mixed> */
    public function completionEventContext(int $bookingId, int $userId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                SHA2(CONCAT(users.id, ':', users.created_at, ':', users.phone), 256)
                    AS anonymous_user_key,
                vehicle_types.code AS vehicle_type_code,
                DATEDIFF(wash_slots.slot_date, DATE(bookings.created_at)) AS booking_lead_days,
                GROUP_CONCAT(services.code ORDER BY booking_items.id SEPARATOR ',') AS service_codes
            FROM bookings
            INNER JOIN users ON users.id = bookings.user_id
            INNER JOIN vehicles ON vehicles.id = bookings.vehicle_id
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            INNER JOIN booking_items ON booking_items.booking_id = bookings.id
            INNER JOIN services ON services.id = booking_items.service_id
            WHERE bookings.id = :booking_id AND bookings.user_id = :user_id
            GROUP BY bookings.id, users.id, vehicle_types.id, wash_slots.id
            SQL
        );
        $statement->execute(['booking_id' => $bookingId, 'user_id' => $userId]);
        $context = $statement->fetch();

        if (!is_array($context)) {
            throw new \RuntimeException('Không thể tải dữ liệu sự kiện hoàn thành lịch đặt.');
        }

        return $context;
    }

    /** @param array<string, mixed> $context */
    public function insertCompletionEvent(
        int $bookingId,
        array $context,
        string $tierCode,
        string $orderValue,
        string $monthlySpend,
        int $monthlyVisits,
        int $points
    ): void {
        $serviceCodes = array_values(array_filter(explode(',', (string) $context['service_codes'])));
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO research_event_logs (
                event_key, anonymous_user_key, event_type, event_time, tier_code,
                vehicle_type_code, service_code, booking_lead_days, order_value,
                monthly_spend_snapshot, monthly_visits_snapshot, points_earned,
                used_reward, used_promotion, data_source, metadata_json
            ) VALUES (
                :event_key, :anonymous_user_key, 'booking_completed', CURRENT_TIMESTAMP, :tier_code,
                :vehicle_type_code, :service_code, :booking_lead_days, :order_value,
                :monthly_spend, :monthly_visits, :points_earned,
                FALSE, FALSE, 'system', :metadata_json
            )
            SQL
        );
        $statement->execute([
            'event_key' => 'booking_completed:' . $bookingId,
            'anonymous_user_key' => $context['anonymous_user_key'],
            'tier_code' => $tierCode,
            'vehicle_type_code' => $context['vehicle_type_code'],
            'service_code' => count($serviceCodes) === 1 ? $serviceCodes[0] : null,
            'booking_lead_days' => $context['booking_lead_days'],
            'order_value' => $orderValue,
            'monthly_spend' => $monthlySpend,
            'monthly_visits' => $monthlyVisits,
            'points_earned' => $points,
            'metadata_json' => json_encode(
                ['service_codes' => $serviceCodes],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function customerSummary(int $userId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                users.id,
                users.full_name,
                users.point_balance,
                users.monthly_spend,
                users.monthly_visits,
                tiers.code AS tier_code,
                tiers.name AS tier_name,
                tiers.point_rate,
                COALESCE(SUM(
                    CASE
                        WHEN loyalty_transactions.type = 'earn'
                          AND loyalty_transactions.remaining_points > 0
                          AND loyalty_transactions.expires_at >= CURRENT_TIMESTAMP
                          AND loyalty_transactions.expires_at < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
                        THEN loyalty_transactions.remaining_points
                        ELSE 0
                    END
                ), 0) AS expiring_points_30_days
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            LEFT JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
            WHERE users.id = :user_id AND users.role = 'customer'
            GROUP BY users.id, tiers.id
            SQL
        );
        $statement->execute(['user_id' => $userId]);
        $summary = $statement->fetch();

        return is_array($summary) ? $summary : null;
    }

    /** @return list<array<string, mixed>> */
    public function transactionHistory(int $userId, ?int $limit = null): array
    {
        $sql = <<<'SQL'
            SELECT
                loyalty_transactions.id,
                loyalty_transactions.type,
                loyalty_transactions.points_delta,
                loyalty_transactions.remaining_points,
                loyalty_transactions.source_type,
                loyalty_transactions.source_id,
                loyalty_transactions.description,
                loyalty_transactions.earned_at,
                loyalty_transactions.expires_at,
                loyalty_transactions.created_at,
                creators.full_name AS created_by_name
            FROM loyalty_transactions
            LEFT JOIN users AS creators ON creators.id = loyalty_transactions.created_by
            WHERE loyalty_transactions.user_id = :user_id
            ORDER BY loyalty_transactions.created_at DESC, loyalty_transactions.id DESC
            SQL;

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $statement = $this->database->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function customersForAdjustment(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT users.id, users.full_name, users.phone, users.point_balance, tiers.name AS tier_name
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.role = 'customer' AND users.status = 'active'
            ORDER BY users.full_name, users.id
            SQL
        )->fetchAll();
    }

    public function sourceTransactionBelongsToUser(int $transactionId, int $userId): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM loyalty_transactions WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $transactionId, 'user_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    public function insertAdjustment(
        int $userId,
        int $adminId,
        int $points,
        string $reason,
        ?int $sourceTransactionId,
        int $sourceId
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                source_transaction_id, description, created_by
            ) VALUES (
                :user_id, 'adjust', :points_delta, NULL, 'admin_adjustment', :source_id,
                :source_transaction_id, :description, :created_by
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'points_delta' => $points,
            'source_id' => $sourceId,
            'source_transaction_id' => $sourceTransactionId,
            'description' => $reason,
            'created_by' => $adminId,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function updatePointBalance(int $userId, int $points): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE users
            SET point_balance = point_balance + :points, updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
            SQL
        );
        $statement->execute(['points' => $points, 'user_id' => $userId]);
    }

    public function insertAdjustmentAudit(
        int $adminId,
        int $userId,
        int $transactionId,
        int $beforeBalance,
        int $afterBalance,
        int $points,
        string $reason
    ): void {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO audit_logs (
                actor_user_id, action, target_type, target_id, before_json, after_json, reason
            ) VALUES (
                :actor_id, 'loyalty_adjusted', 'user', :user_id, :before_json, :after_json, :reason
            )
            SQL
        );
        $statement->execute([
            'actor_id' => $adminId,
            'user_id' => $userId,
            'before_json' => json_encode(['point_balance' => $beforeBalance], JSON_THROW_ON_ERROR),
            'after_json' => json_encode([
                'point_balance' => $afterBalance,
                'points_delta' => $points,
                'loyalty_transaction_id' => $transactionId,
            ], JSON_THROW_ON_ERROR),
            'reason' => $reason,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function reconciliationReport(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT
                users.id AS user_id,
                users.full_name,
                users.point_balance AS cached_balance,
                COALESCE(SUM(loyalty_transactions.points_delta), 0) AS ledger_balance
            FROM users
            LEFT JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
            WHERE users.role = 'customer'
            GROUP BY users.id
            ORDER BY users.id
            SQL
        )->fetchAll();
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

    public function inTransaction(): bool
    {
        return $this->database->inTransaction();
    }
}
