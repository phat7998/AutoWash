<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LprRecognitionOutcome
{
    public function __construct(
        public int $attemptId,
        public string $provider,
        public string $status,
        public string $recognizedText,
        public string $normalizedText,
        public ?float $confidence,
        public ?string $warning
    ) {
    }
}
