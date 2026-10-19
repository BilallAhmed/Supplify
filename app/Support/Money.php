<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function toCents(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            throw new InvalidArgumentException('Amount is required.');
        }

        if (is_int($amount)) {
            return $amount;
        }

        $normalized = str_replace([',', ' '], ['', ''], (string) $amount);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function toCentsNullable(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return self::toCents($amount);
    }

    public static function format(int $cents, string $currency = 'USD'): string
    {
        return ($currency === 'USD' ? '$' : $currency.' ').number_format($cents / 100, 2);
    }

    public static function toDecimalString(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
