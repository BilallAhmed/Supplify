@php
    $imageUrl = filled($section->image_path)
        ? \App\Support\MediaUrl::toUrl($section->image_path)
        : null;
@endphp

<section class="home-promo ui-section" aria-labelledby="promo-heading" data-animate="reveal">
    <x-container>
        <div
            class="home-promo__panel {{ $imageUrl ? 'home-promo__panel--image' : '' }}"
            @if ($imageUrl)
                style="--promo-image: url('{{ $imageUrl }}')"
            @endif
            data-animate-item
        >
            <div class="home-promo__copy">
                @if ($section->subtitle)
                    <p class="home-promo__eyebrow">{{ $section->subtitle }}</p>
                @endif
                <h2 id="promo-heading" class="home-promo__title">{{ $section->title }}</h2>
                @if ($section->body)
                    <p class="home-promo__body">{{ $section->body }}</p>
                @endif
                @if ($section->cta_label && $section->cta_url)
                    <div class="home-promo__actions">
                        <x-button href="{{ url($section->cta_url) }}" variant="accent" size="lg">{{ $section->cta_label }}</x-button>
                    </div>
                @endif
            </div>
        </div>
    </x-container>
</section>
