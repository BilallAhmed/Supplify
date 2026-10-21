<section class="home-featured collection-editorial" aria-labelledby="featured-heading" data-home-featured>
    <x-container>
        <header class="collection-editorial__head">
            <div><p class="editorial-kicker">{{ $section->subtitle ?: 'The collection' }}</p><h2 id="featured-heading">{{ $section->title ?: 'Featured formulas' }}</h2>@if ($section->body)<p class="collection-editorial__description">{{ $section->body }}</p>@endif</div>
            <div class="collection-editorial__aside">
                <x-view-all-products />
                @if ($featuredProducts->isNotEmpty())<div class="collection-editorial__arrows" role="group" aria-label="Featured products controls"><button type="button" data-featured-prev aria-label="Previous products" aria-controls="featured-product-rail"><span class="collection-editorial__prev"><svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button><button type="button" data-featured-next aria-label="Next products" aria-controls="featured-product-rail"><svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button></div>@endif
            </div>
        </header>
    </x-container>
    @if ($featuredProducts->isNotEmpty())
        <div class="collection-editorial__viewport" data-featured-viewport id="featured-product-rail" tabindex="0" aria-label="Featured products carousel" aria-describedby="featured-rail-hint">
            <div class="collection-editorial__track" data-featured-track>
                @foreach ($featuredProducts as $product)
                    @php $image = $product->primaryImage(); $secondaryImage = $product->galleryImages()->first(); $href = route('products.show', $product->slug); @endphp
                    <article class="collection-product" data-featured-card data-product-id="{{ $product->id }}">
                        <header class="collection-product__heading"><h3><a href="{{ $href }}">{{ $product->name }}</a></h3>
                            <button class="collection-product__save" type="button" data-product-action="wishlist" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" aria-pressed="false" aria-label="Save {{ $product->name }} to favorites"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7 3.6C19 15.6 12 20 12 20Z"/></svg></button>
                        </header>
                        <a class="collection-product__scene" href="{{ $href }}" aria-label="{{ $product->name }}" data-featured-media>
                            <span class="collection-product__halo" aria-hidden="true"></span>
                            <img class="home-featured__img home-featured__img--primary" src="{{ $image?->url() ?? $product->displayImageUrl() }}" alt="{{ $image?->alt ?: $product->name }}" width="480" height="640" loading="lazy" decoding="async">
                            @if ($secondaryImage && $secondaryImage->url() !== ($image?->url() ?? $product->displayImageUrl()))
                                <img class="home-featured__img home-featured__img--secondary" src="{{ $secondaryImage->url() }}" alt="{{ $secondaryImage->alt ?: ($product->name.' â€” alternate view') }}" width="480" height="640" loading="lazy" decoding="async" data-hover-secondary>
                            @endif
                        </a>
                        <footer class="collection-product__footer"><div><span>Explore the formula</span><strong>{{ $product->effectivePriceFormatted() }}</strong></div></footer>
                        <x-formula-actions :product="$product" />
                    </article>
                @endforeach
            </div>
        </div>
        <x-container class="collection-editorial__navigation">
            <p id="featured-rail-hint" class="sr-only" data-featured-hint>Swipe or drag to explore</p>
            <div class="collection-editorial__meter" aria-hidden="true"><span data-featured-progress></span></div>
        </x-container>
    @else <x-container><p>Featured formulas will be available soon.</p></x-container> @endif
</section>
