@php $isEdit = $address->exists; @endphp

<form
    method="POST"
    action="{{ $isEdit ? route('account.addresses.update', $address) : route('account.addresses.store') }}"
    class="space-y-4"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-select label="Address type" name="type" required>
        @foreach (['shipping' => 'Shipping', 'billing' => 'Billing', 'both' => 'Shipping & billing'] as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $address->type) === $value)>{{ $label }}</option>
        @endforeach
    </x-select>

    <x-input label="Full name" name="name" required :value="old('name', $address->name)" autocomplete="name" />
    <x-input label="Address line 1" name="line1" required :value="old('line1', $address->line1)" autocomplete="address-line1" />
    <x-input label="Address line 2" name="line2" :value="old('line2', $address->line2)" autocomplete="address-line2" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input label="City" name="city" required :value="old('city', $address->city)" autocomplete="address-level2" />
        <x-province-select name="state" required :value="old('state', $address->state)" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input label="Postal code" name="postal_code" required :value="old('postal_code', $address->postal_code)" autocomplete="postal-code" />
        <x-select label="Country" name="country" required autocomplete="country"><option value="PK" selected>Pakistan</option></x-select>
    </div>
    <x-input label="Phone" name="phone" :value="old('phone', $address->phone)" autocomplete="tel" />
    <x-checkbox
        name="is_default"
        value="1"
        label="Use as default address"
        :checked="(bool) old('is_default', $address->is_default)"
    />

    <div class="flex flex-wrap gap-3 pt-2">
        <x-button type="submit" variant="primary">{{ $isEdit ? 'Save address' : 'Add address' }}</x-button>
        <x-button href="{{ route('account.addresses.index') }}" variant="ghost">Cancel</x-button>
    </div>
</form>
