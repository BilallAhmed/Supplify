@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — '.config('app.name'))
@section('meta_description', 'Your Supplify order confirmation.')
@section('canonical', route('checkout.confirmation', $order))

@section('content')
    <x-container class="py-12 lg:py-16">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold tracking-wide text-accent uppercase">Order received</p>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Thank you</h1>
            <p class="mt-3 text-ink-muted">
                Your order <span class="font-semibold text-ink">{{ $order->order_number }}</span> is confirmed
                @if ($order->payment_method === \App\Enums\PaymentMethod::Cod)
                    for <span class="font-semibold text-ink">cash on delivery</span>
                @endif.
                A confirmation email is on its way to {{ $order->email }}.
            </p>
        </div>

        <div class="mx-auto mt-10 max-w-2xl rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated p-6 shadow-soft sm:p-8">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-ink-muted">Order number</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Email</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ $order->email }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Status</dt>
                    <dd class="mt-1"><x-badge variant="warning">{{ $order->status->label() }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Payment</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Total</dt>
                    <dd class="mt-1 font-display text-lg font-bold text-ink">{{ $order->totalFormatted() }}</dd>
                </div>
            </dl>

            <h2 class="mt-8 font-display text-lg font-bold text-ink">Items</h2>
            <ul class="mt-3 divide-y divide-line/60 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-3 py-3">
                        <span class="text-ink">
                            {{ $item->name }}
                            @if ($item->variationLabel())
                                <span class="text-ink-subtle">({{ $item->variationLabel() }})</span>
                            @endif
                            <span class="text-ink-muted">× {{ $item->quantity }}</span>
                        </span>
                        <span class="font-semibold text-ink">{{ $item->lineTotalFormatted() }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-4 space-y-2 border-t border-line/60 pt-4 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-muted">Subtotal</dt>
                    <dd>{{ \App\Support\Money::format($order->subtotal_cents) }}</dd>
                </div>
                @if ($order->discount_cents > 0)
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt>
                        <dd class="text-accent">−{{ \App\Support\Money::format($order->discount_cents) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-muted">Shipping</dt>
                    <dd>{{ $order->shipping_cents === 0 ? 'Free' : \App\Support\Money::format($order->shipping_cents) }}</dd>
                </div>
                @if ($order->tax_cents > 0)
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-muted">Tax</dt>
                        <dd>{{ \App\Support\Money::format($order->tax_cents) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3 border-t border-line/60 pt-3 font-semibold">
                    <dt>Total</dt>
                    <dd class="font-display text-lg">{{ $order->totalFormatted() }}</dd>
                </div>
            </dl>

            <div class="mt-6 rounded-[var(--radius-control)] bg-brand-mist/50 px-4 py-3 text-left text-sm text-ink-muted">
                <p class="font-medium text-ink">Ships to</p>
                <p class="mt-1">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_line1 }}@if ($order->shipping_line2), {{ $order->shipping_line2 }}@endif<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button href="{{ route('shop.index') }}" variant="primary">Continue shopping</x-button>
                <x-button href="{{ route('home') }}" variant="outline">Home</x-button>
            </div>
        </div>
    </x-container>
@endsection
