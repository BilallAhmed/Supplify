<?php

namespace App\Support;

/**
 * Detect whether hero copy sits on a dark or light region of a banner image.
 * Samples the left text column — not the full-frame average — so product-heavy
 * right sides do not skew the result.
 */
class BannerCopyTone
{
    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * @return 'dark'|'light' Background tone behind the copy (dark ⇒ use light text).
     */
    public static function detect(?string $image): string
    {
        $path = self::resolveLocalPath($image);

        if ($path === null) {
            return 'dark';
        }

        $mtime = @filemtime($path) ?: 0;
        $key = $path.'@'.$mtime;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $tone = self::sample($path);
        self::$cache[$key] = $tone;

        return $tone;
    }

    private static function resolveLocalPath(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        $path = $image;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parts = parse_url($path);
            $path = $parts['path'] ?? '';
        }

        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/'.ltrim(str_replace('\\', '/', $path), '/');

        $publicPos = strpos($path, '/images/');
        if ($publicPos !== false) {
            $path = substr($path, $publicPos);
        }

        $base = self::publicRoot();

        if (str_starts_with($path, '/storage/')) {
            $storagePublic = $base.DIRECTORY_SEPARATOR.'storage'.substr(str_replace('/', DIRECTORY_SEPARATOR, $path), strlen('/storage'));
            $full = is_readable($storagePublic)
                ? $storagePublic
                : dirname($base).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.substr(str_replace('/', DIRECTORY_SEPARATOR, $path), strlen('/storage'));
        } else {
            $full = $base.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        }

        $full = realpath($full) ?: $full;

        return is_readable($full) ? $full : null;
    }

    private static function publicRoot(): string
    {
        if (function_exists('public_path')) {
            try {
                return public_path();
            } catch (\Throwable) {
                // Fall through when the container is not fully booted (CLI scripts).
            }
        }

        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'public';
    }

    private static function sample(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'png' => @imagecreatefrompng($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'gif' => @imagecreatefromgif($path),
            default => false,
        };

        if ($img === false) {
            return 'dark';
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Copy column: left side where headline/body sit (matches hero layout).
        $x0 = (int) round($w * 0.04);
        $x1 = (int) round($w * 0.38);
        $y0 = (int) round($h * 0.16);
        $y1 = (int) round($h * 0.74);

        $stepX = max(1, (int) floor(($x1 - $x0) / 28));
        $stepY = max(1, (int) floor(($y1 - $y0) / 18));

        $sum = 0.0;
        $count = 0;

        for ($y = $y0; $y <= $y1; $y += $stepY) {
            for ($x = $x0; $x <= $x1; $x += $stepX) {
                $c = imagecolorat($img, min($x, $w - 1), min($y, $h - 1));
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $sum += 0.2126 * self::linearize($r)
                    + 0.7152 * self::linearize($g)
                    + 0.0722 * self::linearize($b);
                $count++;
            }
        }

        imagedestroy($img);

        $avg = $count > 0 ? $sum / $count : 0.0;

        // Mid-gray threshold; bias slightly toward dark so teal/mist banners stay light-on-dark.
        return $avg < 0.42 ? 'dark' : 'light';
    }

    private static function linearize(int $channel): float
    {
        $c = $channel / 255;

        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }
}
