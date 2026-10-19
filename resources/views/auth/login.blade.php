@extends('layouts.storefront')

@section('title', 'Sign in — '.config('app.name'))
@section('meta_description', 'Sign in to your '.config('app.name').' account.')

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-md">
            <div class="mb-8 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Sign in</h1>
                <p class="ui-prose-muted mt-2">Access your orders and account details.</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <x-input
                        label="Email"
                        name="email"
                        type="email"
                        autocomplete="username"
                        required
                        :value="old('email')"
                    />

                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <x-label for="password" value="Password" class="!mb-0" />
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand hover:text-brand-hover">
                                Forgot password?
                            </a>
                        </div>
                        <x-password-input
                            name="password"
                            id="password"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <x-checkbox name="remember" value="1" label="Remember me" />

                    <x-button type="submit" variant="primary" class="w-full">Sign in</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-ink-muted">
                New here?
                <a href="{{ route('register') }}" class="font-medium text-brand hover:text-brand-hover">Create an account</a>
            </p>
        </div>
    </x-container>
@endsection
