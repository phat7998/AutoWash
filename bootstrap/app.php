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
use App\Controllers\AdminLoyaltyController;
use App\Controllers\AdminServiceController;
use App\Controllers\AdminSlotController;
use App\Controllers\AdminRewardController;
use App\Controllers\AdminTierReviewController;
use App\Controllers\BookingController;
use App\Controllers\LoyaltyController;
use App\Controllers\RewardController;
use App\Controllers\CatalogController;
use App\Controllers\VehicleController;
use App\Controllers\WashSlotController;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\WashSlotRepository;
use App\Repositories\BookingRepository;
use App\Repositories\LoyaltyTransactionRepository;
use App\Repositories\RewardRepository;
use App\Repositories\TierRepository;
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
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyDebitAllocator;
use App\Services\LoyaltyExpirationPolicy;
use App\Services\LoyaltyService;
use App\Services\RewardService;
use App\Services\TierReviewPolicy;
use App\Services\TierReviewService;
use App\Validation\AuthValidator;
use App\Validation\ServiceCatalogValidator;
use App\Validation\VehicleValidator;
use App\Validation\WashSlotValidator;
use App\Validation\BookingValidator;
use App\Validation\BookingLifecycleValidator;
use App\Validation\LoyaltyAdjustmentValidator;
use App\Validation\RewardValidator;

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
    $loyaltyConfig = require $projectRoot . '/config/loyalty.php';

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
    $loyaltyServiceFactory = static fn (): LoyaltyService => new LoyaltyService(
        new LoyaltyTransactionRepository(Database::connection()),
        new LoyaltyPointCalculator((int) $loyaltyConfig['point_unit_amount']),
        new LoyaltyAdjustmentValidator(),
        new LoyaltyDebitAllocator(),
        new LoyaltyExpirationPolicy(new DateTimeZone($timezone)),
        new DateTimeZone($timezone)
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
        $loyaltyServiceFactory(),
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
    $loyaltyControllerFactory = static fn (): LoyaltyController => new LoyaltyController(
        $loyaltyServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $adminLoyaltyControllerFactory = static fn (): AdminLoyaltyController => new AdminLoyaltyController(
        $loyaltyServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $rewardServiceFactory = static fn (): RewardService => new RewardService(
        new RewardRepository(Database::connection()),
        $loyaltyServiceFactory(),
        new RewardValidator(),
        new DateTimeZone($timezone)
    );
    $rewardControllerFactory = static fn (): RewardController => new RewardController(
        $rewardServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $adminRewardControllerFactory = static fn (): AdminRewardController => new AdminRewardController(
        $rewardServiceFactory(),
        $view,
        $session,
        $tokens
    );
    $tierReviewServiceFactory = static fn (): TierReviewService => new TierReviewService(
        new TierRepository(Database::connection()),
        new TierReviewPolicy(new DateTimeZone($timezone)),
        new DateTimeZone($timezone)
    );
    $adminTierReviewControllerFactory = static fn (): AdminTierReviewController =>
        new AdminTierReviewController(
            $tierReviewServiceFactory(),
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
        $adminBookingControllerFactory,
        $loyaltyControllerFactory,
        $adminLoyaltyControllerFactory,
        $rewardControllerFactory,
        $adminRewardControllerFactory,
        $adminTierReviewControllerFactory
    );

    $errorHandler = new ErrorHandler(
        $view,
        $logger,
        (bool) $config['debug']
    );
    $errorHandler->register();

    return new Application($router, $errorHandler);
};
