@extends('layouts.storefront')

@section('title', 'Create account — '.config('app.name'))
@section('meta_description', 'Create a '.config('app.name').' customer account.')

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-md">
            <div class="mb-8 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Create account</h1>
                <p class="ui-prose-muted mt-2">Register to track orders and manage your profile.</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <x-input label="Full name" name="name" type="text" autocomplete="name" required :value="old('name')" />
                    <x-input label="Email" name="email" type="email" autocomplete="username" required :value="old('email')" />
                    <x-password-input
                        label="Password"
                        name="password"
                        autocomplete="new-password"
                        :checklist="true"
                        required
                    />
                    <x-password-input
                        label="Confirm password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        match-field="password"
                        required
                    />

                    <x-button type="submit" variant="primary" class="w-full">Create account</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-ink-muted">
                Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-brand hover:text-brand-hover">Sign in</a>
            </p>
        </div>
    </x-container>
@endsection
