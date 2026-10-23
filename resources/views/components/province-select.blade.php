@props(['name', 'value' => null, 'country' => 'PK', 'required' => false, 'autocomplete' => 'address-level1'])
<x-select label="Province / territory" :name="$name" :required="$required" :autocomplete="$autocomplete">
    <option value="" disabled @selected(! $value)>Select a province / territory</option>
    @foreach (\App\Support\DeliveryRegions::forCountry($country) as $province)
        <option value="{{ $province }}" @selected($value === $province)>{{ $province }}</option>
    @endforeach
</x-select>
