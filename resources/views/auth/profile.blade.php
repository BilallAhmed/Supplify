@extends('layouts.storefront')

@section('title', 'Your profile — '.config('app.name'))
@section('canonical', route('account.profile.edit'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-2xl">
            <div class="mb-2 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Account</p>
                    <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Profile</h1>
                    <p class="ui-prose-muted mt-2">Update your contact details and password.</p>
                </div>
                <x-badge variant="brand">{{ $user->role->label() }}</x-badge>
            </div>

            @include('storefront.account.partials.nav')

            <x-card>
                <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <x-input label="Full name" name="name" type="text" autocomplete="name" required :value="old('name', $user->name)" />
                    <x-input label="Email" name="email" type="email" autocomplete="username" required :value="old('email', $user->email)" />
                    <x-input label="Phone" name="phone" type="tel" autocomplete="tel" :value="old('phone', $user->phone)" help="Optional — used for order updates." />

                    <div class="border-t border-line pt-4">
                        <h2 class="font-display text-lg font-bold text-ink">Change password</h2>
                        <p class="ui-help !mt-1 mb-4">Leave blank to keep your current password.</p>

                        <div class="space-y-4">
                            <x-input label="Current password" name="current_password" type="password" autocomplete="current-password" />
                            <x-input label="New password" name="password" type="password" autocomplete="new-password" />
                            <x-input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-button type="submit" variant="primary">Save changes</x-button>
                        @if ($user->isAdmin())
                            <x-button href="{{ route('admin.dashboard') }}" variant="outline">Admin dashboard</x-button>
                        @endif
                    </div>
                </form>
            </x-card>
        </div>
    </x-container>
@endsection
