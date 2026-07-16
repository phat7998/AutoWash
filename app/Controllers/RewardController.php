<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\RewardNotEligibleException;
use App\Exceptions\RewardNotFoundException;
use App\Exceptions\ValidationException;
use App\Services\RewardService;

final readonly class RewardController
{
    public function __construct(
        private RewardService $rewards,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render(
            'customer/rewards/index',
            $this->rewards->customerRewards($this->customerId()) + [
                'title' => 'Đổi điểm nhận reward',
                'authUser' => $this->authUser(),
                'csrfToken' => $this->tokens->token(),
                'flashSuccess' => $this->session->get('success'),
                'flashError' => $this->session->get('error'),
            ]
        ));
    }

    public function redeem(Request $request): Response
    {
        try {
            $this->rewards->redeem($this->customerId(), $this->resourceId($request));
            $this->session->flash(
                'success',
                'Đổi reward thành công. Điểm và credit lot đã cập nhật nguyên tử.'
            );
        } catch (InsufficientPointsException | RewardNotEligibleException $exception) {
            $this->session->flash('error', $exception->getMessage());
        }

        return Response::redirect('/doi-thuong');
    }

    private function resourceId(Request $request): int
    {
        $id = $request->route('id', '');

        if (!is_string($id) || preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
            throw new RewardNotFoundException();
        }

        return (int) $id;
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
