@props([
    'value' => null,
    'for' => null,
    'required' => false,
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'ui-label']) }}
>
    <span>{{ $value ?? $slot }}</span>
    @if ($required)
        <span class="ui-label__required" aria-hidden="true">*</span>
        <span class="sr-only">(required)</span>
    @endif
</label>
