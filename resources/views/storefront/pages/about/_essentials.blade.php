@if (($section['enabled'] ?? true) !== false)
@php
    $blocks = array_values($section['blocks'] ?? $section['rows'] ?? []);
    $imagePath = $section['image'] ?? $section['product_image'] ?? null;
    $imageAlt = $section['image_alt'] ?? $section['product_image_alt'] ?? '';
@endphp
<section class="about-connections" aria-labelledby="about-connections-heading">
    <x-container class="about-connections__inner">
        <div class="about-connections__media">
            <x-about-media
                slot-key="connections"
                :path="$imagePath"
                :alt="$imageAlt"
                :focal="$section['focal'] ?? 'center'"
                :width="900"
                :height="675"
                class="about-connections__photo"
            />
        </div>
        <div class="about-connections__copy">
            <h2 id="about-connections-heading" class="about-connections__title">
                {{ $section['heading'] ?? 'USA connections. Pakistan focus.' }}
            </h2>
            @if (! empty($section['intro']))
                <p class="about-connections__intro">{{ $section['intro'] }}</p>
            @endif

            @if (count($blocks) > 0)
                <div class="about-connections__blocks">
                    @foreach ($blocks as $block)
                        <div class="about-connections__block">
                            @if (! empty($block['title']))
                                <h3 class="about-connections__block-title">{{ $block['title'] }}</h3>
                            @endif
                            @if (! empty($block['body']))
                                <p class="about-connections__block-body">{{ $block['body'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! empty($section['closing']))
                <p class="about-connections__closing">{{ $section['closing'] }}</p>
            @endif
        </div>
    </x-container>
</section>
@endif
