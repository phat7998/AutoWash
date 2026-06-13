<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Customer;
use common\models\PointTransaction;
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
}
