<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class ResearchEventRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<string, mixed> */
    public function userContext(int $userId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                SHA2(CONCAT(users.id, ':', users.created_at, ':', users.phone), 256)
                    AS anonymous_user_key,
                tiers.code AS tier_code,
                users.monthly_spend,
                users.monthly_visits
            FROM users
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.id = :user_id AND users.role = 'customer'
            LIMIT 1
            SQL
        );
        $statement->execute(['user_id' => $userId]);
        $context = $statement->fetch();

        if (!is_array($context)) {
            throw new \RuntimeException('Không thể tạo ngữ cảnh nghiên cứu cho khách hàng.');
        }

        return $context;
    }

    /** @return array<string, mixed> */
    public function bookingContext(int $bookingId): array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT bookings.user_id, bookings.final_price AS order_value,
                vehicle_types.code AS vehicle_type_code,
                DATEDIFF(wash_slots.slot_date, DATE(bookings.created_at)) AS booking_lead_days,
                GROUP_CONCAT(services.code ORDER BY booking_items.id SEPARATOR ',') AS service_codes
            FROM bookings
            INNER JOIN vehicles ON vehicles.id = bookings.vehicle_id
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            INNER JOIN booking_items ON booking_items.booking_id = bookings.id
            INNER JOIN services ON services.id = booking_items.service_id
            WHERE bookings.id = :booking_id
            GROUP BY bookings.id, vehicle_types.id, wash_slots.id
            SQL
        );
        $statement->execute(['booking_id' => $bookingId]);
        $context = $statement->fetch();

        if (!is_array($context)) {
            throw new \RuntimeException('Không thể tạo ngữ cảnh nghiên cứu cho lịch đặt.');
        }

        return $context;
    }

    /** @param array<string, mixed> $event */
    public function insert(array $event): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO research_event_logs (
                event_key, anonymous_user_key, event_type, event_time, tier_code,
                tier_before_code, tier_after_code, vehicle_type_code, service_code,
                booking_lead_days, order_value, monthly_spend_snapshot,
                monthly_visits_snapshot, points_earned, points_redeemed,
                used_reward, used_promotion, cancellation_status, data_source, metadata_json
            ) VALUES (
                :event_key, :anonymous_user_key, :event_type, :event_time, :tier_code,
                :tier_before_code, :tier_after_code, :vehicle_type_code, :service_code,
                :booking_lead_days, :order_value, :monthly_spend_snapshot,
                :monthly_visits_snapshot, :points_earned, :points_redeemed,
                :used_reward, :used_promotion, :cancellation_status, 'system', :metadata_json
            )
            SQL
        );
        $statement->execute([
            'event_key' => $event['event_key'],
            'anonymous_user_key' => $event['anonymous_user_key'],
            'event_type' => $event['event_type'],
            'event_time' => $event['event_time'],
            'tier_code' => $event['tier_code'],
            'tier_before_code' => $event['tier_before_code'] ?? null,
            'tier_after_code' => $event['tier_after_code'] ?? null,
            'vehicle_type_code' => $event['vehicle_type_code'] ?? null,
            'service_code' => $event['service_code'] ?? null,
            'booking_lead_days' => $event['booking_lead_days'] ?? null,
            'order_value' => $event['order_value'] ?? null,
            'monthly_spend_snapshot' => $event['monthly_spend_snapshot'] ?? null,
            'monthly_visits_snapshot' => $event['monthly_visits_snapshot'] ?? null,
            'points_earned' => $event['points_earned'] ?? null,
            'points_redeemed' => $event['points_redeemed'] ?? null,
            'used_reward' => !empty($event['used_reward']) ? 1 : 0,
            'used_promotion' => !empty($event['used_promotion']) ? 1 : 0,
            'cancellation_status' => $event['cancellation_status'] ?? null,
            'metadata_json' => isset($event['metadata'])
                ? json_encode($event['metadata'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
                : null,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function exportRows(?string $from, ?string $to, ?string $source): array
    {
        $conditions = [];
        $parameters = [];

        if ($from !== null) {
            $conditions[] = 'events.event_time >= :from_date';
            $parameters['from_date'] = $from . ' 00:00:00';
        }
        if ($to !== null) {
            $conditions[] = 'events.event_time < DATE_ADD(:to_date, INTERVAL 1 DAY)';
            $parameters['to_date'] = $to . ' 00:00:00';
        }
        if ($source !== null) {
            $conditions[] = 'events.data_source = :data_source';
            $parameters['data_source'] = $source;
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                '1.0' AS schema_version,
                events.event_key,
                events.anonymous_user_key,
                events.event_type,
                DATE_FORMAT(events.event_time, '%Y-%m-%d %H:%i:%s') AS event_time,
                events.tier_code,
                events.tier_before_code,
                events.tier_after_code,
                events.vehicle_type_code,
                events.service_code,
                COALESCE(
                    events.service_code,
                    JSON_UNQUOTE(JSON_EXTRACT(events.metadata_json, '$.service_codes'))
                ) AS service_codes,
                events.booking_lead_days,
                events.order_value,
                events.monthly_spend_snapshot,
                events.monthly_visits_snapshot,
                events.points_earned,
                events.points_redeemed,
                events.used_reward,
                events.used_promotion,
                events.cancellation_status,
                DATEDIFF(
                    events.event_time,
                    (
                        SELECT MAX(previous_events.event_time)
                        FROM research_event_logs AS previous_events
                        WHERE previous_events.anonymous_user_key = events.anonymous_user_key
                          AND previous_events.event_type = 'booking_completed'
                          AND previous_events.event_time < events.event_time
                    )
                ) AS return_frequency_days,
                events.data_source
            FROM research_event_logs AS events
            SQL
            . ' ' . $where . ' ORDER BY events.event_time, events.id'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
