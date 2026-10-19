@extends('layouts.storefront')

@section('title', 'Page not found — '.config('app.name'))
@section('meta_description', 'This page could not be found. Return to Supplify to keep shopping.')

@section('content')
    <section class="error-page" aria-labelledby="error-heading">
        <x-container class="py-16 sm:py-20 lg:py-24">
            <div class="error-page__panel mx-auto max-w-2xl text-center">
                <p class="error-page__code" aria-hidden="true">404</p>
                <p class="mb-3 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Page not found</p>
                <h1 id="error-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                    We can’t find that page
                </h1>
                <p class="mx-auto mt-3 max-w-md text-sm text-ink-muted sm:text-base">
                    The link may be broken, or the page may have moved. Head home or browse the catalog to keep going.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <x-button href="{{ route('home') }}" variant="primary" size="lg">
                        Back to homepage
                    </x-button>
                    <x-button href="{{ route('shop.index') }}" variant="outline" size="lg">
                        Shop products
                    </x-button>
                </div>

                <p class="mt-8 text-sm text-ink-subtle">
                    Looking for help?
                    <a href="{{ url('/pages/contact') }}" class="font-semibold text-brand hover:text-brand-hover">Contact us</a>
                </p>
            </div>
        </x-container>
    </section>
@endsection
