@php
    $slides = $heroSlides ?? collect();
    $firstTone = ($slides->first()['tone'] ?? 'dark') === 'light' ? 'light' : 'dark';
@endphp

<section
    class="home-hero home-hero--campaign is-tone-{{ $firstTone }}"
    aria-roledescription="carousel"
    aria-label="Featured Supplify campaigns"
    data-home-hero
    data-hero-tone="{{ $firstTone }}"
>
    <div class="sr-only" aria-live="polite" data-hero-live></div>

    <div class="home-hero__viewport">
        @forelse ($slides as $i => $slide)
            @php
                $tone = ($slide['tone'] ?? 'dark') === 'light' ? 'light' : 'dark';
                $desktop = $slide['scene'] ?? '';
                $mobile = $slide['scene_mobile'] ?? $desktop;
                $alt = $slide['scene_alt'] ?? ($slide['title'] ?? 'Supplify products');
                $w = (int) ($slide['width'] ?? 1920);
                $h = (int) ($slide['height'] ?? 900);
                $mw = (int) ($slide['mobile_width'] ?? 1080);
                $mh = (int) ($slide['mobile_height'] ?? 1350);
                $focus = $slide['focus'] ?? '72% center';
                $accent = trim((string) ($slide['title_accent'] ?? ''));
                $titleRest = trim((string) ($slide['title'] ?? ''));
                $mist = (bool) ($slide['mist'] ?? true);
                $composition = $slide['composition'] ?? 'studio';
            @endphp
            <article
                class="home-hero__slide is-tone-{{ $tone }} is-composition-{{ $composition }} {{ $i === 0 ? 'is-active' : '' }}"
                data-hero-slide
                data-slide-index="{{ $i }}"
                data-hero-tone="{{ $tone }}"
                data-hero-composition="{{ $composition }}"
                style="--hero-focus: {{ $focus }}; --hero-native-width: {{ $w }}px;"
                @unless ($i === 0)
                    aria-hidden="true"
                    inert
                @endunless
            >
                <div class="home-hero__scene" aria-hidden="true" data-hero-scene>
                    <div class="home-hero__scene-plate" data-hero-scene-plate>
                        <img
                            class="home-hero__scene-image"
                            @if ($i === 0)
                                src="{{ $desktop }}"
                                fetchpriority="high"
                            @else
                                data-src="{{ $desktop }}"
                                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='{{ $w }}' height='{{ $h }}'%3E%3C/svg%3E"
                            @endif
                            data-desktop-src="{{ $desktop }}"
                            @if ($desktop !== $mobile)
                                data-mobile-src="{{ $mobile }}"
                            @endif
                            alt=""
                            width="{{ $w }}"
                            height="{{ $h }}"
                            decoding="{{ $i === 0 ? 'sync' : 'async' }}"
                            data-hero-scene-image
                        >
                    </div>

                    @if (! empty($slide['layer_products']))
                        <div class="home-hero__accent" data-hero-accent>
                            <img src="{{ $slide['accent_image'] }}" alt="" width="1000" height="900" decoding="async">
                        </div>
                        <div class="home-hero__products {{ count($slide['layer_products']) === 2 ? 'home-hero__products--pair' : '' }}" data-hero-products>
                            @foreach ($slide['layer_products'] as $productImage)
                                <div class="home-hero__bottle home-hero__bottle--{{ $loop->index }}">
                                    <span class="home-hero__contact-shadow"></span>
                                    <img @if ($i === 0) src="{{ $productImage }}" @else data-src="{{ $productImage }}" @endif alt="" width="480" height="800" decoding="async" data-hero-product-image>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($mist)
                        <div class="home-hero__mist" data-hero-mist>
                            <img
                                class="home-hero__mist-image"
                                src="{{ ($slide['mist_image'] ?? asset('images/homepage/hero/mist.svg')) }}"
                                alt=""
                                width="1600"
                                height="400"
                                decoding="async"
                                loading="lazy"
                            >
                        </div>
                    @endif
                </div>

                <div class="home-hero__atmosphere" aria-hidden="true"></div>
                <div class="home-hero__scrim" aria-hidden="true"></div>

                <div class="home-hero__layout">
                    <div class="home-hero__copy" data-hero-copy>
                        @if (! empty($slide['eyebrow']))
                            <p class="home-hero__eyebrow" data-hero-eyebrow>{{ $slide['eyebrow'] }}</p>
                        @endif

                        <h1 class="home-hero__title font-display" data-hero-title>
                            @if ($accent !== '')
                                <span class="home-hero__phrase-wrap" data-hero-phrase-wrap>
                                    <span class="home-hero__phrase home-hero__phrase--accent" data-hero-phrase>{{ $accent }}</span>
                                </span>
                            @endif
                            @if ($titleRest !== '')
                                <span class="home-hero__phrase-wrap" data-hero-phrase-wrap>
                                    <span class="home-hero__phrase home-hero__phrase--rest" data-hero-phrase>{{ $titleRest }}</span>
                                </span>
                            @endif
                        </h1>

                        @if (! empty($slide['body']))
                            <p class="home-hero__body" data-hero-body>{{ $slide['body'] }}</p>
                        @endif

                        @if (! empty($slide['cta_label']) && ! empty($slide['cta_url']))
                            <div class="home-hero__actions" data-hero-actions>
                                <a
                                    href="{{ url($slide['cta_url']) }}"
                                    class="home-hero__cta"
                                    tabindex="{{ $i === 0 ? '0' : '-1' }}"
                                >
                                    <span>{{ $slide['cta_label'] }}</span>
                                    <svg class="home-hero__cta-arrow" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L10.22 6.03a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <span class="sr-only">{{ $alt }}</span>
            </article>
        @empty
            <article class="home-hero__slide is-active is-tone-dark" data-hero-slide data-hero-tone="dark">
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
        <div class="home-hero__controls" data-hero-controls>
            <button type="button" class="home-hero__nav-btn" data-hero-prev aria-label="Previous slide">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
            </button>

            <div class="home-hero__dots" role="tablist" aria-label="Hero slides">
                @foreach ($slides as $i => $slide)
                    @php
                        $dotLabel = trim(($slide['title_accent'] ?? '').' '.($slide['title'] ?? 'Slide '.($i + 1)));
                    @endphp
                    <button
                        type="button"
                        class="home-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                        data-hero-dot
                        role="tab"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        aria-label="Go to slide {{ $i + 1 }}: {{ $dotLabel }}"
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
