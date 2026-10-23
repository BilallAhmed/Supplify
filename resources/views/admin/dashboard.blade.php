@extends('layouts.admin')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('page_header')
    <div><h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Dashboard</h1><p class="mt-1 text-sm text-ink-muted">Your store at a glance. Review orders, stock, and tasks that need attention.</p></div>
    <x-button href="{{ route('admin.products.create') }}" variant="primary">Add a product</x-button>
@endsection
@section('content')
    <div class="space-y-6">
        <div class="admin-dash__metrics">
            @foreach($overview['stats'] as $stat)
                <a href="{{ $stat['url'] }}" class="admin-dash__metric transition-colors hover:border-brand focus-visible:outline-2 focus-visible:outline-brand">
                    <div class="flex items-center justify-between gap-3"><p class="admin-dash__metric-label">{{ $stat['label'] }}</p><span class="text-brand" aria-hidden="true">@include('admin.partials.nav-icon',['name'=>$stat['icon']])</span></div>
                    <p class="admin-dash__metric-value">{{ number_format($stat['value']) }}</p><p class="admin-dash__metric-hint">{{ $stat['hint'] }}</p>
                </a>
            @endforeach
        </div>
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(20rem,1fr)]">
            <x-card class="min-w-0 !p-0">
                <header class="flex items-center justify-between gap-3 border-b border-line p-5"><h2 class="font-display text-lg font-bold">Recent orders</h2><a class="text-sm font-semibold text-brand" href="{{ route('admin.orders.index') }}">View all orders</a></header>
                <div class="overflow-x-auto"><table class="admin-data-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead><tbody>
                    @forelse($overview['orders'] as $order)
                        <tr><td><a class="font-semibold text-brand" href="{{ route('admin.orders.show',$order) }}">{{ $order->order_number }}</a></td><td>{{ $order->shipping_name ?: 'Guest customer' }}</td><td>{{ $order->totalFormatted() }}</td><td><x-badge variant="neutral">{{ $order->status->label() }}</x-badge></td><td class="whitespace-nowrap text-xs text-ink-muted">{{ ($order->placed_at ?? $order->created_at)?->timezone(config('app.timezone'))->format('M j, Y') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-ink-muted">No orders yet. New orders will appear here.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-card>
            <x-card>
                <h2 class="font-display text-lg font-bold">Needs attention</h2><p class="mb-4 mt-1 text-xs text-ink-muted">Live reminders from your store.</p>
                <ul class="divide-y divide-line">
                    @foreach($overview['alerts'] as $alert)
                        <li><a href="{{ $alert['url'] }}" class="flex items-start justify-between gap-4 py-3"><div><p class="text-sm font-semibold">{{ $alert['title'] }}</p><p class="mt-1 text-xs leading-relaxed text-ink-muted">{{ $alert['description'] }}</p></div><x-badge :variant="$alert['count'] ? 'warning' : 'neutral'">{{ $alert['count'] }}</x-badge></a></li>
                    @endforeach
                </ul>
            </x-card>
        </div>
        <x-card>
            <h2 class="font-display text-lg font-bold">Quick links</h2><p class="mb-4 mt-1 text-sm text-ink-muted">Go straight to your everyday tasks.</p>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach($overview['quickLinks'] as $link)<a href="{{ route($link['route']) }}" class="flex items-center gap-3 rounded-lg border border-line px-4 py-3 text-sm font-semibold transition-colors hover:border-brand hover:bg-brand-soft"><span class="text-brand" aria-hidden="true">@include('admin.partials.nav-icon',['name'=>$link['icon']])</span>{{ $link['label'] }}</a>@endforeach
            </div>
        </x-card>
        <x-card>
            <h2 class="font-display text-lg font-bold">Recent activity</h2>
            <ul class="mt-3 divide-y divide-line">@forelse($overview['activity'] as $activity)<li class="flex flex-wrap items-center justify-between gap-2 py-3"><div><span class="text-sm font-semibold">{{ $activity->label() }}</span><span class="ml-2 text-sm text-ink-muted">{{ $activity->product?->name ?: 'Removed product' }}</span></div><time class="text-xs text-ink-subtle" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time></li>@empty<li class="py-4 text-sm text-ink-muted">No product activity recorded yet.</li>@endforelse</ul>
        </x-card>
    </div>
@endsection
