<?php

return [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=autowash',
            'username' => 'autowash',
            'password' => 'autowash123',
            'charset' => 'utf8mb4',
        ],
    ],
];
