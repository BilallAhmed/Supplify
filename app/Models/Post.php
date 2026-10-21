<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\MediaUrl;

#[Fillable([
    'post_category_id',
    'author_id',
    'title',
    'slug',
    'excerpt',
    'body',
    'featured_image',
    'is_published',
    'is_featured',
    'published_at',
    'seo_title',
    'seo_description',
])]
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function featuredImageUrl(): string
    {
        $placeholder = asset('images/blog/placeholder.svg');
        $reference = MediaUrl::normalize($this->featured_image);

        if ($reference === null) {
            return $placeholder;
        }

        // Remote / absolute URLs — trust as-is.
        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return $reference;
        }

        // Local / media refs — only use when the file actually exists.
        if (MediaUrl::absolutePath($reference) !== null) {
            return MediaUrl::toUrl($reference) ?: $placeholder;
        }

        return $placeholder;
    }

    public function hasCustomFeaturedImage(): bool
    {
        $reference = MediaUrl::normalize($this->featured_image);

        if ($reference === null) {
            return false;
        }

        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return true;
        }

        return MediaUrl::absolutePath($reference) !== null;
    }

    /**
     * Estimated reading time from the article body (200 wpm).
     * Returns null when there is not enough body content to estimate.
     */
    public function readingTimeMinutes(): ?int
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->body)) ?? '');

        if ($text === '') {
            return null;
        }

        $words = str_word_count($text);

        if ($words < 1) {
            return null;
        }

        return max(1, (int) ceil($words / 200));
    }

    public function featuredImageAlt(): string
    {
        return $this->hasCustomFeaturedImage() ? $this->title : '';
    }
}
