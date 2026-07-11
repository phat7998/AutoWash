<?php
return [
    'aliases' => [
        '@vendor' => dirname(dirname(__DIR__)) . '/vendor',
        '@bower'  => '@vendor/bower-asset',
        '@npm'    => '@vendor/npm-asset',
    ],
    'components' => [
	'db' => [
    	    'class' => yii\db\Connection::class,
    	    'dsn' => 'mysql:host=localhost;dbname=autowash',
    	    'username' => 'autowash',
    	    'password' => 'autowash123',
    	    'charset' => 'utf8mb4',
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
