<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

final readonly class RecognitionResult
{
    public function __construct(
        public string $recognizedText,
        public ?float $confidence
    ) {
        if (trim($recognizedText) === '') {
            throw new InvalidArgumentException('Kết quả nhận diện không được để trống.');
        }

        if ($confidence !== null && ($confidence < 0 || $confidence > 1)) {
            throw new InvalidArgumentException('Độ tin cậy nhận diện phải nằm trong khoảng 0 đến 1.');
        }
    }
}
