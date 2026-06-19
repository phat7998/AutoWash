<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $tier_rule_id
 * @property int $point_balance
 * @property float $lifetime_spend
 * @property int $wash_count
 * @property int|null $reviewed_at
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Customer $customer
 * @property TierRule $tierRule
 * @property PointTransaction[] $pointTransactions
 */
class LoyaltyAccount extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%loyalty_account}}';
    }

    public function rules(): array
    {
        return [
            [['customer_id'], 'required'],
            [['customer_id', 'tier_rule_id', 'point_balance', 'wash_count', 'reviewed_at', 'created_at', 'updated_at'], 'integer'],
            [['lifetime_spend'], 'number'],
            [['customer_id'], 'unique'],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['tier_rule_id'], 'exist', 'skipOnError' => true, 'targetClass' => TierRule::class, 'targetAttribute' => ['tier_rule_id' => 'id']],
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getTierRule()
    {
        return $this->hasOne(TierRule::class, ['id' => 'tier_rule_id']);
    }

    public function getPointTransactions()
    {
        return $this->hasMany(PointTransaction::class, ['loyalty_account_id' => 'id']);
    }
}
