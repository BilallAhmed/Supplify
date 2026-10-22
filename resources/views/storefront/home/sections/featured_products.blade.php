@php
    $promoPath = 'images/homepage/featured-products.webp';
    $promoImage = $section->image_path ? \App\Support\MediaUrl::toUrl($section->image_path) : asset($promoPath).'?v='.filemtime(public_path($promoPath));
@endphp
<section id="featured-products" class="home-featured featured-shop" aria-labelledby="featured-heading" data-home-featured-grid>
    <x-container class="featured-shop__container">
        <div class="featured-shop__grid">
            <div class="featured-shop__promo">
                <img src="{{ $promoImage }}" alt="{{ $section->config['image_alt'] ?? '' }}" width="980" height="700" loading="lazy" decoding="async">
                <div class="featured-shop__overlay" aria-hidden="true"></div>
                <h2 id="featured-heading"><span>{{ $section->title ?: 'Featured' }}</span><span>{{ $section->config['title_second'] ?? 'Products' }}</span></h2>
                <a class="featured-shop__promo-button" href="{{ url($section->cta_url ?: '/shop') }}"><span>{{ $section->cta_label ?: 'Shop All Products' }}</span><svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            @forelse ($featuredProducts as $product)
                @include('storefront.home.partials.product_card', ['product' => $product])
            @empty
                <p class="featured-shop__empty">Featured products will be available soon.</p>
            @endforelse
        </div>
    </x-container>
</section>
