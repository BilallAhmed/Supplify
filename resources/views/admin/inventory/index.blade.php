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
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search SKU or name…" :value="request('q')" class="max-w-sm" />
        <x-select name="filter" class="w-44">
            <option value="">All stock</option>
            <option value="low" @selected(request('filter') === 'low')>Low stock</option>
            <option value="out" @selected(request('filter') === 'out')>Out of stock</option>
        </x-select>
        <x-button type="submit" variant="outline">Filter</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Product</th>
                    <th class="px-4 py-3 font-semibold">SKU</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Qty</th>
                    <th class="px-4 py-3 font-semibold">Threshold</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3 font-medium text-ink">{{ $product->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$product->stock_status->badgeVariant()">
                                {{ $product->stock_status->label() }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3" colspan="3">
                            <form method="POST" action="{{ route('admin.inventory.update', $product) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div class="w-24">
                                    <x-input name="stock_quantity" type="number" min="0" :value="$product->stock_quantity" aria-label="Quantity for {{ $product->sku }}" />
                                </div>
                                <div class="w-24">
                                    <x-input name="low_stock_threshold" type="number" min="0" :value="$product->low_stock_threshold" aria-label="Threshold for {{ $product->sku }}" />
                                </div>
                                <x-button type="submit" variant="primary" size="sm">Update</x-button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-muted">No products to manage. Create products first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
