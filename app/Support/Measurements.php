<?php

namespace App\Support;

use InvalidArgumentException;

final class Measurements
{
    /** @var array<string, float> grams per unit */
    private const WEIGHT_TO_GRAMS = [
        'g' => 1.0,
        'kg' => 1000.0,
        'oz' => 28.349523125,
        'lb' => 453.59237,
    ];

    /** @var array<string, float> mm per unit */
    private const DIMENSION_TO_MM = [
        'mm' => 1.0,
        'cm' => 10.0,
        'in' => 25.4,
        'm' => 1000.0,
    ];

    public static function weightToGrams(mixed $value, string $unit): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Weight must be numeric.');
        }

        $unit = strtolower($unit);
        if (! isset(self::WEIGHT_TO_GRAMS[$unit])) {
            throw new InvalidArgumentException('Invalid weight unit.');
        }

        $grams = (float) $value * self::WEIGHT_TO_GRAMS[$unit];

        if ($grams < 0) {
            throw new InvalidArgumentException('Weight cannot be negative.');
        }

        return (int) round($grams);
    }

    public static function gramsToWeight(?int $grams, string $unit): ?string
    {
        if ($grams === null) {
            return null;
        }

        $unit = strtolower($unit);
        $factor = self::WEIGHT_TO_GRAMS[$unit] ?? 1.0;
        $value = $grams / $factor;

        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') ?: '0';
    }

    public static function dimensionToMm(mixed $value, string $unit): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Dimension must be numeric.');
        }

        $unit = strtolower($unit);
        if (! isset(self::DIMENSION_TO_MM[$unit])) {
            throw new InvalidArgumentException('Invalid dimension unit.');
        }

        $mm = (float) $value * self::DIMENSION_TO_MM[$unit];

        if ($mm < 0) {
            throw new InvalidArgumentException('Dimension cannot be negative.');
        }

        return (int) round($mm);
    }

    public static function mmToDimension(?int $mm, string $unit): ?string
    {
        if ($mm === null) {
            return null;
        }

        $unit = strtolower($unit);
        $factor = self::DIMENSION_TO_MM[$unit] ?? 1.0;
        $value = $mm / $factor;

        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') ?: '0';
    }

    /** @return list<string> */
    public static function weightUnits(): array
    {
        return array_keys(self::WEIGHT_TO_GRAMS);
    }

    /** @return list<string> */
    public static function dimensionUnits(): array
    {
        return array_keys(self::DIMENSION_TO_MM);
    }
}
