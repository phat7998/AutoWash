<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'host' => Env::string('DB_HOST', '127.0.0.1'),
    'port' => Env::integer('DB_PORT', 3306),
    'database' => Env::string('DB_NAME', 'autowash'),
    'username' => Env::string('DB_USER', 'autowash'),
    'password' => Env::string('DB_PASSWORD'),
    'charset' => Env::string('DB_CHARSET', 'utf8mb4'),
];
