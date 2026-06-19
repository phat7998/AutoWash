<?php

namespace common\services;

use common\models\LoyaltyAccount;
use Yii;

class LoyaltyService
{
    public static function bookingWindow(string $tier): int
    {
        $windows = Yii::$app->params['bookingWindow'] ?? [];

        return (int) ($windows[strtoupper($tier)] ?? $windows['MEMBER'] ?? 7);
    }

    public static function canRedeem(LoyaltyAccount $account, int $points): bool
    {
        return (int) $account->point_balance >= $points;
    }
}

