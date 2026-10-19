@props([
    'slotKey' => 'hero',
    'path' => null,
    'alt' => '',
    'focal' => 'center',
    'priority' => false,
    'decorative' => false,
    'width' => 800,
    'height' => 1000,
])

@php
    $resolved = \App\Support\AboutPageContent::resolveImage((string) $slotKey, $path);
    $objectPosition = match ($focal) {
        'top' => 'center top',
        'bottom' => 'center bottom',
        'left' => 'left center',
        'right' => 'right center',
        default => 'center center',
    };
    $fallbackProduct = $resolved['fallback_product'] ?? null;
@endphp

<div
    {{ $attributes->class(['about-media']) }}
    style="--about-media-ratio: {{ $resolved['ratio'] }}"
>
    @if ($resolved['is_photography'] && $resolved['src'])
        <img
            src="{{ $resolved['src'] }}"
            @if (! $decorative) alt="{{ $alt }}" @else alt="" @endif
            class="about-media__img"
            style="object-position: {{ $objectPosition }}"
            width="{{ $width }}"
            height="{{ $height }}"
            @if ($priority) fetchpriority="high" @else loading="lazy" @endif
            decoding="async"
            @if ($decorative) aria-hidden="true" @endif
        >
    @else
        <div class="about-media__fallback about-media__fallback--{{ $slotKey }}{{ $fallbackProduct ? '' : ' about-media__fallback--surface' }}" @if ($decorative) aria-hidden="true" @endif>
            <span class="about-media__fallback-glow" aria-hidden="true"></span>
            @if ($fallbackProduct)
                <img
                    src="{{ asset($fallbackProduct) }}"
                    alt=""
                    class="about-media__fallback-product"
                    width="360"
                    height="480"
                    @if ($priority) fetchpriority="high" @else loading="lazy" @endif
                    decoding="async"
                    aria-hidden="true"
                >
            @endif
            <img
                src="{{ asset('images/supplify-mark-dark.png') }}"
                alt=""
                class="about-media__fallback-mark"
                width="72"
                height="72"
                loading="lazy"
                decoding="async"
                aria-hidden="true"
            >
        </div>
    @endif
</div>
