<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cart_id',
    'product_id',
    'product_variation_id',
    'line_key',
    'quantity',
    'meta',
])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'meta' => 'array',
        ];
    }

    public static function lineKeyFor(int $productId, ?int $variationId): string
    {
        return $productId.':'.($variationId ?: 0);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
