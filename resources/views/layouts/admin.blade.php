<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/favicons/favicon-48x48.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicons/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#054648">

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('adminTheme');
                if (theme === 'dark' || theme === 'light') {
                    document.documentElement.setAttribute('data-admin-theme', theme);
                } else {
                    document.documentElement.setAttribute('data-admin-theme', 'light');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-admin-theme', 'light');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|plus-jakarta-sans:600,700,800" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body
    class="admin-app min-h-screen bg-surface-muted text-ink antialiased"
    x-data="{
        mobileNav: false,
        collapsed: localStorage.getItem('adminSidebarCollapsed') === '1',
        darkMode: localStorage.getItem('adminTheme') === 'dark',
        tip: { show: false, label: '', x: 0, y: 0 },
        init() {
            this.applyTheme();
        },
        applyTheme() {
            document.documentElement.setAttribute('data-admin-theme', this.darkMode ? 'dark' : 'light');
            localStorage.setItem('adminTheme', this.darkMode ? 'dark' : 'light');
        },
        toggleTheme() {
            this.darkMode = ! this.darkMode;
            this.applyTheme();
        },
        toggleCollapsed() {
            this.collapsed = ! this.collapsed;
            localStorage.setItem('adminSidebarCollapsed', this.collapsed ? '1' : '0');
            this.tip.show = false;
        },
        showTip(el, label) {
            if (! this.collapsed || ! window.matchMedia('(min-width: 1024px)').matches) return;
            const r = el.getBoundingClientRect();
            this.tip = { show: true, label, x: r.right + 10, y: r.top + r.height / 2 };
        },
        hideTip() {
            this.tip.show = false;
        },
    }"
    :class="{ 'admin-nav-collapsed': collapsed, 'admin-nav-open': mobileNav }"
>
    <div class="admin-shell">
        @include('admin.partials.sidebar')

        <div class="admin-main">
            @include('admin.partials.topbar')

            @if (session('flash_success') || session('status'))
                <div class="px-4 pt-4 lg:px-6">
                    <x-alert variant="success" :dismissible="true" :autohide="3000">
                        {{ session('flash_success') ?? session('status') }}
                    </x-alert>
                </div>
            @endif

            @if (session('flash_error') || session('error'))
                <div class="px-4 pt-4 lg:px-6">
                    <x-alert variant="danger" :dismissible="true" :autohide="3000">
                        {{ session('flash_error') ?? session('error') }}
                    </x-alert>
                </div>
            @endif

            <main id="admin-main" class="flex-1 px-4 py-6 lg:px-6">
                @hasSection('page_header')
                    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                        @yield('page_header')
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @include('admin.partials.media-library-modal')
    @stack('scripts')

    <div
        x-cloak
        x-show="tip.show"
        class="pointer-events-none fixed z-[100] -translate-y-1/2 rounded-md px-2.5 py-1.5 text-xs font-medium whitespace-nowrap"
        :style="`top:${tip.y}px;left:${tip.x}px;background:#1e2a26;color:#fff;border:1px solid rgba(255,255,255,0.18);box-shadow:0 8px 24px rgba(0,0,0,0.55);opacity:1`"
        x-text="tip.label"
        role="tooltip"
    ></div>
</body>
</html>
