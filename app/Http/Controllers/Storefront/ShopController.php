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
        $filters = $request->only(['q', 'category', 'sort', 'in_stock', 'collection', 'on_sale']);
        [$heading, $subheading] = $this->copyForFilters($filters);

        return view('storefront.shop.index', [
            'products' => $this->catalog->paginateProducts($filters),
            'categories' => $this->catalog->activeCategories(),
            'filters' => $filters,
            'activeCategory' => null,
            'heading' => $heading,
            'subheading' => $subheading,
        ]);
    }

    public function category(Request $request, string $category): View
    {
        $activeCategory = $this->catalog->findActiveCategory($category);
        $filters = $request->only(['q', 'sort', 'in_stock', 'collection', 'on_sale']);

        return view('storefront.shop.index', [
            'products' => $this->catalog->paginateProducts($filters, $activeCategory),
            'categories' => $this->catalog->activeCategories(),
            'filters' => $filters,
            'activeCategory' => $activeCategory,
            'heading' => $activeCategory->name,
            'subheading' => $activeCategory->description
                ?: 'Products in '.$activeCategory->name.'.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: string}
     */
    private function copyForFilters(array $filters): array
    {
        $collection = (string) ($filters['collection'] ?? '');
        $onSale = filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return match (true) {
            $collection === 'best-sellers' => [
                'Best Seller',
                'Featured formulas customers come back to.',
            ],
            $collection === 'trending' => [
                'Trending products',
                'Fresh picks gaining attention right now.',
            ],
            $onSale || $collection === 'offers' => [
                'Discount & Offers',
                'Products currently marked down — clear pricing, same standards.',
            ],
            default => [
                'All products',
                'Clean formulas with transparent labels. Browse the full catalog.',
            ],
        };
    }
}
