<?php

namespace App\Services;

use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContentService
{
    /**
     * Canonical homepage section order for a balanced light/dark rhythm.
     * Legacy aliases (featured → featured_products, categories → shop_by_goal) are collapsed.
     * Testimonials are excluded from the storefront (removed “From the community”).
     */
    public function enabledHomepageSections(): Collection
    {
        $alias = [
            'featured' => 'featured_products',
            'categories' => 'shop_by_goal',
        ];

        $preferred = [
            'hero',
            'trust',
            'shop_by_goal',
            'featured_products',
            'explore_more',
            'story',
            'education',
            'promo',
            'newsletter',
        ];

        $rows = HomepageSection::query()
            ->enabled()
            ->orderBy('sort_order')
            ->get();

        // Fresh / unseeded databases would otherwise render an empty homepage shell.
        if ($rows->isEmpty()) {
            $rows = collect($this->defaultHomepageSections())
                ->map(fn (array $attrs) => new HomepageSection($attrs));
        }

        $map = [];
        foreach ($rows as $section) {
            if ($section->key === 'testimonials') {
                continue;
            }

            $canonical = $alias[$section->key] ?? $section->key;

            if ($canonical === 'promo' && ! $this->isPromoLive($section)) {
                continue;
            }

            if (! isset($map[$canonical]) || $section->key === $canonical) {
                $map[$canonical] = $section;
            }
        }

        $ordered = collect();
        foreach ($preferred as $key) {
            if (isset($map[$key])) {
                $ordered->put($key, $map[$key]);
                unset($map[$key]);
            }
        }

        foreach ($map as $key => $section) {
            $ordered->put($key, $section);
        }

        return $ordered;
    }

    public function isPromoLive(HomepageSection $section): bool
    {
        if (! $section->is_enabled) {
            return false;
        }

        if (! filled($section->title) && ! filled($section->image_path) && ! filled($section->body)) {
            return false;
        }

        $config = $section->config ?? [];
        $starts = $config['starts_at'] ?? null;
        $ends = $config['ends_at'] ?? null;

        if (filled($starts) && now()->lt($starts)) {
            return false;
        }

        if (filled($ends) && now()->gt($ends)) {
            return false;
        }

        return true;
    }

    public function featuredProducts(int $limit = 12): Collection
    {
        return Product::query()
            ->published()
            ->where('is_featured', true)
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'variations' => fn ($q) => $q->where('is_enabled', true),
            ])
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Additional catalog products for the homepage — prefers non-featured SKUs
     * ordered by real published_at (no invented “new” claims).
     *
     * @param  list<int>  $excludeIds
     */
    public function exploreMoreProducts(int $limit = 4, array $excludeIds = []): Collection
    {
        $limit = max(1, min(12, $limit));

        $query = Product::query()
            ->published()
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'variations' => fn ($q) => $q->where('is_enabled', true),
            ])
            ->latest('published_at');

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $products = (clone $query)
            ->where('is_featured', false)
            ->limit($limit)
            ->get();

        if ($products->count() >= $limit) {
            return $products;
        }

        $needed = $limit - $products->count();
        $exclude = array_values(array_unique([
            ...$excludeIds,
            ...$products->pluck('id')->all(),
        ]));

        $fill = Product::query()
            ->published()
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'variations' => fn ($q) => $q->where('is_enabled', true),
            ])
            ->latest('published_at')
            ->limit($needed)
            ->get();

        return $products->concat($fill)->values();
    }

    /**
     * Premium homepage hero slides — text left, floating product PNGs right.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function heroSlides(?HomepageSection $hero = null): Collection
    {
        return collect($this->premiumHeroSlides())->values();
    }

    public function findPublishedPage(string $slug): Page
    {
        return Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function uniquePageSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 1;

        while (Page::withTrashed()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * Minimal enabled sections used when the CMS table has not been seeded yet.
     *
     * @return list<array<string, mixed>>
     */
    private function defaultHomepageSections(): array
    {
        return [
            [
                'key' => 'hero',
                'title' => 'Formulas you can read',
                'subtitle' => 'US Supplement Co.',
                'body' => 'Clean formulas. Clear labels. Built for people who take supplements seriously.',
                'cta_label' => 'Shop catalog',
                'cta_url' => '/shop',
                'config' => [],
                'is_enabled' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'trust',
                'title' => 'Shopping with confidence',
                'config' => [
                    'items' => [
                        ['title' => 'Transparent labels', 'body' => 'Amounts you can verify at a glance.', 'icon' => 'label'],
                        ['title' => 'Thoughtful forms', 'body' => 'Ingredients chosen for clarity, not hype.', 'icon' => 'flask'],
                        ['title' => 'US-focused fulfillment', 'body' => 'Shipping designed around domestic routines.', 'icon' => 'package'],
                    ],
                ],
                'is_enabled' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'shop_by_goal',
                'title' => 'Shop by goal',
                'subtitle' => 'Find your lane',
                'body' => 'Browse formulas by the outcomes they support.',
                'config' => ['limit' => 4],
                'is_enabled' => true,
                'sort_order' => 30,
            ],
            [
                'key' => 'featured_products',
                'title' => 'Featured formulas',
                'subtitle' => 'Staff picks',
                'body' => 'A short list of products we recommend starting with.',
                'cta_label' => 'View All Products',
                'cta_url' => '/shop',
                'config' => ['limit' => 12],
                'is_enabled' => true,
                'sort_order' => 40,
            ],
            [
                'key' => 'explore_more',
                'title' => 'Explore More Formulas',
                'subtitle' => 'From the catalog',
                'body' => 'More published products from the Supplify catalog, ordered by publication date.',
                'cta_label' => 'View All Products',
                'cta_url' => '/shop',
                'config' => ['limit' => 4],
                'is_enabled' => true,
                'sort_order' => 45,
            ],
            [
                'key' => 'story',
                'title' => 'Our journey',
                'subtitle' => 'From source to doorstep',
                'body' => 'A short look at how we move from ingredient selection to the bottle you receive.',
                'cta_label' => 'Discover Our Approach',
                'cta_url' => '/pages/about',
                'image_path' => 'images/homepage/our-journey.webp',
                'config' => [
                    'visual' => 'images/homepage/our-journey.webp',
                    'visual_caption' => 'Formulas shaped for everyday routines',
                    'beats' => [
                        ['label' => '01', 'title' => 'Ingredient selection', 'body' => 'We evaluate ingredients for clarity of purpose and fit within a daily routine.', 'icon' => 'leaf'],
                        ['label' => '02', 'title' => 'Formulation', 'body' => 'Formulas are drafted for readable labels and practical serving sizes.', 'icon' => 'flask'],
                        ['label' => '03', 'title' => 'Manufacturing & checks', 'body' => 'Production partners follow agreed quality checks before products ship.', 'icon' => 'shield'],
                        ['label' => '04', 'title' => 'Packaging & delivery', 'body' => 'Finished goods are packed for US-focused fulfillment and careful transit.', 'icon' => 'package'],
                    ],
                ],
                'is_enabled' => true,
                'sort_order' => 50,
            ],
            [
                'key' => 'education',
                'title' => 'Learn before you buy',
                'subtitle' => 'Guides',
                'body' => 'Short reads that help you choose with confidence.',
                'cta_label' => 'View all articles',
                'cta_url' => '/blog',
                'config' => ['limit' => 3],
                'is_enabled' => true,
                'sort_order' => 60,
            ],
            [
                'key' => 'newsletter',
                'title' => 'Stay in the loop',
                'subtitle' => 'Newsletter',
                'body' => 'New formulas, restocks, and practical guidance — no spam.',
                'cta_label' => 'Subscribe',
                'config' => [],
                'is_enabled' => true,
                'sort_order' => 80,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function premiumHeroSlides(): array
    {
        $shop = '/shop';

        return [
            [
                'eyebrow' => 'Supplify',
                'title' => 'Build your daily wellness routine',
                'body' => 'Explore supplements that fit your everyday routine.',
                'cta_label' => 'Shop Supplements',
                'cta_url' => $shop,
                'slide_number' => '01',
                'background' => $this->versionedAsset('images/supplify-slide-1-fitness.webp'),
                'background_alt' => 'Fitness studio',
                'products' => [
                    [
                        'name' => 'Vitamin E',
                        'src' => $this->versionedAsset('images/products/1S5A6197.png'),
                        'width' => 426,
                        'height' => 740,
                        'role' => 'support-left',
                        'depth' => 0.55,
                    ],
                    [
                        'name' => 'Vitamin B-50 Complex',
                        'src' => $this->versionedAsset('images/products/1S5A6200_1.png'),
                        'width' => 400,
                        'height' => 801,
                        'role' => 'foreground',
                        'depth' => 1,
                    ],
                    [
                        'name' => 'L-Carnitine',
                        'src' => $this->versionedAsset('images/products/1S5A4428.png'),
                        'width' => 414,
                        'height' => 758,
                        'role' => 'support-right',
                        'depth' => 0.7,
                    ],
                ],
            ],
            [
                'eyebrow' => 'Essentials',
                'title' => 'Make room for everyday essentials',
                'body' => 'Discover our mineral collection and find your next essential.',
                'cta_label' => 'Explore Minerals',
                'cta_url' => $shop,
                'slide_number' => '02',
                'background' => $this->versionedAsset('images/supplify-slide-2-nutrition.webp'),
                'background_alt' => 'Healthy nutrition',
                'products' => [
                    [
                        'name' => 'Magnesium',
                        'src' => $this->versionedAsset('images/products/1S5A3686.png'),
                        'width' => 403,
                        'height' => 740,
                        'role' => 'foreground',
                        'depth' => 1,
                    ],
                    [
                        'name' => 'Calcium Citrate',
                        'src' => $this->versionedAsset('images/products/1S5A3688.png'),
                        'width' => 393,
                        'height' => 729,
                        'role' => 'support-right',
                        'depth' => 0.65,
                    ],
                ],
            ],
            [
                'eyebrow' => 'Collection',
                'title' => 'Discover your next essential',
                'body' => 'Browse the Supplify collection and choose your next addition.',
                'cta_label' => 'Explore the Collection',
                'cta_url' => $shop,
                'slide_number' => '03',
                'background' => $this->versionedAsset('images/supplify-slide-3-active-life.webp'),
                'background_alt' => 'Outdoor active lifestyle',
                'products' => [
                    [
                        'name' => 'CoQ10',
                        'src' => $this->versionedAsset('images/products/1S5A6193.png'),
                        'width' => 441,
                        'height' => 773,
                        'role' => 'support-left',
                        'depth' => 0.55,
                    ],
                    [
                        'name' => 'NADH',
                        'src' => $this->versionedAsset('images/products/1S5A6199.png'),
                        'width' => 423,
                        'height' => 845,
                        'role' => 'foreground',
                        'depth' => 1,
                    ],
                    [
                        'name' => 'Rutin',
                        'src' => $this->versionedAsset('images/products/1S5A6201_1.png'),
                        'width' => 422,
                        'height' => 843,
                        'role' => 'support-right',
                        'depth' => 0.7,
                    ],
                ],
            ],
        ];
    }

    private function versionedAsset(string $path): string
    {
        $url = asset($path);
        $full = public_path($path);

        if (is_file($full)) {
            $url .= '?v='.filemtime($full);
        }

        return $url;
    }
}
