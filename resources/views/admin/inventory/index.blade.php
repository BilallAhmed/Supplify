@extends('layouts.admin')

@section('title', 'Inventory')
@section('breadcrumb', 'Inventory')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Inventory</h1>
        <p class="mt-1 text-sm text-ink-muted">Update stock quantities and low-stock thresholds. Status syncs automatically.</p>
    </div>
@endsection

@section('content')
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search SKU or name…" :value="request('q')" aria-label="Search inventory" />
            </div>
            <div class="admin-toolbar__control">
                <x-select name="filter" aria-label="Stock filter">
                    <option value="">All stock</option>
                    <option value="low" @selected(request('filter') === 'low')>Low stock</option>
                    <option value="out" @selected(request('filter') === 'out')>Out of stock</option>
                </x-select>
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->filled('q') || request()->filled('filter'))
                    <x-button href="{{ route('admin.inventory.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Apply filters</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Status</th>
                        <th>Qty</th>
                        <th>Threshold</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="font-medium text-ink">{{ $product->name }}</td>
                            <td class="font-mono text-xs">{{ $product->sku }}</td>
                            <td>
                                <x-badge :variant="$product->stock_status->badgeVariant()">
                                    {{ $product->stock_status->label() }}
                                </x-badge>
                            </td>
                            <td colspan="3">
                                <form method="POST" action="{{ route('admin.inventory.update', $product) }}" class="admin-inline-controls">
                                    @csrf
                                    @method('PUT')
                                    <div class="admin-inline-controls__qty">
                                        <x-input name="stock_quantity" type="number" min="0" :value="$product->stock_quantity" aria-label="Quantity for {{ $product->sku }}" />
                                    </div>
                                    <div class="admin-inline-controls__qty">
                                        <x-input name="low_stock_threshold" type="number" min="0" :value="$product->low_stock_threshold" aria-label="Threshold for {{ $product->sku }}" />
                                    </div>
                                    <x-button type="submit" variant="primary" size="sm">Update</x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-data-table__empty">No products to manage. Create products first.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="admin-table-panel__pager">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
