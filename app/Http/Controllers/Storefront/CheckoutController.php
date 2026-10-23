<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
    ) {}

    public function show(): View|RedirectResponse
    {
        $summary = $this->carts->summary();

        if ($summary['lines']->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('flash_error', 'Add items to your cart before checking out.');
        }

        $quote = $this->checkout->quote([
            'subtotal_cents' => $summary['subtotal_cents'],
            'discount_cents' => $summary['discount_cents'],
            'lines' => $summary['lines'],
        ]);

        $user = Auth::user();
        $address = $user?->defaultAddress();

        return view('storefront.checkout.show', [
            'summary' => $summary,
            'quote' => $quote,
            'defaults' => [
                'email' => old('email', $user?->email),
                'phone' => old('phone', $user?->phone ?? $address?->phone),
                'shipping_name' => old('shipping_name', $address?->name ?? $user?->name),
                'shipping_line1' => old('shipping_line1', $address?->line1),
                'shipping_line2' => old('shipping_line2', $address?->line2),
                'shipping_city' => old('shipping_city', $address?->city),
                'shipping_state' => old('shipping_state', $address?->state),
                'shipping_postal_code' => old('shipping_postal_code', $address?->postal_code),
                'shipping_country' => old('shipping_country', $address?->country ?: 'PK'),
                'shipping_phone' => old('shipping_phone', $address?->phone ?? $user?->phone),
            ],
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $order = $placeOrder->execute($request->validated());

        session(['checkout.confirmed_order_id' => $order->id]);

        return redirect()
            ->route('checkout.confirmation', $order)
            ->with('flash_success', 'Order placed. Pay with cash when it arrives.');
    }

    public function confirmation(Order $order): View
    {
        $user = Auth::user();
        $allowed = ($user && (int) $order->user_id === (int) $user->id)
            || (int) session('checkout.confirmed_order_id') === (int) $order->id;

        abort_unless($allowed, 404);

        $order->loadMissing('items');

        return view('storefront.checkout.confirmation', compact('order'));
    }
}
