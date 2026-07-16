<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MonthlyReviewAlreadyCompletedException;
use App\Exceptions\MonthlyReviewBusyException;
use App\Repositories\TierRepository;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

final readonly class TierReviewService
{
    public function __construct(
        private TierRepository $tiers,
        private TierReviewPolicy $policy,
        private DateTimeZone $timezone
    ) {
    }

    /** @return array{review_period: string, processed_users: int, status: string} */
    public function run(?string $reviewPeriod = null, ?DateTimeImmutable $at = null): array
    {
        $reviewPeriod ??= $this->policy->previousPeriod($at);

        if (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $reviewPeriod) !== 1) {
            throw new InvalidArgumentException('Kỳ xét hạng phải có định dạng YYYY-MM.');
        }

        if (!$this->tiers->acquireReviewLock($reviewPeriod)) {
            throw new MonthlyReviewBusyException($reviewPeriod);
        }

        $currentUserId = null;

        try {
            $now = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);
            $existingRun = $this->tiers->run($reviewPeriod);

            if (($existingRun['status'] ?? null) === 'completed') {
                throw new MonthlyReviewAlreadyCompletedException($reviewPeriod);
            }

            if ($existingRun === null) {
                $this->tiers->createRun($reviewPeriod, $now->format('Y-m-d H:i:s'));
            } else {
                $this->tiers->resumeRun($reviewPeriod);
            }

            $activeTiers = $this->tiers->activeTiers();

            if ($activeTiers === []) {
                throw new InvalidArgumentException('Không có hạng thành viên hoạt động để xét.');
            }

            foreach ($this->tiers->unprocessedCustomerIds($reviewPeriod) as $userId) {
                $currentUserId = $userId;
                $this->reviewCustomer($userId, $reviewPeriod, $activeTiers);
                $this->tiers->updateProgress($reviewPeriod, $this->tiers->historyCount($reviewPeriod));
            }

            $currentUserId = null;
            $processedUsers = $this->tiers->historyCount($reviewPeriod);
            $completedAt = $at ?? new DateTimeImmutable('now', $this->timezone);
            $this->tiers->completeRun(
                $reviewPeriod,
                $processedUsers,
                $completedAt->setTimezone($this->timezone)->format('Y-m-d H:i:s')
            );

            return [
                'review_period' => $reviewPeriod,
                'processed_users' => $processedUsers,
                'status' => 'completed',
            ];
        } catch (MonthlyReviewAlreadyCompletedException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            $processedUsers = $this->tiers->historyCount($reviewPeriod);
            $message = $currentUserId === null
                ? 'Kỳ xét hạng thất bại trước khi xử lý khách hàng.'
                : 'Kỳ xét hạng thất bại tại khách hàng #' . $currentUserId . '.';
            $this->tiers->failRun($reviewPeriod, $processedUsers, $message);

            throw $throwable;
        } finally {
            $this->tiers->releaseReviewLock($reviewPeriod);
        }
    }

    /** @return array{runs: list<array<string, mixed>>, histories: list<array<string, mixed>>} */
    public function adminResults(): array
    {
        return [
            'runs' => $this->tiers->recentRuns(),
            'histories' => $this->tiers->recentHistories(),
        ];
    }

    /** @param list<array<string, mixed>> $activeTiers */
    private function reviewCustomer(int $userId, string $reviewPeriod, array $activeTiers): void
    {
        $this->tiers->transactional(function () use ($userId, $reviewPeriod, $activeTiers): void {
            $customer = $this->tiers->lockCustomer($userId);

            if ($customer === null || $this->tiers->historyExists($userId, $reviewPeriod)) {
                return;
            }

            $qualifiedTier = $this->policy->qualifiedTier(
                (string) $customer['monthly_spend'],
                (int) $customer['monthly_visits'],
                $activeTiers
            );
            $reason = $this->reason($customer, $qualifiedTier);
            $this->tiers->insertHistory(
                $userId,
                (int) $customer['current_tier_id'],
                (int) $qualifiedTier['id'],
                $reviewPeriod,
                (string) $customer['monthly_spend'],
                (int) $customer['monthly_visits'],
                $reason
            );
            $this->tiers->updateTierAndResetMetrics($userId, (int) $qualifiedTier['id']);
        });
    }

    /** @param array<string, mixed> $customer @param array<string, mixed> $qualifiedTier */
    private function reason(array $customer, array $qualifiedTier): string
    {
        $oldRank = (int) $customer['current_tier_rank'];
        $newRank = (int) $qualifiedTier['rank_order'];
        $transition = match (true) {
            $newRank > $oldRank => 'Nâng hạng',
            $newRank < $oldRank => 'Hạ hạng',
            default => 'Giữ hạng',
        };

        return sprintf(
            '%s từ %s sang %s theo chi tiêu %s VND và %d lượt hoàn thành.',
            $transition,
            (string) $customer['current_tier_name'],
            (string) $qualifiedTier['name'],
            (string) $customer['monthly_spend'],
            (int) $customer['monthly_visits']
        );
    }
}
