<?php

namespace App\Services;

use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Product;
use App\Support\HomepageLayout;
use App\Support\MediaUrl;
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
        $rows = HomepageSection::query()->whereIn('key', HomepageLayout::ORDER)->get()->keyBy('key');
        $ordered = collect();
        foreach (HomepageLayout::ORDER as $key) {
            $section = $rows->get($key) ?? new HomepageSection(HomepageLayout::defaults($key));
            if ($section->is_enabled) {
                $ordered->put($key, $section);
            }
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
        $defaults = $this->approvedHeroSlideDefinitions();
        $configured = $hero?->config['slides'] ?? [];

        return collect($defaults)->map(function ($base, $index) use ($configured) {
            $row = array_replace($base, $configured[$index] ?? []);
            foreach (['scene', 'scene_mobile'] as $field) {
                $path = $row[$field] ?: $base[$field];
                $row[$field] = HomepageLayout::image($path);
                $size = MediaUrl::absolutePath($path);
                $dimensions = $size ? @getimagesize($size) : false;
                if ($dimensions) {
                    $prefix = $field === 'scene_mobile' ? 'mobile_' : '';
                    $row[$prefix.'width'] = $dimensions[0];
                    $row[$prefix.'height'] = $dimensions[1];
                }
            }
            $row['layer_products'] = [];
            $row['mist'] = false;
            $row['composition'] = 'integrated';
            $row['tone'] = 'dark';
            $row['focus'] = 'center';

            return $row;
        });
    }

    public function approvedHeroSlideDefinitions(): array
    {
        return $this->withHeroLayers(collect($this->defaultHeroSlideDefinitions()))->map(function ($slide) {
            $slide['scene'] = ltrim(parse_url($slide['scene'], PHP_URL_PATH), '/');
            $slide['scene_mobile'] = ltrim(parse_url($slide['scene_mobile'], PHP_URL_PATH), '/');

            return $slide;
        })->all();
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
     * Canonical campaign hero slides — grounded studio compositions (no cutout layers).
     *
     * @return list<array<string, mixed>>
     */
    public function defaultHeroSlideDefinitions(): array
    {
        return [
            [
                'eyebrow' => 'Everyday Essentials',
                'title_accent' => 'Everyday',
                'title' => 'Wellness',
                'body' => 'Explore vitamins and essentials for your everyday routine.',
                'cta_label' => 'Explore Vitamins',
                'cta_url' => '/shop?q=vitamin',
                'tone' => 'dark',
                'composition' => 'studio',
                'mist' => true,
                'scene' => 'images/homepage/hero/campaign-daily-vitamins-desktop.webp',
                'scene_mobile' => 'images/homepage/hero/campaign-daily-vitamins-mobile.webp',
                'scene_alt' => 'Supplify Vitamin E, L-Carnitine, and Vitamin B-50 on stone pedestals',
                'focus' => '74% center',
                'width' => 1920,
                'height' => 900,
                'mobile_width' => 1080,
                'mobile_height' => 1350,
            ],
            [
                'eyebrow' => 'Mineral Essentials',
                'title_accent' => 'Build Your',
                'title' => 'Daily Routine',
                'body' => 'Discover magnesium, calcium, and more, with clear product information.',
                'cta_label' => 'Explore Minerals',
                'cta_url' => '/shop?q=magnesium',
                'tone' => 'dark',
                'composition' => 'studio',
                'mist' => true,
                'scene' => 'images/homepage/hero/campaign-minerals-desktop.webp',
                'scene_mobile' => 'images/homepage/hero/campaign-minerals-mobile.webp',
                'scene_alt' => 'Supplify Magnesium and Calcium Citrate on stone pedestals',
                'focus' => '76% center',
                'width' => 1920,
                'height' => 900,
                'mobile_width' => 1080,
                'mobile_height' => 1350,
            ],
            [
                'eyebrow' => 'Explore Supplify',
                'title_accent' => 'Find Your',
                'title' => 'Next Essential',
                'body' => 'Explore the Supplify range and find your next everyday essential.',
                'cta_label' => 'View All Products',
                'cta_url' => '/shop',
                'tone' => 'dark',
                'composition' => 'studio',
                'mist' => true,
                'scene' => 'images/homepage/hero/campaign-collection-desktop.webp',
                'scene_mobile' => 'images/homepage/hero/campaign-collection-mobile.webp',
                'scene_alt' => 'Supplify collection bottles on dark teal pedestals',
                'focus' => '70% center',
                'width' => 1920,
                'height' => 900,
                'mobile_width' => 1080,
                'mobile_height' => 1350,
            ],
        ];
    }

    /**
     * Finished campaign banners (products already in-scene). No cutout layers.
     *
     * @return list<array<string, mixed>>
     */
    private function premiumHeroSlides(): array
    {
        return array_map(function (array $slide) {
            $desktop = $slide['scene'];
            $mobile = $slide['scene_mobile'] ?? $desktop;
            $w = (int) ($slide['width'] ?? 1920);
            $h = (int) ($slide['height'] ?? 900);
            $mw = (int) ($slide['mobile_width'] ?? 1080);
            $mh = (int) ($slide['mobile_height'] ?? 1350);

            return [
                'eyebrow' => $slide['eyebrow'],
                'title_accent' => $slide['title_accent'] ?? '',
                'title' => $slide['title'],
                'body' => $slide['body'],
                'cta_label' => $slide['cta_label'],
                'cta_url' => $slide['cta_url'],
                'product_sku' => $slide['product_sku'] ?? null,
                'tone' => $slide['tone'],
                'composition' => $slide['composition'] ?? 'studio',
                'mist' => (bool) ($slide['mist'] ?? true),
                'scene_path' => $desktop,
                'scene_mobile_path' => $mobile,
                'scene' => $this->versionedAsset($desktop),
                'scene_mobile' => $this->versionedAsset($mobile),
                'scene_alt' => $slide['scene_alt'],
                'focus' => $slide['focus'] ?? '72% center',
                'width' => $w,
                'height' => $h,
                'mobile_width' => $mw,
                'mobile_height' => $mh,
            ];
        }, $this->defaultHeroSlideDefinitions());
    }

    /** Keep the campaign copy editable while supplying the approved layered artwork. */
    private function withHeroLayers(Collection $slides): Collection
    {
        $sets = [
            ['daily', ['1S5A6197', '1S5A6200_1', '1S5A4428']],
            ['minerals', ['1S5A3686', '1S5A3688']],
            ['collection', ['1S5A6193', '1S5A6199', '1S5A6201_1']],
        ];

        return $slides->map(function (array $slide, int $index) use ($sets) {
            if (! isset($sets[$index])) {
                return $slide;
            }
            [$name, $products] = $sets[$index];
            $base = 'images/homepage/hero/layers/';
            $slide['scene'] = $this->versionedAsset($base.'background-'.$name.'-desktop.webp');
            $slide['scene_mobile'] = $this->versionedAsset($base.'background-'.$name.'-mobile.webp');
            $slide['accent_image'] = $this->versionedAsset($base.'accent-'.$name.'.svg');
            $slide['mist_image'] = $this->versionedAsset($base.'mist-overlay.webp');
            $slide['layer_products'] = [];
            $slide['mist'] = false;
            $slide['composition'] = 'integrated';
            $slide['tone'] = 'dark';
            $slide['focus'] = 'center';
            $slide['scene_alt'] = ['Supplify vitamins on a breakfast tray with oats, fruit and water.', 'Supplify magnesium and calcium on a wellness table with nuts, water and a yoga mat.', 'Supplify supplements on a nutrition planning desk.'][$index];
            $dimensions = getimagesize(public_path($base.'background-'.$name.'-desktop.webp'));
            $slide['width'] = $dimensions[0] ?? 1920;
            $slide['height'] = $dimensions[1] ?? 900;
            $slide['mobile_width'] = 1080;
            $slide['mobile_height'] = 1000;

            return $slide;
        })->values();
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
