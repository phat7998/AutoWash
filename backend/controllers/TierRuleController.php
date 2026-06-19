<?php

namespace backend\controllers;

use yii\web\Controller;
use common\models\TierRule;

class TierRuleController extends Controller
{
    public function actionIndex()
    {
        $models = TierRule::find()->orderBy(['priority_order' => SORT_ASC])->all();
        return $this->render('index', [
            'models' => $models,
        ]);
    }
}
