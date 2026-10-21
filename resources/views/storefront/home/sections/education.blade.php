@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Post>|null $educationPosts */
    $posts = $educationPosts ?? collect();
    $hasPosts = $posts->isNotEmpty();
    $blogUrl = url($section->cta_url ?: '/blog');
    $blogLabel = $section->cta_label ?: 'View all articles';
@endphp

<section
    id="education"
    class="home-education home-band home-band--mist ui-section border-t border-line/40"
    aria-labelledby="education-heading"
>
    <x-container>
        <header class="home-education__intro">
            @if ($section->subtitle)
                <p class="home-education__eyebrow">{{ $section->subtitle }}</p>
            @endif

            <h2 id="education-heading" class="home-education__heading font-display">
                {{ $section->title ?: 'Learn before you buy' }}
            </h2>

            @if ($section->body)
                <p class="home-education__lede">{{ $section->body }}</p>
            @endif
        </header>

        @if ($hasPosts)
            <div
                class="home-education__grid"
                role="list"
                aria-label="Educational articles"
            >
                @include('storefront.home.partials.education-cards', ['posts' => $posts])
            </div>

            <div class="home-education__actions">
                <a href="{{ $blogUrl }}" class="home-education__view-all">
                    {{ $blogLabel }}
                </a>
            </div>
        @else
            <div class="home-education__empty">
                <p>Insights will appear here as posts are published.</p>
                <a href="{{ $blogUrl }}" class="home-education__view-all">{{ $blogLabel }}</a>
            </div>
        @endif
    </x-container>
</section>
