<?php

namespace customer\controllers;

use customer\components\RestController;

class PromotionController extends RestController
{
    public function actionActive(): array
    {
        return [
            'data' => [
                'items' => [],
            ],
        ];
    }
}

