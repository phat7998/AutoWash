<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'name' => Env::string('APP_NAME', 'AutoWash Pro'),
    'environment' => Env::string('APP_ENV', 'production'),
    'debug' => Env::boolean('APP_DEBUG'),
    'timezone' => Env::string('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'url' => Env::string('APP_URL', 'http://localhost:8080'),
];
