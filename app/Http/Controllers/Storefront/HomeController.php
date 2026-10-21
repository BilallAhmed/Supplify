<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\BlogService;
use App\Services\ContentService;
use App\Services\StorefrontCatalogService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly StorefrontCatalogService $catalog,
        private readonly BlogService $blog,
    ) {}

    public function __invoke(): View
    {
        $sections = $this->content->enabledHomepageSections();
        $featuredLimit = (int) ($sections->get('featured_products')?->config['limit'] ?? 12);
        $goalLimit = (int) ($sections->get('shop_by_goal')?->config['limit'] ?? 4);
        $exploreLimit = (int) ($sections->get('explore_more')?->config['limit'] ?? 4);
        $educationLimit = (int) ($sections->get('education')?->config['limit'] ?? 3);
        $hero = $sections->get('hero');

        $featuredProducts = $sections->has('featured_products')
            ? $this->content->featuredProducts(max(1, $featuredLimit))
            : collect();

        $exploreProducts = $sections->has('explore_more')
            ? $this->content->exploreMoreProducts(
                max(1, $exploreLimit),
                $featuredProducts->pluck('id')->all()
            )
            : collect();

        $educationPosts = $sections->has('education')
            ? $this->blog->latestPublished(max(1, min(3, $educationLimit)))
            : collect();

        $heroSlides = $this->content->heroSlides($hero);

        return view('storefront.home', [
            'sections' => $sections,
            'heroSlides' => $heroSlides,
            'featuredProducts' => $featuredProducts,
            'exploreProducts' => $exploreProducts,
            'goalCategories' => $sections->has('shop_by_goal')
                ? $this->catalog->activeCategories()->take(max(1, $goalLimit))
                : collect(),
            'educationPosts' => $educationPosts,
            'depthProduct' => $this->depthShowcaseProduct(
                $heroSlides,
                $featuredProducts->pluck('id')->all()
            ),
        ]);
    }

    /**
     * Prefer a catalog product that is not featured in the lifestyle hero.
     */
    private function depthShowcaseProduct($heroSlides, array $featuredIds): ?Product
    {
        $heroSlugs = collect($heroSlides)
            ->map(function ($slide) {
                $url = (string) ($slide['cta_url'] ?? '');
                if ($url === '') {
                    return null;
                }
                $path = parse_url($url, PHP_URL_PATH) ?: $url;

                return basename(trim($path, '/')) ?: null;
            })
            ->filter()
            ->values()
            ->all();

        $preferredSlugs = [
            'astaxanthin-12mg-120-softgels',
            'berberine-500mg-120-vegcaps',
            'nadh-20mg-90-tablets',
            'l-carnitine-500mg-60-tablets',
        ];

        $query = Product::query()
            ->where('is_active', true)
            ->where('catalog_visibility', '!=', 'hidden')
            ->whereHas('images')
            ->with(['images', 'categories', 'tags']);

        $preferred = (clone $query)
            ->whereIn('slug', $preferredSlugs)
            ->when($heroSlugs !== [], fn ($q) => $q->whereNotIn('slug', $heroSlugs))
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->slug, $preferredSlugs, true))
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return $query
            ->when($featuredIds !== [], fn ($q) => $q->whereNotIn('id', $featuredIds))
            ->when($heroSlugs !== [], fn ($q) => $q->whereNotIn('slug', $heroSlugs))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->first();
    }
}
