<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class StoredLprUpload
{
    public function __construct(
        public string $absolutePath,
        public string $relativePath
    ) {
    }
}
