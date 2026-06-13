<?php

namespace backend\controllers;

use yii\web\Controller;
use common\models\Booking;

class BookingController extends Controller
{
    public function actionIndex()
    {
        $models = Booking::find()->with(['customer', 'vehicle'])->orderBy(['scheduled_at' => SORT_DESC])->all();
        return $this->render('index', [
            'models' => $models,
        ]);
    }
}
