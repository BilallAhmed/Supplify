<?php

namespace App\Support;

class TrackingIdValidator
{
    public static function isValidGtm(?string $id): bool
    {
        return is_string($id) && (bool) preg_match('/^GTM-[A-Z0-9]+$/i', $id);
    }

    public static function isValidGa4(?string $id): bool
    {
        return is_string($id) && (bool) preg_match('/^G-[A-Z0-9]+$/i', $id);
    }

    public static function isValidMetaPixel(?string $id): bool
    {
        return is_string($id) && (bool) preg_match('/^\d{5,20}$/', $id);
    }

    public static function normalizeGtm(?string $id): ?string
    {
        $id = self::clean($id);

        return $id !== null ? strtoupper($id) : null;
    }

    public static function normalizeGa4(?string $id): ?string
    {
        $id = self::clean($id);

        return $id !== null ? strtoupper($id) : null;
    }

    public static function normalizeMetaPixel(?string $id): ?string
    {
        return self::clean($id);
    }

    private static function clean(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $id = trim($id);

        return $id === '' ? null : $id;
    }
}
