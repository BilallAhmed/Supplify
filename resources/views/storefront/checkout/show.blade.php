@extends('layouts.storefront')

@section('title', 'Checkout — '.config('app.name'))
@section('meta_description', 'Complete your Supplify order — shipping, billing, and order summary.')
@section('canonical', route('checkout.show'))

@section('content')
    <section class="border-b border-line/60 bg-gradient-to-b from-brand-mist/50 to-surface-muted" aria-labelledby="checkout-heading">
        <x-container class="py-8 sm:py-10">
            <nav class="mb-3 text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-brand">Cart</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-ink" aria-current="page">Checkout</li>
                </ol>
            </nav>
            <h1 id="checkout-heading" class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Checkout</h1>
            <p class="mt-2 text-sm text-ink-muted sm:text-base">
                Review your details. Payment is handled after this step.
            </p>
        </x-container>
    </section>

    <x-container class="py-10 lg:py-12">
        <form method="POST" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-[1.35fr_0.85fr]">
            @csrf

            <div class="space-y-6">
                <x-card class="space-y-4">
                    <h2 class="font-display text-xl font-bold text-ink">Contact</h2>
                    <x-input label="Email" name="email" type="email" required :value="$defaults['email']" autocomplete="email" />
                    <x-input label="Phone" name="phone" type="tel" :value="old('phone', $defaults['phone'])" autocomplete="tel" />
                </x-card>

                <x-card class="space-y-4">
                    <h2 class="font-display text-xl font-bold text-ink">Shipping address</h2>
                    <x-input label="Full name" name="shipping_name" required :value="$defaults['shipping_name']" autocomplete="shipping name" />
                    <x-input label="Address line 1" name="shipping_line1" required :value="$defaults['shipping_line1']" autocomplete="shipping address-line1" />
                    <x-input label="Address line 2" name="shipping_line2" :value="$defaults['shipping_line2']" autocomplete="shipping address-line2" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input label="City" name="shipping_city" required :value="$defaults['shipping_city']" autocomplete="shipping address-level2" />
                        <x-province-select name="shipping_state" required :value="$defaults['shipping_state']" autocomplete="shipping address-level1" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input label="Postal code" name="shipping_postal_code" required :value="$defaults['shipping_postal_code']" autocomplete="shipping postal-code" />
                        <x-select label="Country" name="shipping_country" required help="Delivery within Pakistan." autocomplete="shipping country"><option value="PK" selected>Pakistan</option></x-select>
                    </div>
                    <x-input label="Shipping phone" name="shipping_phone" :value="$defaults['shipping_phone']" autocomplete="shipping tel" />
                </x-card>

                <x-card class="space-y-4" x-data="{ same: {{ old('billing_same_as_shipping', '1') ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-bold text-ink">Billing address</h2>
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="hidden" name="billing_same_as_shipping" value="0">
                            <input
                                type="checkbox"
                                name="billing_same_as_shipping"
                                value="1"
                                class="size-4 rounded border-line text-brand focus:ring-brand/30"
                                x-model="same"
                                @checked(old('billing_same_as_shipping', true))
                            >
                            Same as shipping
                        </label>
                    </div>

                    <div class="space-y-4" x-show="!same" x-cloak>
                        <x-input label="Full name" name="billing_name" :value="old('billing_name')" autocomplete="billing name" />
                        <x-input label="Address line 1" name="billing_line1" :value="old('billing_line1')" autocomplete="billing address-line1" />
                        <x-input label="Address line 2" name="billing_line2" :value="old('billing_line2')" autocomplete="billing address-line2" />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input label="City" name="billing_city" :value="old('billing_city')" autocomplete="billing address-level2" />
                            <x-province-select name="billing_state" :value="old('billing_state')" autocomplete="billing address-level1" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input label="Postal code" name="billing_postal_code" :value="old('billing_postal_code')" autocomplete="billing postal-code" />
                            <x-select label="Country" name="billing_country" autocomplete="billing country"><option value="PK" selected>Pakistan</option></x-select>
                        </div>
                        <x-input label="Billing phone" name="billing_phone" :value="old('billing_phone')" autocomplete="billing tel" />
                    </div>
                </x-card>

                <x-card class="space-y-4">
                    <h2 class="font-display text-xl font-bold text-ink">Payment</h2>
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-[var(--radius-control)] border border-line/70 bg-surface-muted/40 p-4">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ $method->value }}"
                                class="mt-1 size-4 border-line text-brand focus:ring-brand/30"
                                @checked(old('payment_method', \App\Enums\PaymentMethod::Cod->value) === $method->value)
                            >
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ $method->label() }}</span>
                                <span class="mt-1 block text-sm text-ink-muted">{{ $method->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                    @error('payment_method')
                        <p class="text-sm text-danger">{{ $message }}</p>
                    @enderror
                </x-card>

                <x-card class="space-y-4">
                    <h2 class="font-display text-xl font-bold text-ink">Order notes</h2>
                    <x-textarea name="notes" rows="3" placeholder="Delivery instructions (optional)">{{ old('notes') }}</x-textarea>
                </x-card>
            </div>

            <aside class="h-fit space-y-4 lg:sticky lg:top-28">
                <div class="rounded-[var(--radius-panel)] border border-line/70 bg-surface-elevated p-6 shadow-soft">
                    <h2 class="font-display text-xl font-bold text-ink">Order summary</h2>

                    <ul class="mt-4 divide-y divide-line/60 text-sm">
                        @foreach ($summary['lines'] as $line)
                            <li class="flex items-start justify-between gap-3 py-3">
                                <div>
                                    <p class="font-medium text-ink">{{ $line['product']?->name }}</p>
                                    <p class="text-ink-muted">Qty {{ $line['quantity'] }} · {{ $line['unit_formatted'] }}</p>
                                </div>
                                <p class="font-semibold text-ink">{{ $line['line_formatted'] }}</p>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-2 space-y-3 border-t border-line/60 pt-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-muted">Subtotal</dt>
                            <dd class="font-semibold text-ink">{{ $quote['subtotal_formatted'] }}</dd>
                        </div>
                        @if ($quote['discount_cents'] > 0)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">
                                    Discount
                                    @if ($summary['coupon_code'])
                                        <span class="block text-xs text-ink-subtle">{{ $summary['coupon_code'] }}</span>
                                    @endif
                                </dt>
                                <dd class="font-semibold text-accent">−{{ $quote['discount_formatted'] }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-muted">Shipping</dt>
                            <dd class="font-semibold text-ink">
                                {{ $quote['shipping_is_free'] ? 'Free' : $quote['shipping_formatted'] }}
                            </dd>
                        </div>
                        @if ($quote['tax_cents'] > 0 || (float) config('store.tax.rate_percent') > 0)
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">Tax</dt>
                                <dd class="font-semibold text-ink">{{ $quote['tax_formatted'] }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3 border-t border-line/60 pt-3">
                            <dt class="font-semibold text-ink">Total</dt>
                            <dd class="font-display text-xl font-bold text-ink">{{ $quote['total_formatted'] }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-xs text-ink-subtle">
                        Flat shipping {{ \App\Support\Money::format((int) config('store.shipping.flat_rate_cents')) }};
                        free over {{ \App\Support\Money::format((int) config('store.shipping.free_over_cents')) }}.
                        COD orders reserve inventory when placed.
                    </p>

                    @error('cart')
                        <p class="mt-3 text-sm text-danger" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 space-y-3">
                        <x-button type="submit" variant="primary" class="w-full">Place order</x-button>
                        <x-button href="{{ route('cart.index') }}" variant="outline" class="w-full">Back to cart</x-button>
                    </div>
                </div>
            </aside>
        </form>
    </x-container>
@endsection
