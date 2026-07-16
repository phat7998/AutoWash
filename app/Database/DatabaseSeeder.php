<?php

declare(strict_types=1);

namespace App\Database;

use App\Services\LicensePlateService;
use App\Services\LoyaltyExpirationPolicy;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

final readonly class DatabaseSeeder
{
    public function __construct(
        private PDO $database,
        private string $seedFile
    ) {
    }

    public function seed(): void
    {
        $data = require $this->seedFile;

        if (!is_array($data)) {
            throw new RuntimeException('Dữ liệu seed không hợp lệ.');
        }

        $this->database->beginTransaction();

        try {
            $this->seedSettings($data['settings']);
            $this->seedTiers($data['tiers']);
            $this->seedUsers($data['users']);
            $this->seedVehicleTypes($data['vehicle_types']);
            $this->seedVehicles($data['vehicles']);
            $this->seedServices($data['services']);
            $this->seedServicePrices($data['service_vehicle_prices']);
            $this->seedWashSlots($data['wash_slots']);
            $this->seedRelativeWashSlots($data['relative_wash_slots'] ?? []);
            $this->seedCapacityFixtures($data['capacity_fixtures']);
            $this->seedRewards($data['rewards']);
            $this->seedRewardVehicleRestrictions();
            $this->seedTierPerks($data['tier_perks'] ?? []);
            $this->seedPromotions($data['promotions'] ?? []);
            $this->seedLoyaltyCreditLots($data['loyalty_credit_lots'] ?? []);
            $this->database->commit();
        } catch (Throwable $throwable) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $throwable;
        }
    }

    /** @param list<array{string, string, int, int, int|null}> $creditLots */
    private function seedLoyaltyCreditLots(array $creditLots): void
    {
        $schemaReady = $this->database->query(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'loyalty_allocations'
              AND column_name = 'credit_transaction_id'
            SQL
        )->fetchColumn();

        if ((int) $schemaReady !== 1) {
            return;
        }

        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $expiration = new LoyaltyExpirationPolicy($timezone);
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT IGNORE INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                description, earned_at, expires_at, created_at, updated_at
            ) VALUES (
                :user_id, :type, :points, :remaining_points, 'demo_seed', :source_id,
                :description, :earned_at, :expires_at, :created_at, :updated_at
            )
            SQL
        );
        $userIds = [];

        foreach ($creditLots as [$phone, $type, $points, $sourceId, $expiresInDays]) {
            $userId = $this->idByPhone($phone);
            $createdAt = new DateTimeImmutable('now', $timezone);
            $earnedAt = null;
            $expiresAt = null;

            if ($type === 'earn') {
                $earnedAt = $createdAt->modify('-1 year')->modify('+' . (int) $expiresInDays . ' days');
                $expiresAt = $expiration->expiresAt($earnedAt);
                $createdAt = $earnedAt;
            }

            $statement->execute([
                'user_id' => $userId,
                'type' => $type,
                'points' => $points,
                'remaining_points' => $points,
                'source_id' => $sourceId,
                'description' => $type === 'earn'
                    ? 'Credit lot demo sắp hết hạn.'
                    : 'Điều chỉnh tăng điểm dành cho demo reward.',
                'earned_at' => $earnedAt?->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $createdAt->format('Y-m-d H:i:s'),
            ]);
            $userIds[$userId] = $userId;
        }

        $balance = $this->database->prepare(
            <<<'SQL'
            UPDATE users
            SET point_balance = (
                SELECT COALESCE(SUM(points_delta), 0)
                FROM loyalty_transactions
                WHERE loyalty_transactions.user_id = users.id
            ), updated_at = CURRENT_TIMESTAMP
            WHERE id = :user_id
            SQL
        );

        foreach ($userIds as $userId) {
            $balance->execute(['user_id' => $userId]);
        }
    }

    /** @param list<array{string, string, string, string, string, string, int}> $users */
    private function seedUsers(array $users): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO users (
                current_tier_id, phone, full_name, password_hash, role,
                monthly_spend, monthly_visits, status
            ) VALUES (
                :current_tier_id, :phone, :full_name, :password_hash, :role,
                :monthly_spend, :monthly_visits, 'active'
            )
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($users as $user) {
            [
                $phone,
                $fullName,
                $password,
                $role,
                $tierCode,
                $monthlySpend,
                $monthlyVisits,
            ] = $user;
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            if (!is_string($passwordHash)) {
                throw new RuntimeException('Không thể tạo mật khẩu băm cho tài khoản demo.');
            }

            $statement->execute([
                'current_tier_id' => $this->idByCode('tiers', $tierCode),
                'phone' => $phone,
                'full_name' => $fullName,
                'password_hash' => $passwordHash,
                'role' => $role,
                'monthly_spend' => $monthlySpend,
                'monthly_visits' => $monthlyVisits,
            ]);
        }
    }

    /** @param list<array{string, string, string}> $settings */
    private function seedSettings(array $settings): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO app_settings (setting_key, setting_value, value_type)
            VALUES (:setting_key, :setting_value, :value_type)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                value_type = VALUES(value_type),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($settings as [$key, $value, $type]) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
                'value_type' => $type,
            ]);
        }
    }

    /** @param list<array{string, string, int, int, string, int, string}> $tiers */
    private function seedTiers(array $tiers): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO tiers (
                code, name, rank_order, booking_window_days, min_monthly_spend,
                min_monthly_visits, point_rate, is_active
            ) VALUES (
                :code, :name, :rank_order, :booking_window_days, :min_monthly_spend,
                :min_monthly_visits, :point_rate, TRUE
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                rank_order = VALUES(rank_order),
                booking_window_days = VALUES(booking_window_days),
                min_monthly_spend = VALUES(min_monthly_spend),
                min_monthly_visits = VALUES(min_monthly_visits),
                point_rate = VALUES(point_rate),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($tiers as $tier) {
            $statement->execute(array_combine(
                [
                    'code',
                    'name',
                    'rank_order',
                    'booking_window_days',
                    'min_monthly_spend',
                    'min_monthly_visits',
                    'point_rate',
                ],
                $tier
            ));
        }
    }

    /** @param list<array{string, string, int, int}> $vehicleTypes */
    private function seedVehicleTypes(array $vehicleTypes): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO vehicle_types (
                code, display_name, default_duration_minutes, default_capacity_units, is_active
            ) VALUES (
                :code, :display_name, :default_duration_minutes, :default_capacity_units, TRUE
            )
            ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                default_duration_minutes = VALUES(default_duration_minutes),
                default_capacity_units = VALUES(default_capacity_units),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($vehicleTypes as $vehicleType) {
            $statement->execute(array_combine(
                ['code', 'display_name', 'default_duration_minutes', 'default_capacity_units'],
                $vehicleType
            ));
        }
    }

    /** @param list<array{string, string, string, ?string, ?string, ?string}> $vehicles */
    private function seedVehicles(array $vehicles): void
    {
        $plates = new LicensePlateService();
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO vehicles (
                user_id, vehicle_type_id, normalized_plate, display_plate, brand, model, notes, is_active
            ) VALUES (
                :user_id, :vehicle_type_id, :normalized_plate, :display_plate, :brand, :model, :notes, TRUE
            )
            ON DUPLICATE KEY UPDATE
                vehicle_type_id = IF(user_id = VALUES(user_id), VALUES(vehicle_type_id), vehicle_type_id),
                display_plate = IF(user_id = VALUES(user_id), VALUES(display_plate), display_plate),
                brand = IF(user_id = VALUES(user_id), VALUES(brand), brand),
                model = IF(user_id = VALUES(user_id), VALUES(model), model),
                notes = IF(user_id = VALUES(user_id), VALUES(notes), notes),
                is_active = IF(user_id = VALUES(user_id), TRUE, is_active),
                updated_at = IF(user_id = VALUES(user_id), CURRENT_TIMESTAMP, updated_at)
            SQL
        );

        foreach ($vehicles as [$phone, $typeCode, $plate, $brand, $model, $notes]) {
            $normalizedPlate = $plates->normalize($plate);

            if (!$plates->isCommonCivilianPlate($normalizedPlate)) {
                throw new RuntimeException('Biển số seed không thuộc phạm vi dân sự thông dụng.');
            }

            $statement->execute([
                'user_id' => $this->idByPhone($phone),
                'vehicle_type_id' => $this->idByCode('vehicle_types', $typeCode),
                'normalized_plate' => $normalizedPlate,
                'display_plate' => $plates->display($plate),
                'brand' => $brand,
                'model' => $model,
                'notes' => $notes,
            ]);
        }
    }

    /** @param list<array{string, string, string}> $services */
    private function seedServices(array $services): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO services (code, name, description, is_active)
            VALUES (:code, :name, :description, TRUE)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($services as [$code, $name, $description]) {
            $statement->execute(compact('code', 'name', 'description'));
        }
    }

    /** @param list<array{string, string, ?string, ?int, ?int, bool}> $prices */
    private function seedServicePrices(array $prices): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO service_vehicle_prices (
                service_id, vehicle_type_id, price, duration_minutes,
                capacity_units_override, is_supported, is_active
            ) VALUES (
                :service_id, :vehicle_type_id, :price, :duration_minutes,
                :capacity_units_override, :is_supported, TRUE
            )
            ON DUPLICATE KEY UPDATE
                price = VALUES(price),
                duration_minutes = VALUES(duration_minutes),
                capacity_units_override = VALUES(capacity_units_override),
                is_supported = VALUES(is_supported),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($prices as [$serviceCode, $vehicleTypeCode, $price, $duration, $capacity, $supported]) {
            $statement->execute([
                'service_id' => $this->idByCode('services', $serviceCode),
                'vehicle_type_id' => $this->idByCode('vehicle_types', $vehicleTypeCode),
                'price' => $price,
                'duration_minutes' => $duration,
                'capacity_units_override' => $capacity,
                'is_supported' => $supported ? 1 : 0,
            ]);
        }
    }

    /** @param list<array{string, string, string, int, string}> $slots */
    private function seedWashSlots(array $slots): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, :capacity_units, :status)
            ON DUPLICATE KEY UPDATE
                capacity_units = VALUES(capacity_units),
                status = VALUES(status),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($slots as $slot) {
            $statement->execute(array_combine(
                ['slot_date', 'start_time', 'end_time', 'capacity_units', 'status'],
                $slot
            ));
        }
    }

    /** @param list<array{int, string, string, int, string}> $slots */
    private function seedRelativeWashSlots(array $slots): void
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $normalized = array_map(static fn (array $slot): array => [
            $today->modify('+' . $slot[0] . ' days')->format('Y-m-d'),
            $slot[1],
            $slot[2],
            $slot[3],
            $slot[4],
        ], $slots);
        $this->seedWashSlots($normalized);
    }

    /** @param list<array{string, string, string, string, string, string}> $fixtures */
    private function seedCapacityFixtures(array $fixtures): void
    {
        foreach ($fixtures as [$code, $phone, $plate, $date, $startTime, $serviceCode]) {
            $source = $this->capacityFixtureSource($phone, $plate, $date, $startTime, $serviceCode);
            $booking = $this->database->prepare(
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
                    final_price
                ) VALUES (
                    :booking_code,
                    :user_id,
                    :vehicle_id,
                    :slot_id,
                    'pending',
                    :duration_minutes,
                    :capacity_units,
                    :subtotal,
                    :final_price
                )
                ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id),
                    vehicle_id = VALUES(vehicle_id),
                    start_slot_id = VALUES(start_slot_id),
                    status = 'pending',
                    booking_duration_minutes = VALUES(booking_duration_minutes),
                    booking_capacity_units = VALUES(booking_capacity_units),
                    subtotal = VALUES(subtotal),
                    final_price = VALUES(final_price),
                    updated_at = CURRENT_TIMESTAMP
                SQL
            );
            $booking->execute([
                'booking_code' => $code,
                'user_id' => $source['user_id'],
                'vehicle_id' => $source['vehicle_id'],
                'slot_id' => $source['slot_id'],
                'duration_minutes' => $source['duration_minutes'],
                'capacity_units' => $source['capacity_units'],
                'subtotal' => $source['price'],
                'final_price' => $source['price'],
            ]);
            $bookingId = $this->idByBookingCode($code);
            $this->seedCapacityBookingItem($bookingId, $source);
            $reservation = $this->database->prepare(
                <<<'SQL'
                INSERT INTO booking_slot_reservations (booking_id, wash_slot_id, capacity_units_reserved)
                VALUES (:booking_id, :slot_id, :capacity_units)
                ON DUPLICATE KEY UPDATE
                    capacity_units_reserved = VALUES(capacity_units_reserved),
                    updated_at = CURRENT_TIMESTAMP
                SQL
            );
            $reservation->execute([
                'booking_id' => $bookingId,
                'slot_id' => $source['slot_id'],
                'capacity_units' => $source['capacity_units'],
            ]);
        }
    }

    /** @return array<string, int|string> */
    private function capacityFixtureSource(
        string $phone,
        string $plate,
        string $date,
        string $startTime,
        string $serviceCode
    ): array {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT
                users.id AS user_id,
                vehicles.id AS vehicle_id,
                wash_slots.id AS slot_id,
                services.id AS service_id,
                services.name AS service_name,
                vehicle_types.code AS vehicle_type_code,
                service_vehicle_prices.id AS price_id,
                service_vehicle_prices.price,
                service_vehicle_prices.duration_minutes,
                GREATEST(
                    vehicle_types.default_capacity_units,
                    COALESCE(service_vehicle_prices.capacity_units_override, 0)
                ) AS capacity_units
            FROM users
            INNER JOIN vehicles ON vehicles.user_id = users.id AND vehicles.normalized_plate = :plate
            INNER JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
            INNER JOIN services ON services.code = :service_code
            INNER JOIN service_vehicle_prices
                ON service_vehicle_prices.service_id = services.id
                AND service_vehicle_prices.vehicle_type_id = vehicle_types.id
            INNER JOIN wash_slots
                ON wash_slots.slot_date = :slot_date
                AND wash_slots.start_time = :start_time
            WHERE users.phone = :phone
            LIMIT 1
            SQL
        );
        $statement->execute([
            'phone' => $phone,
            'plate' => $plate,
            'slot_date' => $date,
            'start_time' => $startTime,
            'service_code' => $serviceCode,
        ]);
        $source = $statement->fetch();

        if (!is_array($source)) {
            throw new RuntimeException('Không thể dựng fixture capacity từ dữ liệu seed.');
        }

        return $source;
    }

    /** @param array<string, int|string> $source */
    private function seedCapacityBookingItem(int $bookingId, array $source): void
    {
        $find = $this->database->prepare(
            'SELECT id FROM booking_items WHERE booking_id = :booking_id AND service_id = :service_id LIMIT 1'
        );
        $find->execute(['booking_id' => $bookingId, 'service_id' => $source['service_id']]);
        $itemId = $find->fetchColumn();
        $parameters = [
            'booking_id' => $bookingId,
            'service_id' => $source['service_id'],
            'price_id' => $source['price_id'],
            'service_name' => $source['service_name'],
            'vehicle_type_code' => $source['vehicle_type_code'],
            'unit_price' => $source['price'],
            'line_total' => $source['price'],
            'duration_minutes' => $source['duration_minutes'],
            'capacity_units' => $source['capacity_units'],
        ];

        if ($itemId === false) {
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
        } else {
            $statement = $this->database->prepare(
                <<<'SQL'
                UPDATE booking_items
                SET
                    service_vehicle_price_id = :price_id,
                    service_name_snapshot = :service_name,
                    vehicle_type_code_snapshot = :vehicle_type_code,
                    unit_price_snapshot = :unit_price,
                    duration_minutes_snapshot = :duration_minutes,
                    capacity_units_snapshot = :capacity_units,
                    quantity = 1,
                    line_total = :line_total
                WHERE id = :id AND booking_id = :booking_id AND service_id = :service_id
                SQL
            );
            $parameters['id'] = $itemId;
        }

        $statement->execute($parameters);
    }

    /** @param list<array{string, string, string, int, string, ?string, int, ?string}> $rewards */
    private function seedRewards(array $rewards): void
    {
        $hasMaxDiscount = (int) $this->database->query(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = 'rewards'
              AND column_name = 'max_discount'
            SQL
        )->fetchColumn() === 1;
        $maxColumn = $hasMaxDiscount ? ', max_discount' : '';
        $maxValue = $hasMaxDiscount ? ', :max_discount' : '';
        $maxUpdate = $hasMaxDiscount ? ', max_discount = VALUES(max_discount)' : '';
        $statement = $this->database->prepare(
            <<<SQL
            INSERT INTO rewards (
                code, name, reward_type, points_cost, value{$maxColumn}, service_id,
                minimum_tier_id, valid_days_after_redeem, is_active
            ) VALUES (
                :code, :name, :reward_type, :points_cost, :value{$maxValue}, :service_id,
                NULL, :valid_days_after_redeem, TRUE
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                reward_type = VALUES(reward_type),
                points_cost = VALUES(points_cost),
                value = VALUES(value){$maxUpdate},
                service_id = VALUES(service_id),
                valid_days_after_redeem = VALUES(valid_days_after_redeem),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($rewards as [$code, $name, $type, $points, $value, $serviceCode, $validDays, $maxDiscount]) {
            $parameters = [
                'code' => $code,
                'name' => $name,
                'reward_type' => $type,
                'points_cost' => $points,
                'value' => $value,
                'service_id' => $serviceCode === null ? null : $this->idByCode('services', $serviceCode),
                'valid_days_after_redeem' => $validDays,
            ];
            if ($hasMaxDiscount) {
                $parameters['max_discount'] = $maxDiscount;
            }
            $statement->execute($parameters);
        }
    }

    /** @param list<array{string, string, string, ?string}> $perks */
    private function seedTierPerks(array $perks): void
    {
        $find = $this->database->prepare(
            'SELECT id FROM tier_perks WHERE tier_id = :tier_id AND perk_type = :perk_type '
            . 'AND service_id <=> :service_id ORDER BY id LIMIT 1'
        );
        $insert = $this->database->prepare(
            'INSERT INTO tier_perks (tier_id, perk_type, value, service_id, is_active) '
            . 'VALUES (:tier_id, :perk_type, :value, :service_id, TRUE)'
        );
        $update = $this->database->prepare(
            'UPDATE tier_perks SET value = :value, is_active = TRUE, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE id = :id'
        );
        foreach ($perks as [$tierCode, $type, $value, $serviceCode]) {
            $parameters = [
                'tier_id' => $this->idByCode('tiers', $tierCode),
                'perk_type' => $type,
                'service_id' => $serviceCode === null ? null : $this->idByCode('services', $serviceCode),
            ];
            $find->execute($parameters);
            $id = $find->fetchColumn();
            if ($id === false) {
                $insert->execute($parameters + ['value' => $value]);
            } else {
                $update->execute(['value' => $value, 'id' => $id]);
            }
        }
    }

    /** @param list<array<int, mixed>> $promotions */
    private function seedPromotions(array $promotions): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new DateTimeImmutable('now', $timezone);
        $upsert = $this->database->prepare(
            <<<'SQL'
            INSERT INTO promotions (
                code, name, discount_type, discount_value, max_discount, minimum_order_value,
                start_at, end_at, usage_limit, per_user_limit, is_active
            ) VALUES (
                :code, :name, :type, :value, :max_discount, :minimum_order,
                :start_at, :end_at, :usage_limit, :per_user_limit, TRUE
            ) ON DUPLICATE KEY UPDATE name = VALUES(name), discount_type = VALUES(discount_type),
                discount_value = VALUES(discount_value), max_discount = VALUES(max_discount),
                minimum_order_value = VALUES(minimum_order_value), start_at = VALUES(start_at),
                end_at = VALUES(end_at), usage_limit = VALUES(usage_limit),
                per_user_limit = VALUES(per_user_limit), is_active = TRUE, updated_at = CURRENT_TIMESTAMP
            SQL
        );
        foreach ($promotions as $promotion) {
            [$code, $name, $type, $value, $max, $minimum, $startDays, $endDays,
                $usageLimit, $userLimit, $tierCodes, $serviceCodes, $vehicleCodes] = $promotion;
            $upsert->execute([
                'code' => $code, 'name' => $name, 'type' => $type, 'value' => $value,
                'max_discount' => $max, 'minimum_order' => $minimum,
                'start_at' => $now->modify($startDays . ' days')->format('Y-m-d H:i:s'),
                'end_at' => $now->modify('+' . $endDays . ' days')->format('Y-m-d H:i:s'),
                'usage_limit' => $usageLimit, 'per_user_limit' => $userLimit,
            ]);
            $promotionId = $this->idByCode('promotions', $code);
            $this->seedPromotionRelations($promotionId, 'promotion_tiers', 'tier_id', 'tiers', $tierCodes);
            $this->seedPromotionRelations($promotionId, 'promotion_services', 'service_id', 'services', $serviceCodes);
            $this->seedPromotionRelations(
                $promotionId,
                'promotion_vehicle_types',
                'vehicle_type_id',
                'vehicle_types',
                $vehicleCodes
            );
        }
    }

    /** @param list<string> $codes */
    private function seedPromotionRelations(
        int $promotionId,
        string $table,
        string $column,
        string $sourceTable,
        array $codes
    ): void {
        $delete = $this->database->prepare("DELETE FROM {$table} WHERE promotion_id = :id");
        $delete->execute(['id' => $promotionId]);
        $insert = $this->database->prepare(
            "INSERT INTO {$table} (promotion_id, {$column}) VALUES (:promotion_id, :relation_id)"
        );
        foreach ($codes as $code) {
            $insert->execute([
                'promotion_id' => $promotionId,
                'relation_id' => $this->idByCode($sourceTable, $code),
            ]);
        }
    }

    private function seedRewardVehicleRestrictions(): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO reward_vehicle_types (reward_id, vehicle_type_id)
            VALUES (:reward_id, :vehicle_type_id)
            ON DUPLICATE KEY UPDATE vehicle_type_id = VALUES(vehicle_type_id)
            SQL
        );
        $statement->execute([
            'reward_id' => $this->idByCode('rewards', 'FREE_MOTORBIKE_STANDARD'),
            'vehicle_type_id' => $this->idByCode('vehicle_types', 'motorbike'),
        ]);
    }

    private function idByCode(string $table, string $code): int
    {
        $allowedTables = ['tiers', 'services', 'vehicle_types', 'rewards', 'promotions'];

        if (!in_array($table, $allowedTables, true)) {
            throw new RuntimeException('Bảng seed không nằm trong allowlist.');
        }

        $statement = $this->database->prepare(sprintf('SELECT id FROM `%s` WHERE code = :code', $table));
        $statement->execute(['code' => $code]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(sprintf('Không tìm thấy mã seed %s trong bảng %s.', $code, $table));
        }

        return (int) $id;
    }

    private function idByPhone(string $phone): int
    {
        $statement = $this->database->prepare('SELECT id FROM users WHERE phone = :phone');
        $statement->execute(['phone' => $phone]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(sprintf(
                'Không tìm thấy tài khoản seed có số điện thoại %s.',
                $phone
            ));
        }

        return (int) $id;
    }

    private function idByBookingCode(string $code): int
    {
        $statement = $this->database->prepare('SELECT id FROM bookings WHERE booking_code = :code');
        $statement->execute(['code' => $code]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(sprintf('Không tìm thấy booking fixture có mã %s.', $code));
        }

        return (int) $id;
    }
}
