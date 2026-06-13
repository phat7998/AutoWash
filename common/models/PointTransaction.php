<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $loyalty_account_id
 * @property string $transaction_type EARN, REDEEM, EXPIRE
 * @property int $points
 * @property int $available_points
 * @property int|null $reference_id
 * @property string|null $description
 * @property int|null $expired_at
 * @property int|null $created_at
 *
 * @property LoyaltyAccount $loyaltyAccount
 */
class PointTransaction extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%point_transaction}}';
    }

    public function rules(): array
    {
        return [
            [['loyalty_account_id', 'transaction_type', 'points'], 'required'],
            [['loyalty_account_id', 'points', 'available_points', 'reference_id', 'expired_at', 'created_at'], 'integer'],
            [['transaction_type'], 'string', 'max' => 32],
            [['description'], 'string', 'max' => 255],
            [['loyalty_account_id'], 'exist', 'skipOnError' => true, 'targetClass' => LoyaltyAccount::class, 'targetAttribute' => ['loyalty_account_id' => 'id']],
        ];
    }

    public function getLoyaltyAccount()
    {
        return $this->hasOne(LoyaltyAccount::class, ['id' => 'loyalty_account_id']);
    }
}
