@if (($section['enabled'] ?? true) !== false)
@php
    $stages = array_values($section['stages'] ?? []);
@endphp
<section
    id="about-approach"
    class="about-journey"
    aria-labelledby="about-journey-heading"
    data-about-journey
>
    <x-container class="about-journey__inner">
        <header class="about-journey__header">
            <h2 id="about-journey-heading" class="about-journey__title">
                {{ $section['heading'] ?? 'From an idea to your everyday routine.' }}
            </h2>
            @if (! empty($section['intro']))
                <p class="about-journey__intro">{{ $section['intro'] }}</p>
            @endif
        </header>

        {{-- Desktop: sticky visual + flowing stages --}}
        <div class="about-journey__desktop" data-about-journey-desktop>
            <div class="about-journey__visual-col">
                <div class="about-journey__visual-sticky" data-about-journey-visual>
                    <div class="about-journey__frames">
                        @foreach ($stages as $i => $stage)
                            @php $slot = $stage['slot'] ?? 'journey_research'; @endphp
                            <div
                                class="about-journey__frame {{ $i === 0 ? 'is-active' : '' }}"
                                data-about-journey-frame="{{ $i }}"
                                aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
                            >
                                <span class="about-journey__frame-num" aria-hidden="true">{{ $stage['number'] ?? str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <x-about-media
                                    :slot-key="$slot"
                                    :path="$stage['image'] ?? null"
                                    :alt="$stage['image_alt'] ?? ''"
                                    :decorative="true"
                                    :width="800"
                                    :height="600"
                                />
                            </div>
                        @endforeach
                    </div>
                    <div class="about-journey__progress" aria-hidden="true">
                        <span class="about-journey__progress-track"></span>
                        <span class="about-journey__progress-fill" data-about-journey-progress></span>
                    </div>
                </div>
            </div>

            <ol class="about-journey__stages" data-about-journey-stages>
                @foreach ($stages as $i => $stage)
                    <li
                        class="about-journey__stage {{ $i === 0 ? 'is-active' : '' }}"
                        data-about-journey-stage="{{ $i }}"
                    >
                        <span class="about-journey__stage-num">{{ $stage['number'] ?? str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="about-journey__stage-title">{{ $stage['title'] ?? '' }}</h3>
                        @if (! empty($stage['body']))
                            <p class="about-journey__stage-body">{{ $stage['body'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Mobile / reduced-motion: each stage with its own image --}}
        <ol class="about-journey__mobile" data-about-journey-mobile>
            @foreach ($stages as $i => $stage)
                @php $slot = $stage['slot'] ?? 'journey_research'; @endphp
                <li class="about-journey__mobile-item">
                    <div class="about-journey__mobile-media">
                        <x-about-media
                            :slot-key="$slot"
                            :path="$stage['image'] ?? null"
                            :alt="$stage['image_alt'] ?? ($stage['title'] ?? '')"
                            :width="800"
                            :height="600"
                        />
                    </div>
                    <div class="about-journey__mobile-copy">
                        <span class="about-journey__stage-num">{{ $stage['number'] ?? str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="about-journey__stage-title">{{ $stage['title'] ?? '' }}</h3>
                        @if (! empty($stage['body']))
                            <p class="about-journey__stage-body">{{ $stage['body'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </x-container>
</section>
@endif
