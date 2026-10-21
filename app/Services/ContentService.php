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
     * Premium homepage hero slides — full campaign scenes with live HTML copy.
     *
     * Optional CMS override: homepage_sections.config.slides[] may supply
     * scene / scene_mobile paths (media library relative paths), tone, and copy.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function heroSlides(?HomepageSection $hero = null): Collection
    {
        $defaults = $this->premiumHeroSlides();
        $configured = $hero?->config['slides'] ?? null;

        // Legacy CMS slides stored cutout `products[]` layers — ignore those and use campaign defaults.
        if (! is_array($configured) || $configured === [] || ! $this->hasCampaignSceneSlides($configured)) {
            return collect($defaults)->values();
        }

        $merged = [];
        foreach ($configured as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $base = $defaults[$index] ?? $defaults[0] ?? [
                'eyebrow' => 'Supplify',
                'title' => config('app.name'),
                'body' => '',
                'cta_label' => 'Shop Supplements',
                'cta_url' => '/shop',
                'tone' => 'dark',
                'scene' => '',
                'scene_mobile' => '',
                'scene_alt' => 'Supplify products',
                'width' => 1920,
                'height' => 900,
                'mobile_width' => 1080,
                'mobile_height' => 1350,
            ];

            $scene = $this->resolveHeroScenePath($row['scene'] ?? $row['image'] ?? null) ?? ($base['scene_path'] ?? null);
            $sceneMobile = $this->resolveHeroScenePath($row['scene_mobile'] ?? $row['image_mobile'] ?? null)
                ?? ($base['scene_mobile_path'] ?? $scene);

            if (! $scene) {
                continue;
            }

            $merged[] = [
                'eyebrow' => (string) ($row['eyebrow'] ?? $base['eyebrow'] ?? ''),
                'title' => (string) ($row['title'] ?? $base['title'] ?? config('app.name')),
                'body' => (string) ($row['body'] ?? $base['body'] ?? ''),
                'cta_label' => (string) ($row['cta_label'] ?? $base['cta_label'] ?? ''),
                'cta_url' => (string) ($row['cta_url'] ?? $base['cta_url'] ?? '/shop'),
                'product_sku' => $row['product_sku'] ?? ($base['product_sku'] ?? null),
                'tone' => (($row['tone'] ?? $base['tone'] ?? 'dark') === 'light') ? 'light' : 'dark',
                'scene' => $this->versionedAsset($scene),
                'scene_mobile' => $this->versionedAsset($sceneMobile ?: $scene),
                'scene_alt' => (string) ($row['scene_alt'] ?? $base['scene_alt'] ?? 'Supplify products'),
                'focus' => (string) ($row['focus'] ?? $base['focus'] ?? '70% center'),
                'width' => (int) ($row['width'] ?? $base['width'] ?? 1905),
                'height' => (int) ($row['height'] ?? $base['height'] ?? 732),
                'mobile_width' => (int) ($row['mobile_width'] ?? $base['mobile_width'] ?? $base['width'] ?? 1905),
                'mobile_height' => (int) ($row['mobile_height'] ?? $base['mobile_height'] ?? $base['height'] ?? 732),
            ];
        }

        return collect($merged !== [] ? $merged : $defaults)->values();
    }

    /**
     * @param  list<mixed>  $slides
     */
    private function hasCampaignSceneSlides(array $slides): bool
    {
        foreach ($slides as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($this->resolveHeroScenePath($row['scene'] ?? $row['image'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function resolveHeroScenePath(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        if (is_file(public_path($path))) {
            return $path;
        }

        return null;
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
                'subtitle' => 'Supplify',
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
                        ['title' => 'Careful fulfillment', 'body' => 'Orders prepared for reliable domestic delivery.', 'icon' => 'package'],
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
                        ['label' => '04', 'title' => 'Packaging & delivery', 'body' => 'Finished goods are packed for careful transit to your door.', 'icon' => 'package'],
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
     * Canonical lifestyle hero slides (complete banners; no cutout product layers).
     *
     * @return list<array<string, mixed>>
     */
    public function defaultHeroSlideDefinitions(): array
    {
        return [
            [
                'eyebrow' => 'Vitamin E',
                'title' => 'Vitamin E 400 IU',
                'body' => 'A clear-label softgel formula for everyday routines.',
                'cta_label' => 'Shop Vitamin E',
                'cta_url' => '/products/vitamin-e-400iu-120-softgels',
                'product_sku' => 'SUP-VITAMIN-E-400IU-120',
                'tone' => 'dark',
                'scene' => 'images/homepage/hero-lifestyle-vitamins.webp',
                'scene_mobile' => 'images/homepage/hero-lifestyle-vitamins.webp',
                'scene_alt' => 'Person holding Supplify Vitamin E 400 IU in a bright kitchen',
                'focus' => '68% center',
                'width' => 1905,
                'height' => 732,
            ],
            [
                'eyebrow' => 'Minerals',
                'title' => 'Magnesium 250mg',
                'body' => 'A straightforward mineral essential for daily wellness routines.',
                'cta_label' => 'Shop Magnesium',
                'cta_url' => '/products/magnesium-250mg-90-tablets',
                'product_sku' => 'SUP-MAGNESIUM-250MG-90',
                'tone' => 'dark',
                'scene' => 'images/homepage/hero-lifestyle-magnesium.webp',
                'scene_mobile' => 'images/homepage/hero-lifestyle-magnesium.webp',
                'scene_alt' => 'Person with Supplify Magnesium 250mg on a side table',
                'focus' => '72% center',
                'width' => 1905,
                'height' => 732,
            ],
            [
                'eyebrow' => 'Essentials',
                'title' => 'CoQ10 200mg',
                'body' => 'A focused formula with a transparent label for your collection.',
                'cta_label' => 'Shop CoQ10',
                'cta_url' => '/products/coq10-200mg-120-softgels',
                'product_sku' => 'SUP-COQ10-200MG-120',
                'tone' => 'dark',
                'scene' => 'images/homepage/hero-lifestyle-coq10.webp',
                'scene_mobile' => 'images/homepage/hero-lifestyle-coq10.webp',
                'scene_alt' => 'Hands holding Supplify CoQ10 200mg on a sunlit table',
                'focus' => '78% center',
                'width' => 1905,
                'height' => 732,
            ],
        ];
    }

    /**
     * Finished lifestyle banners (products already in-scene). No cutout layers.
     *
     * @return list<array<string, mixed>>
     */
    private function premiumHeroSlides(): array
    {
        return array_map(function (array $slide) {
            $desktop = $slide['scene'];
            $mobile = $slide['scene_mobile'] ?? $desktop;
            $w = (int) ($slide['width'] ?? 1905);
            $h = (int) ($slide['height'] ?? 732);

            return [
                'eyebrow' => $slide['eyebrow'],
                'title' => $slide['title'],
                'body' => $slide['body'],
                'cta_label' => $slide['cta_label'],
                'cta_url' => $slide['cta_url'],
                'product_sku' => $slide['product_sku'] ?? null,
                'tone' => $slide['tone'],
                'scene_path' => $desktop,
                'scene_mobile_path' => $mobile,
                'scene' => $this->versionedAsset($desktop),
                'scene_mobile' => $this->versionedAsset($mobile),
                'scene_alt' => $slide['scene_alt'],
                'focus' => $slide['focus'] ?? '70% center',
                'width' => $w,
                'height' => $h,
                'mobile_width' => $w,
                'mobile_height' => $h,
            ];
        }, $this->defaultHeroSlideDefinitions());
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
