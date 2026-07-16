<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\CsrfTokenManager;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Controllers\AuthController;
use App\Controllers\AdminBookingController;
use App\Controllers\AdminServiceController;
use App\Controllers\AdminSlotController;
use App\Controllers\BookingController;
use App\Controllers\CatalogController;
use App\Controllers\VehicleController;
use App\Controllers\WashSlotController;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\WashSlotRepository;
use App\Repositories\BookingRepository;
use App\Services\AuthService;
use App\Services\LicensePlateService;
use App\Services\ServiceCatalogService;
use App\Services\VehicleService;
use App\Services\WashSlotService;
use App\Services\BookingService;
use App\Services\BookingLifecyclePolicy;
use App\Services\BookingResourceCalculator;
use App\Services\BookingWindowPolicy;
use App\Services\PriceCalculator;
use App\Validation\AuthValidator;
use App\Validation\ServiceCatalogValidator;
use App\Validation\VehicleValidator;
use App\Validation\WashSlotValidator;
use App\Validation\BookingValidator;
use App\Validation\BookingLifecycleValidator;

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

    $logFile = (string) $config['log_file'];

    if (!str_starts_with($logFile, '/')) {
        $logFile = $projectRoot . '/' . ltrim($logFile, '/');
    }

    $logger = new Logger($logFile, new DateTimeZone($timezone));

    $authControllerFactory = static fn (): AuthController => new AuthController(
        new AuthService(new UserRepository(Database::connection()), new AuthValidator(), $session, $logger),
        $view,
        $session,
        $tokens
    );
    $vehicleControllerFactory = static function () use ($view, $session, $tokens): VehicleController {
        $plates = new LicensePlateService();

        return new VehicleController(
            new VehicleService(
                new VehicleRepository(Database::connection()),
                new VehicleValidator($plates),
                $plates
            ),
            $view,
            $session,
            $tokens
        );
    };
    $catalogServiceFactory = static fn (): ServiceCatalogService => new ServiceCatalogService(
        new ServiceCatalogRepository(Database::connection()),
        new ServiceCatalogValidator()
    );
    $slotServiceFactory = static fn (): WashSlotService => new WashSlotService(
        new WashSlotRepository(Database::connection()),
        new WashSlotValidator(new DateTimeZone($timezone))
    );
    $catalogControllerFactory = static fn (): CatalogController => new CatalogController(
        $catalogServiceFactory(),
        $view,
        $session
    );
    $adminServiceControllerFactory = static fn (): AdminServiceController => new AdminServiceController(
        $catalogServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $washSlotControllerFactory = static fn (): WashSlotController => new WashSlotController(
        $slotServiceFactory(),
        $view,
        $session
    );
    $adminSlotControllerFactory = static fn (): AdminSlotController => new AdminSlotController(
        $slotServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $bookingServiceFactory = static fn (): BookingService => new BookingService(
        new BookingRepository(Database::connection()),
        new BookingValidator(),
        new BookingWindowPolicy(new DateTimeZone($timezone)),
        new PriceCalculator(),
        new BookingResourceCalculator(),
        new DateTimeZone($timezone),
        new BookingLifecyclePolicy(),
        new BookingLifecycleValidator(),
        null,
        $logger
    );
    $bookingControllerFactory = static fn (): BookingController => new BookingController(
        $bookingServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $adminBookingControllerFactory = static fn (): AdminBookingController => new AdminBookingController(
        $bookingServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $registerRoutes = require $projectRoot . '/routes/web.php';
    $registerRoutes(
        $router,
        $view,
        $session,
        $tokens,
        $authControllerFactory,
        $vehicleControllerFactory,
        $catalogControllerFactory,
        $adminServiceControllerFactory,
        $washSlotControllerFactory,
        $adminSlotControllerFactory,
        $bookingControllerFactory,
        $adminBookingControllerFactory
    );

    $errorHandler = new ErrorHandler(
        $view,
        $logger,
        (bool) $config['debug']
    );
    $errorHandler->register();

    return new Application($router, $errorHandler);
};
