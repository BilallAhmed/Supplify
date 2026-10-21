@php
    /** @var \App\Models\Product $depthProduct */
    $image = $depthProduct->primaryImage();
    $imageUrl = $image?->url() ?? $depthProduct->displayImageUrl();
    $facts = array_values(array_filter([
        filled($depthProduct->sku) ? ['label' => 'SKU', 'value' => $depthProduct->sku] : null,
        filled($depthProduct->short_description)
            ? ['label' => 'Formula', 'value' => \Illuminate\Support\Str::limit(strip_tags($depthProduct->short_description), 72)]
            : null,
        ['label' => 'Price', 'value' => $depthProduct->effectivePriceFormatted()],
    ]));
@endphp

<section
    id="formula-in-focus"
    class="home-depth"
    aria-labelledby="depth-heading"
    data-product-depth
>
    <x-container class="home-depth__inner">
        <div class="home-depth__grid">
            <div class="home-depth__stage-wrap" data-depth-stage>
                <div class="home-depth__accent" data-depth-accent aria-hidden="true"></div>
                <div class="home-depth__shadow" data-depth-shadow aria-hidden="true"></div>
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $image?->alt ?: $depthProduct->name }}"
                    class="home-depth__bottle"
                    width="420"
                    height="520"
                    loading="lazy"
                    decoding="async"
                    data-depth-bottle
                >
            </div>

            <div class="home-depth__copy" data-depth-copy>
                <p class="home-depth__eyebrow">Formula in focus</p>
                <h2 id="depth-heading" class="home-depth__title font-display">{{ $depthProduct->name }}</h2>
                @if (filled($depthProduct->short_description))
                    <p class="home-depth__lede">{{ $depthProduct->short_description }}</p>
                @endif

                <p class="home-depth__price">{{ $depthProduct->effectivePriceFormatted() }}</p>
                <a href="{{ route('products.show', $depthProduct->slug) }}" class="home-depth__cta">
                    View {{ $depthProduct->name }}
                    <svg class="home-depth__cta-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </div>
    </x-container>
</section>
