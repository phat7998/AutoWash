<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('008_add_reward_percentage_cap', [
    <<<'SQL'
    ALTER TABLE rewards
        ADD COLUMN max_discount DECIMAL(14,2) UNSIGNED NULL AFTER value,
        ADD CONSTRAINT chk_rewards_max_discount CHECK (max_discount IS NULL OR max_discount > 0)
    SQL,
]);
