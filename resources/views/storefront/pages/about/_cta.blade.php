@if (($section['enabled'] ?? true) !== false)
@php
    $products = array_values(array_filter($section['product_images'] ?? []));
    $products = array_slice($products, 0, 3);
@endphp
<section class="about-cta" aria-labelledby="about-cta-heading">
    <x-container class="about-cta__inner">
        <div class="about-cta__copy">
            <h2 id="about-cta-heading" class="about-cta__title">
                {{ $section['heading'] ?? 'Find your everyday essentials.' }}
            </h2>
            @if (! empty($section['body']))
                <p class="about-cta__body">{{ $section['body'] }}</p>
            @endif
            <div class="about-cta__actions">
                @if (! empty($section['primary_label']))
                    <x-button
                        :href="url($section['primary_url'] ?? route('shop.index'))"
                        variant="accent"
                        size="lg"
                    >
                        {{ $section['primary_label'] }}
                    </x-button>
                @endif
                @if (! empty($section['secondary_label']) && ! empty($section['secondary_url']))
                    <x-button
                        :href="url($section['secondary_url'])"
                        variant="outline"
                        size="lg"
                        class="about-cta__secondary"
                    >
                        {{ $section['secondary_label'] }}
                    </x-button>
                @endif
            </div>
        </div>
        @if (count($products) > 0)
            <div class="about-cta__products" aria-hidden="true">
                @foreach ($products as $productPath)
                    <img
                        src="{{ asset(ltrim($productPath, '/')) }}"
                        alt=""
                        class="about-cta__product"
                        width="200"
                        height="280"
                        loading="lazy"
                        decoding="async"
                    >
                @endforeach
            </div>
        @endif
    </x-container>
</section>
@endif
