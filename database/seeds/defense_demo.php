<?php

declare(strict_types=1);

return new class {
    private PDO $database;

    private DateTimeZone $timezone;

    private DateTimeImmutable $anchor;

    /** @var array<string, array<string, mixed>> */
    private array $tiers = [];

    /** @var array<string, array<string, mixed>> */
    private array $vehicleTypes = [];

    /** @var array<string, array<string, mixed>> */
    private array $services = [];

    /** @var array<string, array<string, mixed>> */
    private array $prices = [];

    /** @var array<int, int> */
    private array $customerIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $vehicleFixtures = [];

    /** @var array<int, array<string, mixed>> */
    private array $bookingFixtures = [];

    /** @var array<string, int> */
    private array $promotionIds = [];

    /** @var array<string, array<string, mixed>> */
    private array $rewards = [];

    /**
     * @return array<string, int|string>
     */
    public function seed(PDO $database, string $password): array
    {
        $this->database = $database;
        $this->timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $this->assertSafeRuntime();
        $this->loadReferenceData();
        $this->anchor = $this->resolveAnchor();

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Không thể tạo password hash cho tài khoản defense.');
        }

        $this->database->beginTransaction();

        try {
            $adminIds = $this->seedUsers($passwordHash);
            $this->seedPromotions();
            $this->seedRewards();
            $this->seedVehicles();
            $this->seedShowcaseSlots();
            $this->seedBookings();
            $redemptions = $this->seedRewardRedemptions();
            $this->seedPromotionUsages();
            $loyalty = $this->seedLoyalty($redemptions, $adminIds[1]);
            $this->seedTierHistories();
            $this->seedLprAttempts();
            $this->seedAuditLogs($adminIds);
            $this->seedResearchEvents($loyalty);
            $this->assertInvariants();
            $this->database->commit();
        } catch (Throwable $throwable) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $throwable;
        }

        return $this->resultCounts();
    }

    private function assertSafeRuntime(): void
    {
        $databaseName = (string) $this->database->query('SELECT DATABASE()')->fetchColumn();
        $configuredName = (string) ($_ENV['DB_NAME'] ?? '');
        $environment = (string) ($_ENV['APP_ENV'] ?? '');

        if ($databaseName === '' || $databaseName !== $configuredName) {
            throw new RuntimeException('Database kết nối không trùng DB_NAME trong .env.');
        }

        if ($environment === 'production') {
            throw new RuntimeException('Seeder defense không được chạy trong APP_ENV=production.');
        }
    }

    private function loadReferenceData(): void
    {
        foreach ($this->database->query('SELECT * FROM tiers WHERE is_active = TRUE')->fetchAll() as $row) {
            $this->tiers[(string) $row['code']] = $row;
        }

        foreach ($this->database->query('SELECT * FROM vehicle_types WHERE is_active = TRUE')->fetchAll() as $row) {
            $this->vehicleTypes[(string) $row['code']] = $row;
        }

        $serviceRows = $this->database->query(
            <<<'SQL'
            SELECT services.*, service_groups.code AS group_code,
                service_groups.selection_mode, service_groups.min_selection,
                service_groups.max_selection
            FROM services
            INNER JOIN service_groups ON service_groups.id = services.service_group_id
            WHERE services.is_active = TRUE AND service_groups.is_active = TRUE
            SQL
        )->fetchAll();

        foreach ($serviceRows as $row) {
            $this->services[(string) $row['code']] = $row;
        }

        $priceRows = $this->database->query(
            <<<'SQL'
            SELECT service_vehicle_prices.*, services.code AS service_code,
                services.name AS service_name, vehicle_types.code AS vehicle_type_code,
                vehicle_types.default_capacity_units
            FROM service_vehicle_prices
            INNER JOIN services ON services.id = service_vehicle_prices.service_id
            INNER JOIN vehicle_types ON vehicle_types.id = service_vehicle_prices.vehicle_type_id
            WHERE service_vehicle_prices.is_active = TRUE
            SQL
        )->fetchAll();

        foreach ($priceRows as $row) {
            $key = (string) $row['service_code'] . ':' . (string) $row['vehicle_type_code'];
            $this->prices[$key] = $row;
        }

        foreach (['MEMBER', 'SILVER', 'GOLD', 'PLATINUM'] as $code) {
            if (!isset($this->tiers[$code])) {
                throw new RuntimeException('Thiếu tier bắt buộc: ' . $code . '.');
            }
        }

        foreach (['motorbike', 'car', 'truck', 'bus'] as $code) {
            if (!isset($this->vehicleTypes[$code])) {
                throw new RuntimeException('Thiếu loại phương tiện bắt buộc: ' . $code . '.');
            }
        }

        foreach (['STANDARD_WASH', 'PREMIUM_WASH', 'ENGINE_CLEAN', 'TIRE_CARE'] as $code) {
            if (!isset($this->services[$code])) {
                throw new RuntimeException('Thiếu dịch vụ bắt buộc: ' . $code . '.');
            }
        }
    }

    private function resolveAnchor(): DateTimeImmutable
    {
        $statement = $this->database->query(
            <<<'SQL'
            SELECT wash_slots.slot_date
            FROM bookings
            INNER JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
            WHERE bookings.booking_code = 'DEF-BKG-0141'
            LIMIT 1
            SQL
        );
        $existing = $statement->fetchColumn();

        if (is_string($existing) && $existing !== '') {
            return (new DateTimeImmutable($existing, $this->timezone))->sub(new DateInterval('P1D'));
        }

        return new DateTimeImmutable('today', $this->timezone);
    }

    /** @return array<int, int> */
    private function seedUsers(string $passwordHash): array
    {
        $memberId = (int) $this->tiers['MEMBER']['id'];
        $adminIds = [];

        for ($index = 1; $index <= 2; $index++) {
            $phone = sprintf('088800000%d', $index);
            $name = sprintf('DEMO ADMIN %02d - Quản trị', $index);
            $adminIds[$index] = $this->upsertUser(
                $phone,
                $name,
                'admin',
                $memberId,
                '0.00',
                0,
                $passwordHash,
                sprintf('defense.admin%02d@autowash.local', $index)
            );
        }

        $scenarioNames = $this->scenarioNames();

        for ($index = 1; $index <= 80; $index++) {
            $name = $scenarioNames[$index] ?? sprintf('Filler đa dạng %02d', $index);
            $tierCode = $this->tierCodeForCustomer($index);
            [$monthlySpend, $monthlyVisits] = $this->monthlyMetricsForCustomer($index, $tierCode);
            $phone = $this->customerPhone($index);
            $this->customerIds[$index] = $this->upsertUser(
                $phone,
                sprintf('DEMO %02d - %s', $index, $name),
                'customer',
                (int) $this->tiers[$tierCode]['id'],
                $monthlySpend,
                $monthlyVisits,
                $passwordHash,
                sprintf('defense.customer%02d@autowash.local', $index)
            );
        }

        return $adminIds;
    }

    /** @return array<int, string> */
    private function scenarioNames(): array
    {
        return [
            1 => 'Khách mới',
            2 => 'Xe máy',
            3 => 'Nhiều phương tiện',
            4 => 'Booking Pending',
            5 => 'Booking Confirmed',
            6 => 'Booking Completed',
            7 => 'Booking Cancelled',
            8 => 'No Show',
            9 => 'Member',
            10 => 'Silver',
            11 => 'Gold',
            12 => 'Platinum',
            13 => 'Không có điểm',
            14 => 'Vừa đủ đổi thưởng',
            15 => 'Thiếu một điểm',
            16 => 'Reward Available',
            17 => 'Promotion',
            18 => 'Slot gần đầy',
            19 => 'Ownership A',
            20 => 'FEFO',
            21 => 'Ownership B',
            22 => 'Price Snapshot',
            23 => 'Thiếu spend Silver',
            24 => 'Thiếu visit Silver',
            25 => 'Đúng spend thiếu visit',
            26 => 'Đúng visit thiếu spend',
            27 => 'Đúng cả hai Silver',
            28 => 'Có thể upgrade',
            29 => 'Có thể downgrade',
            30 => 'Giữ tier',
            31 => 'Monthly metrics bằng 0',
            32 => 'Có điểm nhưng metrics bằng 0',
            33 => 'Một credit lot',
            34 => 'Nhiều credit lot',
            35 => 'Lot sắp hết hạn',
            36 => 'Lot hết hạn xa',
            37 => 'Adjust credit không hết hạn',
            38 => 'Lot đã dùng một phần',
            39 => 'Lịch sử dài balance bằng 0',
            40 => 'Reward xe máy',
            41 => 'Reward add-on',
            42 => 'Reward fixed',
            43 => 'Reward percentage',
            44 => 'Reward chưa đủ tier',
            45 => 'Promotion minimum chưa đạt',
            46 => 'Promotion minimum vừa đạt',
            47 => 'Promotion total limit',
            48 => 'Promotion per-user limit',
            49 => 'Slot coverage bị ngắt',
            50 => 'Booking cutoff',
        ];
    }

    private function tierCodeForCustomer(int $index): string
    {
        return match ($index) {
            10, 23, 24, 25, 26, 27, 30 => 'SILVER',
            11, 17, 42, 43 => 'GOLD',
            12, 29 => 'PLATINUM',
            28, 44 => 'MEMBER',
            default => ['MEMBER', 'SILVER', 'GOLD', 'PLATINUM'][($index - 1) % 4],
        };
    }

    /** @return array{string, int} */
    private function monthlyMetricsForCustomer(int $index, string $tierCode): array
    {
        $silver = $this->tiers['SILVER'];
        $gold = $this->tiers['GOLD'];

        return match ($index) {
            23 => [(string) max(0, (int) $silver['min_monthly_spend'] - 1), (int) $silver['min_monthly_visits']],
            24, 25 => [(string) $silver['min_monthly_spend'], max(0, (int) $silver['min_monthly_visits'] - 1)],
            26 => [(string) max(0, (int) $silver['min_monthly_spend'] - 1), (int) $silver['min_monthly_visits']],
            27, 30 => [(string) $silver['min_monthly_spend'], (int) $silver['min_monthly_visits']],
            28 => [(string) $gold['min_monthly_spend'], (int) $gold['min_monthly_visits']],
            29, 31, 32 => ['0.00', 0],
            default => [sprintf('%d.00', ($index % 6) * 125000), $index % 6],
        };
    }

    private function upsertUser(
        string $phone,
        string $name,
        string $role,
        int $tierId,
        string $monthlySpend,
        int $monthlyVisits,
        string $passwordHash,
        string $email
    ): int {
        $existing = $this->fetchOne('SELECT id, full_name FROM users WHERE phone = :phone', ['phone' => $phone]);

        if ($existing !== null && !str_starts_with((string) $existing['full_name'], 'DEMO ')) {
            throw new RuntimeException('Phone defense trùng tài khoản ngoài bộ seed: ' . $phone . '.');
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO users (
                current_tier_id, phone, full_name, email, password_hash, role,
                monthly_spend, monthly_visits, point_balance, status
            ) VALUES (
                :tier_id, :phone, :full_name, :email, :password_hash, :role,
                :monthly_spend, :monthly_visits, 0, 'active'
            )
            ON DUPLICATE KEY UPDATE
                current_tier_id = VALUES(current_tier_id),
                full_name = VALUES(full_name), email = VALUES(email),
                password_hash = VALUES(password_hash), role = VALUES(role),
                monthly_spend = VALUES(monthly_spend), monthly_visits = VALUES(monthly_visits),
                status = 'active', updated_at = CURRENT_TIMESTAMP
            SQL
        );
        $statement->execute([
            'tier_id' => $tierId,
            'phone' => $phone,
            'full_name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'monthly_spend' => $monthlySpend,
            'monthly_visits' => $monthlyVisits,
        ]);

        return (int) $this->scalar('SELECT id FROM users WHERE phone = :phone', ['phone' => $phone]);
    }

    private function seedPromotions(): void
    {
        $activeStart = $this->anchor->sub(new DateInterval('P1Y'))->format('Y-m-d 00:00:00');
        $activeEnd = $this->anchor->add(new DateInterval('P5Y'))->format('Y-m-d 23:59:59');
        $expiredStart = $this->anchor->sub(new DateInterval('P1Y'))->format('Y-m-d 00:00:00');
        $expiredEnd = $this->anchor->sub(new DateInterval('P15D'))->format('Y-m-d 23:59:59');
        $futureStart = $this->anchor->add(new DateInterval('P30D'))->format('Y-m-d 00:00:00');
        $futureEnd = $this->anchor->add(new DateInterval('P1Y'))->format('Y-m-d 23:59:59');
        $definitions = [
            'DEF_ACTIVE_FIXED' => ['DEMO - Giảm cố định', 'fixed', '10000.00', null, '40000.00', $activeStart, $activeEnd, 200, 20],
            'DEF_ACTIVE_PERCENT' => ['DEMO - Giảm 15% có trần', 'percentage', '15.00', '50000.00', '100000.00', $activeStart, $activeEnd, 200, 20],
            'DEF_EXPIRED' => ['DEMO - Promotion đã hết hạn', 'fixed', '15000.00', null, '50000.00', $expiredStart, $expiredEnd, 50, 5],
            'DEF_FUTURE' => ['DEMO - Promotion chưa bắt đầu', 'percentage', '12.00', '40000.00', '80000.00', $futureStart, $futureEnd, 50, 5],
            'DEF_SILVER' => ['DEMO - Riêng tier Silver', 'percentage', '8.00', '30000.00', '100000.00', $activeStart, $activeEnd, 100, 10],
            'DEF_STANDARD' => ['DEMO - Riêng Standard', 'fixed', '12000.00', null, '40000.00', $activeStart, $activeEnd, 100, 10],
            'DEF_CAR' => ['DEMO - Riêng ô tô', 'percentage', '10.00', '35000.00', '100000.00', $activeStart, $activeEnd, 100, 10],
            'DEF_TOTAL_LIMIT' => ['DEMO - Đạt total limit', 'fixed', '5000.00', null, '40000.00', $activeStart, $activeEnd, 5, 5],
            'DEF_PER_USER_LIMIT' => ['DEMO - Đạt per-user limit', 'fixed', '7000.00', null, '40000.00', $activeStart, $activeEnd, 100, 2],
        ];
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO promotions (
                code, name, description, discount_type, discount_value, max_discount,
                minimum_order_value, start_at, end_at, usage_limit, per_user_limit, is_active
            ) VALUES (
                :code, :name, 'Dữ liệu cấu hình phục vụ defense.', :discount_type,
                :discount_value, :max_discount, :minimum_order_value,
                :start_at, :end_at, :usage_limit, :per_user_limit, TRUE
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), description = VALUES(description),
                discount_type = VALUES(discount_type), discount_value = VALUES(discount_value),
                max_discount = VALUES(max_discount), minimum_order_value = VALUES(minimum_order_value),
                start_at = VALUES(start_at), end_at = VALUES(end_at),
                usage_limit = VALUES(usage_limit), per_user_limit = VALUES(per_user_limit),
                is_active = TRUE, updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($definitions as $code => $definition) {
            $existing = $this->fetchOne('SELECT name FROM promotions WHERE code = :code', ['code' => $code]);

            if ($existing !== null && !str_starts_with((string) $existing['name'], 'DEMO -')) {
                throw new RuntimeException('Mã promotion defense trùng dữ liệu ngoài seed: ' . $code . '.');
            }

            [$name, $type, $value, $cap, $minimum, $start, $end, $limit, $perUser] = $definition;
            $statement->execute([
                'code' => $code,
                'name' => $name,
                'discount_type' => $type,
                'discount_value' => $value,
                'max_discount' => $cap,
                'minimum_order_value' => $minimum,
                'start_at' => $start,
                'end_at' => $end,
                'usage_limit' => $limit,
                'per_user_limit' => $perUser,
            ]);
            $this->promotionIds[$code] = (int) $this->scalar(
                'SELECT id FROM promotions WHERE code = :code',
                ['code' => $code]
            );
        }

        $this->insertPromotionRelation('DEF_SILVER', 'promotion_tiers', 'tier_id', (int) $this->tiers['SILVER']['id']);
        $this->insertPromotionRelation('DEF_STANDARD', 'promotion_services', 'service_id', (int) $this->services['STANDARD_WASH']['id']);
        $this->insertPromotionRelation('DEF_CAR', 'promotion_vehicle_types', 'vehicle_type_id', (int) $this->vehicleTypes['car']['id']);
    }

    private function insertPromotionRelation(string $code, string $table, string $column, int $relationId): void
    {
        $statement = $this->database->prepare(
            "INSERT IGNORE INTO {$table} (promotion_id, {$column}) VALUES (:promotion_id, :relation_id)"
        );
        $statement->execute([
            'promotion_id' => $this->promotionIds[$code],
            'relation_id' => $relationId,
        ]);
    }

    private function seedRewards(): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO rewards (
                code, name, reward_type, points_cost, value, max_discount,
                service_id, minimum_tier_id, valid_days_after_redeem, is_active
            ) VALUES (
                :code, :name, :reward_type, :points_cost, :value, :max_discount,
                :service_id, :minimum_tier_id, :valid_days, TRUE
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), reward_type = VALUES(reward_type),
                points_cost = VALUES(points_cost), value = VALUES(value),
                max_discount = VALUES(max_discount), service_id = VALUES(service_id),
                minimum_tier_id = VALUES(minimum_tier_id),
                valid_days_after_redeem = VALUES(valid_days_after_redeem),
                is_active = TRUE, updated_at = CURRENT_TIMESTAMP
            SQL
        );
        $definitions = [
            'DEF_GOLD_FIXED' => ['DEMO - Reward Gold', 'fixed_discount', 350, '50000.00', null, null, (int) $this->tiers['GOLD']['id'], 45],
            'DEF_CAR_PERCENT' => ['DEMO - Reward phần trăm ô tô', 'percentage_discount', 450, '15.00', '60000.00', null, null, 45],
        ];

        foreach ($definitions as $code => $definition) {
            [$name, $type, $cost, $value, $cap, $serviceId, $tierId, $days] = $definition;
            $statement->execute([
                'code' => $code,
                'name' => $name,
                'reward_type' => $type,
                'points_cost' => $cost,
                'value' => $value,
                'max_discount' => $cap,
                'service_id' => $serviceId,
                'minimum_tier_id' => $tierId,
                'valid_days' => $days,
            ]);
        }

        $carRewardId = (int) $this->scalar('SELECT id FROM rewards WHERE code = :code', ['code' => 'DEF_CAR_PERCENT']);
        $relation = $this->database->prepare(
            'INSERT IGNORE INTO reward_vehicle_types (reward_id, vehicle_type_id) VALUES (:reward_id, :type_id)'
        );
        $relation->execute(['reward_id' => $carRewardId, 'type_id' => (int) $this->vehicleTypes['car']['id']]);

        foreach ($this->database->query(
            <<<'SQL'
            SELECT rewards.*, tiers.rank_order AS minimum_tier_rank
            FROM rewards
            LEFT JOIN tiers ON tiers.id = rewards.minimum_tier_id
            WHERE rewards.is_active = TRUE
            SQL
        )->fetchAll() as $reward) {
            $this->rewards[(string) $reward['code']] = $reward;
        }
    }

    private function seedVehicles(): void
    {
        $explicit = [
            1 => [2, 'motorbike'],
            2 => [3, 'motorbike'],
            3 => [3, 'car'],
            4 => [3, 'truck'],
            5 => [3, 'bus'],
            6 => [19, 'car'],
            7 => [21, 'car'],
            8 => [4, 'car'],
            9 => [5, 'bus'],
            10 => [18, 'truck'],
            11 => [17, 'car'],
            12 => [22, 'car'],
            13 => [6, 'car'],
            14 => [7, 'car'],
            15 => [8, 'car'],
        ];
        $types = ['motorbike', 'car', 'truck', 'bus'];

        for ($index = 1; $index <= 100; $index++) {
            if (isset($explicit[$index])) {
                [$ownerIndex, $typeCode] = $explicit[$index];
            } else {
                $ownerIndex = 9 + (($index - 16) % 72);
                $typeCode = $types[($index - 1) % 4];

                if (in_array($ownerIndex, [10, 46, 50], true)) {
                    $typeCode = 'car';
                } elseif ($ownerIndex === 45) {
                    $typeCode = 'motorbike';
                }
            }

            [$displayPlate, $normalizedPlate] = $this->plateForIndex($index);
            $ownerId = $this->customerIds[$ownerIndex];
            $existing = $this->fetchOne(
                <<<'SQL'
                SELECT vehicles.id, users.full_name
                FROM vehicles
                INNER JOIN users ON users.id = vehicles.user_id
                WHERE vehicles.normalized_plate = :plate
                SQL,
                ['plate' => $normalizedPlate]
            );

            if ($existing !== null && !str_starts_with((string) $existing['full_name'], 'DEMO ')) {
                throw new RuntimeException('Biển số defense trùng dữ liệu ngoài seed: ' . $normalizedPlate . '.');
            }

            $statement = $this->database->prepare(
                <<<'SQL'
                INSERT INTO vehicles (
                    user_id, vehicle_type_id, normalized_plate, display_plate,
                    brand, model, notes, is_active
                ) VALUES (
                    :user_id, :vehicle_type_id, :normalized_plate, :display_plate,
                    :brand, :model, :notes, :is_active
                )
                ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id), vehicle_type_id = VALUES(vehicle_type_id),
                    display_plate = VALUES(display_plate), brand = VALUES(brand),
                    model = VALUES(model), notes = VALUES(notes),
                    is_active = VALUES(is_active), updated_at = CURRENT_TIMESTAMP
                SQL
            );
            $statement->execute([
                'user_id' => $ownerId,
                'vehicle_type_id' => (int) $this->vehicleTypes[$typeCode]['id'],
                'normalized_plate' => $normalizedPlate,
                'display_plate' => $displayPlate,
                'brand' => ['Honda', 'Toyota', 'Ford', 'Thaco'][$index % 4],
                'model' => sprintf('Defense %02d', $index),
                'notes' => sprintf('Dữ liệu defense - phương tiện %03d.', $index),
                'is_active' => $index === 99 ? 0 : 1,
            ]);
            $vehicleId = (int) $this->scalar(
                'SELECT id FROM vehicles WHERE normalized_plate = :plate',
                ['plate' => $normalizedPlate]
            );
            $this->vehicleFixtures[$index] = [
                'id' => $vehicleId,
                'owner_index' => $ownerIndex,
                'user_id' => $ownerId,
                'type_code' => $typeCode,
                'active' => $index !== 99,
                'display_plate' => $displayPlate,
                'normalized_plate' => $normalizedPlate,
            ];
        }
    }

    /** @return array{string, string} */
    private function plateForIndex(int $index): array
    {
        if ($index === 1) {
            $existingOwner = $this->scalar(
                <<<'SQL'
                SELECT users.full_name
                FROM vehicles
                INNER JOIN users ON users.id = vehicles.user_id
                WHERE vehicles.normalized_plate = '78F102307'
                SQL
            );

            if (is_string($existingOwner) && !str_starts_with($existingOwner, 'DEMO ')) {
                return ['78-F1 024.07', '78F102407'];
            }

            return ['78-F1 023.07', '78F102307'];
        }

        $series = ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'K1', 'L1', 'M1', 'N1', 'P1', 'S1', 'T1', 'U1', 'V1', 'X1', 'Y1', 'Z1'];
        $province = 50 + (($index - 2) % 49);
        $letter = $series[($index - 2) % count($series)];
        $serial = 10000 + $index;
        $display = sprintf('%02d-%s %03d.%02d', $province, $letter, intdiv($serial, 100), $serial % 100);

        return [$display, sprintf('%02d%s%05d', $province, $letter, $serial)];
    }

    private function seedShowcaseSlots(): void
    {
        for ($offset = -30; $offset <= 14; $offset++) {
            $date = $this->anchor->modify(sprintf('%+d days', $offset))->format('Y-m-d');
            $this->upsertSlot($date, '06:10:00', '06:40:00', 12, 'open');
        }

        $closedDate = $this->anchor->add(new DateInterval('P5D'))->format('Y-m-d');
        $this->upsertSlot($closedDate, '20:50:00', '21:20:00', 10, 'closed');

        $gapDate = $this->anchor->add(new DateInterval('P6D'))->format('Y-m-d');
        $this->upsertSlot($gapDate, '21:30:00', '22:00:00', 10, 'open');
        $this->upsertSlot($gapDate, '22:30:00', '23:00:00', 10, 'open');
    }

    private function seedBookings(): void
    {
        $ownerOverrides = [
            1 => [6, 'car'],
            10 => [17, 'car'],
            11 => [22, 'car'],
            25 => [17, 'car'],
            26 => [17, 'car'],
            28 => [17, 'car'],
            29 => [17, 'car'],
            35 => [10, 'car'],
            45 => [45, 'motorbike'],
            46 => [46, 'car'],
            81 => [7, 'car'],
            114 => [50, 'car'],
            115 => [50, 'car'],
            116 => [8, 'car'],
            141 => [18, 'truck'],
            142 => [5, 'bus'],
            143 => [4, 'car'],
            144 => [2, 'motorbike'],
            145 => [4, 'car'],
            175 => [5, 'bus'],
            176 => [17, 'car'],
            177 => [11, 'car'],
        ];
        $rewardPlan = $this->rewardPlan();

        for ($index = 1; $index <= 200; $index++) {
            $vehicle = $this->vehicleFixtures[(($index - 1) % 100) + 1];

            if ($index > 140 && !(bool) $vehicle['active']) {
                $vehicle = $this->vehicleFixtures[98];
            }

            if (isset($ownerOverrides[$index])) {
                [$ownerIndex, $typeCode] = $ownerOverrides[$index];
                $vehicle = $this->vehicleForOwner($ownerIndex, $typeCode);
            }

            if (isset($rewardPlan[$index]['vehicle_type'])) {
                $vehicle = $this->firstVehicleOfType((string) $rewardPlan[$index]['vehicle_type']);
            }

            $userId = (int) $vehicle['user_id'];
            $ownerIndex = (int) $vehicle['owner_index'];
            $typeCode = (string) $vehicle['type_code'];
            $status = $this->bookingStatus($index);
            [$slotDate, $startTime] = $this->bookingSchedule($index);
            $serviceCodes = $this->serviceCodesForBooking($index, $typeCode, $rewardPlan[$index] ?? null);
            $items = $this->bookingItems($serviceCodes, $typeCode, $index === 11);
            $duration = array_sum(array_column($items, 'duration_minutes_snapshot'));
            $capacity = max(array_column($items, 'capacity_units_snapshot'));
            $subtotalCents = array_sum(array_column($items, 'line_total_cents'));
            $tierCode = $this->tierCodeForCustomer($ownerIndex);
            $perkCents = $index % 6 === 0
                ? $this->perkDiscountCents((int) $this->tiers[$tierCode]['id'], $items, $subtotalCents)
                : 0;
            $promotionCode = $this->promotionCodeForBooking($index);
            $promotionId = $promotionCode === null ? null : $this->promotionIds[$promotionCode];
            $promotionCents = $promotionCode === null
                ? 0
                : $this->promotionDiscountCents($promotionCode, $subtotalCents - $perkCents);
            $rewardCode = isset($rewardPlan[$index]) ? (string) $rewardPlan[$index]['reward_code'] : null;
            $rewardCents = $rewardCode === null
                ? 0
                : $this->rewardDiscountCents(
                    $rewardCode,
                    $items,
                    $subtotalCents - $perkCents - $promotionCents
                );
            $finalCents = $subtotalCents - $perkCents - $promotionCents - $rewardCents;

            if ($finalCents < 0) {
                throw new RuntimeException('Giá cuối defense không được âm.');
            }

            $slotCapacity = match ($index) {
                141, 142, 144 => 5,
                143 => 4,
                default => 10,
            };
            $slotsNeeded = (int) ceil($duration / 30);
            $slotIds = [];
            $slotStart = new DateTimeImmutable($slotDate . ' ' . $startTime, $this->timezone);

            for ($slotIndex = 0; $slotIndex < $slotsNeeded; $slotIndex++) {
                $start = $slotStart->modify('+' . ($slotIndex * 30) . ' minutes');
                $end = $start->modify('+30 minutes');
                $slotIds[] = $this->upsertSlot(
                    $start->format('Y-m-d'),
                    $start->format('H:i:s'),
                    $end->format('H:i:s'),
                    $slotCapacity,
                    'open'
                );
            }

            $createdAt = $slotStart->sub(new DateInterval($index > 140 ? 'P2D' : 'P3D'));
            $completedAt = null;
            $cancelledAt = null;
            $cancellationReason = null;

            if ($status === 'completed') {
                $completedAt = $slotStart->modify('+' . ($duration + 20) . ' minutes');
            } elseif ($status === 'cancelled') {
                $cancelledAt = match ($index) {
                    114 => $slotStart->modify('-2 hours'),
                    115 => $slotStart->modify('-1 hour'),
                    default => $slotStart->modify('-1 day'),
                };
                $cancellationReason = $index % 2 === 0
                    ? 'Admin hủy để phục vụ tình huống defense: khách yêu cầu đổi lịch.'
                    : 'Khách hàng chủ động hủy lịch trong tình huống defense.';
            } elseif ($status === 'no_show') {
                $cancelledAt = $slotStart->modify('+' . ($duration + 30) . ' minutes');
                $cancellationReason = 'Admin đánh dấu khách không đến trong tình huống defense.';
            }

            $bookingId = $this->upsertBooking([
                'booking_code' => sprintf('DEF-BKG-%04d', $index),
                'user_id' => $userId,
                'vehicle_id' => (int) $vehicle['id'],
                'start_slot_id' => $slotIds[0],
                'promotion_id' => $promotionId,
                'status' => $status,
                'booking_duration_minutes' => $duration,
                'booking_capacity_units' => $capacity,
                'subtotal' => $this->centsToMoney($subtotalCents),
                'perk_discount' => $this->centsToMoney($perkCents),
                'promotion_discount' => $this->centsToMoney($promotionCents),
                'reward_discount' => $this->centsToMoney($rewardCents),
                'final_price' => $this->centsToMoney($finalCents),
                'completed_at' => $completedAt?->format('Y-m-d H:i:s'),
                'cancelled_at' => $cancelledAt?->format('Y-m-d H:i:s'),
                'cancellation_reason' => $cancellationReason,
                'loyalty_processed_at' => $completedAt?->format('Y-m-d H:i:s'),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => ($completedAt ?? $cancelledAt ?? $createdAt)->format('Y-m-d H:i:s'),
            ]);
            $this->upsertBookingItems($bookingId, $items, $createdAt);
            $this->upsertReservations($bookingId, $slotIds, $capacity, $createdAt);
            $this->bookingFixtures[$index] = [
                'id' => $bookingId,
                'code' => sprintf('DEF-BKG-%04d', $index),
                'user_id' => $userId,
                'owner_index' => $ownerIndex,
                'vehicle_id' => (int) $vehicle['id'],
                'vehicle_type_code' => $typeCode,
                'status' => $status,
                'slot_date' => $slotDate,
                'slot_start' => $slotStart,
                'created_at' => $createdAt,
                'completed_at' => $completedAt,
                'cancelled_at' => $cancelledAt,
                'promotion_id' => $promotionId,
                'promotion_code' => $promotionCode,
                'promotion_discount' => $this->centsToMoney($promotionCents),
                'reward_code' => $rewardCode,
                'reward_discount' => $this->centsToMoney($rewardCents),
                'final_price' => $this->centsToMoney($finalCents),
                'service_codes' => $serviceCodes,
                'tier_code' => $tierCode,
            ];
        }
    }

    /** @return array<int, array<string, string>> */
    private function rewardPlan(): array
    {
        return [
            30 => ['reward_code' => 'DISCOUNT_10K'],
            31 => ['reward_code' => 'DISCOUNT_20_PERCENT'],
            32 => ['reward_code' => 'FREE_MOTORBIKE_STANDARD', 'vehicle_type' => 'motorbike', 'services' => 'standard'],
            33 => ['reward_code' => 'FREE_TIRE_CARE', 'vehicle_type' => 'car', 'services' => 'standard_tire'],
            34 => ['reward_code' => 'DISCOUNT_30K'],
            145 => ['reward_code' => 'DISCOUNT_10K'],
            146 => ['reward_code' => 'DEF_CAR_PERCENT', 'vehicle_type' => 'car'],
            147 => ['reward_code' => 'FREE_TIRE_CARE', 'vehicle_type' => 'car', 'services' => 'standard_tire'],
            175 => ['reward_code' => 'DISCOUNT_30K'],
            176 => ['reward_code' => 'DISCOUNT_20_PERCENT'],
            177 => ['reward_code' => 'DEF_GOLD_FIXED'],
        ];
    }

    /** @return array<string, mixed> */
    private function vehicleForOwner(int $ownerIndex, ?string $typeCode = null): array
    {
        foreach ($this->vehicleFixtures as $fixture) {
            if (
                (int) $fixture['owner_index'] === $ownerIndex
                && ($typeCode === null || (string) $fixture['type_code'] === $typeCode)
            ) {
                return $fixture;
            }
        }

        throw new RuntimeException('Thiếu phương tiện defense cho customer ' . $ownerIndex . '.');
    }

    /** @return array<string, mixed> */
    private function firstVehicleOfType(string $typeCode): array
    {
        foreach ($this->vehicleFixtures as $fixture) {
            if ((string) $fixture['type_code'] === $typeCode && (bool) $fixture['active']) {
                return $fixture;
            }
        }

        throw new RuntimeException('Thiếu phương tiện defense loại ' . $typeCode . '.');
    }

    private function bookingStatus(int $index): string
    {
        if ($index <= 80) {
            return 'completed';
        }

        if ($index <= 115) {
            return 'cancelled';
        }

        if ($index <= 140) {
            return 'no_show';
        }

        if ($index === 142 || $index >= 171) {
            return 'confirmed';
        }

        return 'pending';
    }

    /** @return array{string, string} */
    private function bookingSchedule(int $index): array
    {
        if ($index <= 140) {
            $offset = -75 + (int) floor((($index - 1) * 75) / 139);
            $times = ['07:20:00', '15:20:00'];
            $time = $times[($index - 1) % 2];
        } else {
            $relative = $index - 141;
            $offset = 1 + ($relative % 14);
            $times = ['06:50:00', '09:50:00', '12:50:00', '15:50:00', '18:50:00'];
            $time = $times[intdiv($relative, 14)];
        }

        return [$this->anchor->modify(sprintf('%+d days', $offset))->format('Y-m-d'), $time];
    }

    /**
     * @param array<string, string>|null $rewardFixture
     * @return list<string>
     */
    private function serviceCodesForBooking(int $index, string $typeCode, ?array $rewardFixture): array
    {
        if (($rewardFixture['services'] ?? null) === 'standard') {
            return ['STANDARD_WASH'];
        }

        if (($rewardFixture['services'] ?? null) === 'standard_tire') {
            return ['STANDARD_WASH', 'TIRE_CARE'];
        }

        if (in_array($index, [11, 28, 45, 46], true)) {
            return ['STANDARD_WASH'];
        }

        $package = $index % 2 === 0 ? 'PREMIUM_WASH' : 'STANDARD_WASH';

        if ($index > 140) {
            return $index % 4 === 0 ? [$package, 'TIRE_CARE'] : [$package];
        }

        $pattern = $index % 8;
        $addOns = match ($pattern) {
            2, 4 => ['TIRE_CARE'],
            3, 5 => ['ENGINE_CLEAN'],
            6, 7 => ['ENGINE_CLEAN', 'TIRE_CARE'],
            default => [],
        };

        if (!in_array($typeCode, ['car', 'truck'], true) && in_array('ENGINE_CLEAN', $addOns, true)) {
            $addOns = ['TIRE_CARE'];
        }

        return array_values(array_unique([$package, ...$addOns]));
    }

    /**
     * @param list<string> $serviceCodes
     * @return list<array<string, int|string>>
     */
    private function bookingItems(array $serviceCodes, string $typeCode, bool $oldSnapshot): array
    {
        $items = [];

        foreach ($serviceCodes as $serviceCode) {
            $key = $serviceCode . ':' . $typeCode;
            $price = $this->prices[$key] ?? null;

            if (
                !is_array($price)
                || !(bool) $price['is_supported']
                || $price['price'] === null
                || $price['duration_minutes'] === null
            ) {
                throw new RuntimeException('Cặp dịch vụ/loại xe defense không được hỗ trợ: ' . $key . '.');
            }

            $unitCents = $this->moneyToCents((string) $price['price']);

            if ($oldSnapshot && $serviceCode === 'STANDARD_WASH') {
                $unitCents = (int) floor($unitCents * 0.8);
            }

            $items[] = [
                'service_id' => (int) $price['service_id'],
                'service_vehicle_price_id' => (int) $price['id'],
                'service_name_snapshot' => (string) $price['service_name'],
                'vehicle_type_code_snapshot' => $typeCode,
                'unit_price_snapshot' => $this->centsToMoney($unitCents),
                'duration_minutes_snapshot' => (int) $price['duration_minutes'],
                'capacity_units_snapshot' => (int) ($price['capacity_units_override'] ?? $price['default_capacity_units']),
                'quantity' => 1,
                'line_total' => $this->centsToMoney($unitCents),
                'line_total_cents' => $unitCents,
            ];
        }

        return $items;
    }

    /** @param list<array<string, int|string>> $items */
    private function perkDiscountCents(int $tierId, array $items, int $subtotalCents): int
    {
        $statement = $this->database->prepare(
            'SELECT * FROM tier_perks WHERE tier_id = :tier_id AND is_active = TRUE ORDER BY id'
        );
        $statement->execute(['tier_id' => $tierId]);
        $best = 0;

        foreach ($statement->fetchAll() as $perk) {
            $candidate = match ((string) $perk['perk_type']) {
                'fixed_discount' => min($subtotalCents, $this->moneyToCents((string) $perk['value'])),
                'percentage_discount' => min(
                    $subtotalCents,
                    (int) floor($subtotalCents * (float) $perk['value'] / 100)
                ),
                'free_add_on' => $this->serviceDiscountCents($items, (int) $perk['service_id']),
                default => 0,
            };
            $best = max($best, $candidate);
        }

        return $best;
    }

    private function promotionCodeForBooking(int $index): ?string
    {
        return match (true) {
            $index >= 20 && $index <= 24 => 'DEF_TOTAL_LIMIT',
            $index === 25 || $index === 26 => 'DEF_PER_USER_LIMIT',
            $index === 27 => 'DEF_EXPIRED',
            $index === 28 => 'DEF_STANDARD',
            $index === 29 => 'DEF_CAR',
            $index === 35 => 'DEF_SILVER',
            $index === 10, $index === 46 => 'DEF_ACTIVE_PERCENT',
            $index === 45 => null,
            $index % 9 === 0 => 'DEF_ACTIVE_FIXED',
            default => null,
        };
    }

    private function promotionDiscountCents(string $code, int $baseCents): int
    {
        $promotion = $this->fetchOne('SELECT * FROM promotions WHERE code = :code', ['code' => $code]);

        if ($promotion === null) {
            throw new RuntimeException('Không tìm thấy promotion defense ' . $code . '.');
        }

        $discount = (string) $promotion['discount_type'] === 'fixed'
            ? $this->moneyToCents((string) $promotion['discount_value'])
            : (int) floor($baseCents * (float) $promotion['discount_value'] / 100);

        if ($promotion['max_discount'] !== null) {
            $discount = min($discount, $this->moneyToCents((string) $promotion['max_discount']));
        }

        return min($baseCents, $discount);
    }

    /** @param list<array<string, int|string>> $items */
    private function rewardDiscountCents(string $code, array $items, int $baseCents): int
    {
        $reward = $this->rewards[$code] ?? null;

        if (!is_array($reward)) {
            throw new RuntimeException('Không tìm thấy reward defense ' . $code . '.');
        }

        $discount = match ((string) $reward['reward_type']) {
            'fixed_discount' => $this->moneyToCents((string) $reward['value']),
            'percentage_discount' => (int) floor($baseCents * (float) $reward['value'] / 100),
            'free_service', 'add_on' => $this->serviceDiscountCents($items, (int) $reward['service_id']),
            default => 0,
        };

        if ($reward['max_discount'] !== null) {
            $discount = min($discount, $this->moneyToCents((string) $reward['max_discount']));
        }

        return min($baseCents, $discount);
    }

    /** @param list<array<string, int|string>> $items */
    private function serviceDiscountCents(array $items, int $serviceId): int
    {
        foreach ($items as $item) {
            if ((int) $item['service_id'] === $serviceId) {
                return (int) $item['line_total_cents'];
            }
        }

        return 0;
    }

    /** @param array<string, int|string|null> $data */
    private function upsertBooking(array $data): int
    {
        $existing = $this->fetchOne(
            <<<'SQL'
            SELECT bookings.id, users.full_name
            FROM bookings
            INNER JOIN users ON users.id = bookings.user_id
            WHERE bookings.booking_code = :code
            SQL,
            ['code' => $data['booking_code']]
        );

        if ($existing !== null && !str_starts_with((string) $existing['full_name'], 'DEMO ')) {
            throw new RuntimeException('Booking code defense trùng dữ liệu ngoài seed.');
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO bookings (
                booking_code, user_id, vehicle_id, start_slot_id, promotion_id, status,
                booking_duration_minutes, booking_capacity_units, subtotal, perk_discount,
                promotion_discount, reward_discount, final_price, completed_at, cancelled_at,
                cancellation_reason, loyalty_processed_at, created_at, updated_at
            ) VALUES (
                :booking_code, :user_id, :vehicle_id, :start_slot_id, :promotion_id, :status,
                :booking_duration_minutes, :booking_capacity_units, :subtotal, :perk_discount,
                :promotion_discount, :reward_discount, :final_price, :completed_at, :cancelled_at,
                :cancellation_reason, :loyalty_processed_at, :created_at, :updated_at
            )
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id), vehicle_id = VALUES(vehicle_id),
                start_slot_id = VALUES(start_slot_id), promotion_id = VALUES(promotion_id),
                status = VALUES(status), booking_duration_minutes = VALUES(booking_duration_minutes),
                booking_capacity_units = VALUES(booking_capacity_units), subtotal = VALUES(subtotal),
                perk_discount = VALUES(perk_discount), promotion_discount = VALUES(promotion_discount),
                reward_discount = VALUES(reward_discount), final_price = VALUES(final_price),
                completed_at = VALUES(completed_at), cancelled_at = VALUES(cancelled_at),
                cancellation_reason = VALUES(cancellation_reason),
                loyalty_processed_at = VALUES(loyalty_processed_at),
                created_at = VALUES(created_at), updated_at = VALUES(updated_at)
            SQL
        );
        $statement->execute($data);

        return (int) $this->scalar(
            'SELECT id FROM bookings WHERE booking_code = :code',
            ['code' => $data['booking_code']]
        );
    }

    /** @param list<array<string, int|string>> $items */
    private function upsertBookingItems(int $bookingId, array $items, DateTimeImmutable $createdAt): void
    {
        foreach ($items as $item) {
            $duplicates = (int) $this->scalar(
                'SELECT COUNT(*) FROM booking_items WHERE booking_id = :booking_id AND service_id = :service_id',
                ['booking_id' => $bookingId, 'service_id' => $item['service_id']]
            );

            if ($duplicates > 1) {
                throw new RuntimeException('Booking defense có booking item trùng dịch vụ.');
            }

            $existingId = $this->scalar(
                'SELECT id FROM booking_items WHERE booking_id = :booking_id AND service_id = :service_id',
                ['booking_id' => $bookingId, 'service_id' => $item['service_id']]
            );
            $parameters = [
                'booking_id' => $bookingId,
                'service_id' => $item['service_id'],
                'price_id' => $item['service_vehicle_price_id'],
                'service_name' => $item['service_name_snapshot'],
                'vehicle_type_code' => $item['vehicle_type_code_snapshot'],
                'unit_price' => $item['unit_price_snapshot'],
                'duration' => $item['duration_minutes_snapshot'],
                'capacity' => $item['capacity_units_snapshot'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
            ];

            if ($existingId === false) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO booking_items (
                        booking_id, service_id, service_vehicle_price_id, service_name_snapshot,
                        vehicle_type_code_snapshot, unit_price_snapshot, duration_minutes_snapshot,
                        capacity_units_snapshot, quantity, line_total, created_at
                    ) VALUES (
                        :booking_id, :service_id, :price_id, :service_name,
                        :vehicle_type_code, :unit_price, :duration, :capacity,
                        :quantity, :line_total, :created_at
                    )
                    SQL
                );
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE booking_items SET
                        service_vehicle_price_id = :price_id, service_name_snapshot = :service_name,
                        vehicle_type_code_snapshot = :vehicle_type_code, unit_price_snapshot = :unit_price,
                        duration_minutes_snapshot = :duration, capacity_units_snapshot = :capacity,
                        quantity = :quantity, line_total = :line_total, created_at = :created_at
                    WHERE booking_id = :booking_id AND service_id = :service_id
                    SQL
                );
            }

            $statement->execute($parameters);
        }
    }

    /** @param list<int> $slotIds */
    private function upsertReservations(
        int $bookingId,
        array $slotIds,
        int $capacity,
        DateTimeImmutable $createdAt
    ): void {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO booking_slot_reservations (
                booking_id, wash_slot_id, capacity_units_reserved, created_at, updated_at
            ) VALUES (
                :booking_id, :slot_id, :capacity, :created_at, :updated_at
            )
            ON DUPLICATE KEY UPDATE
                capacity_units_reserved = VALUES(capacity_units_reserved),
                created_at = VALUES(created_at), updated_at = VALUES(updated_at)
            SQL
        );

        foreach ($slotIds as $slotId) {
            $statement->execute([
                'booking_id' => $bookingId,
                'slot_id' => $slotId,
                'capacity' => $capacity,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $createdAt->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function upsertSlot(
        string $date,
        string $start,
        string $end,
        int $capacity,
        string $status
    ): int {
        $existing = $this->fetchOne(
            <<<'SQL'
            SELECT id, capacity_units, status
            FROM wash_slots
            WHERE slot_date = :slot_date AND start_time = :start_time AND end_time = :end_time
            SQL,
            ['slot_date' => $date, 'start_time' => $start, 'end_time' => $end]
        );

        if ($existing !== null) {
            if ((int) $existing['capacity_units'] !== $capacity || (string) $existing['status'] !== $status) {
                $defenseReservations = (int) $this->scalar(
                    <<<'SQL'
                    SELECT COUNT(*)
                    FROM booking_slot_reservations
                    INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
                    WHERE booking_slot_reservations.wash_slot_id = :slot_id
                      AND bookings.booking_code LIKE 'DEF-BKG-%'
                    SQL,
                    ['slot_id' => $existing['id']]
                );

                if ($defenseReservations === 0) {
                    throw new RuntimeException('Slot defense trùng slot ngoài seed có cấu hình khác.');
                }

                $update = $this->database->prepare(
                    'UPDATE wash_slots SET capacity_units = :capacity, status = :status, '
                    . 'updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $update->execute(['capacity' => $capacity, 'status' => $status, 'id' => $existing['id']]);
            }

            return (int) $existing['id'];
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO wash_slots (slot_date, start_time, end_time, capacity_units, status)
            VALUES (:slot_date, :start_time, :end_time, :capacity, :status)
            SQL
        );
        $statement->execute([
            'slot_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'capacity' => $capacity,
            'status' => $status,
        ]);

        return (int) $this->database->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    private function seedRewardRedemptions(): array
    {
        $definitions = [];
        $attachedIndexes = array_keys($this->rewardPlan());

        foreach ($attachedIndexes as $bookingIndex) {
            $booking = $this->bookingFixtures[$bookingIndex];
            $definitions[] = [
                'user_id' => (int) $booking['user_id'],
                'reward_code' => (string) $booking['reward_code'],
                'booking_id' => (int) $booking['id'],
                'status' => $booking['status'] === 'completed' ? 'used' : 'available',
                'redeemed_at' => $booking['created_at']->modify('-1 day'),
                'expires_at' => $booking['slot_start']->modify('+45 days'),
                'used_at' => $booking['status'] === 'completed' ? $booking['completed_at'] : null,
            ];
        }

        $unattached = [
            [16, 'DISCOUNT_10K', 'available', 60],
            [14, 'DISCOUNT_10K', 'available', 60],
            [40, 'FREE_MOTORBIKE_STANDARD', 'available', 60],
            [41, 'FREE_TIRE_CARE', 'available', 60],
            [42, 'DISCOUNT_20_PERCENT', 'available', 60],
            [15, 'DISCOUNT_10K', 'expired', -1],
            [16, 'DISCOUNT_30K', 'expired', -2],
            [7, 'DISCOUNT_10K', 'available', 30],
            [8, 'DISCOUNT_30K', 'cancelled', 30],
        ];

        foreach ($unattached as $offset => [$customerIndex, $rewardCode, $status, $expiryOffset]) {
            $redeemedAt = $this->anchor->modify('-40 days')->modify('+' . $offset . ' minutes');
            $expiresAt = $expiryOffset < 0
                ? $this->anchor->modify($expiryOffset . ' days')->modify('+' . $offset . ' minutes')
                : $this->anchor->modify('+' . $expiryOffset . ' days')->modify('+' . $offset . ' minutes');
            $definitions[] = [
                'user_id' => $this->customerIds[$customerIndex],
                'reward_code' => $rewardCode,
                'booking_id' => null,
                'status' => $status,
                'redeemed_at' => $redeemedAt,
                'expires_at' => $expiresAt,
                'used_at' => null,
            ];
        }

        if (count($definitions) !== 20) {
            throw new RuntimeException('Bộ reward redemption defense phải có đúng 20 fixture.');
        }

        $result = [];

        foreach ($definitions as $definition) {
            $reward = $this->rewards[(string) $definition['reward_code']] ?? null;

            if (!is_array($reward)) {
                throw new RuntimeException('Reward redemption tham chiếu reward không tồn tại.');
            }

            $redeemedAt = $definition['redeemed_at'];
            $expiresAt = $definition['expires_at'];

            if (!$redeemedAt instanceof DateTimeImmutable || !$expiresAt instanceof DateTimeImmutable) {
                throw new RuntimeException('Thời gian reward redemption defense không hợp lệ.');
            }

            $existingId = $this->scalar(
                <<<'SQL'
                SELECT id FROM reward_redemptions
                WHERE user_id = :user_id AND reward_id = :reward_id AND redeemed_at = :redeemed_at
                SQL,
                [
                    'user_id' => $definition['user_id'],
                    'reward_id' => $reward['id'],
                    'redeemed_at' => $redeemedAt->format('Y-m-d H:i:s'),
                ]
            );
            $parameters = [
                'user_id' => $definition['user_id'],
                'reward_id' => $reward['id'],
                'booking_id' => $definition['booking_id'],
                'points_spent' => $reward['points_cost'],
                'status' => $definition['status'],
                'redeemed_at' => $redeemedAt->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'used_at' => $definition['used_at'] instanceof DateTimeImmutable
                    ? $definition['used_at']->format('Y-m-d H:i:s')
                    : null,
            ];

            if ($existingId === false) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO reward_redemptions (
                        user_id, reward_id, booking_id, points_spent, status,
                        redeemed_at, expires_at, used_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :reward_id, :booking_id, :points_spent, :status,
                        :redeemed_at, :expires_at, :used_at, :created_at, :updated_at
                    )
                    SQL
                );
                $statement->execute($parameters + [
                    'created_at' => $redeemedAt->format('Y-m-d H:i:s'),
                    'updated_at' => $redeemedAt->format('Y-m-d H:i:s'),
                ]);
                $redemptionId = (int) $this->database->lastInsertId();
            } else {
                $redemptionId = (int) $existingId;
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE reward_redemptions SET
                        booking_id = :booking_id, points_spent = :points_spent,
                        status = :status, expires_at = :expires_at, used_at = :used_at,
                        updated_at = :redeemed_at
                    WHERE id = :id AND user_id = :user_id AND reward_id = :reward_id
                    SQL
                );
                $statement->execute($parameters + ['id' => $redemptionId]);
            }

            $result[] = $parameters + [
                'id' => $redemptionId,
                'reward_code' => $definition['reward_code'],
            ];
        }

        return $result;
    }

    private function seedPromotionUsages(): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO promotion_usages (
                promotion_id, user_id, booking_id, discount_amount, used_at
            ) VALUES (
                :promotion_id, :user_id, :booking_id, :discount_amount, :used_at
            )
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id), discount_amount = VALUES(discount_amount),
                used_at = VALUES(used_at)
            SQL
        );

        foreach ($this->bookingFixtures as $booking) {
            if (
                (string) $booking['status'] !== 'completed'
                || $booking['promotion_id'] === null
                || $this->moneyToCents((string) $booking['promotion_discount']) === 0
            ) {
                continue;
            }

            $usedAt = $booking['completed_at'];

            if (!$usedAt instanceof DateTimeImmutable) {
                throw new RuntimeException('Promotion usage defense thiếu completed_at.');
            }

            $statement->execute([
                'promotion_id' => $booking['promotion_id'],
                'user_id' => $booking['user_id'],
                'booking_id' => $booking['id'],
                'discount_amount' => $booking['promotion_discount'],
                'used_at' => $usedAt->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $redemptions
     * @return array{expired_debits: list<int>, transactions: list<int>}
     */
    private function seedLoyalty(array $redemptions, int $adminId): array
    {
        /** @var array<int, list<array<string, mixed>>> $debitsByUser */
        $debitsByUser = [];

        foreach ($redemptions as $redemption) {
            $userId = (int) $redemption['user_id'];
            $debitsByUser[$userId][] = [
                'type' => 'redeem',
                'points' => (int) $redemption['points_spent'],
                'source_type' => 'reward_redemption',
                'source_id' => (int) $redemption['id'],
                'description' => 'Dữ liệu defense - đổi reward.',
                'created_at' => (string) $redemption['redeemed_at'],
                'created_by' => null,
            ];
        }

        $this->appendSeedDebit($debitsByUser, 1, 'adjust_debit', 210, 101, 'Khách mới defense có balance bằng 0.', $adminId);
        $this->appendSeedDebit($debitsByUser, 13, 'expire', 100, 1301, 'Điểm defense đã hết hạn.', null);
        $this->appendSeedDebit($debitsByUser, 13, 'adjust_debit', 200, 1302, 'Điều chỉnh giảm để balance bằng 0.', $adminId);
        $this->appendSeedDebit($debitsByUser, 20, 'adjust_debit', 150, 2003, 'DEMO FEFO - debit 150 điểm.', $adminId);

        for ($index = 21; $index <= 30; $index++) {
            $type = $index % 2 === 0 ? 'expire' : 'adjust_debit';
            $this->appendSeedDebit(
                $debitsByUser,
                $index,
                $type,
                50,
                3000 + $index,
                $type === 'expire' ? 'Điểm defense đã hết hạn.' : 'Điều chỉnh giảm điểm defense.',
                $type === 'adjust_debit' ? $adminId : null
            );
        }

        $this->appendSeedDebit(
            $debitsByUser,
            38,
            'adjust_debit',
            50,
            3801,
            'Dùng một phần credit lot defense.',
            $adminId
        );
        $this->appendSeedDebit(
            $debitsByUser,
            39,
            'adjust_debit',
            300,
            3901,
            'Lịch sử dài defense kết thúc với balance bằng 0.',
            $adminId
        );

        $desiredBalances = [1 => 0, 13 => 0, 14 => 100, 15 => 99, 16 => 500, 20 => 150, 32 => 800, 39 => 0];
        $creditLots = [];
        $transactionIds = [];

        for ($index = 1; $index <= 42; $index++) {
            $userId = $this->customerIds[$index];
            $debitPoints = array_sum(array_column($debitsByUser[$userId] ?? [], 'points'));
            $desired = $desiredBalances[$index] ?? (200 + ($index * 10));
            $creditTotal = $debitPoints + $desired;

            if ($index === 13 || $index === 20) {
                $splits = [100, 200];
            } elseif ($index === 33) {
                $splits = [$creditTotal];
            } else {
                $first = min(max(100, (int) floor($creditTotal / 3)), $creditTotal - 1);
                $splits = [$first, $creditTotal - $first];
            }

            foreach ($splits as $lotIndex => $points) {
                $lotNumber = $lotIndex + 1;
                $type = $lotNumber === 1 ? 'earn' : 'adjust_credit';
                $earnedAt = $type === 'earn'
                    ? $this->anchor->sub(new DateInterval('P1Y'))->modify('+' . (10 + $index) . ' days')
                    : null;
                $expiresAt = $earnedAt?->add(new DateInterval('P1Y'));

                if ($type === 'earn' && $index === 35) {
                    $earnedAt = $this->anchor->sub(new DateInterval('P1Y'))->modify('+20 days');
                    $expiresAt = $earnedAt->add(new DateInterval('P1Y'));
                } elseif ($type === 'earn' && $index === 36) {
                    $earnedAt = $this->anchor->sub(new DateInterval('P1Y'))->modify('+300 days');
                    $expiresAt = $earnedAt->add(new DateInterval('P1Y'));
                }

                if ($index === 20) {
                    $earnedAt = $this->anchor->sub(new DateInterval('P11M'))->modify('+' . ($lotIndex * 90) . ' days');
                    $expiresAt = $earnedAt->add(new DateInterval('P1Y'));
                    $type = 'earn';
                }

                $sourceType = 'defense_seed';
                $sourceId = 1_000_000 + ($index * 10) + $lotNumber;

                if ($type === 'earn') {
                    $bookingId = $this->completedBookingIdForUser($userId, $lotNumber - 1);

                    if ($bookingId !== null) {
                        $sourceType = 'booking';
                        $sourceId = $bookingId;
                    }
                }

                $transactionId = $this->upsertLoyaltyTransaction([
                    'user_id' => $userId,
                    'type' => $type,
                    'points_delta' => $points,
                    'remaining_points' => $points,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'source_transaction_id' => null,
                    'description' => $index === 20
                        ? sprintf('DEMO FEFO - Lot %s.', $lotNumber === 1 ? 'A' : 'B')
                        : 'Dữ liệu defense - credit lot hợp lệ.',
                    'earned_at' => $earnedAt?->format('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                    'created_by' => $type === 'adjust_credit' ? $adminId : null,
                    'created_at' => ($earnedAt ?? $this->anchor->modify('-90 days')->modify('+' . $index . ' minutes'))
                        ->format('Y-m-d H:i:s'),
                ]);
                $creditLots[$userId][] = [
                    'id' => $transactionId,
                    'points' => $points,
                    'remaining' => $points,
                    'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                    'created_at' => ($earnedAt ?? $this->anchor->modify('-90 days'))->format('Y-m-d H:i:s'),
                ];
                $transactionIds[] = $transactionId;
            }
        }

        $expiredDebitIds = [];

        foreach ($debitsByUser as $userId => $debits) {
            usort(
                $creditLots[$userId],
                static fn (array $left, array $right): int => [
                    $left['expires_at'] === null ? 1 : 0,
                    $left['expires_at'] ?? '9999-12-31 23:59:59',
                    $left['created_at'],
                    $left['id'],
                ] <=> [
                    $right['expires_at'] === null ? 1 : 0,
                    $right['expires_at'] ?? '9999-12-31 23:59:59',
                    $right['created_at'],
                    $right['id'],
                ]
            );

            foreach ($debits as $debitIndex => $debit) {
                $debitId = $this->upsertLoyaltyTransaction([
                    'user_id' => $userId,
                    'type' => $debit['type'],
                    'points_delta' => -((int) $debit['points']),
                    'remaining_points' => null,
                    'source_type' => $debit['source_type'],
                    'source_id' => $debit['source_id'],
                    'source_transaction_id' => null,
                    'description' => $debit['description'],
                    'earned_at' => null,
                    'expires_at' => null,
                    'created_by' => $debit['created_by'],
                    'created_at' => $debit['created_at'] ?? $this->anchor
                        ->modify('-5 days')
                        ->modify('+' . $debitIndex . ' minutes')
                        ->format('Y-m-d H:i:s'),
                ]);
                $transactionIds[] = $debitId;
                $remainingDebit = (int) $debit['points'];

                foreach ($creditLots[$userId] as &$credit) {
                    if ($remainingDebit === 0) {
                        break;
                    }

                    $allocated = min($remainingDebit, (int) $credit['remaining']);

                    if ($allocated === 0) {
                        continue;
                    }

                    $this->upsertAllocation($debitId, (int) $credit['id'], $allocated, (string) $debit['created_at']);
                    $credit['remaining'] -= $allocated;
                    $remainingDebit -= $allocated;
                }
                unset($credit);

                if ($remainingDebit !== 0) {
                    throw new RuntimeException('Credit lot defense không đủ để phân bổ debit.');
                }

                if ((string) $debit['type'] === 'expire') {
                    $expiredDebitIds[] = $debitId;
                }
            }
        }

        $updateRemaining = $this->database->prepare(
            'UPDATE loyalty_transactions SET remaining_points = :remaining, '
            . 'updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );

        foreach ($creditLots as $lots) {
            foreach ($lots as $credit) {
                $updateRemaining->execute(['remaining' => $credit['remaining'], 'id' => $credit['id']]);
            }
        }

        $updateBalance = $this->database->prepare(
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

        foreach ($this->customerIds as $userId) {
            $updateBalance->execute(['user_id' => $userId]);
        }

        return ['expired_debits' => $expiredDebitIds, 'transactions' => $transactionIds];
    }

    /** @param array<int, list<array<string, mixed>>> $debitsByUser */
    private function appendSeedDebit(
        array &$debitsByUser,
        int $customerIndex,
        string $type,
        int $points,
        int $sourceId,
        string $description,
        ?int $createdBy
    ): void {
        $userId = $this->customerIds[$customerIndex];
        $debitsByUser[$userId][] = [
            'type' => $type,
            'points' => $points,
            'source_type' => 'defense_seed',
            'source_id' => $sourceId,
            'description' => $description,
            'created_at' => $this->anchor->modify('-5 days')->modify('+' . $sourceId . ' seconds')->format('Y-m-d H:i:s'),
            'created_by' => $createdBy,
        ];
    }

    /** @param array<string, mixed> $data */
    private function upsertLoyaltyTransaction(array $data): int
    {
        $existing = $this->fetchOne(
            <<<'SQL'
            SELECT loyalty_transactions.id, users.full_name, loyalty_transactions.description
            FROM loyalty_transactions
            INNER JOIN users ON users.id = loyalty_transactions.user_id
            WHERE loyalty_transactions.type = :type
              AND loyalty_transactions.source_type = :source_type
              AND loyalty_transactions.source_id = :source_id
            SQL,
            [
                'type' => $data['type'],
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'],
            ]
        );

        if ($existing !== null && !str_starts_with((string) $existing['full_name'], 'DEMO ')) {
            throw new RuntimeException('Khóa loyalty defense trùng dữ liệu ngoài seed.');
        }

        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_transactions (
                user_id, type, points_delta, remaining_points, source_type, source_id,
                source_transaction_id, description, earned_at, expires_at,
                created_by, created_at, updated_at
            ) VALUES (
                :user_id, :type, :points_delta, :remaining_points, :source_type, :source_id,
                :source_transaction_id, :description, :earned_at, :expires_at,
                :created_by, :created_at, :updated_at
            )
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id), points_delta = VALUES(points_delta),
                remaining_points = VALUES(remaining_points),
                source_transaction_id = VALUES(source_transaction_id),
                description = VALUES(description), earned_at = VALUES(earned_at),
                expires_at = VALUES(expires_at), created_by = VALUES(created_by),
                created_at = VALUES(created_at), updated_at = VALUES(updated_at)
            SQL
        );
        $statement->execute($data + ['updated_at' => $data['created_at']]);

        return (int) $this->scalar(
            <<<'SQL'
            SELECT id FROM loyalty_transactions
            WHERE type = :type AND source_type = :source_type AND source_id = :source_id
            SQL,
            [
                'type' => $data['type'],
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'],
            ]
        );
    }

    private function upsertAllocation(int $debitId, int $creditId, int $points, string $at): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO loyalty_allocations (
                debit_transaction_id, credit_transaction_id, allocated_points, allocated_at
            ) VALUES (
                :debit_id, :credit_id, :points, :allocated_at
            )
            ON DUPLICATE KEY UPDATE
                allocated_points = VALUES(allocated_points), allocated_at = VALUES(allocated_at)
            SQL
        );
        $statement->execute([
            'debit_id' => $debitId,
            'credit_id' => $creditId,
            'points' => $points,
            'allocated_at' => $at,
        ]);
    }

    private function completedBookingIdForUser(int $userId, int $offset): ?int
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT id FROM bookings
            WHERE user_id = :user_id AND status = 'completed' AND booking_code LIKE 'DEF-BKG-%'
            ORDER BY id
            LIMIT 1 OFFSET :row_offset
            SQL
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':row_offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $value = $statement->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    private function seedTierHistories(): void
    {
        $period = $this->anchor->modify('first day of previous month')->format('Y-m');
        $definitions = [
            28 => ['MEMBER', 'GOLD'],
            29 => ['GOLD', 'PLATINUM'],
            30 => ['SILVER', 'SILVER'],
            31 => ['MEMBER', 'MEMBER'],
        ];
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO tier_histories (
                user_id, old_tier_id, new_tier_id, review_period,
                monthly_spend_snapshot, monthly_visits_snapshot, reason, created_at
            ) VALUES (
                :user_id, :old_tier_id, :new_tier_id, :review_period,
                :monthly_spend, :monthly_visits, :reason, :created_at
            )
            ON DUPLICATE KEY UPDATE
                old_tier_id = VALUES(old_tier_id), new_tier_id = VALUES(new_tier_id),
                monthly_spend_snapshot = VALUES(monthly_spend_snapshot),
                monthly_visits_snapshot = VALUES(monthly_visits_snapshot),
                reason = VALUES(reason), created_at = VALUES(created_at)
            SQL
        );

        foreach ($definitions as $customerIndex => [$oldCode, $newCode]) {
            [$spend, $visits] = $this->monthlyMetricsForCustomer($customerIndex, $newCode);
            $statement->execute([
                'user_id' => $this->customerIds[$customerIndex],
                'old_tier_id' => $this->tiers[$oldCode]['id'],
                'new_tier_id' => $this->tiers[$newCode]['id'],
                'review_period' => $period,
                'monthly_spend' => $spend,
                'monthly_visits' => $visits,
                'reason' => 'Dữ liệu defense - lịch sử monthly tier review.',
                'created_at' => $this->anchor->modify('-10 days')->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedLprAttempts(): void
    {
        $definitions = [];

        for ($index = 1; $index <= 20; $index++) {
            $vehicle = $this->vehicleFixtures[$index];
            $status = match (true) {
                $index >= 15 => 'manual_override',
                $index >= 11 => 'failed',
                default => 'success',
            };
            $recognized = match (true) {
                $index === 9 => 'BIEN SAI FORMAT',
                $status === 'failed' => null,
                default => (string) $vehicle['display_plate'],
            };
            $normalized = match (true) {
                $index === 9 => 'BIENSAIFORMAT',
                $status === 'failed' => null,
                default => (string) $vehicle['normalized_plate'],
            };
            $confidence = match (true) {
                $status === 'failed' => null,
                $index % 3 === 0 => '0.5400',
                default => '0.9600',
            };
            $definitions[] = [
                'user_id' => $vehicle['user_id'],
                'image_path' => sprintf('storage/uploads/lpr/defense/attempt-%02d.jpg', $index),
                'provider' => $status === 'failed' && $index % 2 === 0 ? 'mock_failed' : 'mock',
                'recognized_text' => $recognized,
                'normalized_text' => $normalized,
                'confidence' => $confidence,
                'status' => $status,
                'created_at' => $this->anchor->modify('-' . $index . ' hours')->format('Y-m-d H:i:s'),
                'updated_at' => $this->anchor->modify('-' . $index . ' hours')->format('Y-m-d H:i:s'),
            ];
        }

        foreach ($definitions as $definition) {
            $count = (int) $this->scalar(
                'SELECT COUNT(*) FROM lpr_attempts WHERE image_path = :image_path',
                ['image_path' => $definition['image_path']]
            );

            if ($count > 1) {
                throw new RuntimeException('LPR attempt defense bị nhân đôi từ trước.');
            }

            if ($count === 0) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO lpr_attempts (
                        user_id, image_path, provider, recognized_text, normalized_text,
                        confidence, status, created_at, updated_at
                    ) VALUES (
                        :user_id, :image_path, :provider, :recognized_text, :normalized_text,
                        :confidence, :status, :created_at, :updated_at
                    )
                    SQL
                );
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE lpr_attempts SET
                        user_id = :user_id, provider = :provider,
                        recognized_text = :recognized_text, normalized_text = :normalized_text,
                        confidence = :confidence, status = :status,
                        created_at = :created_at, updated_at = :updated_at
                    WHERE image_path = :image_path
                    SQL
                );
            }

            $statement->execute($definition);
        }
    }

    /** @param array<int, int> $adminIds */
    private function seedAuditLogs(array $adminIds): void
    {
        $actions = [
            ['booking_cancelled', 'booking', $this->bookingFixtures[82]['id']],
            ['loyalty_adjusted', 'user', $this->customerIds[13]],
            ['service_updated', 'service', $this->services['STANDARD_WASH']['id']],
            ['promotion_saved', 'promotion', $this->promotionIds['DEF_ACTIVE_FIXED']],
            ['tier_config_saved', 'tier', $this->tiers['SILVER']['id']],
            ['tier_perk_saved', 'tier_perk', 1],
            ['service_activated', 'service', $this->services['PREMIUM_WASH']['id']],
            ['promotion_activated', 'promotion', $this->promotionIds['DEF_ACTIVE_PERCENT']],
        ];

        for ($index = 1; $index <= 20; $index++) {
            [$action, $targetType, $targetId] = $actions[($index - 1) % count($actions)];
            $reason = sprintf('Dữ liệu defense audit #%02d.', $index);
            $count = (int) $this->scalar(
                'SELECT COUNT(*) FROM audit_logs WHERE reason = :reason',
                ['reason' => $reason]
            );
            $parameters = [
                'actor_id' => $adminIds[$index % 2 === 0 ? 2 : 1],
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'before_json' => json_encode(['nguon' => 'defense', 'thu_tu' => $index], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'after_json' => json_encode(['nguon' => 'defense', 'hop_le' => true], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'reason' => $reason,
                'created_at' => $this->anchor->modify('-' . $index . ' minutes')->format('Y-m-d H:i:s'),
            ];

            if ($count === 0) {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    INSERT INTO audit_logs (
                        actor_user_id, action, target_type, target_id,
                        before_json, after_json, reason, created_at
                    ) VALUES (
                        :actor_id, :action, :target_type, :target_id,
                        :before_json, :after_json, :reason, :created_at
                    )
                    SQL
                );
            } else {
                $statement = $this->database->prepare(
                    <<<'SQL'
                    UPDATE audit_logs SET
                        actor_user_id = :actor_id, action = :action,
                        target_type = :target_type, target_id = :target_id,
                        before_json = :before_json, after_json = :after_json,
                        created_at = :created_at
                    WHERE reason = :reason
                    SQL
                );
            }

            $statement->execute($parameters);
        }
    }

    /** @param array{expired_debits: list<int>, transactions: list<int>} $loyalty */
    private function seedResearchEvents(array $loyalty): void
    {
        foreach ($this->bookingFixtures as $booking) {
            $services = $booking['service_codes'];
            $base = [
                'anonymous_user_key' => hash('sha256', 'defense-user:' . $booking['user_id']),
                'event_time' => $booking['created_at']->format('Y-m-d H:i:s'),
                'tier_code' => $booking['tier_code'],
                'tier_before_code' => null,
                'tier_after_code' => null,
                'vehicle_type_code' => $booking['vehicle_type_code'],
                'service_code' => count($services) === 1 ? $services[0] : null,
                'booking_lead_days' => (int) $booking['created_at']->diff($booking['slot_start'])->format('%a'),
                'order_value' => $booking['final_price'],
                'monthly_spend_snapshot' => null,
                'monthly_visits_snapshot' => null,
                'points_earned' => null,
                'points_redeemed' => null,
                'used_reward' => $booking['reward_code'] !== null ? 1 : 0,
                'used_promotion' => $booking['promotion_id'] !== null ? 1 : 0,
                'cancellation_status' => in_array($booking['status'], ['cancelled', 'no_show'], true)
                    ? $booking['status']
                    : null,
                'data_source' => 'system',
                'metadata_json' => json_encode(
                    ['service_codes' => $services, 'nguon' => 'defense'],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                ),
            ];
            $this->upsertResearchEvent(
                ['event_key' => 'booking_created:' . $booking['id'], 'event_type' => 'booking_created'] + $base
            );

            if ($booking['status'] === 'completed') {
                $completedAt = $booking['completed_at'];

                if (!$completedAt instanceof DateTimeImmutable) {
                    throw new RuntimeException('Research event completed thiếu thời gian.');
                }

                $points = (int) floor(
                    floor($this->moneyToCents((string) $booking['final_price']) / 1_000_000)
                    * (float) $this->tiers[(string) $booking['tier_code']]['point_rate']
                );
                $this->upsertResearchEvent([
                    'event_key' => 'booking_completed:' . $booking['id'],
                    'event_type' => 'booking_completed',
                    'event_time' => $completedAt->format('Y-m-d H:i:s'),
                    'points_earned' => $points,
                ] + $base);

                if ($booking['promotion_id'] !== null) {
                    $this->upsertResearchEvent([
                        'event_key' => 'promotion_used:' . $booking['id'],
                        'event_type' => 'promotion_used',
                        'event_time' => $completedAt->format('Y-m-d H:i:s'),
                        'used_promotion' => 1,
                    ] + $base);
                }
            }
        }

        $redemptions = $this->database->query(
            <<<'SQL'
            SELECT reward_redemptions.id, reward_redemptions.user_id,
                reward_redemptions.points_spent, reward_redemptions.redeemed_at,
                tiers.code AS tier_code
            FROM reward_redemptions
            INNER JOIN users ON users.id = reward_redemptions.user_id
            INNER JOIN tiers ON tiers.id = users.current_tier_id
            WHERE users.full_name LIKE 'DEMO %'
            ORDER BY reward_redemptions.id
            SQL
        )->fetchAll();

        foreach ($redemptions as $redemption) {
            $this->upsertResearchEvent([
                'event_key' => 'reward_redeemed:' . $redemption['id'],
                'anonymous_user_key' => hash('sha256', 'defense-user:' . $redemption['user_id']),
                'event_type' => 'reward_redeemed',
                'event_time' => $redemption['redeemed_at'],
                'tier_code' => $redemption['tier_code'],
                'points_redeemed' => $redemption['points_spent'],
                'used_reward' => 1,
                'data_source' => 'system',
                'metadata_json' => json_encode(['nguon' => 'defense'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
        }

        foreach ($loyalty['expired_debits'] as $debitId) {
            $transaction = $this->fetchOne(
                <<<'SQL'
                SELECT loyalty_transactions.*, tiers.code AS tier_code
                FROM loyalty_transactions
                INNER JOIN users ON users.id = loyalty_transactions.user_id
                INNER JOIN tiers ON tiers.id = users.current_tier_id
                WHERE loyalty_transactions.id = :id
                SQL,
                ['id' => $debitId]
            );

            if ($transaction === null) {
                continue;
            }

            $this->upsertResearchEvent([
                'event_key' => 'points_expired:' . $debitId,
                'anonymous_user_key' => hash('sha256', 'defense-user:' . $transaction['user_id']),
                'event_type' => 'points_expired',
                'event_time' => $transaction['created_at'],
                'tier_code' => $transaction['tier_code'],
                'points_redeemed' => abs((int) $transaction['points_delta']),
                'data_source' => 'system',
                'metadata_json' => json_encode(['nguon' => 'defense'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
        }

        $period = $this->anchor->modify('first day of previous month')->format('Y-m');
        $histories = $this->database->prepare(
            <<<'SQL'
            SELECT tier_histories.*, users.id AS user_id,
                old_tier.code AS old_code, new_tier.code AS new_code
            FROM tier_histories
            INNER JOIN users ON users.id = tier_histories.user_id
            INNER JOIN tiers AS old_tier ON old_tier.id = tier_histories.old_tier_id
            INNER JOIN tiers AS new_tier ON new_tier.id = tier_histories.new_tier_id
            WHERE users.full_name LIKE 'DEMO %' AND tier_histories.review_period = :period
            SQL
        );
        $histories->execute(['period' => $period]);

        foreach ($histories->fetchAll() as $history) {
            $this->upsertResearchEvent([
                'event_key' => 'tier_changed:' . $history['id'],
                'anonymous_user_key' => hash('sha256', 'defense-user:' . $history['user_id']),
                'event_type' => 'tier_changed',
                'event_time' => $history['created_at'],
                'tier_code' => $history['new_code'],
                'tier_before_code' => $history['old_code'],
                'tier_after_code' => $history['new_code'],
                'monthly_spend_snapshot' => $history['monthly_spend_snapshot'],
                'monthly_visits_snapshot' => $history['monthly_visits_snapshot'],
                'data_source' => 'system',
                'metadata_json' => json_encode(
                    ['review_period' => $period, 'nguon' => 'defense'],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                ),
            ]);
        }
    }

    /** @param array<string, mixed> $event */
    private function upsertResearchEvent(array $event): void
    {
        $defaults = [
            'tier_before_code' => null,
            'tier_after_code' => null,
            'vehicle_type_code' => null,
            'service_code' => null,
            'booking_lead_days' => null,
            'order_value' => null,
            'monthly_spend_snapshot' => null,
            'monthly_visits_snapshot' => null,
            'points_earned' => null,
            'points_redeemed' => null,
            'used_reward' => 0,
            'used_promotion' => 0,
            'cancellation_status' => null,
            'metadata_json' => null,
        ];
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
                :used_reward, :used_promotion, :cancellation_status, :data_source, :metadata_json
            )
            ON DUPLICATE KEY UPDATE
                anonymous_user_key = VALUES(anonymous_user_key), event_type = VALUES(event_type),
                event_time = VALUES(event_time), tier_code = VALUES(tier_code),
                tier_before_code = VALUES(tier_before_code), tier_after_code = VALUES(tier_after_code),
                vehicle_type_code = VALUES(vehicle_type_code), service_code = VALUES(service_code),
                booking_lead_days = VALUES(booking_lead_days), order_value = VALUES(order_value),
                monthly_spend_snapshot = VALUES(monthly_spend_snapshot),
                monthly_visits_snapshot = VALUES(monthly_visits_snapshot),
                points_earned = VALUES(points_earned), points_redeemed = VALUES(points_redeemed),
                used_reward = VALUES(used_reward), used_promotion = VALUES(used_promotion),
                cancellation_status = VALUES(cancellation_status),
                data_source = VALUES(data_source), metadata_json = VALUES(metadata_json)
            SQL
        );
        $statement->execute($event + $defaults);
    }

    private function assertInvariants(): void
    {
        $checks = [
            'Biển số normalized bị trùng.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT normalized_plate FROM vehicles GROUP BY normalized_plate HAVING COUNT(*) > 1
                ) AS duplicates
                SQL,
            'Customer defense có point_balance âm.' => <<<'SQL'
                SELECT COUNT(*) FROM users WHERE full_name LIKE 'DEMO %' AND point_balance < 0
                SQL,
            'Credit lot defense có remaining_points âm.' => <<<'SQL'
                SELECT COUNT(*)
                FROM loyalty_transactions
                INNER JOIN users ON users.id = loyalty_transactions.user_id
                WHERE users.full_name LIKE 'DEMO %' AND loyalty_transactions.remaining_points < 0
                SQL,
            'Ledger, balance hoặc credit lot defense không khớp.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT users.id
                    FROM users
                    LEFT JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
                    WHERE users.full_name LIKE 'DEMO %' AND users.role = 'customer'
                    GROUP BY users.id
                    HAVING MAX(users.point_balance) <> COALESCE(SUM(loyalty_transactions.points_delta), 0)
                       OR MAX(users.point_balance) <> COALESCE(SUM(
                            CASE WHEN loyalty_transactions.type IN ('earn', 'adjust_credit')
                            THEN loyalty_transactions.remaining_points ELSE 0 END
                       ), 0)
                ) AS mismatches
                SQL,
            'Allocation debit defense không khớp.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT loyalty_transactions.id
                    FROM loyalty_transactions
                    INNER JOIN users ON users.id = loyalty_transactions.user_id
                    LEFT JOIN loyalty_allocations
                        ON loyalty_allocations.debit_transaction_id = loyalty_transactions.id
                    WHERE users.full_name LIKE 'DEMO %'
                      AND loyalty_transactions.type IN ('redeem', 'expire', 'adjust_debit')
                    GROUP BY loyalty_transactions.id
                    HAVING ABS(MAX(loyalty_transactions.points_delta))
                        <> COALESCE(SUM(loyalty_allocations.allocated_points), 0)
                ) AS mismatches
                SQL,
            'Booking defense chọn đồng thời Standard và Premium.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT bookings.id
                    FROM bookings
                    INNER JOIN booking_items ON booking_items.booking_id = bookings.id
                    INNER JOIN services ON services.id = booking_items.service_id
                    WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                      AND services.code IN ('STANDARD_WASH', 'PREMIUM_WASH')
                    GROUP BY bookings.id HAVING COUNT(DISTINCT services.code) > 1
                ) AS invalid_bookings
                SQL,
            'Booking defense add-on-only hoặc thiếu package.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT bookings.id
                    FROM bookings
                    LEFT JOIN booking_items ON booking_items.booking_id = bookings.id
                    LEFT JOIN services ON services.id = booking_items.service_id
                    LEFT JOIN service_groups ON service_groups.id = services.service_group_id
                    WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                    GROUP BY bookings.id
                    HAVING SUM(service_groups.code = 'WASH_PACKAGE') <> 1
                ) AS invalid_bookings
                SQL,
            'Booking defense thiếu item.' => <<<'SQL'
                SELECT COUNT(*) FROM bookings
                LEFT JOIN booking_items ON booking_items.booking_id = bookings.id
                WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                GROUP BY bookings.id HAVING COUNT(booking_items.id) = 0
                SQL,
            'Booking defense thiếu reservation.' => <<<'SQL'
                SELECT COUNT(*) FROM bookings
                LEFT JOIN booking_slot_reservations
                    ON booking_slot_reservations.booking_id = bookings.id
                WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                GROUP BY bookings.id HAVING COUNT(booking_slot_reservations.id) = 0
                SQL,
            'Active capacity bị vượt.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT wash_slots.id
                    FROM wash_slots
                    LEFT JOIN booking_slot_reservations
                        ON booking_slot_reservations.wash_slot_id = wash_slots.id
                    LEFT JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
                        AND bookings.status IN ('pending', 'confirmed')
                    GROUP BY wash_slots.id
                    HAVING COALESCE(SUM(
                        CASE WHEN bookings.id IS NOT NULL
                        THEN booking_slot_reservations.capacity_units_reserved ELSE 0 END
                    ), 0) > MAX(wash_slots.capacity_units)
                ) AS overflow_slots
                SQL,
            'Vehicle defense có booking active overlap.' => <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT bookings.vehicle_id, booking_slot_reservations.wash_slot_id
                    FROM bookings
                    INNER JOIN booking_slot_reservations
                        ON booking_slot_reservations.booking_id = bookings.id
                    WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                      AND bookings.status IN ('pending', 'confirmed')
                    GROUP BY bookings.vehicle_id, booking_slot_reservations.wash_slot_id
                    HAVING COUNT(*) > 1
                ) AS overlaps
                SQL,
            'Promotion usage không khớp booking.' => <<<'SQL'
                SELECT COUNT(*)
                FROM promotion_usages
                INNER JOIN bookings ON bookings.id = promotion_usages.booking_id
                WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                  AND (bookings.promotion_id <> promotion_usages.promotion_id
                    OR bookings.status <> 'completed')
                SQL,
            'Reward gắn sai owner hoặc booking.' => <<<'SQL'
                SELECT COUNT(*)
                FROM reward_redemptions
                INNER JOIN bookings ON bookings.id = reward_redemptions.booking_id
                WHERE bookings.booking_code LIKE 'DEF-BKG-%'
                  AND reward_redemptions.user_id <> bookings.user_id
                SQL,
        ];

        foreach ($checks as $message => $sql) {
            $value = $this->database->query($sql)->fetchColumn();

            if ((int) $value !== 0) {
                throw new RuntimeException($message);
            }
        }

        $minimums = [
            'users' => 82,
            'vehicles' => 100,
            'bookings' => 200,
            'booking_items' => 200,
            'booking_slot_reservations' => 200,
            'loyalty_transactions' => 100,
            'loyalty_allocations' => 30,
            'reward_redemptions' => 20,
            'promotion_usages' => 15,
            'lpr_attempts' => 20,
            'audit_logs' => 20,
            'research_event_logs' => 80,
        ];

        foreach ($minimums as $table => $minimum) {
            $count = $this->defenseCount($table);

            if ($count < $minimum) {
                throw new RuntimeException(sprintf('%s defense chỉ có %d, cần tối thiểu %d.', $table, $count, $minimum));
            }
        }

        $fefo = $this->fetchOne(
            <<<'SQL'
            SELECT users.point_balance,
                SUM(CASE WHEN loyalty_transactions.description = 'DEMO FEFO - Lot A.'
                    THEN loyalty_transactions.remaining_points ELSE 0 END) AS lot_a,
                SUM(CASE WHEN loyalty_transactions.description = 'DEMO FEFO - Lot B.'
                    THEN loyalty_transactions.remaining_points ELSE 0 END) AS lot_b
            FROM users
            INNER JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
            WHERE users.phone = :phone
            GROUP BY users.id
            SQL,
            ['phone' => $this->customerPhone(20)]
        );

        if ($fefo === null || (int) $fefo['point_balance'] !== 150 || (int) $fefo['lot_a'] !== 0 || (int) $fefo['lot_b'] !== 150) {
            throw new RuntimeException('Fixture DEMO 20 - FEFO không đúng kết quả 0/150/balance 150.');
        }
    }

    /** @return array<string, int|string> */
    private function resultCounts(): array
    {
        $result = [
            'database' => (string) $this->database->query('SELECT DATABASE()')->fetchColumn(),
            'users_total' => (int) $this->database->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'users_defense' => $this->defenseCount('users'),
            'vehicles_total' => (int) $this->database->query('SELECT COUNT(*) FROM vehicles')->fetchColumn(),
            'vehicles_defense' => $this->defenseCount('vehicles'),
            'slots_total' => (int) $this->database->query('SELECT COUNT(*) FROM wash_slots')->fetchColumn(),
            'bookings_defense' => $this->defenseCount('bookings'),
            'booking_items_defense' => $this->defenseCount('booking_items'),
            'reservations_defense' => $this->defenseCount('booking_slot_reservations'),
            'loyalty_transactions_defense' => $this->defenseCount('loyalty_transactions'),
            'loyalty_allocations_defense' => $this->defenseCount('loyalty_allocations'),
            'reward_redemptions_defense' => $this->defenseCount('reward_redemptions'),
            'promotion_usages_defense' => $this->defenseCount('promotion_usages'),
            'lpr_attempts_defense' => $this->defenseCount('lpr_attempts'),
            'audit_logs_defense' => $this->defenseCount('audit_logs'),
            'research_events_defense' => $this->defenseCount('research_event_logs'),
        ];
        $statuses = $this->database->query(
            <<<'SQL'
            SELECT status, COUNT(*) AS total
            FROM bookings WHERE booking_code LIKE 'DEF-BKG-%' GROUP BY status ORDER BY status
            SQL
        )->fetchAll();

        foreach ($statuses as $status) {
            $result['bookings_' . (string) $status['status']] = (int) $status['total'];
        }

        return $result;
    }

    private function defenseCount(string $table): int
    {
        $queries = [
            'users' => "SELECT COUNT(*) FROM users WHERE full_name LIKE 'DEMO %'",
            'vehicles' => "SELECT COUNT(*) FROM vehicles WHERE notes LIKE 'Dữ liệu defense - phương tiện %'",
            'bookings' => "SELECT COUNT(*) FROM bookings WHERE booking_code LIKE 'DEF-BKG-%'",
            'booking_items' => "SELECT COUNT(*) FROM booking_items INNER JOIN bookings ON bookings.id = booking_items.booking_id WHERE bookings.booking_code LIKE 'DEF-BKG-%'",
            'booking_slot_reservations' => "SELECT COUNT(*) FROM booking_slot_reservations INNER JOIN bookings ON bookings.id = booking_slot_reservations.booking_id WHERE bookings.booking_code LIKE 'DEF-BKG-%'",
            'loyalty_transactions' => "SELECT COUNT(*) FROM loyalty_transactions INNER JOIN users ON users.id = loyalty_transactions.user_id WHERE users.full_name LIKE 'DEMO %'",
            'loyalty_allocations' => "SELECT COUNT(*) FROM loyalty_allocations INNER JOIN loyalty_transactions ON loyalty_transactions.id = loyalty_allocations.debit_transaction_id INNER JOIN users ON users.id = loyalty_transactions.user_id WHERE users.full_name LIKE 'DEMO %'",
            'reward_redemptions' => "SELECT COUNT(*) FROM reward_redemptions INNER JOIN users ON users.id = reward_redemptions.user_id WHERE users.full_name LIKE 'DEMO %'",
            'promotion_usages' => "SELECT COUNT(*) FROM promotion_usages INNER JOIN bookings ON bookings.id = promotion_usages.booking_id WHERE bookings.booking_code LIKE 'DEF-BKG-%'",
            'lpr_attempts' => "SELECT COUNT(*) FROM lpr_attempts WHERE image_path LIKE 'storage/uploads/lpr/defense/%'",
            'audit_logs' => "SELECT COUNT(*) FROM audit_logs WHERE reason LIKE 'Dữ liệu defense audit #%.'",
            'research_event_logs' => "SELECT COUNT(*) FROM research_event_logs WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata_json, '$.nguon')) = 'defense'",
        ];

        if (!isset($queries[$table])) {
            throw new RuntimeException('Bảng đếm defense không hợp lệ.');
        }

        return (int) $this->database->query($queries[$table])->fetchColumn();
    }

    private function customerPhone(int $index): string
    {
        return sprintf('08881%05d', $index);
    }

    private function moneyToCents(string $amount): int
    {
        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches) !== 1) {
            throw new RuntimeException('Giá trị tiền defense không hợp lệ.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    private function centsToMoney(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /** @param array<string, mixed> $parameters */
    private function scalar(string $sql, array $parameters = []): mixed
    {
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn();
    }

    /** @param array<string, mixed> $parameters @return array<string, mixed>|null */
    private function fetchOne(string $sql, array $parameters = []): ?array
    {
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }
};
