@extends('layouts.admin')

@section('title', 'Settings')
@section('breadcrumb', 'Settings')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Settings</h1>
        <p class="mt-1 text-sm text-ink-muted">Store configuration, shipping rules, tax inputs, and basic site settings.</p>
    </div>
@endsection

@section('content')
    @include('admin.settings._nav')

    <form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
        @csrf
        @method('PUT')

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Store</h2>
                <p class="admin-form__section-desc">Currency used for new orders and formatted totals.</p>
            </div>
            <div class="admin-form-grid">
                <x-input
                    label="Currency code"
                    name="currency"
                    :value="old('currency', $currency)"
                    maxlength="3"
                    required
                    help="ISO currency code. Use PKR for Pakistani rupees."
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Shipping</h2>
                <p class="admin-form__section-desc">Checkout shipping quotes use these values immediately after save.</p>
            </div>
            <div class="admin-form-grid">
                <x-select label="Shipping method" name="shipping_method" required help="Weight-based rates still come from config until a full rate table editor is added.">
                    <option value="flat" @selected(old('shipping_method', $shippingMethod) === 'flat')>Flat rate</option>
                    <option value="weight" @selected(old('shipping_method', $shippingMethod) === 'weight')>Weight-based</option>
                </x-select>
                <x-input
                    label="Flat shipping rate"
                    name="flat_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('flat_rate', $flatRate)"
                    required
                    help="Used when method is flat rate (for example 5.99)."
                />
                <x-input
                    label="Free shipping over"
                    name="free_over"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('free_over', $freeOver)"
                    required
                    help="Merchandise total after discounts (for example 75.00)."
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Tax</h2>
                <p class="admin-form__section-desc">Applied to merchandise plus shipping at checkout.</p>
            </div>
            <div class="admin-form-grid">
                <x-input
                    label="Tax rate (%)"
                    name="tax_rate_percent"
                    type="number"
                    step="0.001"
                    min="0"
                    max="100"
                    :value="old('tax_rate_percent', $taxRatePercent)"
                    required
                    help="Enter 0 for no tax. Example: 8.25"
                />
            </div>
        </x-card>

        <div class="admin-form__actions">
            <x-button type="submit" variant="primary">Save settings</x-button>
            <x-button href="{{ route('admin.dashboard') }}" variant="outline">Back to dashboard</x-button>
        </div>
    </form>
@endsection
