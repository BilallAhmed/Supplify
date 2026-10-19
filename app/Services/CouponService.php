<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        $normalized = Coupon::normalizeCode($code);

        if ($normalized === '') {
            return null;
        }

        return Coupon::query()->where('code', $normalized)->first();
    }

    /**
     * @throws ValidationException
     */
    public function assertApplicable(Coupon $coupon, int $subtotalCents): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is not active.',
            ]);
        }

        if (! $coupon->isWithinSchedule()) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is outside its valid date range.',
            ]);
        }

        if (! $coupon->hasRemainingUses()) {
            throw ValidationException::withMessages([
                'code' => 'This coupon has reached its usage limit.',
            ]);
        }

        if ($coupon->min_subtotal_cents !== null && $subtotalCents < $coupon->min_subtotal_cents) {
            throw ValidationException::withMessages([
                'code' => 'Cart subtotal does not meet this coupon’s minimum of '
                    .\App\Support\Money::format($coupon->min_subtotal_cents).'.',
            ]);
        }

        if ($coupon->discountCents($subtotalCents) <= 0) {
            throw ValidationException::withMessages([
                'code' => 'This coupon does not apply to the current cart.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function applyToCart(Cart $cart, string $code, int $subtotalCents): Coupon
    {
        $coupon = $this->findByCode($code);

        if (! $coupon) {
            throw ValidationException::withMessages([
                'code' => 'Invalid coupon code.',
            ]);
        }

        $this->assertApplicable($coupon, $subtotalCents);

        $cart->forceFill([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ])->save();

        return $coupon;
    }

    public function removeFromCart(Cart $cart): void
    {
        $cart->forceFill([
            'coupon_id' => null,
            'coupon_code' => null,
        ])->save();
    }

    /**
     * Resolve a still-valid coupon for totals, or clear it if no longer applicable.
     */
    public function resolveForCart(Cart $cart, int $subtotalCents): ?Coupon
    {
        $cart->loadMissing('coupon');

        $coupon = $cart->coupon;

        if (! $coupon && $cart->coupon_code) {
            $coupon = $this->findByCode($cart->coupon_code);
        }

        if (! $coupon) {
            if ($cart->coupon_id || $cart->coupon_code) {
                $this->removeFromCart($cart);
            }

            return null;
        }

        try {
            $this->assertApplicable($coupon, $subtotalCents);
        } catch (ValidationException) {
            $this->removeFromCart($cart);

            return null;
        }

        if ((int) $cart->coupon_id !== (int) $coupon->id || $cart->coupon_code !== $coupon->code) {
            $cart->forceFill([
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
            ])->save();
        }

        return $coupon;
    }
}
