<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final readonly class UploadedFile
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $clientMimeType,
        public int $size,
        public int $error,
        private bool $testMode = false
    ) {
    }

    /** @param array<string, mixed> $file */
    public static function fromPhpArray(array $file): self
    {
        $size = $file['size'] ?? null;
        $error = $file['error'] ?? null;

        return new self(
            is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '',
            is_string($file['name'] ?? null) ? $file['name'] : '',
            is_string($file['type'] ?? null) ? $file['type'] : '',
            is_int($size) || is_string($size) && ctype_digit($size) ? (int) $size : 0,
            is_int($error) || is_string($error) && ctype_digit($error) ? (int) $error : UPLOAD_ERR_NO_FILE
        );
    }

    public function moveTo(string $destination): void
    {
        $moved = $this->testMode
            ? @rename($this->temporaryPath, $destination)
            : move_uploaded_file($this->temporaryPath, $destination);

        if (!$moved) {
            throw new RuntimeException('Không thể lưu ảnh tải lên vào vùng lưu trữ an toàn.');
        }
    }
}
