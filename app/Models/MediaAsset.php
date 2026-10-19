<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'disk',
    'path',
    'original_filename',
    'title',
    'alt',
    'mime_type',
    'extension',
    'width',
    'height',
    'size_bytes',
    'checksum',
    'source',
    'is_locked',
    'uploaded_by',
])]
class MediaAsset extends Model
{
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'is_locked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MediaAsset $asset): void {
            if (! filled($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return MediaUrl::toUrl($this->reference()) ?? '';
    }

    /**
     * Value stored in page/content fields (backward-compatible path).
     */
    public function reference(): string
    {
        if ($this->disk === 'public_path') {
            return ltrim($this->path, '/');
        }

        return ltrim($this->path, '/');
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->size_bytes);
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }

    public function dimensionsLabel(): string
    {
        if (! $this->width || ! $this->height) {
            return '—';
        }

        return $this->width.'×'.$this->height;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('original_filename', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhere('alt', 'like', $like)
                ->orWhere('path', 'like', $like);
        });
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }
}
