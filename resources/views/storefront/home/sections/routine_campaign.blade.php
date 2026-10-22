@php
    $left = $section->config['left'];
    $right = $section->config['right'];
@endphp
<section class="wellness-banners" aria-label="Explore daily wellness and targeted nutrition" data-routine-campaign>
    <x-container class="wellness-banners__container">
        <div class="wellness-banners__grid">
            <article class="wellness-banner wellness-banner--daily">
                <img class="wellness-banner__background" src="{{ \App\Support\HomepageLayout::image($left['background']) }}" alt="" width="1200" height="400" loading="lazy" decoding="async">
                <div class="wellness-banner__shade" aria-hidden="true"></div>
                <div class="wellness-banner__products wellness-banner__products--trio" data-banner-entrance>
                    <div class="wellness-banner__product-scale">
                        @foreach (\App\Support\HomepageLayout::products($left['product_ids']) as $bannerProduct)
                            <a class="wellness-banner__bottle wellness-banner__bottle--tilted" href="{{ route('products.show', $bannerProduct->slug) }}" aria-label="Explore {{ $bannerProduct->name }}" data-banner-product>
                                <img src="{{ $bannerProduct->displayImageUrl() }}" alt="{{ $bannerProduct->name }}" width="480" height="840" loading="lazy" decoding="async">
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="wellness-banner__copy wellness-banner__copy--daily">
                    <p class="wellness-banner__eyebrow">{{ $left['eyebrow'] }}</p>
                    <h2>{{ $left['title'] }}<br><span>{{ $left['title_second'] }}</span></h2>
                    <p>{{ $left['body'] }}</p>
                </div>
                <div class="wellness-banner__figure" aria-hidden="true" data-banner-scroll>
                    <img src="{{ \App\Support\HomepageLayout::image($left['element']) }}" alt="" width="560" height="840" loading="lazy" decoding="async">
                </div>
            </article>
            <article class="wellness-banner wellness-banner--minerals">
                <img class="wellness-banner__background" src="{{ \App\Support\HomepageLayout::image($right['background']) }}" alt="" width="600" height="400" loading="lazy" decoding="async">
                <div class="wellness-banner__shade" aria-hidden="true"></div>
                <div class="wellness-banner__copy wellness-banner__copy--minerals">
                    <p class="wellness-banner__eyebrow">{{ $right['eyebrow'] }}</p>
                    <h2>{{ $right['title'] }}<br><span>{{ $right['title_second'] }}</span></h2>
                    <p>{!! nl2br(e($right['body'])) !!}</p>
                    <a class="wellness-banner__button" href="{{ url($right['cta_url'] ?: '/shop') }}"><span>{{ $right['cta_label'] }}</span><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                </div>
                <div class="wellness-banner__products wellness-banner__products--pair">
                    <div class="wellness-banner__product-scale">
                        @foreach (\App\Support\HomepageLayout::products($right['product_ids']) as $bannerProduct)
                            <a class="wellness-banner__bottle" href="{{ route('products.show', $bannerProduct->slug) }}" aria-label="Explore {{ $bannerProduct->name }}" data-banner-product>
                                <img src="{{ $bannerProduct->displayImageUrl() }}" alt="{{ $bannerProduct->name }}" width="480" height="640" loading="lazy" decoding="async">
                            </a>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>
    </x-container>
</section>
