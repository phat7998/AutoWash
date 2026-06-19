<?php

namespace console\controllers;

use yii\console\Controller;

class SeedController extends Controller
{
    public function actionIndex(): int
    {
        $this->stdout("TODO: seed tier rules, point rates, promotions va du lieu mau.\n");
        return self::EXIT_CODE_NORMAL;
    }
}

