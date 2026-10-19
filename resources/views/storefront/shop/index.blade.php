@extends('layouts.storefront')

@php
    $pageTitle = ($activeCategory?->seo_title ?: $heading).' — '.config('app.name');
    $pageDescription = $activeCategory?->seo_description
        ?: ($activeCategory?->description ?: 'Browse premium dietary supplements from '.config('app.name').'.');
    $canonical = $activeCategory
        ? route('shop.category', $activeCategory->slug)
        : route('shop.index');
    $formAction = $activeCategory
        ? route('shop.category', $activeCategory->slug)
        : route('shop.index');
    $resetUrl = $formAction;
    $inStockOnly = filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $activeSort = $filters['sort'] ?? 'featured';
@endphp

@section('title', $pageTitle)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($pageDescription), 160))
@section('canonical', $canonical)

@section('content')
    <section class="border-b border-line/60 bg-gradient-to-b from-brand-mist/50 to-surface-muted" aria-labelledby="shop-heading">
        <x-container class="py-8 sm:py-10">
            <nav class="mb-3 text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    @if ($activeCategory)
                        <li><a href="{{ route('shop.index') }}" class="hover:text-brand">Shop</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="font-medium text-ink" aria-current="page">{{ $activeCategory->name }}</li>
                    @else
                        <li class="font-medium text-ink" aria-current="page">Shop</li>
                    @endif
                </ol>
            </nav>

            <h1 id="shop-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                {{ $heading }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-ink-muted sm:text-base">{{ $subheading }}</p>
        </x-container>
    </section>

    <x-container
        class="py-8 lg:py-10"
        x-data="{ filtersOpen: false }"
        @keydown.escape.window="filtersOpen = false"
    >
        <div
            class="shop-filters-backdrop"
            :class="filtersOpen && 'is-open'"
            @click="filtersOpen = false"
            aria-hidden="true"
        ></div>

        <div class="shop-layout">
            <aside
                id="shop-filters"
                class="shop-filters"
                :class="filtersOpen && 'is-open'"
                aria-label="Catalog filters"
            >
                <div class="mb-4 flex items-center justify-between gap-3 lg:mb-0">
                    <h2 class="shop-filters__title lg:mb-4">Filters</h2>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[var(--radius-control)] border border-line p-2 text-ink lg:hidden"
                        @click="filtersOpen = false"
                        aria-label="Close filters"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <div class="shop-filters__section">
                    <p class="shop-filters__label">Category</p>
                    <nav class="shop-cat" aria-label="Product categories">
                        <a
                            href="{{ route('shop.index', array_filter(['q' => $filters['q'] ?? null, 'sort' => $activeSort !== 'featured' ? $activeSort : null, 'in_stock' => $inStockOnly ? '1' : null])) }}"
                            class="shop-cat__link {{ $activeCategory ? '' : 'is-active' }}"
                        >
                            <span>All products</span>
                            <span class="shop-cat__count">{{ $categories->sum('published_products_count') }}</span>
                        </a>
                        @foreach ($categories as $category)
                            <a
                                href="{{ route('shop.category', array_filter(['category' => $category->slug, 'q' => $filters['q'] ?? null, 'sort' => $activeSort !== 'featured' ? $activeSort : null, 'in_stock' => $inStockOnly ? '1' : null])) }}"
                                class="shop-cat__link {{ $activeCategory?->is($category) ? 'is-active' : '' }}"
                            >
                                <span>{{ $category->name }}</span>
                                @if (isset($category->published_products_count))
                                    <span class="shop-cat__count">{{ $category->published_products_count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>

                <form method="GET" action="{{ $formAction }}" class="shop-filters__section space-y-4">
                    @if (! empty($filters['q']))
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                    @endif
                    @if ($activeSort !== 'featured')
                        <input type="hidden" name="sort" value="{{ $activeSort }}">
                    @endif
                    @if (! empty($filters['collection']))
                        <input type="hidden" name="collection" value="{{ $filters['collection'] }}">
                    @endif
                    @if (filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN))
                        <input type="hidden" name="on_sale" value="1">
                    @endif

                    <div>
                        <p class="shop-filters__label">Availability</p>
                        <label for="shop-in-stock" class="flex cursor-pointer items-center gap-3 rounded-[var(--radius-control)] px-1 py-1">
                            <input
                                id="shop-in-stock"
                                type="checkbox"
                                name="in_stock"
                                value="1"
                                class="size-4 rounded border-line text-brand focus:ring-brand/30"
                                @checked($inStockOnly)
                                onchange="this.form.submit()"
                            >
                            <span class="text-sm text-ink">In stock only</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <x-button type="submit" variant="primary" size="sm">Apply</x-button>
                        <x-button href="{{ $resetUrl }}" variant="ghost" size="sm">Reset</x-button>
                    </div>
                </form>
            </aside>

            <div>
                <form method="GET" action="{{ $formAction }}" class="shop-toolbar">
                    @if ($inStockOnly)
                        <input type="hidden" name="in_stock" value="1">
                    @endif
                    @if (! empty($filters['collection']))
                        <input type="hidden" name="collection" value="{{ $filters['collection'] }}">
                    @endif
                    @if (filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN))
                        <input type="hidden" name="on_sale" value="1">
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="shop-filters-toggle inline-flex items-center gap-2 rounded-[var(--radius-control)] border border-line bg-surface-elevated px-3 py-2 text-sm font-medium text-ink hover:border-brand hover:text-brand"
                            @click="filtersOpen = true"
                            :aria-expanded="filtersOpen.toString()"
                            aria-controls="shop-filters"
                        >
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.628 1.501c-.46-.046-.893.287-.993.745L.78 6.25a.75.75 0 0 0 .75.75h16.94a.75.75 0 0 0 .75-.75l-.854-4.004a.75.75 0 0 0-.993-.745A48.1 48.1 0 0 0 10 1c-2.476 0-4.908.17-7.372.501ZM1.5 8.5v8.75A1.75 1.75 0 0 0 3.25 19h13.5A1.75 1.75 0 0 0 18.5 17.25V8.5h-17Z" clip-rule="evenodd"/>
                            </svg>
                            Filters
                        </button>
                        <p class="shop-toolbar__meta">
                            <span class="font-medium text-ink">{{ $products->total() }}</span>
                            {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                        </p>
                    </div>

                    <div class="shop-toolbar__controls">
                        <div class="shop-toolbar__search">
                            <label for="shop-q" class="sr-only">Search products</label>
                            <x-input
                                id="shop-q"
                                name="q"
                                type="search"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="Search catalog"
                            />
                        </div>
                        <div class="shop-toolbar__sort">
                            <label for="shop-sort" class="sr-only">Sort products</label>
                            <x-select id="shop-sort" name="sort" onchange="this.form.submit()">
                                @foreach ([
                                    'featured' => 'Featured',
                                    'newest' => 'Newest',
                                    'price_asc' => 'Price: low to high',
                                    'price_desc' => 'Price: high to low',
                                    'name' => 'Name A-Z',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($activeSort === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-button type="submit" variant="outline" size="sm">Search</x-button>
                    </div>
                </form>

                @if ($products->isEmpty())
                    <div class="rounded-[var(--radius-panel)] border border-dashed border-line bg-surface-elevated px-6 py-16 text-center">
                        <p class="font-display text-xl font-bold text-ink">No products found</p>
                        <p class="mt-2 text-sm text-ink-muted">Try clearing filters or browsing another category.</p>
                        <div class="mt-6">
                            <x-button href="{{ route('shop.index') }}" variant="outline">View all products</x-button>
                        </div>
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </x-container>

    @php
        $breadcrumbItems = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
        ];
        if ($activeCategory) {
            $breadcrumbItems[] = ['name' => $activeCategory->name, 'url' => route('shop.category', $activeCategory->slug)];
        }
    @endphp
    @include('storefront.partials.json-ld-breadcrumb', ['items' => $breadcrumbItems])
@endsection
