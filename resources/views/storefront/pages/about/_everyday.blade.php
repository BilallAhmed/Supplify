@if (($section['enabled'] ?? true) !== false)
<section class="about-everyday" aria-labelledby="about-everyday-heading">
    <x-container class="about-everyday__inner">
        <div class="about-everyday__media">
            <x-about-media
                slot-key="everyday"
                :path="$section['image'] ?? null"
                :alt="$section['image_alt'] ?? ''"
                :focal="$section['focal'] ?? 'center'"
                :width="1280"
                :height="720"
            />
        </div>
        <div class="about-everyday__panel">
            <h2 id="about-everyday-heading" class="about-everyday__title">
                {{ $section['heading'] ?? 'Building for the years ahead' }}
            </h2>
            @if (! empty($section['body']))
                <p class="about-everyday__body">{{ $section['body'] }}</p>
            @endif
            @if (! empty($section['body_2']))
                <p class="about-everyday__body">{{ $section['body_2'] }}</p>
            @endif
            @if (! empty($section['link_label']) && ! empty($section['link_url']))
                <a href="{{ url($section['link_url']) }}" class="about-everyday__link">
                    {{ $section['link_label'] }}
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            @endif
        </div>
    </x-container>
</section>
@endif
