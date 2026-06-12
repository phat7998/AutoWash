<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use backend\models\LoginForm; // We can use backend's LoginForm
use Yii;

class AuthController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['login'];
        return $behaviors;
    }

    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
            $user = Yii::$app->user->identity;
            if ($user->role !== \common\models\User::ROLE_ADMIN && $user->role !== \common\models\User::ROLE_MANAGER) {
                Yii::$app->user->logout();
                Yii::$app->response->statusCode = 403;
                return ['message' => 'Quyền truy cập bị từ chối'];
            }
            
            // Generate token if not exists
            if (empty($user->access_token)) {
                $user->access_token = Yii::$app->security->generateRandomString();
                $user->save(false);
            }

            return [
                'data' => [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'role' => $user->role,
                    ]
                ],
                'message' => 'Đăng nhập thành công'
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Đăng nhập thất bại', 'data' => $model->errors];
    }
}
