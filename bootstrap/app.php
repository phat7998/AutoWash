<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\CsrfTokenManager;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\CsrfMiddleware;

$projectRoot = require __DIR__ . '/environment.php';
$config = require $projectRoot . '/config/app.php';
$timezone = (string) $config['timezone'];

date_default_timezone_set($timezone);
error_reporting(E_ALL);
ini_set('display_errors', (bool) $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');

return static function (Request $request) use ($config, $projectRoot, $timezone): Application {
    $session = Session::start((array) $config['session'], $request->isSecure());
    $tokens = new CsrfTokenManager($session);
    $view = new View($projectRoot . '/resources/views');
    $router = new Router();
    $router->middleware(new CsrfMiddleware($tokens));

    $registerRoutes = require $projectRoot . '/routes/web.php';
    $registerRoutes($router, $view, $session, $tokens);

    $logFile = (string) $config['log_file'];

    if (!str_starts_with($logFile, '/')) {
        $logFile = $projectRoot . '/' . ltrim($logFile, '/');
    }

    $errorHandler = new ErrorHandler(
        $view,
        new Logger($logFile, new DateTimeZone($timezone)),
        (bool) $config['debug']
    );
    $errorHandler->register();

    return new Application($router, $errorHandler);
};
