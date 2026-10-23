@extends('layouts.admin')

@section('title', 'Coupons')
@section('breadcrumb', 'Coupons')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Coupons</h1>
        <p class="mt-1 text-sm text-ink-muted">Percent and fixed discount codes with usage limits and validity windows.</p>
    </div>
    <x-button href="{{ route('admin.coupons.create') }}" variant="primary">Add coupon</x-button>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-danger p-4 text-sm text-danger" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search codes…" :value="request('q')" aria-label="Search coupons" />
            </div>
<div class="admin-toolbar__control"><x-select name="expiry" aria-label="All expiry dates"><option value="">All expiry dates</option><option value="expired" @selected(request('expiry') === 'expired')>Expired</option><option value="upcoming" @selected(request('expiry') === 'upcoming')>Expires within 30 days</option><option value="none" @selected(request('expiry') === 'none')>No expiry</option></x-select></div>
<div class="admin-toolbar__actions">
                @if (request()->anyFilled(['q', 'expiry']))
                    <x-button href="{{ route('admin.coupons.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Uses</th>
                        <th>Status</th><th>Expires</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $coupon->code }}</p>
                                @if ($coupon->min_subtotal_cents)
                                    <p class="text-xs text-ink-subtle">Min {{ \App\Support\Money::format($coupon->min_subtotal_cents) }}</p>
                                @endif
                            </td>
                            <td class="text-ink-muted">{{ $coupon->type->label() }}</td>
                            <td class="font-medium text-ink">{{ $coupon->valueLabel() }}</td>
                            <td class="text-ink-muted">
                                {{ $coupon->used_count }}{{ $coupon->max_uses !== null ? ' / '.$coupon->max_uses : '' }}
                            </td>
                            <td>
                                <x-badge :variant="$coupon->is_active && $coupon->isWithinSchedule() && $coupon->hasRemainingUses() ? 'success' : 'neutral'">
                                    {{ ! $coupon->is_active ? 'Inactive' : ($coupon->ends_at?->isPast() ? 'Expired' : ($coupon->starts_at?->isFuture() ? 'Scheduled' : (! $coupon->hasRemainingUses() ? 'Used up' : 'Active'))) }}
                                </x-badge>
                            </td>
                            <td class="text-ink-muted">{{ $coupon->ends_at?->format('M j, Y') ?? 'No expiry' }}</td><td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.coupons.edit', $coupon) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-data-table__empty">No coupons match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($coupons->hasPages())
            <div class="admin-table-panel__pager">{{ $coupons->links() }}</div>
        @endif
    </div>
@endsection
