<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('006_create_operations_tables', [
    <<<'SQL'
    CREATE TABLE monthly_review_runs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        review_period CHAR(7) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL,
        started_at DATETIME NOT NULL,
        completed_at DATETIME NULL,
        processed_users INT UNSIGNED NOT NULL DEFAULT 0,
        error_message TEXT NULL,
        CONSTRAINT chk_monthly_review_runs_period CHECK (
            review_period REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$'
        ),
        CONSTRAINT chk_monthly_review_runs_status CHECK (status IN ('running', 'completed', 'failed'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE tier_histories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        old_tier_id BIGINT UNSIGNED NOT NULL,
        new_tier_id BIGINT UNSIGNED NOT NULL,
        review_period CHAR(7) NOT NULL,
        monthly_spend_snapshot DECIMAL(14,2) UNSIGNED NOT NULL,
        monthly_visits_snapshot INT UNSIGNED NOT NULL,
        reason TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_tier_histories_period (user_id, review_period),
        INDEX idx_tier_histories_old_tier (old_tier_id),
        INDEX idx_tier_histories_new_tier (new_tier_id),
        CONSTRAINT fk_tier_histories_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_tier_histories_old_tier FOREIGN KEY (old_tier_id) REFERENCES tiers (id),
        CONSTRAINT fk_tier_histories_new_tier FOREIGN KEY (new_tier_id) REFERENCES tiers (id),
        CONSTRAINT chk_tier_histories_period CHECK (
            review_period REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$'
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE research_event_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_key VARCHAR(190) NOT NULL UNIQUE,
        anonymous_user_key VARCHAR(190) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        event_time DATETIME NOT NULL,
        tier_code VARCHAR(30) NOT NULL,
        tier_before_code VARCHAR(30) NULL,
        tier_after_code VARCHAR(30) NULL,
        vehicle_type_code VARCHAR(30) NULL,
        service_code VARCHAR(50) NULL,
        booking_lead_days INT NULL,
        order_value DECIMAL(14,2) UNSIGNED NULL,
        monthly_spend_snapshot DECIMAL(14,2) UNSIGNED NULL,
        monthly_visits_snapshot INT UNSIGNED NULL,
        points_earned INT UNSIGNED NULL,
        points_redeemed INT UNSIGNED NULL,
        used_reward BOOLEAN NOT NULL DEFAULT FALSE,
        used_promotion BOOLEAN NOT NULL DEFAULT FALSE,
        cancellation_status VARCHAR(30) NULL,
        data_source VARCHAR(20) NOT NULL,
        metadata_json JSON NULL,
        INDEX idx_research_event_logs_time_type (event_time, event_type),
        INDEX idx_research_event_logs_source (data_source),
        CONSTRAINT chk_research_event_logs_source CHECK (data_source IN ('synthetic', 'survey', 'system'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        actor_user_id BIGINT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(100) NOT NULL,
        target_id BIGINT UNSIGNED NULL,
        before_json JSON NULL,
        after_json JSON NULL,
        reason TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_logs_actor_time (actor_user_id, created_at),
        INDEX idx_audit_logs_target (target_type, target_id),
        CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users (id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
