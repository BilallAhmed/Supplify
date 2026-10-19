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
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
            <div class="min-w-[12rem] flex-1">
                <x-input name="q" type="search" placeholder="Search name or SKU…" :value="request('q')" />
            </div>
            <div class="w-44">
                <x-select name="status" label="Status">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Published</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="low_stock" @selected(request('status') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(request('status') === 'out_of_stock')>Out of stock</option>
                </x-select>
            </div>
            <x-button type="submit" variant="outline">Filter</x-button>
            <x-button type="button" variant="ghost" @click="showImport = ! showImport" x-text="showImport ? 'Hide import' : 'Import CSV'"></x-button>
        </form>

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
            <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <div class="min-w-[16rem] flex-1">
                    <x-input name="csv" type="file" accept=".csv,text/csv" required />
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

        <div class="overflow-x-auto rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
            <table class="min-w-full divide-y divide-line text-left text-sm">
                <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="w-10 px-3 py-3">
                            <input
                                type="checkbox"
                                class="size-4 rounded border-line text-brand focus:ring-brand"
                                :checked="allSelected"
                                @change="toggleAll()"
                                aria-label="Select all on page"
                            >
                        </th>
                        <th
                            class="relative w-10 px-1 py-3 text-center"
                            x-data="{ tip: false }"
                            @mouseenter="tip = true"
                            @mouseleave="tip = false"
                            @focusin="tip = true"
                            @focusout="tip = false"
                        >
                            <button
                                type="button"
                                class="mx-auto inline-flex items-center justify-center text-ink-subtle hover:text-amber-400"
                                aria-describedby="featured-col-tip"
                            >
                                <span class="sr-only">Featured</span>
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3.5l2.6 5.27 5.82.85-4.21 4.1 1 5.78L12 16.77l-5.21 2.73 1-5.78-4.21-4.1 5.82-.85L12 3.5z"/>
                                </svg>
                            </button>
                            <div
                                id="featured-col-tip"
                                x-cloak
                                x-show="tip"
                                x-transition.opacity.duration.100ms
                                role="tooltip"
                                class="pointer-events-none absolute top-full left-0 z-20 mt-1 w-48 rounded-md border border-white/15 bg-[#1e2a26] px-2.5 py-2 text-left text-[11px] font-medium normal-case tracking-normal text-white shadow-lg"
                            >
                                Click a star to feature or unfeature on the homepage. Filled = featured.
                            </div>
                        </th>
                        <th class="px-4 py-3 font-semibold">Product</th>
                        <th class="px-4 py-3 font-semibold">SKU</th>
                        <th class="px-4 py-3 font-semibold">Price</th>
                        <th class="px-4 py-3 font-semibold">Stock</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($products as $product)
                        @php $primary = $product->images->first(); @endphp
                        <tr :class="selected.includes('{{ $product->id }}') ? 'bg-brand-mist/40' : ''">
                            <td class="px-3 py-3">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-line text-brand focus:ring-brand"
                                    value="{{ $product->id }}"
                                    :checked="selected.includes('{{ $product->id }}')"
                                    @change="toggle('{{ $product->id }}')"
                                    aria-label="Select {{ $product->name }}"
                                >
                            </td>
                            <td class="px-1 py-3 text-center align-middle">
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
                                        title="{{ $product->is_featured ? 'Remove from homepage featured' : 'Feature on homepage' }}"
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
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="size-12 overflow-hidden rounded-[var(--radius-control)] bg-surface-muted">
                                        @if ($primary)
                                            <img src="{{ $primary->url() }}" alt="" class="size-12 object-cover">
                                        @endif
                                    </div>
                                    <div>
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
                            <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                            <td class="px-4 py-3">
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
                            <td class="px-4 py-3">
                                <x-badge :variant="$product->stock_status->badgeVariant()">
                                    {{ $product->stock_quantity }} · {{ $product->stock_status->label() }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$product->is_active ? 'brand' : 'neutral'">
                                    {{ $product->is_active ? 'Published' : 'Draft' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-1">
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
                            <td colspan="8" class="px-4 py-10 text-center text-ink-muted">No products yet. Add your first SKU or import a CSV.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    </div>
@endsection
