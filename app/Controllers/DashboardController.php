<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Services\DashboardService;

final readonly class DashboardController
{
    public function __construct(
        private DashboardService $dashboards,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function customer(Request $request): Response
    {
        return Response::html($this->view->render(
            'customer/dashboard',
            $this->dashboards->customer((int) ($this->authUser()['id'] ?? 0)) + $this->commonData() + [
                'title' => 'Tổng quan tài khoản',
            ]
        ));
    }

    public function admin(Request $request): Response
    {
        return Response::html($this->view->render(
            'admin/dashboard',
            $this->dashboards->admin() + $this->commonData() + [
                'title' => 'Tổng quan vận hành',
            ]
        ));
    }

    /** @return array<string, mixed> */
    private function commonData(): array
    {
        return [
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'flashSuccess' => $this->session->get('success'),
        ];
    }

    /** @return array<string, mixed> */
    private function authUser(): array
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            throw new ValidationException(['user' => 'Phiên đăng nhập không hợp lệ.']);
        }

        return $user;
    }
}
