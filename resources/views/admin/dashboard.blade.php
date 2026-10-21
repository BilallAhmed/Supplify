@extends('layouts.admin')

@php
    /** @var array<string, mixed> $dashboard */
    $range = $dashboard['range'];
    $summary = $dashboard['summary'];
    $chart = $dashboard['chart'];
    $topProducts = $dashboard['top_products'];
    $recentActivity = $dashboard['recent_activity'];
    $featuredProducts = $dashboard['featured_products'];
    $hasTracking = $dashboard['has_tracking'];
    $trackingStartedAt = $dashboard['tracking_started_at'];
    $periodLabel = $summary['period_label'] ?? $range['label'];
@endphp

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Dashboard</h1>
        <p class="mt-1.5 text-sm text-ink-muted">
            Product analytics overview. Metrics reflect recorded storefront product-page views, not purchases.
        </p>
    </div>
@endsection

@push('head')
    @vite(['resources/js/admin-dashboard.js'])
@endpush

@section('content')
    <div class="admin-dash">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dash__range" aria-label="Reporting period">
            <div>
                <p class="admin-dash__shortcut-label">Period</p>
                <div class="admin-dash__range-presets mt-1.5">
                    <a
                        href="{{ route('admin.dashboard', array_filter(['range' => '7', 'product_id' => $dashboard['product_filter_id']])) }}"
                        class="admin-dash__preset {{ $range['preset'] === '7' ? 'is-active' : '' }}"
                    >Last 7 days</a>
                    <a
                        href="{{ route('admin.dashboard', array_filter(['range' => '30', 'product_id' => $dashboard['product_filter_id']])) }}"
                        class="admin-dash__preset {{ $range['preset'] === '30' ? 'is-active' : '' }}"
                    >Last 30 days</a>
                    <span class="admin-dash__preset {{ $range['preset'] === 'custom' ? 'is-active' : '' }}" aria-current="{{ $range['preset'] === 'custom' ? 'true' : 'false' }}">Custom</span>
                </div>
            </div>

            <div class="admin-dash__custom">
                <input type="hidden" name="range" value="custom">
                @if ($dashboard['product_filter_id'])
                    <input type="hidden" name="product_id" value="{{ $dashboard['product_filter_id'] }}">
                @endif
                <x-input type="date" name="from" label="From" :value="$range['from']" required />
                <x-input type="date" name="to" label="To" :value="$range['to']" required />
                <x-button type="submit" variant="primary" size="sm">Apply range</x-button>
            </div>

            <p class="admin-dash__tracking-note w-full">
                @if ($trackingStartedAt)
                    Product-view tracking started {{ $trackingStartedAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }} ({{ config('app.timezone') }}).
                    Earlier periods have no recorded data.
                @else
                    No product views have been recorded yet. Tracking begins on the next successful public product-page visit (admins and known bots are excluded).
                @endif
            </p>
        </form>

        <div class="admin-dash__metrics">
            <article class="admin-dash__metric">
                <p class="admin-dash__metric-label">Product detail views</p>
                @if ($summary['available'])
                    <p class="admin-dash__metric-value">{{ number_format($summary['total_views']) }}</p>
                @else
                    <p class="admin-dash__metric-value admin-dash__metric-value--muted">No data</p>
                @endif
                <p class="admin-dash__metric-hint">{{ $periodLabel }} · recorded page opens</p>
            </article>

            <article class="admin-dash__metric">
                <p class="admin-dash__metric-label">Unique product visitors</p>
                @if ($summary['available'])
                    <p class="admin-dash__metric-value">{{ number_format($summary['unique_visitors']) }}</p>
                    <p class="admin-dash__metric-hint">{{ $periodLabel }} · distinct anonymous sessions</p>
                @else
                    <p class="admin-dash__metric-value admin-dash__metric-value--muted">No data</p>
                    <p class="admin-dash__metric-hint">Unavailable until views are recorded</p>
                @endif
            </article>

            <article class="admin-dash__metric">
                <p class="admin-dash__metric-label">Most-viewed product</p>
                @if ($summary['available'] && $summary['most_viewed'])
                    <p class="admin-dash__metric-sub" title="{{ $summary['most_viewed']['product']->name }}">
                        {{ $summary['most_viewed']['product']->name }}
                    </p>
                    <p class="admin-dash__metric-hint">
                        {{ number_format($summary['most_viewed']['views']) }} views · {{ $periodLabel }}
                    </p>
                @else
                    <p class="admin-dash__metric-value admin-dash__metric-value--muted">No data</p>
                    <p class="admin-dash__metric-hint">{{ $periodLabel }}</p>
                @endif
            </article>

            <article class="admin-dash__metric">
                <p class="admin-dash__metric-label">Featured products</p>
                <p class="admin-dash__metric-value">{{ number_format($dashboard['featured_count']) }}</p>
                <p class="admin-dash__metric-hint">Current catalog flag · not a popularity metric</p>
            </article>
        </div>

        @if (! empty($summary['note']))
            <p class="admin-dash__tracking-note">{{ $summary['note'] }}</p>
        @endif

        <section class="admin-dash__panel" aria-labelledby="views-chart-heading">
            <div class="admin-dash__panel-head">
                <div>
                    <h2 id="views-chart-heading" class="admin-dash__panel-title">Product views over time</h2>
                    <p class="admin-dash__panel-desc">
                        Top five products by views for {{ $periodLabel }}
                        @if ($dashboard['product_filter_id'])
                            · filtered to one product
                        @endif
                        . Views are storefront detail visits, not sales.
                    </p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dash__chart-filter">
                    <input type="hidden" name="range" value="{{ $range['preset'] === 'custom' ? 'custom' : $range['preset'] }}">
                    @if ($range['preset'] === 'custom')
                        <input type="hidden" name="from" value="{{ $range['from'] }}">
                        <input type="hidden" name="to" value="{{ $range['to'] }}">
                    @endif
                    <x-select name="product_id" label="Filter product" aria-label="Filter chart by product">
                        <option value="">Top 5 in period</option>
                        @foreach ($dashboard['filter_products'] as $product)
                            <option value="{{ $product->id }}" @selected((int) $dashboard['product_filter_id'] === (int) $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-button type="submit" variant="outline" size="sm">Update chart</x-button>
                </form>
            </div>
            <div class="admin-dash__panel-body">
                <div class="admin-dash__chart-wrap">
                    <p id="admin-product-views-chart-status" class="admin-dash__chart-status" @if (! $chart['empty']) hidden @endif>
                        {{ $chart['empty_reason'] ?? 'Loading chart…' }}
                    </p>
                    <canvas
                        id="admin-product-views-chart"
                        role="img"
                        aria-label="Line chart of product page views over time for {{ $periodLabel }}"
                    ></canvas>
                </div>

                @php
                    $chartJson = json_encode([
                        'labels' => $chart['labels'],
                        'datasets' => $chart['datasets'],
                        'empty' => $chart['empty'],
                        'empty_reason' => $chart['empty_reason'],
                    ], JSON_THROW_ON_ERROR);
                @endphp
                <script type="application/json" id="admin-dashboard-chart-data">{!! $chartJson !!}</script>

                <div class="admin-dash__sr-table">
                    <details>
                        <summary class="cursor-pointer text-sm font-semibold text-ink">Accessible data table</summary>
                        @if ($chart['table'] === [])
                            <p class="admin-dash__empty">No daily view rows for this period.</p>
                        @else
                            <div class="admin-table-wrap mt-3">
                                <table class="admin-data-table" style="min-width: 24rem">
                                    <thead>
                                        <tr>
                                            <th scope="col">Date</th>
                                            <th scope="col">Product</th>
                                            <th scope="col">Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($chart['table'] as $row)
                                            <tr>
                                                <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M j, Y') }}</td>
                                                <td>{{ $row['product'] }}</td>
                                                <td>{{ number_format($row['views']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </details>
                </div>
            </div>
        </section>

        <div class="admin-dash__grid">
            <section
                class="admin-dash__panel"
                aria-labelledby="top-products-heading"
                x-data="adminDashPanel('topProducts', true)"
            >
                <div class="admin-dash__panel-head">
                    <div class="min-w-0 flex-1">
                        <h2 id="top-products-heading" class="admin-dash__panel-title">Top-performing products</h2>
                        <p class="admin-dash__panel-desc">
                            Ranked by recorded product-page views for {{ $periodLabel }}. Featured status is separate.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="admin-dash__panel-toggle"
                        @click="toggle()"
                        :aria-expanded="open.toString()"
                        aria-controls="top-products-panel"
                    >
                        <span x-text="open ? 'Hide' : 'Show'">Hide</span>
                        <svg class="admin-dash__panel-toggle-icon" :class="{ 'is-open': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                <div id="top-products-panel" x-show="open">
                    <div class="admin-table-wrap">
                        <table class="admin-data-table" style="min-width: 36rem">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Views</th>
                                    <th scope="col">Unique visitors</th>
                                    <th scope="col">Featured</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $row)
                                    @php $product = $row['product']; @endphp
                                    <tr>
                                        <td>{{ $row['rank'] }}</td>
                                        <td>
                                            <div class="flex items-center gap-3 min-w-0">
                                                @if ($product->primaryImage())
                                                    <img src="{{ $product->primaryImage()->url() }}" alt="" class="admin-dash__rank-thumb">
                                                @else
                                                    <span class="admin-dash__rank-thumb" aria-hidden="true"></span>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="font-medium text-ink truncate">{{ $product->name }}</p>
                                                    <p class="text-xs text-ink-subtle">{{ $product->sku }}</p>
                                                    @if ($row['compare_available'] && $row['views_delta'] !== null)
                                                        @php
                                                            $deltaClass = $row['views_delta'] > 0
                                                                ? 'admin-dash__delta admin-dash__delta--up'
                                                                : ($row['views_delta'] < 0
                                                                    ? 'admin-dash__delta admin-dash__delta--down'
                                                                    : 'admin-dash__delta admin-dash__delta--flat');
                                                        @endphp
                                                        <p class="{{ $deltaClass }}">
                                                            @if ($row['views_delta'] > 0)
                                                                +{{ number_format($row['views_delta']) }} vs prior period
                                                            @elseif ($row['views_delta'] < 0)
                                                                {{ number_format($row['views_delta']) }} vs prior period
                                                            @else
                                                                No change vs prior period
                                                            @endif
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($row['views']) }}</td>
                                        <td>{{ number_format($row['unique_visitors']) }}</td>
                                        <td>
                                            @if ($product->is_featured)
                                                <x-badge variant="brand">Featured</x-badge>
                                            @else
                                                <span class="text-ink-subtle">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="admin-row-actions">
                                                @if (! $product->trashed())
                                                    <x-button href="{{ route('admin.products.edit', $product) }}" variant="outline" size="sm">Edit</x-button>
                                                    @if ($product->is_active)
                                                        <x-button href="{{ route('products.show', $product->slug) }}" variant="ghost" size="sm" target="_blank" rel="noopener">Storefront</x-button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="admin-data-table__empty">
                                            @if ($hasTracking)
                                                No product views in this period.
                                            @else
                                                Rankings appear after the first tracked product-page visit.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="space-y-5">
                <section
                    class="admin-dash__panel"
                    aria-labelledby="activity-heading"
                    x-data="adminDashPanel('recentActivity', true)"
                >
                    <div class="admin-dash__panel-head">
                        <div class="min-w-0 flex-1">
                            <h2 id="activity-heading" class="admin-dash__panel-title">Recent activity</h2>
                            <p class="admin-dash__panel-desc">Catalog events only — not performance rankings.</p>
                        </div>
                        <button
                            type="button"
                            class="admin-dash__panel-toggle"
                            @click="toggle()"
                            :aria-expanded="open.toString()"
                            aria-controls="recent-activity-panel"
                        >
                            <span x-text="open ? 'Hide' : 'Show'">Hide</span>
                            <svg class="admin-dash__panel-toggle-icon" :class="{ 'is-open': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <div id="recent-activity-panel" class="admin-dash__panel-body" x-show="open">
                        @if ($recentActivity === [])
                            <p class="admin-dash__empty">No product activity recorded yet. Create, update, or feature a product to see events here.</p>
                        @else
                            <ul class="admin-dash__activity">
                                @foreach ($recentActivity as $item)
                                    <li class="admin-dash__activity-item">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-ink">{{ $item['label'] }}</p>
                                            <p class="mt-0.5 text-sm text-ink-muted truncate">{{ $item['product_name'] }}</p>
                                            <div class="mt-1.5 flex flex-wrap gap-2">
                                                @if ($item['edit_url'])
                                                    <a href="{{ $item['edit_url'] }}" class="text-xs font-semibold text-brand hover:underline">Edit in admin</a>
                                                @endif
                                                @if ($item['storefront_url'])
                                                    <a href="{{ $item['storefront_url'] }}" class="text-xs font-medium text-ink-muted hover:text-ink hover:underline" target="_blank" rel="noopener">View storefront</a>
                                                @endif
                                            </div>
                                        </div>
                                        <time class="shrink-0 text-xs text-ink-subtle" datetime="{{ optional($item['when'])->toIso8601String() }}">
                                            {{ $item['when_human'] }}
                                        </time>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                <section
                    class="admin-dash__panel"
                    aria-labelledby="quick-heading"
                    x-data="adminDashPanel('quickAccess', true)"
                >
                    <div class="admin-dash__panel-head">
                        <div class="min-w-0 flex-1">
                            <h2 id="quick-heading" class="admin-dash__panel-title">Quick access</h2>
                            <p class="admin-dash__panel-desc">Admin tools and storefront destinations.</p>
                        </div>
                        <button
                            type="button"
                            class="admin-dash__panel-toggle"
                            @click="toggle()"
                            :aria-expanded="open.toString()"
                            aria-controls="quick-access-panel"
                        >
                            <span x-text="open ? 'Hide' : 'Show'">Hide</span>
                            <svg class="admin-dash__panel-toggle-icon" :class="{ 'is-open': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <div id="quick-access-panel" class="admin-dash__panel-body admin-dash__shortcuts" x-show="open">
                        <div class="admin-dash__shortcut-group">
                            <p class="admin-dash__shortcut-label">Storefront</p>
                            <div class="admin-dash__shortcut-list">
                                <x-button href="{{ route('home') }}" variant="ghost" class="w-full justify-start" target="_blank" rel="noopener">Open storefront</x-button>
                            </div>
                        </div>

                        <div class="admin-dash__shortcut-group">
                            <p class="admin-dash__shortcut-label">Catalog admin</p>
                            <div class="admin-dash__shortcut-list">
                                <x-button href="{{ route('admin.products.index') }}" variant="outline" class="w-full justify-start">Manage products</x-button>
                                <x-button href="{{ route('admin.products.index', ['status' => 'featured']) }}" variant="outline" class="w-full justify-start">Manage featured products</x-button>
                            </div>
                        </div>

                        <div class="admin-dash__shortcut-group">
                            <p class="admin-dash__shortcut-label">Leading products ({{ $periodLabel }})</p>
                            <div class="admin-dash__shortcut-list">
                                @forelse (array_slice($topProducts, 0, 5) as $row)
                                    @php $product = $row['product']; @endphp
                                    @if (! $product->trashed())
                                        <div class="flex flex-wrap gap-2">
                                            <x-button href="{{ route('admin.products.edit', $product) }}" variant="outline" size="sm" class="justify-start">
                                                Edit · {{ \Illuminate\Support\Str::limit($product->name, 28) }}
                                            </x-button>
                                            @if ($product->is_active)
                                                <x-button href="{{ route('products.show', $product->slug) }}" variant="ghost" size="sm" target="_blank" rel="noopener">Storefront</x-button>
                                            @endif
                                        </div>
                                    @endif
                                @empty
                                    <p class="text-sm text-ink-muted">No leading products for this period yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="admin-dash__shortcut-group">
                            <p class="admin-dash__shortcut-label">Currently featured</p>
                            <div class="admin-dash__shortcut-list">
                                @forelse ($featuredProducts as $product)
                                    <div class="flex flex-wrap gap-2">
                                        <x-button href="{{ route('admin.products.edit', $product) }}" variant="outline" size="sm" class="justify-start">
                                            Edit · {{ \Illuminate\Support\Str::limit($product->name, 28) }}
                                        </x-button>
                                        @if ($product->is_active)
                                            <x-button href="{{ route('products.show', $product->slug) }}" variant="ghost" size="sm" target="_blank" rel="noopener">Storefront</x-button>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-ink-muted">No products are featured right now.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
