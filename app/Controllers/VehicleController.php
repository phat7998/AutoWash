<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\DuplicateLicensePlateException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Services\VehicleService;

final readonly class VehicleController
{
    public function __construct(
        private VehicleService $vehicles,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('customer/vehicles/index', [
            'title' => 'Phương tiện của tôi',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'vehicles' => $this->vehicles->listForOwner($this->ownerId()),
            'flashSuccess' => $this->session->get('success'),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->formResponse('create');
    }

    public function store(Request $request): Response
    {
        $values = $this->inputValues($request);

        try {
            $this->vehicles->create(
                $this->ownerId(),
                $values['vehicle_type_id'],
                $values['display_plate'],
                $values['brand'],
                $values['model'],
                $values['notes']
            );
        } catch (ValidationException $exception) {
            return $this->formResponse('create', $values, $exception->errors(), 422);
        } catch (DuplicateLicensePlateException $exception) {
            return $this->formResponse('create', $values, ['display_plate' => $exception->getMessage()], 422);
        }

        $this->session->flash('success', 'Đã thêm phương tiện vào tài khoản.');

        return Response::redirect('/phuong-tien');
    }

    public function edit(Request $request): Response
    {
        $vehicle = $this->vehicles->ownedVehicle($this->vehicleId($request), $this->ownerId());

        return $this->formResponse('edit', [
            'vehicle_type_id' => (string) $vehicle['vehicle_type_id'],
            'display_plate' => (string) $vehicle['display_plate'],
            'brand' => (string) ($vehicle['brand'] ?? ''),
            'model' => (string) ($vehicle['model'] ?? ''),
            'notes' => (string) ($vehicle['notes'] ?? ''),
        ], [], 200, (int) $vehicle['id']);
    }

    public function update(Request $request): Response
    {
        $vehicleId = $this->vehicleId($request);
        $values = $this->inputValues($request);

        try {
            $this->vehicles->update(
                $vehicleId,
                $this->ownerId(),
                $values['vehicle_type_id'],
                $values['display_plate'],
                $values['brand'],
                $values['model'],
                $values['notes']
            );
        } catch (ValidationException $exception) {
            return $this->formResponse('edit', $values, $exception->errors(), 422, $vehicleId);
        } catch (DuplicateLicensePlateException $exception) {
            return $this->formResponse(
                'edit',
                $values,
                ['display_plate' => $exception->getMessage()],
                422,
                $vehicleId
            );
        }

        $this->session->flash('success', 'Đã cập nhật thông tin phương tiện.');

        return Response::redirect('/phuong-tien');
    }

    public function deactivate(Request $request): Response
    {
        $this->vehicles->deactivate($this->vehicleId($request), $this->ownerId());
        $this->session->flash(
            'success',
            'Phương tiện đã được ngừng sử dụng và vẫn được giữ lại trong lịch sử.'
        );

        return Response::redirect('/phuong-tien');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function formResponse(
        string $mode,
        array $values = [],
        array $errors = [],
        int $status = 200,
        ?int $vehicleId = null
    ): Response {
        $defaults = [
            'vehicle_type_id' => '',
            'display_plate' => '',
            'brand' => '',
            'model' => '',
            'notes' => '',
        ];

        return Response::html($this->view->render('customer/vehicles/form', [
            'title' => $mode === 'create' ? 'Thêm phương tiện' : 'Sửa phương tiện',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'vehicleTypes' => $this->vehicles->activeTypes(),
            'values' => $values + $defaults,
            'errors' => $errors,
            'mode' => $mode,
            'vehicleId' => $vehicleId,
        ]), $status);
    }

    /** @return array<string, string> */
    private function inputValues(Request $request): array
    {
        return [
            'vehicle_type_id' => $this->stringInput($request, 'vehicle_type_id'),
            'display_plate' => $this->stringInput($request, 'display_plate'),
            'brand' => $this->stringInput($request, 'brand'),
            'model' => $this->stringInput($request, 'model'),
            'notes' => $this->stringInput($request, 'notes'),
        ];
    }

    private function vehicleId(Request $request): int
    {
        $value = $request->route('id', '');

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new VehicleOwnershipException();
        }

        return (int) $value;
    }

    private function ownerId(): int
    {
        $user = $this->authUser();

        return (int) $user['id'];
    }

    /** @return array<string, mixed> */
    private function authUser(): array
    {
        $user = $this->session->get('auth_user');

        if (!is_array($user)) {
            throw new VehicleOwnershipException();
        }

        return $user;
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
