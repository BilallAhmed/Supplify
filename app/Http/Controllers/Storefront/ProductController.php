<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\StorefrontCatalogService;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly StorefrontCatalogService $catalog,
        private readonly WishlistService $wishlist,
    ) {}

    public function show(string $product): View
    {
        $item = $this->catalog->findPublishedProduct($product);

        $policyPages = Page::query()
            ->published()
            ->whereIn('slug', ['shipping', 'returns'])
            ->get()
            ->keyBy('slug');

        return view('storefront.products.show', [
            'product' => $item,
            'related' => $this->catalog->relatedProducts($item),
            'pdp' => $this->catalog->productPagePayload($item),
            'shippingPage' => $policyPages->get('shipping'),
            'returnsPage' => $policyPages->get('returns'),
        ]);
    }

    public function quickView(string $product): JsonResponse
    {
        $item = $this->catalog->findPublishedProduct($product);
        $pdp = $this->catalog->productPagePayload($item);

        $gallery = $item->images
            ->map(fn ($image) => [
                'url' => $image->url(),
                'alt' => $image->alt ?: $item->name,
            ])
            ->values()
            ->all();

        if ($gallery === []) {
            $gallery[] = [
                'url' => $item->displayImageUrl(),
                'alt' => $item->name,
            ];
        }

        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'url' => route('products.show', $item->slug),
            'short_description' => $item->short_description,
            'category' => $item->categories->first()?->name,
            'price_html' => $item->effectivePriceFormatted(),
            'temporary_pricing' => $item->hasTemporaryPricing(),
            'is_variable' => $item->isVariable(),
            'in_wishlist' => $this->wishlist->has($item->id),
            'gallery' => $gallery,
            'pdp' => $pdp,
            'cart_url' => route('cart.items.store'),
        ]);
    }
}
