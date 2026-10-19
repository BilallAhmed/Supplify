@if (($section['enabled'] ?? true) !== false)
<section class="about-purpose" aria-labelledby="about-purpose-heading">
    <x-container class="about-purpose__inner">
        <div class="about-purpose__media">
            <x-about-media
                slot-key="story"
                :path="$section['image'] ?? null"
                :alt="$section['image_alt'] ?? ''"
                :focal="$section['focal'] ?? 'center'"
                :width="900"
                :height="600"
            />
        </div>
        <div class="about-purpose__copy">
            <h2 id="about-purpose-heading" class="about-purpose__title">
                {{ $section['heading'] ?? 'A clear purpose from the beginning' }}
            </h2>
            <div class="about-purpose__prose">
                @if (! empty($section['paragraph_1']))
                    <p>{{ $section['paragraph_1'] }}</p>
                @endif
                @if (! empty($section['paragraph_2']))
                    <p>{{ $section['paragraph_2'] }}</p>
                @endif
                @if (! empty($section['paragraph_3']))
                    <p>{{ $section['paragraph_3'] }}</p>
                @endif
            </div>
            @if (! empty($section['pull_quote']))
                <p class="about-purpose__pull">{{ $section['pull_quote'] }}</p>
            @endif
        </div>
    </x-container>
</section>
@endif
