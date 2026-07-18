<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class ResearchReportRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<string, mixed> */
    public function adminMetrics(): array
    {
        $bookingStatus = $this->database->query(
            <<<'SQL'
            SELECT bookings.status, COUNT(*) AS total
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE wash_slots.slot_date = CURRENT_DATE
            GROUP BY bookings.status
            ORDER BY bookings.status
            SQL
        )->fetchAll();
        $revenue = $this->revenueMetrics();
        $slots = $this->database->query(
            <<<'SQL'
            SELECT COALESCE(SUM(wash_slots.capacity_units), 0) AS total_capacity,
                COALESCE(SUM(reservations.reserved_units), 0) AS reserved_capacity
            FROM wash_slots
            LEFT JOIN (
                SELECT booking_slot_reservations.wash_slot_id,
                    SUM(booking_slot_reservations.capacity_units_reserved) AS reserved_units
                FROM booking_slot_reservations
                INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
                WHERE bookings.status IN ('pending', 'confirmed', 'completed', 'no_show')
                GROUP BY booking_slot_reservations.wash_slot_id
            ) AS reservations ON reservations.wash_slot_id = wash_slots.id
            WHERE wash_slots.slot_date = CURRENT_DATE AND wash_slots.status = 'open'
            SQL
        )->fetch();
        $tiers = $this->tierDistribution();
        $points = $this->database->query(
            <<<'SQL'
            SELECT type, ABS(COALESCE(SUM(points_delta), 0)) AS points
            FROM loyalty_transactions
            WHERE type IN ('earn', 'redeem', 'expire')
              AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
            GROUP BY type
            ORDER BY type
            SQL
        )->fetchAll();
        $usage = $this->database->query(
            <<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM reward_redemptions
                 WHERE status = 'used' AND used_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')) AS rewards_used,
                (SELECT COUNT(*) FROM promotion_usages
                 WHERE used_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')) AS promotions_used
            SQL
        )->fetch();

        return [
            'booking_status' => $bookingStatus,
            'revenue' => $revenue,
            'slots' => is_array($slots) ? $slots : [],
            'tiers' => $tiers,
            'points' => $points,
            'usage' => is_array($usage) ? $usage : [],
        ];
    }

    /** @return array<string, mixed> */
    public function adminReportMetrics(string $fromAt, string $toExclusive): array
    {
        $bookingStatus = $this->database->prepare(
            <<<'SQL'
            SELECT bookings.status, COUNT(*) AS total
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE wash_slots.slot_date >= DATE(:from_at)
              AND wash_slots.slot_date < DATE(:to_exclusive)
            GROUP BY bookings.status
            ORDER BY bookings.status
            SQL
        );
        $bookingStatus->execute(['from_at' => $fromAt, 'to_exclusive' => $toExclusive]);

        $vehicleTypes = $this->database->prepare(
            <<<'SQL'
            SELECT booking_items.vehicle_type_code_snapshot AS code,
                COALESCE(vehicle_types.display_name, booking_items.vehicle_type_code_snapshot) AS name,
                COUNT(DISTINCT bookings.id) AS total
            FROM booking_items
            INNER JOIN bookings ON bookings.id = booking_items.booking_id
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            LEFT JOIN vehicle_types
                ON vehicle_types.code = booking_items.vehicle_type_code_snapshot
            WHERE wash_slots.slot_date >= DATE(:from_at)
              AND wash_slots.slot_date < DATE(:to_exclusive)
            GROUP BY booking_items.vehicle_type_code_snapshot, vehicle_types.display_name
            ORDER BY total DESC, name
            SQL
        );
        $vehicleTypes->execute(['from_at' => $fromAt, 'to_exclusive' => $toExclusive]);

        $services = $this->database->prepare(
            <<<'SQL'
            SELECT booking_items.service_id, booking_items.service_name_snapshot AS name,
                COUNT(DISTINCT bookings.id) AS total
            FROM booking_items
            INNER JOIN bookings ON bookings.id = booking_items.booking_id
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE wash_slots.slot_date >= DATE(:from_at)
              AND wash_slots.slot_date < DATE(:to_exclusive)
            GROUP BY booking_items.service_id, booking_items.service_name_snapshot
            ORDER BY total DESC, name
            SQL
        );
        $services->execute(['from_at' => $fromAt, 'to_exclusive' => $toExclusive]);

        $points = $this->database->prepare(
            <<<'SQL'
            SELECT
                COALESCE(SUM(CASE WHEN points_delta > 0 THEN points_delta ELSE 0 END), 0)
                    AS points_added,
                ABS(COALESCE(SUM(CASE WHEN points_delta < 0 THEN points_delta ELSE 0 END), 0))
                    AS points_deducted
            FROM loyalty_transactions
            WHERE created_at >= :from_at AND created_at < :to_exclusive
            SQL
        );
        $points->execute(['from_at' => $fromAt, 'to_exclusive' => $toExclusive]);

        $usage = $this->database->prepare(
            <<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM reward_redemptions
                 WHERE status = 'used' AND used_at >= :reward_from
                   AND used_at < :reward_to) AS rewards_used,
                (SELECT COUNT(*) FROM promotion_usages
                 WHERE used_at >= :promotion_from
                   AND used_at < :promotion_to) AS promotions_used
            SQL
        );
        $usage->execute([
            'reward_from' => $fromAt,
            'reward_to' => $toExclusive,
            'promotion_from' => $fromAt,
            'promotion_to' => $toExclusive,
        ]);

        return [
            'revenue' => $this->revenueMetrics($fromAt, $toExclusive),
            'booking_status' => $bookingStatus->fetchAll(),
            'vehicle_types' => $vehicleTypes->fetchAll(),
            'services' => $services->fetchAll(),
            'tiers' => $this->tierDistribution(),
            'points' => $this->fetchRow($points->fetch()),
            'usage' => $this->fetchRow($usage->fetch()),
        ];
    }

    /** @return array<string, mixed> */
    public function customerMetrics(int $userId): array
    {
        $latest = $this->database->prepare(
            <<<'SQL'
            SELECT bookings.booking_code AS code, bookings.status, bookings.final_price,
                wash_slots.slot_date, wash_slots.start_time
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE bookings.user_id = :user_id
            ORDER BY wash_slots.slot_date DESC, wash_slots.start_time DESC, bookings.id DESC
            LIMIT 1
            SQL
        );
        $latest->execute(['user_id' => $userId]);
        $latestBooking = $latest->fetch();

        $history = $this->database->prepare(
            <<<'SQL'
            SELECT bookings.booking_code AS code, bookings.final_price, bookings.completed_at,
                GROUP_CONCAT(booking_items.service_name_snapshot ORDER BY booking_items.id SEPARATOR ', ')
                    AS services
            FROM bookings
            INNER JOIN booking_items ON booking_items.booking_id = bookings.id
            WHERE bookings.user_id = :user_id AND bookings.status = 'completed'
            GROUP BY bookings.id
            ORDER BY bookings.completed_at DESC, bookings.id DESC
            LIMIT 3
            SQL
        );
        $history->execute(['user_id' => $userId]);

        $rewards = $this->database->prepare(
            <<<'SQL'
            SELECT COUNT(*)
            FROM reward_redemptions
            WHERE user_id = :user_id AND status = 'available'
              AND booking_id IS NULL AND expires_at > CURRENT_TIMESTAMP
            SQL
        );
        $rewards->execute(['user_id' => $userId]);

        return [
            'latest_booking' => is_array($latestBooking) ? $latestBooking : null,
            'wash_history' => $history->fetchAll(),
            'available_rewards' => (int) $rewards->fetchColumn(),
        ];
    }

    /** @return array<string, mixed> */
    private function revenueMetrics(?string $fromAt = null, ?string $toExclusive = null): array
    {
        if ($fromAt === null || $toExclusive === null) {
            return $this->fetchRow($this->database->query(
                <<<'SQL'
                SELECT
                    COALESCE(SUM(CASE WHEN DATE(completed_at) = CURRENT_DATE
                        THEN final_price ELSE 0 END), 0) AS today_revenue,
                    COALESCE(SUM(final_price), 0) AS completed_revenue
                FROM bookings
                WHERE status = 'completed'
                SQL
            )->fetch());
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                COALESCE(SUM(CASE WHEN DATE(completed_at) = CURRENT_DATE
                    THEN final_price ELSE 0 END), 0) AS today_revenue,
                COALESCE(SUM(final_price), 0) AS completed_revenue,
                COALESCE(SUM(CASE WHEN completed_at >= :from_at AND completed_at < :to_exclusive
                    THEN final_price ELSE 0 END), 0) AS range_revenue
            FROM bookings
            WHERE status = 'completed'
            SQL
        );
        $statement->execute(['from_at' => $fromAt, 'to_exclusive' => $toExclusive]);

        return $this->fetchRow($statement->fetch());
    }

    /** @return list<array<string, mixed>> */
    private function tierDistribution(): array
    {
        return $this->database->query(
            <<<'SQL'
            SELECT tiers.code, tiers.name, COUNT(users.id) AS total
            FROM tiers
            LEFT JOIN users ON users.current_tier_id = tiers.id AND users.role = 'customer'
            GROUP BY tiers.id
            ORDER BY tiers.rank_order
            SQL
        )->fetchAll();
    }

    /** @return array<string, mixed> */
    private function fetchRow(mixed $row): array
    {
        return is_array($row) ? $row : [];
    }
}
