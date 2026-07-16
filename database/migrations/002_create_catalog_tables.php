<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('002_create_catalog_tables', [
    <<<'SQL'
    CREATE TABLE vehicle_types (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        display_name VARCHAR(100) NOT NULL,
        default_duration_minutes INT UNSIGNED NOT NULL,
        default_capacity_units INT UNSIGNED NOT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_vehicle_types_duration CHECK (default_duration_minutes > 0),
        CONSTRAINT chk_vehicle_types_capacity CHECK (default_capacity_units > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE vehicles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        vehicle_type_id BIGINT UNSIGNED NOT NULL,
        normalized_plate VARCHAR(20) NOT NULL UNIQUE,
        display_plate VARCHAR(30) NOT NULL,
        brand VARCHAR(100) NULL,
        model VARCHAR(100) NULL,
        notes TEXT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_vehicles_owner_active (user_id, is_active),
        INDEX idx_vehicles_type (vehicle_type_id),
        CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_vehicles_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE services (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE service_vehicle_prices (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        service_id BIGINT UNSIGNED NOT NULL,
        vehicle_type_id BIGINT UNSIGNED NOT NULL,
        price DECIMAL(14,2) UNSIGNED NULL,
        duration_minutes INT UNSIGNED NULL,
        capacity_units_override INT UNSIGNED NULL,
        is_supported BOOLEAN NOT NULL DEFAULT TRUE,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_service_vehicle_prices_pair (service_id, vehicle_type_id),
        INDEX idx_service_vehicle_prices_catalog (vehicle_type_id, is_supported, is_active),
        CONSTRAINT fk_service_vehicle_prices_service FOREIGN KEY (service_id) REFERENCES services (id),
        CONSTRAINT fk_service_vehicle_prices_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types (id),
        CONSTRAINT chk_service_vehicle_prices_supported CHECK (
            (is_supported = TRUE AND price > 0 AND duration_minutes > 0)
            OR (is_supported = FALSE AND price IS NULL AND duration_minutes IS NULL)
        ),
        CONSTRAINT chk_service_vehicle_prices_capacity CHECK (
            capacity_units_override IS NULL OR capacity_units_override > 0
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE wash_slots (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slot_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        capacity_units INT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wash_slots_period (slot_date, start_time, end_time),
        INDEX idx_wash_slots_availability (slot_date, status, start_time),
        CONSTRAINT chk_wash_slots_time CHECK (end_time > start_time),
        CONSTRAINT chk_wash_slots_capacity CHECK (capacity_units > 0),
        CONSTRAINT chk_wash_slots_status CHECK (status IN ('open', 'closed'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
