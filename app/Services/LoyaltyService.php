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
        $expiresAt = $this->expirationAt($earnedAt);
        $this->transactions->insertEarn(
            $userId,
            $bookingId,
            $points,
            $earnedAt->format('Y-m-d H:i:s'),
            $expiresAt->format('Y-m-d H:i:s')
        );
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
            $points
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

            $transactionId = $this->transactions->insertAdjustment(
                $input['user_id'],
                $adminId,
                $input['points'],
                $input['reason'],
                $input['source_transaction_id'],
                random_int(1, PHP_INT_MAX)
            );
            $this->transactions->updatePointBalance($input['user_id'], $input['points']);
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
            $row['matches'] = $row['cached_balance'] === $row['ledger_balance'];

            return $row;
        }, $this->transactions->reconciliationReport());
    }

    private function expirationAt(DateTimeImmutable $earnedAt): DateTimeImmutable
    {
        $targetYear = (int) $earnedAt->format('Y') + 1;
        $targetMonth = (int) $earnedAt->format('n');
        $lastDay = (int) (new DateTimeImmutable(
            sprintf('%04d-%02d-01', $targetYear, $targetMonth),
            $this->timezone
        ))->format('t');
        $targetDay = min(
            (int) $earnedAt->format('j'),
            $lastDay
        );

        return $earnedAt->setDate($targetYear, $targetMonth, $targetDay);
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
                'adjust' => 'Điều chỉnh',
                default => 'Giao dịch',
            };
            $transaction['is_credit'] = (int) $transaction['points_delta'] >= 0;

            return $transaction;
        }, $transactions);
    }
}
