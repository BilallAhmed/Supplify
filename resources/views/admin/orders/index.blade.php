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
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search number, email, name…" :value="request('q')" aria-label="Search orders" />
            </div>
            <div class="admin-toolbar__control">
                <x-select name="status" aria-label="Order status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->filled('q') || request()->filled('status'))
                    <x-button href="{{ route('admin.orders.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Apply filters</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $order->order_number }}</p>
                                <p class="text-xs text-ink-subtle">{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</p>
                            </td>
                            <td>
                                <p class="text-ink">{{ $order->shipping_name }}</p>
                                <p class="text-xs text-ink-subtle">{{ $order->email }}</p>
                            </td>
                            <td class="font-semibold text-ink">{{ $order->totalFormatted() }}</td>
                            <td class="text-ink-muted">
                                {{ $order->payment_method->label() }}
                                <span class="block text-xs">{{ $order->payment_status->label() }}</span>
                            </td>
                            <td>
                                <x-badge variant="brand">{{ $order->status->label() }}</x-badge>
                            </td>
                            <td class="text-ink-muted whitespace-nowrap">
                                {{ optional($order->placed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.orders.show', $order) }}" variant="outline" size="sm">View</x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-data-table__empty">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="admin-table-panel__pager">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
