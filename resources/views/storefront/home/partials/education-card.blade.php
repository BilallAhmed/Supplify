@php
    /** @var \App\Models\Post $post */
    $editorialImage = $post->hasCustomFeaturedImage() ? $post->featuredImageUrl() : asset(match ($post->slug) {
        'magnesium-forms-explained' => 'images/about/about-journey-research.webp',
        'how-to-read-a-supplement-label' => 'images/about/about-journey-documentation.webp',
        default => 'images/about/about-everyday.webp',
    });
    $description = $post->excerpt
        ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $post->body))), 110);
@endphp

<article class="home-education__card" role="listitem" data-education-card data-post-id="{{ $post->id }}">
    <a href="{{ route('blog.show', $post->slug) }}" class="home-education__media" tabindex="-1" aria-hidden="true">
        <img
            src="{{ $editorialImage }}"
            alt=""
            class="home-education__img"
            loading="lazy"
            decoding="async"
            width="640"
            height="400"
        >
    </a>

    <div class="home-education__body">
        @if ($post->category)
            <p class="home-education__category">{{ $post->category->name }}</p>
        @endif

        <h3 class="home-education__title font-display">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </h3>

        @if (filled($description))
            <p class="home-education__excerpt">{{ $description }}</p>
        @endif

        <a href="{{ route('blog.show', $post->slug) }}" class="home-education__more-link">
            {{ $articleLabel ?? 'Learn More' }}
        </a>
    </div>
</article>
