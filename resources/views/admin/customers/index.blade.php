@extends('layouts.admin')

@section('title', 'Customers')
@section('breadcrumb', 'Customers')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Customers</h1>
        <p class="mt-1 text-sm text-ink-muted">Browse customer profiles, contact details, and order history.</p>
    </div>
@endsection

@section('content')
    <div class="admin-customers space-y-5">
        <div class="admin-customers__stats">
            <div class="admin-customers__stat">
                <p class="admin-customers__stat-label">Total</p>
                <p class="admin-customers__stat-value">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="admin-customers__stat">
                <p class="admin-customers__stat-label">Active</p>
                <p class="admin-customers__stat-value">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="admin-customers__stat">
                <p class="admin-customers__stat-label">Inactive</p>
                <p class="admin-customers__stat-value">{{ number_format($stats['inactive']) }}</p>
            </div>
            <div class="admin-customers__stat">
                <p class="admin-customers__stat-label">With orders</p>
                <p class="admin-customers__stat-value">{{ number_format($stats['with_orders']) }}</p>
            </div>
        </div>

        <div class="admin-customers__panel">
            <form method="GET" class="admin-customers__toolbar">
                <div class="admin-customers__search">
                    <label class="admin-customers__search-field">
                        <span class="sr-only">Search customers</span>
                        <svg class="admin-customers__search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M8.5 14.5a6 6 0 1 1 0-12 6 6 0 0 1 0 12ZM16 16l-3.2-3.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search name, email, or phone…"
                            class="ui-input admin-customers__search-input"
                        >
                    </label>
                </div>

                <div class="admin-customers__status">
                    <label>
                        <span class="sr-only">Status</span>
                        <select name="status" class="ui-input">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </label>
                </div>

                <div class="admin-customers__actions">
                    @if (request()->filled('q') || request()->filled('status'))
                        <x-button href="{{ route('admin.customers.index') }}" variant="ghost" size="sm">Clear</x-button>
                    @endif
                    <x-button type="submit" variant="primary" size="sm">Apply filters</x-button>
                </div>
            </form>

            <div class="admin-customers__table-wrap">
                <table class="admin-customers__table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Activity</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>
                                    <div class="admin-customers__person">
                                        <span class="admin-customers__avatar" aria-hidden="true">
                                            {{ strtoupper(\Illuminate\Support\Str::substr($customer->name, 0, 1)) }}
                                        </span>
                                        <div class="admin-customers__person-copy">
                                            <p class="admin-customers__name">{{ $customer->name }}</p>
                                            <p class="admin-customers__meta">{{ $customer->email }}</p>
                                            <p class="admin-customers__meta admin-customers__meta--muted">{{ $customer->phone ?: 'No phone on file' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="admin-customers__activity-primary">
                                        <strong>{{ $customer->orders_count }}</strong>
                                        order{{ $customer->orders_count === 1 ? '' : 's' }}
                                    </p>
                                    <p class="admin-customers__meta admin-customers__meta--muted">
                                        {{ $customer->addresses_count }} address{{ $customer->addresses_count === 1 ? '' : 'es' }}
                                    </p>
                                </td>
                                <td>
                                    @if ($customer->is_active)
                                        <x-badge variant="success">Active</x-badge>
                                    @else
                                        <x-badge variant="danger">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="admin-customers__joined">
                                    {{ $customer->created_at?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="admin-customers__row-action">
                                    <x-button href="{{ route('admin.customers.show', $customer) }}" variant="outline" size="sm">
                                        View profile
                                    </x-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-customers__empty">
                                    <p class="admin-customers__empty-title">No customers match</p>
                                    <p class="admin-customers__empty-copy">
                                        Try a different search, clear filters, or check back after new registrations.
                                    </p>
                                    @if (request()->filled('q') || request()->filled('status'))
                                        <div class="admin-customers__empty-action">
                                            <x-button href="{{ route('admin.customers.index') }}" variant="outline" size="sm">Clear filters</x-button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="admin-customers__pager">{{ $customers->links() }}</div>
            @endif
        </div>
    </div>
@endsection
