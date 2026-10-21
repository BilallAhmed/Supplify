<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\StorefrontCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly StorefrontCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'q', 'category', 'sort', 'in_stock', 'collection', 'on_sale', 'min_price', 'max_price',
        ]);
        $priceRange = $this->catalog->resolvePriceRange($filters);
        [$heading, $subheading, $eyebrow] = $this->copyForFilters($filters);

        return view('storefront.shop.index', [
            'products' => $this->catalog->paginateProducts($filters),
            'categories' => $this->catalog->activeCategories(),
            'publishedProductCount' => $this->catalog->publishedProductCount(),
            'filters' => $filters,
            'priceRange' => $priceRange,
            'activeCategory' => null,
            'heading' => $heading,
            'subheading' => $subheading,
            'eyebrow' => $eyebrow,
        ]);
    }

    public function category(Request $request, string $category): View
    {
        $activeCategory = $this->catalog->findActiveCategory($category);
        $filters = $request->only([
            'q', 'sort', 'in_stock', 'collection', 'on_sale', 'min_price', 'max_price',
        ]);
        $priceRange = $this->catalog->resolvePriceRange($filters);

        return view('storefront.shop.index', [
            'products' => $this->catalog->paginateProducts($filters, $activeCategory),
            'categories' => $this->catalog->activeCategories(),
            'publishedProductCount' => $this->catalog->publishedProductCount(),
            'filters' => $filters,
            'priceRange' => $priceRange,
            'activeCategory' => $activeCategory,
            'heading' => $activeCategory->name,
            'subheading' => $activeCategory->description
                ?: 'Products in '.$activeCategory->name.'.',
            'eyebrow' => 'Explore Supplify',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: string, 2: string}
     */
    private function copyForFilters(array $filters): array
    {
        $collection = (string) ($filters['collection'] ?? '');
        $onSale = filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return match (true) {
            $collection === 'best-sellers' => [
                'Best Seller',
                'Featured formulas customers come back to.',
                'Explore Supplify',
            ],
            $collection === 'trending' => [
                'Trending products',
                'Fresh picks gaining attention right now.',
                'Explore Supplify',
            ],
            $onSale || $collection === 'offers' => [
                'Discount & Offers',
                'Products currently marked down — clear pricing, same standards.',
                'Explore Supplify',
            ],
            default => [
                'Find Your Everyday Essentials',
                'Browse our range of vitamins, minerals, and nutritional supplements, with clear product information to help you explore your options.',
                'Explore Supplify',
            ],
        };
    }
}
