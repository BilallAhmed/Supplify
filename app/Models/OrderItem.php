<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'product_variation_id',
    'sku',
    'name',
    'unit_price_cents',
    'quantity',
    'line_total_cents',
    'meta',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function lineTotalFormatted(): string
    {
        return Money::format($this->line_total_cents);
    }

    public function variationLabel(): ?string
    {
        $labels = $this->meta['option_labels'] ?? null;

        if (! is_array($labels) || $labels === []) {
            return null;
        }

        return collect($labels)->map(fn ($value, $key) => "{$key}: {$value}")->implode(' / ');
    }
}
