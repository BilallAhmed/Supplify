<?php

namespace App\Models;

use App\Enums\StockStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'product_id',
    'sku',
    'is_enabled',
    'is_default',
    'price_cents',
    'sale_price_cents',
    'sale_starts_at',
    'sale_ends_at',
    'manage_stock',
    'stock_quantity',
    'stock_status',
    'low_stock_threshold',
    'weight_grams',
    'length_mm',
    'width_mm',
    'height_mm',
    'image_path',
    'option_signature',
    'option_labels',
])]
class ProductVariation extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'manage_stock' => 'boolean',
            'stock_quantity' => 'integer',
            'stock_status' => StockStatus::class,
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'option_labels' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class);
    }

    public function syncStockStatus(): void
    {
        if (! $this->manage_stock) {
            $this->stock_status = StockStatus::InStock;

            return;
        }

        $this->stock_status = match (true) {
            $this->stock_quantity <= 0 => StockStatus::OutOfStock,
            $this->stock_quantity <= $this->low_stock_threshold => StockStatus::LowStock,
            default => StockStatus::InStock,
        };
    }

    public function onSale(): bool
    {
        if ($this->sale_price_cents === null || $this->sale_price_cents >= $this->price_cents) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at && $now->gt($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    public function effectivePriceCents(): int
    {
        return $this->onSale() ? (int) $this->sale_price_cents : (int) $this->price_cents;
    }

    public function effectivePriceFormatted(): string
    {
        return Money::format($this->effectivePriceCents());
    }

    public function isPurchasable(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (! $this->manage_stock) {
            return true;
        }

        return $this->stock_status !== StockStatus::OutOfStock && $this->stock_quantity > 0;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function labelSummary(): string
    {
        $labels = $this->option_labels ?? [];

        if ($labels === []) {
            return 'Default';
        }

        return collect($labels)->map(fn ($value, $key) => "{$key}: {$value}")->implode(' / ');
    }

    public function resolvedWeightGrams(?Product $parent = null): ?int
    {
        if ($this->weight_grams !== null) {
            return $this->weight_grams;
        }

        $parent ??= $this->product;

        return $parent?->weight_grams;
    }

    /**
     * @return array{length: ?int, width: ?int, height: ?int}
     */
    public function resolvedDimensionsMm(?Product $parent = null): array
    {
        $parent ??= $this->product;

        return [
            'length' => $this->length_mm ?? $parent?->length_mm,
            'width' => $this->width_mm ?? $parent?->width_mm,
            'height' => $this->height_mm ?? $parent?->height_mm,
        ];
    }
}
