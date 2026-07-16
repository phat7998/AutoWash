<?php

declare(strict_types=1);

namespace App\Support;

final class VietnameseFormatter
{
    public static function vnd(string|int $amount): string
    {
        $whole = explode('.', (string) $amount, 2)[0];
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $groups = str_split(strrev($whole), 3);

        return strrev(implode('.', $groups)) . "\u{00A0}₫";
    }

    public static function date(string $date): string
    {
        $parts = explode('-', $date);

        return count($parts) === 3 ? implode('/', array_reverse($parts)) : $date;
    }

    public static function time(string $time): string
    {
        return substr($time, 0, 5);
    }
}
