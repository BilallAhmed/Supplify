@extends('layouts.storefront')

@php
    $pageTitle = 'Blogs — '.config('app.name');
    $pageDescription = 'Explore practical insights on nutrition, ingredients, and supplements to help you make more informed choices.';
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    $activeCategorySlug = $filters['category'] ?? null;
    $hasActiveFilters = $searchQuery !== '' || filled($activeCategorySlug) || filled($filters['tag'] ?? null);
    $totalMatching = $totalMatching ?? $posts->total();
    $resultsLabel = $totalMatching === 0
        ? '0 articles'
        : $totalMatching.' '.\Illuminate\Support\Str::plural('article', $totalMatching);

    $blogParams = function (array $overrides = []) use ($searchQuery, $activeCategorySlug, $filters) {
        $params = [
            'q' => $searchQuery !== '' ? $searchQuery : null,
            'category' => $activeCategorySlug ?: null,
            'tag' => filled($filters['tag'] ?? null) ? $filters['tag'] : null,
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return array_filter($params, static fn ($value) => $value !== null && $value !== '');
    };

    $clearUrl = route('blog.index');
    $visibleCategories = $categories->filter(fn ($category) => ($category->published_posts_count ?? 0) > 0);
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('canonical', route('blog.index'))
@section('og_type', 'website')

@section('content')
    <x-page-image-banner
        image="images/blog/blog-hero.webp"
        image-alt="A sunlit wellness reading desk with a journal and natural ingredients"
        eyebrow="Supplify Journal"
        title="Learn More About Everyday Wellness"
        description="Explore practical insights on nutrition, ingredients, and supplements to help you make more informed choices."
        variant="journal"
        heading-id="blog-heading"
        current-label="Blogs"
    />

    <x-container class="blog-journal py-10 lg:py-12">
        <div class="blog-toolbar">
            <form method="GET" action="{{ route('blog.index') }}" class="blog-toolbar__search" role="search">
                @if ($activeCategorySlug)
                    <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
                @endif
                @if (! empty($filters['tag']))
                    <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                @endif

                <label for="blog-q" class="sr-only">Search articles</label>
                <span class="blog-toolbar__search-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <input
                    id="blog-q"
                    class="blog-control blog-control--search"
                    name="q"
                    type="search"
                    value="{{ $searchQuery }}"
                    placeholder="Search articles"
                    autocomplete="off"
                >
                <button type="submit" class="blog-control blog-control--btn">Search</button>
            </form>

            <div class="blog-toolbar__meta">
                <p class="blog-toolbar__count" aria-live="polite">{{ $resultsLabel }}</p>
                @if ($hasActiveFilters)
                    <a href="{{ $clearUrl }}" class="blog-toolbar__clear">Clear filters</a>
                @endif
            </div>
        </div>

        @if ($visibleCategories->isNotEmpty())
            <nav class="blog-cats" aria-label="Blog categories">
                <div class="blog-cats__scroller">
                    <a
                        href="{{ route('blog.index', $blogParams(['category' => null])) }}"
                        class="blog-cats__chip {{ $activeCategorySlug ? '' : 'is-active' }}"
                        @if (! $activeCategorySlug) aria-current="page" @endif
                    >
                        All
                    </a>
                    @foreach ($visibleCategories as $category)
                        <a
                            href="{{ route('blog.index', $blogParams(['category' => $category->slug])) }}"
                            class="blog-cats__chip {{ $activeCategorySlug === $category->slug ? 'is-active' : '' }}"
                            @if ($activeCategorySlug === $category->slug) aria-current="page" @endif
                        >
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        @if ($featured)
            @php
                $featuredHref = route('blog.show', $featured->slug);
                $featuredExcerpt = $featured->excerpt
                    ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $featured->body)) ?? ''), 180);
                $featuredMinutes = $featured->readingTimeMinutes();
            @endphp
            <section class="blog-featured" aria-labelledby="blog-featured-heading">
                <a href="{{ $featuredHref }}" class="blog-featured__link" aria-label="Read article: {{ $featured->title }}">
                    <div class="blog-featured__media">
                        <img
                            src="{{ $featured->featuredImageUrl() }}"
                            alt="{{ $featured->featuredImageAlt() }}"
                            class="blog-featured__img"
                            width="960"
                            height="600"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </div>
                    <div class="blog-featured__copy">
                        <div class="blog-featured__meta">
                            @if ($featured->category)
                                <span class="blog-featured__category">{{ $featured->category->name }}</span>
                            @endif
                            @if ($featured->published_at)
                                <time datetime="{{ $featured->published_at->toDateString() }}">
                                    {{ $featured->published_at->format('M j, Y') }}
                                </time>
                            @endif
                            @if ($featuredMinutes)
                                <span>{{ $featuredMinutes }} min read</span>
                            @endif
                        </div>
                        <h2 id="blog-featured-heading" class="blog-featured__title font-display">{{ $featured->title }}</h2>
                        @if (filled($featuredExcerpt))
                            <p class="blog-featured__excerpt">{{ $featuredExcerpt }}</p>
                        @endif
                        <span class="blog-featured__cta">
                            Read Article
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                </a>
            </section>
        @endif

        @if ($posts->isEmpty() && ! $featured)
            <div class="blog-empty" role="status">
                <p class="blog-empty__title font-display">
                    {{ $hasActiveFilters ? 'No articles match these filters' : 'No articles yet' }}
                </p>
                <p class="blog-empty__copy">
                    @if ($hasActiveFilters)
                        Try clearing your search or choosing another category.
                    @else
                        Check back soon, or browse the catalog meanwhile.
                    @endif
                </p>
                <div class="blog-empty__actions">
                    @if ($hasActiveFilters)
                        <a href="{{ $clearUrl }}" class="blog-control blog-control--btn blog-control--primary">View all articles</a>
                    @else
                        <a href="{{ route('shop.index') }}" class="blog-control blog-control--btn">Browse products</a>
                    @endif
                </div>
            </div>
        @elseif ($posts->isNotEmpty())
            <div class="blog-grid">
                @foreach ($posts as $index => $post)
                    <x-blog-article-card :post="$post" :priority="$index < 3 && ! $featured" />
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="blog-pagination">
                    {{ $posts->links('pagination.shop') }}
                </div>
            @endif
        @endif
    </x-container>
@endsection
