<?php

declare(strict_types=1);

namespace App\Database;

use App\Services\LicensePlateService;
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
            $this->seedRewards($data['rewards']);
            $this->seedRewardVehicleRestrictions();
            $this->database->commit();
        } catch (Throwable $throwable) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $throwable;
        }
    }

    /** @param list<array{string, string, string, string, string}> $users */
    private function seedUsers(array $users): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO users (
                current_tier_id, phone, full_name, password_hash, role, status
            ) VALUES (
                :current_tier_id, :phone, :full_name, :password_hash, :role, 'active'
            )
            ON DUPLICATE KEY UPDATE
                current_tier_id = VALUES(current_tier_id),
                full_name = VALUES(full_name),
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($users as [$phone, $fullName, $password, $role, $tierCode]) {
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

    /** @param list<array{string, string, string, int, string, ?string, int}> $rewards */
    private function seedRewards(array $rewards): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO rewards (
                code, name, reward_type, points_cost, value, service_id,
                minimum_tier_id, valid_days_after_redeem, is_active
            ) VALUES (
                :code, :name, :reward_type, :points_cost, :value, :service_id,
                NULL, :valid_days_after_redeem, TRUE
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                reward_type = VALUES(reward_type),
                points_cost = VALUES(points_cost),
                value = VALUES(value),
                service_id = VALUES(service_id),
                valid_days_after_redeem = VALUES(valid_days_after_redeem),
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($rewards as [$code, $name, $type, $points, $value, $serviceCode, $validDays]) {
            $statement->execute([
                'code' => $code,
                'name' => $name,
                'reward_type' => $type,
                'points_cost' => $points,
                'value' => $value,
                'service_id' => $serviceCode === null ? null : $this->idByCode('services', $serviceCode),
                'valid_days_after_redeem' => $validDays,
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
        $allowedTables = ['tiers', 'services', 'vehicle_types', 'rewards'];

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
}
