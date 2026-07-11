<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-customer',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'customer\controllers',
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'autowash-customer-cookie-key',
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
                'multipart/form-data' => yii\web\MultipartFormDataParser::class,
            ],
        ],
        'user' => [
            'class' => yii\web\User::class,
            'identityClass' => common\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'errorAction' => 'default/error',
        ],
        'response' => [
            'class' => yii\web\Response::class,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'version' => 'default/version',
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['default'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET' => 'index',
                        'GET version' => 'version',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['auth'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'POST login' => 'login',
                        'POST register' => 'register',
                        'GET profile' => 'profile',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['booking'],
                    'pluralize' => true,
                    'extraPatterns' => [
                        'GET history' => 'history',
                        'POST <id:\d+>/complete' => 'complete',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['vehicle'],
                    'pluralize' => true,
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['loyalty'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET balance' => 'balance',
                        'GET tiers' => 'tiers',
                        'POST redeem' => 'redeem',
                        'GET tier' => 'tier',
                        'GET next-tier' => 'next-tier',
                        'GET advance-days' => 'advance-days',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['promotion'],
                    'pluralize' => true,
                    'extraPatterns' => [
                        'GET active' => 'active',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
