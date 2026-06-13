<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\web\Response;

class ApiResponseFormatter extends Component
{
    public static function register(Response $response): void
    {
        $response->on(Response::EVENT_BEFORE_SEND, static function ($event): void {
            $sender = $event->sender;
            if (!$sender instanceof Response || $sender->data === null) {
                return;
            }

            $data = $sender->data;
            $payload = is_array($data) ? $data : ['data' => $data];

            $sender->format = Response::FORMAT_JSON;
            $sender->data = [
                'isSuccessful' => $sender->isSuccessful,
                'statusCode' => $sender->statusCode,
                'message' => $payload['message'] ?? ($sender->isSuccessful ? null : Yii::t('app', 'Request failed')),
                'data' => $payload['data'] ?? $data,
            ];
        });
    }
}
