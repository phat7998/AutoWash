<?php

namespace backend\controllers;

use yii\web\Controller;
use common\models\Promotion;

class PromotionController extends Controller
{
    public function actionIndex()
    {
        $models = Promotion::find()->orderBy(['created_at' => SORT_DESC])->all();
        return $this->render('index', [
            'models' => $models,
        ]);
    }
}
