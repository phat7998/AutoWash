<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $vehicle_id
 * @property string $booking_code
 * @property int $scheduled_at
 * @property string $status
 * @property float $service_amount
 * @property int $reward_point_earned
 * @property int $reward_point_redeemed
 * @property int|null $promotion_id
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Customer $customer
 * @property Promotion $promotion
 * @property Vehicle $vehicle
 */
class Booking extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%booking}}';
    }

    public function rules(): array
    {
        return [
            [['customer_id', 'vehicle_id', 'booking_code', 'scheduled_at'], 'required'],
            [['customer_id', 'vehicle_id', 'scheduled_at', 'reward_point_earned', 'reward_point_redeemed', 'promotion_id', 'created_at', 'updated_at'], 'integer'],
            [['service_amount'], 'number'],
            [['booking_code'], 'string', 'max' => 40],
            [['status'], 'string', 'max' => 32],
            [['booking_code'], 'unique'],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['promotion_id'], 'exist', 'skipOnError' => true, 'targetClass' => Promotion::class, 'targetAttribute' => ['promotion_id' => 'id']],
            [['vehicle_id'], 'exist', 'skipOnError' => true, 'targetClass' => Vehicle::class, 'targetAttribute' => ['vehicle_id' => 'id']],
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getPromotion()
    {
        return $this->hasOne(Promotion::class, ['id' => 'promotion_id']);
    }

    public function getVehicle()
    {
        return $this->hasOne(Vehicle::class, ['id' => 'vehicle_id']);
    }
}
