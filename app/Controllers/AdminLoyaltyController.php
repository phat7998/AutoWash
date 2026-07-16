<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\ValidationException;
use App\Services\LoyaltyService;

final readonly class AdminLoyaltyController
{
    public function __construct(
        private LoyaltyService $loyalty,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->response([], [], 200);
    }

    public function adjust(Request $request): Response
    {
        $values = [
            'user_id' => $this->stringInput($request, 'user_id'),
            'points' => $this->stringInput($request, 'points'),
            'reason' => $this->stringInput($request, 'reason'),
            'source_transaction_id' => $this->stringInput($request, 'source_transaction_id'),
        ];

        try {
            $this->loyalty->adjust(
                (int) ($this->authUser()['id'] ?? 0),
                $values['user_id'],
                $values['points'],
                $values['reason'],
                $values['source_transaction_id']
            );
        } catch (ValidationException $exception) {
            return $this->response($values, $exception->errors(), 422);
        } catch (InsufficientPointsException $exception) {
            return $this->response($values, ['points' => $exception->getMessage()], 422);
        }

        $this->session->flash(
            'success',
            'Đã điều chỉnh điểm. Sổ giao dịch, số dư cache và audit log '
            . 'đã được cập nhật cùng nhau.'
        );

        return Response::redirect('/admin/diem-thuong');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function response(array $values, array $errors, int $status): Response
    {
        return Response::html($this->view->render('admin/loyalty/index', [
            'title' => 'Điều chỉnh điểm khách hàng',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'customers' => $this->loyalty->adjustmentCustomers(),
            'values' => $values + [
                'user_id' => '',
                'points' => '',
                'reason' => '',
                'source_transaction_id' => '',
            ],
            'errors' => $errors,
            'flashSuccess' => $this->session->get('success'),
        ]), $status);
    }

    /** @return array<string, mixed> */
    private function authUser(): array
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            throw new ValidationException(['admin' => 'Phiên quản trị không hợp lệ.']);
        }

        return $user;
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
