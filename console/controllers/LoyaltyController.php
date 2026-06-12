<?php

namespace console\controllers;

use yii\console\Controller;
use common\models\LoyaltyAccount;
use common\models\TierRule;
use common\models\PointTransaction;
use Yii;

class LoyaltyController extends Controller
{
    /**
     * Review tiers to auto upgrade or downgrade
     */
    public function actionReviewTiers()
    {
        echo "Starting tier review...\n";
        
        $tiers = TierRule::find()->orderBy(['priority_order' => SORT_DESC])->all();
        $accounts = LoyaltyAccount::find()->all();
        
        $updated = 0;
        foreach ($accounts as $account) {
            $newTierId = null;
            foreach ($tiers as $tier) {
                if ($account->lifetime_spend >= $tier->minimum_spend && $account->wash_count >= $tier->minimum_visits) {
                    $newTierId = $tier->id;
                    break;
                }
            }
            
            if ($newTierId && $account->tier_rule_id !== $newTierId) {
                $account->tier_rule_id = $newTierId;
                $account->reviewed_at = time();
                $account->save(false);
                $updated++;
                echo "Customer {$account->customer_id} upgraded/downgraded to Tier ID {$newTierId}\n";
            }
        }
        
        echo "Tier review finished. {$updated} accounts updated.\n";
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
    }
}
