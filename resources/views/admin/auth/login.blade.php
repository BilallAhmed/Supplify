<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>Admin sign in — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|plus-jakarta-sans:600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-muted text-ink">
    <main class="flex min-h-screen items-center justify-center px-5 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mx-auto mb-8 block w-40"><img src="{{ asset('images/supplify-logo-light.webp') }}" alt="Supplify" width="160" height="36"></a>
            <x-card>
                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">Store administration</p>
                <h1 class="font-display text-2xl font-bold text-ink">Admin sign in</h1>
                <p class="mb-6 mt-2 text-sm text-ink-muted">Sign in with your administrator account to manage Supplify.</p>
                @if(session('flash_success'))<x-alert variant="success" class="mb-4">{{ session('flash_success') }}</x-alert>@endif
                <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                    @csrf
                    <x-input name="email" label="Admin email" type="email" autocomplete="username" :value="old('email')" required />
                    <x-password-input name="password" id="admin-password" label="Password" autocomplete="current-password" required />
                    <div class="flex items-center justify-between gap-3"><x-checkbox name="remember" label="Remember me" value="1" class="min-w-0 flex-1" /><a href="{{ route('password.request') }}" class="shrink-0 whitespace-nowrap text-xs font-medium text-brand">Forgot password?</a></div>
                    <x-button type="submit" variant="primary" class="w-full">Sign in to admin</x-button>
                </form>
            </x-card>
            <p class="mt-6 text-center text-sm text-ink-muted">Shopping with us? <a class="font-semibold text-brand" href="{{ route('login') }}">Customer sign in</a></p>
        </div>
    </main>
    @livewireScripts
</body>
</html>
