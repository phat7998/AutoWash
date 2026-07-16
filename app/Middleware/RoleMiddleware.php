<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\HttpException;

final readonly class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Session $session,
        private string $requiredRole
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            return Response::redirect('/dang-nhap');
        }

        if (($user['role'] ?? null) !== $this->requiredRole) {
            throw new HttpException(403, 'Tài khoản của bạn không có quyền truy cập khu vực này.');
        }

        return $next($request);
    }
}
