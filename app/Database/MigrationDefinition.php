<?php

declare(strict_types=1);

namespace App\Database;

final readonly class MigrationDefinition
{
    /**
     * @param list<string> $statements
     */
    public function __construct(
        public string $name,
        public array $statements
    ) {
    }
}
