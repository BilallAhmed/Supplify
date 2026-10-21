@props([
    'post',
    'priority' => false,
])

@php
    $href = route('blog.show', $post->slug);
    $imageUrl = $post->featuredImageUrl();
    $imageAlt = $post->featuredImageAlt();
    $readingMinutes = $post->readingTimeMinutes();
    $excerpt = $post->excerpt
        ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->body)) ?? ''), 140);
@endphp

<article {{ $attributes->class(['blog-card']) }}>
    <a
        href="{{ $href }}"
        class="blog-card__link"
        aria-label="Read article: {{ $post->title }}"
    >
        <div class="blog-card__media">
            <img
                src="{{ $imageUrl }}"
                alt="{{ $imageAlt }}"
                class="blog-card__img"
                width="640"
                height="400"
                @if ($priority)
                    fetchpriority="high"
                    loading="eager"
                @else
                    loading="lazy"
                @endif
                decoding="async"
            >
        </div>

        <div class="blog-card__body">
            <div class="blog-card__meta">
                @if ($post->category)
                    <span class="blog-card__category">{{ $post->category->name }}</span>
                @endif
                @if ($post->published_at)
                    <time class="blog-card__date" datetime="{{ $post->published_at->toDateString() }}">
                        {{ $post->published_at->format('M j, Y') }}
                    </time>
                @endif
                @if ($readingMinutes)
                    <span class="blog-card__read">{{ $readingMinutes }} min read</span>
                @endif
            </div>

            <h2 class="blog-card__title font-display">{{ $post->title }}</h2>

            @if (filled($excerpt))
                <p class="blog-card__excerpt">{{ $excerpt }}</p>
            @endif

            <span class="blog-card__cta">
                Read Article
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    </a>
</article>
