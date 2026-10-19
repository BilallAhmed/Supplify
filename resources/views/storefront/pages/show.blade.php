@extends('layouts.storefront')

@section('title', ($page->seo_title ?: $page->title).' — '.config('app.name'))
@section('meta_description', $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 160))
@section('canonical', route('pages.show', $page->slug))

@section('content')
    <section class="border-b border-line/60 bg-gradient-to-b from-brand-mist/40 to-surface-muted">
        <x-container class="py-10 sm:py-12">
            <nav class="mb-4 text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-ink" aria-current="page">{{ $page->title }}</li>
                </ol>
            </nav>
            <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $page->title }}</h1>
        </x-container>
    </section>

    <x-container class="py-10 sm:py-12">
        <article class="mx-auto max-w-3xl">
            <div class="whitespace-pre-line text-base leading-relaxed text-ink-muted">{{ $page->body }}</div>
        </article>
    </x-container>
@endsection
