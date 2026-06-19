<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property float $minimum_spend
 * @property int $minimum_visits
 * @property int $booking_window_days
 * @property int $priority_order
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property LoyaltyAccount[] $loyaltyAccounts
 */
class TierRule extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tier_rule}}';
    }

    public function rules(): array
    {
        return [
            [['code', 'name'], 'required'],
            [['minimum_spend'], 'number'],
            [['minimum_visits', 'booking_window_days', 'priority_order', 'created_at', 'updated_at'], 'integer'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 100],
            [['code'], 'unique'],
        ];
    }

    public function getLoyaltyAccounts()
    {
        return $this->hasMany(LoyaltyAccount::class, ['tier_rule_id' => 'id']);
    }
}
