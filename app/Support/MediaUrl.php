<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

final class MediaUrl
{
    /**
     * Resolve a stored media reference to a public URL.
     */
    public static function toUrl(?string $reference): ?string
    {
        $reference = self::normalize($reference);
        if ($reference === null) {
            return null;
        }

        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return $reference;
        }

        if (str_starts_with($reference, 'media:')) {
            $uuid = substr($reference, 6);
            $asset = MediaAsset::query()->where('uuid', $uuid)->first();

            return $asset?->url();
        }

        if (str_starts_with($reference, 'images/') || str_starts_with($reference, '/images/')) {
            return asset(ltrim($reference, '/'));
        }

        if (str_starts_with($reference, 'storage/')) {
            return asset($reference);
        }

        // Laravel public disk paths (products/1/…, media/…, homepage/…)
        if (Storage::disk('public')->exists($reference)) {
            return Storage::disk('public')->url($reference);
        }

        // Fallback: treat as public/ relative path
        if (is_file(public_path($reference))) {
            return asset($reference);
        }

        return Storage::disk('public')->url($reference);
    }

    /**
     * Normalize legacy and picker values for storage in content fields.
     */
    public static function normalize(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $reference = trim(str_replace('\\', '/', $reference));
        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, 'media:')) {
            return $reference;
        }

        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return $reference;
        }

        // Strip leading slash and optional /public/
        $reference = ltrim($reference, '/');
        if (str_starts_with($reference, 'public/')) {
            $reference = substr($reference, 7);
        }

        // Absolute URL path like /storage/foo → storage-relative foo
        if (str_starts_with($reference, 'storage/')) {
            return substr($reference, 8);
        }

        return $reference;
    }

    /**
     * Absolute filesystem path for a reference, if local.
     */
    public static function absolutePath(?string $reference): ?string
    {
        $reference = self::normalize($reference);
        if ($reference === null || str_starts_with($reference, 'http')) {
            return null;
        }

        if (str_starts_with($reference, 'media:')) {
            $uuid = substr($reference, 6);
            $asset = MediaAsset::query()->where('uuid', $uuid)->first();
            if (! $asset) {
                return null;
            }
            $reference = $asset->path;
            if ($asset->disk === 'public_path') {
                return public_path($reference);
            }

            return Storage::disk('public')->path($reference);
        }

        if (str_starts_with($reference, 'images/')) {
            $path = public_path($reference);

            return is_file($path) ? $path : null;
        }

        $storagePath = Storage::disk('public')->path($reference);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        $publicPath = public_path($reference);

        return is_file($publicPath) ? $publicPath : null;
    }
}
