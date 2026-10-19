@props([
    'padding' => true,
    'hover' => false,
])

@php
    $classes = 'rounded-[var(--radius-panel)] border border-line/80 bg-surface-elevated shadow-soft';
    if ($padding) {
        $classes .= ' p-5 sm:p-6';
    }
    if ($hover) {
        $classes .= ' transition duration-200 hover:-translate-y-0.5 hover:shadow-lift';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
