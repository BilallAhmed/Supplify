@props([
    'value' => null,
    'for' => null,
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'ui-label']) }}
>
    {{ $value ?? $slot }}
</label>
