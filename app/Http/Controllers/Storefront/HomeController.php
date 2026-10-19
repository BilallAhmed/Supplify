<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
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

        return view('storefront.home', [
            'sections' => $sections,
            'heroSlides' => $this->content->heroSlides($hero),
            'featuredProducts' => $featuredProducts,
            'exploreProducts' => $exploreProducts,
            'goalCategories' => $sections->has('shop_by_goal')
                ? $this->catalog->activeCategories()->take(max(1, $goalLimit))
                : collect(),
            'educationPosts' => $educationPosts,
        ]);
    }
}
