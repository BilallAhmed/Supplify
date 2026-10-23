@extends('layouts.storefront')

@php
    /** @var \App\Models\Page $page */
    /** @var array<string, mixed> $about */
    $seoTitle = $page->seo_title ?: ($about['hero']['heading'] ?? $page->title);
    $seoDescription = $page->seo_description
        ?: ($about['hero']['intro'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 160));
    $heroImage = \App\Support\AboutPageContent::resolveImage('hero', $about['hero']['image'] ?? null);
@endphp

@section('title', $seoTitle.' — '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $seoDescription), 160))
@section('canonical', route('pages.show', $page->slug))
@section('og_title', $seoTitle)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags((string) $seoDescription), 160))

@push('head')
    @vite(['resources/js/about.js'])
    @if ($heroImage['is_photography'] && $heroImage['src'])
        <meta property="og:image" content="{{ $heroImage['src'] }}">
        <meta name="twitter:image" content="{{ $heroImage['src'] }}">
    @endif
@endpush

@section('content')
    <div class="about-page" data-about-page>
        @include('storefront.pages.about._hero', ['section' => $about['hero'] ?? []])
        @include('storefront.pages.about._purpose', ['section' => $about['purpose'] ?? []])
        @include('storefront.pages.about._principles', ['section' => $about['principles'] ?? []])
        @include('storefront.pages.about._journey', ['section' => $about['journey'] ?? []])
        @include('storefront.pages.about._essentials', ['section' => $about['essentials'] ?? []])
        @include('storefront.pages.about._everyday', ['section' => $about['everyday'] ?? []])
        @include('storefront.pages.about._team', ['section' => $about['team'] ?? []])
    </div>
@endsection
