<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('003_create_benefit_tables', [
    <<<'SQL'
    CREATE TABLE promotions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        discount_type VARCHAR(20) NOT NULL,
        discount_value DECIMAL(14,2) UNSIGNED NOT NULL,
        max_discount DECIMAL(14,2) UNSIGNED NULL,
        minimum_order_value DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
        start_at DATETIME NOT NULL,
        end_at DATETIME NOT NULL,
        usage_limit INT UNSIGNED NULL,
        per_user_limit INT UNSIGNED NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_promotions_active_period (is_active, start_at, end_at),
        CONSTRAINT chk_promotions_type CHECK (discount_type IN ('percentage', 'fixed')),
        CONSTRAINT chk_promotions_value CHECK (discount_value > 0),
        CONSTRAINT chk_promotions_period CHECK (end_at > start_at),
        CONSTRAINT chk_promotions_limits CHECK (
            (usage_limit IS NULL OR usage_limit > 0)
            AND (per_user_limit IS NULL OR per_user_limit > 0)
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE promotion_tiers (
        promotion_id BIGINT UNSIGNED NOT NULL,
        tier_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (promotion_id, tier_id),
        CONSTRAINT fk_promotion_tiers_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
        CONSTRAINT fk_promotion_tiers_tier FOREIGN KEY (tier_id) REFERENCES tiers (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE promotion_services (
        promotion_id BIGINT UNSIGNED NOT NULL,
        service_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (promotion_id, service_id),
        CONSTRAINT fk_promotion_services_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
        CONSTRAINT fk_promotion_services_service FOREIGN KEY (service_id) REFERENCES services (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE promotion_vehicle_types (
        promotion_id BIGINT UNSIGNED NOT NULL,
        vehicle_type_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (promotion_id, vehicle_type_id),
        CONSTRAINT fk_promotion_vehicle_types_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id),
        CONSTRAINT fk_promotion_vehicle_types_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE rewards (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        reward_type VARCHAR(30) NOT NULL,
        points_cost INT UNSIGNED NOT NULL,
        value DECIMAL(14,2) UNSIGNED NOT NULL,
        service_id BIGINT UNSIGNED NULL,
        minimum_tier_id BIGINT UNSIGNED NULL,
        valid_days_after_redeem INT UNSIGNED NOT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rewards_eligibility (is_active, minimum_tier_id),
        CONSTRAINT fk_rewards_service FOREIGN KEY (service_id) REFERENCES services (id),
        CONSTRAINT fk_rewards_minimum_tier FOREIGN KEY (minimum_tier_id) REFERENCES tiers (id),
        CONSTRAINT chk_rewards_type CHECK (
            reward_type IN ('fixed_discount', 'percentage_discount', 'free_service', 'add_on')
        ),
        CONSTRAINT chk_rewards_points CHECK (points_cost > 0),
        CONSTRAINT chk_rewards_valid_days CHECK (valid_days_after_redeem > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE reward_vehicle_types (
        reward_id BIGINT UNSIGNED NOT NULL,
        vehicle_type_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (reward_id, vehicle_type_id),
        CONSTRAINT fk_reward_vehicle_types_reward FOREIGN KEY (reward_id) REFERENCES rewards (id),
        CONSTRAINT fk_reward_vehicle_types_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE tier_perks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tier_id BIGINT UNSIGNED NOT NULL,
        perk_type VARCHAR(30) NOT NULL,
        value DECIMAL(14,2) UNSIGNED NOT NULL,
        service_id BIGINT UNSIGNED NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tier_perks_eligibility (tier_id, is_active),
        CONSTRAINT fk_tier_perks_tier FOREIGN KEY (tier_id) REFERENCES tiers (id),
        CONSTRAINT fk_tier_perks_service FOREIGN KEY (service_id) REFERENCES services (id),
        CONSTRAINT chk_tier_perks_type CHECK (
            perk_type IN ('percentage_discount', 'fixed_discount', 'free_add_on')
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
