@extends('layouts.storefront')

@section('title', 'Addresses — '.config('app.name'))
@section('canonical', route('account.addresses.index'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-4xl">
            <div class="mb-2 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Account</p>
                    <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Addresses</h1>
                    <p class="ui-prose-muted mt-2">Saved for faster checkout.</p>
                </div>
                <x-button href="{{ route('account.addresses.create') }}" variant="primary">Add address</x-button>
            </div>

            @include('storefront.account.partials.nav')

            @if ($addresses->isEmpty())
                <x-card class="text-center">
                    <p class="text-ink-muted">No saved addresses yet.</p>
                    <div class="mt-4">
                        <x-button href="{{ route('account.addresses.create') }}" variant="primary">Add your first address</x-button>
                    </div>
                </x-card>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($addresses as $address)
                        <x-card class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-ink">{{ $address->name }}</p>
                                    <p class="text-xs text-ink-subtle capitalize">
                                        {{ $address->type }}{{ $address->is_default ? ' · Default' : '' }}
                                    </p>
                                </div>
                                @if ($address->is_default)
                                    <x-badge variant="success">Default</x-badge>
                                @endif
                            </div>
                            <p class="text-ink-muted">
                                {{ $address->line1 }}
                                @if ($address->line2)
                                    <br>{{ $address->line2 }}
                                @endif
                                <br>
                                {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
                                <br>
                                {{ $address->country }}
                                @if ($address->phone)
                                    <br>{{ $address->phone }}
                                @endif
                            </p>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <x-button href="{{ route('account.addresses.edit', $address) }}" variant="outline" size="sm">Edit</x-button>
                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm">Remove</x-button>
                                </form>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </x-container>
@endsection
