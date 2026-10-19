@extends('layouts.storefront')

@section('title', 'Your orders — '.config('app.name'))
@section('canonical', route('account.orders.index'))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-4xl">
            <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Account</p>
            <h1 class="font-display text-3xl font-bold tracking-tight text-ink">Orders</h1>
            <p class="ui-prose-muted mt-2">Track status and review past purchases.</p>

            @include('storefront.account.partials.nav')

            @if ($orders->isEmpty())
                <x-card class="text-center">
                    <p class="text-ink-muted">You haven’t placed an order yet.</p>
                    <div class="mt-4">
                        <x-button href="{{ route('shop.index') }}" variant="primary">Browse the shop</x-button>
                    </div>
                </x-card>
            @else
                <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
                    <table class="min-w-full divide-y divide-line text-left text-sm">
                        <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Order</th>
                                <th class="px-4 py-3 font-semibold">Payment</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Total</th>
                                <th class="px-4 py-3 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ $order->order_number }}</p>
                                        <p class="text-xs text-ink-subtle">{{ optional($order->placed_at)->format('M j, Y g:i A') }} · {{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">
                                        {{ $order->payment_method->label() }}
                                        <span class="block text-xs">{{ $order->payment_status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3"><x-badge variant="brand">{{ $order->status->label() }}</x-badge></td>
                                    <td class="px-4 py-3 font-semibold">{{ $order->totalFormatted() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-button href="{{ route('account.orders.show', $order) }}" variant="ghost" size="sm">Details</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $orders->links() }}</div>
            @endif
        </div>
    </x-container>
@endsection
