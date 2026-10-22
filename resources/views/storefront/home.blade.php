@extends('layouts.storefront')

@php
    $hero = $sections->get('hero');
    $firstSlide = ($heroSlides ?? collect())->first();
@endphp

@section('title', ($firstSlide['title'] ?? $hero?->title ?: config('app.name')).' — Science-led supplements')
@section('meta_description', $firstSlide['body'] ?? $hero?->body ?: 'Science-led dietary supplements with clear labels and transparent formulas for everyday wellness.')

@push('head')
    @vite(['resources/js/home.js'])
@endpush

@section('content')
    <div class="home-shell">
        @foreach ($sections as $key => $section)
            @includeIf('storefront.home.sections.'.$key, [
                'section' => $section,
                'heroSlides' => $heroSlides ?? collect(),
                'featuredProducts' => $featuredProducts ?? collect(),
                'dailyPicks' => $dailyPicks ?? collect(),
                'goalCategories' => $goalCategories ?? collect(),
                'educationPosts' => $educationPosts ?? null,
            ])


        @endforeach
    </div>
@endsection

