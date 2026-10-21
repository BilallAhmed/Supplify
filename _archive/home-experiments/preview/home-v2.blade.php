@extends('layouts.storefront')

@section('title', config('app.name').' — Preview homepage')
@section('meta_description', 'Cinematic preview of the Supplify storefront — scroll-driven wellness storytelling.')
@section('canonical', route('home.preview'))
@section('og_title', config('app.name').' — Preview')
@section('og_description', 'Experimental homepage direction. The live site remains at the main URL.')

@push('head')
    @vite(['resources/js/home-v2.js'])
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="home-v2" data-home-v2>
        <div class="home-v2__preview-bar" role="status">
            <p>
                Homepage preview — cinematic direction.
                <a href="{{ route('home') }}">Open live homepage</a>
            </p>
        </div>

        {{-- Opening hero: brand-first, full-bleed --}}
        <section class="home-v2-hero" aria-labelledby="home-v2-hero-title" data-v2-hero>
            <div class="home-v2-hero__media" aria-hidden="true">
                <picture>
                    <source media="(max-width: 767px)" srcset="{{ asset($heroImageMobile) }}">
                    <img
                        src="{{ asset($heroImage) }}"
                        alt=""
                        class="home-v2-hero__img"
                        width="1920"
                        height="1080"
                        fetchpriority="high"
                        decoding="async"
                        data-v2-parallax
                    >
                </picture>
                <div class="home-v2-hero__veil"></div>
            </div>

            <div class="home-v2-hero__copy">
                <p class="home-v2-hero__eyebrow" data-v2-reveal>Good morning</p>
                <h1 id="home-v2-hero-title" class="home-v2-hero__title" data-v2-reveal>
                    {{ config('app.name') }}
                </h1>
                <p class="home-v2-hero__lede" data-v2-reveal>
                    Science-led supplements for everyday performance — transparent formulas, carefully selected for routines that last.
                </p>
                <div class="home-v2-hero__actions" data-v2-reveal>
                    <x-button href="{{ route('shop.index') }}" variant="accent" size="lg">
                        Explore the range
                    </x-button>
                </div>
            </div>

            <aside class="home-v2-hero__aside" data-v2-reveal aria-label="Featured formula">
                @php $spot = $featuredProducts->first(); @endphp
                @if ($spot)
                    <p class="home-v2-hero__aside-label">Now featuring</p>
                    <p class="home-v2-hero__aside-name">{{ $spot->name }}</p>
                    <p class="home-v2-hero__aside-meta">{{ $spot->effectivePriceFormatted() }}</p>
                    <a href="{{ route('products.show', $spot) }}" class="home-v2-hero__aside-link">View formula</a>
                @else
                    <p class="home-v2-hero__aside-label">Open daily</p>
                    <p class="home-v2-hero__aside-name">Clean formulas. Clear labels.</p>
                    <a href="{{ route('shop.index') }}" class="home-v2-hero__aside-link">Shop all</a>
                @endif
            </aside>

            <p class="home-v2-hero__scroll" data-v2-reveal aria-hidden="true">Scroll</p>
        </section>

        {{-- Pinned scroll chapters --}}
        <section class="home-v2-chapters" aria-label="Product chapters" data-v2-chapters>
            <div class="home-v2-chapters__pin">
                <div class="home-v2-chapters__media" aria-hidden="true">
                    @foreach ($scenes as $index => $scene)
                        <div
                            class="home-v2-chapters__scene {{ $index === 0 ? 'is-active' : '' }}"
                            data-v2-scene="{{ $index }}"
                        >
                            <picture>
                                <source media="(max-width: 767px)" srcset="{{ asset($scene['image_mobile']) }}">
                                <img
                                    src="{{ asset($scene['image']) }}"
                                    alt=""
                                    width="1905"
                                    height="732"
                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                >
                            </picture>
                        </div>
                    @endforeach
                    <div class="home-v2-chapters__veil"></div>
                </div>

                <div class="home-v2-chapters__copy">
                    @foreach ($scenes as $index => $scene)
                        <article
                            class="home-v2-chapters__panel {{ $index === 0 ? 'is-active' : '' }}"
                            data-v2-panel="{{ $index }}"
                            aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                        >
                            <p class="home-v2-chapters__eyebrow">{{ $scene['eyebrow'] }}</p>
                            <h2 class="home-v2-chapters__title">{{ $scene['title'] }}</h2>
                            <p class="home-v2-chapters__body">{{ $scene['body'] }}</p>
                        </article>
                    @endforeach

                    <div class="home-v2-chapters__progress" aria-hidden="true">
                        @foreach ($scenes as $index => $scene)
                            <span class="home-v2-chapters__tick {{ $index === 0 ? 'is-active' : '' }}" data-v2-tick="{{ $index }}"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Formula spotlights — editorial callouts --}}
        <section class="home-v2-formulas" aria-labelledby="home-v2-formulas-title">
            <x-container class="home-v2-formulas__inner">
                <header class="home-v2-formulas__head" data-v2-reveal>
                    <p class="home-v2-formulas__eyebrow">On the shelf</p>
                    <h2 id="home-v2-formulas-title" class="home-v2-formulas__title">Formulas worth knowing</h2>
                </header>

                <ul class="home-v2-formulas__list">
                    @forelse ($featuredProducts->take(4) as $product)
                        <li class="home-v2-formulas__item" data-v2-reveal>
                            <a href="{{ route('products.show', $product) }}" class="home-v2-formulas__link">
                                <div class="home-v2-formulas__visual">
                                    @if ($product->primaryImage())
                                        <img
                                            src="{{ $product->primaryImage()->url() }}"
                                            alt=""
                                            width="320"
                                            height="320"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @endif
                                </div>
                                <div class="home-v2-formulas__meta">
                                    <p class="home-v2-formulas__name">{{ $product->name }}</p>
                                    <p class="home-v2-formulas__price">{{ $product->effectivePriceFormatted() }}</p>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="home-v2-formulas__empty">
                            <p>Featured formulas will appear here once products are published.</p>
                            <x-button href="{{ route('shop.index') }}" variant="outline">Browse shop</x-button>
                        </li>
                    @endforelse
                </ul>
            </x-container>
        </section>

        {{-- Deep brand band — cafe "Doors open" energy --}}
        <section class="home-v2-open" aria-labelledby="home-v2-open-title" data-v2-open>
            <x-container class="home-v2-open__inner">
                <div class="home-v2-open__copy" data-v2-reveal>
                    <p class="home-v2-open__eyebrow">Always open</p>
                    <h2 id="home-v2-open-title" class="home-v2-open__title">The range is ready when you are.</h2>
                    <p class="home-v2-open__body">
                        Explore clean, transparent supplements selected for everyday wellness — no noise, just what belongs in your routine.
                    </p>
                    <div class="home-v2-open__actions">
                        <x-button href="{{ route('shop.index') }}" variant="accent" size="lg">Shop all products</x-button>
                        <x-button href="{{ route('pages.show', 'about') }}" variant="outline" size="lg" class="home-v2-open__ghost">
                            Our story
                        </x-button>
                    </div>
                </div>

                @if ($goalCategories->isNotEmpty())
                    <nav class="home-v2-open__nav" aria-label="Shop by goal" data-v2-reveal>
                        <p class="home-v2-open__nav-label">Visit</p>
                        <ul>
                            @foreach ($goalCategories as $category)
                                <li>
                                    <a href="{{ route('shop.category', $category) }}">{{ $category->name }}</a>
                                </li>
                            @endforeach
                            <li>
                                <a href="{{ route('shop.index') }}">All products</a>
                            </li>
                        </ul>
                    </nav>
                @endif
            </x-container>
        </section>
    </div>
@endsection
