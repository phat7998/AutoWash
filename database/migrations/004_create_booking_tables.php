<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('004_create_booking_tables', [
    <<<'SQL'
    CREATE TABLE bookings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        booking_code VARCHAR(40) NOT NULL UNIQUE,
        user_id BIGINT UNSIGNED NOT NULL,
        vehicle_id BIGINT UNSIGNED NOT NULL,
        start_slot_id BIGINT UNSIGNED NOT NULL,
        promotion_id BIGINT UNSIGNED NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        booking_duration_minutes INT UNSIGNED NOT NULL,
        booking_capacity_units INT UNSIGNED NOT NULL,
        subtotal DECIMAL(14,2) UNSIGNED NOT NULL,
        perk_discount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
        promotion_discount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
        reward_discount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
        final_price DECIMAL(14,2) UNSIGNED NOT NULL,
        completed_at DATETIME NULL,
        cancelled_at DATETIME NULL,
        cancellation_reason TEXT NULL,
        loyalty_processed_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_bookings_user_status (user_id, status, created_at),
        INDEX idx_bookings_vehicle_status (vehicle_id, status),
        INDEX idx_bookings_start_slot (start_slot_id),
        INDEX idx_bookings_promotion (promotion_id),
        CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_bookings_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id),
        CONSTRAINT fk_bookings_start_slot FOREIGN KEY (start_slot_id) REFERENCES wash_slots (id),
        CONSTRAINT fk_bookings_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
        CONSTRAINT chk_bookings_status CHECK (
            status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')
        ),
        CONSTRAINT chk_bookings_duration CHECK (booking_duration_minutes > 0),
        CONSTRAINT chk_bookings_capacity CHECK (booking_capacity_units > 0),
        CONSTRAINT chk_bookings_price CHECK (
            final_price = subtotal - perk_discount - promotion_discount - reward_discount
            AND final_price >= 0
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE booking_slot_reservations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        booking_id BIGINT UNSIGNED NOT NULL,
        wash_slot_id BIGINT UNSIGNED NOT NULL,
        capacity_units_reserved INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_booking_slot_reservations_pair (booking_id, wash_slot_id),
        INDEX idx_booking_slot_reservations_slot (wash_slot_id),
        CONSTRAINT fk_booking_slot_reservations_booking FOREIGN KEY (booking_id) REFERENCES bookings (id),
        CONSTRAINT fk_booking_slot_reservations_slot FOREIGN KEY (wash_slot_id) REFERENCES wash_slots (id),
        CONSTRAINT chk_booking_slot_reservations_capacity CHECK (capacity_units_reserved > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE booking_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        booking_id BIGINT UNSIGNED NOT NULL,
        service_id BIGINT UNSIGNED NOT NULL,
        service_vehicle_price_id BIGINT UNSIGNED NOT NULL,
        service_name_snapshot VARCHAR(150) NOT NULL,
        vehicle_type_code_snapshot VARCHAR(30) NOT NULL,
        unit_price_snapshot DECIMAL(14,2) UNSIGNED NOT NULL,
        duration_minutes_snapshot INT UNSIGNED NOT NULL,
        capacity_units_snapshot INT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        line_total DECIMAL(14,2) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_items_booking (booking_id),
        INDEX idx_booking_items_service (service_id),
        INDEX idx_booking_items_price_source (service_vehicle_price_id),
        CONSTRAINT fk_booking_items_booking FOREIGN KEY (booking_id) REFERENCES bookings (id),
        CONSTRAINT fk_booking_items_service FOREIGN KEY (service_id) REFERENCES services (id),
        CONSTRAINT fk_booking_items_price_source FOREIGN KEY (service_vehicle_price_id)
            REFERENCES service_vehicle_prices (id),
        CONSTRAINT chk_booking_items_duration CHECK (duration_minutes_snapshot > 0),
        CONSTRAINT chk_booking_items_capacity CHECK (capacity_units_snapshot > 0),
        CONSTRAINT chk_booking_items_quantity CHECK (quantity > 0),
        CONSTRAINT chk_booking_items_line_total CHECK (line_total = unit_price_snapshot * quantity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE reward_redemptions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        reward_id BIGINT UNSIGNED NOT NULL,
        booking_id BIGINT UNSIGNED NULL UNIQUE,
        points_spent INT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'available',
        redeemed_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_reward_redemptions_owner_status (user_id, status, expires_at),
        INDEX idx_reward_redemptions_reward (reward_id),
        CONSTRAINT fk_reward_redemptions_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_reward_redemptions_reward FOREIGN KEY (reward_id) REFERENCES rewards (id),
        CONSTRAINT fk_reward_redemptions_booking FOREIGN KEY (booking_id) REFERENCES bookings (id),
        CONSTRAINT chk_reward_redemptions_points CHECK (points_spent > 0),
        CONSTRAINT chk_reward_redemptions_status CHECK (
            status IN ('available', 'used', 'expired', 'cancelled')
        ),
        CONSTRAINT chk_reward_redemptions_period CHECK (expires_at > redeemed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE promotion_usages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        promotion_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        booking_id BIGINT UNSIGNED NOT NULL,
        discount_amount DECIMAL(14,2) UNSIGNED NOT NULL,
        used_at DATETIME NOT NULL,
        UNIQUE KEY uq_promotion_usages_booking (promotion_id, booking_id),
        INDEX idx_promotion_usages_limits (promotion_id, user_id),
        INDEX idx_promotion_usages_booking_id (booking_id),
        CONSTRAINT fk_promotion_usages_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
        CONSTRAINT fk_promotion_usages_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_promotion_usages_booking FOREIGN KEY (booking_id) REFERENCES bookings (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
