<?php

declare(strict_types=1);

use App\Core\Database;

// phpcs:disable PSR1.Files.SideEffects -- CLI này vừa điều phối vừa khai báo helper nội bộ.

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$app = require $projectRoot . '/config/app.php';
date_default_timezone_set((string) $app['timezone']);

$options = getopt('', ['bookings::', 'users::']);
$bookingCount = filter_var($options['bookings'] ?? 10000, FILTER_VALIDATE_INT);
$userCount = filter_var($options['users'] ?? 20, FILTER_VALIDATE_INT);

if (!in_array($app['environment'], ['local', 'testing'], true)) {
    fwrite(STDERR, "Chỉ được chuẩn bị workload trong môi trường local hoặc testing.\n");
    exit(1);
}

if (!is_int($bookingCount) || $bookingCount < 10000 || !is_int($userCount) || $userCount < 20) {
    fwrite(STDERR, "Workload yêu cầu tối thiểu 10.000 booking và 20 virtual user.\n");
    exit(1);
}

try {
    $database = Database::connection();
    $existing = (int) $database->query(
        "SELECT COUNT(*) FROM bookings WHERE booking_code LIKE 'PERF\\_%' ESCAPE '\\\\'"
    )->fetchColumn();

    if ($existing > 0) {
        throw new RuntimeException(
            'Đã có dữ liệu PERF_. Hãy chạy database/reset.php --force --seed '
                . 'trước để kết quả tái lập.'
        );
    }

    $source = performanceSource($database);
    $timezone = new DateTimeZone((string) $app['timezone']);
    $today = new DateTimeImmutable('today', $timezone);
    $futureDate = $today->modify('+1 day')->format('Y-m-d');
    $historyDate = $today->modify('-60 days')->format('Y-m-d');
    $passwordHash = password_hash('Performance@123', PASSWORD_BCRYPT);

    if (!is_string($passwordHash)) {
        throw new RuntimeException('Không thể tạo mật khẩu băm cho workload.');
    }

    $database->beginTransaction();
    $futureSlotId = upsertSlot(
        $database,
        $futureDate,
        '16:00:00',
        '17:00:00',
        max(100, $userCount * 2)
    );
    $historySlotId = upsertSlot($database, $historyDate, '09:00:00', '10:00:00', $bookingCount);
    $users = seedPerformanceUsers($database, $source, $userCount, $passwordHash);
    seedPerformanceCredits($database, $users);
    seedPerformanceBookings($database, $source, $users, $historySlotId, $historyDate, $bookingCount);
    $database->commit();

    echo json_encode([
        'bookings' => $bookingCount,
        'users' => $userCount,
        'history_slot_id' => $historySlotId,
        'workload_slot_id' => $futureSlotId,
        'workload_slot_date' => $futureDate,
        'password' => 'Performance@123',
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $throwable) {
    if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }

    fwrite(STDERR, 'Chuẩn bị workload thất bại: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

/** @return array<string, int|string> */
function performanceSource(PDO $database): array
{
    $statement = $database->query(
        <<<'SQL'
        SELECT
            tiers.id AS tier_id,
            vehicle_types.id AS vehicle_type_id,
            vehicle_types.code AS vehicle_type_code,
            vehicle_types.default_capacity_units,
            services.id AS service_id,
            services.name AS service_name,
            service_vehicle_prices.id AS price_id,
            service_vehicle_prices.price,
            service_vehicle_prices.duration_minutes,
            GREATEST(
                vehicle_types.default_capacity_units,
                COALESCE(service_vehicle_prices.capacity_units_override, 0)
            ) AS capacity_units
        FROM tiers
        INNER JOIN vehicle_types ON vehicle_types.code = 'motorbike'
        INNER JOIN services ON services.code = 'STANDARD_WASH'
        INNER JOIN service_vehicle_prices
            ON service_vehicle_prices.service_id = services.id
            AND service_vehicle_prices.vehicle_type_id = vehicle_types.id
        WHERE tiers.code = 'PLATINUM'
          AND tiers.is_active = TRUE
          AND vehicle_types.is_active = TRUE
          AND services.is_active = TRUE
          AND service_vehicle_prices.is_supported = TRUE
          AND service_vehicle_prices.is_active = TRUE
        LIMIT 1
        SQL
    );
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('Thiếu cấu hình Platinum/motorbike/STANDARD_WASH cho workload.');
    }

    return $row;
}

function upsertSlot(PDO $database, string $date, string $start, string $end, int $capacity): int
{
    $statement = $database->prepare(
        <<<'SQL'
        INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
        VALUES (:slot_date, :start_time, :end_time, :capacity, 'open')
        ON DUPLICATE KEY UPDATE
            capacity_units = VALUES(capacity_units), status = 'open', updated_at = CURRENT_TIMESTAMP
        SQL
    );
    $statement->execute([
        'slot_date' => $date,
        'start_time' => $start,
        'end_time' => $end,
        'capacity' => $capacity,
    ]);
    $lookup = $database->prepare(
        'SELECT id FROM wash_slots WHERE slot_date = :date AND start_time = :start AND end_time = :end'
    );
    $lookup->execute(['date' => $date, 'start' => $start, 'end' => $end]);

    return (int) $lookup->fetchColumn();
}

/**
 * @param array<string, int|string> $source
 * @return list<array{id: int, vehicle_id: int, phone: string}>
 */
function seedPerformanceUsers(PDO $database, array $source, int $count, string $passwordHash): array
{
    $insertUser = $database->prepare(
        <<<'SQL'
        INSERT INTO users (
            current_tier_id, phone, full_name, password_hash, role,
            monthly_spend, monthly_visits, point_balance, status
        ) VALUES (
            :tier_id, :phone, :full_name, :password_hash, 'customer',
            0, 0, 2000, 'active'
        )
        SQL
    );
    $insertVehicle = $database->prepare(
        <<<'SQL'
        INSERT INTO vehicles (
            user_id, vehicle_type_id, normalized_plate, display_plate, brand, model, notes, is_active
        ) VALUES (
            :user_id, :vehicle_type_id, :normalized_plate, :display_plate, 'Honda', 'Performance',
            'Fixture kiểm thử hiệu năng Slice 15', TRUE
        )
        SQL
    );
    $users = [];

    for ($index = 1; $index <= $count; $index++) {
        $phone = sprintf('0988%06d', $index);
        $plate = sprintf('88P%05d', $index);
        $insertUser->execute([
            'tier_id' => $source['tier_id'],
            'phone' => $phone,
            'full_name' => sprintf('Khách hiệu năng %02d', $index),
            'password_hash' => $passwordHash,
        ]);
        $userId = (int) $database->lastInsertId();
        $insertVehicle->execute([
            'user_id' => $userId,
            'vehicle_type_id' => $source['vehicle_type_id'],
            'normalized_plate' => $plate,
            'display_plate' => $plate,
        ]);
        $users[] = [
            'id' => $userId,
            'vehicle_id' => (int) $database->lastInsertId(),
            'phone' => $phone,
        ];
    }

    return $users;
}

/** @param list<array{id: int, vehicle_id: int, phone: string}> $users */
function seedPerformanceCredits(PDO $database, array $users): void
{
    $statement = $database->prepare(
        <<<'SQL'
        INSERT INTO loyalty_transactions (
            user_id, type, points_delta, remaining_points, source_type, source_id,
            description, created_at, updated_at
        ) VALUES (
            :user_id, 'adjust_credit', 2000, 2000, 'performance_seed', :source_id,
            'Credit lot tái lập cho workload Slice 15.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
        SQL
    );

    foreach ($users as $user) {
        $statement->execute(['user_id' => $user['id'], 'source_id' => $user['id']]);
    }
}

/**
 * @param array<string, int|string> $source
 * @param list<array{id: int, vehicle_id: int, phone: string}> $users
 */
function seedPerformanceBookings(
    PDO $database,
    array $source,
    array $users,
    int $slotId,
    string $slotDate,
    int $count
): void {
    $insertBooking = $database->prepare(
        <<<'SQL'
        INSERT INTO bookings (
            booking_code, user_id, vehicle_id, start_slot_id, status,
            booking_duration_minutes, booking_capacity_units, subtotal, final_price,
            completed_at, loyalty_processed_at, created_at, updated_at
        ) VALUES (
            :booking_code, :user_id, :vehicle_id, :slot_id, 'completed',
            :duration, :capacity, :subtotal, :final_price,
            :completed_at, :loyalty_at, :created_at, :updated_at
        )
        SQL
    );
    $insertItem = $database->prepare(
        <<<'SQL'
        INSERT INTO booking_items (
            booking_id, service_id, service_vehicle_price_id, service_name_snapshot,
            vehicle_type_code_snapshot, unit_price_snapshot, duration_minutes_snapshot,
            capacity_units_snapshot, quantity, line_total, created_at
        ) VALUES (
            :booking_id, :service_id, :price_id, :service_name,
            :vehicle_type_code, :unit_price, :duration, :capacity, 1, :line_total, :occurred_at
        )
        SQL
    );
    $insertReservation = $database->prepare(
        <<<'SQL'
        INSERT INTO booking_slot_reservations (
            booking_id, wash_slot_id, capacity_units_reserved, created_at, updated_at
        ) VALUES (
            :booking_id, :slot_id, :capacity, :created_at, :updated_at
        )
        SQL
    );

    foreach (range(1, $count) as $index) {
        $user = $users[($index - 1) % count($users)];
        $occurredAt = sprintf('%s %02d:%02d:00', $slotDate, 8 + ($index % 10), $index % 60);
        $insertBooking->execute([
            'booking_code' => sprintf('PERF_%05d', $index),
            'user_id' => $user['id'],
            'vehicle_id' => $user['vehicle_id'],
            'slot_id' => $slotId,
            'duration' => $source['duration_minutes'],
            'capacity' => $source['capacity_units'],
            'subtotal' => $source['price'],
            'final_price' => $source['price'],
            'completed_at' => $occurredAt,
            'loyalty_at' => $occurredAt,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
        $bookingId = (int) $database->lastInsertId();
        $parameters = [
            'booking_id' => $bookingId,
            'service_id' => $source['service_id'],
            'price_id' => $source['price_id'],
            'service_name' => $source['service_name'],
            'vehicle_type_code' => $source['vehicle_type_code'],
            'unit_price' => $source['price'],
            'line_total' => $source['price'],
            'duration' => $source['duration_minutes'],
            'capacity' => $source['capacity_units'],
            'occurred_at' => $occurredAt,
        ];
        $insertItem->execute($parameters);
        $insertReservation->execute([
            'booking_id' => $bookingId,
            'slot_id' => $slotId,
            'capacity' => $source['capacity_units'],
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }
}
