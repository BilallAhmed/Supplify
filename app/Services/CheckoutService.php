<?php

namespace App\Services;

use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * @param  array{
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     lines?: Collection<int, array<string, mixed>>
     * }  $cartTotals
     * @return array{
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     shipping_cents: int,
     *     tax_cents: int,
     *     total_cents: int,
     *     subtotal_formatted: string,
     *     discount_formatted: string,
     *     shipping_formatted: string,
     *     tax_formatted: string,
     *     total_formatted: string,
     *     shipping_is_free: bool,
     *     shipment_weight_grams: ?int,
     *     shipping_method: string
     * }
     */
    public function quote(array $cartTotals): array
    {
        $subtotal = max(0, (int) $cartTotals['subtotal_cents']);
        $discount = max(0, min($subtotal, (int) $cartTotals['discount_cents']));
        $merchandise = max(0, $subtotal - $discount);
        $lines = $cartTotals['lines'] ?? collect();

        $method = (string) config('store.shipping.method', 'flat');
        $weightGrams = $this->shipmentWeightGrams($lines);
        $shipping = 0;

        if ($merchandise > 0) {
            $freeOver = (int) config('store.shipping.free_over_cents', 7500);

            if ($merchandise >= $freeOver) {
                $shipping = 0;
            } elseif ($method === 'weight') {
                if ($weightGrams === null) {
                    throw ValidationException::withMessages([
                        'shipping' => 'One or more products that require shipping are missing a weight. Shipping cannot be calculated.',
                    ]);
                }
                $shipping = $this->weightBasedRate($weightGrams);
            } else {
                $shipping = (int) config('store.shipping.flat_rate_cents', 599);
            }
        }

        $taxable = $merchandise + $shipping;
        $rate = max(0, (float) config('store.tax.rate_percent', 0));
        $tax = (int) round($taxable * ($rate / 100));
        $total = $merchandise + $shipping + $tax;

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'shipping_cents' => $shipping,
            'tax_cents' => $tax,
            'total_cents' => $total,
            'subtotal_formatted' => Money::format($subtotal),
            'discount_formatted' => Money::format($discount),
            'shipping_formatted' => Money::format($shipping),
            'tax_formatted' => Money::format($tax),
            'total_formatted' => Money::format($total),
            'shipping_is_free' => $shipping === 0 && $merchandise > 0,
            'shipment_weight_grams' => $weightGrams,
            'shipping_method' => $method,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>|iterable  $lines
     */
    public function shipmentWeightGrams(iterable $lines): ?int
    {
        $total = 0;
        $hasShippable = false;

        foreach ($lines as $line) {
            if (! ($line['available'] ?? false)) {
                continue;
            }

            $requiresShipping = (bool) ($line['requires_shipping'] ?? true);
            if (! $requiresShipping) {
                continue;
            }

            $hasShippable = true;
            $unitWeight = $line['weight_grams'] ?? null;

            if ($unitWeight === null) {
                return null;
            }

            $total += ((int) $unitWeight) * (int) ($line['quantity'] ?? 1);
        }

        return $hasShippable ? $total : 0;
    }

    private function weightBasedRate(int $grams): int
    {
        $rates = collect(config('store.shipping.weight_rates', []))
            ->sortBy('max_grams')
            ->values();

        foreach ($rates as $rate) {
            if ($grams <= (int) $rate['max_grams']) {
                return (int) $rate['price_cents'];
            }
        }

        $last = $rates->last();

        return (int) ($last['price_cents'] ?? config('store.shipping.flat_rate_cents', 599));
    }
}
