@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Dashboard</h1>
        <p class="mt-1.5 text-sm text-ink-muted">Overview of store health. Commerce metrics fill in as modules ship.</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-5 shadow-soft">
                <p class="text-[0.6875rem] font-semibold tracking-[0.16em] text-ink-subtle uppercase">{{ $stat['label'] }}</p>
                <p class="mt-2.5 font-display text-3xl font-semibold tracking-tight text-ink">{{ $stat['value'] }}</p>
                <p class="mt-1.5 text-xs leading-relaxed text-ink-muted">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <section class="rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-5 shadow-soft lg:col-span-3" aria-labelledby="activity-heading">
            <h2 id="activity-heading" class="font-display text-base font-semibold tracking-tight text-ink sm:text-lg">Recent activity</h2>
            <ul class="mt-4 divide-y divide-line">
                @foreach ($recentActivity as $item)
                    <li class="flex items-start justify-between gap-4 py-3.5 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $item['detail'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-ink-subtle">{{ $item['when'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-5 shadow-soft lg:col-span-2" aria-labelledby="quick-heading">
            <h2 id="quick-heading" class="font-display text-base font-semibold tracking-tight text-ink sm:text-lg">Quick links</h2>
            <div class="mt-4 space-y-2">
                <x-button href="{{ route('admin.products.index') }}" variant="outline" class="w-full justify-start">Products</x-button>
                <x-button href="{{ route('admin.orders.index') }}" variant="outline" class="w-full justify-start">Orders</x-button>
                <x-button href="{{ route('admin.homepage.edit') }}" variant="outline" class="w-full justify-start">Homepage content</x-button>
                <x-button href="{{ route('home') }}" variant="ghost" class="w-full justify-start">Open storefront</x-button>
            </div>
        </section>
    </div>
@endsection
