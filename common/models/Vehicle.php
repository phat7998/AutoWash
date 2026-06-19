<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $license_plate
 * @property string $vehicle_type
 * @property string|null $brand_name
 * @property string $status
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Customer $customer
 * @property Booking[] $bookings
 */
class Vehicle extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%vehicle}}';
    }

    public function rules(): array
    {
        return [
            [['customer_id', 'license_plate'], 'required'],
            [['customer_id', 'created_at', 'updated_at'], 'integer'],
            [['license_plate'], 'string', 'max' => 20],
            [['vehicle_type', 'status'], 'string', 'max' => 32],
            [['brand_name'], 'string', 'max' => 100],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getBookings()
    {
        return $this->hasMany(Booking::class, ['vehicle_id' => 'id']);
    }
}
