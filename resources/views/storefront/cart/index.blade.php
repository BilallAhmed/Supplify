@extends('layouts.storefront')

@section('title', 'Cart — '.config('app.name'))
@section('meta_description', 'Review your Supplify cart before checkout.')
@section('canonical', route('cart.index'))

@section('content')
    <section class="border-b border-line/60 bg-gradient-to-b from-brand-mist/50 to-surface-muted" aria-labelledby="cart-heading">
        <x-container class="py-8 sm:py-10">
            <nav class="mb-3 text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-ink" aria-current="page">Cart</li>
                </ol>
            </nav>
            <h1 id="cart-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Your cart</h1>
            <p class="mt-2 text-sm text-ink-muted sm:text-base">
                {{ $summary['item_count'] }} {{ \Illuminate\Support\Str::plural('item', $summary['item_count']) }} · prices refresh from the live catalog
            </p>
        </x-container>
    </section>

    <x-container class="py-10 lg:py-12">
        @if ($summary['lines']->isEmpty())
            <div class="mx-auto max-w-xl rounded-[var(--radius-panel)] border border-dashed border-line bg-surface-elevated px-6 py-14 text-center">
                <p class="font-display text-xl font-bold text-ink">Your cart is empty</p>
                <p class="mt-2 text-sm text-ink-muted">Browse the catalog and add formulas when you’re ready.</p>
                <div class="mt-6">
                    <x-button href="{{ route('shop.index') }}" variant="primary">Shop products</x-button>
                </div>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-[1.4fr_0.8fr]">
                <div class="space-y-4">
                    @foreach ($summary['lines'] as $line)
                        @php
                            /** @var \App\Models\CartItem $item */
                            $item = $line['item'];
                            $product = $line['product'];
                        @endphp
                        <article class="flex flex-col gap-4 rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated p-4 shadow-soft sm:flex-row sm:items-center">
                            <a href="{{ $product ? route('products.show', $product->slug) : '#' }}" class="mx-auto size-24 shrink-0 overflow-hidden rounded-[var(--radius-control)] border border-line bg-surface-muted sm:mx-0">
                                @if ($line['image'])
                                    <img src="{{ $line['image'] }}" alt="" class="size-full object-cover" width="96" height="96">
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <h2 class="font-display text-lg font-bold text-ink">
                                            @if ($product)
                                                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand">{{ $line['display_name'] ?? $product->name }}</a>
                                            @else
                                                Unavailable product
                                            @endif
                                        </h2>
                                        @if (! empty($line['option_labels']) && is_array($line['option_labels']))
                                            <p class="mt-1 text-xs text-ink-subtle">
                                                {{ collect($line['option_labels'])->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · ') }}
                                            </p>
                                        @endif
                                        <p class="mt-1 text-sm text-ink-muted">{{ $line['unit_formatted'] }} each @if (! empty($line['sku'])) · SKU {{ $line['sku'] }} @endif</p>
                                        @unless ($line['available'])
                                            <p class="mt-1 text-sm font-medium text-danger">Currently unavailable — remove or wait for restock.</p>
                                        @endunless
                                    </div>
                                    <p class="font-display text-lg font-bold text-ink">{{ $line['line_formatted'] }}</p>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="qty-{{ $item->id }}">Quantity</label>
                                        <input
                                            id="qty-{{ $item->id }}"
                                            type="number"
                                            name="quantity"
                                            min="0"
                                            max="{{ max($line['max_quantity'], $line['quantity']) }}"
                                            value="{{ $line['quantity'] }}"
                                            class="ui-input w-20"
                                            @if (! $line['available']) disabled @endif
                                        >
                                        <x-button type="submit" variant="outline" size="sm">Update</x-button>
                                    </form>

                                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="ghost" size="sm">Remove</x-button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="h-fit rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated p-6 shadow-soft lg:sticky lg:top-28">
                    <h2 class="font-display text-xl font-bold text-ink">Order summary</h2>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-muted">Subtotal</dt>
                            <dd class="font-semibold text-ink">{{ $summary['subtotal_formatted'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-muted">
                                Discount
                                @if ($summary['coupon_code'])
                                    <span class="mt-0.5 block text-xs font-normal text-ink-subtle">{{ $summary['coupon_code'] }}</span>
                                @endif
                            </dt>
                            <dd class="font-semibold {{ $summary['discount_cents'] > 0 ? 'text-accent' : 'text-ink-muted' }}">
                                {{ $summary['discount_cents'] > 0 ? '−'.$summary['discount_formatted'] : '—' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-line/60 pt-3">
                            <dt class="font-semibold text-ink">Total</dt>
                            <dd class="font-display text-xl font-bold text-ink">{{ $summary['total_formatted'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 border-t border-line/60 pt-5">
                        @if ($summary['coupon'])
                            <div class="flex items-center justify-between gap-3 rounded-[var(--radius-control)] bg-brand-mist/60 px-3 py-2.5 text-sm">
                                <p class="font-medium text-ink">
                                    {{ $summary['coupon']->code }}
                                    <span class="font-normal text-ink-muted">({{ $summary['coupon']->valueLabel() }})</span>
                                </p>
                                <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm">Remove</x-button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" class="space-y-2">
                                @csrf
                                <label for="coupon-code" class="text-sm font-medium text-ink">Coupon code</label>
                                <div class="flex flex-wrap gap-2">
                                    <input
                                        id="coupon-code"
                                        type="text"
                                        name="code"
                                        value="{{ old('code') }}"
                                        autocomplete="off"
                                        class="ui-input min-w-0 flex-1"
                                        placeholder="Enter code"
                                    >
                                    <x-button type="submit" variant="outline">Apply</x-button>
                                </div>
                                @error('code')
                                    <p class="text-sm text-danger">{{ $message }}</p>
                                @enderror
                            </form>
                        @endif
                    </div>

                    <p class="mt-4 text-xs text-ink-subtle">
                        Taxes and shipping are calculated at checkout. Item prices and discounts are always recomputed on the server.
                    </p>

                    <div class="mt-6 space-y-3">
                        <x-button href="{{ route('checkout.show') }}" variant="primary" class="w-full">
                            Checkout
                        </x-button>
                        <x-button href="{{ route('shop.index') }}" variant="outline" class="w-full">Continue shopping</x-button>
                    </div>
                </aside>
            </div>
        @endif
    </x-container>
@endsection
