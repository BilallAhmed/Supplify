<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Premium US dietary supplements. Clean formulas, transparent labels, science-led wellness.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/favicons/favicon-48x48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicons/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#054648">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', config('app.name'))))">
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Premium US dietary supplements.')))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <meta name="twitter:card" content="{{ $__env->hasSection('og_image') ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="@yield('og_title', trim($__env->yieldContent('title', config('app.name'))))">
    <meta name="twitter:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Premium US dietary supplements.')))">
    @hasSection('og_image')
        <meta name="twitter:image" content="@yield('og_image')">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|plus-jakarta-sans:600,700,800" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="flex min-h-screen flex-col" x-data>
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-[var(--radius-control)] focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white"
    >
        Skip to content
    </a>

    @include('storefront.partials.header')

    @if (session('flash_success') || session('flash_error') || session('status') || session('error'))
        <div
            class="pointer-events-none fixed inset-x-0 top-16 z-50 flex justify-center lg:top-[4.5rem]"
            role="status"
            aria-live="polite"
        >
            <div class="pointer-events-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                @if (session('flash_success') || session('status'))
                    <x-alert variant="success" :dismissible="true" :autohide="3000" class="block w-full">
                        {{ session('flash_success') ?? session('status') }}
                    </x-alert>
                @endif
                @if (session('flash_error') || session('error'))
                    <div @class(['mt-2' => session('flash_success') || session('status')])>
                        <x-alert variant="danger" :dismissible="true" :autohide="3000" class="block w-full">
                            {{ session('flash_error') ?? session('error') }}
                        </x-alert>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    @include('storefront.partials.footer')
    @include('storefront.partials.quick-view-modal')

    <script>
        window.__wishlistIds = @json($wishlistIds ?? []);
    </script>

    @livewireScripts
    @stack('scripts')
</body>
</html>
