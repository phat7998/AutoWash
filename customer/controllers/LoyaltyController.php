<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Customer;
use common\models\PointTransaction;
use common\models\TierRule;
use common\services\LoyaltyService;
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
        $progress = LoyaltyService::nextTierProgress($loyalty);
        $tier = $progress['current_tier'];
        $nextTier = $progress['next_tier'];

        return [
            'data' => [
                'point_balance' => $loyalty->point_balance,
                'lifetime_spend' => $loyalty->lifetime_spend,
                'wash_count' => $loyalty->wash_count,
                'tier' => $tier ? $tier->name : 'Member',
                'tier_code' => $tier ? $tier->code : 'MEMBER',
                'next_tier' => $nextTier ? $nextTier->name : null,
                'spend_needed' => $progress['spend_needed'],
                'visits_needed' => $progress['visits_needed'],
                'next_tier_progress' => $progress['progress_percent'],
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
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }
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
        return $this->actionNextTier();
    }

    public function actionTiers(): array
    {
        $tiers = TierRule::find()->orderBy(['priority_order' => SORT_ASC])->all();

        return [
            'data' => array_map(static function (TierRule $tier): array {
                return [
                    'code' => $tier->code,
                    'name' => $tier->name,
                    'minimum_spend' => (float) $tier->minimum_spend,
                    'minimum_visits' => (int) $tier->minimum_visits,
                    'booking_window_days' => (int) $tier->booking_window_days,
                    'priority_order' => (int) $tier->priority_order,
                ];
            }, $tiers),
        ];
    }

    public function actionNextTier(): array
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
        $progress = LoyaltyService::nextTierProgress($loyalty);
        $currentTier = $progress['current_tier'];
        $nextTier = $progress['next_tier'];

        return [
            'data' => [
                'tier' => $currentTier ? $currentTier->name : 'Member',
                'tier_code' => $currentTier ? $currentTier->code : 'MEMBER',
                'point_balance' => $loyalty->point_balance,
                'lifetime_spend' => $loyalty->lifetime_spend,
                'wash_count' => $loyalty->wash_count,
                'next_tier' => $nextTier ? $nextTier->name : null,
                'next_tier_code' => $nextTier ? $nextTier->code : null,
                'spend_to_next' => $progress['spend_needed'],
                'visits_to_next' => $progress['visits_needed'],
                'points_to_next' => $progress['spend_needed'],
                'progress_percent' => $progress['progress_percent'],
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

    public function actionRedeem(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount')->one();
        if (!$customer || !$customer->loyaltyAccount) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy tài khoản loyalty.'];
        }

        $points = (int) Yii::$app->request->post('points', 0);
        if (!LoyaltyService::canRedeem($customer->loyaltyAccount, $points)) {
            Yii::$app->response->statusCode = 400;
            return ['message' => 'Điểm đổi thưởng không hợp lệ hoặc không đủ số dư. Điểm đổi phải là bội số của 10.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            LoyaltyService::redeem($customer->loyaltyAccount, $points, 'Doi diem lay uu dai');
            $transaction->commit();

            return [
                'message' => 'Đổi điểm thành công',
                'data' => [
                    'redeemed_points' => $points,
                    'point_balance' => $customer->loyaltyAccount->point_balance,
                    'estimated_discount_vnd' => $points * 1000,
                ],
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return ['message' => 'Đổi điểm thất bại: ' . $e->getMessage()];
        }
    }
}
