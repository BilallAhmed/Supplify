@props([
    'variant' => 'neutral',
])

@php
    $styles = [
        'neutral' => 'bg-surface-muted text-ink-muted',
        'brand' => 'bg-brand-soft text-brand',
        'accent' => 'bg-accent-soft text-ink',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-[var(--radius-control)] px-2 py-0.5 text-xs font-medium '.($styles[$variant] ?? $styles['neutral']),
]) }}>
    {{ $slot }}
</span>
