<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$request = Request::capture();
$createApplication = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Application $application */
$application = $createApplication($request);
$application->handle($request)->send();
