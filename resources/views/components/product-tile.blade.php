@props([
    'product',
    'variant' => 'grid', // grid | featured
])

@php
    $primaryImage = $product->primaryImage();
    $secondaryImage = $product->galleryImages()->first();
    $primaryUrl = $primaryImage?->url() ?? $product->displayImageUrl();
    $secondaryUrl = $secondaryImage?->url();
    $href = route('products.show', $product->slug);
    $isVariable = $product->isVariable();
    $temporary = $product->hasTemporaryPricing();
    $canAdd = ! $isVariable && ! $temporary && $product->isPurchasable();
    $isFeatured = $variant === 'featured';
@endphp

<article
    {{ $attributes->class([
        'product-tile',
        'product-tile--featured' => $isFeatured,
        'product-tile--grid' => ! $isFeatured,
        'is-active' => $isFeatured && ($attributes->get('data-active') ?? false),
    ]) }}
    @if ($isFeatured) data-featured-card @endif
    data-product-id="{{ $product->id }}"
>
    <div class="product-tile__media-wrap">
        <a
            href="{{ $href }}"
            class="product-tile__media {{ $secondaryUrl ? 'has-secondary' : '' }}"
            @if ($isFeatured) data-featured-media @endif
            aria-label="{{ $product->name }}"
        >
            <img
                class="product-tile__img product-tile__img--primary {{ $isFeatured ? 'home-featured__img home-featured__img--primary' : 'product-card__img' }}"
                src="{{ $primaryUrl }}"
                alt="{{ $primaryImage?->alt ?: $product->name }}"
                loading="lazy"
                width="480"
                height="480"
                @unless ($isFeatured) data-product-cutout @endunless
            >
            @if ($secondaryUrl)
                <img
                    class="product-tile__img product-tile__img--secondary {{ $isFeatured ? 'home-featured__img home-featured__img--secondary' : '' }}"
                    src="{{ $secondaryUrl }}"
                    alt="{{ $secondaryImage?->alt ?: ($product->name.' — alternate view') }}"
                    loading="lazy"
                    width="480"
                    height="480"
                >
            @endif
        </a>

        <button
            type="button"
            class="product-tile__wish"
            data-product-action="wishlist"
            data-product-id="{{ $product->id }}"
            data-product-name="{{ $product->name }}"
            aria-pressed="false"
            aria-label="Save {{ $product->name }} to favorites"
            title="Save to favorites"
        >
            <svg class="product-tile__wish-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7 3.6C19 15.6 12 20 12 20Z" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    <div class="product-tile__body {{ $isFeatured ? 'home-featured__body' : '' }}">
        @if ($product->categories->isNotEmpty())
            <p class="product-tile__category {{ $isFeatured ? 'home-featured__category' : '' }}">
                {{ $product->categories->first()->name }}
            </p>
        @else
            <p class="product-tile__category {{ $isFeatured ? 'home-featured__category' : '' }}" aria-hidden="true">&nbsp;</p>
        @endif

        <div class="{{ $isFeatured ? 'home-featured__title-wrap' : 'product-tile__title-wrap' }}">
            <h3 class="product-tile__title font-display {{ $isFeatured ? 'home-featured__title' : '' }}">
                <a href="{{ $href }}">{{ $product->name }}</a>
            </h3>
        </div>

        @if (filled($product->short_description))
            <div class="{{ $isFeatured ? 'home-featured__desc-wrap' : 'product-tile__desc-wrap' }}">
                <p class="product-tile__desc {{ $isFeatured ? 'home-featured__desc' : '' }}">{{ $product->short_description }}</p>
            </div>
        @elseif ($isFeatured)
            <div class="home-featured__desc-wrap">
                <p class="home-featured__desc" aria-hidden="true">&nbsp;</p>
            </div>
        @endif

        <div class="product-tile__footer {{ $isFeatured ? 'home-featured__footer' : '' }}">
            <div class="product-tile__price">
                <p class="font-display text-lg font-bold text-ink">{{ $product->effectivePriceFormatted() }}</p>
                @if ($isVariable)
                    <p class="product-tile__note">Options available</p>
                @else
                    {{-- Reserve note row so card footers stay aligned --}}
                    <p class="product-tile__note product-tile__note--spacer" aria-hidden="true">&nbsp;</p>
                @endif
            </div>
        </div>

        <div class="product-tile__actions">
            <button
                type="button"
                class="product-tile__btn product-tile__btn--secondary"
                data-product-action="quick-view"
                data-product-slug="{{ $product->slug }}"
            >
                Quick View
            </button>

            @if ($canAdd)
                <button
                    type="button"
                    class="product-tile__btn product-tile__btn--primary"
                    data-product-action="add-to-cart"
                    data-product-id="{{ $product->id }}"
                >
                    Add to Cart
                </button>
            @elseif ($isVariable && ! $temporary)
                <button
                    type="button"
                    class="product-tile__btn product-tile__btn--primary"
                    data-product-action="quick-view"
                    data-product-slug="{{ $product->slug }}"
                >
                    Select options
                </button>
            @else
                <a href="{{ $href }}" class="product-tile__btn product-tile__btn--primary">
                    View product
                </a>
            @endif
        </div>
    </div>
</article>
