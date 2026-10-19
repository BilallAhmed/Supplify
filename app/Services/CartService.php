<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'cart_id';

    public function __construct(
        private readonly CouponService $coupons,
    ) {}

    public function current(bool $create = true): ?Cart
    {
        $cartId = session(self::SESSION_KEY);
        $user = Auth::user();

        if ($user) {
            $userCart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => session()->getId()]
            );

            if ($cartId && (int) $cartId !== $userCart->id) {
                $guestCart = Cart::query()
                    ->whereKey($cartId)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart) {
                    $this->mergeCarts($guestCart, $userCart);
                }
            }

            session([self::SESSION_KEY => $userCart->id]);
            $userCart->forceFill(['session_id' => session()->getId()])->save();

            return $userCart->loadMissing($this->itemRelations());
        }

        if ($cartId) {
            $cart = Cart::query()
                ->whereKey($cartId)
                ->whereNull('user_id')
                ->first();

            if ($cart) {
                $cart->forceFill(['session_id' => session()->getId()])->save();

                return $cart->loadMissing($this->itemRelations());
            }
        }

        if (! $create) {
            return null;
        }

        $cart = Cart::query()->create([
            'session_id' => session()->getId(),
        ]);

        session([self::SESSION_KEY => $cart->id]);

        return $cart->loadMissing($this->itemRelations());
    }

    public function itemCount(): int
    {
        $cart = $this->current(create: false);

        if (! $cart) {
            return 0;
        }

        return (int) $cart->items()->sum('quantity');
    }

    public function add(Product $product, int $quantity = 1, ?ProductVariation $variation = null): Cart
    {
        $quantity = max(1, $quantity);

        if ($product->isVariable()) {
            if (! $variation || (int) $variation->product_id !== (int) $product->id) {
                throw ValidationException::withMessages([
                    'variation_id' => 'Please select a valid product option.',
                ]);
            }

            if (! $variation->isPurchasable()) {
                throw ValidationException::withMessages([
                    'variation_id' => 'That option is currently unavailable.',
                ]);
            }
        } else {
            $variation = null;

            if (! $product->isPurchasable()) {
                throw ValidationException::withMessages([
                    'product' => 'This product is currently unavailable.',
                ]);
            }
        }

        $cart = $this->current();
        $lineKey = CartItem::lineKeyFor($product->id, $variation?->id);

        return DB::transaction(function () use ($cart, $product, $quantity, $variation, $lineKey) {
            $item = $cart->items()->firstOrNew(['line_key' => $lineKey]);
            $nextQty = ($item->exists ? $item->quantity : 0) + $quantity;
            $max = $this->maxQuantity($product, $variation);

            if ($nextQty > $max) {
                $label = $variation ? $product->name.' ('.$variation->labelSummary().')' : $product->name;
                throw ValidationException::withMessages([
                    'quantity' => "Only {$max} in stock for {$label}.",
                ]);
            }

            $item->fill([
                'product_id' => $product->id,
                'product_variation_id' => $variation?->id,
                'line_key' => $lineKey,
                'quantity' => $nextQty,
                'meta' => $variation ? [
                    'option_labels' => $variation->option_labels,
                    'sku' => $variation->sku,
                ] : null,
            ]);
            $item->save();

            return $cart->fresh($this->itemRelations());
        });
    }

    public function update(CartItem $item, int $quantity): Cart
    {
        $cart = $this->current();
        $this->assertOwnsItem($cart, $item);

        if ($quantity <= 0) {
            return $this->remove($item);
        }

        $product = $item->product;
        $variation = $item->variation;

        if (! $product) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is currently unavailable.',
            ]);
        }

        if ($product->isVariable()) {
            if (! $variation || ! $variation->isPurchasable()) {
                throw ValidationException::withMessages([
                    'quantity' => 'This option is currently unavailable.',
                ]);
            }
        } elseif (! $product->isPurchasable()) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is currently unavailable.',
            ]);
        }

        $max = $this->maxQuantity($product, $variation);

        if ($quantity > $max) {
            $label = $variation ? $product->name.' ('.$variation->labelSummary().')' : $product->name;
            throw ValidationException::withMessages([
                'quantity' => "Only {$max} in stock for {$label}.",
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $cart->fresh($this->itemRelations());
    }

    public function remove(CartItem $item): Cart
    {
        $cart = $this->current();
        $this->assertOwnsItem($cart, $item);
        $item->delete();

        return $cart->fresh($this->itemRelations());
    }

    public function mergeForUser(User $user): void
    {
        $guestCartId = session(self::SESSION_KEY);

        $userCart = Cart::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['session_id' => session()->getId()]
        );

        if ($guestCartId && (int) $guestCartId !== $userCart->id) {
            $guestCart = Cart::query()
                ->whereKey($guestCartId)
                ->whereNull('user_id')
                ->first();

            if ($guestCart) {
                $this->mergeCarts($guestCart, $userCart);
            }
        }

        session([self::SESSION_KEY => $userCart->id]);
        $userCart->forceFill(['session_id' => session()->getId()])->save();
    }

    /**
     * @return array{
     *     cart: Cart,
     *     lines: Collection<int, array<string, mixed>>,
     *     item_count: int,
     *     subtotal_cents: int,
     *     subtotal_formatted: string,
     *     discount_cents: int,
     *     discount_formatted: string,
     *     coupon: ?\App\Models\Coupon,
     *     coupon_code: ?string,
     *     total_cents: int,
     *     total_formatted: string
     * }
     */
    public function summary(?Cart $cart = null): array
    {
        $cart ??= $this->current(create: false) ?? new Cart;
        $cart->loadMissing([...$this->itemRelations(), 'coupon']);

        $lines = $cart->items
            ->sortBy(fn (CartItem $item) => $item->product?->name)
            ->values()
            ->map(function (CartItem $item) {
                $product = $item->product;
                $variation = $item->variation;
                $available = false;
                $unitCents = 0;
                $maxQty = 0;
                $image = $product?->displayImageUrl();
                $weightGrams = null;
                $requiresShipping = (bool) ($product?->requires_shipping ?? true);
                $displayName = $product?->name ?? 'Product';
                $sku = $product?->sku;

                if ($product?->isVariable()) {
                    if ($variation && (int) $variation->product_id === (int) $product->id) {
                        $available = $variation->isPurchasable();
                        $unitCents = $variation->effectivePriceCents();
                        $maxQty = $available ? $this->maxQuantity($product, $variation) : 0;
                        $image = $variation->imageUrl() ?? $image;
                        $weightGrams = $variation->resolvedWeightGrams($product);
                        $displayName = $product->name.' — '.$variation->labelSummary();
                        $sku = $variation->sku ?: $product->sku;
                    }
                } elseif ($product) {
                    $available = $product->isPurchasable();
                    $unitCents = $product->effectivePriceCents();
                    $maxQty = $available ? $this->maxQuantity($product, null) : 0;
                    $weightGrams = $product->weight_grams;
                }

                $lineCents = $available ? $unitCents * $item->quantity : 0;

                return [
                    'item' => $item,
                    'product' => $product,
                    'variation' => $variation,
                    'quantity' => $item->quantity,
                    'unit_cents' => $unitCents,
                    'unit_formatted' => Money::format($unitCents),
                    'line_cents' => $lineCents,
                    'line_formatted' => Money::format($lineCents),
                    'available' => $available,
                    'max_quantity' => $maxQty,
                    'image' => $image,
                    'display_name' => $displayName,
                    'sku' => $sku,
                    'option_labels' => $variation?->option_labels
                        ?? ($item->meta['option_labels'] ?? null),
                    'requires_shipping' => $requiresShipping,
                    'weight_grams' => $requiresShipping ? $weightGrams : 0,
                ];
            });

        $subtotal = (int) $lines->sum('line_cents');
        $coupon = $cart->exists ? $this->coupons->resolveForCart($cart, $subtotal) : null;
        $discount = $coupon ? $coupon->discountCents($subtotal) : 0;
        $total = max(0, $subtotal - $discount);

        return [
            'cart' => $cart,
            'lines' => $lines,
            'item_count' => (int) $lines->sum('quantity'),
            'subtotal_cents' => $subtotal,
            'subtotal_formatted' => Money::format($subtotal),
            'discount_cents' => $discount,
            'discount_formatted' => Money::format($discount),
            'coupon' => $coupon,
            'coupon_code' => $coupon?->code ?? $cart->coupon_code,
            'total_cents' => $total,
            'total_formatted' => Money::format($total),
        ];
    }

    public function applyCoupon(string $code): Cart
    {
        $cart = $this->current();
        $summary = $this->summary($cart);

        if ($summary['lines']->isEmpty()) {
            throw ValidationException::withMessages([
                'code' => 'Add items to your cart before applying a coupon.',
            ]);
        }

        $this->coupons->applyToCart($cart, $code, $summary['subtotal_cents']);

        return $cart->fresh([...$this->itemRelations(), 'coupon']);
    }

    public function removeCoupon(): Cart
    {
        $cart = $this->current(create: false);

        if ($cart) {
            $this->coupons->removeFromCart($cart);
        }

        return $this->current(create: false)?->loadMissing([...$this->itemRelations(), 'coupon'])
            ?? new Cart;
    }

    private function maxQuantity(Product $product, ?ProductVariation $variation): int
    {
        if ($variation) {
            if (! $variation->manage_stock) {
                return 99;
            }

            return max(0, (int) $variation->stock_quantity);
        }

        if (! $product->manage_stock || $product->allow_backorder) {
            return 99;
        }

        return max(0, (int) $product->stock_quantity);
    }

    private function mergeCarts(Cart $guest, Cart $target): void
    {
        if ($guest->is($target)) {
            return;
        }

        DB::transaction(function () use ($guest, $target) {
            $guest->load(['items.product', 'items.variation']);

            foreach ($guest->items as $guestItem) {
                $product = $guestItem->product;
                $variation = $guestItem->variation;

                if (! $product) {
                    continue;
                }

                if ($product->isVariable()) {
                    if (! $variation || ! $variation->isPurchasable()) {
                        continue;
                    }
                } elseif (! $product->isPurchasable()) {
                    continue;
                }

                $lineKey = $guestItem->line_key
                    ?: CartItem::lineKeyFor($product->id, $variation?->id);

                $existing = $target->items()->firstOrNew(['line_key' => $lineKey]);
                $combined = ($existing->exists ? $existing->quantity : 0) + $guestItem->quantity;
                $max = $this->maxQuantity($product, $variation);

                $existing->fill([
                    'product_id' => $product->id,
                    'product_variation_id' => $variation?->id,
                    'line_key' => $lineKey,
                    'quantity' => min($combined, $max),
                    'meta' => $guestItem->meta,
                ]);
                $existing->save();
            }

            if (! $target->coupon_id && $guest->coupon_id) {
                $target->forceFill([
                    'coupon_id' => $guest->coupon_id,
                    'coupon_code' => $guest->coupon_code,
                ])->save();
            }

            $guest->items()->delete();
            $guest->delete();
        });
    }

    /** @return list<string> */
    private function itemRelations(): array
    {
        return [
            'items.product.images',
            'items.product.categories',
            'items.variation',
        ];
    }

    private function assertOwnsItem(?Cart $cart, CartItem $item): void
    {
        if (! $cart || (int) $item->cart_id !== (int) $cart->id) {
            abort(404);
        }
    }
}
