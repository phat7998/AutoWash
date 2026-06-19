<?php
return [
    'aliases' => [
        '@vendor' => dirname(dirname(__DIR__)) . '/vendor',
        '@bower'  => '@vendor/bower-asset',
        '@npm'    => '@vendor/npm-asset',
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=autowash',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => \yii\mutex\MysqlMutex::class,
        ],
    ],
];
