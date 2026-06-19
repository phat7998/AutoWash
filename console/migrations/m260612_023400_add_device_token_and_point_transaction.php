<?php

use yii\db\Migration;

class m260612_023400_add_device_token_and_point_transaction extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        // Add device token for push notifications
        $this->addColumn('{{%user}}', 'device_token', $this->string(255)->null()->after('access_token'));

        // Create point transaction table
        $this->createTable('{{%point_transaction}}', [
            'id' => $this->primaryKey(),
            'loyalty_account_id' => $this->integer()->notNull(),
            'transaction_type' => $this->string(32)->notNull()->comment('EARN, REDEEM, EXPIRE'),
            'points' => $this->integer()->notNull(),
            'available_points' => $this->integer()->notNull()->defaultValue(0)->comment('Points still valid for expiry'),
            'reference_id' => $this->integer()->null()->comment('Booking ID or Promotion ID'),
            'description' => $this->string(255)->null(),
            'expired_at' => $this->integer()->null()->comment('When these points expire'),
            'created_at' => $this->integer()->null(),
        ], $tableOptions);

        $this->addForeignKey(
            'fk_pt_loyalty_account',
            '{{%point_transaction}}',
            'loyalty_account_id',
            '{{%loyalty_account}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_pt_loyalty_account', '{{%point_transaction}}');
        $this->dropTable('{{%point_transaction}}');
        $this->dropColumn('{{%user}}', 'device_token');
    }
}
