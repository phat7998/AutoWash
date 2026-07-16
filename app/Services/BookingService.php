<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookingSelection;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\SlotFullException;
use App\Exceptions\ValidationException;
use App\Exceptions\VehicleOwnershipException;
use App\Repositories\BookingRepository;
use App\Validation\BookingValidator;
use DateTimeImmutable;
use DateTimeZone;

final readonly class BookingService
{
    public function __construct(
        private BookingRepository $bookings,
        private BookingValidator $validator,
        private BookingWindowPolicy $windowPolicy,
        private PriceCalculator $priceCalculator,
        private BookingResourceCalculator $resourceCalculator,
        private DateTimeZone $timezone
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
}
