<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use common\models\Promotion;
use Yii;

class PromotionController extends RestController
{
    public function actionIndex(): array
    {
        return ['data' => Promotion::find()->orderBy(['created_at' => SORT_DESC])->all()];
    }

    public function actionCreate(): array
    {
        $model = new Promotion();
        if ($model->load(Yii::$app->request->post(), '')) {
            $model->created_at = time();
            $model->updated_at = time();
            if ($model->save()) {
                Yii::$app->response->statusCode = 201;
                return ['data' => $model, 'message' => 'Tạo thành công'];
            }
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Tạo thất bại', 'data' => $model->errors];
    }
}
