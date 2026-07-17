<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$user = [
    'id' => 1,
    'current_tier_id' => 2,
];

$tiers = [
    [
        'id' => 1,
        'code' => 'bronze',
        'name' => 'Bronze',
        'booking_window_days' => 7,
    ],
    [
        'id' => 2,
        'code' => 'silver',
        'name' => 'Silver',
        'booking_window_days' => 14,
    ],
];

$api = new App\Api\BookingLeadApi();
$result = $api->resolve($user, $tiers);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
