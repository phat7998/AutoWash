<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class WashSlotRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findAvailable(?string $date = null): array
    {
        $sql = $this->availabilitySelect()
            . " WHERE wash_slots.status = 'open' AND wash_slots.slot_date >= CURRENT_DATE()";
        $parameters = [];

        if ($date !== null) {
            $sql .= ' AND wash_slots.slot_date = :slot_date';
            $parameters['slot_date'] = $date;
        }

        $sql .= ' GROUP BY wash_slots.id ORDER BY wash_slots.slot_date, wash_slots.start_time';
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findAll(): array
    {
        $statement = $this->database->query(
            $this->availabilitySelect()
            . ' GROUP BY wash_slots.id ORDER BY wash_slots.slot_date DESC, wash_slots.start_time'
        );

        return $statement->fetchAll();
    }

    public function create(
        string $date,
        string $startTime,
        string $endTime,
        int $capacityUnits
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, :capacity_units, 'open')
            SQL
        );
        $statement->execute([
            'slot_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'capacity_units' => $capacityUnits,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function close(int $slotId): bool
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE wash_slots
            SET status = 'closed', updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND status = 'open'
            SQL
        );
        $statement->execute(['id' => $slotId]);

        return $statement->rowCount() === 1;
    }

    public function exists(int $slotId): bool
    {
        $statement = $this->database->prepare('SELECT 1 FROM wash_slots WHERE id = :id');
        $statement->execute(['id' => $slotId]);

        return $statement->fetchColumn() !== false;
    }

    private function availabilitySelect(): string
    {
        return <<<'SQL'
        SELECT
            wash_slots.id,
            wash_slots.slot_date,
            wash_slots.start_time,
            wash_slots.end_time,
            wash_slots.capacity_units,
            wash_slots.status,
            COALESCE(SUM(
                CASE
                    WHEN bookings.status IN ('pending', 'confirmed')
                    THEN booking_slot_reservations.capacity_units_reserved
                    ELSE 0
                END
            ), 0) AS used_capacity_units,
            GREATEST(
                wash_slots.capacity_units - COALESCE(SUM(
                    CASE
                        WHEN bookings.status IN ('pending', 'confirmed')
                        THEN booking_slot_reservations.capacity_units_reserved
                        ELSE 0
                    END
                ), 0),
                0
            ) AS remaining_capacity_units
        FROM wash_slots
        LEFT JOIN booking_slot_reservations
            ON booking_slot_reservations.wash_slot_id = wash_slots.id
        LEFT JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
        SQL;
    }
}
