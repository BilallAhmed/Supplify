@php
    $isEdit = $coupon->exists;
    $type = old('type', $coupon->type?->value ?? 'percent');
    $valueDisplay = old('value');
    if ($valueDisplay === null) {
        $valueDisplay = ($coupon->type?->value ?? 'percent') === 'fixed'
            ? ($coupon->value !== null ? \App\Support\Money::toDecimalString((int) $coupon->value) : '')
            : ($coupon->value ?? '');
    }
    $minSubtotal = old('min_subtotal');
    if ($minSubtotal === null && $coupon->min_subtotal_cents !== null) {
        $minSubtotal = \App\Support\Money::toDecimalString((int) $coupon->min_subtotal_cents);
    }
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
    class="mx-auto max-w-2xl space-y-4"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-card class="space-y-4">
        <x-input label="Code" name="code" required help="Letters, numbers, hyphens, and underscores. Stored uppercase." :value="old('code', $coupon->code)" />

        <x-select label="Type" name="type" required>
            @foreach (\App\Enums\CouponType::cases() as $case)
                <option value="{{ $case->value }}" @selected((string) $type === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </x-select>

        <x-input
            label="Value"
            name="value"
            required
            help="Percent: 1–100. Fixed: dollar amount (e.g. 5.00)."
            :value="$valueDisplay"
        />

        <x-input
            label="Minimum subtotal (USD)"
            name="min_subtotal"
            help="Optional. Cart must reach this before the code applies."
            :value="$minSubtotal"
        />

        <x-input
            label="Max uses"
            name="max_uses"
            type="number"
            min="1"
            help="Leave blank for unlimited."
            :value="old('max_uses', $coupon->max_uses)"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input
                label="Starts at"
                name="starts_at"
                type="datetime-local"
                :value="old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i'))"
            />
            <x-input
                label="Ends at"
                name="ends_at"
                type="datetime-local"
                :value="old('ends_at', optional($coupon->ends_at)->format('Y-m-d\TH:i'))"
            />
        </div>

        <x-checkbox
            name="is_active"
            value="1"
            label="Active"
            :checked="(bool) old('is_active', $coupon->is_active ?? true)"
        />
    </x-card>

    <div class="flex flex-wrap gap-3">
        <x-button type="submit" variant="primary">{{ $isEdit ? 'Save changes' : 'Create coupon' }}</x-button>
        <x-button href="{{ route('admin.coupons.index') }}" variant="ghost">Cancel</x-button>
    </div>
</form>
