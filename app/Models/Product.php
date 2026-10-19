<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'sku',
    'product_type',
    'short_description',
    'description',
    'benefits',
    'ingredients',
    'supplement_facts',
    'directions',
    'warnings',
    'price_cents',
    'sale_price_cents',
    'sale_starts_at',
    'sale_ends_at',
    'stock_quantity',
    'manage_stock',
    'allow_backorder',
    'requires_shipping',
    'shipping_class',
    'weight_grams',
    'weight_unit',
    'length_mm',
    'width_mm',
    'height_mm',
    'dimension_unit',
    'stock_status',
    'low_stock_threshold',
    'is_featured',
    'is_active',
    'catalog_visibility',
    'published_at',
    'seo_title',
    'seo_description',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'stock_quantity' => 'integer',
            'manage_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'requires_shipping' => 'boolean',
            'weight_grams' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'low_stock_threshold' => 'integer',
            'stock_status' => StockStatus::class,
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->orderBy('id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_relations',
            'product_id',
            'related_product_id'
        )->withPivot('type')->withTimestamps();
    }

    public function isVariable(): bool
    {
        return $this->product_type === ProductType::Variable;
    }

    public function syncStockStatus(): void
    {
        if ($this->isVariable()) {
            $enabled = $this->relationLoaded('variations')
                ? $this->variations->where('is_enabled', true)
                : $this->variations()->where('is_enabled', true)->get();

            $purchasable = $enabled->filter(fn (ProductVariation $v) => $v->isPurchasable());

            $this->stock_status = match (true) {
                $purchasable->isEmpty() => StockStatus::OutOfStock,
                $purchasable->contains(fn (ProductVariation $v) => $v->stock_status === StockStatus::LowStock) => StockStatus::LowStock,
                default => StockStatus::InStock,
            };

            return;
        }

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

    public function priceFormatted(): string
    {
        return Money::format($this->price_cents);
    }

    public function onSale(): bool
    {
        if ($this->isVariable()) {
            $variations = $this->relationLoaded('variations')
                ? $this->variations->where('is_enabled', true)
                : $this->variations()->where('is_enabled', true)->get();

            return $variations->contains(fn (ProductVariation $v) => $v->onSale());
        }

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
        if ($this->isVariable()) {
            $prices = $this->enabledVariationPrices();

            return $prices === [] ? 0 : min($prices);
        }

        if ($this->onSale()) {
            return (int) $this->sale_price_cents;
        }

        return $this->price_cents;
    }

    public function effectivePriceFormatted(): string
    {
        if ($this->isVariable()) {
            $prices = $this->enabledVariationPrices();

            if ($prices === []) {
                return Money::format(0);
            }

            $min = min($prices);
            $max = max($prices);

            return $min === $max
                ? Money::format($min)
                : Money::format($min).' – '.Money::format($max);
        }

        return Money::format($this->effectivePriceCents());
    }

    /** @return list<int> */
    public function enabledVariationPrices(): array
    {
        $variations = $this->relationLoaded('variations')
            ? $this->variations->where('is_enabled', true)
            : $this->variations()->where('is_enabled', true)->get();

        return $variations
            ->map(fn (ProductVariation $v) => $v->effectivePriceCents())
            ->values()
            ->all();
    }

    public function isPurchasable(): bool
    {
        if ($this->catalog_visibility === 'hidden') {
            return false;
        }

        if ($this->hasTemporaryPricing()) {
            return false;
        }

        if ($this->isVariable()) {
            $variations = $this->relationLoaded('variations')
                ? $this->variations
                : $this->variations()->get();

            return $variations->contains(fn (ProductVariation $v) => $v->isPurchasable());
        }

        if (! $this->manage_stock) {
            return true;
        }

        if ($this->allow_backorder) {
            return true;
        }

        return $this->stock_status !== StockStatus::OutOfStock && $this->stock_quantity > 0;
    }

    public function hasTemporaryPricing(): bool
    {
        if ($this->relationLoaded('tags')) {
            return $this->tags->contains(fn (Tag $tag) => $tag->slug === 'temporary-pricing');
        }

        return $this->tags()->where('slug', 'temporary-pricing')->exists();
    }

    public function hasEditorialDraft(): bool
    {
        if ($this->relationLoaded('tags')) {
            return $this->tags->contains(fn (Tag $tag) => $tag->slug === 'editorial-draft');
        }

        return $this->tags()->where('slug', 'editorial-draft')->exists();
    }

    public function primaryImage(): ?ProductImage
    {
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        }

        return $this->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();
    }

    public function galleryImages()
    {
        if ($this->relationLoaded('images')) {
            return $this->images->where('is_primary', false)->values();
        }

        return $this->images()->where('is_primary', false)->orderBy('sort_order')->get();
    }

    public function displayImageUrl(): string
    {
        $image = $this->primaryImage();
        if ($image) {
            return $image->url();
        }

        $fallback = match ($this->slug) {
            'magnesium-night', 'magnesium-night-copy' => 'images/products/magnesium-night.svg',
            'daily-greens' => 'images/products/daily-greens.svg',
            default => 'images/products/omega-complex.svg',
        };

        $bySku = match ($this->sku) {
            'MAG-NITE', 'ASH-CALM', 'COL-REN' => 'images/products/magnesium-night.svg',
            'GRN-DAILY', 'PRO-BAL', 'ELC-HYD', 'BCP-FOC' => 'images/products/daily-greens.svg',
            'OMG-1000', 'VIT-D3', 'CRE-PWR', 'ZNC-SHLD' => 'images/products/omega-complex.svg',
            default => null,
        };

        return asset($bySku ?? $fallback);
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('catalog_visibility', '!=', 'hidden');
    }
}
