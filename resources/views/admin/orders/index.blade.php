@extends('layouts.admin')

@section('title', 'Orders')
@section('breadcrumb', 'Orders')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Orders</h1>
        <p class="mt-1 text-sm text-ink-muted">Review placements, payment, and fulfillment status.</p>
    </div>
@endsection

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search number, email, name…" :value="request('q')" class="max-w-sm" />
        <x-select name="status" class="max-w-xs">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </x-select>
        <x-button type="submit" variant="outline">Filter</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Order</th>
                    <th class="px-4 py-3 font-semibold">Customer</th>
                    <th class="px-4 py-3 font-semibold">Total</th>
                    <th class="px-4 py-3 font-semibold">Payment</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Placed</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">{{ $order->order_number }}</p>
                            <p class="text-xs text-ink-subtle">{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-ink">{{ $order->shipping_name }}</p>
                            <p class="text-xs text-ink-subtle">{{ $order->email }}</p>
                        </td>
                        <td class="px-4 py-3 font-semibold text-ink">{{ $order->totalFormatted() }}</td>
                        <td class="px-4 py-3 text-ink-muted">
                            {{ $order->payment_method->label() }}
                            <span class="block text-xs">{{ $order->payment_status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge variant="brand">{{ $order->status->label() }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ optional($order->placed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-button href="{{ route('admin.orders.show', $order) }}" variant="ghost" size="sm">View</x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-ink-muted">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
