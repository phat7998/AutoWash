<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Services\WashSlotService;

final readonly class WashSlotController
{
    public function __construct(
        private WashSlotService $slots,
        private View $view,
        private Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        $date = $this->stringInput($request, 'ngay');
        $errors = [];

        try {
            $slots = $this->slots->availableSlots($date);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $slots = $this->slots->availableSlots('');
        }

        return Response::html($this->view->render('customer/slots/index', [
            'title' => 'Khung giờ khả dụng',
            'authUser' => $this->session->get('auth_user'),
            'slots' => $slots,
            'selectedDate' => $date,
            'errors' => $errors,
        ]), $errors === [] ? 200 : 422);
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
