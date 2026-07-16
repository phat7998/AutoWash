<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\CancellationCutoffException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Exceptions\SlotFullException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Services\BookingService;

final readonly class BookingController
{
    public function __construct(
        private BookingService $bookings,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function create(Request $request): Response
    {
        $vehicleId = $this->stringInput($request, 'vehicle_id');

        return $this->formResponse($vehicleId, '', [], [], 200);
    }

    public function index(Request $request): Response
    {
        $data = $this->bookings->customerOverview($this->ownerId());

        return Response::html($this->view->render('customer/bookings/index', $data + [
            'title' => 'Lịch đặt của tôi',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'flashSuccess' => $this->session->get('success'),
            'flashError' => $this->session->get('error'),
        ]));
    }

    public function show(Request $request): Response
    {
        return Response::html($this->view->render('customer/bookings/show', [
            'title' => 'Chi tiết lịch đặt',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'booking' => $this->bookings->customerDetail(
                $this->ownerId(),
                $this->resourceId($request)
            ),
            'flashSuccess' => $this->session->get('success'),
            'flashError' => $this->session->get('error'),
        ]));
    }

    public function cancel(Request $request): Response
    {
        $bookingId = $this->resourceId($request);

        try {
            $this->bookings->cancelByCustomer($this->ownerId(), $bookingId);
        } catch (CancellationCutoffException | InvalidBookingTransitionException $exception) {
            $this->session->flash('error', $exception->getMessage());

            return Response::redirect('/lich-dat/' . $bookingId);
        }

        $this->session->flash(
            'success',
            'Đã hủy lịch đặt. Sức chứa của các khung giờ liên quan đã được giải phóng.'
        );

        return Response::redirect('/lich-dat/' . $bookingId);
    }

    public function store(Request $request): Response
    {
        $vehicleId = $this->stringInput($request, 'vehicle_id');
        $slotId = $this->stringInput($request, 'start_slot_id');
        $serviceIds = $request->input('service_ids', []);

        try {
            $bookingCode = $this->bookings->create(
                $this->ownerId(),
                $vehicleId,
                $slotId,
                $serviceIds
            );
        } catch (ValidationException $exception) {
            return $this->formResponse(
                $vehicleId,
                $slotId,
                $this->serviceValues($serviceIds),
                $exception->errors(),
                422
            );
        } catch (
            BookingWindowExceededException | SlotFullException | BookingConflictException $exception
        ) {
            return $this->formResponse(
                $vehicleId,
                $slotId,
                $this->serviceValues($serviceIds),
                ['booking' => $exception->getMessage()],
                422
            );
        } catch (VehicleOwnershipException $exception) {
            return $this->formResponse(
                '',
                $slotId,
                $this->serviceValues($serviceIds),
                ['vehicle_id' => $exception->getMessage()],
                422
            );
        }

        $this->session->flash('success', sprintf(
            'Đã tạo lịch đặt %s. Giá và sức chứa được xác nhận từ hệ thống.',
            $bookingCode
        ));

        return Response::redirect('/dat-lich');
    }

    /**
     * @param list<string> $selectedServiceIds
     * @param array<string, string> $errors
     */
    private function formResponse(
        string $vehicleId,
        string $slotId,
        array $selectedServiceIds,
        array $errors,
        int $status
    ): Response {
        try {
            $data = $this->bookings->formData($this->ownerId(), $vehicleId);
        } catch (VehicleOwnershipException $exception) {
            $data = $this->bookings->formData($this->ownerId());
            $vehicleId = (string) ($data['selected_vehicle']['id'] ?? '');
            $errors['vehicle_id'] = $exception->getMessage();
            $status = 422;
        }

        if ($vehicleId === '' && is_array($data['selected_vehicle'])) {
            $vehicleId = (string) $data['selected_vehicle']['id'];
        }

        return Response::html($this->view->render('customer/bookings/create', $data + [
            'title' => 'Đặt lịch rửa xe',
            'authUser' => $this->authUser(),
            'csrfToken' => $this->tokens->token(),
            'flashSuccess' => $this->session->get('success'),
            'selectedVehicleId' => $vehicleId,
            'selectedSlotId' => $slotId,
            'selectedServiceIds' => $selectedServiceIds,
            'errors' => $errors,
        ]), $status);
    }

    /** @return list<string> */
    private function serviceValues(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, is_string(...)));
    }

    private function ownerId(): int
    {
        return (int) $this->authUser()['id'];
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

    private function resourceId(Request $request): int
    {
        $value = $request->route('id', '');

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new BookingNotFoundException();
        }

        return (int) $value;
    }
}
