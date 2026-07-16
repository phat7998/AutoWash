<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Exceptions\ValidationException;
use App\Services\BookingService;

final readonly class AdminBookingController
{
    public function __construct(
        private BookingService $bookings,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/bookings/index', [
            'title' => 'Quản lý lịch đặt',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'bookings' => $this->bookings->adminBookings(),
            'flashSuccess' => $this->session->get('success'),
            'flashError' => $this->session->get('error'),
        ]));
    }

    public function confirm(Request $request): Response
    {
        return $this->transition($request, 'confirmed');
    }

    public function complete(Request $request): Response
    {
        return $this->transition($request, 'completed');
    }

    public function noShow(Request $request): Response
    {
        return $this->transition($request, 'no_show');
    }

    public function cancel(Request $request): Response
    {
        return $this->transition($request, 'cancelled');
    }

    private function transition(Request $request, string $targetStatus): Response
    {
        $bookingId = $this->resourceId($request);
        $adminId = (int) $this->authUser()['id'];

        try {
            match ($targetStatus) {
                'confirmed' => $this->bookings->confirmByAdmin($adminId, $bookingId),
                'completed' => $this->bookings->completeByAdmin($adminId, $bookingId),
                'no_show' => $this->bookings->markNoShowByAdmin($adminId, $bookingId),
                'cancelled' => $this->bookings->cancelByAdmin(
                    $adminId,
                    $bookingId,
                    $this->stringInput($request, 'cancellation_reason')
                ),
                default => throw new BookingNotFoundException(),
            };
        } catch (InvalidBookingTransitionException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return Response::redirect('/admin/lich-dat');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $message = reset($errors);
            $this->session->flash(
                'error',
                is_string($message) ? $message : $exception->getMessage()
            );

            return Response::redirect('/admin/lich-dat');
        }

        $this->session->flash('success', match ($targetStatus) {
            'confirmed' => 'Đã xác nhận lịch đặt.',
            'completed' => (
                'Đã ghi nhận hoàn thành. Loyalty sẽ được xử lý ở bước tích hợp tiếp theo.'
            ),
            'no_show' => 'Đã đánh dấu khách không đến.',
            'cancelled' => 'Đã hủy lịch đặt và giải phóng sức chứa.',
            default => 'Đã cập nhật lịch đặt.',
        });

        return Response::redirect('/admin/lich-dat');
    }

    private function resourceId(Request $request): int
    {
        $value = $request->route('id', '');

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new BookingNotFoundException();
        }

        return (int) $value;
    }

    /** @return array<string, mixed> */
    private function authUser(): array
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            throw new BookingNotFoundException();
        }

        return $user;
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
