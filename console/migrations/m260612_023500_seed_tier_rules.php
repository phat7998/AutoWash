<?php

use yii\db\Migration;

class m260612_023500_seed_tier_rules extends Migration
{
    public function safeUp()
    {
        $time = time();
        $this->batchInsert('{{%tier_rule}}', 
            ['code', 'name', 'minimum_spend', 'minimum_visits', 'booking_window_days', 'priority_order', 'created_at', 'updated_at'],
            [
                ['MEMBER', 'Member', 0, 0, 7, 0, $time, $time],
                ['SILVER', 'Silver', 500000, 5, 10, 1, $time, $time],
                ['GOLD', 'Gold', 1500000, 15, 12, 2, $time, $time],
                ['PLATINUM', 'Platinum', 3000000, 30, 14, 3, $time, $time],
            ]
        );
    }

    public function safeDown()
    {
        $this->delete('{{%tier_rule}}', ['code' => ['MEMBER', 'SILVER', 'GOLD', 'PLATINUM']]);
    }
}
