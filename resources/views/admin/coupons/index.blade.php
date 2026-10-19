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
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search codes…" :value="request('q')" class="max-w-sm" />
        <x-button type="submit" variant="outline">Search</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">Type</th>
                    <th class="px-4 py-3 font-semibold">Value</th>
                    <th class="px-4 py-3 font-semibold">Uses</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">{{ $coupon->code }}</p>
                            @if ($coupon->min_subtotal_cents)
                                <p class="text-xs text-ink-subtle">Min {{ \App\Support\Money::format($coupon->min_subtotal_cents) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $coupon->type->label() }}</td>
                        <td class="px-4 py-3 font-medium text-ink">{{ $coupon->valueLabel() }}</td>
                        <td class="px-4 py-3 text-ink-muted">
                            {{ $coupon->used_count }}{{ $coupon->max_uses !== null ? ' / '.$coupon->max_uses : '' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$coupon->is_active ? 'success' : 'neutral'">
                                {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
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
                        <td colspan="6" class="px-4 py-10 text-center text-ink-muted">No coupons yet. Create your first code.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $coupons->links() }}</div>
@endsection
