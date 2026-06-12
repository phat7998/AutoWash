<?php

namespace backend\modules\api;

use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $controllerNamespace = 'backend\modules\api\controllers';

    public function init()
    {
        parent::init();
        // custom initialization code goes here
        \Yii::$app->user->enableSession = false;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }
}
