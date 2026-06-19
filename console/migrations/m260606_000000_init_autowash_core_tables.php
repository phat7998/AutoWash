<?php

use yii\db\Migration;

class m260606_000000_init_autowash_core_tables extends Migration
{
    public function safeUp(): void
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(64)->null(),
            'access_token' => $this->string(255)->null(),
            'role' => $this->string(32)->notNull()->defaultValue('CUSTOMER'),
            'phone' => $this->string(20)->null(),
            'email' => $this->string()->null(),
            'status' => $this->string(32)->notNull()->defaultValue('ACTIVE'),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%customer}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->unique(),
            'full_name' => $this->string()->notNull(),
            'phone' => $this->string(20)->notNull(),
            'license_plate' => $this->string(20)->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%vehicle}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'license_plate' => $this->string(20)->notNull(),
            'vehicle_type' => $this->string(32)->notNull()->defaultValue('MOTORBIKE'),
            'brand_name' => $this->string(100)->null(),
            'status' => $this->string(32)->notNull()->defaultValue('ACTIVE'),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%tier_rule}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'name' => $this->string(100)->notNull(),
            'minimum_spend' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'minimum_visits' => $this->integer()->notNull()->defaultValue(0),
            'booking_window_days' => $this->integer()->notNull()->defaultValue(7),
            'priority_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%loyalty_account}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull()->unique(),
            'tier_rule_id' => $this->integer()->null(),
            'point_balance' => $this->integer()->notNull()->defaultValue(0),
            'lifetime_spend' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'wash_count' => $this->integer()->notNull()->defaultValue(0),
            'reviewed_at' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%promotion}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(150)->notNull(),
            'target_tier' => $this->string(32)->null(),
            'promotion_type' => $this->string(32)->notNull()->defaultValue('DISCOUNT'),
            'value' => $this->decimal(12, 2)->null(),
            'status' => $this->string(32)->notNull()->defaultValue('DRAFT'),
            'starts_at' => $this->integer()->null(),
            'ends_at' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->createTable('{{%booking}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'vehicle_id' => $this->integer()->notNull(),
            'booking_code' => $this->string(40)->notNull()->unique(),
            'scheduled_at' => $this->integer()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('PENDING'),
            'service_amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'reward_point_earned' => $this->integer()->notNull()->defaultValue(0),
            'reward_point_redeemed' => $this->integer()->notNull()->defaultValue(0),
            'promotion_id' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->addForeignKey('fk_customer_user', '{{%customer}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_vehicle_customer', '{{%vehicle}}', 'customer_id', '{{%customer}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_loyalty_customer', '{{%loyalty_account}}', 'customer_id', '{{%customer}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_loyalty_tier_rule', '{{%loyalty_account}}', 'tier_rule_id', '{{%tier_rule}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_booking_customer', '{{%booking}}', 'customer_id', '{{%customer}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_booking_vehicle', '{{%booking}}', 'vehicle_id', '{{%vehicle}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_booking_promotion', '{{%booking}}', 'promotion_id', '{{%promotion}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_booking_promotion', '{{%booking}}');
        $this->dropForeignKey('fk_booking_vehicle', '{{%booking}}');
        $this->dropForeignKey('fk_booking_customer', '{{%booking}}');
        $this->dropForeignKey('fk_loyalty_tier_rule', '{{%loyalty_account}}');
        $this->dropForeignKey('fk_loyalty_customer', '{{%loyalty_account}}');
        $this->dropForeignKey('fk_vehicle_customer', '{{%vehicle}}');
        $this->dropForeignKey('fk_customer_user', '{{%customer}}');

        $this->dropTable('{{%booking}}');
        $this->dropTable('{{%promotion}}');
        $this->dropTable('{{%loyalty_account}}');
        $this->dropTable('{{%tier_rule}}');
        $this->dropTable('{{%vehicle}}');
        $this->dropTable('{{%customer}}');
        $this->dropTable('{{%user}}');
    }
}

