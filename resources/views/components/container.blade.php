@props([
    'as' => 'div',
])

<{{ $as }} {{ $attributes->merge(['class' => 'ui-container']) }}>
    {{ $slot }}
</{{ $as }}>
