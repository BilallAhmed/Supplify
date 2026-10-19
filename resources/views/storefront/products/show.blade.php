@extends('layouts.storefront')

@php
    $title = ($product->seo_title ?: $product->name).' — '.config('app.name');
    $description = $product->seo_description
        ?: ($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160));
    $image = $product->primaryImage();
    $primaryCategory = $product->categories->first();
    $temporary = $product->hasTemporaryPricing();

    // Reliable meta chips only when the name / short description clearly encode them.
    $metaBits = [];
    $haystack = trim($product->name.' '.$product->short_description);
    if (preg_match('/\b(\d[\d.,]*\s?(?:mg|mcg|iu|g))\b/i', $haystack, $m)) {
        $metaBits[] = ['label' => 'Strength', 'value' => $m[1]];
    }
    if (preg_match('/\b(\d+\s?(?:tablets?|softgels?|capsules?|veg\s?caps?|vegcaps?))\b/i', $haystack, $m)) {
        $metaBits[] = ['label' => 'Pack', 'value' => $m[1]];
    }
    if (preg_match('/\b(tablets?|softgels?|capsules?|veg\s?caps?|vegcaps?)\b/i', $haystack, $m)
        && ! collect($metaBits)->contains(fn ($bit) => str_contains(strtolower($bit['value']), strtolower($m[1])))
    ) {
        $metaBits[] = ['label' => 'Format', 'value' => \Illuminate\Support\Str::title($m[1])];
    }

    $benefitLines = filled($product->benefits)
        ? collect(preg_split('/\R+/', (string) $product->benefits))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '*'))
            ->values()
        : collect();

    $factsLines = filled($product->supplement_facts)
        ? collect(preg_split('/\R+/', (string) $product->supplement_facts))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
        : collect();
    $factsStructured = $factsLines->isNotEmpty()
        && $factsLines->every(fn ($line) => str_contains($line, ':'));

    $hasAbout = filled($product->description);
    $hasBenefits = $benefitLines->isNotEmpty();
    $hasFormula = filled($product->ingredients) || filled($product->supplement_facts);
    $hasDirections = filled($product->directions) || filled($product->warnings);
    $sectionLinks = array_filter([
        $hasAbout || $hasBenefits ? ['id' => 'pdp-overview', 'label' => 'Overview'] : null,
        $hasFormula ? ['id' => 'pdp-formula', 'label' => 'Ingredients'] : null,
        $hasDirections ? ['id' => 'pdp-directions', 'label' => 'Directions'] : null,
        $related->isNotEmpty() ? ['id' => 'pdp-related', 'label' => 'Related'] : null,
    ]);

    $backLabelIndex = $product->images->count() > 1 ? 1 : null;
@endphp

@section('title', $title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $description), 160))
@section('canonical', route('products.show', $product->slug))
@section('og_type', 'product')
@section('og_title', $product->seo_title ?: $product->name)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags((string) $description), 160))

@push('head')
    @if ($image)
        <meta property="og:image" content="{{ $image->url() }}">
        <meta name="twitter:image" content="{{ $image->url() }}">
        <link rel="preload" as="image" href="{{ $image->url() }}">
    @endif
@endpush

@section('content')
    <div
        class="pdp"
        x-data="productPdp(@js($pdp))"
        @keydown.escape.window="lightboxOpen && closeLightbox()"
    >
        <x-container class="pdp__container">
            <nav class="pdp__crumbs" aria-label="Breadcrumb">
                <ol class="pdp__crumbs-list">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('shop.index') }}">Shop</a></li>
                    @if ($primaryCategory)
                        <li aria-hidden="true">/</li>
                        <li>
                            <a href="{{ route('shop.category', $primaryCategory->slug) }}">{{ $primaryCategory->name }}</a>
                        </li>
                    @endif
                    <li aria-hidden="true">/</li>
                    <li class="pdp__crumbs-current" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="pdp__layout">
                {{-- Gallery --}}
                <div class="pdp__gallery" data-pdp-gallery>
                    <div class="pdp__gallery-inner">
                        @if (count($pdp['gallery'] ?? []) > 1)
                            <div class="pdp__thumbs" role="list" aria-label="Product images">
                                <template x-for="(img, index) in gallery" :key="img.id || img.url">
                                    <button
                                        type="button"
                                        class="pdp__thumb"
                                        role="listitem"
                                        :class="{ 'is-active': isThumbActive(index) }"
                                        :aria-current="isThumbActive(index) ? 'true' : 'false'"
                                        :aria-label="'View image ' + (index + 1)"
                                        @click="selectImage(index)"
                                    >
                                        <img :src="img.url" :alt="img.alt" loading="lazy" width="96" height="96">
                                    </button>
                                </template>
                            </div>
                        @endif

                        <div class="pdp__stage-wrap">
                            <div
                                class="pdp__stage"
                                x-ref="zoomStage"
                                @pointerenter="onZoomEnter()"
                                @pointerleave="onZoomLeave()"
                                @pointermove="onZoomMove($event)"
                            >
                                <button
                                    type="button"
                                    class="pdp__stage-hit"
                                    @click="openLightbox()"
                                    aria-label="Enlarge product image"
                                >
                                    <img
                                        x-ref="mainImage"
                                        :src="current.imageUrl"
                                        :alt="current.imageAlt"
                                        class="pdp__stage-img"
                                        :class="{ 'is-zooming': zoomActive }"
                                        :style="mainImageStyle()"
                                        width="900"
                                        height="900"
                                        fetchpriority="high"
                                        @load="onMainImageLoad($event)"
                                    >
                                </button>

                                <p class="pdp__zoom-hint" x-show="zoomEnabled && !zoomActive" x-cloak>
                                    Hover to zoom
                                </p>

                                <button
                                    type="button"
                                    class="pdp__expand"
                                    @click="openLightbox()"
                                    aria-label="Open enlarged gallery"
                                >
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M9 4H4v5M15 4h5v5M9 20H4v-5M20 15v5h-5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span>Expand</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Purchase panel --}}
                <div class="pdp__buy" x-ref="purchaseBlock">
                    @if ($primaryCategory)
                        <p class="pdp__family">
                            <a href="{{ route('shop.category', $primaryCategory->slug) }}">{{ $primaryCategory->name }}</a>
                        </p>
                    @endif

                    <h1 class="pdp__title font-display">{{ $product->name }}</h1>

                    @if ($metaBits !== [])
                        <ul class="pdp__meta" aria-label="Product details">
                            @foreach ($metaBits as $bit)
                                <li>
                                    <span class="pdp__meta-label">{{ $bit['label'] }}</span>
                                    <span class="pdp__meta-value">{{ $bit['value'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($product->short_description)
                        <p class="pdp__lede">{{ $product->short_description }}</p>
                    @endif

                    <div class="pdp__price-row">
                        <p class="pdp__price font-display" x-html="current.priceHtml"></p>
                        <p class="pdp__compare" x-show="current.compareHtml" x-html="current.compareHtml" x-cloak></p>
                        <span class="pdp__sale" x-show="current.onSale" x-cloak>Sale</span>
                    </div>

                    <div class="pdp__status-row">
                        <span
                            class="pdp__stock"
                            :class="'pdp__stock--' + (current.stockVariant || 'neutral')"
                            x-text="current.stockLabel"
                        ></span>
                        <span class="pdp__sku">SKU <span x-text="current.sku"></span></span>
                    </div>

                    @if ($product->isVariable())
                        <div class="pdp__options">
                            <template x-for="attr in attributes" :key="attr.name">
                                <fieldset class="pdp__option">
                                    <legend class="pdp__option-label" x-text="attr.name"></legend>
                                    <div class="pdp__option-values">
                                        <template x-for="value in attr.values" :key="value">
                                            <button
                                                type="button"
                                                class="pdp__option-btn"
                                                data-pdp-option
                                                :class="{ 'is-selected': selected[attr.name] === value }"
                                                :disabled="isOptionDisabled(attr.name, value)"
                                                :aria-pressed="selected[attr.name] === value"
                                                @click="selectOption(attr.name, value)"
                                                x-text="value"
                                            ></button>
                                        </template>
                                    </div>
                                </fieldset>
                            </template>
                            <p class="pdp__option-error" x-show="!canAdd() && Object.values(selected).filter(Boolean).length && !temporaryPricing" x-cloak>
                                That combination is unavailable. Choose another option.
                            </p>
                        </div>
                    @endif

                    <div class="pdp__actions">
                        <form
                            method="POST"
                            action="{{ route('cart.items.store') }}"
                            class="pdp__cart-form"
                            x-show="canAdd()"
                            @if (! $product->isPurchasable() || $product->isVariable()) x-cloak @endif
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <template x-if="productType === 'variable' && matched">
                                <input type="hidden" name="variation_id" :value="matched.id">
                            </template>

                            <div class="pdp__qty">
                                <label class="sr-only" for="pdp-qty">Quantity</label>
                                <button type="button" class="pdp__qty-btn" @click="quantity = Math.max(1, quantity - 1)" aria-label="Decrease quantity">−</button>
                                <input
                                    id="pdp-qty"
                                    type="number"
                                    name="quantity"
                                    min="1"
                                    :max="current.maxQty"
                                    x-model.number="quantity"
                                    class="pdp__qty-input"
                                >
                                <button type="button" class="pdp__qty-btn" @click="quantity = Math.min(current.maxQty || 99, quantity + 1)" aria-label="Increase quantity">+</button>
                            </div>

                            <button type="submit" class="pdp__add">Add to cart</button>
                        </form>

                        <div class="pdp__unavailable" x-show="!canAdd()" @if ($product->isPurchasable() && ! $product->isVariable()) x-cloak @endif>
                            <button
                                type="button"
                                class="pdp__add pdp__add--disabled"
                                disabled
                                x-text="unavailableLabel()"
                            ></button>
                        </div>

                        <button
                            type="button"
                            class="pdp__wish"
                            data-product-action="wishlist"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            aria-pressed="false"
                            aria-label="Save {{ $product->name }} to favorites"
                        >
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 20s-7-4.4-7-9.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7 3.6C19 15.6 12 20 12 20Z" stroke-linejoin="round"/>
                            </svg>
                            <span>Save</span>
                        </button>
                    </div>

                    @if (($shippingPage ?? null) || ($returnsPage ?? null))
                        <ul class="pdp__policies">
                            @if ($shippingPage ?? null)
                                <li>
                                    <a href="{{ route('pages.show', $shippingPage->slug) }}">Shipping information</a>
                                </li>
                            @endif
                            @if ($returnsPage ?? null)
                                <li>
                                    <a href="{{ route('pages.show', $returnsPage->slug) }}">Returns policy</a>
                                </li>
                            @endif
                        </ul>
                    @endif

                    @php
                        $storefrontTags = $product->tags->reject(
                            fn ($tag) => in_array($tag->slug, ['temporary-pricing', 'editorial-draft'], true)
                        );
                    @endphp
                    @if ($storefrontTags->isNotEmpty())
                        <div class="pdp__tags" aria-label="Tags">
                            @foreach ($storefrontTags as $tag)
                                <span class="pdp__tag">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if (count($sectionLinks) >= 2)
                <nav class="pdp__toc" aria-label="On this page">
                    <ul>
                        @foreach ($sectionLinks as $link)
                            <li><a href="#{{ $link['id'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($hasAbout || $hasBenefits)
                <div id="pdp-overview" class="pdp__band">
                    @if ($hasAbout)
                        <section class="pdp__section pdp__section--editorial" aria-labelledby="pdp-about-heading">
                            <h2 id="pdp-about-heading" class="pdp__section-title font-display">About this formula</h2>
                            <div class="pdp__prose">{{ $product->description }}</div>
                        </section>
                    @endif

                    @if ($hasBenefits)
                        <section class="pdp__section" aria-labelledby="pdp-benefits-heading">
                            <h2 id="pdp-benefits-heading" class="pdp__section-title font-display">Key benefits</h2>
                            <ul class="pdp__benefits">
                                @foreach ($benefitLines as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @endif

            @if ($hasFormula)
                <section id="pdp-formula" class="pdp__section pdp__section--panel" aria-labelledby="pdp-formula-heading">
                    <div class="pdp__section-head">
                        <h2 id="pdp-formula-heading" class="pdp__section-title font-display">Formula details</h2>
                        @if ($backLabelIndex !== null)
                            <button type="button" class="pdp__label-link" @click="openLightbox({{ $backLabelIndex }})">
                                View back label
                            </button>
                        @endif
                    </div>

                    @if (filled($product->supplement_facts))
                        <div class="pdp__facts">
                            <h3 class="pdp__subhead">Supplement facts</h3>
                            @if ($factsStructured)
                                <table class="pdp__facts-table">
                                    <caption class="sr-only">Supplement facts</caption>
                                    <tbody>
                                        @foreach ($factsLines as $line)
                                            @php
                                                [$factKey, $factVal] = array_pad(explode(':', $line, 2), 2, '');
                                            @endphp
                                            <tr>
                                                <th scope="row">{{ trim($factKey) }}</th>
                                                <td>{{ trim($factVal) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="pdp__prose pdp__prose--compact">{{ $product->supplement_facts }}</div>
                            @endif
                        </div>
                    @endif

                    @if (filled($product->ingredients))
                        <div class="pdp__ingredients">
                            <h3 class="pdp__subhead">Ingredients</h3>
                            <div class="pdp__prose pdp__prose--compact">{{ $product->ingredients }}</div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($hasDirections)
                <section id="pdp-directions" class="pdp__section" aria-labelledby="pdp-info-heading">
                    <h2 id="pdp-info-heading" class="pdp__section-title font-display">Directions and important information</h2>
                    <div class="pdp__accordions">
                        @if (filled($product->directions))
                            <details class="pdp__accordion" open>
                                <summary>Directions</summary>
                                <div class="pdp__prose pdp__prose--compact">{{ $product->directions }}</div>
                            </details>
                        @endif
                        @if (filled($product->warnings))
                            <details class="pdp__accordion pdp__accordion--warn" open>
                                <summary>Warnings</summary>
                                <div class="pdp__prose pdp__prose--compact">{{ $product->warnings }}</div>
                            </details>
                        @endif
                    </div>
                </section>
            @endif

            @if ($related->isNotEmpty())
                <section id="pdp-related" class="pdp__related" aria-labelledby="related-heading">
                    <x-section-heading
                        id="related-heading"
                        title="You may also like"
                        description="Related formulas from the same goals and routines."
                    />
                    <div class="pdp__related-grid">
                        @foreach ($related as $relatedProduct)
                            <x-product-tile :product="$relatedProduct" />
                        @endforeach
                    </div>
                </section>
            @endif
        </x-container>

        {{-- Mobile sticky purchase bar --}}
        <div
            class="pdp__sticky"
            x-show="showStickyBar"
            x-transition.opacity
            x-cloak
            aria-label="Quick purchase"
        >
            <div class="pdp__sticky-inner">
                <div class="pdp__sticky-copy">
                    <p class="pdp__sticky-price font-display" x-html="current.priceHtml"></p>
                    <p class="pdp__sticky-stock" x-text="current.stockLabel"></p>
                </div>

                <form
                    method="POST"
                    action="{{ route('cart.items.store') }}"
                    class="pdp__sticky-form"
                    x-show="canAdd()"
                    x-cloak
                >
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <template x-if="productType === 'variable' && matched">
                        <input type="hidden" name="variation_id" :value="matched.id">
                    </template>
                    <input type="hidden" name="quantity" :value="quantity">
                    <button type="submit" class="pdp__sticky-add">Add to cart</button>
                </form>

                <button
                    type="button"
                    class="pdp__sticky-add pdp__sticky-add--secondary"
                    x-show="!canAdd()"
                    x-cloak
                    @click="productType === 'variable' && !matched && focusOptions()"
                    :disabled="!(productType === 'variable' && !matched)"
                    x-text="unavailableLabel()"
                ></button>
            </div>
        </div>

        {{-- Enlarged gallery dialog --}}
        <div
            class="pdp-lb"
            x-show="lightboxOpen"
            x-cloak
            x-ref="lightboxDialog"
            role="dialog"
            aria-modal="true"
            aria-label="Product image gallery"
            @keydown.tab="trapLightboxFocus($event)"
        >
            <div class="pdp-lb__backdrop" @click="closeLightbox()" aria-hidden="true"></div>
            <div class="pdp-lb__panel">
                <div class="pdp-lb__toolbar">
                    <div class="pdp-lb__tools">
                        <button type="button" class="pdp-lb__tool" @click="lightboxZoomOut()" aria-label="Zoom out">−</button>
                        <button type="button" class="pdp-lb__tool" @click="resetLightboxTransform()" aria-label="Reset zoom">Reset</button>
                        <button type="button" class="pdp-lb__tool" @click="lightboxZoomIn()" aria-label="Zoom in">+</button>
                    </div>
                    <button type="button" class="pdp-lb__close" @click="closeLightbox()" aria-label="Close gallery" x-ref="lightboxClose">
                        <svg viewBox="0 0 20 20" width="20" height="20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <div
                    class="pdp-lb__stage"
                    @wheel.prevent="onLightboxWheel($event)"
                    @pointerdown="onLightboxPointerDown($event)"
                    @pointermove="onLightboxPointerMove($event)"
                    @pointerup="onLightboxPointerUp($event)"
                    @pointercancel="onLightboxPointerUp($event)"
                    @touchstart="onLightboxTouchStart($event)"
                    @touchmove="onLightboxTouchMove($event)"
                    @touchend="onLightboxTouchEnd($event)"
                >
                    <button
                        type="button"
                        class="pdp-lb__nav pdp-lb__nav--prev"
                        x-show="gallery.length > 1"
                        @click="lightboxPrev()"
                        aria-label="Previous image"
                    >‹</button>

                    <img
                        :src="displayImage.url"
                        :alt="displayImage.alt"
                        class="pdp-lb__img"
                        :style="lightboxImageStyle()"
                        :class="{ 'is-zoomed': lightboxZoom > 1 }"
                        draggable="false"
                    >

                    <button
                        type="button"
                        class="pdp-lb__nav pdp-lb__nav--next"
                        x-show="gallery.length > 1"
                        @click="lightboxNext()"
                        aria-label="Next image"
                    >›</button>
                </div>

                <div class="pdp-lb__footer" x-show="gallery.length > 1">
                    <div class="pdp-lb__thumbs" role="list">
                        <template x-for="(img, index) in gallery" :key="'lb-' + (img.id || img.url)">
                            <button
                                type="button"
                                class="pdp-lb__thumb"
                                role="listitem"
                                :class="{ 'is-active': activeIndex === index }"
                                :aria-current="activeIndex === index ? 'true' : 'false'"
                                @click="selectImage(index); resetLightboxTransform()"
                            >
                                <img :src="img.url" :alt="img.alt" loading="lazy" width="72" height="72">
                            </button>
                        </template>
                    </div>
                    <p class="pdp-lb__count">
                        <span x-text="activeIndex + 1"></span> / <span x-text="gallery.length"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @php
        $breadcrumbItems = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
        ];
        if ($primaryCategory) {
            $breadcrumbItems[] = [
                'name' => $primaryCategory->name,
                'url' => route('shop.category', $primaryCategory->slug),
            ];
        }
        $breadcrumbItems[] = [
            'name' => $product->name,
            'url' => route('products.show', $product->slug),
        ];
    @endphp
    @include('storefront.partials.json-ld-breadcrumb', ['items' => $breadcrumbItems])
    @include('storefront.partials.json-ld-product', ['product' => $product])
@endsection
