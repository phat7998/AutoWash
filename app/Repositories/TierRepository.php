<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final readonly class TierRepository
{
    private const LOCK_PREFIX = 'autowash:tier-review:';

    public function __construct(private PDO $database)
    {
    }

    public function acquireReviewLock(string $reviewPeriod): bool
    {
        $statement = $this->database->prepare('SELECT GET_LOCK(:lock_name, 0)');
        $statement->execute(['lock_name' => self::LOCK_PREFIX . $reviewPeriod]);

        return (int) $statement->fetchColumn() === 1;
    }

    public function releaseReviewLock(string $reviewPeriod): void
    {
        $statement = $this->database->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $statement->execute(['lock_name' => self::LOCK_PREFIX . $reviewPeriod]);
    }

    /** @return array<string, mixed>|null */
    public function run(string $reviewPeriod): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM monthly_review_runs WHERE review_period = :review_period LIMIT 1'
        );
        $statement->execute(['review_period' => $reviewPeriod]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function createRun(string $reviewPeriod, string $startedAt): int
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO monthly_review_runs (
                review_period, status, started_at, processed_users
            ) VALUES (:review_period, 'running', :started_at, 0)
            SQL
        );
        $statement->execute(['review_period' => $reviewPeriod, 'started_at' => $startedAt]);

        return (int) $this->database->lastInsertId();
    }

    public function resumeRun(string $reviewPeriod): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE monthly_review_runs
            SET status = 'running', completed_at = NULL, error_message = NULL
            WHERE review_period = :review_period
            SQL
        );
        $statement->execute(['review_period' => $reviewPeriod]);
    }

    /** @return list<array<string, mixed>> */
    public function activeTiers(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT id, code, name, rank_order, min_monthly_spend, min_monthly_visits
            FROM tiers
            WHERE is_active = TRUE
            ORDER BY rank_order DESC, id DESC
            SQL
        )->fetchAll();
    }

    /** @return list<int> */
    public function unprocessedCustomerIds(string $reviewPeriod): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT users.id
            FROM users
            LEFT JOIN tier_histories
                ON tier_histories.user_id = users.id
                AND tier_histories.review_period = :review_period
            WHERE users.role = 'customer' AND tier_histories.id IS NULL
            ORDER BY users.id
            SQL
        );
        $statement->execute(['review_period' => $reviewPeriod]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return array<string, mixed>|null */
    public function lockCustomer(int $userId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT users.id, users.current_tier_id, users.monthly_spend,
                users.monthly_visits, users.point_balance,
                tiers.code AS current_tier_code, tiers.name AS current_tier_name,
                tiers.rank_order AS current_tier_rank
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.id = :id AND users.role = 'customer'
            LIMIT 1
            FOR UPDATE
            SQL
        );
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function historyExists(int $userId, string $reviewPeriod): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM tier_histories WHERE user_id = :user_id '
            . 'AND review_period = :review_period LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'review_period' => $reviewPeriod]);

        return $statement->fetchColumn() !== false;
    }

    public function insertHistory(
        int $userId,
        int $oldTierId,
        int $newTierId,
        string $reviewPeriod,
        string $monthlySpend,
        int $monthlyVisits,
        string $reason
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO tier_histories (
                user_id, old_tier_id, new_tier_id, review_period,
                monthly_spend_snapshot, monthly_visits_snapshot, reason
            ) VALUES (
                :user_id, :old_tier_id, :new_tier_id, :review_period,
                :monthly_spend, :monthly_visits, :reason
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'old_tier_id' => $oldTierId,
            'new_tier_id' => $newTierId,
            'review_period' => $reviewPeriod,
            'monthly_spend' => $monthlySpend,
            'monthly_visits' => $monthlyVisits,
            'reason' => $reason,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function updateTierAndResetMetrics(int $userId, int $tierId): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE users
            SET current_tier_id = :tier_id,
                monthly_spend = 0,
                monthly_visits = 0,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id AND role = 'customer'
            SQL
        );
        $statement->execute(['tier_id' => $tierId, 'user_id' => $userId]);
    }

    public function historyCount(string $reviewPeriod): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM tier_histories WHERE review_period = :review_period'
        );
        $statement->execute(['review_period' => $reviewPeriod]);

        return (int) $statement->fetchColumn();
    }

    public function updateProgress(string $reviewPeriod, int $processedUsers): void
    {
        $statement = $this->database->prepare(
            'UPDATE monthly_review_runs SET processed_users = :processed_users '
            . 'WHERE review_period = :review_period'
        );
        $statement->execute([
            'processed_users' => $processedUsers,
            'review_period' => $reviewPeriod,
        ]);
    }

    public function completeRun(string $reviewPeriod, int $processedUsers, string $completedAt): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE monthly_review_runs
            SET status = 'completed', completed_at = :completed_at,
                processed_users = :processed_users, error_message = NULL
            WHERE review_period = :review_period
            SQL
        );
        $statement->execute([
            'completed_at' => $completedAt,
            'processed_users' => $processedUsers,
            'review_period' => $reviewPeriod,
        ]);
    }

    public function failRun(string $reviewPeriod, int $processedUsers, string $errorMessage): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE monthly_review_runs
            SET status = 'failed', processed_users = :processed_users,
                completed_at = NULL, error_message = :error_message
            WHERE review_period = :review_period
            SQL
        );
        $statement->execute([
            'processed_users' => $processedUsers,
            'error_message' => $errorMessage,
            'review_period' => $reviewPeriod,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recentRuns(int $limit = 24): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT * FROM monthly_review_runs
            ORDER BY review_period DESC, id DESC
            LIMIT :limit
            SQL
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function recentHistories(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT tier_histories.*, users.full_name,
                old_tier.name AS old_tier_name, new_tier.name AS new_tier_name
            FROM tier_histories
            INNER JOIN users ON users.id = tier_histories.user_id
            INNER JOIN tiers AS old_tier ON old_tier.id = tier_histories.old_tier_id
            INNER JOIN tiers AS new_tier ON new_tier.id = tier_histories.new_tier_id
            ORDER BY tier_histories.review_period DESC, tier_histories.id DESC
            LIMIT :limit
            SQL
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
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
}
