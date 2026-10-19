<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class BlogService
{
    public function __construct(
        private readonly MediaService $media,
    ) {}

    /**
     * @param  array{q?: string, category?: string, tag?: string, per_page?: int}  $filters
     */
    public function paginatePublished(array $filters = []): LengthAwarePaginator
    {
        $query = Post::query()
            ->published()
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name']);

        if (! empty($filters['q'])) {
            $term = '%'.addcslashes(trim((string) $filters['q']), '%_\\').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('body', 'like', $term);
            });
        }

        if (! empty($filters['category'])) {
            $query->whereHas(
                'category',
                fn (Builder $q) => $q->where('slug', $filters['category'])
            );
        }

        if (! empty($filters['tag'])) {
            $query->whereHas(
                'tags',
                fn (Builder $q) => $q->where('slug', $filters['tag'])
            );
        }

        return $query
            ->latest('published_at')
            ->paginate((int) ($filters['per_page'] ?? 9))
            ->withQueryString();
    }

    public function findPublished(string $slug): Post
    {
        return Post::query()
            ->published()
            ->where('slug', $slug)
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->firstOrFail();
    }

    public function relatedPosts(Post $post, int $limit = 3): Collection
    {
        $sameCategory = Post::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when(
                $post->post_category_id,
                fn (Builder $q) => $q->where('post_category_id', $post->post_category_id)
            )
            ->with(['category:id,name,slug'])
            ->latest('published_at')
            ->limit($limit)
            ->get();

        if ($sameCategory->count() >= $limit || ! $post->post_category_id) {
            return $sameCategory;
        }

        $extra = Post::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->whereNotIn('id', $sameCategory->pluck('id'))
            ->with(['category:id,name,slug'])
            ->latest('published_at')
            ->limit($limit - $sameCategory->count())
            ->get();

        return $sameCategory->concat($extra)->values();
    }

    public function latestPublished(int $limit = 3): Collection
    {
        return Post::query()
            ->published()
            ->with(['category:id,name,slug'])
            ->latest('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Homepage education grid — stable published order for progressive pagination.
     */
    public function paginateHomepageEducation(int $perPage = 6, ?int $page = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = max(1, min(12, $perPage));

        return Post::query()
            ->published()
            ->with(['category:id,name,slug'])
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName, $page);
    }

    public function activeCategories(): Collection
    {
        return PostCategory::query()
            ->withCount([
                'posts as published_posts_count' => fn (Builder $q) => $q->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Post $post, array $data, ?UploadedFile $image = null, ?int $authorId = null): Post
    {
        $post->fill([
            'title' => $data['title'],
            'slug' => filled($data['slug'] ?? null)
                ? $this->uniqueSlug($data['slug'], $post->id)
                : $this->uniqueSlug($data['title'], $post->id),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'post_category_id' => $data['post_category_id'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        if (array_key_exists('featured_image', $data) && ! $image) {
            // Association only — clearing does not delete shared media files.
            $post->featured_image = $data['featured_image'] ?: null;
        }

        if ($authorId && ! $post->author_id) {
            $post->author_id = $authorId;
        }

        if ($post->is_published && ! $post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        if ($image) {
            $post->featured_image = $this->media->storePostImage($image, $post->id);
            $post->save();
        }

        $this->syncTags($post, $data['tags'] ?? null);

        return $post->fresh(['category', 'tags', 'author']);
    }

    public function delete(Post $post): void
    {
        if ($post->featured_image && ! str_starts_with($post->featured_image, 'images/')) {
            $this->media->deletePublicFile($post->featured_image);
        }

        $post->tags()->detach();
        $post->delete();
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 1;

        while (Post::withTrashed()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function uniqueCategorySlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;

        while (PostCategory::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function syncTags(Post $post, mixed $tagsInput): void
    {
        $tagNames = collect(explode(',', (string) $tagsInput))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values();

        $tagIds = $tagNames->map(function (string $name) {
            $slug = Str::slug($name) ?: Str::random(6);

            return Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        });

        $post->tags()->sync($tagIds->all());
    }
}
