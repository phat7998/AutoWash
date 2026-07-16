<?php

declare(strict_types=1);

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

return static function (
    Router $router,
    View $view,
    Session $session,
    CsrfTokenManager $tokens
): void {
    $router->get('/', static function () use ($view, $session, $tokens): Response {
        return Response::html($view->render('home', [
            'title' => 'Nền tảng AutoWash Pro đã sẵn sàng',
            'csrfToken' => $tokens->token(),
            'flashSuccess' => $session->get('success'),
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
};
