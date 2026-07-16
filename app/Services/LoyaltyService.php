<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BookingCompletionProcessorInterface;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\ValidationException;
use App\Repositories\LoyaltyTransactionRepository;
use App\Validation\LoyaltyAdjustmentValidator;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final readonly class LoyaltyService implements BookingCompletionProcessorInterface
{
    public function __construct(
        private LoyaltyTransactionRepository $transactions,
        private LoyaltyPointCalculator $calculator,
        private LoyaltyAdjustmentValidator $adjustmentValidator,
        private LoyaltyDebitAllocator $allocator,
        private LoyaltyExpirationPolicy $expirationPolicy,
        private DateTimeZone $timezone
    ) {
    }

    /** @param array<string, mixed> $lockedBooking */
    public function process(array $lockedBooking): void
    {
        if (!$this->transactions->inTransaction()) {
            throw new RuntimeException('Loyalty completion phải chạy trong transaction của booking.');
        }

        $bookingId = (int) ($lockedBooking['id'] ?? 0);
        $userId = (int) ($lockedBooking['user_id'] ?? 0);

        if ($bookingId <= 0 || $userId <= 0 || $lockedBooking['loyalty_processed_at'] !== null) {
            throw new RuntimeException('Lịch đặt không hợp lệ để xử lý loyalty.');
        }

        $user = $this->transactions->lockCustomerContext($userId);

        if ($user === null || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            throw new RuntimeException('Khách hàng không hợp lệ để xử lý loyalty.');
        }

        $finalPrice = (string) $lockedBooking['final_price'];
        $points = $this->calculator->earnedPoints($finalPrice, (string) $user['point_rate']);
        $earnedAt = new DateTimeImmutable('now', $this->timezone);
        $expiresAt = $this->expirationPolicy->expiresAt($earnedAt);
        if ($points > 0) {
            $this->transactions->insertEarn(
                $userId,
                $bookingId,
                $points,
                $earnedAt->format('Y-m-d H:i:s'),
                $expiresAt->format('Y-m-d H:i:s')
            );
        }
        $this->transactions->applyCompletionMetrics($userId, $finalPrice, $points);
        $this->transactions->markBookingLoyaltyProcessed(
            $bookingId,
            $earnedAt->format('Y-m-d H:i:s')
        );
        $eventContext = $this->transactions->completionEventContext($bookingId, $userId);
        $monthlySpend = $this->addMoney((string) $user['monthly_spend'], $finalPrice);
        $this->transactions->insertCompletionEvent(
            $bookingId,
            $eventContext,
            (string) $user['tier_code'],
            $finalPrice,
            $monthlySpend,
            (int) $user['monthly_visits'] + 1,
            $points,
            (bool) $eventContext['used_reward'],
            (bool) $eventContext['used_promotion']
        );
    }

    /** @return array{summary: array<string, mixed>, recent_transactions: list<array<string, mixed>>} */
    public function dashboard(int $userId): array
    {
        return [
            'summary' => $this->requiredSummary($userId),
            'recent_transactions' => $this->decorateTransactions(
                $this->transactions->transactionHistory($userId, 5)
            ),
        ];
    }

    /** @return array{summary: array<string, mixed>, transactions: list<array<string, mixed>>} */
    public function history(int $userId): array
    {
        return [
            'summary' => $this->requiredSummary($userId),
            'transactions' => $this->decorateTransactions(
                $this->transactions->transactionHistory($userId)
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function adjustmentCustomers(): array
    {
        return $this->transactions->customersForAdjustment();
    }

    public function adjust(
        int $adminId,
        string $userId,
        string $points,
        string $reason,
        string $sourceTransactionId = ''
    ): int {
        $input = $this->adjustmentValidator->validate(
            $userId,
            $points,
            $reason,
            $sourceTransactionId
        );

        return $this->transactions->transactional(function () use ($adminId, $input): int {
            if (!$this->transactions->isActiveAdmin($adminId)) {
                throw new ValidationException(['admin' => 'Tài khoản quản trị không hợp lệ.']);
            }

            $user = $this->transactions->lockCustomerContext($input['user_id']);

            if ($user === null || $user['role'] !== 'customer' || $user['status'] !== 'active') {
                throw new ValidationException([
                    'user_id' => 'Khách hàng không tồn tại hoặc đã bị vô hiệu.',
                ]);
            }

            if (
                $input['source_transaction_id'] !== null
                && !$this->transactions->sourceTransactionBelongsToUser(
                    $input['source_transaction_id'],
                    $input['user_id']
                )
            ) {
                throw new ValidationException([
                    'source_transaction_id' => 'Giao dịch nguồn không thuộc khách hàng đã chọn.',
                ]);
            }

            $beforeBalance = (int) $user['point_balance'];
            $afterBalance = $beforeBalance + $input['points'];

            if ($afterBalance < 0) {
                throw new InsufficientPointsException();
            }

            $sourceId = random_int(1, PHP_INT_MAX);

            if ($input['points'] > 0) {
                $transactionId = $this->transactions->insertAdjustmentCredit(
                    $input['user_id'],
                    $adminId,
                    $input['points'],
                    $input['reason'],
                    $input['source_transaction_id'],
                    $sourceId
                );
                $this->transactions->updatePointBalance($input['user_id'], $input['points']);
            } else {
                $transactionId = $this->createDebitForLockedUser(
                    $input['user_id'],
                    'adjust_debit',
                    abs($input['points']),
                    'admin_adjustment',
                    $sourceId,
                    $input['reason'],
                    $adminId,
                    $input['source_transaction_id'],
                    new DateTimeImmutable('now', $this->timezone)
                );
            }
            $this->transactions->insertAdjustmentAudit(
                $adminId,
                $input['user_id'],
                $transactionId,
                $beforeBalance,
                $afterBalance,
                $input['points'],
                $input['reason']
            );

            return $transactionId;
        });
    }

    /** @return list<array<string, mixed>> */
    public function reconciliationReport(): array
    {
        return array_map(static function (array $row): array {
            $row['cached_balance'] = (int) $row['cached_balance'];
            $row['ledger_balance'] = (int) $row['ledger_balance'];
            $row['credit_lot_balance'] = (int) $row['credit_lot_balance'];
            $row['matches'] = $row['cached_balance'] === $row['ledger_balance']
                && $row['cached_balance'] === $row['credit_lot_balance'];

            return $row;
        }, $this->transactions->reconciliationReport());
    }

    /** @return list<array{debit_transaction_id: int, debit_points: int, allocated_points: int, matches: bool}> */
    public function debitAllocationReport(): array
    {
        return array_map(static function (array $row): array {
            $row['matches'] = $row['debit_points'] === $row['allocated_points'];

            return $row;
        }, $this->transactions->debitAllocationReport());
    }

    public function debitInCurrentTransaction(
        int $userId,
        string $type,
        int $points,
        string $sourceType,
        int $sourceId,
        string $description,
        ?int $createdBy = null,
        ?int $sourceTransactionId = null,
        ?DateTimeImmutable $at = null
    ): int {
        if (!$this->transactions->inTransaction()) {
            throw new RuntimeException('Debit loyalty phải chạy trong transaction nghiệp vụ hiện tại.');
        }

        $user = $this->transactions->lockCustomerContext($userId);
        if ($user === null || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            throw new ValidationException(['user' => 'Khách hàng không tồn tại hoặc đã bị vô hiệu.']);
        }

        if ((int) $user['point_balance'] < $points) {
            throw new InsufficientPointsException();
        }

        return $this->createDebitForLockedUser(
            $userId,
            $type,
            $points,
            $sourceType,
            $sourceId,
            $description,
            $createdBy,
            $sourceTransactionId,
            $at ?? new DateTimeImmutable('now', $this->timezone)
        );
    }

    /** @return array<string, mixed> */
    public function customerForMutationInCurrentTransaction(int $userId): array
    {
        if (!$this->transactions->inTransaction()) {
            throw new RuntimeException('Customer loyalty phải được khóa trong transaction hiện tại.');
        }

        $user = $this->transactions->lockCustomerContext($userId);

        if ($user === null || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            throw new ValidationException(['user' => 'Khách hàng không tồn tại hoặc đã bị vô hiệu.']);
        }

        return $user;
    }

    /** @return array{expired_lots: int, expired_points: int} */
    public function expirePoints(?DateTimeImmutable $at = null): array
    {
        $at = ($at ?? new DateTimeImmutable('now', $this->timezone))->setTimezone($this->timezone);
        $formattedAt = $at->format('Y-m-d H:i:s');
        $expiredLots = 0;
        $expiredPoints = 0;

        foreach ($this->transactions->expiredCreditLotCandidates($formattedAt) as $candidate) {
            $points = $this->transactions->transactional(function () use ($candidate, $formattedAt): int {
                $user = $this->transactions->lockCustomerContext($candidate['user_id']);

                if ($user === null || $user['role'] !== 'customer') {
                    throw new RuntimeException('Credit lot hết hạn không thuộc customer hợp lệ.');
                }

                $lot = $this->transactions->lockExpiredCreditLot(
                    $candidate['id'],
                    $candidate['user_id'],
                    $formattedAt
                );

                if ($lot === null) {
                    return 0;
                }

                $points = $lot['remaining_points'];

                if ((int) $user['point_balance'] < $points) {
                    throw new RuntimeException(
                        'Số dư cache không đủ để expire credit lot #' . $candidate['id'] . '.'
                    );
                }

                $debitId = $this->transactions->insertDebit(
                    $candidate['user_id'],
                    'expire',
                    $points,
                    'credit_lot',
                    $candidate['id'],
                    'Hết hạn điểm từ credit lot #' . $candidate['id'] . '.',
                    null,
                    null,
                    $formattedAt
                );
                $this->transactions->allocateDebit(
                    $debitId,
                    $candidate['id'],
                    $points,
                    $formattedAt
                );
                $this->transactions->updatePointBalance($candidate['user_id'], -$points);

                return $points;
            });

            if ($points > 0) {
                $expiredLots++;
                $expiredPoints += $points;
            }
        }

        return ['expired_lots' => $expiredLots, 'expired_points' => $expiredPoints];
    }

    private function createDebitForLockedUser(
        int $userId,
        string $type,
        int $points,
        string $sourceType,
        int $sourceId,
        string $description,
        ?int $createdBy,
        ?int $sourceTransactionId,
        DateTimeImmutable $at
    ): int {
        $formattedAt = $at->setTimezone($this->timezone)->format('Y-m-d H:i:s');
        $lots = $this->transactions->lockAvailableCreditLots($userId, $formattedAt);
        $allocations = $this->allocator->allocate($points, $lots);
        $debitId = $this->transactions->insertDebit(
            $userId,
            $type,
            $points,
            $sourceType,
            $sourceId,
            $description,
            $createdBy,
            $sourceTransactionId,
            $formattedAt
        );

        foreach ($allocations as $allocation) {
            $this->transactions->allocateDebit(
                $debitId,
                $allocation['credit_transaction_id'],
                $allocation['allocated_points'],
                $formattedAt
            );
        }

        $this->transactions->updatePointBalance($userId, -$points);

        return $debitId;
    }

    private function addMoney(string $left, string $right): string
    {
        $sum = $this->moneyMinorUnits($left) + $this->moneyMinorUnits($right);

        return sprintf('%d.%02d', intdiv($sum, 100), $sum % 100);
    }

    private function moneyMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /** @return array<string, mixed> */
    private function requiredSummary(int $userId): array
    {
        $summary = $this->transactions->customerSummary($userId);

        if ($summary === null) {
            throw new ValidationException(['user' => 'Không tìm thấy tài khoản khách hàng.']);
        }

        return $summary;
    }

    /**
     * @param list<array<string, mixed>> $transactions
     * @return list<array<string, mixed>>
     */
    private function decorateTransactions(array $transactions): array
    {
        return array_map(static function (array $transaction): array {
            $transaction['type_label'] = match ($transaction['type']) {
                'earn' => 'Cộng điểm',
                'redeem' => 'Đổi thưởng',
                'expire' => 'Hết hạn',
                'adjust_credit' => 'Điều chỉnh tăng',
                'adjust_debit' => 'Điều chỉnh giảm',
                default => 'Giao dịch',
            };
            $transaction['is_credit'] = (int) $transaction['points_delta'] >= 0;

            return $transaction;
        }, $transactions);
    }
}
