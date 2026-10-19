@php
    $slides = $heroSlides ?? collect();
@endphp

<section
    class="home-hero home-hero--promo"
    aria-roledescription="carousel"
    aria-label="Featured collections"
    data-home-hero
>
    <div class="home-hero__atmosphere" aria-hidden="true" data-hero-atmosphere>
        <div class="home-hero__bg-stack" data-hero-bg-stack>
            @foreach ($slides as $i => $slide)
                @php
                    $bg = $slide['background'] ?? asset('images/supplify-slider-background.webp');
                @endphp
                <img
                    class="home-hero__bg-image {{ $i === 0 ? 'is-active' : '' }}"
                    @if ($i === 0)
                        src="{{ $bg }}"
                        fetchpriority="high"
                    @else
                        data-src="{{ $bg }}"
                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1920' height='1080'%3E%3C/svg%3E"
                    @endif
                    alt=""
                    width="1920"
                    height="1080"
                    decoding="async"
                    data-hero-bg-image
                    data-bg-index="{{ $i }}"
                >
            @endforeach
        </div>
        <div class="home-hero__overlay"></div>
    </div>

    <div class="sr-only" aria-live="polite" data-hero-live></div>

    <div class="home-hero__viewport">
        @forelse ($slides as $i => $slide)
            @php
                $products = $slide['products'] ?? [];
                $countClass = 'is-count-'.min(3, max(1, count($products)));
            @endphp
            <article
                class="home-hero__slide {{ $i === 0 ? 'is-active' : '' }} {{ $countClass }}"
                data-hero-slide
                data-slide-index="{{ $i }}"
                @unless ($i === 0)
                    aria-hidden="true"
                    inert
                @endunless
            >
                <div class="home-hero__layout">
                    <div class="home-hero__copy" data-hero-copy>
                        @if (! empty($slide['eyebrow']))
                            <p class="home-hero__eyebrow" data-hero-line>{{ $slide['eyebrow'] }}</p>
                        @endif
                        <h1 class="home-hero__title font-display" data-hero-title data-hero-line>
                            {{ $slide['title'] }}
                        </h1>
                        @if (! empty($slide['body']))
                            <p class="home-hero__body" data-hero-line>{{ $slide['body'] }}</p>
                        @endif
                        @if (! empty($slide['cta_label']) && ! empty($slide['cta_url']))
                            <div class="home-hero__actions" data-hero-line>
                                <a href="{{ url($slide['cta_url']) }}" class="home-hero__cta" tabindex="{{ $i === 0 ? '0' : '-1' }}">
                                    {{ $slide['cta_label'] }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="home-hero__stage" data-hero-stage aria-hidden="true">
                        @foreach ($products as $pIndex => $product)
                            <div
                                class="home-hero__product home-hero__product--{{ $product['role'] ?? 'foreground' }}"
                                data-hero-product
                                data-depth="{{ $product['depth'] ?? 0.7 }}"
                            >
                                <div class="home-hero__product-enter" data-hero-enter>
                                    <div class="home-hero__product-parallax" data-hero-parallax>
                                        <img
                                            @if ($i === 0)
                                                src="{{ $product['src'] }}"
                                                @if ($pIndex === 0) fetchpriority="high" @else fetchpriority="low" @endif
                                            @else
                                                data-src="{{ $product['src'] }}"
                                                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='{{ $product['width'] ?? 420 }}' height='{{ $product['height'] ?? 800 }}'%3E%3C/svg%3E"
                                            @endif
                                            alt="{{ $product['name'] }}"
                                            width="{{ $product['width'] ?? 420 }}"
                                            height="{{ $product['height'] ?? 800 }}"
                                            decoding="async"
                                        >
                                        <span class="home-hero__contact-shadow" data-hero-shadow aria-hidden="true"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <article class="home-hero__slide is-active is-count-1" data-hero-slide>
                <div class="home-hero__layout">
                    <div class="home-hero__copy">
                        <h1 class="home-hero__title font-display">Supplify</h1>
                        <p class="home-hero__body">Explore supplements that fit your everyday routine.</p>
                        <div class="home-hero__actions">
                            <a href="{{ route('shop.index') }}" class="home-hero__cta">Shop Supplements</a>
                        </div>
                    </div>
                </div>
            </article>
        @endforelse
    </div>

    @if ($slides->count() > 1)
        <div class="home-hero__controls">
            <button type="button" class="home-hero__nav-btn" data-hero-prev aria-label="Previous slide">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
            </button>

            <div class="home-hero__dots" role="tablist" aria-label="Hero slides">
                @foreach ($slides as $i => $slide)
                    <button
                        type="button"
                        class="home-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                        data-hero-dot
                        role="tab"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        aria-label="Go to slide {{ $i + 1 }}: {{ $slide['title'] }}"
                    >
                        <span class="home-hero__dot-progress" data-hero-progress aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>

            <button type="button" class="home-hero__nav-btn" data-hero-next aria-label="Next slide">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
            </button>

            <button
                type="button"
                class="home-hero__play"
                data-hero-play
                aria-label="Pause automatic slideshow"
                aria-pressed="false"
            >
                <svg class="home-hero__play-icon is-pause" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 4.5a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h1.25a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H6.5Zm5.75 0a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1H13.5a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1h-1.25Z"/></svg>
                <svg class="home-hero__play-icon is-play" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" hidden><path d="M6.3 4.2a1 1 0 0 1 1.52-.85l8.1 5.05a1 1 0 0 1 0 1.7l-8.1 5.05A1 1 0 0 1 6 14.3V5.2a1 1 0 0 1 .3-.99Z"/></svg>
            </button>
        </div>
    @endif
</section>
