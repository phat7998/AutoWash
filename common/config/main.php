<?php

return [
    'name' => 'AutoWash',
    'timeZone' => 'Asia/Ho_Chi_Minh',
    'language' => 'vi',
    'sourceLanguage' => 'en-US',
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'bootstrap' => ['log'],
    'components' => [
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=autowash',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
        'queue' => [
            'class' => yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => yii\mutex\MysqlMutex::class,
        ],
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
            'cache' => 'cache',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => yii\i18n\PhpMessageSource::class,
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'en-US',
                ],
            ],
        ],
    ],
];

