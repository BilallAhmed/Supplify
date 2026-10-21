<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;

class StoreSettings
{
    private const CACHE_KEY = 'store_settings.all';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return StoreSetting::query()
                ->pluck('value', 'key')
                ->map(fn (?string $value) => $this->decode($value))
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            StoreSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->encode($value)],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function currency(): string
    {
        return (string) $this->get('currency', config('store.currency', 'USD'));
    }

    public function shippingMethod(): string
    {
        $method = (string) $this->get('shipping.method', config('store.shipping.method', 'flat'));

        return in_array($method, ['flat', 'weight'], true) ? $method : 'flat';
    }

    public function flatRateCents(): int
    {
        return max(0, (int) $this->get('shipping.flat_rate_cents', config('store.shipping.flat_rate_cents', 599)));
    }

    public function freeOverCents(): int
    {
        return max(0, (int) $this->get('shipping.free_over_cents', config('store.shipping.free_over_cents', 7500)));
    }

    public function taxRatePercent(): float
    {
        return max(0, (float) $this->get('tax.rate_percent', config('store.tax.rate_percent', 0)));
    }

    /**
     * @return list<array{max_grams: int, price_cents: int}>
     */
    public function weightRates(): array
    {
        $stored = $this->get('shipping.weight_rates');

        if (is_array($stored) && $stored !== []) {
            return array_values(array_map(function ($rate) {
                return [
                    'max_grams' => (int) ($rate['max_grams'] ?? 0),
                    'price_cents' => (int) ($rate['price_cents'] ?? 0),
                ];
            }, $stored));
        }

        return array_values(config('store.shipping.weight_rates', []));
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function decode(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
