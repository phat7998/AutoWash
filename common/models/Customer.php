<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $phone
 * @property string|null $license_plate
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property User $user
 * @property Vehicle[] $vehicles
 * @property LoyaltyAccount $loyaltyAccount
 * @property Booking[] $bookings
 */
class Customer extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%customer}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'full_name', 'phone'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['full_name', 'phone', 'license_plate'], 'string', 'max' => 255],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getVehicles()
    {
        return $this->hasMany(Vehicle::class, ['customer_id' => 'id']);
    }

    public function getLoyaltyAccount()
    {
        return $this->hasOne(LoyaltyAccount::class, ['customer_id' => 'id']);
    }

    public function getBookings()
    {
        return $this->hasMany(Booking::class, ['customer_id' => 'id']);
    }
}
