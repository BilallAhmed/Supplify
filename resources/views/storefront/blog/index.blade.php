@extends('layouts.storefront')

@php
    $pageTitle = 'Blogs — '.config('app.name');
    $pageDescription = 'Insights on routines, ingredients, and how to evaluate supplements with a clearer eye.';
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('canonical', route('blog.index'))
@section('og_type', 'website')

@section('content')
    <section class="border-b border-line/60 bg-gradient-to-b from-brand-mist/50 to-surface-muted" aria-labelledby="blog-heading">
        <x-container class="py-8 sm:py-10">
            <nav class="mb-3 text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-ink" aria-current="page">Blogs</li>
                </ol>
            </nav>

            <h1 id="blog-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                Blogs
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-ink-muted sm:text-base">
                {{ $pageDescription }}
            </p>
        </x-container>
    </section>

    <x-container class="py-10 lg:py-12">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <form method="GET" action="{{ route('blog.index') }}" class="flex flex-wrap gap-2">
                @if (! empty($filters['category']))
                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                @endif
                <x-input name="q" type="search" placeholder="Search insights…" :value="$filters['q'] ?? ''" class="min-w-[14rem]" />
                <x-button type="submit" variant="outline" size="sm">Search</x-button>
                @if (! empty($filters['q']) || ! empty($filters['category']))
                    <x-button href="{{ route('blog.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
            </form>

            @if ($categories->isNotEmpty())
                <div class="flex flex-wrap gap-2" role="navigation" aria-label="Blog categories">
                    <a
                        href="{{ route('blog.index', array_filter(['q' => $filters['q'] ?? null])) }}"
                        class="rounded-[var(--radius-control)] border px-3 py-1.5 text-xs font-semibold tracking-wide uppercase {{ empty($filters['category']) ? 'border-brand bg-brand text-white' : 'border-line text-ink-muted hover:border-brand hover:text-brand' }}"
                    >
                        All
                    </a>
                    @foreach ($categories as $category)
                        @if (($category->published_posts_count ?? 0) > 0)
                            <a
                                href="{{ route('blog.index', array_filter(['category' => $category->slug, 'q' => $filters['q'] ?? null])) }}"
                                class="rounded-[var(--radius-control)] border px-3 py-1.5 text-xs font-semibold tracking-wide uppercase {{ ($filters['category'] ?? null) === $category->slug ? 'border-brand bg-brand text-white' : 'border-line text-ink-muted hover:border-brand hover:text-brand' }}"
                            >
                                {{ $category->name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        @if ($posts->isEmpty())
            <div class="mx-auto max-w-xl rounded-[var(--radius-panel)] border border-dashed border-line bg-surface-elevated px-6 py-14 text-center">
                <p class="font-display text-xl font-bold text-ink">No articles yet</p>
                <p class="mt-2 text-sm text-ink-muted">Check back soon, or browse the catalog meanwhile.</p>
                <div class="mt-6">
                    <x-button href="{{ route('shop.index') }}" variant="outline">Browse products</x-button>
                </div>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="group flex flex-col overflow-hidden rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated shadow-soft transition hover:border-brand/35">
                        <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden bg-surface-muted">
                            <img
                                src="{{ $post->featuredImageUrl() }}"
                                alt=""
                                class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                loading="lazy"
                                width="640"
                                height="400"
                            >
                        </a>
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-[0.12em] text-brand uppercase">
                                @if ($post->category)
                                    <span>{{ $post->category->name }}</span>
                                    <span class="text-ink-subtle" aria-hidden="true">·</span>
                                @endif
                                <time datetime="{{ $post->published_at?->toDateString() }}">
                                    {{ $post->published_at?->format('M j, Y') }}
                                </time>
                            </div>
                            <h2 class="font-display text-xl font-bold tracking-tight text-ink">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-brand">{{ $post->title }}</a>
                            </h2>
                            @if ($post->excerpt)
                                <p class="text-sm leading-relaxed text-ink-muted">{{ $post->excerpt }}</p>
                            @endif
                            <div class="mt-auto pt-2">
                                <a href="{{ route('blog.show', $post->slug) }}" class="text-sm font-semibold text-brand hover:underline">
                                    Read article
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </x-container>
@endsection
