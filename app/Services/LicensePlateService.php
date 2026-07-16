<?php

declare(strict_types=1);

namespace App\Services;

final class LicensePlateService
{
    private const COMMON_CIVILIAN_PATTERN = '/^[0-9]{2}[A-Z]{1,2}[0-9]{4,5}$/';

    public function normalize(string $plate): string
    {
        $uppercase = mb_strtoupper(trim($plate), 'UTF-8');

        return preg_replace('/[\s.\-]+/u', '', $uppercase) ?? $uppercase;
    }

    public function isCommonCivilianPlate(string $normalizedPlate): bool
    {
        if (preg_match(self::COMMON_CIVILIAN_PATTERN, $normalizedPlate) !== 1) {
            return false;
        }

        $series = preg_replace('/^[0-9]{2}([A-Z]{1,2})[0-9]{4,5}$/', '$1', $normalizedPlate);

        return is_string($series) && !in_array($series, ['NG', 'NN', 'QT'], true);
    }

    public function display(string $plate): string
    {
        $uppercase = mb_strtoupper(trim($plate), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $uppercase) ?? $uppercase;
    }
}
