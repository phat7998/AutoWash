<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Repositories\WashSlotRepository;
use App\Validation\WashSlotValidator;
use PDOException;

final readonly class WashSlotService
{
    public function __construct(
        private WashSlotRepository $slots,
        private WashSlotValidator $validator
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function availableSlots(string $date): array
    {
        $date = trim($date);

        if ($date !== '') {
            $errors = $this->validator->validate($date, '00:00', '00:01', '1');

            if (isset($errors['slot_date'])) {
                throw new ValidationException(['slot_date' => $errors['slot_date']]);
            }
        }

        return $this->slots->findAvailable($date !== '' ? $date : null);
    }

    /** @return list<array<string, mixed>> */
    public function adminSlots(): array
    {
        return $this->slots->findAll();
    }

    public function create(string $date, string $startTime, string $endTime, string $capacity): int
    {
        $date = trim($date);
        $startTime = trim($startTime);
        $endTime = trim($endTime);
        $capacity = trim($capacity);
        $errors = $this->validator->validate($date, $startTime, $endTime, $capacity);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            return $this->slots->create(
                $date,
                (string) $this->validator->normalizeTime($startTime),
                (string) $this->validator->normalizeTime($endTime),
                (int) $capacity
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DuplicateCatalogException('Khung giờ này đã tồn tại.');
            }

            throw $exception;
        }
    }

    public function close(int $slotId): void
    {
        if (!$this->slots->exists($slotId)) {
            throw new ValidationException(['slot' => 'Không tìm thấy khung giờ được yêu cầu.']);
        }

        $this->slots->close($slotId);
    }
}
