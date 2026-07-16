<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final readonly class AuthenticatedMiddleware implements MiddlewareInterface
{
    public function __construct(private Session $session)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!is_array($this->session->get('auth_user'))) {
            $this->session->flash('error', 'Vui lòng đăng nhập để tiếp tục.');

            return Response::redirect('/dang-nhap');
        }

        return $next($request);
    }
}
