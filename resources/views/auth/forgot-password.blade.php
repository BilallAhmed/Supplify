@extends('layouts.storefront')

@section('title', 'Forgot password — '.config('app.name'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-md">
            <div class="mb-8 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Reset password</h1>
                <p class="ui-prose-muted mt-2">Enter your email and we will send a reset link if an account exists.</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf
                    <x-input label="Email" name="email" type="email" autocomplete="username" required :value="old('email')" />
                    <x-button type="submit" variant="primary" class="w-full">Send reset link</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-ink-muted">
                <a href="{{ route('login') }}" class="font-medium text-brand hover:text-brand-hover">Back to sign in</a>
            </p>
        </div>
    </x-container>
@endsection
