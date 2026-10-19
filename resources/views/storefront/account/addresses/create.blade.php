@extends('layouts.storefront')

@section('title', 'Add address — '.config('app.name'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-2xl">
            <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Account</p>
            <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Add address</h1>

            @include('storefront.account.partials.nav')

            <x-card>
                @include('storefront.account.addresses._form')
            </x-card>
        </div>
    </x-container>
@endsection
