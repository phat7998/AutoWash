<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Customer;
use common\models\PointTransaction;
use common\models\TierRule;
use Yii;

class LoyaltyController extends RestController
{
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount.tierRule')->one();
        if (!$customer || !$customer->loyaltyAccount) {
            return ['data' => []];
        }

        $loyalty = $customer->loyaltyAccount;

        return [
            'data' => [
                'point_balance' => $loyalty->point_balance,
                'lifetime_spend' => $loyalty->lifetime_spend,
                'wash_count' => $loyalty->wash_count,
                'tier' => $loyalty->tierRule ? $loyalty->tierRule->name : 'Member',
                'next_tier_progress' => 'Chưa triển khai logic tính toán next tier progress' // optional frontend UI helper
            ]
        ];
    }

    public function actionBalance(): array
    {
        return $this->actionIndex();
    }

    public function actionHistory(): array
    {
        $user = Yii::$app->user->identity;
        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount')->one();
        
        if (!$customer || !$customer->loyaltyAccount) {
            return ['data' => []];
        }

        $transactions = PointTransaction::find()
            ->where(['loyalty_account_id' => $customer->loyaltyAccount->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return [
            'data' => array_map(function($t) {
                return [
                    'id' => $t->id,
                    'transaction_type' => $t->transaction_type,
                    'points' => $t->points,
                    'description' => $t->description,
                    'created_at' => $t->created_at,
                ];
            }, $transactions)
        ];
    }

    public function actionTier(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount.tierRule')->one();
        if (!$customer || !$customer->loyaltyAccount) {
            return ['data' => []];
        }

        $loyalty = $customer->loyaltyAccount;
        $currentTier = $loyalty->tierRule;

        // Get all tiers ordered by priority
        $allTiers = TierRule::find()->orderBy(['priority_order' => SORT_ASC])->all();

        $currentTierName = $currentTier ? $currentTier->name : 'Member';
        $nextTier = null;
        $pointsToNext = null;
        $progress = null;

        // Find next tier after current one
        $found = false;
        foreach ($allTiers as $tier) {
            if ($found) {
                $nextTier = $tier;
                break;
            }
            if ($currentTier && $tier->id === $currentTier->id) {
                $found = true;
            }
        }

        if ($nextTier) {
            $currentPoints = (float) $loyalty->lifetime_spend;
            $nextThreshold = (float) $nextTier->minimum_spend;
            $currentThreshold = (float) ($currentTier ? $currentTier->minimum_spend : 0);
            $pointsToNext = max(0, $nextThreshold - $currentPoints);
            $range = $nextThreshold - $currentThreshold;
            $progress = $range > 0 ? min(100, round(($currentPoints - $currentThreshold) / $range * 100)) : 100;
        }

        return [
            'data' => [
                'tier' => $currentTierName,
                'tier_code' => $currentTier ? $currentTier->code : 'MEMBER',
                'point_balance' => $loyalty->point_balance,
                'lifetime_spend' => $loyalty->lifetime_spend,
                'wash_count' => $loyalty->wash_count,
                'next_tier' => $nextTier ? $nextTier->name : null,
                'next_tier_code' => $nextTier ? $nextTier->code : null,
                'points_to_next' => $pointsToNext,
                'progress_percent' => $progress,
                'booking_window_days' => $currentTier ? (int) $currentTier->booking_window_days : 7,
            ]
        ];
    }

    public function actionAdvanceDays(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount.tierRule')->one();
        if (!$customer || !$customer->loyaltyAccount) {
            return ['data' => ['advance_days' => 7]];
        }

        $loyalty = $customer->loyaltyAccount;
        $days = $loyalty->tierRule ? $loyalty->tierRule->booking_window_days : 7;

        return [
            'data' => [
                'advance_days' => (int) $days,
                'tier' => $loyalty->tierRule ? $loyalty->tierRule->name : 'Member',
            ]
        ];
    }
}
