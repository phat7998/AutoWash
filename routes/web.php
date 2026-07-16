<?php

declare(strict_types=1);

use App\Core\CsrfTokenManager;
use App\Controllers\AuthController;
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
    callable $authControllerFactory
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

    $router->get('/tai-khoan', static function () use ($view, $session, $tokens): Response {
        return Response::html($view->render('customer/dashboard', [
            'title' => 'Tổng quan tài khoản',
            'authUser' => $session->get('auth_user'),
            'csrfToken' => $tokens->token(),
            'flashSuccess' => $session->get('success'),
        ]));
    }, $authenticated, new RoleMiddleware($session, 'customer'));

    $router->get('/admin', static function () use ($view, $session, $tokens): Response {
        return Response::html($view->render('admin/dashboard', [
            'title' => 'Khu vực quản trị',
            'authUser' => $session->get('auth_user'),
            'csrfToken' => $tokens->token(),
            'flashSuccess' => $session->get('success'),
        ]));
    }, $authenticated, $admin);
};
