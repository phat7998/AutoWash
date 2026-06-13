<?php

namespace customer\controllers;

use customer\components\RestController;
use customer\models\LoginForm;
use customer\models\RegisterForm;
use common\models\Customer;
use Yii;

class AuthController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        // Allow unauthenticated access to login and register
        $behaviors['authenticator']['optional'] = ['login', 'register'];
        return $behaviors;
    }

    public function actionLogin(): array
    {
        $model = new LoginForm();
        $model->load(Yii::$app->request->post(), '');

        if ($user = $model->login()) {
            $customer = Customer::findOne(['user_id' => $user->id]);
            return [
                'data' => [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'full_name' => $customer ? $customer->full_name : null,
                        'phone' => $customer ? $customer->phone : null,
                    ]
                ],
                'message' => 'Đăng nhập thành công',
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Đăng nhập thất bại', 'data' => $model->errors];
    }

    public function actionRegister(): array
    {
        $model = new RegisterForm();
        $model->load(Yii::$app->request->post(), '');

        if ($user = $model->register()) {
            $customer = Customer::findOne(['user_id' => $user->id]);
            return [
                'data' => [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'full_name' => $customer ? $customer->full_name : null,
                        'phone' => $customer ? $customer->phone : null,
                    ]
                ],
                'message' => 'Đăng ký thành công',
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Đăng ký thất bại', 'data' => $model->errors];
    }

    public function actionProfile(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount.tierRule')->one();
        if (!$customer) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Customer not found'];
        }

        return [
            'data' => [
                'full_name' => $customer->full_name,
                'phone' => $customer->phone,
                'license_plate' => $customer->license_plate,
                'loyalty' => [
                    'point_balance' => $customer->loyaltyAccount->point_balance,
                    'tier' => $customer->loyaltyAccount->tierRule->name ?? 'Member',
                ]
            ]
        ];
    }
}
