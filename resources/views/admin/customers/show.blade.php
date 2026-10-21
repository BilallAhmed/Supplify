@extends('layouts.admin')

@section('title', $customer->name)
@section('breadcrumb', 'Customers / '.$customer->name)

@section('page_header')
    <div class="flex min-w-0 items-start gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-soft font-display text-sm font-bold text-brand" aria-hidden="true">
            {{ strtoupper(\Illuminate\Support\Str::substr($customer->name, 0, 1)) }}
        </span>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $customer->name }}</h1>
                @if ($customer->is_active)
                    <x-badge variant="success">Active</x-badge>
                @else
                    <x-badge variant="danger">Inactive</x-badge>
                @endif
            </div>
            <p class="mt-1 truncate text-sm text-ink-muted">
                {{ $customer->email }}
                · Joined {{ $customer->created_at?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}
            </p>
        </div>
    </div>
    <x-button href="{{ route('admin.customers.index') }}" variant="outline">Back to customers</x-button>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)]">
        <div class="space-y-6">
            <x-card class="space-y-4">
                <div>
                    <h2 class="font-display text-lg font-bold text-ink">Profile</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">Update contact details and account access.</p>
                </div>
                <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="admin-form-grid">
                        <x-input label="Name" name="name" :value="old('name', $customer->name)" required />
                        <x-input label="Phone" name="phone" :value="old('phone', $customer->phone)" />
                        <x-input label="Email" name="email" class="ui-field--full" :value="$customer->email" disabled help="Email cannot be changed here." />
                        <input type="hidden" name="is_active" value="0">
                        <x-checkbox
                            name="is_active"
                            value="1"
                            label="Account is active"
                            help="Inactive customers cannot sign in."
                            :checked="(bool) old('is_active', $customer->is_active)"
                        />
                    </div>
                    <div class="admin-form__actions">
                        <x-button type="submit" variant="primary">Save changes</x-button>
                    </div>
                </form>
            </x-card>

            <x-card class="space-y-3">
                <div>
                    <h2 class="font-display text-lg font-bold text-ink">Addresses</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $customer->addresses->count() }} saved address{{ $customer->addresses->count() === 1 ? '' : 'es' }}</p>
                </div>
                @forelse ($customer->addresses as $address)
                    <div class="rounded-[var(--radius-control)] border border-line bg-surface px-3.5 py-3 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-ink">{{ $address->name }}</p>
                            @if ($address->is_default)
                                <x-badge variant="brand">Default</x-badge>
                            @endif
                            <span class="text-xs tracking-wide text-ink-subtle uppercase">{{ $address->type }}</span>
                        </div>
                        <p class="mt-1 text-ink-muted">
                            {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif
                        </p>
                        <p class="text-ink-muted">
                            {{ $address->city }}@if ($address->state), {{ $address->state }}@endif
                            {{ $address->postal_code }}
                            · {{ $address->country }}
                        </p>
                        @if ($address->phone)
                            <p class="mt-1 text-ink-subtle">{{ $address->phone }}</p>
                        @endif
                    </div>
                @empty
                    <p class="rounded-[var(--radius-control)] border border-dashed border-line px-3 py-6 text-center text-sm text-ink-muted">No saved addresses.</p>
                @endforelse
            </x-card>
        </div>

        <x-card class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg font-bold text-ink">Order history</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">Latest {{ $orders->count() }} order{{ $orders->count() === 1 ? '' : 's' }}</p>
                </div>
                @if ($orders->isNotEmpty())
                    <x-button href="{{ route('admin.orders.index', ['q' => $customer->email]) }}" variant="ghost" size="sm">All orders</x-button>
                @endif
            </div>

            <div class="overflow-hidden rounded-[var(--radius-control)] border border-line">
                <table class="min-w-full divide-y divide-line text-left text-sm">
                    <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th class="px-3 py-2.5 font-semibold">Order</th>
                            <th class="px-3 py-2.5 font-semibold">Total</th>
                            <th class="px-3 py-2.5 font-semibold">Status</th>
                            <th class="px-3 py-2.5 font-semibold"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($orders as $order)
                            <tr class="transition-colors hover:bg-brand-mist/30">
                                <td class="px-3 py-3">
                                    <p class="font-medium text-ink">{{ $order->order_number }}</p>
                                    <p class="text-xs text-ink-subtle">
                                        {{ optional($order->placed_at)->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}
                                        · {{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 font-semibold text-ink">{{ $order->totalFormatted() }}</td>
                                <td class="px-3 py-3">
                                    <x-badge variant="brand">{{ $order->status->label() }}</x-badge>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <x-button href="{{ route('admin.orders.show', $order) }}" variant="ghost" size="sm">View</x-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-10 text-center text-ink-muted">No orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
