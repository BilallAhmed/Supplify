@php
    $reasons = $section->config['reasons'] ?? [];
    $stripWords = $section->config['strip_words'] ?? [];
@endphp
<section id="why-supplify" class="why-supplify" aria-labelledby="why-supplify-heading" data-why-supplify>
    <x-container class="why-supplify__container">
        <div class="why-supplify__layout">
            <figure class="why-supplify__visual">
                <img src="{{ \App\Support\HomepageLayout::image($section->image_path) }}" alt="{{ $section->config['image_alt'] ?? '' }}" width="900" height="1125" loading="lazy" decoding="async">
                <button class="why-supplify__pause" type="button" data-strip-pause aria-pressed="false" aria-label="Pause moving strip"><svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M5 3v10M11 3v10" stroke="currentColor" stroke-width="1.5"/></svg><span data-strip-label>Pause</span></button>
                <div class="why-supplify__ribbon" aria-hidden="true">
                    <div class="why-supplify__track">
                        @for ($copy = 0; $copy < 2; $copy++)
                            <div class="why-supplify__strip-group">
                                @foreach ($stripWords as $word)
                                    <span>{{ $word }}</span><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M10 2v16M2 10h16M4.4 4.4l11.2 11.2M15.6 4.4 4.4 15.6" stroke="currentColor" stroke-width="1.5"/></svg>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                </div>
            </figure>
            <div class="why-supplify__content">
                <p class="why-supplify__eyebrow">{{ $section->subtitle }}</p>
                <h2 id="why-supplify-heading">{{ $section->title }}</h2>
                <p class="why-supplify__intro">{{ $section->body }}</p>
                <div class="why-supplify__reasons">
                    @foreach ($reasons as $reason)
                        <article class="why-supplify__reason">
                            <span class="why-supplify__icon"><x-goal-icon :name="$reason['icon']" /></span>
                            <h3>{{ $reason['title'] }}</h3>
                            <p>{{ $reason['body'] }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="why-supplify__contact">
                    <p>{{ $section->config['contact_prompt'] ?? '' }}<br><strong>{{ $section->config['contact_emphasis'] ?? '' }}</strong></p>
                    <a class="home-view-all" href="{{ url($section->cta_url ?: '/pages/contact') }}">{{ $section->cta_label }}<svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                </div>
            </div>
        </div>
    </x-container>
</section>
