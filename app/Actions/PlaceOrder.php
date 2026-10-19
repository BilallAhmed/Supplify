<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Notifications\OrderPlacedNotification;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
        private readonly CouponService $coupons,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(array $input, ?Cart $cart = null): Order
    {
        $cart ??= $this->carts->current(create: false);

        if (! $cart || $cart->items()->doesntExist()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $method = PaymentMethod::from((string) ($input['payment_method'] ?? PaymentMethod::Cod->value));

        return DB::transaction(function () use ($input, $cart, $method) {
            $cart = Cart::query()
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->with(['items.product', 'items.variation', 'coupon'])
                ->firstOrFail();

            $summary = $this->carts->summary($cart);

            if ($summary['lines']->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            foreach ($summary['lines'] as $line) {
                if (! $line['available'] || ! $line['product']) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more items are unavailable. Update your cart and try again.',
                    ]);
                }

                if ($line['quantity'] > $line['max_quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => "Only {$line['max_quantity']} in stock for {$line['display_name']}.",
                    ]);
                }
            }

            $coupon = $summary['coupon'];
            if ($coupon) {
                $this->coupons->assertApplicable($coupon, $summary['subtotal_cents']);
            }

            $quote = $this->checkout->quote([
                'subtotal_cents' => $summary['subtotal_cents'],
                'discount_cents' => $summary['discount_cents'],
                'lines' => $summary['lines'],
            ]);

            $billingSame = (bool) ($input['billing_same_as_shipping'] ?? true);
            $shipping = $this->addressFrom($input, 'shipping');
            $billing = $billingSame
                ? $shipping
                : $this->addressFrom($input, 'billing');

            $userId = $cart->user_id ?? Auth::id();

            $status = match ($method) {
                PaymentMethod::Cod => OrderStatus::Processing,
            };
            $paymentStatus = PaymentStatus::Pending;
            $historyNote = match ($method) {
                PaymentMethod::Cod => 'Order placed with cash on delivery. Inventory reserved.',
            };

            $order = Order::query()->create([
                'order_number' => $this->uniqueOrderNumber(),
                'user_id' => $userId,
                'email' => Str::lower(trim((string) $input['email'])),
                'phone' => $input['phone'] ?? $shipping['phone'] ?? null,
                'shipping_name' => $shipping['name'],
                'shipping_line1' => $shipping['line1'],
                'shipping_line2' => $shipping['line2'],
                'shipping_city' => $shipping['city'],
                'shipping_state' => $shipping['state'],
                'shipping_postal_code' => $shipping['postal_code'],
                'shipping_country' => $shipping['country'],
                'shipping_phone' => $shipping['phone'],
                'billing_name' => $billing['name'],
                'billing_line1' => $billing['line1'],
                'billing_line2' => $billing['line2'],
                'billing_city' => $billing['city'],
                'billing_state' => $billing['state'],
                'billing_postal_code' => $billing['postal_code'],
                'billing_country' => $billing['country'],
                'billing_phone' => $billing['phone'],
                'status' => $status,
                'payment_status' => $paymentStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'payment_method' => $method,
                'subtotal_cents' => $quote['subtotal_cents'],
                'discount_cents' => $quote['discount_cents'],
                'shipping_cents' => $quote['shipping_cents'],
                'tax_cents' => $quote['tax_cents'],
                'total_cents' => $quote['total_cents'],
                'currency' => config('store.currency', 'USD'),
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'notes' => $input['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($summary['lines'] as $line) {
                /** @var Product $product */
                $product = Product::query()
                    ->whereKey($line['product']->id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stock changed while placing your order. Please review your cart.',
                    ]);
                }

                $variation = null;
                $unitCents = $product->effectivePriceCents();
                $sku = $product->sku;
                $name = $product->name;
                $meta = null;

                if ($product->isVariable()) {
                    $variation = ProductVariation::query()
                        ->whereKey($line['variation']?->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variation || ! $variation->isPurchasable() || ($variation->manage_stock && $variation->stock_quantity < $line['quantity'])) {
                        throw ValidationException::withMessages([
                            'cart' => 'Stock changed while placing your order. Please review your cart.',
                        ]);
                    }

                    if ($variation->manage_stock) {
                        $variation->stock_quantity -= $line['quantity'];
                        $variation->syncStockStatus();
                        $variation->save();
                    }

                    $unitCents = $variation->effectivePriceCents();
                    $sku = $variation->sku ?: $product->sku;
                    $name = $product->name;
                    $meta = [
                        'option_labels' => $variation->option_labels,
                        'variation_sku' => $variation->sku,
                    ];

                    $product->load('variations');
                    $product->syncStockStatus();
                    $product->save();
                } else {
                    if ($product->manage_stock && ! $product->allow_backorder && $product->stock_quantity < $line['quantity']) {
                        throw ValidationException::withMessages([
                            'cart' => 'Stock changed while placing your order. Please review your cart.',
                        ]);
                    }

                    if ($product->manage_stock) {
                        $product->stock_quantity = max(0, $product->stock_quantity - $line['quantity']);
                        $product->syncStockStatus();
                        $product->save();
                    }

                    $unitCents = $product->effectivePriceCents();
                }

                if ($unitCents !== $line['unit_cents']) {
                    // Recompute from locked inventory source — never trust cart browser prices.
                    $lineTotal = $unitCents * $line['quantity'];
                } else {
                    $lineTotal = $line['line_cents'];
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variation_id' => $variation?->id,
                    'sku' => $sku,
                    'name' => $name,
                    'unit_price_cents' => $unitCents,
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $lineTotal,
                    'meta' => $meta,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => $status->value,
                'user_id' => $userId,
                'note' => $historyNote,
            ]);

            if ($coupon) {
                Coupon::query()
                    ->whereKey($coupon->id)
                    ->lockForUpdate()
                    ->increment('used_count');
            }

            $cart->items()->delete();
            $this->coupons->removeFromCart($cart);

            $order = $order->load('items');

            DB::afterCommit(function () use ($order) {
                Notification::route('mail', $order->email)
                    ->notify(new OrderPlacedNotification($order));
            });

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, line1: string, line2: ?string, city: string, state: ?string, postal_code: string, country: string, phone: ?string}
     */
    private function addressFrom(array $input, string $prefix): array
    {
        return [
            'name' => trim((string) $input["{$prefix}_name"]),
            'line1' => trim((string) $input["{$prefix}_line1"]),
            'line2' => filled($input["{$prefix}_line2"] ?? null) ? trim((string) $input["{$prefix}_line2"]) : null,
            'city' => trim((string) $input["{$prefix}_city"]),
            'state' => filled($input["{$prefix}_state"] ?? null) ? trim((string) $input["{$prefix}_state"]) : null,
            'postal_code' => trim((string) $input["{$prefix}_postal_code"]),
            'country' => strtoupper(trim((string) ($input["{$prefix}_country"] ?? 'US'))),
            'phone' => filled($input["{$prefix}_phone"] ?? null) ? trim((string) $input["{$prefix}_phone"]) : null,
        ];
    }

    private function uniqueOrderNumber(): string
    {
        do {
            $number = 'SUP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
