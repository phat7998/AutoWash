<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('005_create_loyalty_tables', [
    <<<'SQL'
    CREATE TABLE loyalty_transactions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(20) NOT NULL,
        points_delta INT NOT NULL,
        remaining_points INT UNSIGNED NULL,
        source_type VARCHAR(50) NOT NULL,
        source_id BIGINT UNSIGNED NOT NULL,
        source_transaction_id BIGINT UNSIGNED NULL,
        description TEXT NOT NULL,
        earned_at DATETIME NULL,
        expires_at DATETIME NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_loyalty_transactions_source (type, source_type, source_id),
        INDEX idx_loyalty_transactions_history (user_id, created_at),
        INDEX idx_loyalty_transactions_expiry (type, expires_at, remaining_points),
        INDEX idx_loyalty_transactions_source_transaction (source_transaction_id),
        INDEX idx_loyalty_transactions_created_by (created_by),
        CONSTRAINT fk_loyalty_transactions_user FOREIGN KEY (user_id) REFERENCES users (id),
        CONSTRAINT fk_loyalty_transactions_source_transaction FOREIGN KEY (source_transaction_id)
            REFERENCES loyalty_transactions (id),
        CONSTRAINT fk_loyalty_transactions_created_by FOREIGN KEY (created_by) REFERENCES users (id),
        CONSTRAINT chk_loyalty_transactions_type CHECK (type IN ('earn', 'redeem', 'expire', 'adjust')),
        CONSTRAINT chk_loyalty_transactions_delta CHECK (
            (type = 'earn' AND points_delta >= 0)
            OR (type IN ('redeem', 'expire') AND points_delta < 0)
            OR (type = 'adjust' AND points_delta <> 0)
        ),
        CONSTRAINT chk_loyalty_transactions_remaining CHECK (
            (type = 'earn' AND remaining_points IS NOT NULL AND remaining_points <= points_delta)
            OR (type <> 'earn' AND remaining_points IS NULL)
        ),
        CONSTRAINT chk_loyalty_transactions_expiry_period CHECK (
            type <> 'earn'
            OR (earned_at IS NOT NULL AND expires_at IS NOT NULL AND expires_at > earned_at)
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<'SQL'
    CREATE TABLE loyalty_allocations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        debit_transaction_id BIGINT UNSIGNED NOT NULL,
        earn_transaction_id BIGINT UNSIGNED NOT NULL,
        points_allocated INT UNSIGNED NOT NULL,
        allocated_at DATETIME NOT NULL,
        UNIQUE KEY uq_loyalty_allocations_pair (debit_transaction_id, earn_transaction_id),
        INDEX idx_loyalty_allocations_earn (earn_transaction_id),
        CONSTRAINT fk_loyalty_allocations_debit FOREIGN KEY (debit_transaction_id)
            REFERENCES loyalty_transactions (id),
        CONSTRAINT fk_loyalty_allocations_earn FOREIGN KEY (earn_transaction_id)
            REFERENCES loyalty_transactions (id),
        CONSTRAINT chk_loyalty_allocations_points CHECK (points_allocated > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
