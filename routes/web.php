<?php

declare(strict_types=1);

use App\Core\CsrfTokenManager;
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
use App\Core\Response;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AuthenticatedMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;

return static function (
    Router $router,
    View $view,
    Session $session,
    CsrfTokenManager $tokens,
    callable $authControllerFactory,
    ?callable $vehicleControllerFactory = null,
    ?callable $catalogControllerFactory = null,
    ?callable $adminServiceControllerFactory = null,
    ?callable $washSlotControllerFactory = null,
    ?callable $adminSlotControllerFactory = null,
    ?callable $bookingControllerFactory = null,
    ?callable $adminBookingControllerFactory = null,
    ?callable $loyaltyControllerFactory = null,
    ?callable $adminLoyaltyControllerFactory = null,
    ?callable $rewardControllerFactory = null,
    ?callable $adminRewardControllerFactory = null,
    ?callable $adminTierReviewControllerFactory = null
): void {
    $router->get('/', static function () use ($view, $session, $tokens): Response {
        return Response::html($view->render('home', [
            'title' => 'Nền tảng AutoWash Pro đã sẵn sàng',
            'csrfToken' => $tokens->token(),
            'flashSuccess' => $session->get('success'),
            'authUser' => $session->get('auth_user'),
        ]));
    });

    $router->get('/health', static fn (): Response => Response::json([
        'status' => 'ok',
        'service' => 'AutoWash Pro',
    ]));

    if ($catalogControllerFactory !== null) {
        $catalogController = static fn (): CatalogController => $catalogControllerFactory();
        $router->get('/dich-vu', static fn (Request $request): Response =>
            $catalogController()->index($request));
    }

    $router->post('/thong-bao-mau', static function () use ($session): Response {
        $session->flash('success', 'Yêu cầu hợp lệ đã được xử lý an toàn.');
        return Response::redirect('/');
    });

    $guest = new GuestMiddleware($session);
    $authenticated = new AuthenticatedMiddleware($session);
    $admin = new RoleMiddleware($session, 'admin');

    $controller = static fn (): AuthController => $authControllerFactory();
    $router->get('/dang-ky', static fn (Request $request): Response =>
        $controller()->showRegister($request), $guest);
    $router->post('/dang-ky', static fn (Request $request): Response =>
        $controller()->register($request), $guest);
    $router->get('/dang-nhap', static fn (Request $request): Response =>
        $controller()->showLogin($request), $guest);
    $router->post('/dang-nhap', static fn (Request $request): Response =>
        $controller()->login($request), $guest);
    $router->post('/dang-xuat', static fn (Request $request): Response =>
        $controller()->logout($request), $authenticated);

    if ($loyaltyControllerFactory !== null) {
        $loyalty = static fn (): LoyaltyController => $loyaltyControllerFactory();
        $customer = new RoleMiddleware($session, 'customer');
        $router->get('/tai-khoan', static fn (Request $request): Response =>
            $loyalty()->dashboard($request), $authenticated, $customer);
        $router->get('/diem-thuong', static fn (Request $request): Response =>
            $loyalty()->index($request), $authenticated, $customer);
    } else {
        $router->get('/tai-khoan', static function () use ($view, $session, $tokens): Response {
            return Response::html($view->render('customer/dashboard', [
                'title' => 'Tổng quan tài khoản',
                'authUser' => $session->get('auth_user'),
                'csrfToken' => $tokens->token(),
                'flashSuccess' => $session->get('success'),
                'summary' => null,
                'recent_transactions' => [],
            ]));
        }, $authenticated, new RoleMiddleware($session, 'customer'));
    }

    $router->get('/admin', static function () use ($view, $session, $tokens): Response {
        return Response::html($view->render('admin/dashboard', [
            'title' => 'Khu vực quản trị',
            'authUser' => $session->get('auth_user'),
            'csrfToken' => $tokens->token(),
            'flashSuccess' => $session->get('success'),
        ]));
    }, $authenticated, $admin);

    if ($adminServiceControllerFactory !== null) {
        $adminServices = static fn (): AdminServiceController => $adminServiceControllerFactory();
        $router->get('/admin/dich-vu', static fn (Request $request): Response =>
            $adminServices()->index($request), $authenticated, $admin);
        $router->get('/admin/dich-vu/them', static fn (Request $request): Response =>
            $adminServices()->create($request), $authenticated, $admin);
        $router->post('/admin/dich-vu/them', static fn (Request $request): Response =>
            $adminServices()->store($request), $authenticated, $admin);
        $router->get('/admin/dich-vu/{id}/sua', static fn (Request $request): Response =>
            $adminServices()->edit($request), $authenticated, $admin);
        $router->post('/admin/dich-vu/{id}/sua', static fn (Request $request): Response =>
            $adminServices()->update($request), $authenticated, $admin);
        $router->post('/admin/dich-vu/{id}/ngung-hoat-dong', static fn (Request $request): Response =>
            $adminServices()->deactivate($request), $authenticated, $admin);
        $router->post('/admin/dich-vu/{id}/kich-hoat', static fn (Request $request): Response =>
            $adminServices()->activate($request), $authenticated, $admin);
    }

    if ($washSlotControllerFactory !== null) {
        $customerSlots = static fn (): WashSlotController => $washSlotControllerFactory();
        $router->get('/khung-gio', static fn (Request $request): Response =>
            $customerSlots()->index($request), $authenticated, new RoleMiddleware($session, 'customer'));
    }

    if ($adminSlotControllerFactory !== null) {
        $adminSlots = static fn (): AdminSlotController => $adminSlotControllerFactory();
        $router->get('/admin/khung-gio', static fn (Request $request): Response =>
            $adminSlots()->index($request), $authenticated, $admin);
        $router->get('/admin/khung-gio/them', static fn (Request $request): Response =>
            $adminSlots()->create($request), $authenticated, $admin);
        $router->post('/admin/khung-gio/them', static fn (Request $request): Response =>
            $adminSlots()->store($request), $authenticated, $admin);
        $router->post('/admin/khung-gio/{id}/dong', static fn (Request $request): Response =>
            $adminSlots()->close($request), $authenticated, $admin);
    }

    if ($bookingControllerFactory !== null) {
        $bookings = static fn (): BookingController => $bookingControllerFactory();
        $customer = new RoleMiddleware($session, 'customer');
        $router->get('/dat-lich', static fn (Request $request): Response =>
            $bookings()->create($request), $authenticated, $customer);
        $router->post('/dat-lich', static fn (Request $request): Response =>
            $bookings()->store($request), $authenticated, $customer);
        $router->get('/lich-dat', static fn (Request $request): Response =>
            $bookings()->index($request), $authenticated, $customer);
        $router->get('/lich-dat/{id}', static fn (Request $request): Response =>
            $bookings()->show($request), $authenticated, $customer);
        $router->post('/lich-dat/{id}/huy', static fn (Request $request): Response =>
            $bookings()->cancel($request), $authenticated, $customer);
    }

    if ($adminBookingControllerFactory !== null) {
        $adminBookings = static fn (): AdminBookingController => $adminBookingControllerFactory();
        $router->get('/admin/lich-dat', static fn (Request $request): Response =>
            $adminBookings()->index($request), $authenticated, $admin);
        $router->post('/admin/lich-dat/{id}/xac-nhan', static fn (Request $request): Response =>
            $adminBookings()->confirm($request), $authenticated, $admin);
        $router->post('/admin/lich-dat/{id}/hoan-thanh', static fn (Request $request): Response =>
            $adminBookings()->complete($request), $authenticated, $admin);
        $router->post('/admin/lich-dat/{id}/khong-den', static fn (Request $request): Response =>
            $adminBookings()->noShow($request), $authenticated, $admin);
        $router->post('/admin/lich-dat/{id}/huy', static fn (Request $request): Response =>
            $adminBookings()->cancel($request), $authenticated, $admin);
    }

    if ($adminLoyaltyControllerFactory !== null) {
        $adminLoyalty = static fn (): AdminLoyaltyController => $adminLoyaltyControllerFactory();
        $router->get('/admin/diem-thuong', static fn (Request $request): Response =>
            $adminLoyalty()->index($request), $authenticated, $admin);
        $router->post('/admin/diem-thuong/dieu-chinh', static fn (Request $request): Response =>
            $adminLoyalty()->adjust($request), $authenticated, $admin);
    }

    if ($rewardControllerFactory !== null) {
        $rewards = static fn (): RewardController => $rewardControllerFactory();
        $customer = new RoleMiddleware($session, 'customer');
        $router->get('/doi-thuong', static fn (Request $request): Response =>
            $rewards()->index($request), $authenticated, $customer);
        $router->post('/doi-thuong/{id}', static fn (Request $request): Response =>
            $rewards()->redeem($request), $authenticated, $customer);
    }

    if ($adminRewardControllerFactory !== null) {
        $adminRewards = static fn (): AdminRewardController => $adminRewardControllerFactory();
        $router->get('/admin/reward', static fn (Request $request): Response =>
            $adminRewards()->index($request), $authenticated, $admin);
        $router->get('/admin/reward/them', static fn (Request $request): Response =>
            $adminRewards()->create($request), $authenticated, $admin);
        $router->post('/admin/reward/them', static fn (Request $request): Response =>
            $adminRewards()->store($request), $authenticated, $admin);
        $router->get('/admin/reward/{id}/sua', static fn (Request $request): Response =>
            $adminRewards()->edit($request), $authenticated, $admin);
        $router->post('/admin/reward/{id}/sua', static fn (Request $request): Response =>
            $adminRewards()->update($request), $authenticated, $admin);
        $router->post('/admin/reward/{id}/ngung-hoat-dong', static fn (Request $request): Response =>
            $adminRewards()->deactivate($request), $authenticated, $admin);
        $router->post('/admin/reward/{id}/kich-hoat', static fn (Request $request): Response =>
            $adminRewards()->activate($request), $authenticated, $admin);
    }

    if ($adminTierReviewControllerFactory !== null) {
        $adminTierReviews = static fn (): AdminTierReviewController =>
            $adminTierReviewControllerFactory();
        $router->get('/admin/xet-hang', static fn (Request $request): Response =>
            $adminTierReviews()->index($request), $authenticated, $admin);
    }

    if ($vehicleControllerFactory === null) {
        return;
    }

    $customer = new RoleMiddleware($session, 'customer');
    $vehicleController = static fn (): VehicleController => $vehicleControllerFactory();
    $router->get('/phuong-tien', static fn (Request $request): Response =>
        $vehicleController()->index($request), $authenticated, $customer);
    $router->get('/phuong-tien/them', static fn (Request $request): Response =>
        $vehicleController()->create($request), $authenticated, $customer);
    $router->post('/phuong-tien/them', static fn (Request $request): Response =>
        $vehicleController()->store($request), $authenticated, $customer);
    $router->get('/phuong-tien/{id}/sua', static fn (Request $request): Response =>
        $vehicleController()->edit($request), $authenticated, $customer);
    $router->post('/phuong-tien/{id}/sua', static fn (Request $request): Response =>
        $vehicleController()->update($request), $authenticated, $customer);
    $router->post('/phuong-tien/{id}/ngung-su-dung', static fn (Request $request): Response =>
        $vehicleController()->deactivate($request), $authenticated, $customer);
};
