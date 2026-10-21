@props([
    'product',
])

@php
    $image = $product->primaryImage();
    $imageUrl = $image?->url() ?? $product->displayImageUrl();
    $href = route('products.show', $product->slug);
    $isVariable = $product->isVariable();
    $temporary = $product->hasTemporaryPricing();
    $canAdd = ! $isVariable && ! $temporary && $product->isPurchasable();
@endphp

<article
    {{ $attributes->merge(['class' => 'product-card group']) }}
    data-product-id="{{ $product->id }}"
>
    <div class="product-card__media-wrap">
        <a
            href="{{ $href }}"
            class="product-card__media"
            data-product-media
            aria-label="{{ $product->name }}"
        >
            <img
                src="{{ $imageUrl }}"
                alt="{{ $image?->alt ?: $product->name }}"
                class="product-card__img"
                data-product-cutout
                loading="lazy"
                width="480"
                height="480"
            >
        </a>

        @if ($product->onSale())
            <span class="product-card__badge">
                <x-badge variant="accent">Sale</x-badge>
            </span>
        @endif

        <button
            type="button"
            class="product-card__wish"
            data-product-action="wishlist"
            data-product-id="{{ $product->id }}"
            data-product-name="{{ $product->name }}"
            aria-pressed="false"
            aria-label="Save {{ $product->name }} to favorites"
            title="Save to favorites"
        >
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7 3.6C19 15.6 12 20 12 20Z" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    <div class="product-card__body">
        @if ($product->categories->isNotEmpty())
            <p class="product-card__category">{{ $product->categories->first()->name }}</p>
        @else
            <p class="product-card__category" aria-hidden="true">&nbsp;</p>
        @endif

        <h3 class="product-card__title font-display">
            <a href="{{ $href }}">{{ $product->name }}</a>
        </h3>

        @if ($product->short_description)
            <p class="product-card__desc">{{ $product->short_description }}</p>
        @endif

        <div class="product-card__meta">
            <div class="product-card__price">
                <p class="product-card__amount font-display">{{ $product->effectivePriceFormatted() }}</p>
                @if (! $isVariable && $product->onSale())
                    <p class="product-card__compare">{{ $product->priceFormatted() }}</p>
                @elseif ($isVariable)
                    <p class="product-card__note">Options available</p>
                @else
                    <p class="product-card__note product-card__note--spacer" aria-hidden="true">&nbsp;</p>
                @endif
            </div>

            @unless ($isVariable)
                <x-badge :variant="$temporary ? 'warning' : $product->stock_status->badgeVariant()">
                    {{ $temporary ? 'Pricing TBD' : $product->stock_status->label() }}
                </x-badge>
            @endunless
        </div>

        <div class="product-card__actions">
            <button
                type="button"
                class="product-card__btn product-card__btn--secondary"
                data-product-action="quick-view"
                data-product-slug="{{ $product->slug }}"
            >
                Quick View
            </button>

            @if ($canAdd)
                <button
                    type="button"
                    class="product-card__btn product-card__btn--primary"
                    data-product-action="add-to-cart"
                    data-product-id="{{ $product->id }}"
                >
                    Add to Cart
                </button>
            @elseif ($isVariable && ! $temporary)
                <button
                    type="button"
                    class="product-card__btn product-card__btn--primary"
                    data-product-action="quick-view"
                    data-product-slug="{{ $product->slug }}"
                >
                    Select options
                </button>
            @else
                <a href="{{ $href }}" class="product-card__btn product-card__btn--primary">
                    View product
                </a>
            @endif
        </div>
    </div>
</article>
