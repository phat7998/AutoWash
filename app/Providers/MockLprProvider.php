<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\LprProviderInterface;
use App\DTO\RecognitionResult;

final readonly class MockLprProvider implements LprProviderInterface
{
    public function __construct(
        private string $recognizedText,
        private ?float $confidence
    ) {
    }

    public function name(): string
    {
        return 'mock';
    }

    public function recognize(string $imagePath): RecognitionResult
    {
        return new RecognitionResult($this->recognizedText, $this->confidence);
    }
}
