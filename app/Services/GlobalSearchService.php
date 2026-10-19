<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * @return array{q: string, results: list<array<string, mixed>>, total: int}
     */
    public function search(string $query, int $limit = 8): array
    {
        $q = trim($query);

        if (mb_strlen($q) < 2) {
            return [
                'q' => $q,
                'results' => [],
                'total' => 0,
            ];
        }

        $productLimit = min(4, $limit);
        $remaining = max(0, $limit - $productLimit);
        $postLimit = min(3, $remaining);
        $pageLimit = max(0, $remaining - $postLimit);

        $products = $this->searchProducts($q, $productLimit);
        $posts = $this->searchPosts($q, $postLimit > 0 ? $postLimit : 2);
        $pages = $this->searchPages($q, $pageLimit > 0 ? $pageLimit : 2);

        $results = [...$products, ...$posts, ...$pages];

        return [
            'q' => $q,
            'results' => $results,
            'total' => count($results),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchProducts(string $term, int $limit): array
    {
        $like = $this->likePattern($term);

        return Product::query()
            ->published()
            ->with([
                'categories:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('tags', fn (Builder $tq) => $tq->where('name', 'like', $like))
                    ->orWhereHas('categories', fn (Builder $cq) => $cq->where('name', 'like', $like));
            })
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                $category = $product->categories->first()?->name;

                return [
                    'type' => 'product',
                    'type_label' => 'Product',
                    'title' => $product->name,
                    'url' => route('products.show', $product->slug),
                    'image' => $product->displayImageUrl(),
                    'meta' => collect([$product->effectivePriceFormatted(), $category])
                        ->filter()
                        ->implode(' · '),
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPosts(string $term, int $limit): array
    {
        $like = $this->likePattern($term);

        return Post::query()
            ->published()
            ->with(['category:id,name,slug'])
            ->where(function (Builder $query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('seo_description', 'like', $like)
                    ->orWhereHas('tags', fn (Builder $tq) => $tq->where('name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $cq) => $cq->where('name', 'like', $like));
            })
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Post $post) => [
                'type' => 'article',
                'type_label' => 'Article',
                'title' => $post->title,
                'url' => route('blog.show', $post->slug),
                'image' => $post->featuredImageUrl(),
                'meta' => collect([
                    $post->category?->name,
                    $post->published_at?->format('M j, Y'),
                ])->filter()->implode(' · ') ?: 'Article',
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPages(string $term, int $limit): array
    {
        $like = $this->likePattern($term);

        return Page::query()
            ->published()
            ->where(function (Builder $query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('seo_description', 'like', $like);
            })
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Page $page) => [
                'type' => 'page',
                'type_label' => 'Page',
                'title' => $page->title,
                'url' => route('pages.show', $page->slug),
                'image' => null,
                'meta' => $page->seo_description
                    ? Str::limit($page->seo_description, 48)
                    : 'Page',
            ])
            ->all();
    }

    private function likePattern(string $term): string
    {
        $escaped = addcslashes($term, '%_\\');

        return '%'.$escaped.'%';
    }
}
