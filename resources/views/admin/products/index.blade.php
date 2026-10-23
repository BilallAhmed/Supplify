@extends('layouts.admin')

@section('title', 'Products')
@section('breadcrumb', 'Products')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Products</h1>
        <p class="mt-1 text-sm text-ink-muted">Manage SKUs, pricing, stock, content, and SEO fields.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <x-button href="{{ route('admin.products.export', request()->only(['q', 'status'])) }}" variant="outline">
            Export CSV
        </x-button>
        <x-button href="{{ route('admin.products.create') }}" variant="primary">Add product</x-button>
    </div>
@endsection

@section('content')
    <div
        x-data="{
            selected: [],
            showImport: false,
            pageIds: @js($products->pluck('id')->map(fn ($id) => (string) $id)->values()->all()),
            get allSelected() {
                return this.pageIds.length > 0 && this.pageIds.every((id) => this.selected.includes(String(id)));
            },
            toggleAll() {
                this.selected = this.allSelected ? [] : [...this.pageIds];
            },
            toggle(id) {
                id = String(id);
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter((x) => x !== id);
                } else {
                    this.selected.push(id);
                }
            },
            confirmDelete() {
                return confirm('Delete ' + this.selected.length + ' selected product(s)? This cannot be undone from the list.');
            },
        }"
    >
        <div class="admin-table-panel mb-4">
            <form method="GET" class="admin-toolbar">
                <div class="admin-toolbar__search">
                    <x-input name="q" type="search" placeholder="Search name or SKU…" :value="request('q')" aria-label="Search products" />
                </div>
                <div class="admin-toolbar__control">
                    <x-select name="status" aria-label="Product status">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="featured" @selected(request('status') === 'featured')>Featured</option>
                        <option value="low_stock" @selected(request('status') === 'low_stock')>Low stock</option>
                        <option value="out_of_stock" @selected(request('status') === 'out_of_stock')>Out of stock</option>
                    </x-select>
                </div>
                <div class="admin-toolbar__actions">
                    @if (request()->filled('q') || request()->filled('status'))
                        <x-button href="{{ route('admin.products.index') }}" variant="ghost" size="sm">Clear</x-button>
                    @endif
                    <x-button type="submit" variant="primary" size="sm">Apply filters</x-button>
                    <x-button type="button" variant="outline" size="sm" @click="showImport = ! showImport" x-text="showImport ? 'Hide import' : 'Import CSV'"></x-button>
                </div>
            </form>
        </div>

        <div
            x-cloak
            x-show="showImport"
            x-transition
            class="mb-4 rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-4 shadow-soft"
        >
            <p class="text-sm font-medium text-ink">Bulk upload via CSV</p>
            <p class="mt-1 text-xs text-ink-muted">
                Upserts by <span class="font-mono">sku</span>. Required columns: sku, name, price.
                Categories and tags use <span class="font-mono">|</span> separators. Export first for a template.
            </p>
            <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="mt-3 admin-inline-controls">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input name="csv" type="file" accept=".csv,text/csv" required aria-label="CSV file" />
                </div>
                <x-button type="submit" variant="secondary">Upload &amp; import</x-button>
            </form>
        </div>

        <div
            x-cloak
            x-show="selected.length > 0"
            x-transition
            class="mb-3 flex flex-wrap items-center gap-2 rounded-[var(--radius-panel)] border border-brand/30 bg-brand-mist px-3 py-2 text-sm text-ink"
        >
            <span class="font-medium" x-text="selected.length + ' selected'"></span>
            <span class="text-ink-subtle">·</span>
            <form method="POST" action="{{ route('admin.products.bulk') }}" class="flex flex-wrap items-center gap-1">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                @foreach (request()->only(['q', 'status']) as $key => $value)
                    @if (filled($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <x-button type="submit" name="action" value="activate" variant="outline" size="sm">Publish</x-button>
                <x-button type="submit" name="action" value="deactivate" variant="outline" size="sm">Draft</x-button>
                <x-button type="submit" name="action" value="feature" variant="outline" size="sm">Feature</x-button>
                <x-button type="submit" name="action" value="unfeature" variant="outline" size="sm">Unfeature</x-button>
                <x-button type="submit" name="action" value="delete" variant="danger" size="sm" @click="if (! confirmDelete()) $event.preventDefault()">Delete</x-button>
            </form>
            <button type="button" class="ml-auto text-xs font-medium text-ink-muted hover:text-ink" @click="selected = []">Clear</button>
        </div>

        <div class="admin-table-panel">
            <div class="admin-table-wrap">
                <table class="admin-data-table" style="min-width: 52rem">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-line text-brand focus:ring-brand"
                                    :checked="allSelected"
                                    @change="toggleAll()"
                                    aria-label="Select all on page"
                                >
                            </th>
                            <th class="w-10 text-center">
                                <button type="button" class="mx-auto inline-flex items-center justify-center text-ink-subtle hover:text-brand" aria-label="Featured products" data-tooltip="Click a star to feature or unfeature on the homepage. Filled means featured.">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.27 5.82.85-4.21 4.1 1 5.78L12 16.77l-5.21 2.73 1-5.78-4.21-4.1 5.82-.85L9.4 8.77 12 3.5z"/></svg>
                                </button>
                            </th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php $primary = $product->images->first(); @endphp
                            <tr :class="selected.includes('{{ $product->id }}') ? 'bg-brand-mist/40' : ''">
                                <td>
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border-line text-brand focus:ring-brand"
                                        value="{{ $product->id }}"
                                        :checked="selected.includes('{{ $product->id }}')"
                                        @change="toggle('{{ $product->id }}')"
                                        aria-label="Select {{ $product->name }}"
                                    >
                                </td>
                                <td class="text-center align-middle">
                                    <form method="POST" action="{{ route('admin.products.featured', $product) }}" class="inline-flex">
                                        @csrf
                                        @foreach (request()->only(['q', 'status', 'page']) as $key => $value)
                                            @if (filled($value))
                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                            @endif
                                        @endforeach
                                        <button
                                            type="submit"
                                            class="inline-flex size-7 items-center justify-center rounded-full transition {{ $product->is_featured ? 'text-amber-400 hover:bg-amber-400/10' : 'text-ink-subtle/70 hover:bg-surface-muted hover:text-amber-400' }}"
                                            data-tooltip="{{ $product->is_featured ? 'Remove from homepage featured' : 'Feature on homepage' }}"
                                            aria-label="{{ $product->is_featured ? 'Unfeature '.$product->name : 'Feature '.$product->name }}"
                                            aria-pressed="{{ $product->is_featured ? 'true' : 'false' }}"
                                        >
                                            @if ($product->is_featured)
                                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M12 3.5l2.6 5.27 5.82.85-4.21 4.1 1 5.78L12 16.77l-5.21 2.73 1-5.78-4.21-4.1 5.82-.85L12 3.5z"/>
                                                </svg>
                                            @else
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                                    <path d="M12 3.5l2.6 5.27 5.82.85-4.21 4.1 1 5.78L12 16.77l-5.21 2.73 1-5.78-4.21-4.1 5.82-.85L12 3.5z" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="size-12 shrink-0 overflow-hidden rounded-[var(--radius-control)] bg-surface-muted">
                                            @if ($primary)
                                                <img src="{{ $primary->url() }}" alt="" class="size-12 object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink">{{ $product->name }}</p>
                                            <p class="text-xs text-ink-subtle">{{ $product->categories->pluck('name')->join(', ') ?: 'Uncategorized' }}</p>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @if ($product->hasTemporaryPricing())
                                                    <x-badge variant="warning">Temporary pricing</x-badge>
                                                @endif
                                                @if ($product->hasEditorialDraft())
                                                    <x-badge variant="neutral">Editorial draft</x-badge>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono text-xs">{{ $product->sku }}</td>
                                <td>
                                    @if ($product->sale_price_cents)
                                        <span class="font-medium">{{ \App\Support\Money::format($product->sale_price_cents) }}</span>
                                        <span class="ml-1 text-xs text-ink-subtle line-through">{{ $product->priceFormatted() }}</span>
                                    @else
                                        {{ $product->priceFormatted() }}
                                    @endif
                                    @if ($product->hasTemporaryPricing())
                                        <p class="mt-0.5 text-[11px] font-medium text-warning">Preview only — not approved pricing</p>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :variant="$product->stock_status->badgeVariant()">
                                        {{ $product->stock_quantity }} · {{ $product->stock_status->label() }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="$product->is_active ? 'brand' : 'neutral'">
                                        {{ $product->is_active ? 'Published' : 'Draft' }}
                                    </x-badge>
                                </td>
                                <td>
                                    <div class="admin-row-actions">
                                        <x-button href="{{ route('admin.products.edit', $product) }}" variant="ghost" size="sm">Edit</x-button>
                                        <form method="POST" action="{{ route('admin.products.duplicate', $product) }}">
                                            @csrf
                                            <x-button type="submit" variant="outline" size="sm">Duplicate</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="admin-data-table__empty">No products yet. Add your first SKU or import a CSV.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="admin-table-panel__pager">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
@endsection
