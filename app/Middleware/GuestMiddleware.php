<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final readonly class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private Session $session)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            return $next($request);
        }

        return Response::redirect(($user['role'] ?? null) === 'admin' ? '/admin' : '/tai-khoan');
    }
}
