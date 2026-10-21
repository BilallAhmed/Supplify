@if (($section['enabled'] ?? true) !== false)
<section class="about-cta" aria-labelledby="about-cta-heading">
    <x-container class="about-cta__shell">
        <div class="about-cta__content" data-reveal="up">
            <p class="about-cta__eyebrow">{{ $section['eyebrow'] ?? 'DISCOVER SUPPLIFY' }}</p>
            <h2 id="about-cta-heading" class="about-cta__title">
                {{ $section['heading'] ?? 'Get to know Supplify' }}
            </h2>
            @if (! empty($section['body']))
                <p class="about-cta__body">{{ $section['body'] }}</p>
            @endif
            @if (! empty($section['primary_label']))
                <div class="about-cta__actions">
                    <x-button
                        :href="url($section['primary_url'] ?? route('shop.index'))"
                        variant="accent"
                        size="lg"
                    >
                        {{ $section['primary_label'] }}
                    </x-button>
                </div>
            @endif
        </div>

        <div class="about-cta__rule" aria-hidden="true"></div>
    </x-container>
</section>
@endif
