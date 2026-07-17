<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('010_add_service_group_selection_policy', [
    'DROP PROCEDURE IF EXISTS autowash_preflight_service_groups',
    <<<'SQL'
    CREATE PROCEDURE autowash_preflight_service_groups()
    BEGIN
        DECLARE service_count INT DEFAULT 0;
        DECLARE classified_count INT DEFAULT 0;

        SELECT COUNT(*), COALESCE(SUM(
            code IN ('STANDARD_WASH', 'PREMIUM_WASH', 'TIRE_CARE', 'ENGINE_CLEAN')
        ), 0)
        INTO service_count, classified_count
        FROM services;

        IF service_count <> 0 AND (service_count <> 4 OR classified_count <> 4) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'SERVICE_GROUP_BACKFILL_UNCLASSIFIED_CATALOG';
        END IF;
    END
    SQL,
    'CALL autowash_preflight_service_groups()',
    'DROP PROCEDURE autowash_preflight_service_groups',
    <<<'SQL'
    CREATE TABLE service_groups (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        selection_mode VARCHAR(20) NOT NULL,
        min_selection INT UNSIGNED NOT NULL DEFAULT 0,
        max_selection INT UNSIGNED NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_service_groups_mode CHECK (selection_mode IN ('single', 'multiple')),
        CONSTRAINT chk_service_groups_range CHECK (
            max_selection IS NULL OR max_selection >= min_selection
        ),
        CONSTRAINT chk_service_groups_single CHECK (
            selection_mode <> 'single' OR (min_selection <= 1 AND max_selection = 1)
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    INSERT INTO service_groups (code, name, selection_mode, min_selection, max_selection, is_active)
    VALUES
        ('WASH_PACKAGE', 'Gói rửa chính', 'single', 1, 1, TRUE),
        ('ADD_ON', 'Dịch vụ bổ sung', 'multiple', 0, NULL, TRUE)
    SQL,
    <<<'SQL'
    ALTER TABLE services
        ADD COLUMN service_group_id BIGINT UNSIGNED NULL AFTER description,
        ADD INDEX idx_services_group_active (service_group_id, is_active)
    SQL,
    <<<'SQL'
    UPDATE services
    SET service_group_id = CASE
        WHEN code IN ('STANDARD_WASH', 'PREMIUM_WASH')
            THEN (SELECT id FROM service_groups WHERE code = 'WASH_PACKAGE')
        WHEN code IN ('TIRE_CARE', 'ENGINE_CLEAN')
            THEN (SELECT id FROM service_groups WHERE code = 'ADD_ON')
        ELSE service_group_id
    END
    SQL,
    <<<'SQL'
    UPDATE service_vehicle_prices
    INNER JOIN services ON services.id = service_vehicle_prices.service_id
    SET service_vehicle_prices.capacity_units_override = NULL
    WHERE services.code IN ('STANDARD_WASH', 'PREMIUM_WASH', 'TIRE_CARE', 'ENGINE_CLEAN')
    SQL,
    <<<'SQL'
    ALTER TABLE services
        MODIFY service_group_id BIGINT UNSIGNED NOT NULL,
        ADD CONSTRAINT fk_services_group
            FOREIGN KEY (service_group_id) REFERENCES service_groups (id)
    SQL,
]);
