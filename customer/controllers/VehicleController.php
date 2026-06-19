<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Vehicle;
use common\models\Customer;
use Yii;

class VehicleController extends RestController
{
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::findOne(['user_id' => $user->getId()]);
        if (!$customer) {
            return ['data' => []];
        }

        $vehicles = Vehicle::find()->where(['customer_id' => $customer->id, 'status' => 'ACTIVE'])->all();

        return [
            'data' => array_map(function($v) {
                return [
                    'id' => $v->id,
                    'license_plate' => $v->license_plate,
                    'vehicle_type' => $v->vehicle_type,
                    'brand_name' => $v->brand_name,
                ];
            }, $vehicles)
        ];
    }

    public function actionCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::findOne(['user_id' => $user->getId()]);
        
        $vehicle = new Vehicle();
        $vehicle->customer_id = $customer->id;
        $vehicle->load(Yii::$app->request->post(), '');
        $vehicle->created_at = time();
        $vehicle->updated_at = time();

        if ($vehicle->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'data' => $vehicle,
                'message' => 'Thêm xe thành công'
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Thêm xe thất bại', 'data' => $vehicle->errors];
    }

    public function actionDelete($id): array
    {
        $user = Yii::$app->user->identity;
        $customer = Customer::findOne(['user_id' => $user->getId()]);

        $vehicle = Vehicle::findOne(['id' => $id, 'customer_id' => $customer->id]);
        if ($vehicle) {
            $vehicle->status = 'INACTIVE';
            $vehicle->save(false);
            return ['message' => 'Xóa xe thành công'];
        }

        Yii::$app->response->statusCode = 404;
        return ['message' => 'Không tìm thấy xe'];
    }
}
