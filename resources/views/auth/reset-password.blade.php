@extends('layouts.storefront')

@section('title', 'Choose new password — '.config('app.name'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-md">
            <div class="mb-8 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Choose a new password</h1>
            </div>

            <x-card>
                <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <x-input label="Email" name="email" type="email" autocomplete="username" required :value="old('email', $email)" />
                    <x-password-input
                        label="New password"
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

                    <x-button type="submit" variant="primary" class="w-full">Update password</x-button>
                </form>
            </x-card>
        </div>
    </x-container>
@endsection
