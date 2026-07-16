<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('009_create_lpr_attempts', [
    <<<'SQL'
    CREATE TABLE lpr_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        image_path VARCHAR(500) NOT NULL,
        provider VARCHAR(100) NOT NULL,
        recognized_text VARCHAR(50) NULL,
        normalized_text VARCHAR(30) NULL,
        confidence DECIMAL(5,4) UNSIGNED NULL,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_lpr_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT chk_lpr_attempts_confidence CHECK (
            confidence IS NULL OR (confidence >= 0 AND confidence <= 1)
        ),
        CONSTRAINT chk_lpr_attempts_status CHECK (status IN ('success', 'failed', 'manual_override')),
        INDEX idx_lpr_attempts_user_created (user_id, created_at),
        INDEX idx_lpr_attempts_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
]);
