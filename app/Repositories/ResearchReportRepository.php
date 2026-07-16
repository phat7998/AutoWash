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
        $revenue = $this->database->query(
            <<<'SQL'
            SELECT
                COALESCE(SUM(CASE WHEN DATE(completed_at) = CURRENT_DATE THEN final_price ELSE 0 END), 0)
                    AS today_revenue,
                COALESCE(SUM(final_price), 0) AS completed_revenue
            FROM bookings
            WHERE status = 'completed'
            SQL
        )->fetch();
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
        $tiers = $this->database->query(
            <<<'SQL'
            SELECT tiers.code, tiers.name, COUNT(users.id) AS total
            FROM tiers
            LEFT JOIN users ON users.current_tier_id = tiers.id AND users.role = 'customer'
            GROUP BY tiers.id
            ORDER BY tiers.rank_order
            SQL
        )->fetchAll();
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
            'revenue' => is_array($revenue) ? $revenue : [],
            'slots' => is_array($slots) ? $slots : [],
            'tiers' => $tiers,
            'points' => $points,
            'usage' => is_array($usage) ? $usage : [],
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
}
