@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — '.config('app.name'))
@section('canonical', route('account.orders.show', $order))

@section('content')
    <x-container class="ui-section">
        <div class="mx-auto max-w-3xl">
            <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Account</p>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">{{ $order->order_number }}</h1>
                <x-badge variant="brand">{{ $order->status->label() }}</x-badge>
            </div>
            <p class="ui-prose-muted">
                Placed {{ optional($order->placed_at)->format('M j, Y g:i A') }}
                · {{ $order->payment_method->label() }}
                · Payment {{ strtolower($order->payment_status->label()) }}
            </p>

            @include('storefront.account.partials.nav')

            <x-card class="space-y-4">
                <h2 class="font-display text-lg font-bold text-ink">Items</h2>
                <ul class="divide-y divide-line text-sm">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-ink">{{ $item->name }}</p>
                                @if ($item->variationLabel())
                                    <p class="text-xs text-ink-subtle">{{ $item->variationLabel() }}</p>
                                @endif
                                <p class="text-ink-muted">Qty {{ $item->quantity }} · {{ \App\Support\Money::format($item->unit_price_cents) }}@if ($item->sku) · {{ $item->sku }}@endif</p>
                            </div>
                            <p class="font-semibold">{{ $item->lineTotalFormatted() }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">Subtotal</dt><dd>{{ \App\Support\Money::format($order->subtotal_cents) }}</dd></div>
                    @if ($order->discount_cents > 0)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Discount</dt><dd class="text-accent">−{{ \App\Support\Money::format($order->discount_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">Shipping</dt><dd>{{ $order->shipping_cents ? \App\Support\Money::format($order->shipping_cents) : 'Free' }}</dd></div>
                    @if ($order->tax_cents > 0)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Tax</dt><dd>{{ \App\Support\Money::format($order->tax_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3 border-t border-line pt-3 font-semibold"><dt>Total</dt><dd class="font-display text-lg">{{ $order->totalFormatted() }}</dd></div>
                </dl>
            </x-card>

            <x-card class="mt-6 space-y-2 text-sm">
                <h2 class="font-display text-lg font-bold text-ink">Shipping address</h2>
                <p class="text-ink">{{ $order->shipping_name }}</p>
                <p class="text-ink-muted">
                    {{ $order->shipping_line1 }}@if ($order->shipping_line2), {{ $order->shipping_line2 }}@endif<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </p>
            </x-card>

            <div class="mt-6">
                <x-button href="{{ route('account.orders.index') }}" variant="outline">Back to orders</x-button>
            </div>
        </div>
    </x-container>
@endsection
