<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlist,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'ids' => $this->wishlist->ids(),
        ]);
    }

    public function toggle(Product $product): JsonResponse
    {
        $item = Product::query()
            ->published()
            ->whereKey($product->getKey())
            ->firstOrFail();

        $result = $this->wishlist->toggle($item);

        return response()->json([
            'saved' => $result['saved'],
            'ids' => $result['ids'],
            'message' => $result['saved']
                ? $item->name.' saved to favorites.'
                : $item->name.' removed from favorites.',
        ]);
    }
}
