<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const FLASH_NEW = '__flash_new';
    private const FLASH_OLD = '__flash_old';

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array &$data)
    {
        $this->data =& $data;
        $this->ageFlashData();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function start(array $config, bool $secureRequest): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name((string) ($config['name'] ?? 'AUTOWASH_SESSION'));
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secureRequest,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start([
                'use_strict_mode' => 1,
                'use_only_cookies' => 1,
                'cookie_httponly' => 1,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => $secureRequest ? 1 : 0,
            ]);
        }

        return new self($_SESSION);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $newKeys = $this->flashKeys(self::FLASH_NEW);

        if (!in_array($key, $newKeys, true)) {
            $newKeys[] = $key;
        }

        $this->data[self::FLASH_NEW] = $newKeys;
    }

    private function ageFlashData(): void
    {
        foreach ($this->flashKeys(self::FLASH_OLD) as $key) {
            unset($this->data[$key]);
        }

        $this->data[self::FLASH_OLD] = $this->flashKeys(self::FLASH_NEW);
        unset($this->data[self::FLASH_NEW]);
    }

    /**
     * @return list<string>
     */
    private function flashKeys(string $key): array
    {
        $keys = $this->data[$key] ?? [];

        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_filter($keys, is_string(...)));
    }
}
