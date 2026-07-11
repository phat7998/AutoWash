<?php

namespace console\controllers;

use yii\console\Controller;
use common\models\LoyaltyAccount;
use common\models\PointTransaction;
use common\services\LoyaltyService;
use Yii;
use yii\console\ExitCode;

class LoyaltyController extends Controller
{
    /**
     * Review tiers to auto upgrade or downgrade
     */
    public function actionReviewTiers()
    {
        echo "Starting tier review...\n";
        
        $accounts = LoyaltyAccount::find()->with('tierRule')->all();
        
        $updated = 0;
        foreach ($accounts as $account) {
            $oldTier = $account->tierRule ? $account->tierRule->code : 'NONE';
            $newTier = LoyaltyService::reviewTier($account);
            $newTierCode = $newTier ? $newTier->code : 'NONE';

            if ($oldTier !== $newTierCode) {
                $updated++;
                echo "Customer {$account->customer_id}: {$oldTier} -> {$newTierCode}\n";
            }
        }
        
        echo "Tier review finished. {$updated} accounts updated.\n";
        return ExitCode::OK;
    }

    /**
     * Expire points older than 12 months
     */
    public function actionExpirePoints()
    {
        echo "Starting point expiration...\n";
        
        $now = time();
        $transactions = PointTransaction::find()
            ->where(['transaction_type' => 'EARN'])
            ->andWhere(['>', 'available_points', 0])
            ->andWhere(['<', 'expired_at', $now])
            ->all();
            
        $expiredCount = 0;
        foreach ($transactions as $txn) {
            $loyalty = LoyaltyAccount::findOne($txn->loyalty_account_id);
            if ($loyalty && $loyalty->point_balance > 0) {
                $pointsToExpire = min($txn->available_points, $loyalty->point_balance);
                
                // Add expire transaction
                $pt = new PointTransaction();
                $pt->loyalty_account_id = $loyalty->id;
                $pt->transaction_type = 'EXPIRE';
                $pt->points = -$pointsToExpire;
                $pt->description = 'Điểm hết hạn';
                $pt->created_at = time();
                $pt->save(false);
                
                // Deduct from account
                $loyalty->point_balance -= $pointsToExpire;
                $loyalty->save(false);
                
                // Update transaction available points
                $txn->available_points = 0;
                $txn->save(false);
                
                $expiredCount++;
            }
        }
        
        echo "Finished expiration. Processed {$expiredCount} transactions.\n";
        return ExitCode::OK;
    }
}
