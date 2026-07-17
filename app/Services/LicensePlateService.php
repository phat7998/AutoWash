<?php

declare(strict_types=1);

namespace App\Services;

final class LicensePlateService
{
    private const STANDARD_CIVILIAN_PATTERN = '/^[0-9]{2}([A-Z]{1,2})[0-9]{4,5}$/';

    private const MOTORBIKE_CIVILIAN_PATTERN = '/^[0-9]{2}([A-Z])[0-9]{6}$/';

    public function normalize(string $plate): string
    {
        $uppercase = mb_strtoupper(trim($plate), 'UTF-8');

        return preg_replace('/[\s.\-]+/u', '', $uppercase) ?? $uppercase;
    }

    public function isCommonCivilianPlate(string $normalizedPlate): bool
    {
        if (
            preg_match(
                self::STANDARD_CIVILIAN_PATTERN,
                $normalizedPlate,
                $matches
            ) === 1
        ) {
            $series = $matches[1] ?? '';

            return !in_array($series, ['NG', 'NN', 'QT'], true);
        }

        return preg_match(
            self::MOTORBIKE_CIVILIAN_PATTERN,
            $normalizedPlate
        ) === 1;
    }

    public function display(string $plate): string
    {
        $uppercase = mb_strtoupper(trim($plate), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $uppercase) ?? $uppercase;
    }
}
