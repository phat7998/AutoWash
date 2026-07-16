<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\RecognitionResult;

interface LprProviderInterface
{
    public function name(): string;

    public function recognize(string $imagePath): RecognitionResult;
}
