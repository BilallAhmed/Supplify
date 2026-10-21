@props([
    'image',
    'imageAlt' => '',
    'title',
    'description' => null,
    'eyebrow' => null,
    'headingId',
    'currentLabel',
    'category' => null,
    'variant' => null,
])

{{-- Compact photographic banner matching About (1905x440 natural aspect). --}}
<section class="page-banner about-hero {{ $variant === 'journal' ? 'page-banner--journal' : '' }}" aria-labelledby="{{ $headingId }}">
    <div class="page-banner__frame about-hero__banner">
        <img
            src="{{ asset($image) }}"
            alt="{{ $imageAlt }}"
            class="page-banner__photo about-hero__photo"
            width="1905"
            height="440"
            fetchpriority="high"
            decoding="async"
        >
        <div class="page-banner__shade about-hero__shade" aria-hidden="true"></div>

        <x-container class="page-banner__body about-hero__inner">
            <div class="page-banner__copy about-hero__copy">
                <nav class="about-hero__crumb" aria-label="Breadcrumb">
                    <ol class="about-hero__crumb-list">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li aria-hidden="true">/</li>
                        @if ($category)
                            <li><a href="{{ route('shop.index') }}">All Products</a></li>
                            <li aria-hidden="true">/</li>
                        @endif
                        <li aria-current="page">{{ $currentLabel }}</li>
                    </ol>
                </nav>
                @if ($eyebrow)
                    <p class="about-hero__eyebrow">{{ $eyebrow }}</p>
                @endif
                <h1 id="{{ $headingId }}" class="about-hero__title">{{ $title }}</h1>
                @if ($description)
                    <p class="about-hero__intro">{{ $description }}</p>
                @endif
            </div>
        </x-container>
    </div>
</section>
