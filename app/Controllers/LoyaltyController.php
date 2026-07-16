<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Services\LoyaltyService;

final readonly class LoyaltyController
{
    public function __construct(
        private LoyaltyService $loyalty,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function dashboard(Request $request): Response
    {
        return Response::html($this->view->render(
            'customer/dashboard',
            $this->loyalty->dashboard($this->customerId()) + $this->commonData() + [
                'title' => 'Tổng quan tài khoản',
                'flashSuccess' => $this->session->get('success'),
            ]
        ));
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render(
            'customer/loyalty/index',
            $this->loyalty->history($this->customerId()) + $this->commonData() + [
                'title' => 'Điểm thưởng của tôi',
            ]
        ));
    }

    /** @return array<string, mixed> */
    private function commonData(): array
    {
        return [
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
        ];
    }

    private function customerId(): int
    {
        return (int) ($this->authUser()['id'] ?? 0);
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
