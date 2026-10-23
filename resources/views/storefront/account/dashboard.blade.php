@extends('layouts.storefront')

@section('title', 'Your account — '.config('app.name'))
@section('meta_description', 'Manage your Supplify profile, addresses, and orders.')
@section('canonical', route('account.dashboard'))

@section('content')
    <section class="account-hero" aria-labelledby="account-heading">
        <x-container class="py-8 sm:py-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Your account</p>
                    <h1 id="account-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                        Welcome back, {{ explode(' ', trim($user->name))[0] }}
                    </h1>
                    <p class="mt-2 text-sm text-ink-muted sm:text-base">
                        Track orders, keep addresses ready for checkout, and update your profile anytime.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-button href="{{ route('shop.index') }}" variant="primary">Continue shopping</x-button>
                    <x-button href="{{ route('account.orders.index') }}" variant="outline">View all orders</x-button>
                </div>
            </div>
        </x-container>
    </section>

    <x-container class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl">
            @include('storefront.account.partials.nav')

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('account.orders.index') }}" class="account-stat">
                    <span class="account-stat__glow" aria-hidden="true"></span>
                    <span class="account-stat__label">Orders</span>
                    <span class="account-stat__value">{{ $orderCount }}</span>
                    <span class="account-stat__meta">
                        {{ $orderCount === 0 ? 'Place your first order' : 'Review order history' }}
                    </span>
                </a>

                <a href="{{ route('account.addresses.index') }}" class="account-stat">
                    <span class="account-stat__glow" aria-hidden="true"></span>
                    <span class="account-stat__label">Addresses</span>
                    <span class="account-stat__value">{{ $addressCount }}</span>
                    <span class="account-stat__meta">
                        {{ $addressCount === 0 ? 'Add a shipping address' : 'Manage saved addresses' }}
                    </span>
                </a>

                <a href="{{ route('account.profile.edit') }}" class="account-stat">
                    <span class="account-stat__glow" aria-hidden="true"></span>
                    <span class="account-stat__label">Profile</span>
                    <span class="account-stat__value truncate text-[1.15rem] sm:text-xl" data-tooltip="{{ $user->email }}">
                        {{ $user->email }}
                    </span>
                    <span class="account-stat__meta">Edit contact &amp; password</span>
                </a>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                <div>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-bold text-ink">Recent orders</h2>
                        @if ($orderCount > 0)
                            <a href="{{ route('account.orders.index') }}" class="text-sm font-semibold text-brand hover:text-brand-hover">
                                See all
                            </a>
                        @endif
                    </div>

                    @if ($recentOrders->isEmpty())
                        <div class="account-empty">
                            <p class="font-display text-lg font-bold text-ink">No orders yet</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm text-ink-muted">
                                When you place an order, it will show up here with status and totals.
                            </p>
                            <div class="mt-5">
                                <x-button href="{{ route('shop.index') }}" variant="primary">Browse products</x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-[calc(var(--radius-panel)+0.1rem)] border border-line bg-surface-elevated shadow-soft">
                            @foreach ($recentOrders as $order)
                                <article class="account-order">
                                    <div>
                                        <p class="font-semibold text-ink">{{ $order->order_number }}</p>
                                        <p class="mt-0.5 text-xs text-ink-subtle">
                                            {{ optional($order->placed_at)->format('M j, Y') }}
                                            · {{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge variant="brand">{{ $order->status->label() }}</x-badge>
                                        <span class="text-xs text-ink-muted">{{ $order->payment_method->label() }}</span>
                                    </div>
                                    <p class="font-display text-base font-bold text-ink">{{ $order->totalFormatted() }}</p>
                                    <div class="sm:justify-self-end">
                                        <x-button href="{{ route('account.orders.show', $order) }}" variant="outline" size="sm">
                                            Details
                                        </x-button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="space-y-4">
                    <div class="rounded-[calc(var(--radius-panel)+0.1rem)] border border-line bg-surface-elevated p-5 shadow-soft">
                        <h2 class="font-display text-lg font-bold text-ink">Default address</h2>
                        @if ($defaultAddress)
                            <p class="mt-3 text-sm font-medium text-ink">{{ $defaultAddress->name }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                                {{ $defaultAddress->line1 }}@if ($defaultAddress->line2), {{ $defaultAddress->line2 }}@endif<br>
                                {{ $defaultAddress->city }}, {{ $defaultAddress->state }} {{ $defaultAddress->postal_code }}
                            </p>
                            <div class="mt-4">
                                <x-button href="{{ route('account.addresses.edit', $defaultAddress) }}" variant="ghost" size="sm" class="!px-0">
                                    Edit address
                                </x-button>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-ink-muted">
                                Save a shipping address to speed up checkout.
                            </p>
                            <div class="mt-4">
                                <x-button href="{{ route('account.addresses.create') }}" variant="outline" size="sm">
                                    Add address
                                </x-button>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-[calc(var(--radius-panel)+0.1rem)] border border-line/80 bg-brand-mist/50 p-5">
                        <h2 class="font-display text-lg font-bold text-ink">Need help?</h2>
                        <p class="mt-2 text-sm text-ink-muted">
                            Questions about an order or formula? We’re here for product and shipping support.
                        </p>
                        <div class="mt-4">
                            <x-button href="{{ route('pages.show', 'contact') }}" variant="ghost" size="sm" class="!px-0">
                                Contact support
                            </x-button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </x-container>
@endsection
