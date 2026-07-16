<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\CatalogResourceNotFoundException;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Services\WashSlotService;

final readonly class AdminSlotController
{
    public function __construct(
        private WashSlotService $slots,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/slots/index', $this->commonData() + [
            'title' => 'Quản lý khung giờ',
            'slots' => $this->slots->adminSlots(),
            'flashSuccess' => $this->session->get('success'),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->formResponse();
    }

    public function store(Request $request): Response
    {
        $values = $this->inputValues($request);

        try {
            $this->slots->create(
                $values['slot_date'],
                $values['start_time'],
                $values['end_time'],
                $values['capacity_units']
            );
        } catch (ValidationException $exception) {
            return $this->formResponse($values, $exception->errors(), 422);
        } catch (DuplicateCatalogException $exception) {
            return $this->formResponse($values, ['slot_date' => $exception->getMessage()], 422);
        }

        $this->session->flash('success', 'Đã tạo khung giờ mới.');

        return Response::redirect('/admin/khung-gio');
    }

    public function close(Request $request): Response
    {
        try {
            $this->slots->close($this->resourceId($request));
        } catch (ValidationException) {
            throw new CatalogResourceNotFoundException('Không tìm thấy khung giờ được yêu cầu.');
        }

        $this->session->flash(
            'success',
            'Khung giờ đã được đóng; dữ liệu giữ chỗ vẫn được bảo toàn.'
        );

        return Response::redirect('/admin/khung-gio');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function formResponse(array $values = [], array $errors = [], int $status = 200): Response
    {
        $defaults = ['slot_date' => '', 'start_time' => '', 'end_time' => '', 'capacity_units' => ''];

        return Response::html($this->view->render('admin/slots/form', $this->commonData() + [
            'title' => 'Thêm khung giờ',
            'values' => $values + $defaults,
            'errors' => $errors,
        ]), $status);
    }

    /** @return array<string, string> */
    private function inputValues(Request $request): array
    {
        return [
            'slot_date' => $this->stringInput($request, 'slot_date'),
            'start_time' => $this->stringInput($request, 'start_time'),
            'end_time' => $this->stringInput($request, 'end_time'),
            'capacity_units' => $this->stringInput($request, 'capacity_units'),
        ];
    }

    private function resourceId(Request $request): int
    {
        $value = $request->route('id', '');

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new CatalogResourceNotFoundException('Không tìm thấy khung giờ được yêu cầu.');
        }

        return (int) $value;
    }

    /** @return array<string, mixed> */
    private function commonData(): array
    {
        return [
            'authUser' => $this->session->get('auth_user'),
            'csrfToken' => $this->tokens->token(),
        ];
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
