@extends('layouts.storefront')

@php
    /** @var \App\Models\Product|null $heroProduct */
    /** @var \Illuminate\Support\Collection $rangeProducts */
    /** @var \App\Models\Product|null $detailProduct */
    $heroImg = $heroProduct?->primaryImage();
    $detailImg = $detailProduct?->primaryImage();
    $rangeTones = ['is-tone-a', 'is-tone-b', 'is-tone-c'];
@endphp

@section('title', 'Homepage concept — '.config('app.name'))
@section('meta_description', 'Experimental Supplify homepage concept. Not the live storefront.')
@section('canonical', route('home.concept'))

@push('head')
    @vite(['resources/js/home-concept.js'])
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="hc" data-home-concept>
        <div class="hc__bar" role="status">
            <p>
                Phase 2 homepage concept — cinematic scroll (isolated).
                <a href="{{ route('home') }}">Live homepage</a>
                ·
                <a href="{{ route('home.preview') }}">Earlier preview</a>
            </p>
        </div>

        {{-- 1–2. Hero → brand intro (pinned product journey) --}}
        <section class="hc-journey" aria-label="Opening story" data-hc-journey>
            <div class="hc-journey__pin" data-hc-pin>
                <div class="hc-journey__wash" data-hc-wash aria-hidden="true">
                    <img
                        src="{{ asset($lifestyle['hero_wash']) }}"
                        alt=""
                        width="1905"
                        height="732"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>

                <div class="hc-journey__bottle-slot" data-hc-bottle-slot>
                    @if ($heroImg)
                        <img
                            src="{{ $heroImg->url() }}"
                            alt="{{ $heroProduct->name }} packaging"
                            width="640"
                            height="800"
                            fetchpriority="high"
                            decoding="async"
                            class="hc-journey__bottle"
                            data-hc-bottle
                        >
                    @endif
                </div>

                <div class="hc-journey__panel hc-journey__panel--hero" data-hc-hero-copy>
                    <p class="hc-hero__eyebrow" data-hc-hero-line>{{ $brand['eyebrow'] }}</p>
                    <h1 id="hc-hero-title" class="hc-hero__title" data-hc-hero-line>{{ $brand['headline'] }}</h1>
                    <p class="hc-hero__lede" data-hc-hero-line>{{ $brand['lede'] }}</p>
                    <div class="hc-hero__actions" data-hc-hero-line>
                        <x-button :href="$brand['cta_url']" variant="primary" size="lg">
                            {{ $brand['cta_label'] }}
                        </x-button>
                        @if ($heroProduct)
                            <a href="{{ route('products.show', $heroProduct) }}" class="hc-text-link">
                                View {{ $heroProduct->name }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="hc-journey__panel hc-journey__panel--intro" data-hc-intro-copy>
                    <p class="hc-kicker" data-hc-intro-line>About the brand</p>
                    <h2 id="hc-intro-title" class="hc-display" data-hc-intro-line>{{ $brand['intro_title'] }}</h2>
                    <p class="hc-body" data-hc-intro-line>{{ $brand['intro_body'] }}</p>
                    <a href="{{ route('pages.show', 'about') }}" class="hc-text-link" data-hc-intro-line>Read our story</a>
                </div>
            </div>
        </section>

        {{-- 3. Range discovery --}}
        <section class="hc-range" aria-labelledby="hc-range-title" data-hc-range>
            <x-container class="hc-range__head" data-hc-range-head>
                <p class="hc-kicker">The range</p>
                <h2 id="hc-range-title" class="hc-display">Three formulas. One clear shelf.</h2>
                <p class="hc-body hc-body--narrow">
                    Distinct products from the live catalog — coordinated presentation, real packaging.
                </p>
            </x-container>

            <div class="hc-range__grid">
                @forelse ($rangeProducts as $index => $product)
                    @php $tone = $rangeTones[$index % count($rangeTones)]; @endphp
                    <article class="hc-range__card {{ $tone }}" data-hc-range-card>
                        <a href="{{ route('products.show', $product) }}" class="hc-range__link">
                            <div class="hc-range__media">
                                @if ($product->primaryImage())
                                    <img
                                        src="{{ $product->primaryImage()->url() }}"
                                        alt="{{ $product->name }} packaging"
                                        width="400"
                                        height="500"
                                        loading="lazy"
                                        decoding="async"
                                        data-hc-range-bottle
                                    >
                                @endif
                            </div>
                            <div class="hc-range__meta">
                                <h3 class="hc-range__name">{{ $product->name }}</h3>
                                <p class="hc-range__desc">{{ $product->short_description }}</p>
                                <p class="hc-range__price">{{ $product->effectivePriceFormatted() }}</p>
                                <span class="hc-range__cta">View product</span>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="hc-empty">Range products are unavailable. <a href="{{ route('shop.index') }}">Browse the shop</a>.</p>
                @endforelse
            </div>
        </section>

        {{-- 4. Product detail focus --}}
        @if ($detailProduct)
            <section class="hc-detail" aria-labelledby="hc-detail-title" data-hc-detail>
                <x-container class="hc-detail__inner">
                    <div class="hc-detail__visual" data-hc-detail-visual>
                        @if ($detailImg)
                            <img
                                src="{{ $detailImg->url() }}"
                                alt="{{ $detailProduct->name }} label"
                                width="560"
                                height="700"
                                loading="lazy"
                                decoding="async"
                                class="hc-detail__bottle"
                                data-hc-detail-bottle
                            >
                        @endif
                    </div>
                    <div class="hc-detail__copy" data-hc-detail-copy>
                        <p class="hc-kicker">In focus</p>
                        <h2 id="hc-detail-title" class="hc-display">{{ $detailProduct->name }}</h2>
                        <p class="hc-body">{{ $detailProduct->short_description }}</p>

                        <dl class="hc-facts">
                            @if (filled($detailProduct->supplement_facts))
                                <div class="hc-facts__row" data-hc-fact>
                                    <dt>Supplement facts</dt>
                                    <dd>{!! nl2br(e($detailProduct->supplement_facts)) !!}</dd>
                                </div>
                            @endif
                            @if (filled($detailProduct->directions))
                                <div class="hc-facts__row" data-hc-fact>
                                    <dt>Directions</dt>
                                    <dd>{{ $detailProduct->directions }}</dd>
                                </div>
                            @endif
                            <div class="hc-facts__row" data-hc-fact>
                                <dt>Price</dt>
                                <dd>{{ $detailProduct->effectivePriceFormatted() }}</dd>
                            </div>
                        </dl>

                        <div class="hc-detail__actions" data-hc-fact>
                            <x-button :href="route('products.show', $detailProduct)" variant="primary" size="lg">
                                View full product page
                            </x-button>
                        </div>
                        <p class="hc-disclaimer">
                            Product information is taken from the catalog listing. These statements have not been evaluated by the FDA. Products are not intended to diagnose, treat, cure, or prevent any disease.
                        </p>
                    </div>
                </x-container>
            </section>
        @endif

        {{-- 5. Everyday context --}}
        <section class="hc-everyday" aria-labelledby="hc-everyday-title" data-hc-everyday>
            <div class="hc-everyday__media" aria-hidden="true" data-hc-everyday-media>
                <img
                    src="{{ asset($lifestyle['everyday']) }}"
                    alt=""
                    width="1905"
                    height="732"
                    loading="lazy"
                    decoding="async"
                >
            </div>
            <x-container class="hc-everyday__inner" data-hc-everyday-copy>
                <p class="hc-kicker hc-kicker--on-dark">Everyday context</p>
                <h2 id="hc-everyday-title" class="hc-display hc-display--on-dark">Made for the hours between work, rest, and routine.</h2>
                <p class="hc-body hc-body--on-dark">
                    Lifestyle imagery here is temporary campaign photography from our homepage set — replace with client shoots when ready.
                </p>
                <a href="{{ route('shop.index') }}" class="hc-text-link hc-text-link--on-dark">Browse the shop</a>
            </x-container>
        </section>

        {{-- 6. Closing CTA --}}
        <section class="hc-close" aria-labelledby="hc-close-title" data-hc-close>
            <x-container class="hc-close__inner" data-hc-close-inner>
                <p class="hc-kicker hc-kicker--accent">Continue</p>
                <h2 id="hc-close-title" class="hc-display hc-display--on-dark">Explore the full Supplify range.</h2>
                <p class="hc-body hc-body--on-dark hc-body--narrow">
                    Clear labels. Transparent formulas. Start with the products that fit your routine.
                </p>
                <div class="hc-close__actions">
                    <x-button href="{{ route('shop.index') }}" variant="accent" size="lg">Explore Products</x-button>
                    <x-button href="{{ route('pages.show', 'about') }}" variant="outline" size="lg" class="hc-close__ghost">
                        About Supplify
                    </x-button>
                </div>
            </x-container>
            <div class="hc-close__rule" aria-hidden="true"></div>
        </section>
    </div>
@endsection
