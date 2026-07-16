<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;

final class Logger
{
    public function __construct(
        private readonly string $logFile,
        private readonly DateTimeZone $timezone
    ) {
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        try {
            $line = json_encode(
                [
                    'time' => (new DateTimeImmutable('now', $this->timezone))->format(DATE_ATOM),
                    'level' => $level,
                    'message' => $message,
                    'context' => $this->sanitizeContext($context),
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (JsonException) {
            $line = '{"level":"error","message":"Không thể mã hóa nội dung log"}';
        }

        $directory = dirname($this->logFile);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục log.');
        }

        if (file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Không thể ghi application log.');
        }
    }

    /**
     * @param array<string, scalar|null> $context
     * @return array<string, scalar|null>
     */
    private function sanitizeContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (preg_match('/password|secret|token|authorization|cookie/i', $key) === 1) {
                $context[$key] = '[ĐÃ ẨN]';
                continue;
            }

            if (is_string($value)) {
                $context[$key] = preg_replace(
                    '/\b(password|secret|token|authorization|cookie|DB_PASSWORD)\s*[=:]\s*[^\s,;]+/i',
                    '$1=[ĐÃ ẨN]',
                    $value
                ) ?? '[ĐÃ ẨN]';
            }
        }

        return $context;
    }
}
