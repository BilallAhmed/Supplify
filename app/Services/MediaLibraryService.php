<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MediaLibraryService
{
    public const MAX_UPLOAD_BYTES = 2 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Directories under public/ that may be indexed (no recursive arbitrary browsing).
     *
     * @var list<string>
     */
    public const PUBLIC_INDEX_ROOTS = [
        'images/about',
        'images/contact',
        'images/products',
        'images/homepage',
        'images/favicons',
    ];

    /**
     * Top-level public/images files (logos, slides) — not nested dirs beyond whitelist.
     *
     * @var list<string>
     */
    public const PUBLIC_INDEX_GLOBS = [
        'images/*.{webp,png,jpg,jpeg,gif}',
    ];

    /**
     * Storage disk directories to index (uploaded product/content media).
     *
     * @var list<string>
     */
    public const STORAGE_INDEX_ROOTS = [
        'products',
        'posts',
        'categories',
        'homepage',
        'media',
    ];

    public function __construct(
        private readonly MediaService $media,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function indexExistingAssets(): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->collectPublicFiles() as $absolute => $relative) {
            $result = $this->upsertFromFile('public_path', $relative, $absolute, locked: true, source: 'imported');
            $created += $result === 'created' ? 1 : 0;
            $updated += $result === 'updated' ? 1 : 0;
            $skipped += $result === 'skipped' ? 1 : 0;
        }

        foreach ($this->collectStorageFiles() as $absolute => $relative) {
            $result = $this->upsertFromFile('public', $relative, $absolute, locked: false, source: 'imported');
            $created += $result === 'created' ? 1 : 0;
            $updated += $result === 'updated' ? 1 : 0;
            $skipped += $result === 'skipped' ? 1 : 0;
        }

        return compact('created', 'updated', 'skipped');
    }

    public function upload(UploadedFile $file, ?User $user = null): MediaAsset
    {
        $this->assertValidUpload($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('media', $filename, 'public');
        $absolute = Storage::disk('public')->path($path);

        $meta = $this->readFileMeta($absolute, $file->getClientOriginalName());

        return MediaAsset::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $meta['original_filename'],
            'title' => pathinfo($meta['original_filename'], PATHINFO_FILENAME),
            'alt' => null,
            'mime_type' => $meta['mime_type'],
            'extension' => $meta['extension'],
            'width' => $meta['width'],
            'height' => $meta['height'],
            'size_bytes' => $meta['size_bytes'],
            'checksum' => $meta['checksum'],
            'source' => 'uploaded',
            'is_locked' => false,
            'uploaded_by' => $user?->id,
        ]);
    }

    public function updateMeta(MediaAsset $asset, array $data): MediaAsset
    {
        $asset->fill([
            'title' => array_key_exists('title', $data) ? ($data['title'] !== null ? trim((string) $data['title']) : $asset->title) : $asset->title,
            'alt' => array_key_exists('alt', $data) ? ($data['alt'] !== null ? trim((string) $data['alt']) : null) : $asset->alt,
        ])->save();

        return $asset->refresh();
    }

    /**
     * Delete library record and file only when unlocked and not referenced.
     *
     * @return array{ok: bool, message: string}
     */
    public function delete(MediaAsset $asset, bool $force = false): array
    {
        if ($asset->is_locked && ! $force) {
            return ['ok' => false, 'message' => 'This asset is locked (site media). Remove associations first; locked files cannot be deleted.'];
        }

        $usages = $this->findUsages($asset);
        if ($usages !== [] && ! $force) {
            return [
                'ok' => false,
                'message' => 'This image is still used in: '.implode(', ', array_slice($usages, 0, 5)).(count($usages) > 5 ? '…' : ''),
            ];
        }

        if ($asset->disk === 'public' && ! $asset->is_locked) {
            $this->media->deletePublicFile($asset->path);
        }

        $asset->delete();

        return ['ok' => true, 'message' => 'Media deleted.'];
    }

    /**
     * @return list<string>
     */
    public function findUsages(MediaAsset $asset): array
    {
        $refs = array_unique(array_filter([
            $asset->reference(),
            $asset->path,
            'media:'.$asset->uuid,
        ]));

        $hits = [];

        foreach (DB::table('pages')->select(['id', 'title', 'slug', 'sections'])->get() as $page) {
            $blob = json_encode($page->sections) ?: '';
            foreach ($refs as $ref) {
                if ($ref !== '' && str_contains($blob, $ref)) {
                    $hits[] = 'Page: '.$page->title.' (/pages/'.$page->slug.')';
                    break;
                }
            }
        }

        foreach (DB::table('homepage_sections')->select(['id', 'key', 'label', 'image_path'])->get() as $section) {
            foreach ($refs as $ref) {
                if ($ref !== '' && (string) $section->image_path === $ref) {
                    $hits[] = 'Homepage: '.($section->label ?: $section->key);
                    break;
                }
            }
        }

        if (Schema::hasTable('posts')) {
            foreach (DB::table('posts')->select(['id', 'title', 'featured_image'])->get() as $post) {
                foreach ($refs as $ref) {
                    if ($ref !== '' && (string) $post->featured_image === $ref) {
                        $hits[] = 'Post: '.$post->title;
                        break;
                    }
                }
            }
        }

        return $hits;
    }

    public function toArray(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'uuid' => $asset->uuid,
            'disk' => $asset->disk,
            'path' => $asset->path,
            'reference' => $asset->reference(),
            'url' => $asset->url(),
            'original_filename' => $asset->original_filename,
            'title' => $asset->title,
            'alt' => $asset->alt,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'width' => $asset->width,
            'height' => $asset->height,
            'dimensions' => $asset->dimensionsLabel(),
            'size_bytes' => $asset->size_bytes,
            'human_size' => $asset->humanSize(),
            'source' => $asset->source,
            'is_locked' => $asset->is_locked,
            'created_at' => optional($asset->created_at)?->toIso8601String(),
        ];
    }

    public function assertValidUpload(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('The upload failed. Please try again.');
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new InvalidArgumentException('Images must be 2MB or smaller.');
        }

        $mime = $file->getMimeType() ?: '';
        $realMime = @mime_content_type($file->getRealPath()) ?: $mime;
        if (! in_array($realMime, self::ALLOWED_MIMES, true) && ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException('Only JPG, PNG, WebP, and GIF images are allowed.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        if ($extension === 'svg' || str_contains($realMime, 'svg')) {
            throw new InvalidArgumentException('SVG uploads are not allowed.');
        }
    }

    /**
     * @return array<string, string> absolute => relative
     */
    private function collectPublicFiles(): array
    {
        $map = [];
        $publicRoot = public_path();

        foreach (self::PUBLIC_INDEX_ROOTS as $root) {
            $dir = public_path($root);
            if (! is_dir($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if (! $this->isIndexableImage($file->getPathname())) {
                    continue;
                }
                $relative = ltrim(str_replace('\\', '/', Str::after($file->getPathname(), $publicRoot)), '/');
                if ($this->isUnsafeRelative($relative)) {
                    continue;
                }
                $map[$file->getPathname()] = $relative;
            }
        }

        foreach (glob(public_path('images').'/*.{webp,png,jpg,jpeg,gif}', GLOB_BRACE) ?: [] as $file) {
            if (! is_file($file) || ! $this->isIndexableImage($file)) {
                continue;
            }
            $relative = 'images/'.basename($file);
            $map[$file] = $relative;
        }

        return $map;
    }

    /**
     * @return array<string, string> absolute => relative
     */
    private function collectStorageFiles(): array
    {
        $map = [];
        $disk = Storage::disk('public');

        foreach (self::STORAGE_INDEX_ROOTS as $root) {
            if (! $disk->exists($root)) {
                continue;
            }
            foreach ($disk->allFiles($root) as $relative) {
                $absolute = $disk->path($relative);
                if (! $this->isIndexableImage($absolute)) {
                    continue;
                }
                if ($this->isUnsafeRelative($relative)) {
                    continue;
                }
                $map[$absolute] = $relative;
            }
        }

        return $map;
    }

    private function isIndexableImage(string $absolute): bool
    {
        if (! is_file($absolute)) {
            return false;
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (! in_array($ext, [...self::ALLOWED_EXTENSIONS, 'svg'], true)) {
            return false;
        }

        // Skip SVG from library selection unless already present as decorative; still skip executables.
        if ($ext === 'svg') {
            return false;
        }

        $basename = basename($absolute);
        if (str_starts_with($basename, '.') || str_contains($basename, '.php') || str_ends_with($basename, '.tmp')) {
            return false;
        }

        return true;
    }

    private function isUnsafeRelative(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);
        if (str_contains($relative, '..') || str_starts_with($relative, '/')) {
            return true;
        }

        $forbidden = ['.git', 'vendor', 'node_modules', 'storage/framework', 'bootstrap/cache'];
        foreach ($forbidden as $part) {
            if (str_contains($relative, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertFromFile(string $disk, string $relative, string $absolute, bool $locked, string $source): string
    {
        try {
            $meta = $this->readFileMeta($absolute, basename($absolute));
        } catch (RuntimeException) {
            return 'skipped';
        }

        $existing = MediaAsset::query()->where('disk', $disk)->where('path', $relative)->first();
        if ($existing) {
            $existing->fill([
                'original_filename' => $existing->original_filename ?: $meta['original_filename'],
                'mime_type' => $meta['mime_type'],
                'extension' => $meta['extension'],
                'width' => $meta['width'],
                'height' => $meta['height'],
                'size_bytes' => $meta['size_bytes'],
                'checksum' => $meta['checksum'],
                'is_locked' => $locked || $existing->is_locked,
            ])->save();

            return 'updated';
        }

        MediaAsset::query()->create([
            'disk' => $disk,
            'path' => $relative,
            'original_filename' => $meta['original_filename'],
            'title' => pathinfo($meta['original_filename'], PATHINFO_FILENAME),
            'alt' => null,
            'mime_type' => $meta['mime_type'],
            'extension' => $meta['extension'],
            'width' => $meta['width'],
            'height' => $meta['height'],
            'size_bytes' => $meta['size_bytes'],
            'checksum' => $meta['checksum'],
            'source' => $source,
            'is_locked' => $locked,
            'uploaded_by' => null,
        ]);

        return 'created';
    }

    /**
     * @return array{original_filename: string, mime_type: string, extension: string, width: ?int, height: ?int, size_bytes: int, checksum: string}
     */
    private function readFileMeta(string $absolute, string $originalFilename): array
    {
        if (! is_file($absolute)) {
            throw new RuntimeException('File missing.');
        }

        $mime = @mime_content_type($absolute) ?: 'application/octet-stream';
        $size = @getimagesize($absolute);
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: '');

        return [
            'original_filename' => $originalFilename,
            'mime_type' => $mime,
            'extension' => $extension,
            'width' => is_array($size) ? (int) $size[0] : null,
            'height' => is_array($size) ? (int) $size[1] : null,
            'size_bytes' => (int) filesize($absolute),
            'checksum' => hash_file('sha1', $absolute) ?: '',
        ];
    }
}
