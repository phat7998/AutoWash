<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('001_create_core_tables', [
    <<<'SQL'
    CREATE TABLE app_settings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value VARCHAR(255) NOT NULL,
        value_type VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_app_settings_value_type CHECK (value_type IN ('integer', 'decimal', 'boolean', 'string'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE tiers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        rank_order INT UNSIGNED NOT NULL UNIQUE,
        booking_window_days INT UNSIGNED NOT NULL,
        min_monthly_spend DECIMAL(14,2) UNSIGNED NOT NULL,
        min_monthly_visits INT UNSIGNED NOT NULL,
        point_rate DECIMAL(5,2) UNSIGNED NOT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_tiers_point_rate CHECK (point_rate > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        current_tier_id BIGINT UNSIGNED NOT NULL,
        phone VARCHAR(20) NOT NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'customer',
        monthly_spend DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
        monthly_visits INT UNSIGNED NOT NULL DEFAULT 0,
        point_balance INT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_users_current_tier (current_tier_id),
        INDEX idx_users_status_role (status, role),
        CONSTRAINT fk_users_current_tier FOREIGN KEY (current_tier_id) REFERENCES tiers (id),
        CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin')),
        CONSTRAINT chk_users_status CHECK (status IN ('active', 'disabled'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
