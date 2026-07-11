<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log', 'admin'],
    'modules' => [
        'api' => [
            'class' => 'backend\modules\api\Module',
        ],
        'admin' => [
            'class' => 'mdm\admin\Module',
            'layout' => 'left-menu',
            'mainLayout' => '@app/views/layouts/main.php',
            'controllerMap' => [
                'assignment' => [
                    'class' => 'mdm\admin\controllers\AssignmentController',
                    'userClassName' => 'common\models\User',
                    'idField' => 'id',
                    'usernameField' => 'username',
                ],
            ],
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => 'autowash-backend-cookie-key',
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
                'multipart/form-data' => yii\web\MultipartFormDataParser::class,
            ],
        ],
        'user' => [
            'identityClass' => common\models\User::class,
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_identity-backend',
                'httpOnly' => true,
            ],
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@vendor/mdmsoft/yii2-admin/views' => '@app/views/admin'
                ],
            ],
        ],
        'session' => [
            'name' => 'autowash-backend',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'OPTIONS <path:.+>' => 'api/auth/login',
                'POST auth/login' => 'api/auth/login',
                'GET tier-rules' => 'api/tier-rule/index',
                'PUT tier-rules/<id:\d+>' => 'api/tier-rule/update',
                'GET promotions' => 'api/promotion/index',
                'POST promotions' => 'api/promotion/create',
                'GET bookings' => 'api/booking/index',
                'GET bookings/queue' => 'api/booking/queue',
                'POST bookings/complete' => 'api/booking/complete',
                'GET analytics/summary' => 'api/analytics/summary',
                'GET analytics/tier-distribution' => 'api/analytics/tier-distribution',
                'GET analytics/booking-by-hour' => 'api/analytics/booking-by-hour',
                'GET analytics/retention' => 'api/analytics/retention',
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['api/auth'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'POST login' => 'login',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['api/tier-rule'],
                    'pluralize' => true,
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['api/promotion'],
                    'pluralize' => true,
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['api/booking'],
                    'pluralize' => true,
                    'extraPatterns' => [
                        'POST complete' => 'complete',
                        'GET queue' => 'queue',
                    ],
                ],
                [
                    'class' => yii\rest\UrlRule::class,
                    'controller' => ['api/analytics'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET summary' => 'summary',
                        'GET tier-distribution' => 'tier-distribution',
                        'GET booking-by-hour' => 'booking-by-hour',
                        'GET retention' => 'retention',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
