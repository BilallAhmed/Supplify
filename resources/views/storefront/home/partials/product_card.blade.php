@php
    $primary = $product->primaryImage();
    $href = route('products.show', $product->slug);
    $temporary = $product->hasTemporaryPricing();
    $variable = $product->isVariable();
    $canAdd = ! $variable && ! $temporary && $product->isPurchasable();
    $selectOptions = $variable && ! $temporary && $product->isPurchasable();
@endphp
<article class="featured-shop__product" data-product-id="{{ $product->id }}">
    <header class="featured-shop__product-heading">
        <h3><a href="{{ $href }}">{{ $product->name }}</a></h3>
        <p>{{ $product->categories->first()?->name ?? 'Daily wellness' }}</p>
    </header>
    <a class="featured-shop__media" href="{{ $href }}" aria-label="View {{ $product->name }}" data-featured-grid-media>
        <img src="{{ $primary?->url() ?? $product->displayImageUrl() }}" alt="{{ $primary?->alt ?: $product->name }}" width="480" height="640" loading="lazy" decoding="async">
    </a>
    <div class="featured-shop__tools">
        <button type="button" data-product-action="wishlist" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" aria-pressed="false" aria-label="Save {{ $product->name }} to favorites" title="Save to favorites"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7 3.6C19 15.6 12 20 12 20Z"/></svg></button>
        <button type="button" data-product-action="quick-view" data-product-slug="{{ $product->slug }}" aria-label="Quick view {{ $product->name }}" title="Quick view"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 4H4v4m12-4h4v4M4 16v4h4m12-4v4h-4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    </div>
    <footer class="featured-shop__price-row">
        <div><strong>{{ $product->effectivePriceFormatted() }}</strong>@if ($product->onSale() && ! $variable)<del>{{ $product->priceFormatted() }}</del>@endif</div>
        @if ($product->onSale())<span class="featured-shop__badge">Sale</span>@elseif ($product->is_featured)<span class="featured-shop__badge">Featured</span>@endif
    </footer>
    <button type="button" class="featured-shop__cart"
        @if ($canAdd) data-product-action="add-to-cart" data-product-id="{{ $product->id }}" aria-label="Add {{ $product->name }} to cart"
        @elseif ($selectOptions) data-product-action="quick-view" data-product-slug="{{ $product->slug }}" aria-label="Select options for {{ $product->name }}"
        @else disabled title="{{ $temporary ? 'Available to purchase once pricing is confirmed' : 'Currently unavailable for purchase' }}" @endif>
        <span data-cart-label>{{ $selectOptions ? 'Select Options' : 'Add to Cart' }}</span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m8 9 4-6 4 6M4 9h16l-2 11H6L4 9Zm5 4 1 4m5-4-1 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
</article>
