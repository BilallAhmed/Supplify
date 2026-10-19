@if (($section['enabled'] ?? true) !== false)
@php
    $resolved = \App\Support\AboutPageContent::resolveImage('hero', $section['image'] ?? null);
    $photoSrc = $resolved['is_photography'] ? $resolved['src'] : null;
    $imagePath = $section['image'] ?? \App\Support\AboutPageContent::SLOTS['hero']['path'] ?? null;
    $absolute = $imagePath ? public_path(ltrim(str_replace('\\', '/', (string) $imagePath), '/')) : null;
    $size = ($absolute && is_file($absolute)) ? @getimagesize($absolute) : false;
    $imgW = is_array($size) ? (int) $size[0] : 1905;
    $imgH = is_array($size) ? (int) $size[1] : 440;
@endphp
<section class="page-banner about-hero" aria-labelledby="about-hero-heading">
    <div class="page-banner__frame about-hero__banner">
        @if ($photoSrc)
            <img
                src="{{ $photoSrc }}"
                alt="{{ $section['image_alt'] ?? '' }}"
                class="page-banner__photo about-hero__photo"
                width="{{ $imgW }}"
                height="{{ $imgH }}"
                fetchpriority="high"
                decoding="async"
            >
        @else
            <div class="page-banner__photo page-banner__photo--fallback about-hero__photo" aria-hidden="true"></div>
        @endif
        <div class="page-banner__shade about-hero__shade" aria-hidden="true"></div>

        <x-container class="page-banner__body about-hero__inner">
            <div class="page-banner__copy about-hero__copy">
                <nav class="about-hero__crumb" aria-label="Breadcrumb">
                    <ol class="about-hero__crumb-list">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">About</li>
                    </ol>
                </nav>
                @if (! empty($section['eyebrow']))
                    <p class="about-hero__eyebrow">{{ $section['eyebrow'] }}</p>
                @endif
                <h1 id="about-hero-heading" class="about-hero__title">
                    {{ $section['heading'] ?? 'Trusted Nutrition for a Healthier Tomorrow' }}
                </h1>
                @if (! empty($section['intro']))
                    <p class="about-hero__intro">{{ $section['intro'] }}</p>
                @endif
                @if (! empty($section['cta_label']))
                    <a href="{{ $section['cta_href'] ?? '#about-approach' }}" class="about-hero__cta">
                        {{ $section['cta_label'] }}
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M8 3v10M4 9l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif
            </div>
        </x-container>
    </div>
</section>
@endif
