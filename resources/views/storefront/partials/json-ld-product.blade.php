@php
    use App\Enums\StockStatus;
    use App\Support\Money;

    $image = $product->primaryImage();
    $availability = match ($product->stock_status) {
        StockStatus::OutOfStock => 'https://schema.org/OutOfStock',
        StockStatus::LowStock => 'https://schema.org/LimitedAvailability',
        default => 'https://schema.org/InStock',
    };

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->seo_description
            ?: ($product->short_description ?: strip_tags((string) $product->description)),
        'sku' => $product->sku,
        'url' => route('products.show', $product->slug),
        'brand' => [
            '@type' => 'Brand',
            'name' => config('app.name'),
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('products.show', $product->slug),
            'priceCurrency' => 'USD',
            'price' => Money::toDecimalString($product->effectivePriceCents()),
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];

    if ($image) {
        $schema['image'] = [$image->url()];
    }

    if ($product->categories->isNotEmpty()) {
        $schema['category'] = $product->categories->pluck('name')->implode(' > ');
    }
@endphp

<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
