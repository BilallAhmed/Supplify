@extends('layouts.storefront')

@php
    $metaDescription = $post->seo_description
        ?: ($post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 160));
    $ogImage = $post->featuredImageUrl();
@endphp

@section('title', ($post->seo_title ?: $post->title).' — '.config('app.name'))
@section('meta_description', $metaDescription)
@section('canonical', route('blog.show', $post->slug))
@section('og_type', 'article')
@section('og_image', $ogImage)

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $metaDescription,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?: config('app.name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
            ],
            'mainEntityOfPage' => route('blog.show', $post->slug),
            'image' => $ogImage ? [$ogImage] : [],
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP) !!}
    </script>
@endpush

@section('content')
    <article>
        <header class="border-b border-line/60 bg-gradient-to-b from-brand-mist/40 to-surface-muted">
            <x-container class="py-10 sm:py-12">
                <nav class="mb-4 text-sm text-ink-muted" aria-label="Breadcrumb">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('blog.index') }}" class="hover:text-brand">Blogs</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="font-medium text-ink" aria-current="page">{{ $post->title }}</li>
                    </ol>
                </nav>

                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs font-semibold tracking-[0.14em] text-brand uppercase">
                    @if ($post->category)
                        <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="hover:underline">
                            {{ $post->category->name }}
                        </a>
                        <span class="text-ink-subtle" aria-hidden="true">·</span>
                    @endif
                    <time datetime="{{ $post->published_at?->toDateString() }}">
                        {{ $post->published_at?->format('F j, Y') }}
                    </time>
                    @if ($post->author)
                        <span class="text-ink-subtle" aria-hidden="true">·</span>
                        <span class="normal-case tracking-normal text-ink-muted">{{ $post->author->name }}</span>
                    @endif
                </div>

                <h1 class="font-display max-w-3xl text-3xl font-bold tracking-tight text-ink sm:text-4xl lg:text-5xl">
                    {{ $post->title }}
                </h1>

                @if ($post->excerpt)
                    <p class="mt-4 max-w-2xl text-base text-ink-muted sm:text-lg">{{ $post->excerpt }}</p>
                @endif
            </x-container>
        </header>

        <x-container class="pt-8 sm:pt-10">
            <figure class="overflow-hidden rounded-[var(--radius-panel)] border border-line/60 bg-surface-muted shadow-soft">
                <img
                    src="{{ $post->featuredImageUrl() }}"
                    alt=""
                    class="aspect-[21/9] w-full object-cover"
                    width="1200"
                    height="514"
                >
            </figure>
        </x-container>

        <x-container class="py-10 sm:py-12">
            <div class="mx-auto max-w-3xl">
                <div class="whitespace-pre-line text-base leading-relaxed text-ink-muted sm:text-[1.05rem]">
                    {{ $post->body }}
                </div>

                @if ($post->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap gap-2 border-t border-line/60 pt-6">
                        @foreach ($post->tags as $tag)
                            <a
                                href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                                class="rounded-[var(--radius-control)] border border-line px-3 py-1 text-xs font-semibold tracking-wide text-ink-muted uppercase hover:border-brand hover:text-brand"
                            >
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-container>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-line/50 bg-surface-muted/50 py-12" aria-labelledby="related-posts-heading">
            <x-container>
                <h2 id="related-posts-heading" class="font-display text-2xl font-bold tracking-tight text-ink">
                    Related insights
                </h2>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <a
                            href="{{ route('blog.show', $item->slug) }}"
                            class="overflow-hidden rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated shadow-soft transition hover:border-brand/40"
                        >
                            <img
                                src="{{ $item->featuredImageUrl() }}"
                                alt=""
                                class="aspect-[16/10] w-full object-cover"
                                loading="lazy"
                                width="480"
                                height="300"
                            >
                            <span class="block p-5">
                                @if ($item->category)
                                    <p class="mb-2 text-xs font-semibold tracking-[0.12em] text-brand uppercase">{{ $item->category->name }}</p>
                                @endif
                                <p class="font-display text-lg font-bold text-ink">{{ $item->title }}</p>
                                @if ($item->excerpt)
                                    <p class="mt-2 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit($item->excerpt, 100) }}</p>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif
@endsection
