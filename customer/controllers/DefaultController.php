<?php

namespace customer\controllers;

use customer\components\RestController;
use Yii;
use yii\web\HttpException;

class DefaultController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['index', 'version', 'error'];
        return $behaviors;
    }

    public function actionIndex(): array
    {
        return [
            'data' => [
                'name' => 'AutoWash Customer API',
                'version' => '0.1.0',
            ],
        ];
    }

    public function actionVersion(): array
    {
        return [
            'data' => [
                'version' => '0.1.0',
                'module' => 'root',
            ],
        ];
    }

    public function actionError(): array
    {
        $exception = Yii::$app->errorHandler->exception;
        Yii::$app->response->statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;

        return [
            'message' => $exception ? $exception->getMessage() : 'Internal Server Error',
        ];
    }
}
