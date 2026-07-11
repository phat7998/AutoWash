<?php

namespace backend\modules\api\components;

use common\components\ApiResponseFormatter;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class RestController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
        ];
        
        return $behaviors;
    }

    public function beforeAction($action): bool
    {
        $request = \Yii::$app->request;
        $headers = \Yii::$app->response->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
        $headers->set('Access-Control-Max-Age', '86400');

        if ($request->getMethod() === 'OPTIONS') {
            \Yii::$app->response->statusCode = 204;
            return false;
        }

        ApiResponseFormatter::register(\Yii::$app->response);

        // Enforce Content-Type: application/json for POST/PUT/PATCH to prevent CSRF
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->getContentType();
            if (empty($contentType) || stripos($contentType, 'application/json') === false) {
                throw new BadRequestHttpException('Strict security: Request must contain Content-Type: application/json');
            }
        }

        return parent::beforeAction($action);
    }
}
