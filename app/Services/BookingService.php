<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BookingCompletionProcessorInterface;
use App\Core\Logger;
use App\DTO\BookingSelection;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\SlotFullException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Repositories\BookingRepository;
use App\Validation\BookingLifecycleValidator;
use App\Validation\BookingValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final readonly class BookingService
{
    public function __construct(
        private BookingRepository $bookings,
        private BookingValidator $validator,
        private BookingWindowPolicy $windowPolicy,
        private PriceCalculator $priceCalculator,
        private BookingResourceCalculator $resourceCalculator,
        private DateTimeZone $timezone,
        private BookingLifecyclePolicy $lifecyclePolicy = new BookingLifecyclePolicy(),
        private BookingLifecycleValidator $lifecycleValidator = new BookingLifecycleValidator(),
        private ?BookingCompletionProcessorInterface $completionProcessor = null,
        private ?Logger $logger = null
    ) {
    }

    /** @return array<string, mixed> */
    public function formData(int $ownerId, string $selectedVehicleId = ''): array
    {
        $vehicles = $this->bookings->findActiveVehiclesByOwner($ownerId);

        if ($vehicles === []) {
            return [
                'vehicles' => [],
                'selected_vehicle' => null,
                'services' => [],
                'slots' => [],
            ];
        }

        $vehicleId = $selectedVehicleId === ''
            ? (int) $vehicles[0]['id']
            : $this->formVehicleId($selectedVehicleId);
        $context = $this->bookings->findOwnedVehicleContext($vehicleId, $ownerId);

        if (
            $context === null
            || !(bool) $context['is_active']
            || !(bool) $context['vehicle_type_active']
        ) {
            throw new VehicleOwnershipException();
        }

        $slots = array_map(function (array $slot) use ($context): array {
            try {
                $leadDays = $this->windowPolicy->assertAllowed(
                    (string) $slot['slot_date'],
                    (int) $context['booking_window_days']
                );
                $withinWindow = true;
            } catch (BookingWindowExceededException) {
                $leadDays = null;
                $withinWindow = false;
            }

            $slot['lead_days'] = $leadDays;
            $slot['within_window'] = $withinWindow;

            return $slot;
        }, $this->bookings->findAvailableSlots());

        return [
            'vehicles' => $vehicles,
            'selected_vehicle' => $context,
            'services' => $this->bookings->findServicesForVehicleType((int) $context['vehicle_type_id']),
            'slots' => $slots,
        ];
    }

    /**
     * @param mixed $serviceIds
     */
    public function create(
        int $ownerId,
        string $vehicleId,
        string $startSlotId,
        mixed $serviceIds
    ): string {
        $selection = $this->validator->selection($vehicleId, $startSlotId, $serviceIds);
        $bookingCode = $this->bookingCode();

        return $this->bookings->transactional(function () use ($ownerId, $selection, $bookingCode): string {
            $context = $this->bookings->findOwnedVehicleContext(
                $selection->vehicleId,
                $ownerId,
                true
            );

            if ($context === null) {
                throw new VehicleOwnershipException();
            }

            if (!(bool) $context['is_active'] || !(bool) $context['vehicle_type_active']) {
                throw new ValidationException([
                    'vehicle_id' => 'Phương tiện hoặc loại phương tiện đã ngừng hoạt động.',
                ]);
            }

            $items = $this->bookings->lockServiceConfigurations(
                (int) $context['vehicle_type_id'],
                $selection->serviceIds
            );
            $this->assertAllServicesAvailable($selection, $items);
            $resources = $this->resourceCalculator->calculate(
                (int) $context['default_capacity_units'],
                $items
            );
            $durationMinutes = $resources->durationMinutes;
            $capacityUnits = $resources->capacityUnits;
            $price = $this->priceCalculator->calculate($items);
            $startSlot = $this->bookings->findStartSlot($selection->startSlotId);

            if ($startSlot === null) {
                throw new ValidationException(['start_slot_id' => 'Khung giờ không tồn tại.']);
            }

            $leadDays = $this->windowPolicy->assertAllowed(
                (string) $startSlot['slot_date'],
                (int) $context['booking_window_days']
            );
            $bookingStart = new DateTimeImmutable(
                (string) $startSlot['slot_date'] . ' ' . (string) $startSlot['start_time'],
                $this->timezone
            );
            $bookingEnd = $bookingStart->modify('+' . $durationMinutes . ' minutes');
            $startValue = $bookingStart->format('Y-m-d H:i:s');
            $endValue = $bookingEnd->format('Y-m-d H:i:s');
            $slots = $this->bookings->lockOverlappingSlots($startValue, $endValue);
            $slotIds = $this->validatedSlotIds(
                $selection->startSlotId,
                $slots,
                $bookingStart,
                $bookingEnd
            );

            if ($this->bookings->hasActiveVehicleOverlap($selection->vehicleId, $startValue, $endValue)) {
                throw new BookingConflictException();
            }

            $usage = $this->bookings->activeCapacityBySlot($slotIds);

            foreach ($slots as $slot) {
                $slotId = (int) $slot['id'];

                if (($usage[$slotId] ?? 0) + $capacityUnits > (int) $slot['capacity_units']) {
                    throw new SlotFullException();
                }
            }

            $bookingId = $this->bookings->insertBooking(
                $bookingCode,
                $ownerId,
                $selection->vehicleId,
                $selection->startSlotId,
                $durationMinutes,
                $capacityUnits,
                $price
            );
            $this->bookings->insertBookingItems($bookingId, $items);
            $this->bookings->insertReservations($bookingId, $slotIds, $capacityUnits);
            $this->bookings->insertBookingCreatedEvent(
                $bookingId,
                $this->bookings->anonymousUserKey($ownerId),
                (string) $context['tier_code'],
                (string) $context['vehicle_type_code'],
                $leadDays,
                $price->finalPrice,
                $items
            );

            return $bookingCode;
        });
    }

    /** @return array{bookings: list<array<string, mixed>>, history: list<array<string, mixed>>} */
    public function customerOverview(int $ownerId): array
    {
        return [
            'bookings' => array_map(
                fn (array $booking): array => $this->decorateBooking($booking),
                $this->bookings->findCustomerBookings($ownerId, false)
            ),
            'history' => array_map(
                fn (array $booking): array => $this->decorateBooking($booking),
                $this->bookings->findCustomerBookings($ownerId, true)
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function customerDetail(int $ownerId, int $bookingId): array
    {
        $booking = $this->bookings->findBookingForOwner($bookingId, $ownerId);

        if ($booking === null) {
            throw new BookingNotFoundException();
        }

        return $this->decorateBooking($booking) + [
            'items' => $this->bookings->findBookingItems($bookingId),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function adminBookings(): array
    {
        return array_map(function (array $booking): array {
            $booking = $this->decorateBooking($booking);
            $allowed = $this->lifecyclePolicy->allowedTargets((string) $booking['status']);
            $booking['can_confirm'] = in_array('confirmed', $allowed, true);
            $booking['can_complete'] = in_array('completed', $allowed, true);
            $booking['can_cancel'] = in_array('cancelled', $allowed, true);
            $booking['can_no_show'] = in_array('no_show', $allowed, true);

            return $booking;
        }, $this->bookings->findAdminBookings());
    }

    public function cancelByCustomer(
        int $ownerId,
        int $bookingId,
        ?DateTimeImmutable $now = null
    ): void {
        $now ??= new DateTimeImmutable('now', $this->timezone);
        $fromStatus = $this->bookings->transactional(function () use (
            $ownerId,
            $bookingId,
            $now
        ): string {
            $booking = $this->bookings->lockBooking($bookingId, $ownerId);

            if ($booking === null) {
                throw new BookingNotFoundException();
            }

            $currentStatus = (string) $booking['status'];
            $this->lifecyclePolicy->assertTransition($currentStatus, 'cancelled');
            $this->lifecyclePolicy->assertCustomerCancellation(
                new DateTimeImmutable((string) $booking['starts_at'], $this->timezone),
                $now
            );
            $this->bookings->markCancelled($bookingId, 'Khách hàng tự hủy lịch đặt.');

            return $currentStatus;
        });
        $this->logTransition($bookingId, $ownerId, $fromStatus, 'cancelled');
    }

    public function confirmByAdmin(int $adminId, int $bookingId): void
    {
        $this->transitionByAdmin($adminId, $bookingId, 'confirmed');
    }

    public function completeByAdmin(int $adminId, int $bookingId): void
    {
        $this->transitionByAdmin($adminId, $bookingId, 'completed');
    }

    public function markNoShowByAdmin(int $adminId, int $bookingId): void
    {
        $this->transitionByAdmin($adminId, $bookingId, 'no_show');
    }

    public function cancelByAdmin(int $adminId, int $bookingId, string $reason): void
    {
        $reason = $this->lifecycleValidator->cancellationReason($reason);
        $this->transitionByAdmin($adminId, $bookingId, 'cancelled', $reason);
    }

    /** @param list<array<string, mixed>> $items */
    private function assertAllServicesAvailable(BookingSelection $selection, array $items): void
    {
        $foundIds = array_map(static fn (array $item): int => (int) $item['service_id'], $items);
        sort($foundIds);

        if ($foundIds !== $selection->serviceIds) {
            throw new ValidationException([
                'service_ids' => (
                    'Có dịch vụ không hoạt động hoặc không hỗ trợ loại phương tiện đã chọn.'
                ),
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $slots
     * @return list<int>
     */
    private function validatedSlotIds(
        int $startSlotId,
        array $slots,
        DateTimeImmutable $bookingStart,
        DateTimeImmutable $bookingEnd
    ): array {
        $selectedStartFound = false;
        $coverageEnd = $bookingStart;
        $slotIds = [];

        foreach ($slots as $slot) {
            $slotStart = new DateTimeImmutable((string) $slot['starts_at'], $this->timezone);
            $slotEnd = new DateTimeImmutable((string) $slot['ends_at'], $this->timezone);

            if ((int) $slot['id'] === $startSlotId && $slotStart == $bookingStart) {
                $selectedStartFound = true;
            }

            if ((string) $slot['status'] !== 'open') {
                throw new ValidationException([
                    'start_slot_id' => 'Khoảng thời gian đặt lịch có khung giờ đã đóng.',
                ]);
            }

            if ($slotStart > $coverageEnd) {
                throw new ValidationException([
                    'start_slot_id' => (
                        'Không có đủ khung giờ liên tục cho thời lượng dịch vụ đã chọn.'
                    ),
                ]);
            }

            if ($slotEnd > $coverageEnd) {
                $coverageEnd = $slotEnd;
            }

            $slotIds[] = (int) $slot['id'];
        }

        if (!$selectedStartFound) {
            throw new ValidationException(['start_slot_id' => 'Khung giờ bắt đầu không còn khả dụng.']);
        }

        if ($coverageEnd < $bookingEnd) {
            throw new ValidationException([
                'start_slot_id' => (
                    'Không có đủ khung giờ liên tục cho thời lượng dịch vụ đã chọn.'
                ),
            ]);
        }

        return $slotIds;
    }

    private function formVehicleId(string $value): int
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new VehicleOwnershipException();
        }

        return (int) $value;
    }

    private function bookingCode(): string
    {
        return 'AW' . date('ymd') . strtoupper(bin2hex(random_bytes(8)));
    }

    private function transitionByAdmin(
        int $adminId,
        int $bookingId,
        string $targetStatus,
        ?string $reason = null
    ): void {
        $fromStatus = $this->bookings->transactional(function () use (
            $adminId,
            $bookingId,
            $targetStatus,
            $reason
        ): string {
            $booking = $this->bookings->lockBooking($bookingId);

            if ($booking === null) {
                throw new BookingNotFoundException();
            }

            $currentStatus = (string) $booking['status'];
            $this->lifecyclePolicy->assertTransition($currentStatus, $targetStatus);

            if ($targetStatus === 'completed') {
                $this->bookings->markCompleted($bookingId);

                if ($this->completionProcessor !== null) {
                    $this->completionProcessor->process($booking);
                }
            } elseif ($targetStatus === 'cancelled' && $reason !== null) {
                $this->bookings->markCancelled($bookingId, $reason);
                $this->bookings->insertTransitionAudit(
                    $adminId,
                    $bookingId,
                    $currentStatus,
                    $targetStatus,
                    $reason
                );
            } else {
                $this->bookings->markStatus($bookingId, $targetStatus);
            }

            return $currentStatus;
        });
        $this->logTransition($bookingId, $adminId, $fromStatus, $targetStatus);
    }

    /** @param array<string, mixed> $booking @return array<string, mixed> */
    private function decorateBooking(array $booking): array
    {
        $status = (string) $booking['status'];
        $start = new DateTimeImmutable((string) $booking['starts_at'], $this->timezone);
        $now = new DateTimeImmutable('now', $this->timezone);
        $canTransitionToCancelled = in_array(
            'cancelled',
            $this->lifecyclePolicy->allowedTargets($status),
            true
        );
        $booking['status_label'] = match ($status) {
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Đã hoàn thành',
            'cancelled' => 'Đã hủy',
            'no_show' => 'Không đến',
            default => 'Không xác định',
        };
        $booking['status_class'] = match ($status) {
            'pending' => 'status-pending',
            'confirmed' => 'status-confirmed',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            'no_show' => 'status-no-show',
            default => 'status-neutral',
        };
        $booking['can_cancel_customer'] = $canTransitionToCancelled
            && $this->lifecyclePolicy->customerCanCancel($start, $now);

        return $booking;
    }

    private function logTransition(
        int $bookingId,
        int $actorId,
        string $fromStatus,
        string $toStatus
    ): void {
        if ($this->logger === null) {
            return;
        }

        try {
            $this->logger->info('Trạng thái lịch đặt đã thay đổi.', [
                'booking_id' => $bookingId,
                'actor_id' => $actorId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ]);
        } catch (Throwable) {
            // Lỗi ghi log sau commit không được làm sai kết quả chuyển trạng thái đã hoàn tất.
        }
    }
}
