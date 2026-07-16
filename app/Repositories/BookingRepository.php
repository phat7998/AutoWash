<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\BookingPrice;
use PDO;
use Throwable;

final readonly class BookingRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findActiveVehiclesByOwner(int $ownerId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                vehicles.id,
                vehicles.display_plate,
                vehicles.brand,
                vehicles.model,
                vehicles.vehicle_type_id,
                vehicle_types.code AS vehicle_type_code,
                vehicle_types.display_name AS vehicle_type_name
            FROM vehicles
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            WHERE vehicles.user_id = :owner_id
              AND vehicles.is_active = TRUE
              AND vehicle_types.is_active = TRUE
            ORDER BY vehicles.created_at, vehicles.id
            SQL
        );
        $statement->execute(['owner_id' => $ownerId]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findOwnedVehicleContext(int $vehicleId, int $ownerId, bool $lock = false): ?array
    {
        if ($lock) {
            $lockStatement = $this->database->prepare(
                'SELECT id FROM vehicles WHERE id = :vehicle_id AND user_id = :owner_id FOR UPDATE'
            );
            $lockStatement->execute(['vehicle_id' => $vehicleId, 'owner_id' => $ownerId]);

            if ($lockStatement->fetchColumn() === false) {
                return null;
            }
        }

        $sql = <<<'SQL'
            SELECT
                vehicles.id,
                vehicles.user_id,
                vehicles.vehicle_type_id,
                vehicles.display_plate,
                vehicles.is_active,
                vehicle_types.code AS vehicle_type_code,
                vehicle_types.display_name AS vehicle_type_name,
                vehicle_types.default_capacity_units,
                vehicle_types.is_active AS vehicle_type_active,
                tiers.code AS tier_code,
                tiers.name AS tier_name,
                tiers.id AS tier_id,
                tiers.rank_order AS tier_rank,
                tiers.booking_window_days
            FROM vehicles
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN users ON users.id = vehicles.user_id
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE vehicles.id = :vehicle_id
              AND vehicles.user_id = :owner_id
              AND users.status = 'active'
            LIMIT 1
            SQL;

        $statement = $this->database->prepare($sql);
        $statement->execute(['vehicle_id' => $vehicleId, 'owner_id' => $ownerId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function findServicesForVehicleType(int $vehicleTypeId): array
    {
        $statement = $this->database->prepare(
            $this->serviceConfigurationSelect()
            . <<<'SQL'
             WHERE service_vehicle_prices.vehicle_type_id = :vehicle_type_id
               AND services.is_active = TRUE
               AND service_vehicle_prices.is_supported = TRUE
               AND service_vehicle_prices.is_active = TRUE
               AND vehicle_types.is_active = TRUE
             ORDER BY services.name, services.id
            SQL
        );
        $statement->execute(['vehicle_type_id' => $vehicleTypeId]);

        return $statement->fetchAll();
    }

    /**
     * @param list<int> $serviceIds
     * @return list<array<string, mixed>>
     */
    public function lockServiceConfigurations(int $vehicleTypeId, array $serviceIds): array
    {
        $placeholders = implode(', ', array_fill(0, count($serviceIds), '?'));
        $statement = $this->database->prepare(
            $this->serviceConfigurationSelect()
            . <<<SQL
             WHERE service_vehicle_prices.vehicle_type_id = ?
               AND services.id IN ({$placeholders})
               AND services.is_active = TRUE
               AND service_vehicle_prices.is_supported = TRUE
               AND service_vehicle_prices.is_active = TRUE
               AND vehicle_types.is_active = TRUE
             ORDER BY services.id
             FOR SHARE
            SQL
        );
        $statement->execute([$vehicleTypeId, ...$serviceIds]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findAvailableSlots(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT
                wash_slots.id,
                wash_slots.slot_date,
                wash_slots.start_time,
                wash_slots.end_time,
                wash_slots.capacity_units,
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
            WHERE wash_slots.status = 'open' AND wash_slots.slot_date >= CURRENT_DATE()
            GROUP BY wash_slots.id
            ORDER BY wash_slots.slot_date, wash_slots.start_time, wash_slots.id
            SQL
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findCustomerBookings(int $ownerId, bool $completed): array
    {
        $statusCondition = $completed ? "bookings.status = 'completed'" : "bookings.status <> 'completed'";
        $statement = $this->database->prepare(
            $this->bookingSummarySelect(false) . "\n"
            . <<<SQL
            WHERE bookings.user_id = :owner_id
              AND {$statusCondition}
            ORDER BY wash_slots.slot_date DESC, wash_slots.start_time DESC, bookings.id DESC
            SQL
        );
        $statement->execute(['owner_id' => $ownerId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findAdminBookings(): array
    {
        return $this->database->query(
            $this->bookingSummarySelect(true)
            . "\n ORDER BY wash_slots.slot_date DESC, wash_slots.start_time DESC, bookings.id DESC"
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findBookingForOwner(int $bookingId, int $ownerId): ?array
    {
        $statement = $this->database->prepare(
            $this->bookingSummarySelect(false)
            . "\n WHERE bookings.id = :booking_id AND bookings.user_id = :owner_id LIMIT 1"
        );
        $statement->execute(['booking_id' => $bookingId, 'owner_id' => $ownerId]);
        $booking = $statement->fetch();

        return is_array($booking) ? $booking : null;
    }

    /** @return list<array<string, mixed>> */
    public function findBookingItems(int $bookingId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                service_name_snapshot,
                vehicle_type_code_snapshot,
                unit_price_snapshot,
                duration_minutes_snapshot,
                capacity_units_snapshot,
                quantity,
                line_total
            FROM booking_items
            WHERE booking_id = :booking_id
            ORDER BY id
            SQL
        );
        $statement->execute(['booking_id' => $bookingId]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function lockBooking(int $bookingId, ?int $ownerId = null): ?array
    {
        $sql = <<<'SQL'
            SELECT
                bookings.*,
                TIMESTAMP(wash_slots.slot_date, wash_slots.start_time) AS starts_at,
                wash_slots.slot_date,
                wash_slots.start_time,
                wash_slots.end_time
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE bookings.id = :booking_id
            SQL;
        $parameters = ['booking_id' => $bookingId];

        if ($ownerId !== null) {
            $sql .= ' AND bookings.user_id = :owner_id';
            $parameters['owner_id'] = $ownerId;
        }

        $statement = $this->database->prepare($sql . ' LIMIT 1 FOR UPDATE');
        $statement->execute($parameters);
        $booking = $statement->fetch();

        return is_array($booking) ? $booking : null;
    }

    public function markStatus(int $bookingId, string $status): void
    {
        $statement = $this->database->prepare(
            'UPDATE bookings SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['status' => $status, 'id' => $bookingId]);
    }

    public function markCompleted(int $bookingId): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE bookings
            SET status = 'completed', completed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
            SQL
        );
        $statement->execute(['id' => $bookingId]);
    }

    public function markCancelled(int $bookingId, string $reason): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE bookings
            SET
                status = 'cancelled',
                cancelled_at = CURRENT_TIMESTAMP,
                cancellation_reason = :reason,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
            SQL
        );
        $statement->execute(['reason' => $reason, 'id' => $bookingId]);
    }

    public function updateResearchCancellationStatus(int $bookingId, string $status): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE research_event_logs
            SET cancellation_status = :status
            WHERE event_key = :event_key AND event_type = 'booking_created'
            SQL
        );
        $statement->execute([
            'status' => $status,
            'event_key' => 'booking_created:' . $bookingId,
        ]);
    }

    public function insertTransitionAudit(
        int $actorId,
        int $bookingId,
        string $fromStatus,
        string $toStatus,
        string $reason
    ): void {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO audit_logs (
                actor_user_id, action, target_type, target_id, before_json, after_json, reason
            ) VALUES (
                :actor_id,
                'booking_cancelled',
                'booking',
                :booking_id,
                :before_json,
                :after_json,
                :reason
            )
            SQL
        );
        $statement->execute([
            'actor_id' => $actorId,
            'booking_id' => $bookingId,
            'before_json' => json_encode(['status' => $fromStatus], JSON_THROW_ON_ERROR),
            'after_json' => json_encode(['status' => $toStatus], JSON_THROW_ON_ERROR),
            'reason' => $reason,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findStartSlot(int $slotId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT id, slot_date, start_time, end_time, capacity_units, status
            FROM wash_slots
            WHERE id = :id
            LIMIT 1
            SQL
        );
        $statement->execute(['id' => $slotId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function lockOverlappingSlots(string $bookingStart, string $bookingEnd): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                id,
                slot_date,
                start_time,
                end_time,
                capacity_units,
                status,
                TIMESTAMP(slot_date, start_time) AS starts_at,
                TIMESTAMP(slot_date, end_time) AS ends_at
            FROM wash_slots
            WHERE TIMESTAMP(slot_date, start_time) < :booking_end
              AND TIMESTAMP(slot_date, end_time) > :booking_start
            ORDER BY slot_date, start_time, id
            FOR UPDATE
            SQL
        );
        $statement->execute([
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
        ]);

        return $statement->fetchAll();
    }

    /** @param list<int> $slotIds @return array<int, int> */
    public function activeCapacityBySlot(array $slotIds): array
    {
        $placeholders = implode(', ', array_fill(0, count($slotIds), '?'));
        $statement = $this->database->prepare(
            <<<SQL
            SELECT
                booking_slot_reservations.wash_slot_id,
                booking_slot_reservations.capacity_units_reserved
            FROM booking_slot_reservations
            INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
            WHERE booking_slot_reservations.wash_slot_id IN ({$placeholders})
              AND bookings.status IN ('pending', 'confirmed')
            ORDER BY booking_slot_reservations.wash_slot_id, booking_slot_reservations.id
            FOR UPDATE
            SQL
        );
        $statement->execute($slotIds);
        $usage = [];

        foreach ($statement->fetchAll() as $row) {
            $slotId = (int) $row['wash_slot_id'];
            $usage[$slotId] = ($usage[$slotId] ?? 0) + (int) $row['capacity_units_reserved'];
        }

        return $usage;
    }

    public function hasActiveVehicleOverlap(int $vehicleId, string $bookingStart, string $bookingEnd): bool
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT bookings.id
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE bookings.vehicle_id = :vehicle_id
              AND bookings.status IN ('pending', 'confirmed')
              AND TIMESTAMP(wash_slots.slot_date, wash_slots.start_time) < :booking_end
              AND DATE_ADD(
                    TIMESTAMP(wash_slots.slot_date, wash_slots.start_time),
                    INTERVAL bookings.booking_duration_minutes MINUTE
                  ) > :booking_start
            ORDER BY bookings.id
            LIMIT 1
            FOR UPDATE
            SQL
        );
        $statement->execute([
            'vehicle_id' => $vehicleId,
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function insertBooking(
        string $bookingCode,
        int $ownerId,
        int $vehicleId,
        int $startSlotId,
        int $durationMinutes,
        int $capacityUnits,
        BookingPrice $price
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO bookings (
                booking_code,
                user_id,
                vehicle_id,
                start_slot_id,
                status,
                booking_duration_minutes,
                booking_capacity_units,
                subtotal,
                perk_discount,
                promotion_discount,
                reward_discount,
                final_price,
                promotion_id
            ) VALUES (
                :booking_code,
                :user_id,
                :vehicle_id,
                :start_slot_id,
                'pending',
                :duration_minutes,
                :capacity_units,
                :subtotal,
                :perk_discount,
                :promotion_discount,
                :reward_discount,
                :final_price,
                :promotion_id
            )
            SQL
        );
        $statement->execute([
            'booking_code' => $bookingCode,
            'user_id' => $ownerId,
            'vehicle_id' => $vehicleId,
            'start_slot_id' => $startSlotId,
            'duration_minutes' => $durationMinutes,
            'capacity_units' => $capacityUnits,
            'subtotal' => $price->subtotal,
            'perk_discount' => $price->perkDiscount,
            'promotion_discount' => $price->promotionDiscount,
            'reward_discount' => $price->rewardDiscount,
            'final_price' => $price->finalPrice,
            'promotion_id' => $price->promotionId,
        ]);

        return (int) $this->database->lastInsertId();
    }

    /** @param list<array<string, mixed>> $items */
    public function insertBookingItems(int $bookingId, array $items): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO booking_items (
                booking_id,
                service_id,
                service_vehicle_price_id,
                service_name_snapshot,
                vehicle_type_code_snapshot,
                unit_price_snapshot,
                duration_minutes_snapshot,
                capacity_units_snapshot,
                quantity,
                line_total
            ) VALUES (
                :booking_id,
                :service_id,
                :price_id,
                :service_name,
                :vehicle_type_code,
                :unit_price,
                :duration_minutes,
                :capacity_units,
                1,
                :line_total
            )
            SQL
        );

        foreach ($items as $item) {
            $statement->execute([
                'booking_id' => $bookingId,
                'service_id' => $item['service_id'],
                'price_id' => $item['price_id'],
                'service_name' => $item['service_name'],
                'vehicle_type_code' => $item['vehicle_type_code'],
                'unit_price' => $item['price'],
                'duration_minutes' => $item['duration_minutes'],
                'capacity_units' => $item['capacity_units'],
                'line_total' => $item['price'],
            ]);
        }
    }

    /** @param list<int> $slotIds */
    public function insertReservations(int $bookingId, array $slotIds, int $capacityUnits): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO booking_slot_reservations (booking_id, wash_slot_id, capacity_units_reserved)
            VALUES (:booking_id, :slot_id, :capacity_units)
            SQL
        );

        foreach ($slotIds as $slotId) {
            $statement->execute([
                'booking_id' => $bookingId,
                'slot_id' => $slotId,
                'capacity_units' => $capacityUnits,
            ]);
        }
    }

    public function anonymousUserKey(int $ownerId): string
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT SHA2(CONCAT(id, ':', created_at, ':', phone), 256)
            FROM users
            WHERE id = :id
            SQL
        );
        $statement->execute(['id' => $ownerId]);

        return (string) $statement->fetchColumn();
    }

    /** @param list<array<string, mixed>> $items */
    public function insertBookingCreatedEvent(
        int $bookingId,
        string $anonymousUserKey,
        string $tierCode,
        string $vehicleTypeCode,
        int $leadDays,
        string $orderValue,
        array $items,
        bool $usedReward = false,
        bool $usedPromotion = false
    ): void {
        $serviceCodes = array_values(array_map(
            static fn (array $item): string => (string) $item['service_code'],
            $items
        ));
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO research_event_logs (
                event_key,
                anonymous_user_key,
                event_type,
                event_time,
                tier_code,
                vehicle_type_code,
                service_code,
                booking_lead_days,
                order_value,
                used_reward,
                used_promotion,
                data_source,
                metadata_json
            ) VALUES (
                :event_key,
                :anonymous_user_key,
                'booking_created',
                CURRENT_TIMESTAMP,
                :tier_code,
                :vehicle_type_code,
                :service_code,
                :booking_lead_days,
                :order_value,
                :used_reward,
                :used_promotion,
                'system',
                :metadata_json
            )
            SQL
        );
        $statement->execute([
            'event_key' => 'booking_created:' . $bookingId,
            'anonymous_user_key' => $anonymousUserKey,
            'tier_code' => $tierCode,
            'vehicle_type_code' => $vehicleTypeCode,
            'service_code' => count($serviceCodes) === 1 ? $serviceCodes[0] : null,
            'booking_lead_days' => $leadDays,
            'order_value' => $orderValue,
            'used_reward' => $usedReward ? 1 : 0,
            'used_promotion' => $usedPromotion ? 1 : 0,
            'metadata_json' => json_encode(
                ['service_codes' => $serviceCodes],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
        ]);
    }

    public function transactional(callable $callback): mixed
    {
        $this->database->beginTransaction();

        try {
            $result = $callback();
            $this->database->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $throwable;
        }
    }

    private function serviceConfigurationSelect(): string
    {
        return <<<'SQL'
            SELECT
                services.id AS service_id,
                services.code AS service_code,
                services.name AS service_name,
                services.description,
                service_vehicle_prices.id AS price_id,
                service_vehicle_prices.price,
                service_vehicle_prices.duration_minutes,
                GREATEST(
                    vehicle_types.default_capacity_units,
                    COALESCE(service_vehicle_prices.capacity_units_override, 0)
                ) AS capacity_units,
                vehicle_types.code AS vehicle_type_code
            FROM service_vehicle_prices
            INNER JOIN services ON services.id = service_vehicle_prices.service_id
            INNER JOIN vehicle_types ON vehicle_types.id = service_vehicle_prices.vehicle_type_id
            SQL;
    }

    private function bookingSummarySelect(bool $includeCustomer): string
    {
        $customerColumns = $includeCustomer
            ? ', users.full_name AS customer_name'
            : '';

        return <<<SQL
            SELECT
                bookings.id,
                bookings.booking_code,
                bookings.user_id,
                bookings.status,
                bookings.booking_duration_minutes,
                bookings.booking_capacity_units,
                bookings.subtotal,
                bookings.perk_discount,
                bookings.promotion_discount,
                bookings.reward_discount,
                bookings.final_price,
                bookings.completed_at,
                bookings.cancelled_at,
                bookings.cancellation_reason,
                bookings.loyalty_processed_at,
                bookings.created_at,
                vehicles.display_plate,
                vehicles.brand,
                vehicles.model,
                vehicle_types.display_name AS vehicle_type_name,
                wash_slots.slot_date,
                wash_slots.start_time,
                wash_slots.end_time,
                TIMESTAMP(wash_slots.slot_date, wash_slots.start_time) AS starts_at,
                DATE_ADD(
                    TIMESTAMP(wash_slots.slot_date, wash_slots.start_time),
                    INTERVAL bookings.booking_duration_minutes MINUTE
                ) AS ends_at,
                item_summaries.service_names,
                item_summaries.item_count
                {$customerColumns}
            FROM bookings
            INNER JOIN users ON users.id = bookings.user_id
            INNER JOIN vehicles ON vehicles.id = bookings.vehicle_id
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            INNER JOIN (
                SELECT
                    booking_id,
                    GROUP_CONCAT(service_name_snapshot ORDER BY id SEPARATOR ', ') AS service_names,
                    COUNT(*) AS item_count
                FROM booking_items
                GROUP BY booking_id
            ) AS item_summaries ON item_summaries.booking_id = bookings.id
            SQL;
    }
}
