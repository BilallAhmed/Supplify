<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function storeProductImage(UploadedFile $file, int $productId): string
    {
        $directory = 'products/'.$productId;
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'public');
    }

    public function storePostImage(UploadedFile $file, int $postId): string
    {
        $directory = 'posts/'.$postId;
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'public');
    }

    /**
     * Store a category goal icon. PNG/WebP only — SVG is not accepted (no sanitizer).
     */
    public function storeCategoryIcon(UploadedFile $file, int $categoryId): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        if (! in_array($extension, ['png', 'webp'], true)) {
            $extension = 'png';
        }

        $directory = 'categories/'.$categoryId.'/icons';
        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($directory, $filename, 'public');
    }

    public function storeHomepageImage(UploadedFile $file, string $sectionKey): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $directory = 'homepage/'.$sectionKey;
        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($directory, $filename, 'public');
    }

    public function copyPublicFile(string $path, string $destinationDirectory): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = trim($destinationDirectory, '/').'/'.Str::uuid()->toString().($extension ? '.'.$extension : '');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->copy($path, $newPath);
        }

        return $newPath;
    }

    public function deletePublicFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
