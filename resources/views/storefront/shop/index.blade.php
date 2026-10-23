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
    $inStockOnly = filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $onSaleOnly = filter_var($filters['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $activeSort = $filters['sort'] ?? 'featured';
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    $collection = trim((string) ($filters['collection'] ?? ''));
    $publishedProductCount = $publishedProductCount ?? 0;
    $priceRange = $priceRange ?? [
        'min_price' => null,
        'max_price' => null,
        'min_cents' => null,
        'max_cents' => null,
        'error' => null,
    ];
    $minPriceInput = $priceRange['min_price'] ?? null;
    $maxPriceInput = $priceRange['max_price'] ?? null;
    $priceError = $priceRange['error'] ?? null;
    $hasValidPriceFilter = $priceError === null
        && (($priceRange['min_cents'] ?? null) !== null || ($priceRange['max_cents'] ?? null) !== null);

    $shopParams = function (array $overrides = []) use ($activeSort, $inStockOnly, $onSaleOnly, $searchQuery, $collection, $minPriceInput, $maxPriceInput) {
        $params = [
            'q' => $searchQuery !== '' ? $searchQuery : null,
            'sort' => $activeSort !== 'featured' ? $activeSort : null,
            'in_stock' => $inStockOnly ? '1' : null,
            'on_sale' => $onSaleOnly ? '1' : null,
            'collection' => $collection !== '' ? $collection : null,
            'min_price' => $minPriceInput,
            'max_price' => $maxPriceInput,
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return array_filter(
            $params,
            static fn ($value) => $value !== null && $value !== ''
        );
    };

    $shopUrl = function (?\App\Models\Category $category = null, array $overrides = []) use ($shopParams) {
        $params = $shopParams($overrides);

        return $category
            ? route('shop.category', array_merge(['category' => $category->slug], $params))
            : route('shop.index', $params);
    };

    $clearAllUrl = route('shop.index');
    $collectionLabels = [
        'best-sellers' => 'Best sellers',
        'trending' => 'Trending',
        'offers' => 'Offers',
    ];

    $activeChips = [];
    if ($activeCategory) {
        $activeChips[] = [
            'label' => $activeCategory->name,
            'removeUrl' => $shopUrl(null),
        ];
    }
    if ($searchQuery !== '') {
        $activeChips[] = [
            'label' => 'Search: '.$searchQuery,
            'removeUrl' => $shopUrl($activeCategory, ['q' => null]),
        ];
    }
    if ($inStockOnly) {
        $activeChips[] = [
            'label' => 'In stock',
            'removeUrl' => $shopUrl($activeCategory, ['in_stock' => null]),
        ];
    }
    if ($onSaleOnly) {
        $activeChips[] = [
            'label' => 'On sale',
            'removeUrl' => $shopUrl($activeCategory, ['on_sale' => null]),
        ];
    }
    if ($collection !== '') {
        $activeChips[] = [
            'label' => $collectionLabels[$collection] ?? ucfirst(str_replace('-', ' ', $collection)),
            'removeUrl' => $shopUrl($activeCategory, ['collection' => null]),
        ];
    }
    if ($hasValidPriceFilter || ($priceError && ($minPriceInput || $maxPriceInput))) {
        $chipLabel = match (true) {
            $minPriceInput && $maxPriceInput => 'PKR '.$minPriceInput.' – '.$maxPriceInput,
            $minPriceInput => 'PKR '.$minPriceInput.'+',
            default => 'Up to PKR '.$maxPriceInput,
        };
        $activeChips[] = [
            'label' => $chipLabel,
            'removeUrl' => $shopUrl($activeCategory, ['min_price' => null, 'max_price' => null]),
        ];
    }

    $activeFilterCount = count($activeChips);
    $hasActiveFilters = $activeFilterCount > 0;
    $resultsLabel = $products->total() === 0
        ? '0 products'
        : ($products->firstItem() === $products->lastItem()
            ? $products->firstItem().' of '.$products->total().' '.\Illuminate\Support\Str::plural('product', $products->total())
            : $products->firstItem().'–'.$products->lastItem().' of '.$products->total().' products');
@endphp

@section('title', $pageTitle)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($pageDescription), 160))
@section('canonical', $canonical)

@section('content')
    <x-page-image-banner
        variant="catalog"
        image="images/catalog/catalog-hero.webp"
        image-alt="Supplify supplements displayed on natural stone stands"
        :eyebrow="$eyebrow ?? null"
        :title="$heading"
        :description="$subheading"
        heading-id="shop-heading"
        :current-label="$activeCategory?->name ?? 'All Products'"
        :category="$activeCategory"
    />

    <x-container
        class="shop-catalog py-8 lg:py-10"
        x-data="shopCatalogFilters()"
        @keydown.escape.window="closeFilters()"
    >
        <div
            class="shop-filters-backdrop"
            :class="open && 'is-open'"
            @click="closeFilters()"
            x-cloak
            aria-hidden="true"
        ></div>

        <div class="shop-layout">
            <aside
                id="shop-filters"
                class="shop-filters"
                :class="open && 'is-open'"
                x-ref="drawer"
                aria-label="Catalog filters"
                @keydown="trapFocus($event)"
            >
                <div class="shop-filters__header">
                    <div class="shop-filters__heading-row">
                        <h2 class="shop-filters__title" id="shop-filters-title">Filters</h2>
                        @if ($hasActiveFilters)
                            <a href="{{ $clearAllUrl }}" class="shop-filters__clear">Clear all</a>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="shop-filters__close"
                        @click="closeFilters()"
                        x-ref="closeBtn"
                        aria-label="Close filters"
                    >
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <div class="shop-filters__body">
                    <div class="shop-filters__section" x-data="{ expanded: true }">
                        <button
                            type="button"
                            class="shop-filters__group-toggle"
                            @click="expanded = !expanded"
                            :aria-expanded="expanded.toString()"
                            aria-controls="shop-filter-categories"
                        >
                            <span class="shop-filters__label">Category</span>
                            <svg class="shop-filters__chevron" :class="expanded && 'is-open'" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <nav
                            id="shop-filter-categories"
                            class="shop-cat"
                            aria-label="Product categories"
                            x-show="expanded"
                        >
                            <a
                                href="{{ $shopUrl(null) }}"
                                class="shop-cat__link {{ $activeCategory ? '' : 'is-active' }}"
                                @if (! $activeCategory) aria-current="page" @endif
                            >
                                <span class="shop-cat__name">All products</span>
                                <span class="shop-cat__count">{{ $publishedProductCount }}</span>
                            </a>
                            @foreach ($categories as $category)
                                <a
                                    href="{{ $shopUrl($category) }}"
                                    class="shop-cat__link {{ $activeCategory?->is($category) ? 'is-active' : '' }}"
                                    @if ($activeCategory?->is($category)) aria-current="page" @endif
                                >
                                    <span class="shop-cat__name">{{ $category->name }}</span>
                                    @if (isset($category->published_products_count))
                                        <span class="shop-cat__count">{{ $category->published_products_count }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Instant-apply availability: toggling submits immediately (no separate Apply). --}}
                    <div class="shop-filters__section" x-data="{ expanded: true }">
                        <button
                            type="button"
                            class="shop-filters__group-toggle"
                            @click="expanded = !expanded"
                            :aria-expanded="expanded.toString()"
                            aria-controls="shop-filter-availability"
                        >
                            <span class="shop-filters__label">Availability</span>
                            <svg class="shop-filters__chevron" :class="expanded && 'is-open'" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div id="shop-filter-availability" x-show="expanded">
                            <form method="GET" action="{{ $formAction }}" class="shop-avail-form">
                                @foreach ($shopParams(['in_stock' => null]) as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                <label class="shop-check" for="shop-in-stock">
                                    <input
                                        id="shop-in-stock"
                                        class="shop-check__input"
                                        type="checkbox"
                                        name="in_stock"
                                        value="1"
                                        @checked($inStockOnly)
                                        onchange="this.form.submit()"
                                    >
                                    <span class="shop-check__mark" aria-hidden="true"></span>
                                    <span class="shop-check__label">In stock only</span>
                                </label>
                            </form>
                        </div>
                    </div>

                    <div class="shop-filters__section" x-data="{ expanded: true }">
                        <button
                            type="button"
                            class="shop-filters__group-toggle"
                            @click="expanded = !expanded"
                            :aria-expanded="expanded.toString()"
                            aria-controls="shop-filter-price"
                        >
                            <span class="shop-filters__label">Price</span>
                            <svg class="shop-filters__chevron" :class="expanded && 'is-open'" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div id="shop-filter-price" x-show="expanded">
                            <form
                                method="GET"
                                action="{{ $formAction }}"
                                class="shop-price-form"
                                data-shop-price-form
                            >
                                @foreach ($shopParams(['min_price' => null, 'max_price' => null]) as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                <div class="shop-price-fields">
                                    <div class="shop-price-field">
                                        <label class="shop-price-field__label" for="shop-min-price">Min (PKR)</label>
                                        <input
                                            id="shop-min-price"
                                            class="shop-control shop-control--price{{ $priceError ? ' is-invalid' : '' }}"
                                            type="number"
                                            name="min_price"
                                            inputmode="decimal"
                                            min="0"
                                            step="0.01"
                                            placeholder="0"
                                            value="{{ $minPriceInput }}"
                                            aria-invalid="{{ $priceError ? 'true' : 'false' }}"
                                            @if ($priceError) aria-describedby="shop-price-error" @endif
                                        >
                                    </div>
                                    <div class="shop-price-field">
                                        <label class="shop-price-field__label" for="shop-max-price">Max (PKR)</label>
                                        <input
                                            id="shop-max-price"
                                            class="shop-control shop-control--price{{ $priceError ? ' is-invalid' : '' }}"
                                            type="number"
                                            name="max_price"
                                            inputmode="decimal"
                                            min="0"
                                            step="0.01"
                                            placeholder="Any"
                                            value="{{ $maxPriceInput }}"
                                            aria-invalid="{{ $priceError ? 'true' : 'false' }}"
                                            @if ($priceError) aria-describedby="shop-price-error" @endif
                                        >
                                    </div>
                                </div>
                                <p
                                    id="shop-price-error"
                                    class="shop-price-error"
                                    role="alert"
                                    @if ($priceError) data-server-error="1" @endif
                                    @if (! $priceError) hidden @endif
                                >{{ $priceError }}</p>
                                <button type="submit" class="shop-control shop-control--btn shop-price-form__apply">
                                    Apply price
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="shop-filters__footer">
                    @if ($hasActiveFilters)
                        <a href="{{ $clearAllUrl }}" class="shop-filters__footer-clear">Clear all</a>
                    @endif
                    <button type="button" class="shop-filters__done" @click="closeFilters()">
                        View results
                        <span class="shop-filters__done-count">({{ $products->total() }})</span>
                    </button>
                </div>
            </aside>

            <div class="shop-results">
                <form method="GET" action="{{ $formAction }}" class="shop-toolbar" role="search">
                    @if ($inStockOnly)
                        <input type="hidden" name="in_stock" value="1">
                    @endif
                    @if ($onSaleOnly)
                        <input type="hidden" name="on_sale" value="1">
                    @endif
                    @if ($collection !== '')
                        <input type="hidden" name="collection" value="{{ $collection }}">
                    @endif
                    @if ($minPriceInput !== null)
                        <input type="hidden" name="min_price" value="{{ $minPriceInput }}">
                    @endif
                    @if ($maxPriceInput !== null)
                        <input type="hidden" name="max_price" value="{{ $maxPriceInput }}">
                    @endif

                    <div class="shop-toolbar__lead">
                        <button
                            type="button"
                            class="shop-filters-toggle"
                            @click="openFilters()"
                            :aria-expanded="open.toString()"
                            aria-controls="shop-filters"
                        >
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.628 1.501c-.46-.046-.893.287-.993.745L.78 6.25a.75.75 0 0 0 .75.75h16.94a.75.75 0 0 0 .75-.75l-.854-4.004a.75.75 0 0 0-.993-.745A48.1 48.1 0 0 0 10 1c-2.476 0-4.908.17-7.372.501ZM1.5 8.5v8.75A1.75 1.75 0 0 0 3.25 19h13.5A1.75 1.75 0 0 0 18.5 17.25V8.5h-17Z" clip-rule="evenodd"/>
                            </svg>
                            <span>Filters</span>
                            @if ($activeFilterCount > 0)
                                <span class="shop-filters-toggle__badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>
                        <p class="shop-toolbar__meta" aria-live="polite">{{ $resultsLabel }}</p>
                    </div>

                    <div class="shop-toolbar__controls">
                        <div class="shop-toolbar__search">
                            <label for="shop-q" class="sr-only">Search products</label>
                            <span class="shop-toolbar__search-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <input
                                id="shop-q"
                                class="shop-control shop-control--search"
                                name="q"
                                type="search"
                                value="{{ $searchQuery }}"
                                placeholder="Search products"
                                autocomplete="off"
                            >
                        </div>
                        <div class="shop-toolbar__sort">
                            <label for="shop-sort" class="sr-only">Sort products</label>
                            <select
                                id="shop-sort"
                                class="shop-control shop-control--select"
                                name="sort"
                                onchange="this.form.submit()"
                            >
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
                            </select>
                        </div>
                        <button type="submit" class="shop-control shop-control--btn">Search</button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="shop-chips" aria-label="Active filters">
                        @foreach ($activeChips as $chip)
                            <a href="{{ $chip['removeUrl'] }}" class="shop-chip">
                                <span>{{ $chip['label'] }}</span>
                                <span class="shop-chip__remove" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                    </svg>
                                </span>
                                <span class="sr-only">Remove {{ $chip['label'] }} filter</span>
                            </a>
                        @endforeach
                        <a href="{{ $clearAllUrl }}" class="shop-chips__clear">Clear all</a>
                    </div>
                @endif

                @if ($products->isEmpty())
                    <div class="shop-empty" role="status">
                        <p class="shop-empty__title font-display">No products match these filters</p>
                        <p class="shop-empty__copy">Try clearing a filter, adjusting your search, or browsing the full catalog.</p>
                        <div class="shop-empty__actions">
                            <a href="{{ $clearAllUrl }}" class="shop-control shop-control--btn shop-control--btn-primary">Clear all filters</a>
                        </div>
                    </div>
                @else
                    <div class="shop-grid">
                        @foreach ($products as $product)
                            @include('storefront.home.partials.product_card', ['product' => $product, 'catalog' => true])
                        @endforeach
                    </div>

                    <div class="shop-pagination">
                        {{ $products->links('pagination.shop') }}
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