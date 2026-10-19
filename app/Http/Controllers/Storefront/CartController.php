<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function index(): View
    {
        return view('storefront.cart.index', [
            'summary' => $this->cart->summary(),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        $product = Product::query()
            ->published()
            ->whereKey($request->integer('product_id'))
            ->firstOrFail();

        $variation = null;
        if ($request->filled('variation_id')) {
            $variation = ProductVariation::query()
                ->whereKey($request->integer('variation_id'))
                ->where('product_id', $product->id)
                ->first();
        }

        $this->cart->add($product, $request->integer('quantity', 1), $variation);

        $label = $variation
            ? $product->name.' ('.$variation->labelSummary().')'
            : $product->name;

        $message = $label.' added to cart.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'cart_count' => $this->cart->itemCount(),
            ]);
        }

        return redirect()
            ->back()
            ->with('flash_success', $message);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $this->cart->update($item, $request->integer('quantity'));

        return redirect()
            ->route('cart.index')
            ->with('flash_success', 'Cart updated.');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->cart->remove($item);

        return redirect()
            ->route('cart.index')
            ->with('flash_success', 'Item removed from cart.');
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        $this->cart->applyCoupon($request->string('code')->toString());

        return redirect()
            ->route('cart.index')
            ->with('flash_success', 'Coupon applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cart->removeCoupon();

        return redirect()
            ->route('cart.index')
            ->with('flash_success', 'Coupon removed.');
    }
}
