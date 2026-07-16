<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'point_unit_amount' => Env::integer('LOYALTY_POINT_UNIT_AMOUNT', 10_000),
];
