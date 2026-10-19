@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)
@section('breadcrumb', 'Orders / '.$order->order_number)

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $order->order_number }}</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Placed {{ optional($order->placed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
            · {{ $order->payment_method->label() }}
        </p>
    </div>
    <x-button href="{{ route('admin.orders.index') }}" variant="outline">Back to orders</x-button>
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
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
                                <p class="text-ink-muted">{{ $item->sku }} · Qty {{ $item->quantity }} · {{ \App\Support\Money::format($item->unit_price_cents) }}</p>
                            </div>
                            <p class="font-semibold text-ink">{{ $item->lineTotalFormatted() }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">Subtotal</dt><dd>{{ \App\Support\Money::format($order->subtotal_cents) }}</dd></div>
                    @if ($order->discount_cents > 0)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt><dd class="text-accent">−{{ \App\Support\Money::format($order->discount_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">Shipping</dt><dd>{{ $order->shipping_cents ? \App\Support\Money::format($order->shipping_cents) : 'Free' }}</dd></div>
                    @if ($order->tax_cents > 0)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Tax</dt><dd>{{ \App\Support\Money::format($order->tax_cents) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3 border-t border-line pt-3 font-semibold"><dt>Total</dt><dd class="font-display text-lg">{{ $order->totalFormatted() }}</dd></div>
                </dl>
            </x-card>

            <div class="grid gap-6 sm:grid-cols-2">
                <x-card class="space-y-2 text-sm">
                    <h2 class="font-display text-lg font-bold text-ink">Ship to</h2>
                    <p class="text-ink">{{ $order->shipping_name }}</p>
                    <p class="text-ink-muted">
                        {{ $order->shipping_line1 }}@if ($order->shipping_line2)<br>{{ $order->shipping_line2 }}@endif<br>
                        {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                        {{ $order->shipping_country }}
                    </p>
                    <p class="text-ink-muted">{{ $order->email }}@if ($order->phone) · {{ $order->phone }}@endif</p>
                </x-card>
                <x-card class="space-y-2 text-sm">
                    <h2 class="font-display text-lg font-bold text-ink">Bill to</h2>
                    <p class="text-ink">{{ $order->billing_name }}</p>
                    <p class="text-ink-muted">
                        {{ $order->billing_line1 }}@if ($order->billing_line2)<br>{{ $order->billing_line2 }}@endif<br>
                        {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_postal_code }}<br>
                        {{ $order->billing_country }}
                    </p>
                </x-card>
            </div>

            @if ($order->notes)
                <x-card class="space-y-2 text-sm">
                    <h2 class="font-display text-lg font-bold text-ink">Customer notes</h2>
                    <p class="text-ink-muted">{{ $order->notes }}</p>
                </x-card>
            @endif

            <x-card class="space-y-3">
                <h2 class="font-display text-lg font-bold text-ink">Status history</h2>
                <ul class="space-y-3 text-sm">
                    @forelse ($order->statusHistories as $history)
                        <li class="rounded-[var(--radius-control)] border border-line/60 px-3 py-2">
                            <p class="font-medium text-ink">
                                {{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}
                            </p>
                            <p class="text-ink-muted">{{ $history->note }}</p>
                            <p class="mt-1 text-xs text-ink-subtle">
                                {{ optional($history->created_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                @if ($history->user) · {{ $history->user->name }}@endif
                            </p>
                        </li>
                    @empty
                        <li class="text-ink-muted">No history yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <x-card class="h-fit space-y-4">
            <h2 class="font-display text-lg font-bold text-ink">Update status</h2>
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-select label="Order status" name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $order->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>

                <x-select label="Payment status" name="payment_status" required>
                    @foreach ($paymentStatuses as $status)
                        <option value="{{ $status->value }}" @selected(old('payment_status', $order->payment_status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>

                <x-select label="Fulfillment" name="fulfillment_status" required>
                    @foreach ($fulfillmentStatuses as $status)
                        <option value="{{ $status->value }}" @selected(old('fulfillment_status', $order->fulfillment_status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>

                <x-textarea label="Internal note" name="note" rows="3" placeholder="Optional note for history">{{ old('note') }}</x-textarea>

                <x-button type="submit" variant="primary" class="w-full">Save changes</x-button>
            </form>
        </x-card>
    </div>
@endsection
