<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontCatalogService
{
    /**
     * @param  array{q?: string, category?: string, sort?: string, in_stock?: bool|string, collection?: string, on_sale?: bool|string, min_price?: mixed, max_price?: mixed, per_page?: int}  $filters
     */
    public function paginateProducts(array $filters = [], ?Category $category = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->published()
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'variations' => fn ($q) => $q->where('is_enabled', true),
            ]);

        if ($category) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $category->id));
        } elseif (! empty($filters['category'])) {
            $query->whereHas(
                'categories',
                fn (Builder $q) => $q->where('categories.slug', $filters['category'])
            );
        }

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $query->where(function (Builder $q) use ($term) {
                $like = '%'.$term.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('tags', fn (Builder $tq) => $tq->where('name', 'like', $like));
            });
        }

        $collection = (string) ($filters['collection'] ?? '');

        if ($collection === 'best-sellers') {
            $query->where('is_featured', true);
        }

        if (filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN) || $collection === 'offers') {
            $query->whereNotNull('sale_price_cents')
                ->whereColumn('sale_price_cents', '<', 'price_cents');
        }

        if (filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('stock_status', '!=', StockStatus::OutOfStock->value)
                ->where(function (Builder $q) {
                    $q->where('product_type', ProductType::Variable->value)
                        ->orWhere('manage_stock', false)
                        ->orWhere('allow_backorder', true)
                        ->orWhere('stock_quantity', '>', 0);
                });
        }

        $priceRange = $this->resolvePriceRange($filters);
        if ($priceRange['error'] === null && ($priceRange['min_cents'] !== null || $priceRange['max_cents'] !== null)) {
            $this->applyEffectivePriceFilter($query, $priceRange['min_cents'], $priceRange['max_cents']);
        }

        $sort = $filters['sort'] ?? null;
        if (! $sort) {
            $sort = $collection === 'trending' ? 'newest' : 'featured';
        }

        $this->applySort($query, $sort);

        return $query->paginate((int) ($filters['per_page'] ?? 12))->withQueryString();
    }

    /**
     * Parse user-facing major-unit price bounds into cents.
     *
     * @param  array<string, mixed>  $filters
     * @return array{min_price: ?string, max_price: ?string, min_cents: ?int, max_cents: ?int, error: ?string}
     */
    public function resolvePriceRange(array $filters): array
    {
        $minRaw = $this->normalizePriceInput($filters['min_price'] ?? null);
        $maxRaw = $this->normalizePriceInput($filters['max_price'] ?? null);

        $result = [
            'min_price' => $minRaw,
            'max_price' => $maxRaw,
            'min_cents' => null,
            'max_cents' => null,
            'error' => null,
        ];

        if ($minRaw === null && $maxRaw === null) {
            return $result;
        }

        try {
            $minCents = $minRaw !== null ? Money::toCents($minRaw) : null;
            $maxCents = $maxRaw !== null ? Money::toCents($maxRaw) : null;
        } catch (\InvalidArgumentException) {
            $result['error'] = 'Enter valid non-negative prices in PKR.';

            return $result;
        }

        if (($minCents !== null && $minCents < 0) || ($maxCents !== null && $maxCents < 0)) {
            $result['error'] = 'Price values cannot be negative.';

            return $result;
        }

        if ($minCents !== null && $maxCents !== null && $minCents > $maxCents) {
            $result['error'] = 'Minimum price cannot be greater than maximum price.';

            return $result;
        }

        $result['min_cents'] = $minCents;
        $result['max_cents'] = $maxCents;

        return $result;
    }

    public function findPublishedProduct(string $slug): Product
    {
        return Product::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'attributes.values',
                'variations' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'),
                'relatedProducts' => fn ($q) => $q->published()
                    ->with([
                        'categories:id,name,slug',
                        'images' => fn ($iq) => $iq->orderByDesc('is_primary')->orderBy('sort_order'),
                        'variations' => fn ($vq) => $vq->where('is_enabled', true),
                    ]),
            ])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function productPagePayload(Product $product): array
    {
        $primary = $product->primaryImage();
        $defaultVariation = $product->variations->firstWhere('is_default', true)
            ?? $product->variations->firstWhere('is_enabled', true);

        $defaultSelection = [];
        if ($defaultVariation && is_array($defaultVariation->option_labels)) {
            $defaultSelection = $defaultVariation->option_labels;
        }

        $attributes = $product->attributes
            ->where('used_for_variations', true)
            ->values()
            ->map(fn ($attr) => [
                'name' => $attr->name,
                'values' => $attr->values->pluck('value')->values()->all(),
            ])
            ->all();

        $temporaryPricing = $product->hasTemporaryPricing();

        $variations = $product->variations->map(function ($variation) use ($product, $temporaryPricing) {
            $max = $variation->manage_stock ? max(0, (int) $variation->stock_quantity) : 99;
            $purchasable = ! $temporaryPricing && $variation->isPurchasable();

            return [
                'id' => $variation->id,
                'options' => $variation->option_labels ?? [],
                'sku' => $variation->sku,
                'price_html' => Money::format($variation->effectivePriceCents()),
                'compare_html' => $variation->onSale() ? Money::format($variation->price_cents) : '',
                'on_sale' => $variation->onSale(),
                'stock_label' => $temporaryPricing
                    ? 'Awaiting confirmed pricing'
                    : $variation->stock_status->label(),
                'stock_variant' => $temporaryPricing
                    ? 'warning'
                    : $variation->stock_status->badgeVariant(),
                // Nullable — do not fall back to the parent image or gallery clicks break.
                'image_url' => $variation->imageUrl(),
                'image_alt' => $product->name,
                'max_qty' => $max,
                'purchasable' => $purchasable,
                'enabled' => $variation->is_enabled,
            ];
        })->values()->all();

        $maxQty = $product->isVariable()
            ? ($defaultVariation?->manage_stock ? max(0, (int) $defaultVariation->stock_quantity) : 99)
            : ($product->manage_stock && ! $product->allow_backorder ? max(0, (int) $product->stock_quantity) : 99);

        $gallery = $product->images
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
            ])
            ->values()
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url(),
                'alt' => $image->alt ?: $product->name,
                'is_primary' => (bool) $image->is_primary,
            ])
            ->unique('url')
            ->values()
            ->all();

        if ($gallery === []) {
            $gallery[] = [
                'id' => null,
                'url' => $product->displayImageUrl(),
                'alt' => $product->name,
                'is_primary' => true,
            ];
        }

        $stockLabel = $temporaryPricing
            ? 'Awaiting confirmed pricing'
            : $product->stock_status->label();
        $stockVariant = $temporaryPricing
            ? 'warning'
            : $product->stock_status->badgeVariant();

        return [
            'productType' => $product->product_type?->value ?? 'simple',
            'attributes' => $attributes,
            'variations' => $variations,
            'defaultSelection' => $defaultSelection,
            'gallery' => $gallery,
            'priceHtml' => $product->effectivePriceFormatted(),
            'compareHtml' => (! $product->isVariable() && $product->onSale()) ? $product->priceFormatted() : '',
            'onSale' => $product->onSale(),
            'stockLabel' => $stockLabel,
            'stockVariant' => $stockVariant,
            'sku' => $product->sku,
            'imageUrl' => $primary?->url() ?? $product->displayImageUrl(),
            'imageAlt' => $primary?->alt ?: $product->name,
            'maxQty' => $maxQty,
            'purchasable' => $product->isPurchasable(),
            'temporaryPricing' => $temporaryPricing,
            'productId' => $product->id,
            'productName' => $product->name,
        ];
    }

    public function relatedProducts(Product $product, int $limit = 4): Collection
    {
        $related = $product->relatedProducts
            ->where('is_active', true)
            ->take($limit)
            ->values();

        if ($related->count() >= $limit) {
            return $related;
        }

        $exclude = $related->pluck('id')->push($product->id)->all();
        $categoryIds = $product->categories->pluck('id');

        $fallbacks = Product::query()
            ->published()
            ->whereNotIn('id', $exclude)
            ->when(
                $categoryIds->isNotEmpty(),
                fn (Builder $q) => $q->whereHas(
                    'categories',
                    fn (Builder $cq) => $cq->whereIn('categories.id', $categoryIds)
                )
            )
            ->with([
                'categories:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->latest('published_at')
            ->limit($limit - $related->count())
            ->get();

        return $related->concat($fallbacks)->values();
    }

    public function activeCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount([
                'products as published_products_count' => fn (Builder $q) => $q->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Distinct published catalog size (not a sum of per-category counts).
     */
    public function publishedProductCount(): int
    {
        return Product::query()->published()->count();
    }

    public function findActiveCategory(string $slug): Category
    {
        return Category::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy(
                DB::raw('COALESCE(sale_price_cents, price_cents)')
            )->orderBy('name'),
            'price_desc' => $query->orderByDesc(
                DB::raw('COALESCE(sale_price_cents, price_cents)')
            )->orderBy('name'),
            'name' => $query->orderBy('name'),
            'newest' => $query->latest('published_at')->orderBy('name'),
            default => $query->orderByDesc('is_featured')->latest('published_at')->orderBy('name'),
        };
    }

    /**
     * Filter by effective selling price (active sale when valid), in cents.
     * Variable products match when any enabled variation falls in range.
     */
    private function applyEffectivePriceFilter(Builder $query, ?int $minCents, ?int $maxCents): void
    {
        $now = now()->toDateTimeString();

        $query->where(function (Builder $outer) use ($minCents, $maxCents, $now) {
            $outer->where(function (Builder $simple) use ($minCents, $maxCents, $now) {
                $simple->where('product_type', '!=', ProductType::Variable->value);
                $this->constrainEffectivePriceColumn($simple, 'products', $minCents, $maxCents, $now);
            })->orWhere(function (Builder $variable) use ($minCents, $maxCents, $now) {
                $variable->where('product_type', ProductType::Variable->value)
                    ->whereHas('variations', function (Builder $variation) use ($minCents, $maxCents, $now) {
                        $variation->where('is_enabled', true);
                        $this->constrainEffectivePriceColumn($variation, 'product_variations', $minCents, $maxCents, $now);
                    });
            });
        });
    }

    private function constrainEffectivePriceColumn(
        Builder $query,
        string $table,
        ?int $minCents,
        ?int $maxCents,
        string $now,
    ): void {
        $effective = $this->effectivePriceSql($table);

        if ($minCents !== null) {
            $query->whereRaw("({$effective}) >= ?", [$now, $now, $minCents]);
        }

        if ($maxCents !== null) {
            $query->whereRaw("({$effective}) <= ?", [$now, $now, $maxCents]);
        }
    }

    private function effectivePriceSql(string $table): string
    {
        return "CASE
            WHEN {$table}.sale_price_cents IS NOT NULL
                AND {$table}.sale_price_cents < {$table}.price_cents
                AND ({$table}.sale_starts_at IS NULL OR {$table}.sale_starts_at <= ?)
                AND ({$table}.sale_ends_at IS NULL OR {$table}.sale_ends_at >= ?)
            THEN {$table}.sale_price_cents
            ELSE {$table}.price_cents
        END";
    }

    private function normalizePriceInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
