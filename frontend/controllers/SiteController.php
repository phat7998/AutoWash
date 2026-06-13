<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'bookingWindow' => Yii::$app->params['bookingWindow'],
        ]);
    }

    public function actionError(): string
    {
        return $this->render('error');
    }
}

