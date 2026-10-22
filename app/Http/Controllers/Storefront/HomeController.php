<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Services\BlogService;
use App\Services\ContentService;
use App\Services\StorefrontCatalogService;
use App\Support\HomepageLayout;
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
        $featuredLimit = max(1, min(8, (int) ($sections->get('featured_products')?->config['limit'] ?? 8)));
        $goalLimit = (int) ($sections->get('shop_by_goal')?->config['limit'] ?? 4);
        $educationLimit = (int) ($sections->get('education')?->config['limit'] ?? 3);
        $hero = $sections->get('hero');

        $featuredProducts = $sections->has('featured_products')
            ? $this->content->featuredProducts(max(1, $featuredLimit))
            : collect();

        if ($sections->has('featured_products') && $featuredProducts->count() < $featuredLimit) {
            $featuredProducts = $featuredProducts->concat(
                $this->content->exploreMoreProducts(
                    $featuredLimit - $featuredProducts->count(),
                    $featuredProducts->pluck('id')->all()
                )
            )->unique('id')->values();
        }

        $featuredSelected = HomepageLayout::products($sections->get('featured_products')?->config['product_ids'] ?? []);
        if ($featuredSelected->isNotEmpty()) {
            $featuredProducts = $featuredSelected->concat($featuredProducts)->unique('id')->take($featuredLimit)->values();
            if ($featuredProducts->count() < $featuredLimit) {
                $featuredProducts = $featuredProducts->concat($this->content->exploreMoreProducts($featuredLimit - $featuredProducts->count(), $featuredProducts->pluck('id')->all()));
            }
        }
        $dailyPicks = HomepageLayout::products($sections->get('daily_picks')?->config['product_ids'] ?? [])->take(3);
        if ($dailyPicks->count() < 3) {
            $dailyPicks = $dailyPicks->concat($this->content->exploreMoreProducts(3 - $dailyPicks->count(), array_merge($dailyPicks->pluck('id')->all(), $featuredProducts->pluck('id')->all())));
        }

        $educationPosts = $sections->has('education')
            ? $this->blog->latestPublished(max(1, min(3, $educationLimit)))
            : collect();

        $selectedPosts = collect(array_filter($sections->get('education')?->config['post_ids'] ?? []));
        if ($selectedPosts->isNotEmpty()) {
            $posts = Post::query()->published()->with('category')->whereIn('id', $selectedPosts)->get()->keyBy('id');
            $educationPosts = $selectedPosts->map(fn ($id) => $posts->get($id))->filter()->concat($educationPosts)->unique('id')->take(max(1, min(3, $educationLimit)))->values();
        }
        $goalRows = $sections->get('shop_by_goal')?->config['goals'] ?? [];
        $goalCategories = $this->catalog->activeCategories();
        if ($goalRows) {
            $indexed = $goalCategories->keyBy('id');
            $goalCategories = collect($goalRows)->map(function ($row) use ($indexed) {
                $category = isset($row['category_id']) ? $indexed->get($row['category_id']) : null;
                if (! $category) {
                    return null;
                }
                $category = clone $category;
                $category->name = $row['title'] ?: $category->name;
                $category->description = $row['body'];
                $category->image_path = $row['image'];

                return $category;
            })->filter()->unique('id')->values();
        }
        $heroSlides = $this->content->heroSlides($hero);

        return view('storefront.home', [
            'sections' => $sections,
            'heroSlides' => $heroSlides,
            'featuredProducts' => $featuredProducts,
            'dailyPicks' => $dailyPicks,
            'goalCategories' => $sections->has('shop_by_goal')
                ? $goalCategories->take(4)
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
