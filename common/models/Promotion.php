<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $target_tier
 * @property string $promotion_type
 * @property float|null $value
 * @property string $status
 * @property int|null $starts_at
 * @property int|null $ends_at
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Booking[] $bookings
 */
class Promotion extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%promotion}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['value'], 'number'],
            [['starts_at', 'ends_at', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 150],
            [['target_tier', 'promotion_type', 'status'], 'string', 'max' => 32],
        ];
    }

    public function getBookings()
    {
        return $this->hasMany(Booking::class, ['promotion_id' => 'id']);
    }
}
