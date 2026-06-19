<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use common\models\TierRule;
use Yii;

class TierRuleController extends RestController
{
    public function actionIndex(): array
    {
        return ['data' => TierRule::find()->orderBy(['priority_order' => SORT_ASC])->all()];
    }

    public function actionUpdate($id): array
    {
        $model = TierRule::findOne($id);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy'];
        }

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            return ['data' => $model, 'message' => 'Cập nhật thành công'];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Cập nhật thất bại', 'data' => $model->errors];
    }
}
