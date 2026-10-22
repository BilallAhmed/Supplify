@php
    $beats = $section->config['beats'] ?? [
        [
            'label' => '01',
            'title' => $section->title ?: 'Our journey',
            'body' => $section->body,
            'icon' => 'leaf',
        ],
    ];
    $stepDetails = [
        'Our review includes the source, available specifications and information needed for a clear label.',
        'We discuss suitable combinations and review the intended role of each ingredient.',
        'Supplier specifications and supporting documentation guide the questions we ask our partners.',
        'Readable labels, relevant precautions and thoughtful packing help keep product information close at hand.',
    ];
    $ctaUrl = $section->cta_url ?: '/pages/about';
    $ctaLabel = $section->cta_label ?: 'Discover Our Approach';
    $visualSrc = filled($section->image_path)
        ? \App\Support\MediaUrl::toUrl($section->image_path)
        : asset($section->config['visual'] ?? 'images/homepage/our-journey.webp');
    $visualCaption = $section->config['visual_caption'] ?? 'Formulas shaped for everyday routines';
@endphp

<section id="approach" class="home-approach" aria-labelledby="approach-heading" data-home-approach>
    <x-container class="home-approach__inner">
        <div class="home-approach__layout">
            <div class="home-approach__intro" data-approach-intro>
                @if ($section->subtitle)
                    <p class="home-approach__eyebrow">{{ $section->subtitle }}</p>
                @endif
                <h2 id="approach-heading" class="home-approach__title">{{ $section->title ?: 'Our journey' }}</h2>
                @if ($section->body)
                    <p class="home-approach__lede">{{ $section->body }}</p>
                @endif

                <figure class="home-approach__visual">
                    <div class="home-approach__visual-frame">
                        <img
                            src="{{ $visualSrc }}"
                            alt=""
                            class="home-approach__visual-img"
                            width="420"
                            height="520"
                            loading="lazy"
                            decoding="async"
                        >
                        <span class="home-approach__visual-glow" aria-hidden="true"></span>
                    </div>
                    @if (filled($visualCaption))
                        <figcaption class="home-approach__visual-caption">{{ $visualCaption }}</figcaption>
                    @endif
                </figure>

                <a href="{{ url($ctaUrl) }}" class="home-approach__cta" data-approach-cta>
                    <span>{{ $ctaLabel }}</span>
                    <svg class="home-approach__cta-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            <ol class="home-approach__timeline" data-approach-timeline>
                <li class="home-approach__path" aria-hidden="true">
                    <svg class="home-approach__path-svg" viewBox="0 0 4 100" preserveAspectRatio="none">
                        <line
                            class="home-approach__path-track"
                            x1="2"
                            y1="0"
                            x2="2"
                            y2="100"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                        <line
                            class="home-approach__path-progress"
                            data-approach-path
                            x1="2"
                            y1="0"
                            x2="2"
                            y2="100"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                </li>

                @foreach ($beats as $i => $beat)
                    <li class="home-approach__stage" data-approach-stage>
                        <span class="home-approach__node" aria-hidden="true">
                            <span class="home-approach__node-ring"></span>
                            <span class="home-approach__node-num">{{ $beat['label'] ?? str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        </span>
                        <article class="home-approach__card">
                            <span class="home-approach__card-icon" aria-hidden="true">
                                <x-goal-icon :name="$beat['icon'] ?? 'leaf'" class="size-5" />
                            </span>
                            <h3 class="home-approach__card-title">{{ $beat['title'] ?? '' }}</h3>
                            @if (! empty($beat['body']))
                                <p class="home-approach__card-body">{{ $beat['body'] }} {{ $beat['detail'] ?? ($stepDetails[$i] ?? '') }}</p>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-container>
</section>
