<?php

namespace common\services;

use common\models\Booking;
use common\models\LoyaltyAccount;
use common\models\PointTransaction;
use common\models\TierRule;
use Yii;

class LoyaltyService
{
    public const POINTS_PER_VND = 10000;
    public const REDEEM_STEP = 10;

    public static function bookingWindow(string $tier): int
    {
        $windows = Yii::$app->params['bookingWindow'] ?? [];

        return (int) ($windows[strtoupper($tier)] ?? $windows['MEMBER'] ?? 7);
    }

    public static function canRedeem(LoyaltyAccount $account, int $points): bool
    {
        return $points > 0
            && $points % self::REDEEM_STEP === 0
            && (int) $account->point_balance >= $points;
    }

    public static function calculateEarnedPoints(float $serviceAmount): int
    {
        return max(0, (int) floor($serviceAmount / self::POINTS_PER_VND));
    }

    public static function resolveTier(float $spend, int $visits): ?TierRule
    {
        $tiers = TierRule::find()->orderBy(['priority_order' => SORT_DESC])->all();
        foreach ($tiers as $tier) {
            if ($spend >= (float) $tier->minimum_spend && $visits >= (int) $tier->minimum_visits) {
                return $tier;
            }
        }

        return TierRule::findOne(['code' => 'MEMBER']);
    }

    public static function reviewTier(LoyaltyAccount $account): ?TierRule
    {
        $tier = self::resolveTier((float) $account->lifetime_spend, (int) $account->wash_count);
        if ($tier && (int) $account->tier_rule_id !== (int) $tier->id) {
            $account->tier_rule_id = $tier->id;
        }
        $account->reviewed_at = time();
        $account->updated_at = time();
        $account->save(false);

        return $tier;
    }

    public static function earnForCompletedBooking(Booking $booking): int
    {
        $account = LoyaltyAccount::findOne(['customer_id' => $booking->customer_id]);
        if (!$account) {
            return 0;
        }

        $points = self::calculateEarnedPoints((float) $booking->service_amount);
        if ($points <= 0) {
            return 0;
        }

        $account->point_balance += $points;
        $account->lifetime_spend += (float) $booking->service_amount;
        $account->wash_count += 1;
        $account->updated_at = time();
        $account->save(false);

        $transaction = new PointTransaction();
        $transaction->loyalty_account_id = $account->id;
        $transaction->transaction_type = 'EARN';
        $transaction->points = $points;
        $transaction->available_points = $points;
        $transaction->reference_id = $booking->id;
        $transaction->description = 'Tich diem tu booking ' . $booking->booking_code;
        $transaction->expired_at = strtotime('+12 months');
        $transaction->created_at = time();
        $transaction->save(false);

        self::reviewTier($account);

        return $points;
    }

    public static function redeem(LoyaltyAccount $account, int $points, string $description = 'Doi diem loyalty'): bool
    {
        if (!self::canRedeem($account, $points)) {
            return false;
        }

        $account->point_balance -= $points;
        $account->updated_at = time();
        $account->save(false);

        $transaction = new PointTransaction();
        $transaction->loyalty_account_id = $account->id;
        $transaction->transaction_type = 'REDEEM';
        $transaction->points = -$points;
        $transaction->available_points = 0;
        $transaction->description = $description;
        $transaction->created_at = time();
        $transaction->save(false);

        self::consumeEarnedPoints($account->id, $points);

        return true;
    }

    public static function nextTierProgress(LoyaltyAccount $account): array
    {
        $currentTier = $account->tierRule ?: TierRule::findOne(['code' => 'MEMBER']);
        $nextTier = null;

        if ($currentTier) {
            $nextTier = TierRule::find()
                ->where(['>', 'priority_order', (int) $currentTier->priority_order])
                ->orderBy(['priority_order' => SORT_ASC])
                ->one();
        }

        if (!$nextTier) {
            return [
                'current_tier' => $currentTier,
                'next_tier' => null,
                'spend_needed' => 0,
                'visits_needed' => 0,
                'progress_percent' => 100,
            ];
        }

        $spendNeeded = max(0, (float) $nextTier->minimum_spend - (float) $account->lifetime_spend);
        $visitsNeeded = max(0, (int) $nextTier->minimum_visits - (int) $account->wash_count);

        $currentSpendBase = $currentTier ? (float) $currentTier->minimum_spend : 0;
        $currentVisitsBase = $currentTier ? (int) $currentTier->minimum_visits : 0;
        $spendRange = max(1, (float) $nextTier->minimum_spend - $currentSpendBase);
        $visitRange = max(1, (int) $nextTier->minimum_visits - $currentVisitsBase);

        $spendProgress = ((float) $account->lifetime_spend - $currentSpendBase) / $spendRange;
        $visitProgress = ((int) $account->wash_count - $currentVisitsBase) / $visitRange;
        $progress = max(0, min(100, (int) round(min($spendProgress, $visitProgress) * 100)));

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'spend_needed' => $spendNeeded,
            'visits_needed' => $visitsNeeded,
            'progress_percent' => $progress,
        ];
    }

    private static function consumeEarnedPoints(int $loyaltyAccountId, int $points): void
    {
        $remaining = $points;
        $earned = PointTransaction::find()
            ->where([
                'loyalty_account_id' => $loyaltyAccountId,
                'transaction_type' => 'EARN',
            ])
            ->andWhere(['>', 'available_points', 0])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        foreach ($earned as $transaction) {
            if ($remaining <= 0) {
                break;
            }
            $used = min($remaining, (int) $transaction->available_points);
            $transaction->available_points -= $used;
            $transaction->save(false);
            $remaining -= $used;
        }
    }
}
